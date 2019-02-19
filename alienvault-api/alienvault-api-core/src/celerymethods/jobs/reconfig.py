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
from celery.utils.log import get_logger

from celerymethods.tasks import celery_instance
import time
from celery.task.control import inspect
from celery import current_task
import ast
import traceback

from ansiblemethods.system.system import reconfigure

logger = get_logger("celery")


def job_alienvault_reconfigure(system_ip):
    """
    Launch alienvault reconfigure job
    """
    job = alienvault_reconfigure.delay(system_ip)
    if job.state is 'FAILURE':
        return (False, "Error configuring the sensor")

    return (True, job.id)


@celery_instance.task
def alienvault_reconfigure(system_ip):
    """Runs an alienvault reconfig

    :param system_ip: The system IP where we would like to run the alienvault-reconfig
    :return: A tuple (success, error_message).
    """
    # Alienvault Reconfig Help
    # AlienVault-reconfig 0.0.2 release 1 Help (linux, perl 5.010001)
    #
    #Usage examples:
    #  ossim-reconfig [options]
    #
    #Command line options:
    #
    #    --help (or -h)
    #      Displays this help message.
    #
    #    --console-log (or -c)
    #      Enable logging messages to console.
    #
    #    --verbose (or -v)
    #      Enable verbose mode.
    #
    #    --debug (or -d)
    #      Enable debug mode. (insane)
    #
    #    --quiet (or -q)
    #      Quiet mode.
    #
    #    --add_sensor (or -a) + --add_sensor_name (or -an)
    #      Add a new sensor to database
    #
    #    --add_vpnnode
    #      Create a new vpn node
    #
    #    --update_sensors
    #      Update sensors config from ddbb info. (only for cron.d)
    #
    #    --mysql_replication
    #      MySQL dump and replication with binary log coordinates
    #
    #For more info, please visit http://www.alienvault.com/

    # At the moment we only need to run the alienvault-reconfig without any input parameter
    # So we won't consider any of them.
    rt = True
    error_str = ""
    logger.info("Alienvault Reconfig Running")
    try:
        #
        # Get the current task_id
        request = current_task.request
        my_task_id = request.id #alienvault_reconfigure.request.id
        i = inspect()
        my_start_time = time.time()
        tasks_time_start = {}
        keep_waiting = True
        while keep_waiting:
            tasks_time_start.clear()
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
                    if task['name'].find('alienvault_reconfigure') > 0:
                        task_system_ip = ast.literal_eval(task['args'])[0]#system ip
                        if not tasks_time_start.has_key(task_system_ip):
                            tasks_time_start[task_system_ip] = []
                        tasks_time_start[task_system_ip].append(task_time)

            prior_task = False
            for task_ip ,ttime_list in tasks_time_start.iteritems():
                if str(system_ip) == str(task_ip):
                    for ttime in ttime_list:
                        if ttime != my_start_time and ttime < my_start_time:
                            prior_task = True
                            break

            if prior_task:
                logger.info("A reconfig is running....waiting [%s]" % my_task_id)
                time.sleep(1)
            else:
                keep_waiting = False

        rt, error_str = reconfigure(system_ip)
        logger.info("Running alienvault-reconfig ... end %s" % my_task_id)

    except Exception, e:
        logger.error("An error occurred running alienvault-reconfig: %s, %s" % (str(e), traceback.format_exc()))
        rt = False
        error_str = "%s" % str(e)
    return rt, error_str
