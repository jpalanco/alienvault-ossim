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

from string import strip
import os
import codecs
import re
from ConfigParser import ConfigParser, DEFAULTSECT

from apimethods.apps.plugins.error import ErrorCodes
from apimethods.apps.plugins.prule import PluginRule
from apiexceptions.plugin import APIPluginFileNotFound


class PluginFile(ConfigParser):
    """
    PluginFile class. Loads a plugin file and checks it.
    """
    NEEDED_CONFIG_ENTRIES = {
        'config': ['type', 'source', 'enable']
    }
    SECTIONS_NOT_RULES = ["config", "info", "translation", "DEFAULT"]
    OPTCRE = re.compile(
        r'(?P<option>[^:=\s][^=]*)'
        r'\s*(?P<vi>[:=])\s*'
        r'(?P<value>.*)$'
    )
    CONFIG_ALLOW_ENTRIES = ["type",
                            "enable",
                            "source",
                            "location",
                            "rlocation",
                            "create_file",
                            "process",
                            "start",
                            "stop",
                            "startup",
                            "shutdown",
                            "exclude_sids",
                            "exclude_inventory_sources",
                            "plugin_id",  # ConfigParsers use DEFAULT as a special section. We're using it,
                            # and the parsers stores its values on config (the next one)
                            "dst_ip",
                            "dst_port",
                            "src_ip",
                            "src_port",
                            "custom_functions_file",
                            "restart_interval",
                            "interface",
                            "restart",
                            "precheck",
                            "rlocation",
                            "sleep",
                            "user",
                            "password",
                            "pre_match",
                            "inventory_source",
                            "ip",
                            "mac",
                            "interfaces",
                            "ossim_dsn",
                            "source_ip",  # for sdee plugins
                            ]
    ALLOW_DATABASE_CONFIG_ENTRIES = ["source_type",
                                     "db",
                                     "source_ip",
                                     "source_port",
                                     "dsn",
                                     "host"]

    ALLOW_SNORT_UNIFIED_CONFIG_ENTRIES = ["prefix",
                                          "directory",
                                          "unified_version",
                                          "linklayer"
                                          ]
    ALLOW_REMOTE_LOG_CONFIG_ENTRIES = ["readall",
                                       "host",
                                       "passwd",
                                       ]
    ALLOW_WMI_CONFIG_ENTRIES = ["credentials_file",
                                "section"
                                ]
    BOOLEAN_VALUES_ALLOWED = ["yes", "true", "1", "no", "false", "0"]
    PLUGIN_TYPES_ALLOWED = ["detector", "monitor"]
    SOURCE_TYPES_ALLOWED = ["log", "snortlog", "snortnewlog", "snortsyslogbin", "database", "wmi", "sdee", "remote-log",
                            "ftp", "command", "http"]

    def __init__(self):
        ConfigParser.__init__(self)
        # Cannot use super since ConfigParser is an old style class (it doesn't inherit from object)
        # super(PluginFile, self).__init__()
        self._headers = []
        self.__plugin_file = ""
        self.__plugin_id = 0
        self.__errors = []
        self.__rule_errors = {}
        self.__custom_user_functions = []
        self.__encoding = None
        self.__total_errors = 0
        self.__not_a_plugin_file = False
        self.__plugin_rules = None
        self.__translate2_sections = []

    @property
    def plugin_file(self):
        return self.__plugin_file

    @plugin_file.setter
    def plugin_file(self, value):
        self.__plugin_file = value

    @property
    def plugin_id(self):
        return self.__plugin_id

    @plugin_id.setter
    def plugin_id(self, value):
        self.__plugin_id = value

    @property
    def errors(self):
        return self.__errors

    def append_error(self, code, msg=""):
        """Appends an error to the error list"""
        self.__errors.append(ErrorCodes.get_detected_error_obj(code, msg))

    @property
    def rule_errors(self):
        return self.__rule_errors

    def append_rule_error(self, rule_name, rule_error):
        self.__rule_errors[rule_name] = rule_error

    @property
    def custom_user_functions(self):
        return self.__custom_user_functions

    def append_custom_user_function(self, value):
        self.__custom_user_functions.append(value)

    @property
    def encoding(self):
        return self.__encoding

    @encoding.setter
    def encoding(self, value):
        self.__encoding = value

    @property
    def total_errors(self):
        return self.__total_errors

    @total_errors.setter
    def total_errors(self, value):
        self.__total_errors = value

    def increase_total_errors(self):
        self.__total_errors += 1

    def decrease_total_errors(self):
        self.__total_errors -= 1

    @property
    def not_a_plugin_file(self):
        return self.__not_a_plugin_file

    @not_a_plugin_file.setter
    def not_a_plugin_file(self, value):
        self.__not_a_plugin_file = value

    @property
    def translate2_sections(self):
        return self.__translate2_sections

    def append_translate2_section(self, section):
        self.__translate2_sections.append(section)

    @staticmethod
    def strip_value(value):
        """avoid confusions in configuration values"""
        return strip(strip(value, '"'), "'")

    def read(self, plugin_file, encoding):
        """Reads the plugin file
        Raises:
            APIPluginFileNotFound: When the plugin file cannot be read
        """
        self.plugin_file = plugin_file
        del self.errors[:]
        self.rule_errors.clear()
        self.total_errors = 0
        if not os.path.isfile(plugin_file):
            raise APIPluginFileNotFound(plugin_file)
        fp = None
        try:
            fp = codecs.open(plugin_file, 'r', encoding=encoding)
            self._read_headers(fp)
            self.readfp(fp)
            self.plugin_file = plugin_file
            self.encoding = encoding
        except:
            fp = None
            self.append_error(ErrorCodes.PLUGIN_FILE_CANT_BE_READED_ERROR, "")
            self.not_a_plugin_file = True
        finally:
            if fp:
                fp.close()

    def _read_headers(self, fp):
        """ Reads headers from original config file and stores them

        Args:
            fp:
        """
        try:
            for line in fp:
                line = line.strip()
                if line.startswith('#'):
                    self._headers.append(line)
                else:
                    # stop right after we read the header - no need to parse the rest of comments.
                    break
        finally:
            # Move to the beginning again to process file as usual.
            fp.seek(0, os.SEEK_SET)

    def _write_headers(self, fp, headers=None):
        headers = headers or self._headers
        for header in headers:
            fp.write("{}\n".format(header))
        fp.write("\n")

    def write(self, fp):
        """ Writes plugin content to a file with appropriate formatting (without whitespace surrounding operator).

        Args:
            fp: (obj) Open file.
        """
        if self._defaults:
            fp.write("[{}]\n".format(DEFAULTSECT))
            for (key, value) in self._defaults.items():
                fp.write("{}={}\n".format(key, str(value).replace('\n', '\n\t')))
            fp.write("\n")
        for section in self._sections:
            fp.write("[{}]\n".format(section))
            for (key, value) in self._sections[section].items():
                if key == "__name__":
                    continue
                if (value is not None) or (self._optcre == self.OPTCRE):
                    key = "=".join((key, str(value).replace('\n', '\n\t')))
                fp.write("{}\n".format(key))
            fp.write("\n")

    def save(self, destination, vendor="", model="", version="-", product_type=None):
        """ Stores plugin data to destination path file in "INI/CFG" file format.

        Args:
            destination: (str) cfg destination path.
            vendor: (str) plugin vendor.
            model: (str) plugin model.
            version: (str) plugin version.
            product_type: (int) product_type ID.
        """
        try:
            # extend headers with the vendor-model-version info just before saving
            # in order to eliminate possibility to add the same header multiple times.
            headers_with_extra_data = list()
            headers_with_extra_data.extend(self._headers)
            headers_with_extra_data.extend(['# Plugin Selection Info:', '# {}:{}:{}'.format(vendor, model, version)])

            # save .cfg file
            with open(destination, 'w') as config_file:
                self._write_headers(config_file, headers=headers_with_extra_data)
                self.write(config_file)
        except Exception as err:
            self.append_error(ErrorCodes.OUTPUT_FILE_WRITE_ERROR, msg=str(err))
            return False

        return True

    def __look_for_translate2_sections(self):
        """Look inside the plugin for translate2 sections"""
        translate2_regex = re.compile(".*(\{translate2\(\$(?P<ruleid>[^\)]+)\,\$(?P<section_name>[^\)]+)\)\})",
                                      re.UNICODE)
        with open(self.plugin_file, "r") as f:
            for line in f.readlines():
                data = translate2_regex.match(line)
                if data is not None:
                    for key, value in data.groupdict().iteritems():
                        if key == "section_name":
                            if value not in self.translate2_sections and self.has_section(value):
                                self.append_translate2_section(value)

    def check(self):
        """Runs the plugin checks
        Args:
            -
        Returns:
            True is all is ok, otherwise False
        """
        # Check for translate2 sections
        if self.not_a_plugin_file:
            self.total_errors += len(self.errors)
            return False

        if self.has_option("DEFAULT", "plugin_id"):
            try:
                self.plugin_id = int(self.get("DEFAULT", "plugin_id"))
            except ValueError, e:
                self.append_error(ErrorCodes.PLUGIN_ID_INVALID_ERROR, self.get("DEFAULT", "plugin_id"))
        else:
            self.append_error(ErrorCodes.PLUGIN_ID_MANDATORY_ERROR)
        if not self.is_monitor_plugin():
            self.__look_for_translate2_sections()
            self.__check_needed_config_entries()
            self.__check_config_section()
            self.__check_duplicate_rules()
            self.__plugin_rules = self.__get_plugin_rules()
            self.__check_rules()
        self.total_errors += len(self.errors)
        return self.__build_output()

    def get_latest_error_msg(self):
        """ Returns latest error message.
        """
        return str(self.errors[-1]) if len(self.errors) > 0 else ""

    def __check_custom_user_functions(self, functions_file):
        """Checks the user custom functions file
        """
        if not os.path.isfile(functions_file):
            self.append_error(ErrorCodes.PLUGIN_USER_CUSTOM_FUNTION_FILE_NOT_FOUND, "File: {0}".format(functions_file))
        else:
            try:
                f = open(functions_file, 'rb')
                lines = f.read()
                result = re.findall("Start Function\s+(\w+)\n(.*?)End Function", lines, re.M | re.S)
                function_list = {}
                for name, function in result:
                    try:
                        exec function.strip() in function_list
                        self.append_custom_user_function(name)
                    except Exception, e:
                        self.append_error(ErrorCodes.PLUGIN_USER_CUSTOM_FUNTION_FILE_COMPILE_ERROR,
                                          "Error: %s" % str(e))
            except:
                self.append_error(ErrorCodes.PLUGIN_USER_CUSTOM_FUNTION_FILE_ERROR_LOADING_FILE)

    def __check_config_section(self):
        """Checks the config section values
        """
        config_items = self.__hitems("config")
        for key, value in config_items.iteritems():
            if key not in PluginFile.CONFIG_ALLOW_ENTRIES and not \
                    (self.is_database_plugin() and key in PluginFile.ALLOW_DATABASE_CONFIG_ENTRIES) and not \
                    (self.is_snort_plugin() and key in PluginFile.ALLOW_SNORT_UNIFIED_CONFIG_ENTRIES) and not \
                    (self.is_remote_log_plugin() and key in PluginFile.ALLOW_REMOTE_LOG_CONFIG_ENTRIES) and not \
                    (self.is_wmi_log_plugin() and key in PluginFile.ALLOW_WMI_CONFIG_ENTRIES):
                self.append_error(ErrorCodes.PLUGIN_CONFIG_INVALID_ENTRY, " '{0}'='{1}'".format(key, value))
            if key == "type" and value not in PluginFile.PLUGIN_TYPES_ALLOWED:
                self.append_error(ErrorCodes.PLUGIN_INVALID_VALUE_FOR_ENTRY, " '{0}'='{1}'".format(key, value))
            if key in ["enable", "create_file", "start",
                       "stop"] and value.lower() not in PluginFile.BOOLEAN_VALUES_ALLOWED:
                self.append_error(ErrorCodes.PLUGIN_INVALID_VALUE_FOR_ENTRY, " '{0}'='{1}'".format(key, value))
            if key == "source" and value.lower() not in PluginFile.SOURCE_TYPES_ALLOWED:
                self.append_error(ErrorCodes.PLUGIN_INVALID_VALUE_FOR_ENTRY, " '{0}'='{1}'".format(key, value))
            if key == 'custom_functions_file':
                self.__check_custom_user_functions(value)

    def __check_needed_config_entries(self):
        """Check for needed entries in the cfg file.
        """
        for section, values in PluginFile.NEEDED_CONFIG_ENTRIES.iteritems():
            if not self.has_section(section):
                self.append_error(ErrorCodes.PLUGIN_MANDATORY_SECTION_NOT_FOUND_ERROR,
                                  "Section: {0}".format(section))
            for value in values:
                if not self.has_option(section, value):
                    self.append_error(ErrorCodes.PLUGIN_MANDATORY_SECTION_NOT_FOUND_ERROR,
                                      "Section: {0}, value: {1}".format(section, value))

    def __check_duplicate_rules(self):
        """Check for duplicate rule names.
        To do that we should read and parse the plugin file line by line
        """
        plugin_file_data = open(self.plugin_file, 'r')
        regex = re.compile('\[(?P<header>[^]]+)\]')
        rules = {}
        for line in plugin_file_data:
            dd = regex.match(line)
            if dd:
                section_name = dd.group('header')
                if section_name.lower() not in PluginFile.SECTIONS_NOT_RULES:
                    if section_name not in rules:
                        rules[section_name] = 1
                    else:
                        self.append_error(ErrorCodes.PLUGIN_RULE_DUPLICATE_RULE, section_name.lower())

    def __check_rules(self):
        """Check the plugin rules.
        """

        if len(self.__plugin_rules) == 0 and not self.is_snort_plugin() and not self.is_sdee_plugin():
            self.append_error(ErrorCodes.PLUGIN_WITHOUT_RULES, "")
            return False
        for rule_name, plugin_rule in self.__plugin_rules.iteritems():
            error_list = plugin_rule.check()
            self.append_rule_error(rule_name, error_list)
            self.total_errors += len(error_list)
        return True

    def __get_rules_hash(self):
        """Returns a plugin rules hash
        """
        rules = {}
        for section in sorted(self.sections()):
            if section.lower() not in PluginFile.SECTIONS_NOT_RULES + self.translate2_sections:
                rules[section] = self.__hitems(section, True)
        return rules

    def __get_plugin_rules(self):
        """Returns a hash table of PluginRule Objects
        """
        rules = self.__get_rules_hash()
        plugin_rules = {}
        has_translation_section = self.has_section("translation")
        has_user_custom_function_file = self.has_option('config', 'custom_functions_file')
        for rule_name, rule_data in rules.iteritems():
            plugin_rules[rule_name] = PluginRule(rule_name,
                                                 rule_data,
                                                 self.custom_user_functions,
                                                 has_translation_section,
                                                 has_user_custom_function_file,
                                                 self.is_database_plugin(),
                                                 self.is_wmi_log_plugin(),
                                                 self.translate2_sections)
        return plugin_rules

    def __hitems(self, section, braw=False):
        """same as ConfigParser.items() but returns a hash instead of a list
        @param section: Section whose items should returns to
        @param braw: Be Raw
        """
        hashtable = {}
        for item in self.items(section, braw):
            hashtable[item[0]] = PluginFile.strip_value(item[1])
        return hashtable

    def __build_output(self):
        """Returns a python dic with all the plugin detected errors.
        """
        base_json = {
            "filename": self.plugin_file,
            "id": self.plugin_id,
            "error_count": self.total_errors,
            "errors": self.errors,
            "rules": []
        }
        for rule_name, error_list in self.rule_errors.iteritems():
            n_errors = len(error_list)
            regexp = ""
            if rule_name in self.__plugin_rules:
                regexp = self.__plugin_rules[rule_name].regexp
            rule_dic = {
                "name": rule_name,
                "regexp": regexp,
                "error_count": n_errors,
                "errors": error_list
            }
            base_json["rules"].append(rule_dic)

        return base_json

    def plugin_type(self):
        return self.get("config", "type")

    def plugin_enable(self):
        return self.getboolean("config", "enable")

    def plugin_source(self):
        if self.has_option("config", "source"):
            return self.get("config", "source")
        return ""

    def is_database_plugin(self):
        """Returns if the current plugin is a database plugin"""
        if self.has_option("config", "source"):
            plugin_type = self.get("config", "source")
            if plugin_type == "database":
                return True
        return False

    def is_snort_plugin(self):
        """Returns if the current plugin is a snort plugin"""
        if self.has_option("config", "source"):
            plugin_type = self.get("config", "source")
            if plugin_type == "snortlog":
                return True
        return False

    def is_sdee_plugin(self):
        """Returns if the current plugin is a sdee plugin"""
        if self.has_option("config", "source"):
            plugin_type = self.get("config", "source")
            if plugin_type == "sdee":
                return True
        return False

    def is_remote_log_plugin(self):
        """Returns if the current plugin is a remote-log plugin"""
        if self.has_option("config", "source"):
            plugin_type = self.get("config", "source")
            if plugin_type == "remote-log":
                return True
        return False

    def is_monitor_plugin(self):
        """Returns if the current plugin is a remote-log plugin"""
        if self.has_option("config", "type"):
            plugin_type = self.get("config", "type")
            if plugin_type == "monitor":
                return True
        return False

    def is_wmi_log_plugin(self):
        """Returns if the current plugin is a wmi plugin"""
        if self.has_option("config", "source"):
            plugin_type = self.get("config", "source")
            if plugin_type == "wmi":
                return True
        return False

    def plugin_location(self):
        if self.has_option("config", "location"):
            return self.get("config", "location")
        return ""

    def plugin_create_file(self):
        return self.getboolean("config", "create_file")

    def plugin_process(self):
        if self.has_option("config", "process"):
            return self.get("config", "process")
        return ""

    def plugin_process_start(self):
        if self.has_option("config", "start"):
            return self.getboolean("config", "start")
        return ""

    def plugin_process_stop(self):
        if self.has_option("config", "stop"):
            return self.getboolean("config", "stop")
        return ""

    def plugin_process_startup(self):
        if self.has_option("config", "startup"):
            return self.get("config", "startup")
        return ""

    def plugin_process_shutdown(self):
        if self.has_option("config", "shutdown"):
            return self.get("config", "shutdown")
        return ""

    def translation_hash(self):
        tr = {}
        if self.has_section("translation"):
            defaults_tmp = self._defaults
            self._defaults = {}
            tr = dict(self.items('translation'))
            self._defaults = defaults_tmp
        return tr
