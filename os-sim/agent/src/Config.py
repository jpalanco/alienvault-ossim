# -*- coding: utf-8 -*-
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

#
# GLOBAL IMPORTS
#
import os, sys, string, re
import codecs


#
# LOCAL IMPORTS
#
from ConfigParser import ConfigParser
from optparse import OptionParser
from Exceptions import AgentCritical
from Logger import Logger

logger = Logger.logger
import ParserUtil


class Conf(ConfigParser):

    DEFAULT_CONFIG_FILE = "/etc/ossim/agent/config.cfg"

    # fill this table with the *mandatory* entries
    # that need to be present in config.cfg file
    _NEEDED_CONFIG_ENTRIES = {
        'daemon': [],
        'log': [],
        'plugin-defaults': ['sensor', 'interface', 'tzone'],
        'watchdog': ['enable', 'interval'],
        'output-server': ['enable', 'ip', 'port'],
        'plugins': [],
    }
    _EXIT_IF_MALFORMED_CONFIG = True
    __validConfig = True

    # Overwrites OPTCRE option in order to allow the use of ':' inside the option names.
    # This is fixed in pythonn 3.2 (delimiters options)
    OPTCRE = re.compile(
        r'(?P<option>[^:=\s][^=]*)'          # very permissive!
        r'\s*(?P<vi>[:=])\s*'                 # any number of space/tab,
                                              # followed by separator
                                              # (either : or =), followed
                                              # by any # space/tab
        r'(?P<value>.*)$'                     # everything up to eol
        )


    # same as ConfigParser.read() but also check
    # if configuration files exists
    def read(self, filenames, encoding='latin1',check_neededEntries = True):
        self.__validConfig = True
        fp=None
        for filename in filenames:
            if not os.path.isfile(filename):
                AgentCritical("Configuration file (%s) does not exist!" % (filename))
            try:
                fp = codecs.open(filename, 'r', encoding=encoding)
                self.readfp(fp)
            except Exception,e:
                logger.error("Invalid plugin file:%s",str(e))
                self.__validConfig = False
            if check_neededEntries:
                self.check_needed_config_entries()


    # check for needed entries in .cfg files
    # this function uses the variable _NEEDED_CONFIG_ENTRIES
    def check_needed_config_entries(self):
        for section, values in self._NEEDED_CONFIG_ENTRIES.iteritems():
            if not self.has_section(section):
                logger.critical (
                    "Needed section [%s] not found!" % (section))
                self.__validConfig = False
                if self._EXIT_IF_MALFORMED_CONFIG:
                    sys.exit()
            for value in values:
                if not self.has_option(section, value):
                    self.__validConfig = False
                    logger.critical (
                        "Needed option [%s->%s] not found!" % (section, value))
                    if self._EXIT_IF_MALFORMED_CONFIG:
                        sys.exit()

    def get_validConfig(self):
        return self.__validConfig

    # avoid confusions in configuration values
    def _strip_value(self, value):
        from string import strip
        return strip(strip(value, '"'), "'")


    # same as RawConfigParser.items() but returns a hash instead of a list
    def hitems(self, section,braw=False):
        hash = {}
        for item in self.items(section,braw):
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
        conf_str = '<sensor-config>\n'
        for section in sorted(self.sections()):
            conf_str += '  <section name="%s">\n' % (section)
            for i in self.items(section):
                conf_str += '    <item name="%s" value="%s" />\n' % (i[0], i[1])
            conf_str += '  </section>\n'
        conf_str += '</sensor-config>'
        return conf_str



class Plugin(Conf):

    # fill this table with the *mandatory* entries
    # that need to be present in a plugin.cfg file
    _NEEDED_CONFIG_ENTRIES = {
        'config': ['type', 'source', 'enable']
    }
    _EXIT_IF_MALFORMED_CONFIG = False
    TRANSLATION_SECTION = 'translation'
    TRANSLATION_FUNCTION = 'translate'
    TRANSLATION_DEFAULT = '_DEFAULT_'
    SECTIONS_NOT_RULES = ["config", "info", TRANSLATION_SECTION]

    CONCAT_FUNCTION = 'CONCAT'
    
    # constants for _replace_*_assess functions
    _MAP_REPLACE_TRANSLATIONS = 4
    _MAP_REPLACE_USER_FUNCTIONS = 8
    _MAP_REPLACE_CUSTOM_USER_FUNCTIONS = 16
    _MAP_REPLACE_CONCAT = 32 #00100000
    def rules(self):
        rules = {}
        for section in sorted(self.sections()):
            if section.lower() not in Plugin.SECTIONS_NOT_RULES :
                rules[section] = self.hitems(section,True)

        return rules


    def _replace_array_variables(self, value, groups):
        '''
            Replace positional variables:
            userdata1={$1}
        '''
        for i in range(2):
            search = re.findall("\{\$[^\}\{]+\}", value, re.UNICODE)
            rvalue = value
            if search != []:
                for string in search:
                    var = string[2:-1]
                    var_position = 0
                    try:
                        var_position= int(var)
                        if var_position < len(groups):
                            rvalue = rvalue.replace(string, str(groups[var_position]))
                    except ValueError,e:
                        rvalue =  value
                    #if int(var) < len(groups):
                    #    value = rvalue.replace(string, str(groups[int(var)]))

        return rvalue

    def get_replace_array_value(self, value, groups):
        # 1) replace variables
        rvalue = ""
        rvalue = self._replace_array_variables(value, groups)
        if rvalue==value:
            # if rvalue ==  value -> nothing have been replaced.. try to replace fucntions.
            # 2) replace user functions
            rvalue = self._replace_user_array_functions(value, groups)
        if rvalue == value:
            # if rvalue ==  value -> nothing have been replaced.. try to replace custom fucntions.
            # 3) replace custom user functions.
            rvalue = self._replace_custom_user_function_array(value,groups)
        return rvalue

    def _replace_custom_user_function_array(self,value,groups):
        '''
            Replace custom user functions using positional arguments
            data={:functionName($1)}
        '''
        search = re.findall("(\{:(\w+)\((\$[^\)]+)?\)\})", value, re.UNICODE)
        if search != []:
            for string in search:
                (string_matched, func, variables) = string
                vars = split_variables(variables)

                # check that all variables have a replacement
                for var in vars:
                    var_pos =0
                    try:
                        var_pos = int(var)
                    except TypeError:
                        logger.warning("Can not replace '%s'" % var)
                        return value
                    if var_pos >= len(groups):
                        logger.warning("Can not replace '%s' " % var)
                        return value

                # call function 'func' with arg groups[var]
                # functions are defined in ParserUtil.py file
                func_name = "%s_%s" % (func,self.get("DEFAULT", "plugin_id"))
                if func_name != Plugin.TRANSLATION_FUNCTION and \
                   hasattr(Plugin, func_name):

                    # 'f' is the function to be called
                    # 'vars' are the list of arguments of the function
                    # 'args' are a custom representation of the list
                    #        to be used as f argument [ f(args) ]
                    args = ",".join(["groups[%s]"%s  for s in vars])
                    # exec replacement
                    try:
                        cmd = "value = value.replace(string_matched, str(self.%s(%s)))" %(func_name,args)
                        exec cmd
                    except TypeError, e:
                        logger.error(e)

                else:
                    logger.warning( "Function '%s' is not implemented" % (func))
                    value = value.replace(string_matched, str(groups[var]))

        return value


    def _replace_user_array_functions(self, value, groups):
        '''
            Replace positional functions:
            {normalize($2)}
        '''
        if value is None:
            return None
        search = re.findall("(\{(\w+)\((\$[^\)]+)\)\})", value, re.UNICODE)
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
                if func != Plugin.TRANSLATION_FUNCTION and \
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
                        if int(v) > (len(groups) - 1):
                            logger.debug("Error var:%d, is greatter than groups size. (%d)" % (int(v), len(groups)))
                        else:
                            var_value = groups[int(v)] 
                            if self.has_section(Plugin.TRANSLATION_SECTION):
                                if self.has_option(Plugin.TRANSLATION_SECTION, var_value):
                                    value = self.get(Plugin.TRANSLATION_SECTION, var_value)

#                    # exec replacement
#                    try:
#                        exec "value = value.replace(string_matched," + \
#                            "str(f(" + args + ")))"
#                    except TypeError, e:
#                        logger.error(e)


        return value


    # look for \_CFG(section,option) values in config parameters
    # and replace this with value found in global config file 
    def replace_config(self, conf):

        for section in sorted(self.sections()):
            for option in self.options(section):
                regexp = self.get(section, option)
                search = re.findall("(\\\\_CFG\(([\w-]+),([\w-]+)\))", regexp, re.UNICODE)
                if search != []:
                    for string in search:
                        (all, arg1, arg2) = string
                        if conf.has_option(arg1, arg2):
                            value = conf.get(arg1, arg2)
                            regexp = regexp.replace(all, value)
                            self.set(section, option, regexp)


    def replace_aliases(self, aliases):
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

        for i in range(rounds):
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
        ret = 0
        for i in range(2):
            search = re.findall("\{\$[^\}\{]+\}", value, re.UNICODE)
            if search != []:
                ret = i
        return ret


    # special function translate() for translations
    # translations are defined in the own plugin with a [translation] entry
    # this function is called from get_replace_value()
    def _replace_translations(self, value, groups):
        regexp = "(\{(" + Plugin.TRANSLATION_FUNCTION + ")\(\$([^\)]+)\)\})"
        search = re.findall(regexp, value, re.UNICODE)
        if search != []:
            for string in search:
                (string_matched, func, var) = string
                if groups.has_key(var):
                    if self.has_section(Plugin.TRANSLATION_SECTION):
                        if self.has_option(Plugin.TRANSLATION_SECTION,
                                           groups[var]):
                            value = self.get(Plugin.TRANSLATION_SECTION,
                                             groups[var])
                        else:
                            logger.warning("Can not translate '%s' value" % \
                                (groups[var]))

                            # It's not possible to translate the value,
                            # revert to _DEFAULT_ if the entry is present
                            if self.has_option(Plugin.TRANSLATION_SECTION,
                                               Plugin.TRANSLATION_DEFAULT):
                                value = self.get(Plugin.TRANSLATION_SECTION,
                                                 Plugin.TRANSLATION_DEFAULT)
                            else:
                                value = groups[var]
                    else:
                        logger.warning("There is no translation section")
                        value = groups[var]

        return value


    # determine if replace translations achieves anything and if not we can
    # skip it later on in get_replace_value()
    def _replace_translations_assess(self, value):
        regexp = "(\{(" + Plugin.TRANSLATION_FUNCTION + ")\(\$([^\)]+)\)\})"
        search = re.findall(regexp, value, re.UNICODE)
        if search != []:
            return self._MAP_REPLACE_TRANSLATIONS

        return 0

    # functions are specified as {f($v)}
    # this function is called from get_replace_value()
    def _replace_user_functions(self, value, groups):

        search = re.findall("(\{(\w+)\((\$[^\)]+)\)\})", value, re.UNICODE)
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
                if func != Plugin.TRANSLATION_FUNCTION and \
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
        search = re.findall("(\{(\w+)\((\$[^\)]+)\)\})", value, re.UNICODE)
        if search != []:
            return self._MAP_REPLACE_USER_FUNCTIONS
        
        return 0


    def _replace_custom_user_functions_assess(self,value):
        search = re.findall("(\{:(\w+)\((\$[^\)]+)?\)\})", value, re.UNICODE)
        if search != []:
            return self._MAP_REPLACE_CUSTOM_USER_FUNCTIONS
      
        return 0


    def _replace_custom_user_functions(self,value,groups):
        search = re.findall("(\{:(\w+)\((\$[^\)]+)?\)\})", value, re.UNICODE)
        if search != []:
            for string in search:
                (string_matched, func, variables) = string
                vars = split_variables(variables)

                # check that all variables have a replacement
                for var in vars:

                    if not groups.has_key(var):
                        logger.warning("Can not replace '%s'" % (var))
                        return value

                # call function 'func' with arg groups[var]
                # functions are defined in ParserUtil.py file
                func_name = "%s_%s" % (func,self.get("DEFAULT", "plugin_id"))
                if func_name != Plugin.TRANSLATION_FUNCTION and \
                   hasattr(Plugin, func_name):

                    # 'f' is the function to be called
                    # 'vars' are the list of arguments of the function
                    # 'args' are a custom representation of the list
                    #        to be used as f argument [ f(args) ]
                    args = ""
                    for i in (range(len(vars))):
                        args += "groups[vars[%s]]," % (str(i))

                    # exec replacement
                    try:
                        cmd = "value = value.replace(string_matched, str(self.%s(%s)))" %(func_name,args)
                        exec cmd
                    except TypeError, e:
                        logger.error(e)

                else:
                    logger.warning(
                        "Function '%s' is not implemented" % (func))

                    value = value.replace(string_matched, \
                                          str(groups[var]))

        return value

    def __replaceConcatFunction(self,value,groups):
        """Replaeces the value for the concatenation of the array variables.
        """
        concat =""
        m =  re.match("\$CONCAT\((?P<params>.*)\)",value)
        if m:
            mdict = m.groupdict()
            if mdict.has_key('params'):
                paramlist = mdict['params'].split(',')
                for param in paramlist:
                    if param.startswith('$'):#assume variable
                        if groups.has_key(param[1:]):
                            concat+=groups[param[1:]]
                        else:
                            concat+=param
                    else:
                        concat+=param
            #check for variables.
        return concat


    def __checkReplaceConcatFunction(self,value):
        """Check if it is neccesary to do a concat function replacement
        @param value: the string in the rule
        """
        ret = 0
        if re.match("\$CONCAT\((.*)\)",value):
            ret = self._MAP_REPLACE_CONCAT
        return ret
    def replace_value_assess(self, value):
        ret = self._replace_variables_assess(value)
        ret |= self._replace_translations_assess(value)
        ret |= self._replace_user_functions_assess(value)
        ret |= self._replace_custom_user_functions_assess(value)
        ret |= self.__checkReplaceConcatFunction(value)
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

            # replace custom user functions
            if replace & self._MAP_REPLACE_CUSTOM_USER_FUNCTIONS:
                value = self._replace_custom_user_functions(value,groups)
            
            # replace concat function
            if replace & self._MAP_REPLACE_CONCAT:
                value = self.__replaceConcatFunction(value,groups)


        return value
    def setUnicode(self):
        self.__UTF8_ENCODED = True
    def isUnicode(self):
        return self.__UTF8_ENCODED




class Aliases(Conf):
    _NEEDED_CONFIG_ENTRIES = {}


class CommandLineOptions:

    def __init__(self):

        self.__options = None

        parser = OptionParser(
            usage="%prog [-v] [-q] [-d] [-f] [-g] [-c config_file]",
            version="OSSIM (Open Source Security Information Management) " + \
                      "- Agent ")

        parser.add_option("-v", "--verbose", dest="verbose",
                          action="count",
                          help="verbose mode, makes lot of noise")
        parser.add_option("-d", "--daemon", dest="daemon", action="store_true",
                          help="Run agent in daemon mode")
        parser.add_option("-f", "--force", dest="force", action="store_true",
                          help="Force startup overriding pidfile")
        parser.add_option("-s", "--stats", dest="stats", type='choice', choices=['all', 'clients', 'plugins'], default=None,
                          help="Get stats about the agent")
        parser.add_option("-c", "--config", dest="config_file", action="store",
                          help="read config from FILE", metavar="FILE")
        (self.__options, args) = parser.parse_args()

        if len(args) > 1:
            parser.error("incorrect number of arguments")

        if self.__options.verbose and self.__options.daemon:
            parser.error("incompatible options -v -d")


    def get_options(self):
        return self.__options


# create an array from a list of variables
# for example:
# "$1,$2, $3,   $5" => [1, 2, 3, 5]
def split_variables(string):
    return re.findall("(?:\$([^,\s]+))+", string)


# create an array from a list of sids
# for example:
# "1,2,3-6,7" => [1, 2, 3, 4, 5, 6, 7]
def split_sids(string, separator=','):

    list = list_tmp = []

    # split by 'separator'
    list = string.split(separator)

    # split by "-"
    for sid in list:
        a = sid.split('-')
        if len(a) == 2:
            list.remove(sid)
            for i in range(int(a[0]), int(a[1]) + 1):
                list_tmp.append(str(i))

    list.extend(list_tmp)
    return list


if __name__ == '__main__':
    conf = Conf()
    conf.read(['/etc/ossim/agent/config.cfg'])
    print conf


# vim:ts=4 sts=4 tw=79 expandtab:

