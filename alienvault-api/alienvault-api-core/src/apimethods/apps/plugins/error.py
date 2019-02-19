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
#
import json


class ErrorCodes(object):
    """Class to manage errors with the plugin syntax.
    """
    SUCCESS = 0
    EXCEPTION = 1
    OUTPUT_FILE_WRITE_ERROR = 2
    PLUGIN_FILE_MANDATORY_ERROR = 1000
    PLUGIN_CANNOT_BE_PROCESSED = 1001

    PLUGIN_FILE_NOT_EXIST_ERROR = 2000
    PLUGIN_FILE_CANT_BE_READ_ERROR = 2001
    PLUGIN_MANDATORY_SECTION_NOT_FOUND_ERROR = 2002
    PLUGIN_MANDATORY_VALUE_SECTION_NOT_FOUND_ERROR = 2003
    PLUGIN_ID_MANDATORY_ERROR = 2004
    PLUGIN_ID_INVALID_ERROR = 2005
    PLUGIN_USER_CUSTOM_FUNCTION_FILE_NOT_FOUND = 2006
    PLUGIN_USER_CUSTOM_FUNCTION_FILE_ERROR_LOADING_FILE = 2007
    PLUGIN_USER_CUSTOM_FUNCTION_FILE_COMPILE_ERROR = 2008
    PLUGIN_WITHOUT_RULES = 2009

    PLUGIN_CONFIG_INVALID_ENTRY = 3000
    PLUGIN_INVALID_VALUE_FOR_ENTRY = 3001

    PLUGIN_RULE_MANDATORY_ATTRIBUTE_NOT_FOUND = 4000
    PLUGIN_RULE_INVALID_ATTRIBUTE = 4001
    PLUGIN_RULE_REGEXP_NOT_FOUND = 4002
    PLUGIN_RULE_REGEXP_COMPILE_ERROR = 4003
    PLUGIN_RULE_REGEXP_DUPLICATE_GROUP_NAME = 4004
    PLUGIN_RULE_REGEXP_USE_WRONG_CAPTURE_NAME_VARIABLE = 4005
    PLUGIN_RULE_REGEXP_USE_INVALID_INDEX_VARIABLE = 4006
    PLUGIN_RULE_UNKNOWN_FUNCTION = 4007
    PLUGIN_RULE_TRANSLATION_SECTION_NOT_FOUND = 4008
    PLUGIN_RULE_CUSTOM_USER_FUNCTION_FILE_NOT_DEFINED = 4009
    PLUGIN_RULE_UNKNOWN_CUSTOM_USER_FUNCTION = 4010
    PLUGIN_RULE_DUPLICATE_RULE = 4011
    PLUGIN_TRANSLATE2_WRONG_USAGE = 4012
    PLUGIN_TRANSLATE2_SECTION_NOT_FOUND = 4013

    ERROR_CODE_MAP_STR = {
        0: "Success.",
        1: "Exception:",
        2: "Error write the output file",
        1000: "Plugin file is mandatory",
        2000: "Plugin file not exist or is not accesible",
        2001: "It can't be read the plugin file",
        2002: "Mandatory section not found",
        2003: "Mandatory value on section not found",
        2004: "plugin_id not found in section DEFAULT",
        2005: "Invalid plugin id",
        2006: "User custom function file defined but not found/or access not allowed",
        2007: "Error loading custom user function file ",
        2008: "Error compiling the custom user functions",
        2009: "Plugin file without rules",
        3000: "Invalid config entry",
        3001: "Invalid value",
        4000: "Mandatory Attribute not found",
        4001: "Invalid Plugin rule attribute",
        4002: "regexp not found",
        4003: "Regexp compilation error",
        4004: "Duplicate group name",
        4005: "Variable assignement on unknown capture name",
        4006: "Variable assignement on invalid index",
        4007: "Use of unknown function",
        4008: "Use of translate function and not [translation] section found",
        4009: "Use of a custom user function (:userfuncion($param)) but not custom user function file defined at [config] section",
        4010: "Use of unknown user custom function",
        4011: "Duplicate rule name",
        4012: "Invalid use of translate2 function",
        4013: "Translate2 section not found"

    }

    @staticmethod
    def get_str(error_code):
        """Returns the error code string
        """
        try:
            return ErrorCodes.ERROR_CODE_MAP_STR[error_code]
        except ValueError:
            return ErrorCodes.ERROR_CODE_MAP_STR[ErrorCodes.EXCEPTION] + " Invalid Error Code"

    @staticmethod
    def get_str_on_exception(error_code, exception):
        """Returns the error code string
        """
        return ErrorCodes.get_str(error_code) + " Exception: %s" % exception

    @staticmethod
    def get_detected_error_obj(code, msg_extra):
        return DetectedError(code, ErrorCodes.get_str(code), msg_extra).get_dict()


class DetectedError():
    """Detected error abstraction"""

    def __init__(self, error_code, error_message, extra_msg):
        self.__error_code = error_code
        self.__error_msg = error_message
        self.__error_extra = extra_msg

    def get_dict(self):
        errod_dic = {"code": self.__error_code,
                      "description": "{0} - {1}".format(self.__error_msg, self.__error_extra)}
        return errod_dic

    def __repr__(self):
        return str(self.get_dict())

