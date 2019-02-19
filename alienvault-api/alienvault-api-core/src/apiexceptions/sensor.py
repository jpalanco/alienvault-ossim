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


class APICannotResolveSensorID(APIException):
    def __init__(self, sensor_id, log=None):
        super(APICannotResolveSensorID, self).__init__(
            message="Cannot resolve the given sensor <{0}>".format(sensor_id),
            log=log)


class APICannotAddSensor(APIException):
    def __init__(self, sensor_id, log=None):
        msg = "Cannot add the given sensor <{0}>.".format(sensor_id)
        msg += " Check the system is reachable and the password is correct" 
        super(APICannotAddSensor, self).__init__(
            message=msg,
            log=log)


class APICannotSetSensorContext(APIException):
    def __init__(self, sensor_id, log=None):
        super(APICannotSetSensorContext, self).__init__(
            message="Cannot set sensor context for sensor <{0}>.".format(sensor_id),
            log=log)

