# -*- coding: utf-8 -*-
#
#  License:
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

import os
import yaml
import redis
import uuid

import api_log
from copy import deepcopy

from celerybeatredis.schedulers import PeriodicTask
from celerymethods import celeryconfig

from db.methods.system import db_get_config

from apiexceptions import APIException
from apiexceptions.tasks import APITaskInvalid
from apiexceptions.tasks import APITaskInvalidName
from apiexceptions.tasks import APITaskErrorInsertInDB
from apiexceptions.tasks import APICeleryConfigurationError
from apiexceptions.tasks import APISchedulerErrorLoadingTasks
from apiexceptions.tasks import APISchedulerErrorUpdatingTasks
from apiexceptions.common import APIFileDoesntExists
from apiexceptions.common import APIFileInvalidContent


class Task(object):
    """ Task Class
    """

    TASK_CUSTOM = 'custom'
    TASK_DEFAULT = 'default'
    PREFIX = getattr(celeryconfig, 'CELERY_REDIS_SCHEDULER_KEY_PREFIX', '')
    TELEMERY = 'monitor_check_platform_telemetry_data'

    def __init__(self,
                 task_id=None,
                 task=None,  # task Method
                 key_name=None,  # key name for celery and yaml
                 name=None,
                 args=[],
                 kwargs={},
                 interval=None,
                 crontab=None,
                 enabled=None,
                 task_type=None):
        """ Constructor
        """
        self._task_id = task_id
        self._key_name = key_name
        self._name = name
        self._task = task
        self._args = args
        self._kwargs = kwargs
        self._interval = interval
        self._crontab = crontab
        self.enabled = enabled
        if task_type != Task.TASK_DEFAULT:
            self._task_type = Task.TASK_CUSTOM
        else:
            self._task_type = Task.TASK_DEFAULT

        if name is None and key_name is not None:
            self._name = key_name

        # Generate new id and key_name for new custom tasks
        if task_id is None and key_name is None:
            self._task_id = uuid.uuid1()
            self._key_name = '{0}:{1}'.format(name,
                                              str(self._task_id))

    @classmethod
    def from_dict(cls, task, key_name):
        """ Create a task object from dictionary
        """
        task_id = task.get('task_id', None)
        task_method = task.get('task', None)
        name = task.get('task_name', None)
        args = task.get('args', None)
        kwargs = task.get('kwargs', None)
        interval = task.get('interval', None)
        crontab = task.get('crontab', None)
        enabled = task.get('enabled', None)
        task_type = task.get('task_type', None)
        return cls(task_id=task_id,
                   task=task_method,
                   key_name=key_name,
                   name=name,
                   args=args,
                   kwargs=kwargs,
                   interval=interval,
                   crontab=crontab,
                   enabled=enabled,
                   task_type=task_type)

    @property
    def task_id(self):
        return self._task_id

    @property
    def task(self):
        return self._task

    @property
    def key_name(self):
        return self._key_name

    @property
    def name(self):
        return self._name

    @name.setter
    def name(self, value):
        self._name = value

    @property
    def args(self):
        return self._args

    @property
    def kwargs(self):
        return self._kwargs

    @property
    def interval(self):
        return self._interval

    @property
    def crontab(self):
        return self._crontab

    @property
    def enabled(self):
        return self.__enabled

    @enabled.setter
    def enabled(self, value):
        if not isinstance(value, bool):
            self.__enabled = None
        self.__enabled = value

    def enable(self):
        self.enabled = True

    def disable(self):
        self.enabled = False

    @property
    def task_type(self):
        return self._task_type

    def get_celery_name(self):
        return "{0}:{1}".format(Task.PREFIX, self.key_name)

    def to_dict(self, for_celery=False):
        """ Return the dictionary that represents the task
        """
        d = {}
        if self.task_id is not None:
            d['task_id'] = str(self.task_id)
        if self.task is not None:
            d['task'] = self.task
        if for_celery:
            d['name'] = self.get_celery_name()
        if self.name is not None:
            d['task_name'] = self.name
        if self.args is not None:
            d['args'] = self.args
        if self.kwargs is not None:
            d['kwargs'] = self.kwargs
        if self.interval is not None:
            d['interval'] = self.interval
        if self.crontab is not None:
            d['crontab'] = self.crontab
        if self.enabled is not None:
            d['enabled'] = self.enabled
        if self.task_type is not None:
            d['task_type'] = self.task_type
        return d

    def __repr__(self):
        return "{0}".format(self.to_dict())

    def diff(self, task_dict):
        """ Return the differences with a task in a dictionary
        """
        d = self.to_dict()
        for key in d.keys():
            if key in task_dict and d[key] == task_dict[key]:
                del d[key]
        return d

    def _validate(self):
        """ Validate the task
        """
        # Check for required fields
        if self.task is None:
            raise APITaskInvalid(
                log="[Task:_validatate] Required field 'task' missing")
        if self.name is None:
            raise APITaskInvalid(
                log="[Task:_validatate] Required field 'name' missing")
        if self.args is None:
            raise APITaskInvalid(
                log="[Task:_validatate] Required field 'args' missing")
        if self.kwargs is None:
            raise APITaskInvalid(
                log="[Task:_validatate] Required field 'kwargs' missing")
        if self.enabled is None:
            raise APITaskInvalid(
                log="[Task:_validatate] Required field 'enabled' missing")
        if self.interval is None and self.crontab is None:
            raise APITaskInvalid(
                log="[Task:_validatate] Required field 'interval' or 'crontab' missing")
        if self.interval is not None and self.crontab is not None:
            raise APITaskInvalid(
                log="[Task:_validatate] Fields 'interval' and 'crontab' are exclusive")

    def save_in_database(self):
        """ Save the task in redis database
        """
        self._validate()
        try:
            task_dict = self.to_dict(for_celery=True)
            ptask = PeriodicTask.from_dict(task_dict)
            ptask.save()
        except Exception as e:
            raise APITaskErrorInsertInDB(
                log="[task.save_in_database] Cannot insert the scheduled task: "
                "'{0}': {1}".format(self.name, str(e)))


class Scheduler(object):
    """ Scheduler Class
    """

    __instance = None

    def __new__(cls, *args, **kwargs):
        if cls.__instance is None:
            cls.__instance = super(Scheduler, cls).__new__(
                cls, *args, **kwargs)
        return cls.__instance

    def __init__(self):
        """ Constructor
        """
        # Load celery configuration
        default_tasks_file_param = 'CELERY_REDIS_SCHEDULER_DEFAULT_TASKS_FILE'
        self._default_tasks_file = getattr(celeryconfig, default_tasks_file_param, None)
        if self._default_tasks_file is None:
            raise APICeleryConfigurationError(param=default_tasks_file_param)

        custom_tasks_file_param = 'CELERY_REDIS_SCHEDULER_CUSTOM_TASKS_FILE'
        self._custom_tasks_file = getattr(celeryconfig, custom_tasks_file_param, None)
        if self._custom_tasks_file is None:
            raise APICeleryConfigurationError(param=custom_tasks_file_param)

        redis_scheduler_url_param = 'CELERY_REDIS_SCHEDULER_URL'
        self._redis_scheduler_url = getattr(celeryconfig, redis_scheduler_url_param, None)
        if self._redis_scheduler_url is None:
            raise APICeleryConfigurationError(param=redis_scheduler_url_param)

        # Load task
        self.load_tasks()

        self._sanitize_tasks()

    def _sanitize_tasks(self):
        # Special case for telemetry
        if Task.TELEMERY not in self._custom_tasks:
            try:
                success, value = db_get_config('track_usage_information')
                if success and value != '':
                    telemetry_task = self.get_task(Task.TELEMERY)
                    telemetry_task.enabled = bool(int(value))
                    self.update_task(telemetry_task)
            except Exception as e:
                api_log.warning("[Scheduler._sanitize_tasks] {0}".format(str(e)))

    def _load_tasks_from_file(self, filename):
        """  Load the task dictionary from file
        Returns:
        dictionary with the tasks
        Raises:
        """
        if not os.path.isfile(filename):
            raise APIFileDoesntExists(filename=filename)

        tasks = {}
        try:
            with open(filename, 'r') as f:
                content = yaml.load(f.read())
                tasks = content if content else {}
        except Exception, e:
            raise APIFileInvalidContent(filename=filename,
                                        error_message=str(e))

        # Sanity checks
        for task in tasks.keys():
            if not isinstance(tasks[task], dict):
                del tasks[task]

        return tasks

    def _load_default_tasks_from_file(self):
        """ Load the default task dictionary from the configuration files
        Returns:
        dictionary with the tasks
        """
        tasks = self._load_tasks_from_file(self._default_tasks_file)
        for task in tasks.keys():
            tasks[task]['task_type'] = Task.TASK_DEFAULT

        return tasks

    def _load_custom_tasks_from_file(self):
        try:
            tasks = self._load_tasks_from_file(self._custom_tasks_file)
        except APIException:
            tasks = {}
        return tasks

    def _update_tasks(self):
        for task in self._custom_tasks.keys():
            if task in self._tasks:
                self._tasks[task].update(self._custom_tasks[task])
            else:
                self._tasks[task] = self._custom_tasks[task]

    def _update_custom_tasks_file(self):
        """ Update the custom tasks file
        """
        try:
            with open(self._custom_tasks_file, 'w') as f:
                f.write("# DO NOT MODIFY THIS FILE.\n"
                        "# AlienVault tasks file.\n"
                        "# This is auto-generated by the alienvault-api.\n")
                yaml.dump(self._custom_tasks, f, default_flow_style=False)
            os.chmod(self._custom_tasks_file, 0770)
        except Exception as e:
            raise APISchedulerErrorUpdatingTasks(
                log="[Scheduler:_update_custom_task_file]".format(str(e)))

    def _remove_all_tasks_from_database(self):
        redis_proto = redis.StrictRedis.from_url(self._redis_scheduler_url)
        redis_proto.flushdb()

    def load_tasks(self):
        """ Load scheduled task from configuration files
        Raises:
            APISchedulerErrorLoadingTasks
        """
        try:
            self._default_tasks = self._load_default_tasks_from_file()
            self._custom_tasks = self._load_custom_tasks_from_file()
            self._tasks = deepcopy(self._default_tasks)
            self._update_tasks()
        except APIException as e:
            raise APISchedulerErrorLoadingTasks(
                log="[Scheduler:load_tasks] {0}".format(str(e)))

    def restore_tasks_to_db(self):
        """ Set the initial tasks in the database
        (i.e. Redis) from configuration files.
        """
        self._remove_all_tasks_from_database()
        for (key_name, task) in self._tasks.iteritems():
            try:
                task = Task.from_dict(task=task, key_name=key_name)
                task.save_in_database()
            except APITaskInvalid:
                # Ingnore the task and try to insert the rest of them.
                pass

    def add_custom_task(self, task):
        """ Add a custom task to the system.
        Update the custom tasks file and insert it in the redis database
        Args:
             task(Task): Task to schedule
        Raises:
            APITaskErrorInsertInDB
            APISchedulerErrorUpdatingTasks
        """
        if task.key_name in self._custom_tasks:
            raise APITaskInvalidName(name=task.key_name)

        task.save_in_database()
        self._custom_tasks[task.key_name] = task.to_dict()
        self._update_custom_tasks_file()
        self._update_tasks()

    def get_task(self, name):
        """ Get a scheduled Task
        Args:
            name (str): Name of the scheduled task
        """
        task_dict = self._tasks.get(name, None)
        if task_dict is None:
            return None
        return Task.from_dict(task_dict, name)

    def get_tasks_by_method(self, method):
        """ Get a list of tasks filtered by method
        Args:
            method (str): Method name
        """
        tasks = []
        task_keys = [i for i in self._tasks.keys() if 'task' in self._tasks[i] and self._tasks[i]['task'] == method]
        for task_key in task_keys:
            task = Task.from_dict(self._tasks[task_key], task_key)
            tasks.append(task)
        return tasks

    def update_task(self, task):
        """ Add a custom task to the system.
        Update the custom tasks file and insert it in the redis database
        Args:
            task(Task): The task with the modifications
        Raises:
            APITaskErrorInsertInDB
            APISchedulerErrorUpdatingTasks
        """
        if task.key_name not in self._tasks:
            raise APITaskInvalidName(name=task.key_name)

        task.save_in_database()

        if task.task_type == Task.TASK_CUSTOM:
            self._custom_tasks[task.key_name] = task.to_dict()
        else:
            # Store only the differences with the Default tasks
            default_task = self._default_tasks[task.key_name]
            differences = task.diff(default_task)
            self._custom_tasks[task.key_name] = differences

        self._update_custom_tasks_file()
        self._update_tasks()
