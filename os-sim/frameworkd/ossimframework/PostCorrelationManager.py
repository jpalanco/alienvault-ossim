#!/usr/bin/python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#
import socket
import threading
import time
import os
import sys
import Util
import signal
import re, string, struct
from datetime import datetime, timedelta
from pytz import timezone, all_timezones
import pytz
import calendar
import codecs
from base64 import b64encode
from time import time,sleep,strftime,localtime,mktime,strptime
import pickle
#
#    LOCAL IMPORTS
#
from DBConstantNames import *
from OssimDB import OssimDB
from OssimConf import OssimConf,OssimMiniConf
from ConfigParser import ConfigParser

from Logger import Logger
logger = Logger.logger
_CONF = OssimConf()
_DB = OssimDB(_CONF[VAR_DB_HOST],
            _CONF[VAR_DB_SCHEMA],
            _CONF[VAR_DB_USER],
            _CONF[VAR_DB_PASSWORD])
_DB.connect()
ERROR_CONNECTING_TO_SERVER = "Error connection to server (%s:%s) -> "

class PostCorrelationEvent():
    POSTCORRELATION_EVENT_ID = 20505
    EVENT_BASE64 = [
        'username',
        'password',
        'filename',
        'userdata1',
        'userdata2',
        'userdata3',
        'userdata4',
        'userdata5',
        'userdata6',
        'userdata7',
        'userdata8',
        'userdata9', 
        'log',
        'domain']
    EVENT_TYPE='event'
    EVENT_ATTRS = [
        "type",
        "date",
        "sensor",
        "interface",
        "plugin_id",
        "plugin_sid",
        "priority",
        "protocol",
        "src_ip",
        "src_port",
        "dst_ip",
        "dst_port",
        "username",
        "password",
        "filename",
        "userdata1",
        "userdata2",
        "userdata3",
        "userdata4",
        "userdata5",
        "userdata6",
        "userdata7",
        "userdata8",
        "userdata9",
        "occurrences",
        "log",
        "data",
        "snort_sid", # snort specific
        "snort_cid", # snort specific
        "fdate",
        "tzone",
        "cctx_id",
        "sensor_id"
    ]
    def __init__(self):
        self.event = {}
        self.event["event_type"] = self.EVENT_TYPE
        self.normalized = False

    def __setitem__(self, key, value):

        if key in self.EVENT_ATTRS:
            if key in self.EVENT_BASE64:
                self.event[key] = b64encode (value)
            else:
                self.event[key] = value#self.sanitize_value(value)
            if key == "date" and not self.normalized:
                # Fill with a default date.
                date_epoch = int(time())
                # Try first for string dates.
                try:
                    date_epoch = int(mktime(strptime(value, "%Y-%m-%d %H:%M:%S")))
                    self.event["fdate"] = value
                    self.event["date"] = date_epoch
                    self.normalized = True
                except (ValueError):
                    logger.warning("There was an error parsing a string date (%s)" % (value))
        elif key != 'event_type':
            logger.warning("Bad event attribute: %s" % (key))

    def __getitem__(self, key):
        return self.event.get(key, None)


    def __repr__(self):
        """Event representation."""
        event = self.EVENT_TYPE

        for attr in self.EVENT_ATTRS:
            if self[attr]:
                event += ' %s="%s"' % (attr, self[attr])

        return event + "\n"


    def dict(self):
        # return the internal hash
        return self.event


    def sanitize_value(self, string):
        return str(string).strip().replace("\"", "\\\"").replace("'", "")


class ConfigCritical(Exception):
    """Use this class only for non-recoverable errors.

     The exception is handled in the main loop
      - logging the error with [CRITICAL] severity
      - stopping agent closing all descriptors
    """

    def __init__(self, msg=''):
        self.msg = msg


    def __str__(self):
        return repr(self.msg)


class ConfigManager(ConfigParser):

    DEFAULT_CONFIG_FILE = "/etc/ossim/framework/post_correlation.cfg"

    # fill this table with the *mandatory* entries
    # that need to be present in config.cfg file
    _NEEDED_CONFIG_ENTRIES = {
    }
    _EXIT_IF_MALFORMED_CONFIG = True

    # same as ConfigParser.read() but also check
    # if configuration files exists
    def read(self, filenames, utf8,check_neededEntries = True):
        for filename in filenames:
            if not os.path.isfile(filename):
                ConfigCritical("Configuration file (%s) does not exist!" %  (filename))
        if not utf8:
            ConfigParser.read(self, filenames)
        else:
            fp = codecs.open(filenames, 'r', encoding='utf-8')
            fp.readline()#discard first line
            self.readfp(fp)
        if check_neededEntries:
            self.check_needed_config_entries()


    # check for needed entries in .cfg files
    # this function uses the variable _NEEDED_CONFIG_ENTRIES
    def check_needed_config_entries(self):
        for section, values in self._NEEDED_CONFIG_ENTRIES.iteritems():
            if not self.has_section(section):
                logger.critical (
                    "Needed section [%s] not found!" % (section))
                if self._EXIT_IF_MALFORMED_CONFIG:
                    sys.exit()
            for value in values:
                if not self.has_option(section, value):
                    logger.critical (
                        "Needed option [%s->%s] not found!" % (section, value))
                    if self._EXIT_IF_MALFORMED_CONFIG:
                        sys.exit()


    # avoid confusions in configuration values
    def _strip_value(self, value):
        from string import strip
        return strip(strip(value, '"'), "'")


    # same as ConfigParser.items() but returns a hash instead of a list
    def hitems(self, section):
        hash = {}
        for item in self.items(section):
            hash[item[0]] = self._strip_value(item[1])
        return hash


    # same as ConfigParser.get() but stripping values with " and '
    def get(self, section, option):
        try:
            value = ConfigParser.get(self, section, option)
            value = self._strip_value(value)

        except:
            value = ""

        return value


    def getboolean(self, section, option):
        try:
            value = ConfigParser.getboolean(self, section, option)
        except ValueError: # not a boolean
            logger.warning("Value %s->%s is not a boolean" % (section, option))
            return False
        return value


    # print a representation of a config object,
    # very useful for debug purposes
    def __repr__(self):
        conf_str = '<postcorrealtion-config>\n'
        for section in sorted(self.sections()):
            conf_str += '  <section name="%s">\n' % (section)
            for i in self.items(section):
                conf_str += '    <item name="%s" value="%s" />\n' % (i[0], i[1])
            conf_str += '  </section>\n'
        conf_str += '</postcorrealtion-config>'
        return conf_str


class PostCorrelationConfig(ConfigManager):

    # fill this table with the *mandatory* entries
    # that need to be present in a plugin.cfg file
    _NEEDED_CONFIG_ENTRIES = {
                              'postcorrelation-defaults':['tzone'],
    }
    _EXIT_IF_MALFORMED_CONFIG = True
    TRANSLATION_SECTION = 'translation'
    TRANSLATION_FUNCTION = 'translate'
    TRANSLATION_DEFAULT = '_DEFAULT_'
    SECTIONS_NOT_RULES = ["config", "info", TRANSLATION_SECTION,'postcorrelation-defaults']

    # constants for _replace_*_assess functions
    _MAP_REPLACE_TRANSLATIONS = 4
    _MAP_REPLACE_USER_FUNCTIONS = 8

    def rules(self):
        rules = {}
        for section in sorted(self.sections()):
            if section.lower() not in PostCorrelationConfig.SECTIONS_NOT_RULES :
                rules[section] = self.hitems(section)

        return rules


    def _replace_array_variables(self, value, groups):
        unicode = self.__UTF8_ENCODED

        for i in range(2):
            if unicode:
                search = re.findall("\{\$[^\}\{]+\}", value)
            else:
                search = re.findall("\{\$[^\}\{]+\}", value, re.UNICODE)
            if search != []:
                for string in search:
                    var = string[2:-1]
                    value = value.replace(string, str(groups[int(var)]))

        return value


    def get_replace_array_value(self, value, groups):
        # 1) replace variables
        value = self._replace_array_variables(value, groups)
        # 2) replace user functions
        value = self._replace_user_array_functions(value, groups)

        return value


    def _replace_user_array_functions(self, value, groups):
        if value is None:
            return None
        #logger.info("config.repace_user_array_functions --value:%s", value)
        unicode = self.__UTF8_ENCODED
        if unicode:
            search = re.findall("(\{(\w+)\((\$[^\)]+)\)\})", value, re.UNICODE)
        else:
            search = re.findall("(\{(\w+)\((\$[^\)]+)\)\})", value)
        if search != []:
            for string in search:
                (string_matched, func, variables) = string
                vars = split_variables(variables)

                # check that all variables have a replacement
                for var in vars:
                    #logger.warning(var)
                    #if not groups.has_key(var):
                    #    logger.warning("Can not replace '%s'" % (value))
                    #    return value
                    if len(groups) - 1 < int(var):
                        return value

                # call function 'func' with arg groups[var]
                # functions are defined in ParserUtil.py file
                if func != PostCorrelationConfig.TRANSLATION_FUNCTION and \
                   hasattr(ParserUtil, func):
                    # 'f' is the function to be called
                    f = getattr(ParserUtil, func)

                    # 'vars' are the list of arguments of the function
                    # 'args' are a custom representation of the list
                    #        to be used as f argument [ f(args) ]
                    args = ""
                    #for i in (range(len(vars))):
                        #args += "groups[vars[%s]]," % (str(i))
                        #args += "groups[vars[%s]]," % (str(i))
                        #logger.info(args)
                    for v in vars:
                        #logger.info(groups[int(v)])
                        args += "str(groups[%s])," % (str(v))
                        #exec replacement
                    try:
                        #logger.info("value = value.replace(string_matched," + "str(f(" + args + ")))")
                        exec "value = value.replace(string_matched," + \
                            "str(f(" + args + ")))"
                        #logger.info(value)
                    except TypeError, e:
                        logger.error(e)

                else:
                    #pdb.set_trace()
                    #logger.warning("Function '%s' is not implemented" % (func))
                    #value = value.replace(string_matched, str(groups[var]))
                    logger.debug("Tranlation fucntion...")

                    for v in vars:
                        #check if the positions exists.
                        if int(var) > (len(groups) - 1):
                            logger.debug("Error var:%d, is greatter than groups size. (%d)" % (int(var), len(groups)))
                        else:
                            if self.has_section(PostCorrelationConfig.TRANSLATION_SECTION):
                                if self.has_option(PostCorrelationConfig.TRANSLATION_SECTION, var):
                                    value = self.get(PostCorrelationConfig.TRANSLATION_SECTION, var)
        return value


    def replace_config(self, conf):
        '''
         Look for \_CFG(section,option) values in config parameters
             and replace this with value found in global config file 
        '''
        unicode = self.__UTF8_ENCODED
        for section in sorted(self.sections()):
            for option in self.options(section):
                regexp = self.get(section, option)
                if not unicode:
                    search = re.findall("(\\\\_CFG\(([\w-]+),([\w-]+)\))", regexp)
                else:
                    search = re.findall("(\\\\_CFG\(([\w-]+),([\w-]+)\))", regexp, re.UNICODE)

                if search != []:
                    for string in search:
                        (all, arg1, arg2) = string
                        if conf.has_option(arg1, arg2):
                            value = conf.get(arg1, arg2)
                            regexp = regexp.replace(all, value)
                            self.set(section, option, regexp)


    def replace_aliases(self, aliases):
        unicode = self.__UTF8_ENCODED
        # iter over all rules
        for rule in self.rules().iterkeys():

            regexp = self.get(rule, 'regexp')

            # look for \X values in regexp entry
            #
            # To match a literal backslash, one has to write '\\\\'
            # as the RE string, because the regular expression must be
            # "\\", and each backslash must be expressed as "\\" inside
            # a regular Python string literal
            #
            if not unicode:
                search = re.findall("\\\\\w\w+", regexp)
            else:
                search = re.findall("\\\\\w\w+", regexp, re.UNICODE)

            if search != []:
                for string in search:

                    # replace \X with aliases' X entry
                    repl = string[1:]
                    if aliases.has_option("regexp", repl):
                        value = aliases.get("regexp", repl)
                        regexp = regexp.replace(string, value)
                        self.set(rule, "regexp", regexp)


    # variables are specified as {$v}
    # you can use two-anidated variables: {${$v}}
    # this function is called from get_replace_value()
    def _replace_variables(self, value, groups, rounds=2):
        unicode = self.__UTF8_ENCODED
        for i in range(rounds):
            if not unicode:
                search = re.findall("\{\$[^\}\{]+\}", value)
            else:
                search = re.findall("\{\$[^\}\{]+\}", value, re.UNICODE)
                
            if search != []:
                for string in search:
                    var = string[2:-1]
                    if groups.has_key(var):
                        value = value.replace(string, str(groups[var]))

        return value


    # determine if replace variables achieves anything and if not we can
    # skip it later on in get_replace_value()
    def _replace_variables_assess(self, value):
        unicode = self.__UTF8_ENCODED
        ret = 0
        for i in range(2):
            if unicode:
                search = re.findall("\{\$[^\}\{]+\}", value, re.UNICODE)
            else:
                search = re.findall("\{\$[^\}\{]+\}", value)
            if search != []:
                ret = i
        return ret


    # special function translate() for translations
    # translations are defined in the own plugin with a [translation] entry
    # this function is called from get_replace_value()
    def _replace_translations(self, value, groups):
        unicode = self.__UTF8_ENCODED
        regexp = "(\{(" + PostCorrelationConfig.TRANSLATION_FUNCTION + ")\(\$([^\)]+)\)\})"
        if unicode:
            search = re.findall(regexp, value, re.UNICODE)
        else:
            search = re.findall(regexp, value)
        if search != []:
            for string in search:
                (string_matched, func, var) = string
                if groups.has_key(var):
                    if self.has_section(PostCorrelationConfig.TRANSLATION_SECTION):
                        if self.has_option(PostCorrelationConfig.TRANSLATION_SECTION,
                                           groups[var]):
                            value = self.get(PostCorrelationConfig.TRANSLATION_SECTION,
                                             groups[var])
                        else:
                            logger.warning("Can not translate '%s' value" % \
                                (groups[var]))

                            # It's not possible to translate the value,
                            # revert to _DEFAULT_ if the entry is present
                            if self.has_option(PostCorrelationConfig.TRANSLATION_SECTION,
                                               PostCorrelationConfig.TRANSLATION_DEFAULT):
                                value = self.get(PostCorrelationConfig.TRANSLATION_SECTION,
                                                 PostCorrelationConfig.TRANSLATION_DEFAULT)
                            else:
                                value = groups[var]
                    else:
                        logger.warning("There is no translation section")
                        value = groups[var]

        return value


    # determine if replace translations achieves anything and if not we can
    # skip it later on in get_replace_value()
    def _replace_translations_assess(self, value):
        unicode = self.__UTF8_ENCODED
        regexp = "(\{(" + PostCorrelationConfig.TRANSLATION_FUNCTION + ")\(\$([^\)]+)\)\})"
        if unicode:
            search = re.findall(regexp, value, re.UNICODE)
        else:
            search = re.findall(regexp, value)
        if search != []:
            return self._MAP_REPLACE_TRANSLATIONS

        return 0

    # functions are specified as {f($v)}
    # this function is called from get_replace_value()
    def _replace_user_functions(self, value, groups):
        unicode = self.__UTF8_ENCODED
        if unicode:
            search = re.findall("(\{(\w+)\((\$[^\)]+)\)\})", value, re.UNICODE)
        else:
            search = re.findall("(\{(\w+)\((\$[^\)]+)\)\})", value)
        if search != []:
            for string in search:
                (string_matched, func, variables) = string
                vars = split_variables(variables)

                # check that all variables have a replacement
                for var in vars:

                    if not groups.has_key(var):
                        logger.warning("Can not replace '%s'" % (value))
                        return value

                # call function 'func' with arg groups[var]
                # functions are defined in ParserUtil.py file
                if func != PostCorrelationConfig.TRANSLATION_FUNCTION and \
                   hasattr(ParserUtil, func):

                    # 'f' is the function to be called
                    f = getattr(ParserUtil, func)

                    # 'vars' are the list of arguments of the function
                    # 'args' are a custom representation of the list
                    #        to be used as f argument [ f(args) ]
                    args = ""
                    for i in (range(len(vars))):
                        args += "groups[vars[%s]]," % (str(i))

                    # exec replacement
                    try:
                        cmd = "value = value.replace(string_matched," + \
                              "str(f(" + args + ")))"
                        exec cmd
                    except TypeError, e:
                        logger.error(e)

                else:
                    logger.warning(
                        "Function '%s' is not implemented" % (func))

                    value = value.replace(string_matched, \
                                          str(groups[var]))

        return value

    # determine if replace translations achieves anything and if not we can
    # skip it later on in get_replace_value()
    def _replace_user_functions_assess(self, value):
        unicode = self.__UTF8_ENCODED
        if unicode:
            search = re.findall("(\{(\w+)\((\$[^\)]+)\)\})", value, re.UNICODE)
        else:
            search = re.findall("(\{(\w+)\((\$[^\)]+)\)\})", value)
        if search != []:
            return self._MAP_REPLACE_USER_FUNCTIONS

        return 0


    def replace_value_assess(self, value):
        ret = self._replace_variables_assess(value)
        ret |= self._replace_translations_assess(value)
        ret |= self._replace_user_functions_assess(value)

        return ret


    # replace config values matching {$X} with self.groups["X"]
    # and {f($X)} with f(self.groups["X"])
    def get_replace_value(self, value, groups, replace=15):
        # do we need to replace anything?
        if replace > 0:
            # replace variables
            if replace & 3:
                value = self._replace_variables(value, groups, (replace & 3))
            # replace translations
            if replace & 4:
                value = self._replace_translations(value, groups)
            # replace user functions
            if replace & 8:
                value = self._replace_user_functions(value, groups)

        return value
    def setUnicode(self,value):
        self.__UTF8_ENCODED = value
    def isUnicode(self):
        return self.__UTF8_ENCODED


class PostCorrelationManager(threading.Thread):
    MSG_CONNECT = 'connect id="%s" type="frameworkd" version="3.0.0"\n'
    UPDATE_FILE = '/etc/ossim/framework/postcorrelation_ruleupdated.fkm'
    def __init__(self):
        threading.Thread.__init__(self)
        server_config = OssimMiniConf(config_file='/etc/ossim/ossim_setup.conf')
        self.__server_ip = server_config['server_ip']
        self.__server_port = server_config['server_port']
        self.__isAlive = False
        self.__connection = None
        self.__configuration = None
        self.__keepWorking = True
        self.__sequence = 0
        self.__timezone = None
        self.__pcmtimezone = None
        self.__eventTZ = None
        self.__correlationContextID = None
        self.__sensorID = None
        self.__rules = {}
        self.__unicode = False
        self.patternISO_date = re.compile('(?P<year>\d+)[\s-](?P<month>\d+)[\s-](?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)')
        self.patternUTClocalized = re.compile('(?P<year>\d+)[\s-](?P<month>\d+)[\s-](?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)(?P<tzone_symbol>[-|+])(?P<tzone_hour>\d{2}):(?P<tzone_min>\d{2})')
        self.__loadConfiguration()
        self.__setTZData()
        self.__rule_update = {}


    def __getLocalIP(self):
        if self.__configuration.has_section("postcorrelation-defaults"):
            if self.__configuration.has_option("postcorrelation-defaults","sensor"):
                mylocalip = self.__configuration.get("postcorrelation-defaults", "sensor")
                return mylocalip
        
        hostname, aliaslist, ipaddrlist = socket.gethostbyname_ex(socket.gethostname())
        for ip in ipaddrlist:
            if not ip.startswith("127"):
                return ip
        #In this case we try to parse the output of ip a
        lines = commands.getoutput("ip a | grep inet | grep -v inet6 | awk '{print $2}'| grep -v \"127.0.0.1\" | awk -F '/' '{print $1}'").split("\n")
        if len(lines) > 0:
            logger.info("Using sensor ip: %s" % lines[0])
            return lines[0]


    def __setTZData(self):
        self.__pcmtimezone = self.__configuration.get("postcorrelation-defaults", "tzone")
        self.__checkTimeZone()


    def __checkTimeZone(self):
        if self.__pcmtimezone in all_timezones:
            used_tzone = self.__pcmtimezone
            logger.debug("Using custom plugin tzone data: %s" % used_tzone)
        else:
            try:
                #read local timezone information. 
                f = open('/etc/timezone', 'r')
                used_tzone = f.readline().rstrip()
                f.close()
                if used_tzone not in all_timezones:
                    logger.warning("Warning, we can't read valid timezone data.Using GMT")
                    used_tzone = 'GMT'
            except Exception, e:
                used_tzone = 'GMT'
                logger.warning("Warning, we can't read valid timezone data.Using GMT")
            logger.warning("Warning: Invalid plugin tzone and invalid agent tzone, using system tzone: %s" % used_tzone)
        self.__eventTZ = used_tzone


    def __loadConfiguration(self):
        self.__loadRuleUpdateFile()
        self.__configuration = PostCorrelationConfig()
        conffile = self.__configuration.DEFAULT_CONFIG_FILE
        custom_config = "%s.local" % self.__configuration.DEFAULT_CONFIG_FILE
        #Check if unicode support is needed.
        ff = open ( conffile, 'r' )
        bom = ff.read( 4 )
        withunicode = False

        if bom.startswith( codecs.BOM_UTF8 ):
            logger.info( "Plugin configuration file: %s is encoded as utf-8, all regular expressions will be compiled as unicode" % path )
            withunicode = True
        ff.close()
        self.__unicode = withunicode
        self.__configuration.setUnicode(self.__unicode)
        self.__configuration.read( [conffile], False )
        
        if "cctx_id" in self.__configuration.hitems("postcorrelation-defaults"):
            self.__correlationContextID = self.__configuration.get("postcorrelation-defaults", "cctx_id")
        if "sensor_id" in self.__configuration.hitems("postcorrelation-defaults"):
            self.__sensorID = self.__configuration.get("postcorrelation-defaults", "sensor_id")
        self.__rules = self.__configuration.rules()
        for key,rule in self.__rules.iteritems():
            rule['valid'] = True
            if self.__rule_update.has_key(key):
                rule['last_run'] = self.__rule_update[key]
            else:
                rule['last_run'] = time() 
            if not rule.has_key('timeout'):
                rule['valid'] = False
            if not rule.has_key('sql_query'):
                rule['valid'] = False
            if not rule.has_key('id'):
                rule['valid'] = False
            if not rule.has_key('reliability'):
                rule['valid'] = False
            if not rule.has_key('priority'):
                rule['valid'] = False
            if not rule.has_key('enable'):
                rule['enable'] = True
            elif rule['enable'].lower() in ['true','1','yes']:
                 rule['enable'] = True
            else:
                rule['enable'] = False
        self.__updateRuleUpdateFile()

    def __updateRuleUpdateFile(self):
        try:
            post_correlationfile = open(PostCorrelationManager.UPDATE_FILE, "wb")
            pickle.dump(self.__rule_update, post_correlationfile)
            post_correlationfile.close()
        except Exception,e:
            logger.error("Error dumping postcorrelation update_file...:%s" % str(e))

    def __loadRuleUpdateFile(self):
        if os.path.isfile(PostCorrelationManager.UPDATE_FILE):
            try:
                post_correlationfile = open(PostCorrelationManager.UPDATE_FILE)
                self.__rule_update = pickle.load(post_correlationfile)
                post_correlationfile.close()
            except Exception,e:
                logger.error("Error loading postcorrelation update_file...:%s" % str(e))
                self.__rule_update = {}
        else:
            self.__rule_update = {}

    def __connect_to_server(self):
        if self.__isAlive:
            return
        data = ""
        self.__connection = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        try:
            self.__connection.connect((self.__server_ip, int(self.__server_port)))
            self.__connection.send(self.MSG_CONNECT % (self.__sequence))
            logger.debug("Waiting for server..")
            data = self.__connection.recv(1024)
        except socket.error, e:
            logger.error(ERROR_CONNECTING_TO_SERVER \
                % (self.__server_ip, str(self.__server_port)) + ": " + str(e))
            self.__connection = None
            self.__isAlive = False
        except Exception, e:
            logger.error("Error connection. %s" % str(e))
        else:
            if data == 'ok id="' + str(self.__sequence) + '"\n':
                logger.info("Connected to server %s:%s!" % (self.__server_ip, self.__server_port))
                self.__isAlive = True
            else:
                logger.error("Bad response from server (seq_exp:%s): %s " % (self.__sequence,str(data)))
                self.__connection = None
                self.__isAlive = False
        return self.__connection


    def __connect(self, attempts=3, waittime=10.0):
        '''
        connect to server:
            - attempts == 0 means that agent try to connect forever
            - waittime = seconds between attempts
        '''
        count = 1
        if self.__connection is None:
            logger.info("Connecting to server (%s, %s).." % (self.__server_ip, self.__server_port))
            while not self.__isAlive:
                self.__connect_to_server()
                if self.__connection is not None:
                    self.__sendCmdSids()
                    break
                else:
                    logger.info("Can't connect to server, retrying in %d seconds" % (waittime))
                    sleep(waittime)
                if attempts != 0 and count == attempts:
                    break
                count += 1
        else:
            logger.info("Reusing server connection (%s, %s).." % (self.__server_ip, self.__server_port))
        return self.__connection

    def __sendCmdSids(self):
        cmd_sids = 'reload-post-correlation-sids sids="%s"\n'
        sid_list = ''
        for key,rule in self.__rules.iteritems():
            sid_list += "%s:%s:%s:%s;" %  (rule['id'],rule['log'],rule['priority'],rule['reliability'])
        cmd_sids = cmd_sids % sid_list[:-1]
        self.sendEvent(cmd_sids) 
    def __runQuery(self,query):
        data = _DB.exec_query(query)
        return data


    def stop(self):
        self.__keepWorking = False
        self.close()


    def __generateEvent(self,data,rule):
        event = PostCorrelationEvent()
        groups = {}
        replace_assessment = {}
        for key, value in rule.iteritems():
            if key != "regexp":
                replace_assessment[key] = self.__configuration.replace_value_assess(str(value))
        event['type'] = 'detector' #PostCorrelationEvent.EVENT_TYPE
        event['plugin_id'] = PostCorrelationEvent.POSTCORRELATION_EVENT_ID
        for key, group in data.iteritems():
            if group is None:
                group = '' # convert to '' better than 'None'
                value = ''
            if self.__unicode:
                value = str(group.encode('utf-8'))
            else:
                value = str(group)                     
            groups.update({str(key): value})
        for key,value in rule.iteritems():
            if key in event.EVENT_ATTRS:
                if self.__unicode:
                    event[key] = self.__configuration.get_replace_value(value, groups, replace_assessment[key])
                else:
                    event[key] = self.__configuration.get_replace_value(value, groups, replace_assessment[key])
        event['plugin_sid'] = rule['id']
        event['sensor'] = self.__getLocalIP()
        self.__event_defaults(event)
        return event


    def __event_defaults(self, event):
        ipv4_reg = "\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}"
        # get default values from config
        if self.__correlationContextID is not None:
            event['cctx_id'] = self.__correlationContextID
        if self.__sensorID is not None:
            event['sensor_id'] = self.__sensorID
            
        if self.__configuration.has_section("postcorrelation-defaults"):

        # 1) date
            default_date_format = self.__configuration.get("postcorrelation-defaults", "date_format")
            if event["date"] is None and default_date_format and \
               'date' in event.EVENT_ATTRS:
                event["date"] = strftime(default_date_format,
                                              localtime(time()))

        # 2) sensor
            default_sensor = self.__configuration.get("postcorrelation-defaults", "sensor")
            if event["sensor"] is None  and default_sensor and \
               'sensor' in event.EVENT_ATTRS and not self.__override_sensor:
                event["sensor"] = default_sensor

        # 3) interface
            default_iface = self.__configuration.get("postcorrelation-defaults", "interface")
            if event["interface"] is None and default_iface and \
               'interface' in event.EVENT_ATTRS:
                event["interface"] = default_iface

        # 4) source ip
            if event["src_ip"] is None and 'src_ip' in event.EVENT_ATTRS:
                event["src_ip"] = event["sensor"]

        # 5) Time zone 
            #default_tzone = self._conf.get("postcorrelation-defaults", "tzone")
            if 'tzone' in event.EVENT_ATTRS:
                self.normalizeToUTCDate(event, self.__eventTZ)
        # 6) sensor,source ip and dest != localhost
            if event["sensor"] in ('127.0.0.1', '127.0.1.1') and not self.__override_sensor:
                event["sensor"] = default_sensor

            if event["dst_ip"] in ('127.0.0.1', '127.0.1.1') and 'dst_ip' in event.EVENT_ATTRS: 
                event["dst_ip"] = default_sensor

            if event["src_ip"] in ('127.0.0.1', '127.0.1.1') and 'src_ip' in event.EVENT_ATTRS:
                event["src_ip"] = default_sensor

            #Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['src_ip'] is not None and 'src_ip' in event.EVENT_ATTRS:
                if not re.match(ipv4_reg, event['src_ip']):
                    data = event['src_ip']
                    event['src_ip'] = '0.0.0.0'
                    logger.warning("Event's field src_ip (%s) is not a valid IP.v4 address, set it to default ip 0.0.0.0 and real data on userdata8" % data)
                    event['userdata8'] = data
            elif 'src_ip' in event.EVENT_ATTRS:
                event['src_ip'] = '0.0.0.0'
            #Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['dst_ip'] is not None and 'dst_ip' in event.EVENT_ATTRS:
                if not re.match(ipv4_reg, event['dst_ip']):
                    data = event['dst_ip']
                    logger.warning("Event's field dst_ip (%s) is not a valid IP.v4 address, set it to default ip 0.0.0.0 and real data on userdata9" % data)
                    event['dst_ip'] = '0.0.0.0'
                    event['userdata9'] = data
            elif 'src_ip' in event.EVENT_ATTRS:
                event['dst_ip'] = '0.0.0.0'
            #Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['sensor'] is not None:
                if not re.match(ipv4_reg, event['sensor']):
                    data = event['sensor']
                    logger.warning("Event's field sensor (%s) is not a valid IP.v4 address, set it to default local and real data on userdata7" % data)
                    event['sensor'] = self.__getLocalIP()
                    event['userdata7'] = data
            else:
                event['sensor'] = self.__getLocalIP()

        # the type of this event should always be 'detector'
        if event["type"] is None and 'type' in event.EVENT_ATTRS:
            event["type"] = 'detector'
        return event


    def normalizeToUTCDate(self,event, used_tzone):
        if event["fdate"] == "" or event["fdate"] is None:
            logger.debug("Warning: fdate key doesn't exist in event object!")
            return
        plugin_date_str = event["fdate"]
        #2011-02-01 17:00:16
        matchgroup1 = self.patternISO_date.match(event["fdate"])
        plugin_dt = datetime(year=int(matchgroup1.group("year")), month=int(matchgroup1.group("month")), day=int(matchgroup1.group("day")), hour=int(matchgroup1.group("hour")), minute=int(matchgroup1.group("minute")), second=int(matchgroup1.group("second")))
        logger.debug("Plugin localtime date: %s and used time zone: %s", plugin_dt, used_tzone)
    
        try:
            plugin_tz = timezone(used_tzone)
        except UnknownTimeZoneError, e:
            logger.info("Error: Unknow tzone, %s may be not valid" % used_tzone)
            plugin_tz = timezone('GMT')
    
        logger.debug("Plugin tzone: %s" % plugin_tz.zone)
        plugin_localized_date = plugin_tz.localize(plugin_dt)
        logger.debug("Plugin localized time: %s" % plugin_localized_date)
        matchgroup2 = self.patternUTClocalized.match(str(plugin_localized_date))
        tzone_symbol = matchgroup2.group("tzone_symbol")
        tzone_hour = matchgroup2.group("tzone_hour")
        tzone_min = matchgroup2.group("tzone_min")
        tzone_float = (float(tzone_hour) * 60 + float(tzone_min)) / 60
    
        if tzone_symbol == "-":
            tzone_float = -1 * tzone_float
        logger.debug("Calculated float timezone: %s" % tzone_float)
        utc_tz = pytz.utc
        plugin_utc_dt = plugin_localized_date.astimezone(utc_tz)
        logger.debug("Plugin UTC Date: %s", plugin_utc_dt)
        dateformat = "%Y-%m-%d %H:%M:%S"
        logger.debug("Plugin UTC ISO Normalized date: %s" % plugin_utc_dt.strftime(dateformat))
        event['tzone'] = tzone_float
        if 'fdate' in event.EVENT_ATTRS:
            event["date"] = calendar.timegm(plugin_utc_dt.timetuple()) #int(mktime(plugin_utc_dt.timetuple()))
            event["fdate"] = plugin_utc_dt.strftime(dateformat)


    def sendEvent(self,event):
        if self.__isAlive:
            try:
                self.__connection.send(event)
            except socket.error, e:
                logger.error(str(e))
                self.close()
            except AttributeError,e:
                logger.error("Atributte Error, %s" % str(e))
                self.close()
            else:
                logger.debug(event.rstrip())


    def close(self):
        self.__isAlive = False
        if self.__connection is not None:
            self.__connection.close()
        self.__connection = None


    def __run_rule(self,rulename, rule):
        logger.debug("Running rule..:%s" % rulename)
        current_time = time()
        timeout_rule = 0
        sqlquery = ''
        if not rule['valid']:
            logger.warning("Invalid rule :%s, please check it." % rulename)
            return
        sqlquery = rule['sql_query']
        timeout = float(rule['timeout'])
        current_time = time()
        elapsed_time = current_time - rule['last_run']
        logger.debug("Elapsed time from last run: %s  timeout:%s" %(elapsed_time,timeout))
        if elapsed_time > timeout:
            rule ['last_run'] = time()
            self.__rule_update[rulename] = rule['last_run']
            data = self.__runQuery(sqlquery)
            for row in data:
                event = self.__generateEvent(row,rule)
                if event:
                    logger.info("Send Event: %s" % event)
                    self.sendEvent(str(event))


    def run(self):
        if not self.__isAlive:
            self.__connect(attempts=3, waittime=10)
        
        while self.__keepWorking:
            if not self.__isAlive:
                self.__connect(attempts=3, waittime=10)
                continue
            for key,rule in self.__rules.iteritems():
                if rule['enable']:
                    self.__run_rule(key,rule)
            self.__updateRuleUpdateFile()
            sleep(2)


if __name__ == "__main__":
    server_config = OssimMiniConf(config_file='/etc/ossim/ossim_setup.conf')
    
    print server_config['server_ip']
    print server_config['server_port']
    pcm = PostCorrelationManager()
    pcm.start()
    try:
        while True:
            sleep(1)
    except KeyboardInterrupt:
        print "Ctrl-c received! Stopping PostCorrelationManager..."
        pcm.stop()
        pcm.join(1)
        sys.exit(0)
    
