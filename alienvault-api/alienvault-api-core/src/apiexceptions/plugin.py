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


class APICannotCheckPlugin(APIException):
    def __init__(self, plugin_file):
        super(APICannotCheckPlugin, self).__init__(
            message="Cannot check the given plugin: {}".format(plugin_file))


class APIPluginFileNotFound(APIException):
    def __init__(self, plugin_file):
        super(APIPluginFileNotFound, self).__init__(
            message="Plugin File not found: {}".format(plugin_file))


class APIPluginListCannotBeLoaded(APIException):
    def __init__(self):
        super(APIPluginListCannotBeLoaded, self).__init__(
            message="Plugin list cannot be loaded")


class APIPluginHeaderNotValid(APIException):
    def __init__(self, msg=""):
        super(APIPluginHeaderNotValid, self).__init__(
            message="Invalid plugin header: {}".format(msg))


class APIPluginHeaderNotFound(APIException):
    def __init__(self):
        super(APIPluginHeaderNotFound, self).__init__(
            message="Plugin Header Not Found")


class APIPluginInvalidFile(APIException):
    def __init__(self, error_list):
        msg = " ".join(error_list)
        super(APIPluginInvalidFile, self).__init__(message=msg)


class APIPluginFileWithErrors(APIException):
    def __init__(self, error_summary):
        super(APIPluginFileWithErrors, self).__init__(
            message="Plugin with errors:\n{}".format(error_summary))


class APICannotUploadPlugin(APIException):
    def __init__(self, error_summary):
        super(APICannotUploadPlugin, self).__init__(
            message="Cannot upload the given plugin: {}".format(error_summary))


class APICannotSavePlugin(APIException):
    def __init__(self, message="Cannot save the given plugin"):
        super(APICannotSavePlugin, self).__init__(message=message)


class APICannotGetSensorPlugins(APIException):
    def __init__(self, log=None):
        super(APICannotGetSensorPlugins, self).__init__(
            message="Cannot get the Plugins from Sensor",
            log=log)


class APICannotSetSensorPlugins(APIException):
    def __init__(self, log=None):
        super(APICannotSetSensorPlugins, self).__init__(
            message="Cannot set the Plugins in the Sensor",
            log=log)


class APIInvalidPlugin(APIException):
    def __init__(self, error_summary):
        super(APIInvalidPlugin, self).__init__(
            message="Invalid plugin error: {}".format(error_summary))


class APICannotBeRemoved(APIException):
    def __init__(self, message="Cannot remove plugin data"):
        super(APICannotBeRemoved, self).__init__(message=message)
