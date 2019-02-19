# -*- coding: utf-8 -*-
#
# License:
#
#  Copyright (c) 2015 AlienVault
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
##

import time
import ast
import redis
from celery.utils.log import get_logger
from celery.task.control import inspect
from celery.task.control import revoke
from amqplib import client_0_8 as amqp
import json
from celeryconfig import CELERY_QUEUES
from ansiblemethods.system.system import ansible_get_process_pid
import api_log


logger = get_logger("celery")
redis_instance = redis.Redis("localhost")


class JobResult:
    def __init__(self, result, message, log_file="", error_id="0", system_ip=""):
        self.result = result
        self.message = message
        self.log_file = log_file
        self.error_id = error_id
        self.system_ip = system_ip

    @property
    def serialize(self):
        return {'result': self.result,
                'message': self.message,
                'log_file': self.log_file,
                'system_ip': self.system_ip,
                'error_id': self.error_id}


def exist_task_running(task_type, current_task_request, param_to_compare=None, argnum=0):
    """
    Check if there is any task of type 'task_type' running for the
    given system.

    If the param_to_compare is None, returns only if there is a task
    of type <task_type> running.

    In order to find a task running in a <param_to_compare>, you should
    specify which argument of the task represent the <param_to_compare>.

    For example:
    If the task was launched by running:
    alienvault_reconfigure("192.168.5.134") -> args[0] will be
    system ip, so you should specify argnum=0

    Args:
        task_type (str): The kind of task to look for (usually the method
                         name)
        current_task_request(): The current task request (current_task.request
                                from the caller)
        param_to_compare (str or None): Parameter to compare whithin the task,
                                        for example the system ip or
                                        the system id.
        argnum (int): The argument number where we can find the system ip
                      if needed.
    Returns:
        rt (True or False): True when a task matching the given criteria
                            is running, false otherwise.
    """
    try:
        # Get the current task_id
        # alienvault_reconfigure.request.id
        current_task_id = current_task_request.id
        i = inspect()
        current_task_start_time = time.time()
        task_list = []
        # Retrieve the list of active tasks.
        active_taks = i.active()
        for node, tasks_list in active_taks.iteritems():
            for task in tasks_list:
                # Is this task of the given type?
                if task['id'] == current_task_id:
                    current_task_start_time = float(task['time_start'])
                if task['name'].find(task_type) > 0:
                    task_list.append(task)

        previous_task_running = False
        for task in task_list:
            # 1 - Is my own task?
            if task['id'] == current_task_id:
                continue
            task_start_time = task['time_start']
            # 2 - if not, Does the task started before the current one?
            started_before_the_current_one = (task_start_time != current_task_start_time) and \
                                             (task_start_time < current_task_start_time)
            if started_before_the_current_one and \
                            param_to_compare is None:  # An existing task is running
                previous_task_running = True
                break

            # 3 - Does the task running in the same system?
            task_param_value = ast.literal_eval(task['args'])[argnum]
            if str(task_param_value) == str(param_to_compare) and \
                    started_before_the_current_one:
                previous_task_running = True
                break

        if previous_task_running:
            info_msg = "A %s is running" % task_type + \
                       "....waiting [%s]" % current_task_id
            logger.info(info_msg)

    except Exception, e:
        logger.error("An error occurred %s" % (str(e)))
        return True
    return previous_task_running


def find_task_in_worker_list(celery_task_list, my_task):
    """
    Check if there is any task of type 'type' running or pending
    in a worker list.

    The celery_task_list corresponds to the list given from the call:
        i = inspect()
        i.active().values() or
        i.scheduled().values() or
        i.reserved().values()

    The task has the following format:
        {'task': <name of the celery task>,
         'process': <name of the process>,
         'param_value': <task condition>,
         'param_argnum': <position of the condition>}

    If the 'param_value' is None, it returns only if there is a task of type
    'task' found within the given list.

    In order to find a task running in a <param_value>, you should specify
    which argument from the task represents the <param_value>.

    For example:
    If the task was launched by running:
    alienvault_reconfigure("192.168.5.134") -> args[0] will be
    the system ip, so you should specify 'param_argnum':0

    Args:
        celery_task_list (list) : The list of task from a worker list
        my_task (dict)          : The task we want to look for.

    Returns:
        success (bool) : True if the task was found in the list,
                         False otherwise
        job_id (str)   : Job ID of the task

    """
    success = False
    job_id = 0

    for tasks_list in celery_task_list:
        for task in tasks_list:
            if task['name'].find(my_task['task']) > 0:
                if my_task['param_value'] is None:
                    success = True
                    job_id = task['id']
                    break
                else:
                    try:
                        task_param_value = ast.literal_eval(task['args'])[my_task['param_argnum']]
                    except IndexError:
                        task_param_value = ''

                    if str(task_param_value) == str(my_task['param_value']):
                        success = True
                        job_id = task['id']
                        break

    return success, job_id


def get_task_status(system_id, system_ip, task_list):
    """
    Check if there is any task within the 'task_list' running or pending
    for the given system.

    The format of the list of tasks to check is the following:
    {
        <Name of the task>: {'task': <name of the celery task>,
                             'process': <name of the process>,
                             'param_value': <task condition>,
                             'param_argnum': <position of the condition>}
    }

    Args:
        system_id (str) : The system_id where you want to check
                          if it's running
        system_ip (str) : The system_ip where you want to check
                          if it's running
        task_list (dict): The list of tasks to check.

    Returns:
        success (bool) : True if successful, False otherwise
        result (dict)  : Dic with the status and the job id for each task.

    """
    result = {}

    try:
        i = inspect()
        # Retrieve the list of active tasks.
        active = i.active()
        pending = i.scheduled()
        reserved = i.reserved()
        running_tasks = []
        pending_tasks = []
        if active is not None:
            running_tasks = active.values()
        if pending is not None:
            pending_tasks = pending.values()
        if reserved is not None:
            pending_tasks.extend(reserved.values())
            # Retrieve the list of pending tasks.
    except Exception, e:
        error_msg = "[celery.utils.get_task_status]: An error occurred: " + \
                    "%s" % (str(e))
        logger.error(error_msg)
        return False, {}

    # For each task we are going to check its status
    for name, my_task in task_list.iteritems():
        # Default status is not running
        result[name] = {"job_id": 0, "job_status": "not_running"}

        # Is the task in the list of active tasks?
        success, job_id = find_task_in_worker_list(running_tasks, my_task)
        if success:
            result[name]['job_status'] = "running"
            result[name]['job_id'] = job_id
            continue

        # Is the task in the list of pending tasks?
        success, job_id = find_task_in_worker_list(pending_tasks, my_task)
        if success:
            result[name]['job_status'] = "pending"
            result[name]['job_id'] = job_id
            continue

        # Is the task process running?
        success, pid = ansible_get_process_pid(system_ip, my_task['process'])
        if success:
            result[name]['job_status'] = "running" if pid > 0 else "not_running"
            result[name]['job_id'] = pid
            continue
        else:
            warning_msg = "Cannot retrieve the process pid %s: " % success + \
                          "%s" % pid
            logger.warning(warning_msg)
            return False, {}

    return True, result


def get_running_tasks(system_ip):
    try:
        i = inspect()
        tasks = i.active()
    except Exception as e:
        error_msg = "[celery.utils.get_running_tasks]: " + \
                    "An error occurred: %s" % (str(e))
        logger.error(error_msg)
        return False, {}
    return (True, tasks)


def stop_running_task(task_id, force=True):
    """Terminates the given task
    Args:
        task_id(str): The task id you want to stop
        force(boolean): You want to force the stop
    """
    try:
        if force:
            revoke(task_id, terminate=True, signal='SIGKILL')
        else:
            revoke(task_id, terminate=True)
    except Exception as e:
        return False, str(e)
    return True, ""


def is_task_in_rabbit(task_id):
    """Checks if the task is in rabbit or not. If the task is found returns the json data, otherwise returns none
    {u'retries': 0, u'task': u'celerymethods.tasks.monitor_tasks.monitor_retrieves_remote_info', u'eta': None, u'args': [], u'expires': None, u'callbacks': None, u'errbacks': None, u'kwargs': {}, u'id': u'de1cd3b1-d001-4bea-a050-8ffe610bee21', u'utc': False}
    """
    try:
        conn = amqp.Connection(host="localhost:5672 ", userid="guest", password="guest", virtual_host="/", insist=False)
        chan = conn.channel()

        # Inspect all available queues
        for celery_queue in CELERY_QUEUES:
            while True:
                msg = chan.basic_get(queue=celery_queue.name)
                if msg is not None:
                    try:
                        task_json = json.loads(msg.body)
                        if task_json['id'] == task_id:
                            api_log.warning("Task found in rabbit... that means celery is busy..")
                            return task_json
                    except Exception as cannot_parse:
                        api_log.warning("Cannot parse rabbit message: %s" % str(cannot_parse))
                else:
                    break
    except Exception as e:
        api_log.error("Cannot inspect rabbitmq: %s" % str(e))
    return None

def is_task_in_celery(task_id):
    """Look whether a task is scheduled, reserved or active
    Args:
        The task id
    Returns the task dictionary or None
    """
    try:
        # When celery is down, inspect will be None, in this case we will wait for a while.
        i = None
        tries = 0
        while tries < 3:
            try:
                i = inspect(timeout=10)
                if inspect is not None:
                    break
            except Exception as exp:
                api_log.warning("Cannot inspect the celery queue.. let's wait for while... %s" % str(exp))
            finally:
                tries = tries + 1
                time.sleep(5)
        if inspect is None:
            return None
        active = i.active()
        scheduled = i.scheduled()
        reserved = i.reserved()
        if active is not None:
            for node, tasks in active.iteritems():
                for task in tasks:
                    if str(task['id']) == task_id:
                        del i
                        return task.copy()
        if reserved is not None:
            for node, tasks in reserved.iteritems():
                for task in tasks:
                    if str(task['id']) == task_id:
                        del i
                        return task.copy()

        if scheduled is not None:
            for node, tasks in scheduled.iteritems():
                for task in tasks:
                    if str(task['id']) == task_id:
                        del i
                        return task.copy()
        del i
        # Wow, we have reached this point...
        # Maybe celery is to busy to get tasks from the queue, let's see whether the task is in rabbit.
        task_in_rabbit = is_task_in_rabbit(task_id)
        if task_in_rabbit is not None:
            return task_in_rabbit

    except Exception as exp:
        api_log.error("[is_task_in_celery] An error occurred while reading the task list %s" % str(exp))

    return None


def only_one_task(function=None, key="", timeout=None):
    """Enforce only one celery task at a time.

    Args:
        function(obj): wrapped function.
        key(str): key value for a lock identification in Redis.
        timeout(int): time to live for the lock.
    """

    def _dec(run_func):
        """Decorator."""

        def _caller(*args, **kwargs):
            """Caller."""
            ret_value = None
            have_lock = False
            lock = redis_instance.lock(key, timeout=timeout)
            try:
                # Wait while previous task is running.
                while not have_lock:
                    have_lock = lock.acquire(blocking=False)
                    time.sleep(1)

                ret_value = run_func(*args, **kwargs)
            except redis.exceptions.RedisError as err:
                logger.error("Failed to acquire a lock while adding HIDS agent. Reason: %s" % err)

            finally:
                if have_lock:
                    lock.release()

            return ret_value
        return _caller
    return _dec(function) if function is not None else _dec
