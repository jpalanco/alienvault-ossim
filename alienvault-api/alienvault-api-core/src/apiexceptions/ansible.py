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


class APIAnsibleError(APIException):
    def __init__(self, error_message, log=None):
        message = "Error Running Ansible Module"
        if log is None:
            log = "{0}: {1}".format(message, error_message)
        super(APIAnsibleError, self).__init__(
            message=message,
            log=log,
            log_level='WARNING')


class APIAnsibleBadResponse(APIException):
    def __init__(self, error_message, log=None):
        message = "Invalid Ansible Response"
        if log is None:
            log = "{0}: {1}".format(message, error_message)
        super(APIAnsibleBadResponse, self).__init__(
            message=message,
            log=log,
            log_level='WARNING')
