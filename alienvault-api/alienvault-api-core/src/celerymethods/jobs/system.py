# -*- coding: utf-8 -*-
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

import time
import traceback
from celery.utils.log import get_logger
from celery import current_task
from celerymethods.tasks import celery_instance
from celerymethods.utils import exist_task_running, JobResult
from celerymethods.errors import SYSTEM_UPDATE_ERROR_STRINGS

from apimethods.system.cache import flush_cache

from ansiblemethods.system.system import ansible_run_async_reconfig
from ansiblemethods.system.system import ansible_run_async_update
from ansiblemethods.system.system import ansible_check_if_process_is_running
from ansiblemethods.system.system import ansible_get_asynchronous_command_log_file
from ansiblemethods.system.system import ansible_check_asynchronous_command_return_code

logger = get_logger("celery")


@celery_instance.task
def alienvault_asynchronous_reconfigure(system_ip, new_system_ip):
    """Runs an asynchronous  alienvault reconfig
    Args:
      system_ip (str): The system IP where we would like to run the alienvault-reconfig
      new_system_ip (str): The new system admin IP. This is used for the case of changing the ansible IP
    Returns:
        JobResult (dict): where obj['result'] == True if success or False otherwise.
    """
    running = exist_task_running(task_type='alienvault_asynchronous_reconfigure',
                                 current_task_request=current_task.request,
                                 param_to_compare=system_ip,
                                 argnum=0)
    if running:
        return JobResult(False, "An existing task running", "", "0").serialize

    try:
        logger.info("Start asynchronous reconfigure <%s>" % system_ip)
        (success, log_file) = ansible_run_async_reconfig(system_ip)
        if not success:
            logger.error("Error running alienvault reconfig: %s" % log_file)
            return JobResult(False,
                             "Something wrong happened while running alienvault reconfig %s" % log_file,
                             log_file, "0").serialize

        logger.info("reconfigure <%s> waiting to finish...." % system_ip)

        # Wait until the task is launched.
        time.sleep(1)

        # Wait until the process is finished
        n_process = 1
        while n_process > 0:
            (success, n_process) = ansible_check_if_process_is_running(system_ip, log_file)
            if not success:
                if new_system_ip is not None and system_ip != new_system_ip:
                    system_ip = new_system_ip
                else:
                    logger.error("Cannot retrieve the process status from %s" % system_ip)
                    return JobResult(False,
                                     "Cannot retrieve the process status from %s" % system_ip,
                                     log_file, "0").serialize
            time.sleep(1)

        logger.info("Running alienvault-reconfig ... end %s - %s" % (success, log_file))

        # Get the log file
        (success, log_file_path) = ansible_get_asynchronous_command_log_file(system_ip, log_file)
        if not success:
            return JobResult(
                False,
                "Something wrong happened while retrieving the alienvault-reconfig log file %s" % log_file_path,
                log_file, "0"
            ).serialize

        # Get the return code
        success, return_code_msg = ansible_check_asynchronous_command_return_code(system_ip, log_file + ".rc")
        if not success:
            logger.error(
                "Something wrong happened while retrieving the alienvault-reconfig return code %s" % return_code_msg)
            return JobResult(False,
                             "Something wrong happened while retrieving the return code %s" % return_code_msg,
                             log_file, "0").serialize

    except Exception, e:
        logger.error("An error occurred running alienvault-reconfig: %s, %s" % (str(e), traceback.format_exc()))
        return JobResult(False, "An error occurred running alienvault-reconfig:  %s" % (str(e)), "", "0").serialize

    return JobResult(True, "Success!!", log_file_path, "0").serialize


@celery_instance.task
def alienvault_asynchronous_update(system_ip, only_feed=False, update_key=""):
    """Runs an asynchronous  alienvault update
    Args:
      system_ip (str): The system IP where we would like to run the alienvault-update
      only_feed (boolean): A boolean indicating whether we should update only the feed or not.
      update_key (str): Upgrade key.
    Returns:
      JobResult (dict): where obj['result'] == True if success or False otherwise.
    """
    running = exist_task_running(task_type='alienvault_asynchronous_update',
                                 current_task_request=current_task.request,
                                 param_to_compare=system_ip,
                                 argnum=0)
    if running:
        return JobResult(False, "An existing task running", "", "300090", system_ip=system_ip).serialize

    try:
        logger.info("Start asynchronous update <%s>" % system_ip)

        rt, error_str = ansible_run_async_update(system_ip, only_feed=only_feed, update_key=update_key)
        # When the task has been launched properly the error_str variable will contain the log file.
        if not rt:
            error_msg = "Something wrong happened while running the alienvault update %s" % error_str
            if 'unreachable' in error_msg:
                error_msg = 'System unreachable'
            return JobResult(False, error_msg, "", "300091", system_ip=system_ip).serialize
        logger.info(" alienvault-update <%s> waiting to finish...." % system_ip)
        time.sleep(1)  # Wait until the task is launched.
        n_process = 1
        while int(n_process) > 0:
            success, n_process = ansible_check_if_process_is_running(system_ip, error_str)
            time.sleep(1)

        flush_cache(namespace='system_packages')

        rt, log_file = ansible_get_asynchronous_command_log_file(system_ip, error_str)
        if not rt:
            return JobResult(
                False,
                "Something wrong happened while retrieving the alienvault-update log file: %s" % log_file,
                "", "300092", system_ip=system_ip).serialize
        rt, return_code_msg = ansible_check_asynchronous_command_return_code(system_ip, error_str+".rc")
        if not rt:
            error_msg = "Something wrong happened while retrieving the alienvault-return code <%s>" % return_code_msg
            error_id = "300093"
            if return_code_msg.startswith("Return code is different from 0"):
                return_code = return_code_msg.split("<")[1].split(">")[0]
                error_msg = SYSTEM_UPDATE_ERROR_STRINGS[return_code][1]
                error_id = SYSTEM_UPDATE_ERROR_STRINGS[return_code][0]

            return JobResult(False, error_msg, log_file, error_id, system_ip=system_ip).serialize
        logger.info("Running alienvault-update ... end %s - %s" % (rt, error_str))

    except Exception, e:
        logger.error("An error occurred running alienvault-update: %s, %s" % (str(e), traceback.format_exc()))
        return JobResult(False, "An error occurred running alienvault-update <%s>" % str(e), "", "300099").serialize
    return JobResult(True, "Success!!", log_file, "0", system_ip=system_ip).serialize
