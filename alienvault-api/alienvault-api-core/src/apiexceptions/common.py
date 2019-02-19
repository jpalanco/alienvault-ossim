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


class APIFileDoesntExists(APIException):
    def __init__(self, filename, log=None):
        message = "File '{0}' doesn't exists".format(filename)
        if log is None:
            log = message
        super(APIFileDoesntExists, self).__init__(
            message=message,
            log=log,
            log_level='WARNING')


class APIFileInvalidContent(APIException):
    def __init__(self, filename, error_message='', log=None):
        message = "The content of '{0}' is not valid and cannot be loaded".format(filename)
        if log is None:
            log = "{0}: {1}".format(message, error_message)
        super(APIFileInvalidContent, self).__init__(
            message=message,
            log=log,
            log_level='WARNING')


class APIInvalidInputFormat(APIException):
    def __init__(self, log=None):
        super(APIInvalidInputFormat, self).__init__(
            message="Invalid input format",
            log=log,
            http_code=400,
            log_level='WARNING')
