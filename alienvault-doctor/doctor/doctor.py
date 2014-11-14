#
#  License:
#
#  Copyright (c) 2003-2006 ossim.net
#  Copyright (c) 2007-2013 AlienVault
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

import sys, os, time, json, pwd, grp
import subprocess
import re
import platform
from optparse import OptionParser, OptionGroup
from ConfigParser import RawConfigParser
import random
from M2Crypto import EVP, RSA
from zlib import compress
from ftplib import FTP
from os import path, unlink
from base64 import b64encode

import default

from output import *
from plugin import Plugin, PluginError, PluginConfigParserError

'''
Class Doctor.
Main class.
'''
class Doctor:

  def __init__( self ):
    self.__options = {}
    self.__config = {}
    self.__plugin_list = []
    self.__category_list = []
    self.__ossim_config = {}
    self.__summary = []
    self.__output_file = ''
    self.__rc = 0

  # Start the actual process.
  def run (self):

    # Parse command line options.
    parser = OptionParser (description='Save the world, one Alien at a time', version=default.version_string)
    parser.add_option ("-v", "--verbose", dest="verbose", default=default.verbose, action="count", help="More meaningful warnings [default: %default]")
    parser.add_option ("-l", "--plugin-list", dest="plugin_list", default=default.plugin_list, help="A list of plugins you want to run, separated by commas [default: run all plugins]")
    parser.add_option ("-c", "--category_list", dest="category_list", default=default.category_list, help="A list of plugin categories you want to run [default: run all plugins]")
    parser.add_option ("-s", "--severity_list", dest="severity_list", default=default.severity_list, help="A list of check severities you want to run [default: run all checks]")
    parser.add_option ("-P", "--plugin-dir", dest="plugin_dir", default=default.plugin_dir, help="Directory where plugins are stored [default: %default]")

    output_group = OptionGroup (parser, 'Output options')
    output_group.add_option('-o', '--output-type', dest='output_type', type='choice', choices=default.valid_output_types, default=default.output_type, help='Output type [default: %default]')
    output_group.add_option('-p', '--output-path', dest='output_path', default=default.output_path, help='Output file path [default: %default]')
    output_group.add_option('-f', '--output-file-prefix', dest='output_file_prefix', default=default.output_file_prefix, help='Output file prefix [default: %default]')
    output_group.add_option('-r', '--output-raw', dest='output_raw', default=default.output_raw, action='store_true', help='Retrieve raw data [default: %default]')
    parser.add_option_group(output_group)

    (options, args) = parser.parse_args()

    # Disable normal output for 'ansible' and 'support' output options.
    if options.output_type in ['ansible', 'support']:
      Output.set_std_output (False)

    Output.emphasized ('\nAlienVault Doctor version %s (%s)\n' % (default.version, default.nickname), ['AlienVault Doctor'], [GREEN])

    # Parse Doctor configuration file options.
    if path.isfile (default.doctor_cfg_file):
      self.__parse_doctor_cfg__ ()
    else:
      Output.warning ('Doctor configuration file does not exist, trying to continue...')

    # Parse plugin configuration files.
    if not path.isdir (options.plugin_dir):
      Output.error ('"%s" is not a valid directory' % options.plugin_dir)
      sys.exit (default.error_codes['invalid_dir'])

    output_fd = None

    # Parse output options.
    if options.output_type in ['file', 'support']:
      mode = 'w+'

      # Support ticket ID has to be a 8 char long, all digit string.
      if options.output_type == 'support':
        if options.output_file_prefix == default.output_file_prefix or \
           len(options.output_file_prefix) != 8 or not options.output_file_prefix.isdigit():
          Output.error ('For "support" output, a valid ticket number has to be specified as the file prefix')
          sys.exit(default.error_codes['undef_support_prefix'])

      if not path.exists (options.output_path):
        os.mkdir (options.output_path)
        Output.info ('Output file directory "%s" created\n' % options.output_path)

      if path.isdir (options.output_path):
        try:
          self.__output_file = path.join (options.output_path, options.output_file_prefix + '-' + str(int(time.time())) + '.doctor')
          output_fd = open (self.__output_file, mode)
        except IOError as e:
          Output.warning ('Cannot open file "%s" for writing: %s' % (self.__output_file, e))
      else:
        Output.warning ('"%s" is not a valid directory, messages will be shown in stdout only' % options.output_path)

    elif options.output_type == 'ansible':
      output_fd = sys.stdout

    elif options.output_type == 'none':
      pass

    else:
      Output.warning ('"%s" is not a valid output type, messages will be shown in stdout only' % options.output_type)

    # Parse ossim_setup for profiles.
    self.__parse_ossim_setup__ ()

    # Show some system info.
    self.__show_platform_info__ ()

    # Run a list of plugins or categories of plugins
    self.__plugin_list = options.plugin_list.split(',')

    if self.__plugin_list == [] or 'all' in self.__plugin_list:
      self.__plugin_list = os.listdir(options.plugin_dir)

    # Filter by category.
    self.__category_list = options.category_list.split(',')

    # Filter checks by severity.
    self.__severity_list = options.severity_list.split(',')

    # Run! Run! Run!
    Output.emphasized ('\nHmmm, let the Doctor have a look at you%s' % ('...\n' if options.verbose > 0 else ''), ['Doctor'], [GREEN], False)

    for filename in self.__plugin_list:
      if filename.endswith ('.plg'):
        if options.verbose < 1:
          Progress.dots ()
        self.__run_plugin__ (options.plugin_dir + '/' + filename, options.verbose, options.output_raw)

    # Separator
    print ''

    # Show summary only for screen output.
    if options.output_type == default.output_type:
      Output.emphasized ('\nHooray! The Doctor has diagnosed you, check out the results...', ['Doctor'], [GREEN])

      for result in self.__summary:
        plugin_name = result['plugin']

        if (not 'checks' in result.keys()) and ('warning' in result.keys()):
          Output.emphasized ('\n     Plugin %s didn\'t run: %s' % (plugin_name, result['warning']), [plugin_name])
        else:
          checks = result['checks']

          Output.emphasized ('\n     Plugin: %s' % plugin_name, [plugin_name])
          for (check_name, check_result) in checks.items():
            if check_result['result'] == False:
              if check_result['severity'] == 'High':
                severity_color = RED
              elif check_result['severity'] == 'Medium':
                severity_color = YELLOW
              elif check_result['severity'] == 'Low':
                severity_color = BLUE
              else:
                severity_color = YELLOW

              Output.emphasized ('%s[*] %s: %s' % ((' '*10), check_name, check_result['warning']), ['*', check_name], [severity_color, EMPH])
              if check_result['advice'] != '':
                Output.emphasized ('%sWord of advice: %s' % ((' '*14), check_result['advice']), ['Word of advice'])
            else:
              Output.emphasized ('          [*] %s: All good' % check_name, ['*', check_name], [GREEN, EMPH])
    elif options.output_type in ['file', 'support']:
      output_data = plain_data = json.dumps(self.__summary, sort_keys=True, indent=4, separators=(',', ': ')) + '\n'

      # 'file' output mode will store the results in a plain file.
      # 'support' output mode will try to upload the encrypted and compressed file to a FTP server.
      if options.output_type == 'file':
        output_fd.write (output_data)
        output_fd.close ()
        Output.emphasized ('\n\nResults are stored in %s' % self.__output_file, [self.__output_file])

      elif options.output_type == 'support':
        output_data = plain_data
        if output_data != None:
          output_data = self.__cipher__ (output_data)

        if output_data != None:
          output_fd.write (self.__compress__(output_data))

        output_fd.close ()

        # If the FTP upload fails, let the file stay in the directory for the web to take care of it.
        if output_data != None:
          if self.__upload__ (self.__output_file):
            unlink (self.__output_file)
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
      output_fd.write (json.dumps(self.__summary, sort_keys=True, indent=4, separators=(',', ': ')) + '\n')

    print ''
    sys.exit (self.__rc)

  # Parse doctor.cfg for some configuration values.
  def __parse_doctor_cfg__ (self):
    parser = RawConfigParser ()
    parser.read (default.doctor_cfg_file)
    self.__config = parser._sections['main']

  # Parse ossim configuration file and return profiles & versions.
  def __parse_ossim_setup__ (self):
    setup_file = open(default.ossim_setup_file, 'r').read()

    # Find profiles and versions.
    line = setup_file[(setup_file.find('\nprofile=') + 9):]
    profiles = line[:line.find('\n')].split(',')
    self.__ossim_config['profiles'] = []
    self.__ossim_config['versions'] = []
    self.__ossim_config['@version'] = ''
    cmd = ['dpkg', '-l']

    if profiles is []:
      Output.error ('There are no defined profiles in ossim_setup.conf')
      sys.exit (default.error_codes['undef_ossim_profiles'])

    for profile in profiles:
      profile = profile.replace(' ', '')
      if profile == 'Server':
        cmd.append('ossim-server')
      elif profile == 'Sensor':
        cmd.append('ossim-agent')
      elif profile == 'Framework':
        cmd.append('ossim-framework')
      elif profile == 'Database':
        cmd.append('ossim-mysql')
      else:
        Output.error ('"%s" is not a valid profile' % profile)
        sys.exit (default.error_codes['invalid_ossim_profile'])

      proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
      output, err = proc.communicate()
      parsed_output = output.split('\n')[5].split()

      self.__ossim_config['profiles'].append(profile)
      version = re.findall("^\d+:(\d+(?:\.\d+){1,3})[\-\+]\S+", parsed_output[2])[0]
      self.__ossim_config['versions'].append(version)

      # Ossim version translate (this is an ugly hack, hope we'll fix this soon with a standarized ossim version file)
      if (self.__ossim_config['@version'] == '') or (version < self.__ossim_config['@version']):
        self.__ossim_config['@version'] = '\"' + version + '\"'

    # Parse file for translates.

    # Find MySQL properties.
    try:
      line = setup_file[(setup_file.index('\npass=') + 6):]
      self.__ossim_config['@pass'] = line[:line.index('\n')]
      line = setup_file[(setup_file.index('\nuser=') + 6):]
      self.__ossim_config['@user'] = line[:line.index('\n')]
      line = setup_file[(setup_file.index('\ndb_ip=') + 7):]
      self.__ossim_config['@db_ip'] = line[:line.index('\n')]
    except ValueError:
      Output.error ('Missing MySQL configuration field, check your ossim_setup.conf file')
      sys.exit (default.error_codes['missing_mysql_config'])

    # Sensor configuration.
    line = setup_file[(setup_file.index('\ndetectors=') + 11):]
    self.__ossim_config['@detectors'] = line[:line.index('\n')].split(',')

  # System info.
  def __show_platform_info__ (self):
    platform_info = [
      ('AlienVault version', self.__ossim_config['versions'][0]),
      ('Installed profiles', ','.join(self.__ossim_config['profiles'])),
      ('Operating system', platform.system ()),
      ('Hardware platform', platform.machine ()),
      ('Hostname', platform.node ()),
    ]

    for (field, value) in platform_info:
      rjustify = 60 - len (field)
      Output.emphasized ('     %s: %s' % (field, value.rjust(rjustify, ' ')), [value])

  # Run a plugin.
  def __run_plugin__ (self, filename, verbose, raw):
    try:
      plugin = Plugin (filename, self.__ossim_config, self.__severity_list, verbose, raw)
    except (PluginError, PluginConfigParserError) as e:
      if verbose > 0:
        print ''
        Output.warning (e.msg)
      else:
        self.__summary.append ({'plugin': e.plugin, 'warning': e.msg})
    except Exception as e:
      print ''
      Output.error ('Unknown error parsing plugin "%s": %s' % (filename, str(e)))
    else:
      if (plugin.get_enable()) and (plugin.get_checks_len() > 0) and (plugin.check_category (self.__category_list)):
        self.__summary.append (plugin.run ())
      else:
        del plugin

  # Compress some data.
  def __compress__ (self, data):
    try:
      compressed = compress (data)
    except Exception, e:
      Output.warning ('Output data cannot be compressed: %s' % str(e))
      return None

    Output.info ('Output data successfully compressed')
    return compressed

  # Cipher some data.
  def __cipher__ (self, data):
    if not 'public_key' in self.__config.keys():
      Output.warning ('Output data cannot be encrypted: cannot find the cipher key file')
      return None

    # Create a random initialization vector and our AES cipher object.
    iv = '\0' * 16
    random_key = ''.join(chr(random.randint(0x20, 0x7E)) for i in range(16))
    aes_cipher = EVP.Cipher(alg='aes_128_cbc', key=random_key, iv=iv, op=1)

    try:
      if len(data) % 16 != 0:
        data += '\0' * (16 - (len(data) % 16))
      ciphered_log = aes_cipher.update (data)
      ciphered_log = ciphered_log + aes_cipher.final()
    except Exception, e:
      Output.warning ('Output data cannot be encrypted: %s' % str(e))
      return None
    finally:
      del aes_cipher

    # Load our RSA key and cipher the pass.
    try:
      rsa_cipher = RSA.load_pub_key (self.__config['public_key'])
      ciphered_pass = rsa_cipher.public_encrypt(random_key, RSA.sslv23_padding)
    except Exception, e:
      Output.warning ('Output data cannot be encrypted: %s' % str(e))
      return None
    finally:
      del rsa_cipher

    ciphered_data = b64encode(ciphered_log) + '\n____key____\n' + b64encode(ciphered_pass)

    Output.info ('Output data successfully encrypted')
    return ciphered_data

   # Upload some data to an FTP server.
  def __upload__ (self, filename):
    params_needed = set(['ftp_user', 'ftp_password', 'ftp_host'])
    if not params_needed <= set(self.__config.keys()):
      Output.warning ('Output data cannot be uploaded: missing FTP connection parameters')
      return False

    fd = open(filename, 'r')

    try:
      ftp_conn = FTP (self.__config['ftp_host'], self.__config['ftp_user'], self.__config['ftp_password'])
      ftp_conn.storbinary ('STOR %s' % path.basename(filename), fd)
    except Exception, e:
      Output.warning ('Output data cannot be uploaded: %s' % str(e))
      return False
    finally:
      fd.close()

    return True
