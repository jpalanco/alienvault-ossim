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
import re, time
from pytz import timezone, all_timezones

#
# LOCAL IMPORTS
#
from Config import Conf, Plugin, Aliases, CommandLineOptions
import Config
from Logger import Logger
from Output import Output
from Stats import Stats
import Utils
from base64 import b64decode
logger = Logger.logger


class Monitor:

    def __init__(self, plugin, watch_rule):
        self.plugin = plugin
        self.options = CommandLineOptions().get_options()

        # read configuration
        self._conf = Conf()
        if self.options.config_file:
            conffile = self.options.config_file
        else:
            conffile = self._conf.DEFAULT_CONFIG_FILE
        self._conf.read([conffile],'latin1')
        self.watch_rule = watch_rule
        groups =  self.watch_rule.dict()
        for item,value in groups.iteritems():
            if item in self.watch_rule.EVENT_BASE64:
                groups[item] = b64decode(value)
        self.queries = \
            self.get_replaced_values('query',groups)
        self.regexps = \
            self.get_replaced_values('regexp', groups)
        self.results = \
            self.get_replaced_values('result', groups)
        self.initial_time = int(time.time()) # initial time at object call
        self.first_value = None

        if "tzone" in self.plugin.hitems("DEFAULT"):
            self.timezone = self.plugin.get("DEFAULT", "tzone")
            logger.debug("Plugin %s (%s) with specific tzone = %s" % \
                         (self.plugin.get("config", "name"),
                          self.plugin.get("DEFAULT", "plugin_id"),
                          self.timezone))
        else:
            self.timezone = self._conf.get("plugin-defaults", "tzone")

        self.__agenttimezone = None
        self.__EventTimeZone = None
        self.__systemTimeZone = None
        self.__set_system_tzone()
        self.__setTZData()


        self.open()

    def get_replaced_values(self, key, groups):

        # replace plugin variables with watch_rule data
        #
        # for example, given the following watch_rule:
        # 
        #     watch-rule plugin_id="2006" plugin_sid="1" condition="eq"
        #                value="1" from="192.168.6.64" to="192.168.6.63"
        #                port_from="5643" port_to="22"
        #
        #  and the following plugin query:
        #     query = {$from}:{$port_from} {$to}:{$port_to}
        #
        #  replace the variables with the watch-rule data:
        #     query = 192.168.6.64:5643 192.168.6.63:22

        values = {}
        for rule_name, rule in self.plugin.rules().iteritems():
            if key !='result':
                values[rule_name] = self.plugin.get_replace_value(rule[key], groups)
            else:
                values[rule_name]=rule[key]

        return values

    def _plugin_defaults(self, event, log):


        # get default values from config
        #
        ipv4_reg = "^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$"
        if self._conf.has_section("plugin-defaults"):

        # 1) date
            default_date_format = self._conf.get("plugin-defaults",
                                                 "date_format")
            if event["date"] is None and default_date_format:
                event["date"] = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
                event["fdate"] = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))


        # 2) sensor
            default_sensor = self._conf.get("plugin-defaults", "sensor")
            if event["sensor"] is None and default_sensor:
                event["sensor"] = default_sensor

        # 3) interface
            default_iface = self._conf.get("plugin-defaults", "interface")
            if event["interface"] is None and default_iface:
                event["interface"] = default_iface

        # 4) source ip
            if event["src_ip"] is None:
                event["src_ip"] = event["from"]

        # 5) dest ip
            if event["dst_ip"] is None:
                event["dst_ip"] = event["to"]

        # 6) protocol
            if event["protocol"] is None:
                event["protocol"] = "TCP"

        # 7) ports
            if event["src_port"] is None:
                event["src_port"] = event["port_from"]
            if event["dst_port"] is None:
                event["dst_port"] = event["port_to"]
            if event["src_port"] is None:
                event["src_port"] = 0
            if event["dst_port"] is None:
                event["dst_port"] = 0
            if event["src_ip"] is None:
                event["src_ip"] = event["sensor"]
            if event["dst_ip"] is None:
                event["dst_ip"] = event["sensor"]

        # 8) Time zone
            if 'tzone' in event.EVENT_ATTRS:
                Utils.normalizeToUTCDate(event, self.__EventTimeZone)

        #Check if valid ip, if not we put 0.0.0.0 in sensor field
        if event['src_ip'] is not None:
            if not re.match(ipv4_reg, event['src_ip']):
                data = event['src_ip']
                event['src_ip'] = '0.0.0.0'
                print ("Event's field src_ip (%s) is not a valid IP.v4/IP.v6 address, set it to default ip 0.0.0.0 and real data on userdata8" % (data))
                event['userdata8'] = data
        elif 'src_ip' in event.EVENT_ATTRS:
            event['src_ip'] = '0.0.0.0'
        #Check if valid ip, if not we put 0.0.0.0 in sensor field
        if event['dst_ip'] is not None:
            if not re.match(ipv4_reg, event['dst_ip']):
                data = event['dst_ip']
                print ("Event's field dst_ip (%s) is not a valid IP.v4 address, set it to default ip 0.0.0.0 and real data on userdata9" % (data))
                event['dst_ip'] = '0.0.0.0'
                event['userdata9'] = data
        elif 'dst_ip' in event.EVENT_ATTRS:
            event['dst_ip'] = '0.0.0.0'
        event["log"] = log





        # the type of this event should always be 'monitor'
        if event["type"] is None:
            event["type"] = 'monitor'

        # Clean up mess
        event["port_from"] = ""
        event["port_to"] = ""
        event["to"] = ""
        event["from"] = ""
        event["absolute"] = ""
        event["interval"] = ""

        return event


    def __set_system_tzone(self):
        """Sets the system timezone by reading the timezone """
        try:
            #read local timezone information. 
            f = open('/etc/timezone', 'r')
            used_tzone = f.readline().rstrip()
            f.close()
            if used_tzone not in all_timezones:
                logger.info("Warning, we can't read valid timezone data.Using GMT")
                used_tzone = 'GMT'
            self.systemtzone = used_tzone
        except Exception, e:
            used_tzone = 'GMT'
            logger.info("Warning, we can't read valid timezone data.Using GMT")


    def __setTZData(self):
        self.__agenttimezone = self._conf.get("plugin-defaults", "tzone")
        if "tzone" in self.plugin.hitems("DEFAULT"):
            self.timezone = self.plugin.get("DEFAULT", "tzone")
            logger.warning ("Plugin %s (%s) with specific tzone = %s" % \
                         (self.plugin.get("config", "name"),
                          self.plugin.get("DEFAULT", "plugin_id"),
                          self.timezone))
        else:
            self.timezone = self._conf.get("plugin-defaults", "tzone")
        logger.info("Starting detector %s (%s)..Plugin tzone: %s" % \
                    (self.plugin.get("config", "name"),
                     self.plugin.get("DEFAULT", "plugin_id"),
                     self.timezone))
        self.__checkTimeZone()


    def __checkTimeZone(self):
        if self.timezone in all_timezones:
            used_tzone = self.timezone
            logger.warning ("Using custom plugin tzone data: %s" % used_tzone)
        elif self.__agenttimezone in all_timezones:
            used_tzone = self.__agenttimezone
            logger.info("Warning: Invalid plugin tzone (%s) information (Not found in database). Using agent tzone: %s" % (self.timezone, used_tzone))
        else:
            used_tzone = self.__systemTimeZone
            logger.info("Warning: Invalid plugin tzone and invalid agent tzone, using system tzone: %s" % used_tzone)
        self.__EventTimeZone = used_tzone



    # given the server's watch_rule, find what rule to apply
    def match_rule(self):
        plugin_sid = self.watch_rule['plugin_sid']
        for rule_name, rule in self.plugin.rules().iteritems():
            for sid in Config.split_sids(str(rule['sid'])): # sid=1,2-4,5
                if str(plugin_sid) == str(sid) or str(sid).lower() == 'any':
                    return rule_name
        return None

    # eval watch rule condition
    def eval_condition(self, cond, arg1, arg2, value):

        if type(arg1) is not int:
            try:
                arg1 = int(arg1)
            except ValueError:
                logger.warning(
                    "value returned by monitor (arg1=%s) is not an integer" % \
                    str(arg1))
                return False

        if type(arg2) is not int:
            try:
                arg2 = int(arg2)
            except ValueError:
                logger.warning(
                    "value returned by monitor (arg2=%s) is not an integer" % \
                    str(arg2))
                return False

        if type(value) is not int:
            try:
                value = int(value)
            except ValueError:
                logger.warning(
                    "value returned by monitor (value=%s) is not an integer" % \
                    str(value))
                return False

        logger.debug("Monitor expresion evaluation: " + \
            "%s(arg2) <%s> %s(arg1) + %s(value)?" % \
            (str(arg2), str(cond), str(arg1), str(value)))

        if cond == "eq":
            return (int(arg2) == int(arg1) + int(value))
        elif cond == "ne":
            return (int(arg2) != int(arg1) + int(value))
        elif cond == "gt":
            return (int(arg2) > int(arg1) + int(value))
        elif cond == "ge":
            return (int(arg2) >= int(arg1) + int(value))
        elif cond == "le":
            return (int(arg2) <= int(arg1) + int(value))
        elif cond == "lt":
            return (int(arg2) < int(arg1) + int(value))
        else:
            return False
 
    # given the watch rule, ask to Monitor and obtain a result
    # *must* be overriden in child classes:
    # different implementations for each type of monitor
    # (socket, database, etc.)
    def get_data(self, rule_name):
        pass

    # *must* be overriden in child classes:
    def open(self):
        pass

    # *must* be overriden in child classes:
    def close(self):
        pass


    # TODO: merge with ParserLog.feed()
    #
    def get_value(self, monitor_response, rule_name):

        value = None
        hash = {}
        count = 1

        regexp = self.regexps[rule_name]
        pattern = re.compile(regexp, re.IGNORECASE | re.MULTILINE)
        
        # TODO: monitor_response could possibly be a list
        if isinstance(monitor_response, list):
            match = pattern.search(monitor_response[0])
        else:
            match = pattern.search(monitor_response)

        if match is not None:
            groups = match.groups()

            for group in groups:

                # group by index ()
                if group is None: 
                    group = ''
                hash.update({str(count): str(group)})
                count += 1

                # group by name (?P<name-of-group>)
                hash.update(match.groupdict())
        else:
            return None


        # first, try getting substitution from the regular expresion syntax
        result = self.results[rule_name]
        value = self.plugin.get_replace_value(result, hash)
        try:
            val = int(value.split(".")[0])
        except:
            return False

        return val


    # get a new value from monitor and compare with the first one
    # returns True if the condition apply, False in the other case
    def evaluate(self, rule_name):
        
        if self.first_value is None:
            logger.debug("Can not extract value (arg1) from monitor response or no initial value to compare with")
            return True

        value = None
        monitor_response = self.get_data(rule_name)
        if not monitor_response:
            logger.warning("No data received from monitor")
            return True
        else:
            value = self.get_value(monitor_response, rule_name)
            if value is None:
                return True
            #if not value:
            #    continue
            if self.eval_condition(cond=self.watch_rule["condition"],
                                   arg1=self.first_value,
                                   arg2=value,
                                   value=int(self.watch_rule["value"])):
                self.watch_rule["type"] = "monitor"
                try:
                    cond = self.watch_rule["condition"]
                    arg1 = self.first_value
                    arg2 = value
                    value = int(self.watch_rule["value"])
                    comm = self.queries
                    log = "Monitor Command: %s , Monitor expresion evaluation: %s(arg2) <%s> %s(arg1) + %s(value)? , Command Response: %s" % (str(comm), str(arg2), str(cond), str(arg1), str(value), monitor_response.replace("\n", "\r"))
                except:
                    log = "Monitor Exception"
                self.watch_rule = self._plugin_defaults(self.watch_rule, log)
                Output.event(self.watch_rule)
                Stats.new_event(self.watch_rule)
                return True
        logger.info( "No data matching the watch-rule received from monitor cond:%s arg1:%s arg2: %s value:%s" % \
            (self.watch_rule["condition"],self.first_value,value,self.watch_rule["value"]))
        return False


    # *may* be overriden in child classes
    def process(self):

        # get the name of rule to apply
        rule_name = self.match_rule()
        if rule_name is not None:
            logger.info("Matched rule: [%s]" % (rule_name))

        # get data from plugin (first time)
            if self.first_value is None:

        # <absolute> is "no" by default
        # the absence of <interval> implies that <absolute> is "yes"
                if self.watch_rule['absolute'] in ('yes', 'true') or\
                   not self.watch_rule['interval']:
                    self.first_value = 0
                else:
                    monitor_response = self.get_data(rule_name)
                    if not monitor_response:
                        self.first_value = 0
                    for resp in monitor_response:
                        if resp:
                            self.first_value = self.get_value(resp, rule_name)
                            if self.first_value == False:
                                self.first_value = 0

        # get current time
        current_time = int(time.time())

        # Three posibilities:
        #
        # 1) no interval specified, no need to wait
        if not self.watch_rule.dict().has_key('interval'):
            self.evaluate(rule_name)

        # 2) we are in time, check the result of the watch-rule
        elif (self.initial_time + \
                int(self.watch_rule["interval"]) > current_time):
            self.evaluate(rule_name)

        # 3) we are out of time
        else:
            self.evaluate(rule_name)
        return True

class MonitorFile(Monitor):
    pass



# vim:ts=4 sts=4 tw=79 expandtab:

