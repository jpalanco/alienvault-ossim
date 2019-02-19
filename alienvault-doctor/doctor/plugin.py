#
#  License:
#
#  Copyright (c) 2013 AlienVault
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

import subprocess
import MySQLdb

from os import path

import ConfigParser
from ConfigParser import RawConfigParser
from distutils.version import LooseVersion

import dependency
from output import Output, log_debug
from wildcard import Wildcard
from check import Check
from error import PluginError, PluginConfigParserError, CheckError


class PluginConfigParser (RawConfigParser, object):
    '''
    Class PluginConfigParser.
    Parses a plugin configuration file and checks it for inconsistencies.
    '''
    # Load a specific plugin file and returns it.
    def read(self, filename):
        try:
            RawConfigParser.read(self, filename)
            self.__check_dependencies__(filename)
        except ConfigParser.Error, e:
            raise PluginConfigParserError('Cannot read file %s: %s' % (filename, e))

    # Load a specific plugin file using a file descriptor and returns it.
    def readfp(self, fp):
        try:
            RawConfigParser.readfp(self, fp)
            self.__check_dependencies__()
        except ConfigParser.Error, e:
            raise PluginConfigParserError('Cannot read file with descriptor %d: %s' % (fp, e))

    def get(self, section, option):
        if self.has_option(section, option):
            return super(PluginConfigParser, self).get(section, option)

        return None

    # Check dependencies on plugin configuration options.
    def __check_dependencies__(self, filename):
        # Check for exclusive mandatory options.
        for section in self.sections():
            options = self.options(section)
            for moption in dependency.moptions:
                if sum([int(x in moption) for x in options]) > 1:
                    raise PluginConfigParserError('Incompatible options in section %s' % section, filename)

        # Check for section dependencies.
        for key in dependency.sections.keys():
            if self.has_section(key):
                deps = dependency.sections.values()
                for dep in deps:
                    for key in dep.iterkeys():
                        if self.has_section(key) and key.startswith('!'):
                            raise PluginConfigParserError('File "%s" does not met section dependency for section "%s"' % (filename, key), filename)
                        elif not self.has_section(key):
                            raise PluginConfigParserError('File "%s" does not met section dependency for section "%s"' % (filename, key), filename)

                        for value in dep[key]:
                            if self.has_option(key, value) and value.startswith('!'):
                                raise PluginConfigParserError('File "%s" does not met option dependency for section "%s"' % (filename, key), filename)
                            elif not self.has_option(key, value):
                                raise PluginConfigParserError('File "%s" does not met option dependency for section "%s"' % (filename, key), filename)

    # Check for option dependencies.
    # TODO


class Plugin:
    '''
    Plugin class.
    Defines a plugin with a set of conditions/actions.
    '''
    # # def __init__(self, filename, alienvault_config, severity_list, appliance_type_list, verbose, raw):
    def __init__(self, filename, config_file, alienvault_config, severity_list, appliance_type_list, ignore_dummy_platform, verbose, raw):
        # Common properties.
        self.__config_file = None
        self.__sections = []
        self.__alienvault_config = {}
        self.__severity_list = []
        self.__appliance_type_list = []
        self.__ignore_dummy_platform = False
        self.__verbose = 0
        self.__raw = False
        self.__name = ''
        self.__id = ''
        self.__description = ''
        self.__type = ''
        self.__exclude = ''
        self.__category = []
        self.__requires = []
        self.__profiles = []
        self.__cutoff = False
        self.__strike_zone = False
        self.__raw_limit = 0
        self.__result = True

        # 'file' type properties.
        self.__filename = ''
        self.__file_must_exist = False
        self.__check_force_true = False

        # 'command' type properties.
        self.__command = ''

        # Shared properties for 'file' and 'command' types.
        self.__data = ''
        self.__data_len = ''

        # 'db' type properties.
        self.__host = ''
        self.__user = ''
        self.__password = ''
        self.__database = ''
        self.__db_conn = None
        self.__db_cursor = None

        # Plugin defined checks.
        self.__checks = []

        self.__config_file = config_file
        self.__alienvault_config = alienvault_config
        self.__severity_list = severity_list
        self.__appliance_type_list = appliance_type_list
        self.__ignore_dummy_platform = ignore_dummy_platform
        self.__verbose = verbose
        self.__raw = raw

        # try:
        #     # Parse the plugin configuration file.
        #     self.__config_file = PluginConfigParser()
        #     self.__config_file.read(filename)
        # except PluginConfigParserError:
        #     raise
        # except Exception as e:
        #     raise PluginError('Cannot parse plugin file "%s": %s' % (filename, str(e)), filename)

        try:
            # Parse 'check' sections.
            self.__sections = self.__config_file.sections()
            self.__name = self.__config_file.get('properties', 'name')
            self.__id = self.__config_file.get('properties', 'id')
            self.__description = self.__config_file.get('properties', 'description')
            self.__type = self.__config_file.get('properties', 'type')
            self.__category = self.__config_file.get('properties', 'category').split(',')

            plugin_data = {'id': self.__id if self.__id else '',
                           'type': self.__type if self.__type else '',
                           'description': self.__description if self.__description else ''}
            if self.__verbose > 0:
                Output.emphasized('\nRunning plugin "%s"...\n' % self.__name, [self.__name])

            if self.__config_file.has_option('properties', 'requires'):
                self.__requires = self.__config_file.get('properties', 'requires').split(';')
                self.__check_requirements__()

            # Check for the 'cutoff' option
            if self.__config_file.has_option('properties', 'cutoff'):
                self.__cutoff = self.__config_file.getboolean('properties', 'cutoff')

            # Check for the 'strike_zone' option
            if self.__config_file.has_option('properties', 'affects_strike_zone'):
                self.__strike_zone = self.__config_file.getboolean('properties', 'affects_strike_zone')

            # Check for the 'file_must_exist' option
            if self.__config_file.has_option('properties', 'file_must_exist'):
                self.__file_must_exist = self.__config_file.getboolean('properties', 'file_must_exist')

            # Check for the 'exclude' option
            if self.__config_file.has_option('properties', 'exclude'):
                self.__exclude = self.__config_file.get('properties', 'exclude').split(',')
                for excluding_profile in self.__exclude:
                    excluding_profile = excluding_profile.strip()
                    if excluding_profile in self.__alienvault_config['hw_profile']:
                        raise PluginError(msg='Plugin cannot be executed in %s' % self.__alienvault_config['hw_profile'],
                                          plugin=self.__name,
                                          plugin_data=plugin_data)

            # Check for the 'limit' option (in kbytes), used in combination with the raw data output. '0' means no limit.
            if self.__raw and self.__config_file.has_option('properties', 'raw_limit'):
                self.__raw_limit = self.__config_file.getint('properties', 'raw_limit')

            # Check for profile & version where this plugin is relevant.
            if self.__config_file.has_option('properties', 'profiles'):
                profiles_versions = self.__config_file.get('properties', 'profiles')
                self.__profiles = [(x.partition(':')[0], x.partition(':')[2]) for x in profiles_versions.split(';')]

                for (profile, version) in self.__profiles:
                    if profile == '' or version == '':
                        raise PluginError(msg='Empty profile or version in "profiles" field',
                                          plugin=self.__name,
                                          plugin_data=plugin_data)

                    if profile not in self.__alienvault_config['sw_profile'] and \
                       profile != self.__alienvault_config['hw_profile']:
                        raise PluginError(msg='Profile "%s" does not match installed profiles' % profile,
                                          plugin=self.__name,
                                          plugin_data=plugin_data)

                    if version.startswith('>'):
                        ret = LooseVersion(self.__alienvault_config['version']) > LooseVersion(version[1:])
                    elif version.startswith('<'):
                        ret = LooseVersion(self.__alienvault_config['version']) < LooseVersion(version[1:])
                    elif version.startswith('=='):
                        ret = LooseVersion(self.__alienvault_config['version']) == LooseVersion(version[2:])
                    elif version.startswith('!='):
                        ret = LooseVersion(self.__alienvault_config['version']) != LooseVersion(version[2:])
                    else:
                        raise PluginError(msg='Profile "%s" version does not match installed profiles' % profile,
                                          plugin=self.__name,
                                          plugin_data=plugin_data)

            # Ugly...
            if self.__type == 'file':
                self.__init_file__()
            elif self.__type == 'command':
                self.__init_command__()
            elif self.__type == 'db':
                self.__init_db__()
            elif self.__type == 'hardware':
                pass
            else:
                raise PluginError(msg='Unknown type',
                                  plugin=self.__name,
                                  plugin_data=plugin_data)

            # Parse 'check' sections.
            sections = self.__config_file.sections()
            for section in sections:
                if section != 'properties':
                    check = Check(self, section)
                    needs_deletion = False
                    if not check.check_appliance_type(self.__alienvault_config['hw_profile'],
                                                      self.__appliance_type_list,
                                                      self.__ignore_dummy_platform):
                        Output.info("\nCheck %s is not meant to be run in %s" % (section,
                                                                                 self.__alienvault_config['hw_profile']))
                        needs_deletion = True

                    elif not check.check_version(self.__alienvault_config['version']):
                        Output.info("\nCheck %s cannot be run in version %s" % (section,
                                                                                self.__alienvault_config['version']))
                        needs_deletion = True

                    elif not check.check_version_type():
                        Output.info("\nCheck %s is not meant to be run in a %s license" % (section,
                                                                                           self.__alienvault_config['versiontype']))
                        needs_deletion = True
                    if not needs_deletion:
                        self.__checks.append(check)
                    else:
                        del check

        except PluginError:
            raise

        except PluginConfigParserError:
            raise

        except CheckError as e:
            plugin_data = {'id': self.__id if self.__id else '',
                           'type': self.__type if self.__type else '',
                           'description': self.__description if self.__description else ''}
            raise PluginError(msg=e.msg,
                              plugin=e.plugin,
                              plugin_data=plugin_data)

        except Exception as e:
            raise PluginError(msg='%s' % str(e),
                              plugin=filename,
                              plugin_data={})

    # Check for plugin requirements.
    # Currently, requirement types could be 'modules', 'files', 'dpkg', 'hardware'.
    def __check_requirements__(self):

        plugin_data = {'id': self.__id if self.__id else '',
                       'type': self.__type if self.__type else '',
                       'description': self.__description if self.__description else ''}

        for requirement in self.__requires:
            req_type, req_data = requirement.split(':')
            req_data_split = req_data.split(',')

            if req_type == '@modules':
                # Would be nice to rewrite this using python-kmod
                # https://github.com/agrover/python-kmod
                for req_module in req_data_split:
                    command = 'lsmod|grep ' + req_module
                    proc = subprocess.Popen(command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                    data, err = proc.communicate()
                    if data == '':
                        raise PluginError(msg='Required module "%s" is not present' % req_module,
                                          plugin=self.__name,
                                          plugin_data=plugin_data)
            elif req_type == '@files':
                for req_file in req_data_split:
                    if not path.exists(req_file):
                        raise PluginError(msg='Required file "%s" does not exist' % req_file,
                                          plugin=self.__name,
                                          plugin_data=plugin_data)
            elif req_type == '@dpkg':
                for req_pkg in req_data_split:
                    command = 'dpkg -l ' + req_pkg
                    proc = subprocess.Popen(command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                    data, err = proc.communicate()
                    if err != '':
                        raise PluginError(msg='Required package "%s" is not installed' % req_pkg,
                                          plugin=self.__name,
                                          plugin_data=plugin_data)
            elif req_type == '@hardware':
                self.__check_hardware_requirements__(req_data_split)
            else:
                raise PluginError(msg='Unknown requirement type: %s' % req_type,
                                  plugin=self.__name,
                                  plugin_data=plugin_data)

    # Check for hardware requirements.
    # # Items in the list are defined this way: hardware/attribute/value
    # def __check_hardware_requirements__(self, hw_list):
    #     plugin_data = {'id': self.__id if self.__id else '',
    #                    'type': self.__type if self.__type else '',
    #                    'description': self.__description if self.__description else ''}
    #     # Check hardware requirements.
    #     for hw_req in hw_list:
    #         (hw_req_pretty, eval_str) = Wildcard.hw_config(hw_req)
    #         try:
    #             if not eval(eval_str):
    #                 raise PluginError(msg=hw_req_pretty,
    #                                   plugin=self.__name,
    #                                   plugin_data=plugin_data)
    #         except PluginError:
    #             raise
    #         except:
    #             raise PluginError(msg='Expression "%s" cannot be evaluated' % eval_str,
    #                               plugin=self.__name,
    #                               plugin_data=plugin_data)

    # Initialize 'file' type plugin properties.
    def __init_file__(self):
        plugin_data = {'id': self.__id if self.__id else '',
                       'type': self.__type if self.__type else '',
                       'description': self.__description if self.__description else ''}
        self.__filename = self.__config_file.get('properties', 'filename')

        try:
            fp = open(self.__filename, 'r')
            self.__data = fp.read()
            self.__data_len = len(self.__data)
        except IOError as e:
            if e.strerror == "No such file or directory" and not self.__file_must_exist:
                self.__check_force_true = True
            else:
                raise PluginError(msg='Cannot parse file "%s": %s' % (self.__filename, e),
                                  plugin=self.__name,
                                  plugin_data=plugin_data)
        except Exception as e:
            raise PluginError(msg='Cannot parse file "%s": %s' % (self.__filename, e),
                              plugin=self.__name,
                              plugin_data=plugin_data)

    # Initialize 'command' type plugin properties.
    def __init_command__(self):
        plugin_data = {'id': self.__id if self.__id else '',
                       'type': self.__type if self.__type else '',
                       'description': self.__description if self.__description else ''}
        self.__command = self.__config_file.get('properties', 'command')
        self.__command = Wildcard.av_config(self.__command)

        try:
            proc = subprocess.Popen(self.__command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            self.__data, err = proc.communicate()
            self.__data_len = len(self.__data)
        except Exception as e:
            raise PluginError(msg='Cannot run command "%s": %s' % (self.__command, e),
                              plugin=self.__name,
                              plugin_data=plugin_data)

    # Initialize 'db' type plugin properties.
    def __init_db__(self):
        plugin_data = {'id': self.__id if self.__id else '',
                       'type': self.__type if self.__type else '',
                       'description': self.__description if self.__description else ''}
        self.__host = Wildcard.av_config(self.__config_file.get('properties', 'host')) if self.__config_file.get('properties', 'host') else self.__alienvault_config['dbhost']
        self.__user = Wildcard.av_config(self.__config_file.get('properties', 'user')) if self.__config_file.get('properties', 'user') else self.__alienvault_config['dbuser']
        self.__password = Wildcard.av_config(self.__config_file.get('properties', 'password')) if self.__config_file.get('properties', 'password') else self.__alienvault_config['dbpass']
        self.__database = self.__config_file.get('properties', 'database')

        try:
            self.__db_conn = MySQLdb.connect(host=self.__host, user=self.__user, passwd=self.__password, db=self.__database)
            self.__db_cursor = self.__db_conn.cursor()
        except Exception as e:
            raise PluginError(msg='Cannot connect to database: %s' % e,
                              plugin=self.__name,
                              plugin_data=plugin_data)

    # Getter/setter methods.
    def get_name(self):
        return self.__name

    def get_description(self):
        return self.__description

    def get_category(self):
        return self.__category

    def get_config_file(self):
        return self.__config_file

    def get_data(self):
        return self.__data

    def get_checks_len(self):
        return len(self.__checks)

    def get_filename(self):
        return self.__filename

    def get_result(self):
        return self.__result

    def get_alienvault_config(self):
        return self.__alienvault_config

    def get_ignore_dummy_platform(self):
        return self.__ignore_dummy_platform

      # Check if any of the categories match with the plugin ones.
    def check_category(self, categories):
        # Treat the empty list as 'all'
        if categories == []:
            return True

        # Search for the 'all' wildcard.
        if 'all' in categories:
            return True

        for category in self.__category:
            if category in categories:
                return True
        return False

    # Run a query with the userdata placed in the plugin.
    def run_query(self, query, result=False):
        try:
            self.__db_cursor.execute(query)
        except Exception as e:
            Output.warning('Cannot run query for plugin "%s": %s' % (self.__name, e))
            return []

        if result:
            try:
                rows = self.__db_cursor.fetchall()
            except Exception as e:
                Output.warning('Cannot run query for plugin "%s": %s' % (self.__name, e))
                return []

            self.__data += query + '\n' + str(rows) + '\n\n'
            return list(rows)

        return []

    # Run checks.
    def run(self):
        total = 0
        passed = 0

        json_msg = {'id': self.__id,
                    'name': self.__name,
                    'description': self.__description,
                    'checks': {},
                    }

        if self.__strike_zone:
            json_msg['strike_zone'] = True

        if self.__raw:
            json_msg['source'] = unicode(self.__data[-(self.__raw_limit * 1024):], errors='replace')

        for check in self.__checks:
            total += 1
            result = False
            msg = ''
            if self.__check_force_true:
                result = True
            else:
                result, msg, fo = check.run()
                fo = fo.lstrip().split('\n\t')[-1]

            # Prepare 'command' field
            aux_command = ''
            if self.__type == "file":
                aux_command = "cat %s" % self.__filename
            elif self.__type == "command":
                aux_command = self.__command
            elif self.__type == "db":
                aux_command = "echo '%s' | ossim-db" % check.get_query()

            if not result:
                if self.__verbose >= 2:
                    Output.error("Check '%s' failed: %s" % (check.get_name(), msg))
                elif self.__verbose == 1:
                    Output.error("Check '%s' failed" % check.get_name())

                if self.__alienvault_config['has_ha'] and check.get_ha_dependant():
                        json_msg['checks'][check.get_name()] = {'result': 'passed',
                                                                'severity': check.get_severity(),
                                                                'description': check.get_description(),
                                                                'summary': 'HA configuration detected. Invalid issue: %s. Disregard this check' % check.get_summary_failed(),
                                                                'remediation': check.get_remediation(),
                                                                'detail': fo.lstrip().replace('\n\t', ';'),
                                                                'debug_detail': msg.lstrip().replace('\n\t', ';'),
                                                                'pattern': str(check.get_pattern()),
                                                                'command': aux_command,
                                                                'output': check.get_output(),
                                                                'strike_zone': True}

                elif check.get_severity() != 'Info':
                    json_msg['checks'][check.get_name()] = {'result': 'failed',
                                                            'severity': check.get_severity(),
                                                            'description': check.get_description(),
                                                            'summary': check.get_summary_failed(),
                                                            'remediation': check.get_remediation(),
                                                            'detail': fo.lstrip().replace('\n\t', ';'),
                                                            'debug_detail': msg.lstrip().replace('\n\t', ';'),
                                                            'pattern': check.get_pattern(),
                                                            'command': aux_command,
                                                            'output': check.get_output()}

                    # If current check affects to strike_zone, set 'strike_zone' param to 'False'
                    json_msg['checks'][check.get_name()]['strike_zone'] = False if check.get_strike_zone() else True

                else:
                    json_msg['checks'][check.get_name()] = {'result': 'passed',
                                                            'severity': check.get_severity(),
                                                            'description': check.get_description(),
                                                            'summary': check.get_summary_failed(),
                                                            'remediation': check.get_remediation(),
                                                            'detail': fo.lstrip().replace('\n\t', ';'),
                                                            'debug_detail': msg.lstrip().replace('\n\t', ';'),
                                                            'pattern': check.get_pattern(),
                                                            'command': aux_command,
                                                            'output': check.get_output(),
                                                            'strike_zone': True}

                if json_msg['checks'][check.get_name()]['result'] == 'failed':
                    self.__result &= False

                # Evaluate strike zone. We have to take in mind:
                # 1. If the current plugin affects to the strike zone
                # 2. If the check analysed affects to the strike zone
                # 3. If this is an info check
                if self.__strike_zone:
                    if check.get_strike_zone():
                        if check.get_severity() != 'Info':
                            json_msg['strike_zone'] = False

                # Exit the loop if one check has failed.
                if self.__cutoff:
                    break
            else:
                if self.__verbose > 0:
                    Output.info('Check "%s" passed' % check.get_name())
                if check.get_severity() != 'Debug':
                    json_msg['checks'][check.get_name()] = {'result': 'passed',
                                                            'severity': check.get_severity(),
                                                            'description': check.get_description(),
                                                            'summary': check.get_summary_passed(),
                                                            'pattern': str(check.get_pattern()),
                                                            'command': aux_command,
                                                            'output': check.get_output(),
                                                            'strike_zone': True}
                else:
                    json_msg['checks'][check.get_name()] = {'result': 'passed',
                                                            'severity': check.get_severity(),
                                                            'description': check.get_description(),
                                                            'summary': fo.lstrip().replace('\n\t', ';'),
                                                            'pattern': str(check.get_pattern()),
                                                            'command': aux_command,
                                                            'output': check.get_output(),
                                                            'strike_zone': True}
                passed += 1

        json_msg['result'] = self.get_result()

        if self.__verbose > 0:
            if passed == total:
                Output.emphasized('\nAll checks passed for plugin "%s".' % self.__name, [self.__name])
            else:
                Output.emphasized('\n%d out of %d checks passed for plugin "%s".' % (passed, total, self.__name), [self.__name])

        return (json_msg)
