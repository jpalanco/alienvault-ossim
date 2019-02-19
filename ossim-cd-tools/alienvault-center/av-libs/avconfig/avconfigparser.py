# -*- coding: utf-8 -*-
#
# © Alienvault Inc. 2012
# All rights reserved
#
# This code is protected by copyright and distributed under licenses
# restricting its use, copying, distribution, and decompilation. It may
# only be reproduced or used under license from Alienvault Inc. or its
# authorised licensees.
import re
import os
from avconfigparsererror import AVConfigParserErrors


class InvalidConfigLine(Exception):
    """Invalid line in the config file
    """

    def __init__(self, msg='', filename="", lineno=0):
        self.msg = "Invalid Line:%s:%d -  %s" % (filename, lineno, msg)

    def __str__(self):
        return repr(self.msg)


class DuplicatedConfigSeciton(Exception):
    def __init__(self, msg='', filename="", lineno=0):
        self.msg = "Duplicated Section:%s:%d -  %s" % (filename, lineno, msg)

    def __str__(self):
        return repr(self.msg)


class WriteConfigError(Exception):
    def __init__(self, msg='', filename="", lineno=0):
        self.msg = "Cannot write:%s:%d -  %s" % (filename, lineno, msg)

    def __str__(self):
        return repr(self.msg)


class AVConfigParser():
    """Class to read INI files
    Note: we can't use ConfigParser due to the ossim_setup.conf file format. 
    It's possible to find values without section.
    This code is based on python2.6 RawConfigParser
    """
    VARIABLES_WITHOUT_SECTION = "DEFAULT"
    SECTION_REGEX = re.compile("\[(?P<header>[^]]+)\]")
    OPTION_REGEX = re.compile("^(?P<option>[^\[\]:=\s][^\[\]:=]*)\s*(?P<vi>[:=])\s*(?P<value>.*)$")
    BOOLEAN_VALUES = {'1': True, 'yes': True, 'true': True, 'on': True,
                      '0': False, 'no': False, 'false': False, 'off': False}

    def __init__(self, default_section_for_values_without_section=None):
        """Constructor
        @param default_section_for_values_without_section: Name of the section assigned to 
        those variables that doesn't have section.
        """
        self.__filename = ""
        self.__sections = {}
        self.__defualt = self.VARIABLES_WITHOUT_SECTION
        if default_section_for_values_without_section:
            self.__defualt = default_section_for_values_without_section

    def sections(self):
        """Returns the list of section names.
        Those values without section will be in the default section 
        """
        return self.__sections.keys()

    def has_section(self, section):
        """Indicate whether the named section is present in the configuration."""
        return self.__sections.has_key(section)

    def options(self, section):
        """Return the list of option names for the given section name"""
        if self.__sections.has_key(section):
            return self.__sections[section].keys()
        return []

    def read(self, filename):
        """Reads the given filename   
        @param filename the ossim-setup.conf path
        @returns a tuple (code, "error string") 
        """

        if not os.path.isfile(filename):
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_EXIST)
        try:
            self.__sections.clear()
            configfile = open(filename, 'r')
            current_section = None
            nline = 0
            for line in configfile.readlines():
                nline += 1
                if line.strip() == '' or line[0] in '#;':
                    continue
                line = line.strip()
                sec_data = self.SECTION_REGEX.match(line)
                if sec_data:
                    section_name = sec_data.group('header')
                    if not self.__sections.has_key(section_name):
                        current_section = section_name
                        self.__sections[section_name] = {}
                    else:
                        raise DuplicatedConfigSeciton(filename=filename, lineno=nline, msg=section_name)
                else:
                    opt_data = self.OPTION_REGEX.match(line)
                    if opt_data:
                        optname, vi, optval = opt_data.group('option', 'vi', 'value')
                        if ';' in optval:
                            # ';' is a comment delimiter only if it follows
                            # a spacing character
                            pos = optval.find(';')
                            if pos != -1 and optval[pos - 1].isspace():
                                optval = optval[:pos]
                                # allow empty values
                        if optval == '""':
                            optval = ''
                        optname = optname.rstrip().lower()
                        if not current_section:
                            current_section = self.__defualt
                            self.__sections[current_section] = {}
                        self.__sections[current_section][optname] = optval
                    else:
                        raise InvalidConfigLine(filename=filename, lineno=nline, msg=line)
            configfile.close()
        except Exception, e:
            self.__sections.clear()
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.EXCEPTION, str(e))

        if self.__sections['sensor']['detectors'] != '':
            detector_plugin_list = self.__sections['sensor']['detectors']
            detector_plugin_list = detector_plugin_list.replace(' ', '')
            detector_plugin_list = detector_plugin_list.split(',')

            detector_plugin_list = ["AlienVault_NIDS" if p == "suricata" else p for p in detector_plugin_list]
            detector_plugin_list = ["AlienVault_HIDS" if p == "ossec-single-line" else p for p in detector_plugin_list]
            detector_plugin_list = ["availability_monitoring" if p == "nagios" else p for p in detector_plugin_list]
            detector_plugin_list = ["AlienVault_HIDS-IDM" if p == "ossec-idm-single-line" else p for p in
                                    detector_plugin_list]

            self.__sections['sensor']['detectors'] = ', '.join(detector_plugin_list)

        return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.SUCCESS)

    def get_option(self, section, option):
        """Returns the option value. 
        If section is "", this method will look for the option in the default section.
        If the option is not found or the section doesn't exists
        returns None
        """
        if section == "":
            section = self.__defualt
        if self.__sections.has_key(section):
            if self.__sections[section].has_key(option):
                return self.__sections[section][option]
        return None

    def get_items(self, section):
        """Return the section hash table.
        """
        if section == "":
            section = self.__defualt
        if self.__sections.has_key(section):
            return self.__sections[section]
        return {}

    def has_option(self, section, option):
        """Returns true whether the option exists inside the given section
        Whether the given section is equal to "", it will look for the option 
        inside the default section 
        Returns None
        """
        if section == "":
            section = self.__defualt
        if self.__sections.has_key(section):
            if self.__sections[section].has_key(option):
                return self.__sections[section][option]
        return None

    def get_int(self, section, option):
        """Returns an integer value for the given option whether it exists, 
        (and it's a valid int value),otherwise returns None
        """
        int_value = None
        try:
            if self.has_option(section, option):
                int_value = int(self.get_option(section, option))
        except Exception, e:
            print "Error :%s" % str(e)
            int_value = None
        return int_value

    def get_float(self, section, option):
        """Returns an float value for the given option whether it exists
        (and it's a valid float value),otherwise returns None
        """
        value = None
        try:
            if self.has_option(section, option):
                value = float(self.get_option(section, option))
        except Exception, e:
            print "Error :%s" % str(e)
            value = None
        return value

    def get_boolean(self, section, option):
        """Returns the boolean value for the given option whether it exists 
        (and it's a valid boolean value),otherwise returns None
        """
        value = None
        try:
            if self.has_option(section, option):
                value = self.get_option(section, option)
                if value.lower() not in self.BOOLEAN_VALUES:
                    value = None
                else:
                    value = self.BOOLEAN_VALUES[value.lower()]

        except Exception, e:
            print "Error :%s" % str(e)
            value = None
        return value

    def write(self, filename):
        """Write an .ini-format representation of the configuration state.
        Returns true on success, false otherwise
        """
        try:
            fp = open(filename, 'w')
            if self.__sections.has_key(self.__defualt):
                sorted_keys = sorted(self.__sections[self.__defualt].keys())
                for key in sorted_keys:
                    value = self.__sections[self.__defualt][key]
                    fp.write("%s=%s\n" % (key, str(value).replace('\n', '\n\t')))
                fp.write("\n")
            for section in sorted(self.__sections.keys()):
                if section == self.__defualt:
                    continue
                fp.write("[%s]\n" % section)
                sorted_keys = sorted(self.__sections[section].keys())
                for key in sorted_keys:
                    value = self.__sections[section][key]

                    if section == 'sensor' and key == 'detectors' and value != '':
                        detector_plugin_list = value.replace(' ', '')
                        detector_plugin_list = detector_plugin_list.split(',')

                        detector_plugin_list = ["suricata" if p == "AlienVault_NIDS" else p for p in
                                                detector_plugin_list]
                        detector_plugin_list = ["ossec-single-line" if p == "AlienVault_HIDS" else p for p in
                                                detector_plugin_list]
                        detector_plugin_list = ["nagios" if p == "availability_monitoring" else p for p in
                                                detector_plugin_list]
                        detector_plugin_list = ["ossec-idm-single-line" if p == "AlienVault_HIDS-IDM" else p for p in
                                                detector_plugin_list]

                        value = ', '.join(detector_plugin_list)

                    fp.write("%s=%s\n" % (key, str(value).replace('\n', '\n\t')))
                fp.write("\n")
        except Exception, e:
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.EXCEPTION, str(e))
        return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.SUCCESS)

    def set(self, section, option, value):
        """Set an option."""
        result = True
        if not section or section == "":
            section = self.__defualt
        try:
            self.__sections[section][option] = value
        except KeyError:
            result = False
        return result
