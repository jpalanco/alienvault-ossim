#
#  License:
#
#  Copyright (c) 2003-2006 ossim.net
#  Copyright (c) 2007-2014 AlienVault
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

import sys
import os
import time
import json
import pwd
import grp
from optparse import OptionParser, OptionGroup, SUPPRESS_HELP
from ConfigParser import RawConfigParser
import random
from M2Crypto import EVP, RSA
from zlib import compress
from ftplib import FTP
from os import path, unlink
from base64 import b64encode

import default

from output import *
from sysinfo import Sysinfo
from plugin import Plugin, PluginConfigParser
from error import PluginError, PluginConfigParserError, CheckError
from wildcard import Wildcard

class Doctor:
    '''
    Class Doctor.
    Main class.
    '''
    def __init__(self):
        self.__options = {}
        self.__config = {}
        self.__plugin_list = []
        self.__plugin_dir = ''
        self.__category_list = []
        self.__alienvault_config = {}
        self.__successful_config = {}
        self.__system_summary = []
        self.__summary = {}
        self.__in_strike_zone = True
        self.__ignore_dummy_platform = False
        self.__output_file = ''
        self.__rc = 0

    # Start the actual process.
    def run(self):

        # Parse command line options.
        parser = OptionParser(description='Save the world, one Alien at a time', version=default.version_string)
        parser.add_option("-v", "--verbose", dest="verbose", default=default.verbose, action="count", help="More meaningful warnings [default: %default]. Maximum verbosity depth is 2 (-vv)")
        parser.add_option("-l", "--plugin-list", dest="plugin_list", default=default.plugin_list, help="A list of plugins you want to run, separated by commas [default: run all plugins]")
        parser.add_option("-c", "--category_list", dest="category_list", default=default.category_list, help="A list of plugin categories you want to run [default: run all plugins]")
        parser.add_option("-s", "--severity_list", dest="severity_list", default=default.severity_list, help="A list of check severities you want to run [default: run all checks]")
        parser.add_option("-a", "--appliance_type", dest="appliance_type_list", default=default.appliance_type_list, help="Appliance whose checks you want to run [default: run checks for current appliance] ")
        parser.add_option("-P", "--plugin-dir", dest="plugin_dir", default=default.plugin_dir, help="Directory where plugins are stored [default: %default]")
        parser.add_option("-k", "--ko", dest="ko_only", default=default.ko, action="store_true", help=SUPPRESS_HELP)
        parser.add_option("-i", "--ignore-dummy", dest="ignore_dummy", default=default.ignore_dummy, action="store_true", help=SUPPRESS_HELP)

        output_group = OptionGroup(parser, 'Output options')
        output_group.add_option('-o', '--output-type', dest='output_type', type='choice', choices=default.valid_output_types, default=default.output_type, help='Output type [default: %default]')
        output_group.add_option('-p', '--output-path', dest='output_path', default=default.output_path, help='Output file path [default: %default]')
        output_group.add_option('-f', '--output-file-prefix', dest='output_file_prefix', default=default.output_file_prefix, help='Output file prefix [default: %default]')
        output_group.add_option('-r', '--output-raw', dest='output_raw', default=default.output_raw, action='store_true', help='Retrieve raw data [default: %default]')
        parser.add_option_group(output_group)

        (options, args) = parser.parse_args()

        # Disable normal output for 'ansible' and 'support' output options.
        if options.output_type in ['ansible']:
            Output.set_std_output(False)

        # Ignore dummy platform package
        self.__ignore_dummy_platform = options.ignore_dummy

        # Get basic system info.
        self.__sysinfo = Sysinfo()
        self.__alienvault_config = self.__sysinfo.get_alienvault_config()
        self.__successful_config = self.__sysinfo.get_successful_config()

        Output.emphasized('\nAlienVault Doctor version %s (%s)\n' % (default.version, default.nickname), ['AlienVault Doctor'], [GREEN])

        # Parse Doctor configuration file options.
        if path.isfile(default.doctor_cfg_file):
            self.__parse_doctor_cfg__()
        else:
            Output.warning('Doctor configuration file does not exist, trying to continue...')

        # Parse plugin configuration files.
        if not path.isdir(options.plugin_dir):
            Output.error('"%s" is not a valid directory' % options.plugin_dir)
            sys.exit(default.error_codes['invalid_dir'])
        else:
            self.__plugin_dir = options.plugin_dir

        output_fd = None

        # Parse output options.
        if options.output_type in ['file', 'support']:
            mode = 'w+'

            # Support ticket ID has to be a 8 char long, all digit string.
            if options.output_type == 'support':
                if options.output_file_prefix == default.output_file_prefix or \
                   len(options.output_file_prefix) != 8 or not options.output_file_prefix.isdigit():
                    Output.error('For "support" output, a valid ticket number has to be specified as the file prefix (-f option)')
                    sys.exit(default.error_codes['undef_support_prefix'])
                Output.set_std_output(False)

            if not path.exists(options.output_path):
                os.mkdir(options.output_path)
                Output.info('Output file directory "%s" created\n' % options.output_path)

            if path.isdir(options.output_path):
                try:
                    self.__output_file = path.join(options.output_path, options.output_file_prefix + '-' + str(int(time.time())) + '.doctor')
                    output_fd = open(self.__output_file, mode)
                except IOError as e:
                    Output.warning('Cannot open file "%s" for writing: %s' % (self.__output_file, e))
            else:
                Output.warning('"%s" is not a valid directory, messages will be shown in stdout only' % options.output_path)

        elif options.output_type == 'ansible':
            output_fd = sys.stdout

        elif options.output_type == 'none':
            pass

        else:
            Output.warning('"%s" is not a valid output type, messages will be shown in stdout only' % options.output_type)

        # Show some system info.
        self.__system_summary = self.__sysinfo.show_platform_info(extended=bool(options.verbose))

        if self.__system_summary['Hardware profile'] != 'ossim-free':
            # Run a list of plugins or categories of plugins
            self.__plugin_list = options.plugin_list.split(',')

            if self.__plugin_list == [] or 'all' in self.__plugin_list:
                self.__plugin_list = os.listdir(options.plugin_dir)

            # Filter by category.
            self.__category_list = options.category_list.split(',')

            # Filter checks by severity.
            self.__severity_list = options.severity_list.split(',')

            # Filter checks by appliance_type.
            self.__appliance_type_list = options.appliance_type_list.split(',')

            # Run! Run! Run!
            Output.emphasized('\nHmmm, let the Doctor have a look at you%s' % ('...\n' if options.verbose > 0 else '\n'), ['Doctor'], [GREEN], False)

            for filename in self.__plugin_list:
                if filename.endswith('.plg'):
                    if options.verbose < 1:
                        Progress.dots()
                    self.__run_plugin__(options.plugin_dir + '/' + filename, options.verbose, options.output_raw)

            # Separator
            print ''
        else:
            Output.emphasized('\nThe Doctor is not aimed to diagnose an ossim free version...', ['Doctor'], [GREEN])

        # Show summary only for screen output.
        if options.output_type == default.output_type:
            if self.__system_summary['Hardware profile'] != 'ossim-free':
                if self.__summary != {}:
                    Output.emphasized('\nHooray! The Doctor has diagnosed you, check out the results...', ['Doctor'], [GREEN])
                else:
                    Output.emphasized('\nThe Doctor has finished, nothing to see here though', ['Doctor'], [GREEN])

                # Show if the system is in the 'Strike zone'
                if not self.__in_strike_zone:
                    Output.emphasized('\n  Be careful! Seems that you are not in the Strike Zone! Please check the output below.', ['Strike', 'Zone'], [RED])

                # Show per plugin results.
                plugin_det = {}
                for x, y in self.__summary.iteritems():
                    if self.__plugin_dir in x:
                        Output.emphasized('\n     Plugin %s didn\'t run: %s' % (x, y['summary']), [x])
                        continue
                    ident = int(y['id']) if 'id' in y.keys() else int(x.split(" ", 1)[0])
                    plugin_det[ident] = x

                plugin_ids = plugin_det.keys()
                plugin_ids.sort()

                failing_checks = []
                for plugin_id in plugin_ids:
                    plugin_name = plugin_det[plugin_id]
                    result = self.__summary[plugin_name]
                    plugin_description = result.get('description', None)
                    plugin_strike_zone = result.get('strike_zone', None)

                    if (not 'checks' in result.keys() or not result['checks']) and ('summary' in result.keys()):
                        Output.emphasized('\n     Plugin %s didn\'t run: %s' % (plugin_name, result['summary']), [plugin_name])
                    else:
                        checks = result['checks']
                        header = '\n     Plugin: %s' % plugin_name
                        if plugin_description is not None:
                            header += '\n             %s' % plugin_description
                        if plugin_strike_zone is not None:
                            header += '\n             In the Strike Zone?: %s' % str(plugin_strike_zone)

                        Output.emphasized(header, [plugin_name, 'In the Strike Zone?'])
                        for (check_name, check_result) in checks.items():
                            if check_result['result'] == 'failed':
                                failing_checks.append({'check': check_name,
                                                       'severity': check_result['severity'],
                                                       'strike_zone': check_result['strike_zone'],
                                                       'detail': check_result['detail']})
                                if check_result['severity'] == 'Emerg':
                                    severity_color = RED
                                elif check_result['severity'] == 'Alert':
                                    severity_color = RED
                                elif check_result['severity'] == 'Critical':
                                    severity_color = RED
                                elif check_result['severity'] == 'Error':
                                    severity_color = RED
                                elif check_result['severity'] == 'Warning':
                                    severity_color = YELLOW
                                elif check_result['severity'] == 'Notice':
                                    severity_color = GREEN
                                elif check_result['severity'] == 'Info':
                                    severity_color = GREEN
                                elif check_result['severity'] == 'Debug':
                                    severity_color = BLUE
                                else:
                                    severity_color = YELLOW

                                Output.emphasized('%s[*] %s: %s' % ((' '*9), check_name, check_result['summary']), ['*', check_name], [severity_color, EMPH])
                                if check_result['remediation'] != '':
                                    Output.emphasized('%sWord of advice: %s' % ((' '*13), check_result['remediation']), ['Word of advice'])
                            else:
                                Output.emphasized('%s[*] %s: All good' % ((' '*9), check_name), ['*', check_name], [GREEN, EMPH])

                if options.ko_only:
                    Output.emphasized('\n\n%s%s' % (' '*5, '*'*22), ['%s%s' % (' '*5, '*'*22)])
                    Output.emphasized('         Failing checks    ', ['Failing checks'])
                    Output.emphasized('%s%s' % (' '*5, '*'*22), ['%s%s' % (' '*5, '*'*22)])
                    if len(failing_checks) == 0:
                        Output.emphasized('\n%sNone' % (' '*13))
                    else:
                        for check_item in failing_checks:
                            Output.emphasized('\n%sCheck: %s' % ((' '*13), check_item['check']), ['Check'])
                            Output.emphasized('%sSeverity: %s' % ((' '*13), check_item['severity']), ['Severity'])
                            Output.emphasized('%sStrike Zone: %s' % ((' '*13), check_item['strike_zone']), ['Strike Zone'])
                            Output.emphasized('%sDetail: %s' % ((' '*13), check_item['detail']), ['Detail'])

        elif options.output_type in ['file', 'support']:
            if self.__system_summary['Hardware profile'] != 'ossim-free':
                full_summary = dict(self.__system_summary, **self.__summary)
            else:
                full_summary = dict(self.__system_summary)
            full_summary['strike_zone'] = self.__in_strike_zone
            output_data = plain_data = json.dumps(full_summary, sort_keys=True, indent=4, separators=(',', ': ')) + '\n'

            # 'file' output mode will store the results in a plain file.
            # 'support' output mode will try to upload the encrypted and compressed file to a FTP server.
            if options.output_type == 'file':
                output_fd.write(output_data)
                output_fd.close()
                Output.emphasized('\n\nResults are stored in %s' % self.__output_file, [self.__output_file])

            elif options.output_type == 'support':
                output_data = plain_data

                if output_data is not None:
                    output_data = self.__compress__(output_data)

                if output_data is not None:
                    output_fd.write(self.__cipher__(output_data))

                output_fd.close()

                # If the FTP upload fails, let the file stay in the directory for the web to take care of it.
                if output_data is not None:
                    if self.__upload__(self.__output_file):
                        unlink(self.__output_file)
                    else:
                        # Notify that there was a non fatal error.
                        # Printing this on screen will notify the user.
                        # The permissions are changed for the web UI to read it.
                        uid = pwd.getpwnam("root").pw_uid
                        gid = grp.getgrnam("alienvault").gr_gid
                        os.chown(self.__output_file, uid, gid)
                        os.chmod(self.__output_file, 0640)
                        print '%s' % self.__output_file
                        self.__rc = default.exit_codes['ftp_upload_failed']

        elif options.output_type == 'ansible':
            if self.__system_summary['Hardware profile'] != 'ossim-free':
                full_summary = dict(self.__system_summary, **self.__summary)
            else:
                full_summary = dict(self.__system_summary)
            full_summary['strike_zone'] = self.__in_strike_zone
            output_fd.write(json.dumps(full_summary, sort_keys=True, indent=4, separators=(',', ': ')) + '\n')

        print ''
        sys.exit(self.__rc)

    # Parse doctor.cfg for some configuration values.
    def __parse_doctor_cfg__(self):
        parser = RawConfigParser()
        parser.read(default.doctor_cfg_file)
        self.__config = parser._sections['main']

    # Load plugin file
    def __load_plugin_file(self, filename):
        config_file = None
        try:
            config_file = PluginConfigParser()
            config_file.read(filename)
        except PluginConfigParserError as e:
            raise PluginError(e.msg, filename)
        except Exception as e:
            raise PluginError("Cannot parse plugin file: %s" % str(e), filename)
        return config_file

    # Run a plugin.
    def __run_plugin__(self, filename, verbose, raw):
        # Parse the plugin configuration file.
        # Check if file exists
        if not path.isfile(filename):
            msg = 'Plugin file does not exist: %s' % filename
            self.__generate_blocked_output(config_file=None,
                                           plugin=filename,
                                           plugin_data={},
                                           sections=[],
                                           error_msg=msg)
            return

        # Check for file extension.
        if not filename.endswith('.plg'):
            msg = 'File extension is not .plg'
            self.__generate_blocked_output(config_file=None,
                                           plugin=filename,
                                           plugin_data={},
                                           sections=[],
                                           error_msg=msg)
            return

        # Check for db connections and some other basic param config
        cfg_msg = ''
        for key in self.__successful_config.keys():
            if not self.__successful_config[key]['result']:
                cfg_msg = ';'.join([cfg_msg, self.__successful_config[key]['error']])
        if cfg_msg != '':
            self.__generate_blocked_output(config_file=None,
                                           plugin=filename,
                                           plugin_data={},
                                           sections=[],
                                           error_msg=cfg_msg)

        config_file = None
        try:
            config_file = self.__load_plugin_file(filename)
        except PluginError as e:
            self.__generate_blocked_output(config_file=None,
                                           plugin=e.plugin,
                                           plugin_data={},
                                           sections=[],
                                           error_msg=e.msg)

            return

        # Fill the Plugin Object
        if config_file:
            try:
                plugin = Plugin(filename,
                                config_file,
                                self.__alienvault_config,
                                self.__severity_list,
                                self.__appliance_type_list,
                                self.__ignore_dummy_platform,
                                verbose,
                                raw)

                if (plugin.get_checks_len() > 0) and (plugin.check_category(self.__category_list)):
                    result = plugin.run()
                    self.__in_strike_zone &= result.get('strike_zone', True)
                    self.__summary[plugin.get_name()] = result
                else:
                    del plugin

            except (PluginError, PluginConfigParserError, CheckError) as e:

                if verbose > 0:
                    Output.warning(e.msg)

                sections = []
                try:
                    sections = config_file.sections()
                except Exception:
                    pass

                self.__generate_blocked_output(config_file=config_file,
                                               plugin=e.plugin,
                                               plugin_data=e.kwargs,
                                               sections=sections,
                                               error_msg=e.msg)

            except KeyError, msg:
                Output.error('Unknown error running plugin "%s": %s' % (filename, str(msg)))

        else:
            msg = "There was an error parsing the plugin file %s" % filename
            self.__generate_blocked_output(config_file=None,
                                           plugin=filename,
                                           plugin_data={},
                                           sections=[],
                                           error_msg=msg)

    def __generate_blocked_output(self, config_file, plugin, plugin_data, sections, error_msg):
        # Parse the plugin configuration file.
        checks = {}
        if config_file:
            try:
                data = {'filename': '', 'command': ''}
                for section in sections:
                    if section != 'properties':
                        pass
                    else:
                        items = dict(config_file.items(section))
                        if 'type' in plugin_data.keys() and plugin_data['type'] == "file":
                            data['filename'] = items['filename']
                        elif plugin_data and plugin_data['type'] == "command":
                            data['command'] = items['command']
                        break

                for section in sections:
                    if section != 'properties':
                        items = dict(config_file.items(section))
                        aux_app_type = []
                        for x in items['appliance_type'].split(','):
                            aux_app_type += Wildcard.appliance_exec(x.strip())
                        if self.__alienvault_config['hw_profile'].lower() not in aux_app_type:
                            continue
                        if 'type' in plugin_data.keys() and plugin_data['type'] == "db":
                            try:
                                data['command'] = "echo '%s;' | ossim-db" % items['query']
                            except Exception:
                                pass
                        elif 'type' in plugin_data.keys() and plugin_data['type'] == "file":
                            try:
                                data['command'] = "cat %s" % data['filename']
                            except Exception:
                                pass

                        checks[section] = {'result': 'blocked',
                                           'detail': error_msg,
                                           'description': items['description'],
                                           'command': data['command'],
                                           'severity': items['severity']}

                # No checks for this plugin in the current appliance --> do not return plugin details
                if not checks:
                    return

            except Exception as e:
                checks = {}

        self.__summary[plugin] = {'result': 'blocked',
                                  'summary': error_msg,
                                  'checks': checks}

    # Compress some data.
    def __compress__(self, data):
        try:
            compressed = compress(data)
        except Exception, e:
            Output.warning('Output data cannot be compressed: %s' % str(e))
            return None

        Output.info('Output data successfully compressed')
        return compressed

    # Cipher some data.
    def __cipher__(self, data):
        if not 'public_key' in self.__config.keys():
            Output.warning('Output data cannot be encrypted: cannot find the cipher key file')
            return None

        # Create a random initialization vector and our AES cipher object.
        iv = '\0' * 16
        random_key = ''.join(chr(random.randint(0x20, 0x7E)) for i in range(16))
        aes_cipher = EVP.Cipher(alg='aes_128_cbc', key=random_key, iv=iv, op=1)

        try:
            if len(data) % 16 != 0:
                data += '\0' * (16 - (len(data) % 16))
            ciphered_log = aes_cipher.update(data)
            ciphered_log = ciphered_log + aes_cipher.final()
        except Exception, e:
            Output.warning('Output data cannot be encrypted: %s' % str(e))
            return None
        finally:
            del aes_cipher

        # Load our RSA key and cipher the pass.
        rsa_cipher = None
        try:
            rsa_cipher = RSA.load_pub_key(self.__config['public_key'])
            ciphered_pass = rsa_cipher.public_encrypt(random_key, RSA.sslv23_padding)
        except Exception, e:
            Output.warning('Output data cannot be encrypted: %s' % str(e))
            return None
        finally:
            del rsa_cipher

        ciphered_data = b64encode(ciphered_log) + '\n____key____\n' + b64encode(ciphered_pass)

        Output.info('Output data successfully encrypted')
        return ciphered_data

    # Upload some data to an FTP server.
    def __upload__(self, filename):
        params_needed = set(['ftp_user', 'ftp_password', 'ftp_host'])
        if not params_needed <= set(self.__config.keys()):
            Output.warning('Output data cannot be uploaded: missing FTP connection parameters')
            return False

        fd = open(filename, 'r')

        try:
            ftp_conn = FTP(self.__config['ftp_host'], self.__config['ftp_user'], self.__config['ftp_password'])
            ftp_conn.storbinary('STOR %s' % path.basename(filename), fd)
        except Exception, e:
            Output.warning('Output data cannot be uploaded: %s' % str(e))
            return False
        finally:
            fd.close()

        return True
