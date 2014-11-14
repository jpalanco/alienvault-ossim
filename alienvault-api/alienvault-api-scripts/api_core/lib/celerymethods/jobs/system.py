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
import ast
import traceback
from celery.utils.log import get_logger
from celery.task.control import inspect
from celery import current_task
from celerymethods.tasks import celery_instance
from celerymethods.utils import exist_task_running, JobResult
from apimethods.system.system import asynchronous_reconfigure as api_run_reconfigure
from apimethods.system.system import asynchronous_update as api_run_update
from apimethods.system.system import check_if_process_is_running as api_check_if_process_is_running
from apimethods.system.system  import apimethod_get_asynchronous_command_log_file
from apimethods.system.system  import apimethod_check_asynchronous_command_return_code
from apimethods.system.cache import flush_cache
logger = get_logger("celery")

@celery_instance.task
def alienvault_asynchronous_reconfigure(system_id):
    """Runs an asynchronous  alienvault reconfig
    Args:
      system_id (str): The system ID where we would like to run the alienvault-reconfig
    Returns:
      rt (boolean): True if success false otherwise
    , job_log=job_log"""
    if exist_task_running(task_type='alienvault_asynchronous_reconfigure', current_task_request=current_task.request, param_to_compare=system_id, argnum=0):
        return JobResult(False, "An existing task running", "").serialize

    try:
        logger.info("Start asynchronous reconfigure <%s>" % system_id)
        rt, error_str = api_run_reconfigure(system_id)
        # -- When the task has been launched properly the error_str variable will contain the log file.
        if not rt:
            return JobResult(False, "Something wrong happend while running the alienvault reconfig %s" % error_str, "").serialize

        logger.info("reconfigure <%s> waiting to finish...." % system_id)
        time.sleep(1) # Wait until the task is lauched.
        n_process = 1
        while n_process > 0:
            success, n_process = api_check_if_process_is_running(system_id, error_str)
            time.sleep(1)
        logger.info("Running alienvault-reconfig ... end %s - %s" % (rt, error_str))

        rt, log_file = apimethod_get_asynchronous_command_log_file(system_id, error_str)
        if not rt:
            return JobResult(False, "Something wrong happend while retrieving the alienvault-reconfig log file %s" % log_file, "").serialize
        rt, return_code_msg = apimethod_check_asynchronous_command_return_code(system_id, error_str+".rc")
        if not rt:
            return JobResult(False, "Something wrong happend while retrieving the return code", log_file).serialize

    except Exception, e:
        logger.error("An error occurred running alienvault-reconfig: %s, %s" % (str(e), traceback.format_exc()))
        return JobResult(False, "An error occurred running alienvault-reconfig:  %s" % (str(e)), "").serialize
    return JobResult(True, "Success!!", log_file).serialize


@celery_instance.task
def alienvault_asynchronous_update(system_id, only_feed=False,update_key=""):
    """Runs an asynchronous  alienvault update
    Args:
      system_id (str): The system ID where we would like to run the alienvault-update
      only_feed (boolean): A boolean indicatin whether we should update only the feed or not.
    Returns:
      rt (boolean): True if success false otherwise
    """
    if exist_task_running(task_type='alienvault_asynchronous_update',current_task_request=current_task.request, param_to_compare=system_id,argnum=0):
        return JobResult(False, "An existing task running","").serialize

    try:
        logger.info("Start asynchronous update <%s>" % system_id)
        rt, error_str = api_run_update(system_id, only_feed=only_feed,update_key=update_key)
        # When the task has been launched properly the error_str variable will contain the log file. 
        if not rt:
            return JobResult( False, "Something wrong happend while running the alienvault update %s" % error_str,"").serialize
        logger.info(" alienvault-update <%s> waiting to finish...." % system_id)
        time.sleep(1) # Wait until the task is lauched.
        n_process = 1
        while n_process > 0:
            success,n_process = api_check_if_process_is_running(system_id, error_str)
            time.sleep(1)

        rt, log_file = apimethod_get_asynchronous_command_log_file(system_id, error_str)
        if not rt:
            return JobResult(False, "Something wrong happend while retrieving the alienvault-update log file %s" % log_file,"").serialize
        rt, return_code_msg = apimethod_check_asynchronous_command_return_code(system_id,error_str+".rc")
        if not rt:
            return JobResult(False,"Something wrong happend while retrieving the alienvault-return code <%s>" % str(return_code_msg) , log_file).serialize
        flush_cache(namespace="system_packages")
        logger.info("Running alienvault-update ... end %s - %s" % (rt,error_str))

    except Exception, e:
        logger.error("An error occurred running alienvault-reconfig: %s, %s" % (str(e), traceback.format_exc()))
        return JobResult(False, "An error occurred running alienvault-update <%s>" % str(e),"").serialize
    return JobResult(True,"Success!!",log_file).serialize
