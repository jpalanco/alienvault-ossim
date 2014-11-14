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
import threading
import time
import commands
#
# LOCAL IMPORTS
#
import Config
import Event
from Event import EventIdm
from Logger import Logger
from Output import Output
from Stats import Stats
from Threshold import EventConsolidation
import re
import uuid
#from datetime import datetime, timedelta
from pytz import timezone, all_timezones
#import pytz
from time import mktime, gmtime, strftime
import Utils
import socket
#
# GLOBAL VARIABLES
#
logger = Logger.logger


DEFAULT_PLUGIN_SID=20000123456
class Detector(threading.Thread):

    def __init__(self, conf, plugin, conn):

        self._conf = conf
        self._plugin = plugin
        self.os_hash = {}
        self.conn = conn
        self.consolidation = EventConsolidation(self._conf)
        self.systemtzone = None
        if "tzone" in self._plugin.hitems("DEFAULT"):
            self._timezone = self._plugin.get("DEFAULT", "tzone")
            logger.debug("Plugin %s (%s) with specific tzone = %s" % \
                         (self._plugin.get("config", "name"),
                          self._plugin.get("DEFAULT", "plugin_id"),
                          self._timezone))
        else:
            self._timezone = self._conf.get("plugin-defaults", "tzone")
        self._sensorID = None
        if "sensor_id" in self._conf.hitems("plugin-defaults"):
            self._sensorID = self._conf.get("plugin-defaults", "sensor_id")

        logger.info("Starting detector %s (%s)..Plugin tzone: %s" % \
                    (self._plugin.get("config", "name"),
                     self._plugin.get("DEFAULT", "plugin_id"),
                     self._timezone))
        threading.Thread.__init__(self)
        self._agenttimezone = self._conf.get("plugin-defaults", "tzone")
        self._EventTimeZone = None
        if self._conf.has_option("plugin-defaults", "override_sensor"):
            self.override_sensor = self._conf.getboolean("plugin-defaults", "override_sensor")
        else:
            self.override_sensor = False

        #2011-02-01 17:00:16
        self.patterndate = re.compile('(\d{10})')
        self.patternISO_date = re.compile('(?P<year>\d+)[\s-](?P<month>\d+)[\s-](?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)')
#        self.patternUTClocalized = re.compile('(?P<year>\d+)[\s-](?P<month>\d+)[\s-](?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)(?P<tzone_symbol>[-|+])(?P<tzone_hour>\d{2}):(?P<tzone_min>\d{2})')
        self.set_system_tzone()
        self.checkTimeZone()
    def __isDigit(self,value):
        """Checks if value is a digit
        @returns true if value is a number, otherwise false
        """

        rt = True
        try:
            a = int(value)
        except:
            rt = False
        return rt
    def set_system_tzone(self):
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

    def checkTimeZone(self):
        if self._timezone in all_timezones:
            used_tzone = self._timezone
            logger.debug("Using custom plugin tzone data: %s" % used_tzone)
        elif self._agenttimezone in all_timezones:
            used_tzone = self._agenttimezone
            logger.info("Warning: Invalid plugin tzone (%s) information (Not found in database). Using agent tzone: %s" % (self._timezone, used_tzone))
        else:
            used_tzone = self.set_system_tzone()
        self._EventTimeZone = used_tzone

    def _event_os_cached(self, event):

        if isinstance(event, Event.EventOS):
            import string
            current_os = string.join(string.split(event["os"]), ' ')
            previous_os = self.os_hash.get(event["host"], '')

            if current_os == previous_os:
                return True

            else:
                # Fallthrough and add to cache
                self.os_hash[event["host"]] = \
                    string.join(string.split(event["os"]), ' ')

        return False


    def _exclude_event(self, event):

        if self._plugin.has_option("config", "exclude_sids"):
            exclude_sids = self._plugin.get("config", "exclude_sids")
            if event["plugin_sid"] in Config.split_sids(exclude_sids):
                logger.debug("Excluding event with " + \
                    "plugin_id=%s and plugin_sid=%s" % \
                    (event["plugin_id"], event["plugin_sid"]))
                return True

        return False

    def _thresholding(self):
        """
        This section should contain:
          - Absolute thresholding by plugin, src, etc...
          - Time based thresholding
          - Consolidation
        """

        self.consolidation.process()

    def _getLocalIP(self):
        if self.override_sensor:
            logger.info("override_sensor detected")

        if self._conf.has_section("plugin-defaults"):
            mylocalip = self._conf.get("plugin-defaults", "sensor")
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

            
    def isIPV6(self, string_ip):
        ipv6 = True
        try:
            socket.inet_pton(socket.AF_INET6, string_ip)
        except:
            ipv6 = False
        return ipv6
    def _plugin_defaults(self, event):
        ipv4_reg = "^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$"
        # get default values from config
        #
        #override_sensor = self._conf.getboolean( "plugin-defaults", "override_sensor" )
        #event_id = uuid.uuid1()
        #event['event_id'] = str(event_id)
        if self._sensorID is not None:
            event['sensor_id'] = self._sensorID
        if 'plugin_sid' in event.EVENT_ATTRS:
            if event['plugin_sid'] == "0":
                event['plugin_sid'] = DEFAULT_PLUGIN_SID
        if  not self.__isDigit(event['plugin_sid']) and not str(event).startswith("idm-event"):
            logger.warning("Event discarded [%s] Plugin sid not a number..." % (str(event)))
            return ""
        if  not self.__isDigit(event['plugin_id']) and not str(event).startswith("idm-event"):
            logger.warning("Event discarded [%s] Plugin id not a number... " % (str(event)))
            return ""
        # 1) date
        if self._conf.has_section("plugin-defaults"):
            default_date_format = self._conf.get("plugin-defaults",
                                                 "date_format")
            if event["date"] is None and default_date_format and \
               'date' in event.EVENT_ATTRS:
                event["date"] = time.strftime(default_date_format,
                                              time.localtime(time.time()))

        # 2) sensor
            default_sensor = self._conf.get("plugin-defaults", "sensor")
            if event["sensor"] is None  and default_sensor and \
               'sensor' in event.EVENT_ATTRS and not self.override_sensor:
                event["sensor"] = default_sensor

        # 3) interface
            default_iface = self._conf.get("plugin-defaults", "interface")
            if event["interface"] is None and default_iface and \
               'interface' in event.EVENT_ATTRS:
                event["interface"] = default_iface

        # 4) source ip
            if event["src_ip"] is None and 'src_ip' in event.EVENT_ATTRS:
                event["src_ip"] = event["sensor"]

        # 5) Time zone 
            #default_tzone = self._conf.get("plugin-defaults", "tzone")
            if 'tzone' in event.EVENT_ATTRS:
                Utils.normalizeToUTCDate(event, self._EventTimeZone)
        # 6) sensor,source ip and dest != localhost
            if event["sensor"] in ('127.0.0.1', '127.0.1.1', '::1') and not self.override_sensor:
                event["sensor"] = default_sensor

            if event["dst_ip"] in ('127.0.0.1', '127.0.1.1', '::1') and 'dst_ip' in event.EVENT_ATTRS:
                event["dst_ip"] = event['device']

            if event["src_ip"] in ('127.0.0.1', '127.0.1.1', '::1','localhost') and 'src_ip' in event.EVENT_ATTRS:
                event["src_ip"] = event['device']

            #Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['src_ip'] is not None and 'src_ip' in event.EVENT_ATTRS:
                #check for ipv6
                if not re.match(ipv4_reg, event['src_ip']) and not self.isIPV6(event['src_ip']):
                    data = event['src_ip']
                    event['src_ip'] = '0.0.0.0'
                    logger.warning("Event's field src_ip (%s) is not a valid IP.v4/IP.v6 address, set it to default ip 0.0.0.0" % data)
                    #event['userdata8'] = data
            elif 'src_ip' in event.EVENT_ATTRS:
                event['src_ip'] = '0.0.0.0'
            #Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['dst_ip'] is not None and 'dst_ip' in event.EVENT_ATTRS:
                if not re.match(ipv4_reg, event['dst_ip']) and not self.isIPV6(event['dst_ip']):
                    data = event['dst_ip']
                    logger.warning("Event's field dst_ip (%s) is not a valid IP.v4/IP.v6 address, set it to default ip 0.0.0.0" % data)
                    event['dst_ip'] = '0.0.0.0'
                    #event['userdata9'] = data
            elif 'dst_ip' in event.EVENT_ATTRS:
                event['dst_ip'] = '0.0.0.0'
            #Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['sensor'] is not None and not self.override_sensor:
                if not re.match(ipv4_reg, event['sensor'])  and not self.isIPV6(event['sensor']):
                    data = event['sensor']
                    logger.warning("Event's field sensor (%s) is not a valid IP.v4/IP.v6 address, set it to default local" % data)
                    event['sensor'] = self._getLocalIP()
                    #event['userdata7'] = data
            else:
                event['sensor'] = self._getLocalIP()

            # Check for device valid IP
            if event['device'] is not None:
                if not re.match(ipv4_reg, event['device'])  and not self.isIPV6(event['device']):
                    data = event['device']
                    logger.warning("Event's device field (%s) is not a valid IP.v4/IP.v6 address, set it to default local." % (data))
                    if event['sensor'] != None:
                        event['device'] = event['sensor']
                    else:
                        event['device'] = self._getLocalIP()
            else:
                if event['sensor'] != None:
                    event['device'] = event['sensor']
                else:
                    event['device'] = self._getLocalIP()

        #check port
        if event['dst_port'] is not None and 'dst_port' in event.EVENT_ATTRS:
            try:
                port = int(event['dst_port'])
                if port < 0 or port > 65535:
                    event['dst_port'] = None
                    logger.warning("Event's  field dst_port out of range: %d - (PID:%s SID:%s)" % (port, event['plugin_id'], event['plugin_sid']))
            except ValueError:
                #Do not show warnings on empty values
                if event['dst_port'] != '':
                    logger.warning("Event's  field dst_port is not a number: %s - (PID:%s SID:%s)" % (event['dst_port'], event['plugin_id'], event['plugin_sid']))
                event['dst_port'] = None

        if event['src_port'] is not None and 'src_port' in event.EVENT_ATTRS:
            try:
                port = int(event['src_port'])
                if port < 0 or port > 65535:
                    event['src_port'] = None
                    logger.warning("Event's  field src_port out of range: %d - (PID:%s SID:%s)" % (port, event['plugin_id'], event['plugin_sid']))
            except ValueError:
                #Do not show warnings on empty values
                if event['src_port'] != '':
                    logger.warning("Event's  field src_port is not a number: %s - (PID:%s SID:%s)" % (event['src_port'], event['plugin_id'], event['plugin_sid']))
                event['src_port'] = None
        if event['tzone'] is None:
            event['tzone'] = 0
        # the type of this event should always be 'detector'
        if event["type"] is None and 'type' in event.EVENT_ATTRS:
            event["type"] = 'detector'


        if not str(event).startswith("idm-event") and self.should_set_system_date(event["date"],event["fdate"]):
            logger.warning("Invalid plugin date... using system date...")
            event["date"] = time.strftime(default_date_format, time.localtime(time.time()))
            Utils.normalizeToUTCDate(event, self.systemtzone)
        return event


    def should_set_system_date(self,event_date, event_fdate):
        """Checks whether the system date should be setted"""
        if (event_date is None or event_fdate is None):
            return True
        if ( not self.patterndate.match(str(event_date)) or not self.patternISO_date.match(event_fdate)):
            return True
        return False


    def send_message(self, event):

        if self._event_os_cached(event):
            return

        if self._exclude_event(event):
            return

        # use default values for some empty attributes
        event = self._plugin_defaults(event)
        Output.event(event)
        Stats.new_event(event)
        return
        # check for consolidation
#        if self.conn is not None:
#            try:
#                self.conn.send(str(event))
#            except:
#                id = self._plugin.get("config", "plugin_id")
#                c = ServerConnPro(self._conf, id)
#                self.conn = c.connect(0, 10)
#                try:
#                    self.conn.send(str(event))
#                except:
#                    return
#
#            logger.info(str(event).rstrip())
#
#        elif not self.consolidation.insert(event):
#            Output.event(event)
#
#        Stats.new_event(event)


    def stop(self):
        #self.consolidation.clear()
        pass

    def process(self):
        """Process method placeholder.

        NOTE: Must be overriden in child classes.
        """
        pass


    def run(self):
        self.process()



class ParserSocket(Detector):

    def process(self):
        self.process()



class ParserDatabase(Detector):

    def process(self):
        self.process()



class ParserWMI(Detector):

    def process(self):
        self.process()



# vim:ts=4 sts=4 tw=79 expandtab:
