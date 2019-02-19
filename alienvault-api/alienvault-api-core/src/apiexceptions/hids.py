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


class APICannotGetHIDSAgents(APIException):
    def __init__(self, sensor_id, log=None):
        super(APICannotGetHIDSAgents, self).__init__(
            message="Cannot get HIDS agents for sensor <{0}>".format(sensor_id),
            log=log)


class APICannotGetHIDSAgentByAsset(APIException):
    def __init__(self, asset_id, log=None):
        super(APICannotGetHIDSAgentByAsset, self).__init__(
            message="Cannot get HIDS agents related to asset <{0}>".format(asset_id),
            log=log)


class APICannotGetLinkedAssets(APIException):
    def __init__(self, log=None):
        super(APICannotGetLinkedAssets, self).__init__(
            message="Cannot get assets linked to HIDS agents",
            log=log)


class APICannotGetHIDSAgent(APIException):
    def __init__(self, sensor_id, agent_id, log=None):
        super(APICannotGetHIDSAgent, self).__init__(
            message="Cannot get HIDS agent '{0}' on sensor <{1}>".format(agent_id, sensor_id),
            log=log)


class APIInvalidHIDSAgentID(APIException):
    def __init__(self, agent_id, log=None):
        super(APIInvalidHIDSAgentID, self).__init__(
            message="Invalid HIDS Agent <{0}>".format(agent_id),
            log=log)


class APICannotRunHIDSCommand(APIException):
    def __init__(self, sensor_id, op_ossec, log=None):
        super(APICannotRunHIDSCommand, self).__init__(
            message="Cannot run HIDS command '{0}' on sensor <{1}>".format(op_ossec, sensor_id),
            log=log)


class APICannotAddHIDSAgent(APIException):
    def __init__(self, agent_id, sensor_id, log=None):
        super(APICannotAddHIDSAgent, self).__init__(
            message="Cannot add HIDS agent '{0}' to the sensor <{1}>".format(agent_id, sensor_id),
            log=log)


class APICannotCreateHIDSAgent(APIException):
    def __init__(self, agent_name, sensor_id, log=None):
        super(APICannotCreateHIDSAgent, self).__init__(
            message="Cannot create HIDS agent '{0}' on the sensor <{1}>".format(agent_name, sensor_id),
            log=log)


class APIInvalidDeploymentIP(APIException):
    def __init__(self, ip_address, log=None):
        super(APIInvalidDeploymentIP, self).__init__(
            message="Deployment IP '{0}' is not valid IP address".format(ip_address),
            log=log)


class APIInvalidWindowsUsername(APIException):
    def __init__(self, windows_username, log=None):
        super(APIInvalidWindowsUsername, self).__init__(
            message="Invalid Credentials: '{0}' is not valid username".format(windows_username),
            log=log)


class APIInvalidWindowsPassword(APIException):
    def __init__(self, log=None):
        super(APIInvalidWindowsPassword, self).__init__(
            message="Invalid Credentials: Password is not valid",
            log=log)


class APIInvalidAgentID(APIException):
    def __init__(self, agent_id, log=None):
        super(APIInvalidAgentID, self).__init__(
            message="Agent ID '{0}' is not valid. Agent ID has to be 1-4 digital characters".format(agent_id),
            log=log)


class APICannotDeployHIDSAgent(APIException):
    def __init__(self, reason, log=None):
        super(APICannotDeployHIDSAgent, self).__init__(
            message=reason,
            log=log)


class APICannotUpdateHIDSAgent(APIException):
    def __init__(self, agent_id, sensor_id, log=None):
        super(APICannotUpdateHIDSAgent, self).__init__(
            message="Cannot update HIDS agent '{0}' on sensor <{1}>".format(agent_id, sensor_id),
            log=log)


class APICannotDeleteHIDSAgent(APIException):
    def __init__(self, agent_id, sensor_id, log=None):
        super(APICannotDeleteHIDSAgent, self).__init__(
            "Cannot delete HIDS agent '{0}' from the sensor <{1}>".format(agent_id, sensor_id),
            log=log)


class APICannotDeleteHIDSAgentList(APIException):
    def __init__(self, agent_list, sensor_id, log=None):
        super(APICannotDeleteHIDSAgentList, self).__init__(
            message="Cannot delete HIDS agents '{0}' from the sensor <{1}>".format(agent_list, sensor_id),
            log=log)
