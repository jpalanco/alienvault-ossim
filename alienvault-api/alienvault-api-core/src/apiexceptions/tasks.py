# -*- coding: utf-8 -*-
#
# License:
#
# Copyright (c) 2015 AlienVault
# All rights reserved.
#
# This package is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; version 2 dated June, 1991.
# You may not use, modify or distribute this program under any other version
# of the GNU General Public License.
#
# This package is distributed in the hope that it will be useful,
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

from apiexceptions import APIException


class APICeleryConfigurationError(APIException):
    def __init__(self, param, log=None):
        message = "Missing Scheduler configuration parameter '{0}'".format(param)
        if log is None:
            log = message
        super(APICeleryConfigurationError, self).__init__(
            message=message,
            log=log)


class APITaskInvalid(APIException):
    def __init__(self, log=None):
        super(APITaskInvalid, self).__init__(
            message="Invalid Task. Required fields missing",
            log=log)


class APITaskErrorInsertInDB(APIException):
    def __init__(self, log=None):
        super(APITaskErrorInsertInDB, self).__init__(
            message="Cannot insert scheduled task in the system due to "
            "Database connection problem",
            log=log)


class APITaskInvalidName(APIException):
    def __init__(self, name='', log=None):
        message = "Invalid task name '{0}'".format(name)
        if log is None:
            log = message
        super(APITaskInvalidName, self).__init__(
            message=message,
            log=log)


class APISchedulerErrorLoadingTasks(APIException):
    def __init__(self, log=None):
        message = "Cannot load Scheduled tasks"
        if log is None:
            log = message
        super(APISchedulerErrorLoadingTasks, self).__init__(
            message=message,
            log=log)


class APISchedulerErrorUpdatingTasks(APIException):
    def __init__(self, log=None):
        message = "Cannot update Scheduled tasks"
        if log is None:
            log = message
        super(APISchedulerErrorUpdatingTasks, self).__init__(
            message=message,
            log=log)
