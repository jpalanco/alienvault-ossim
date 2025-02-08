#
#  License:
#
#  Copyright (c) 2013 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

from celery.utils.log import get_logger
from celerymethods.tasks import celery_instance
from ansiblemethods.ansibleinventory import CheckAnsibleInventory
from api.lib.monitors.triggers import CheckTriggers
from api.blueprints.central_console.central_console import build_console_service
from infrastructure.shared_kernel.config.domain_services.abstract_config_repository.alchemy_config_repository import \
    AlchemyConfigRepository
from shared_kernel.config.models.config import Config
from db.methods.system import get_system_id_from_local, get_children_servers
from db.methods.server import get_server_id_from_local
from db.methods.data   import get_local_alarms
from apimethods.system.system import sync_database_from_child
from apimethods.system.system import get_child_alarms, resend_alarms
from apimethods.system.engine import update_engine_stats

from celery.task.control import inspect
from celery import current_task
import time
from celery_once.tasks import QueueOnce

logger = get_logger("celery")


@celery_instance.task
def check_ansible_components():
    """Checks the alienvault-api components"""
    rt = True
    try:
        check = CheckAnsibleInventory()
        if not check.process():
            rt = False
        del check
    except Exception, e:
        logger.error("An error occurred while running the 'check_ansible_components' %s" % str(e))
        rt = False
    return rt


@celery_instance.task
def run_triggers():
    rt = True
    try:
        trigger = CheckTriggers()
        if not trigger.start():
            rt = False
        del trigger
    except Exception, e:
        logger.error("An error occurred while running the 'check_ansible_components' %s" % str(e))
        rt = False
    return rt


@celery_instance.task
def sync_databases():
    """Celery task to sync database with data from children

    Returns:
        bool: True if successful, False otherwise
        Message (str): Error description (if any)

    """
    # Check if there is already one instance of this task already running
    # @TODO This check should be refactored and put into a generic function for reuse
    # as it's been borrowed from celerymethods.jobs.reconfig.py
    rt = True
    logger.info("Sync Databases")
    try:
        # Get the current task_id
        request = current_task.request
        my_task_id = request.id  # sync_databases.request.id
        i = inspect()
        my_start_time = time.time()
        tasks_time_start = []
        active_taks = i.active()
        for node, tasks_list in active_taks.iteritems():
            for task in tasks_list:
                # Sample task
                # {u'args': u"(u'192.168.5.119',)", u'time_start': 1381734702.1942401,
                # u'name': u'api.jobs.reconfig.alienvault_reconfigure', u'delivery_info':
                # {u'routing_key': u'celery', u'exchange': u'celery'}, u'hostname': u'w1.alienvault',
                # u'acknowledged': True, u'kwargs': u'{}',
                # u'id': u'9c83c664-5d8a-4daf-ac2c-532c0209a734', u'worker_pid': 26497}
                task_time = task['time_start']
                if task['id'] == my_task_id:
                    my_start_time = task_time
                if task['name'].find('sync_databases') > 0:
                    tasks_time_start.append(task_time)

        prior_task = False
        for ttime in tasks_time_start:
            if ttime != my_start_time and ttime < my_start_time:
                prior_task = True
                break
    except Exception, e:
        logger.error("An error occurred running sync_databases: %s" % (str(e)))
        return False, str(e)

    if prior_task:
        logger.info("Sync Databases: A sync task is already running. Bailing out")
        return True, "A sync task is already running. Bailing out"

    (success, local_id) = get_server_id_from_local()
    if not success:
        logger.error("Can't retrieve system_id.")
        return False, "Can't retrieve system_id."

    success, server_list = get_children_servers(local_id)
    if not success:
        logger.error("Can't retrieve children system list.")
        return False, "Can't retrieve children system list."

    for server_id in server_list:
        try:
            logger.debug("Trying to sync database from server %s" % server_id)
            (success, msg) = sync_database_from_child(server_id)
            if not success:
                logger.debug("Sync database from server %s failed: %s" % (server_id, str(msg)))
            rt &= success
        except Exception, e:
            logger.error("An error occurred while tryng to sync database from server %s: %s" % (server_id, str(e)))
            rt = False

    return rt, ""


@celery_instance.task
def update_ctx_stats():
    """
        Update the RRD from stats info
    """
    success, data = ret = update_engine_stats()
    if  not success:
        return ret
    else:
        return True, ''


@celery_instance.task
def usm_central_status():
    """
    Check the USM Central status

    If the USM Central token is not present in the Config
    it means that integration is not configured and status check
    will be skipped.
    """
    config_repository = AlchemyConfigRepository(Config)
    usm_central_token = config_repository.get_config('usm_central_token')
    if usm_central_token is None:
        return 'Integration with USM Central is not configured'

    try:
        response = build_console_service().send_console_status()
        logger.info('usm_central_status: response status: %s' % response.status)
        res = response.status
    except Exception as e:
        logger.exception('Error in usm_central_status:')
        res = str(e)

    return res


class ExcQueueOnce(QueueOnce):

    def apply_async(self, args=None, kwargs=None, **options):
        try:
            return super(ExcQueueOnce, self).apply_async(args=None, kwargs=None, **options)
        except self.AlreadyQueued:
            logger.warning('Task already exist')
            return type('obj', (object,), {'id': None})


@celery_instance.task(base=ExcQueueOnce)
def forward_check():
    """
        Check the forward events for consistence issues

        Returns:
            bool: True if successful, False otherwhise
            Message (str): Error description (if any)
    """
    logger.info("Forward consistence check")
    (success, local_id) = get_server_id_from_local()
    if not success:
        logger.error("Can't retrieve system_id.")
        return False, "Can't retrieve system_id."

    success, server_list = get_children_servers(local_id)
    if not server_list:
        return True, ''
    if not success:
        logger.error("Can't retrieve children system list.")
        return False, "Can't retrieve children system list."
    # Get the current alarms
    # Check 8 hours back
    success, local_alarms = get_local_alarms(delay=1, delta=3)

    # local_alarms => hash, key => event_id data => server_id
    if not success:
        logger.error("Can't retrieve local alarms")
        return False, "Can't retrieve local alarms"
    for server in server_list:
        success, child_alarms = get_child_alarms(server, delay=1, delta=3)

        if not success:
            logger.error("Can't retrieve remote alarms for server '%s'. Skiping" % str(server))
            continue
        missing_alarms = [event_id for event_id in child_alarms if event_id not in local_alarms]

        if missing_alarms:  # This check is list is empty
            success, message = resend_alarms(server, missing_alarms)
            if not success:
                logger.error("Can't resend remote alarams from server '%s': %s. Skiping" % (message, str(server)))

    return True, ""
