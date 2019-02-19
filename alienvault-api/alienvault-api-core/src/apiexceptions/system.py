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


class APICannotResolveLocalSystemID(APIException):
    def __init__(self, log=None):
        super(APICannotResolveLocalSystemID, self).__init__(
            message="Cannot resolve local system",
            log=log)


class APICannotResolveSystemIP(APIException):
    def __init__(self, system_id, log=None):
        super(APICannotResolveSystemIP, self).__init__(
            message="Cannot resolve the IP for the given system <{0}>".format(system_id),
            log=log,
            http_code=400)


class APICannotRetrieveSystems(APIException):
    def __init__(self, log=None):
        super(APICannotRetrieveSystems, self).__init__(
            message="Cannot retrieve systems information",
            log=log)


class APICannotRetrieveSystem(APIException):
    def __init__(self, system_id, log=None):
        super(APICannotRetrieveSystems, self).__init__(
            message="Cannot retrieve system information for the given system <{0}>".format(system_id),
            log=log)


class APICannotResolveSensorID(APIException):
    def __init__(self, system_id, log=None):
        super(APICannotResolveSensorID, self).__init__(
            message="Cannot resolve the sensor for the given system <{0}>".format(system_id),
            log=log)


class APICannotResolveSensor(APIException):
    def __init__(self, sensor_id, log=None):
        super(APICannotResolveSensor, self).__init__(
            message="Cannot resolve the given sensor <{0}>".format(sensor_id),
            log=log)


class APICannotRetrieveOssimSetup(APIException):
    def __init__(self, system_ip, log=None):
        message = "Cannot retrieve Ossim Setup in system <{0}>".format(system_ip)
        if log is not None:
            log = "{0}: {1}".format(message, log)

        super(APICannotRetrieveOssimSetup, self).__init__(
            message=message,
            log=log)
