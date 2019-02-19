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
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
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
import re
from apimethods.apps.plugins.error import ErrorCodes


class PluginRule(object):
    """Plugin Rule abstraction
    """

    PLUGIN_DB_RULE_VALID_ATTRS = ["query", "ref"]
    PLUGIN_RULE_VALID_ATTRS = ['absolute',
                               'application',
                               'binary_data',
                               'condition',
                               'cpu',
                               'ctx',
                               'date',
                               'device',
                               'domain',
                               'dst_ip',
                               'dst_port',
                               'event_id',
                               'event_type',
                               'extra_data',
                               'fdate',
                               'filename',
                               'from',
                               'gzipdata',
                               'hids_event_type',
                               'host',
                               'hostname',
                               'interface',
                               'interval',
                               'inventory_source',
                               'ip',
                               'ipv6',
                               'log',
                               'login',
                               'mac',
                               'mail',
                               'memory',
                               'occurrences',
                               'organization',
                               'os',
                               'password',
                               'plugin_id',
                               'plugin_sid',
                               'port',
                               'port_from',
                               'port_to',
                               'precheck',
                               'priority',
                               'protocol',
                               'regexp',
                               'reliability',
                               'sensor',
                               'sensor_id',
                               'service',
                               'snort_cid',
                               'snort_sid',
                               'software',
                               'src_ip',
                               'src_port',
                               'state',
                               'target',
                               'to',
                               'type',
                               'tzone',
                               'unziplen',
                               'userdata1',
                               'userdata2',
                               'userdata3',
                               'userdata4',
                               'userdata5',
                               'userdata6',
                               'userdata7',
                               'userdata8',
                               'userdata9',
                               'username',
                               'value',
                               'vendor',
                               'video',
                               'what']

    PLUGIN_STARTQUERY_RULE_MANDATORY_FIELDS = ["query"]
    PLUGIN_DB_RULE_MANDATORY_FIELDS = ["query", "ref"]
    PLUGIN_RULE_MANDATORY_FIELDS = ["event_type", "regexp", "plugin_sid"]
    PLUGIN_WMI_RULE_MANDATORY_FIELDS = ["cmd", "regexp", "start_regexp"]
    PLUGIN_RULE_VALID_FUNCTIONS = ['checkValue', 'critical', 'debug', 'error', 'geoip_getCity', 'geoip_getCountryCode',
                                   'geoip_getCountryCode3', 'geoip_getCountryName', 'geoip_getData', 'geoip_getDmaCode',
                                   'geoip_getLatitude', 'geoip_getLongitude', 'geoip_getMetroCode',
                                   'geoip_getPostalCode', 'geoip_getRegionCode', 'geoip_getRegionName',
                                   'geoip_getTimeZone', 'hextoint', 'info', 'intrushield_sid', 'iss_siteprotector_sid',
                                   'md5sum', 'netscreen_idp_sid', 'normalize_date', 'normalize_date_american',
                                   'normalize_protocol', 'read_verbose_info', 'resolv', 'resolv_iface', 'resolv_ip',
                                   'resolv_port', 'sanitize', 'snort_id', 'translate_wsaea_IDs', 'upper', 'warning']

    RegexReplaceArrayVariables = re.compile("\{\$[^\}\{]+\}", re.UNICODE)
    RegexReplaceCustomUserFunctionArray = re.compile("(\{:(\w+)\((\$[^\)]+)?\)\})", re.UNICODE)
    RegexReplaceUserArrayFunctions = re.compile("(\{(\w+)\((\$[^\)]+)\)\})", re.UNICODE)
    RegexReplaceVariables = re.compile("\{\$[^\}\{]+\}", re.UNICODE)
    RegexIndexReplaceVariables = re.compile("\{\$\d+\}", re.UNICODE)
    RegexTranslationSection = re.compile("(\{(translate)\(\$([^\)]+)\)\})", re.UNICODE)
    RegexReplaceUserFunctions = re.compile("(\{(\w+)\((\$[^\)]+)\)\})", re.UNICODE)
    RegexReplaceCustomUserFunctions = re.compile("(\{:(\w+)\((\$[^\)]+)?\)\})", re.UNICODE)
    RegexReplaceConcatFunction = re.compile("\$CONCAT\((?P<params>.*)\)", re.UNICODE)

    def __init__(self, rule_name, rule_data, user_custom_function_list, translate_section_exist=False,
                 user_custom_function_file_exist=False,
                 is_database_plugin=False, is_wmi_plugin=False, translate2_sections=None):
        """Constructor
        Args:
            rule_name (str) it's the rule name
            rule_data (str) Python dic with the plugin rule data
            Example:  {"event_type": "event",
                       "regexp" : "(?P<date>\d....",
                       ...}
            translate_section_exist: Indicates whether a [translation] section is present or not in the plugin file
        """
        self.__is_wmi_plugin = is_wmi_plugin
        self.__is_database_plugin = is_database_plugin
        self.__rule_name = rule_name
        self.__rule_data = rule_data
        self.__regexLines = []  # list of regexs
        self.__nlines = 0
        self.__line_count = 1  # Line match
        self.__matched = False
        self.__ngroups = 0
        self.__group_names = []
        self.__is_idm_plugin = False
        self.__has_translation_section = translate_section_exist
        self.__has_user_custom_function_file = user_custom_function_file_exist
        self.__user_custom_function_list = user_custom_function_list
        self.__translate2_sections = translate2_sections
        self.__errors = []
        self.__regexp = ""

    @property
    def regexp(self):
        return self.__regexp

    @regexp.setter
    def regexp(self, value):
        self.__regexp = value

    @staticmethod
    def is_digit(value):
        try:
            _ = int(value)
        except ValueError:
            return False
        return True

    @staticmethod
    def is_attribute_db_rule_valid_attributes(attr_name):
        if attr_name not in PluginRule.PLUGIN_DB_RULE_VALID_ATTRS:
            return False
        return True

    @staticmethod
    def is_attribute_wmi_rule_valid_attribute(attr_name):
        if attr_name not in PluginRule.PLUGIN_WMI_RULE_MANDATORY_FIELDS:
            return False
        return True

    def append_error(self, code, msg=""):
        """Appends an error to the error list"""
        self.__errors.append(ErrorCodes.get_detected_error_obj(code, msg))

    def get_error_list(self):
        return self.__errors

    def check_mandatory_fields(self):
        """Checks for mandatory fields in a plugin rule
        """
        mandatory_fields = self.PLUGIN_RULE_MANDATORY_FIELDS

        if self.__is_database_plugin:
            if self.__rule_name == "start_query":
                mandatory_fields = PluginRule.PLUGIN_STARTQUERY_RULE_MANDATORY_FIELDS
        if self.__is_wmi_plugin:
            if self.__rule_name == "cmd" or self.__rule_name == "start_cmd":
                mandatory_fields = PluginRule.PLUGIN_WMI_RULE_MANDATORY_FIELDS
            else:
                mandatory_fields = PluginRule.PLUGIN_DB_RULE_MANDATORY_FIELDS

        for mandatory in mandatory_fields:
            attrs = self.__rule_data.keys()
            if mandatory == "plugin_sid" and self.__is_idm_plugin:
                continue
            if mandatory not in attrs:
                self.append_error(ErrorCodes.PLUGIN_RULE_MANDATORY_ATTRIBUTE_NOT_FOUND, " ->'{0}'".format(mandatory))

    def check_invalid_fields(self):
        """Checks for invalid fields in a plugin rule
        """
        attrs = self.__rule_data.keys()
        for attribute in attrs:
            if attribute not in PluginRule.PLUGIN_RULE_VALID_ATTRS:
                if self.__is_database_plugin and PluginRule.is_attribute_db_rule_valid_attributes(attribute):
                    continue
                if self.__is_wmi_plugin and PluginRule.is_attribute_wmi_rule_valid_attribute(attribute):
                    continue
                msg = " ->'{0}'".format(attribute)
                if attribute == "sensor":
                    msg = " ->'{0}' is not allowed anymore. Please use device instead of sensor".format(attribute)
                self.append_error(ErrorCodes.PLUGIN_RULE_INVALID_ATTRIBUTE, msg)

    def check_regex_wmi(self):
        try:
            self.regexp = self.__rule_data['regexp']
        except ValueError:
            self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_NOT_FOUND)
        except KeyError, e:
            self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_NOT_FOUND)
        else:
            regex_compile_flags = re.IGNORECASE | re.UNICODE
            # Split in lines.
            try:
                compiled_regex = re.compile(self.regexp, regex_compile_flags)
                # The number of capturing groups in the pattern
                self.__ngroups += compiled_regex.groups
                # A dictionary mapping any symbolic group names defined by (?P<id>) to group numbers.
                group_index = compiled_regex.groupindex
                for capture_name in group_index.keys():
                    if capture_name not in self.__group_names:
                        self.__group_names.append(capture_name)
                    else:
                        self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_DUPLICATE_GROUP_NAME,
                                          "->'{0}'".format(capture_name))
            except Exception, e:
                self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_COMPILE_ERROR, str(e))

    def check_regex(self):
        """Checks if the regex its a valid regex"""
        try:
            self.regexp = self.__rule_data['regexp']
        except ValueError:
            self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_NOT_FOUND)
        except KeyError, e:
            self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_NOT_FOUND)
        else:
            regex_compile_flags = re.IGNORECASE | re.UNICODE
            # Split in lines.
            regex_list = self.regexp.split("\\n")
            try:
                for r in regex_list:
                    if r == '':
                        continue
                    compiled_regex = re.compile(r, regex_compile_flags)
                    self.__regexLines.append(compiled_regex)
                    # The number of capturing groups in the pattern
                    self.__ngroups += compiled_regex.groups
                    # A dictionary mapping any symbolic group names defined by (?P<id>) to group numbers.
                    group_index = compiled_regex.groupindex
                    for capture_name in group_index.keys():
                        if capture_name not in self.__group_names:
                            self.__group_names.append(capture_name)
                        else:
                            self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_DUPLICATE_GROUP_NAME,
                                              "->'{0}'".format(capture_name))
                self.__nlines = self.regexp.count("\\n") + 1
            except Exception, e:
                self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_COMPILE_ERROR, str(e))

    def check_values(self):
        """Checks the values assigned to the rule attributes.
        - If it's a function, check if it's a valid function
             plugin_sid={translate($type)} ->Check if translate is a valid function name
        - If it's a capture name, check if it exists
             src_ip={resolv($src)} -> Check if src is in the capture name list
        - If it's a capture index, check if it exists
            src_ip={$10} -> Check if the capture index list has this element
        """
        for key, value in self.__rule_data.iteritems():
            if key == "regexp":
                continue
            search = PluginRule.RegexReplaceVariables.findall(value)
            for string in search:
                var = string[2:-1]
                # if var is a number check after
                if not PluginRule.is_digit(var):
                    if var not in self.__group_names:
                        self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_USE_WRONG_CAPTURE_NAME_VARIABLE,
                                          "{0}={1}, where {2} is an unknown capture name, not in regex capture name list".format(
                                              key, string, var))
            search = PluginRule.RegexIndexReplaceVariables.findall(value)

            for string in search:
                try:
                    val = int(string[2:-1])
                    if val > self.__ngroups:
                        self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_USE_INVALID_INDEX_VARIABLE,
                                          "Value {0} is greater than number of groups:{1}".format(string,
                                                                                                  self.__ngroups))
                except ValueError, e:
                    self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_USE_INVALID_INDEX_VARIABLE, str(e))
            # Check for functions.
            search = PluginRule.RegexReplaceUserFunctions.findall(value)
            for string in search:
                # String is a tuple like this: (u'{resolv($dst)}', u'resolv', u'$dst')
                (string_matched, func, variables) = string
                if func == "translate":
                    if not self.__has_translation_section:  # translate function isn't on the ParserUtil
                        self.append_error(ErrorCodes.PLUGIN_RULE_TRANSLATION_SECTION_NOT_FOUND)
                    continue
                if func == "translate2":
                    try:
                        section = variables.replace('$', '').split(',')[1]
                        if section not in self.__translate2_sections:
                            self.append_error(ErrorCodes.PLUGIN_TRANSLATE2_SECTION_NOT_FOUND, section)
                    except:
                        self.append_error(ErrorCodes.PLUGIN_TRANSLATE2_WRONG_USAGE)
                    continue
                if func not in PluginRule.PLUGIN_RULE_VALID_FUNCTIONS:
                    self.append_error(ErrorCodes.PLUGIN_RULE_UNKNOWN_FUNCTION, "Function: {0}".format(func))

                if PluginRule.is_digit(variables.replace('$', '')):
                    if int(variables.replace('$', '')) > self.__ngroups:
                        self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_USE_INVALID_INDEX_VARIABLE,
                                          "Value {0} is greater than number of groups:{1}".format(string,
                                                                                                  self.__ngroups))
                elif variables.replace('$', '') not in self.__group_names:
                    self.append_error(ErrorCodes.PLUGIN_RULE_REGEXP_USE_WRONG_CAPTURE_NAME_VARIABLE,
                                      "{0}={1}, where {2} is an unknown capture name, not in regex capture name list".format(
                                          key, string_matched, variables.replace('$', '')))
            # Check for custom user functions:
            search = PluginRule.RegexReplaceCustomUserFunctions.findall(value)
            if len(search) > 0 and not self.__has_user_custom_function_file:
                self.append_error(ErrorCodes.PLUGIN_RULE_CUSTOM_USER_FUNCTION_FILE_NOT_DEFINED)
            else:
                for string in search:
                    (string_matched, func, variables) = string
                    if func not in self.__user_custom_function_list:
                        self.append_error(ErrorCodes.PLUGIN_RULE_UNKNOWN_CUSTOM_USER_FUNCTION,
                                          "Function: {0}".format(func))

    def is_idm(self):
        for key, value in self.__rule_data.iteritems():
            if key == "event_type" and value == "idm-event":
                return True
        return False

    def check(self):
        """Starts all the checks over the plugin rule
        """
        self.__is_idm_plugin = self.is_idm()
        self.check_invalid_fields()
        if not self.__is_database_plugin:
            if self.__is_wmi_plugin:
                self.check_regex_wmi()
            else:
                self.check_regex()
            self.check_values()
        return self.__errors

    def get_rule_data(self):
        return self.__rule_data
