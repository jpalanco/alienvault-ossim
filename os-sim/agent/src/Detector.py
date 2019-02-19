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
import commands
import re
import socket
import threading
import time

from pytz import all_timezones
#
# LOCAL IMPORTS
#
import Config
from Logger import Logger, Lazyformat
from Output import Output
from Stats import Stats
from Threshold import EventConsolidation


import Utils
#
# GLOBAL VARIABLES
#
logger = Logger.logger


DEFAULT_PLUGIN_SID = 20000000


class Detector(threading.Thread):

    def __init__(self, conf, plugin, conn):

        self._conf = conf
        self._plugin = plugin
        self.os_hash = {}
        self.conn = conn
        self.consolidation = EventConsolidation(self._conf)
        self.systemtzone = None
        self.__plugin_id = self._plugin.get("DEFAULT", "plugin_id")
        self.__plugin_name = self._plugin.get("config", "name")

        if "tzone" in self._plugin.hitems("DEFAULT"):
            self._timezone = self._plugin.get("DEFAULT", "tzone")
        else:
            self._timezone = self._conf.get("plugin-defaults", "tzone")

        self._sensorID = None
        if "sensor_id" in self._conf.hitems("plugin-defaults"):
            self._sensorID = self._conf.get("plugin-defaults", "sensor_id")

        self.loginfo(Lazyformat("Starting plugin with the following tzone: {}", self._timezone))
        threading.Thread.__init__(self)
        self._agenttimezone = self._conf.get("plugin-defaults", "tzone")
        self._EventTimeZone = None
        if self._conf.has_option("plugin-defaults", "override_sensor"):
            self.override_sensor = self._conf.getboolean("plugin-defaults", "override_sensor")
        else:
            self.override_sensor = False

        # 2011-02-01 17:00:16
        self.patterndate = re.compile('(\d{10})')
        self.patternISO_date = re.compile(
            '(?P<year>\d+)[\s-](?P<month>\d+)[\s-](?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)')
        self.set_system_tzone()
        self.checkTimeZone()

    def __isDigit(self, value):
        """Checks if value is a digit
        @returns true if value is a number, otherwise false
        """

        rt = True
        try:
            int(value)
        except:
            rt = False
        return rt

    def set_system_tzone(self):
        """Sets the system timezone by reading the timezone """
        try:
            # read local timezone information.
            f = open('/etc/timezone', 'r')
            used_tzone = f.readline().rstrip()
            f.close()
            if used_tzone not in all_timezones:
                self.loginfo("Failed to get valid timezone. Falling back to the default one: GMT")
                used_tzone = 'GMT'
        except Exception, e:
            used_tzone = 'GMT'
            self.loginfo("Failed to get the timezone. Falling back to the default one: GMT")

        self.systemtzone = used_tzone

    def checkTimeZone(self):
        if self._timezone in all_timezones:
            used_tzone = self._timezone
            self.logdebug(Lazyformat("Using custom plugin tzone data: {}", used_tzone))
        elif self._agenttimezone in all_timezones:
            used_tzone = self._agenttimezone
            self.loginfo(Lazyformat(
                "Failed to find the {} timezone in database. Falling back to agent timezone: {}",
                self._timezone,
                used_tzone
            ))
        else:
            self.set_system_tzone()
            used_tzone = self.systemtzone

        self._EventTimeZone = used_tzone

    def _event_os_cached(self, event):
        return False

    def _exclude_event(self, event):

        if self._plugin.has_option("config", "exclude_sids"):
            exclude_sids = self._plugin.get("config", "exclude_sids")
            if event["plugin_sid"] in Config.split_sids(exclude_sids):
                self.logdebug(Lazyformat(
                    "Excluding event with plugin_id={} and plugin_sid={}",
                    event["plugin_id"],
                    event["plugin_sid"]
                ))
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
            self.loginfo("override_sensor detected")

        if self._conf.has_section("plugin-defaults"):
            mylocalip = self._conf.get("plugin-defaults", "sensor")
            return mylocalip

        hostname, aliaslist, ipaddrlist = socket.gethostbyname_ex(socket.gethostname())
        for ip in ipaddrlist:
            if not ip.startswith("127"):
                return ip
        # In this case we try to parse the output of ip a
        lines = commands.getoutput("ip a | grep inet | grep -v inet6 | awk '{print $2}'| grep -v \"127.0.0.1\" | awk -F '/' '{print $1}'").split("\n")
        if len(lines) > 0:
            self.loginfo(Lazyformat("Using sensor ip: {}", lines[0]))
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
        default_date_format = '%Y-%m-%d %H:%M:%S'
        default_ip = '0.0.0.0'

        # get default values from config
        if self._sensorID is not None:
            event['sensor_id'] = self._sensorID

        if 'plugin_sid' in event.EVENT_ATTRS:
            if event['plugin_sid'] == "0":
                event['plugin_sid'] = DEFAULT_PLUGIN_SID

        if not self.__isDigit(event['plugin_sid']) and not str(event).startswith("idm-event"):
            self.logwarn(Lazyformat("Event discarded [{!s}] Plugin sid not a number...", event))
            return ""

        if not self.__isDigit(event['plugin_id']) and not str(event).startswith("idm-event"):
            self.logwarn(Lazyformat("Event discarded [{!s}] Plugin id not a number... ", event))
            return ""

        # 1) date
        if self._conf.has_section("plugin-defaults"):
            default_date_format = self._conf.get("plugin-defaults",
                                                 "date_format")
            if event["date"] is None and default_date_format and 'date' in event.EVENT_ATTRS:
                event["date"] = time.strftime(default_date_format,
                                              time.localtime(time.time()))

        # 2) sensor
            default_sensor = self._conf.get("plugin-defaults", "sensor")
            if event["sensor"] is None and default_sensor and \
               'sensor' in event.EVENT_ATTRS and not self.override_sensor:
                event["sensor"] = default_sensor

        # 3) interface
            default_iface = self._conf.get("plugin-defaults", "interface")
            if event["interface"] is None and default_iface and 'interface' in event.EVENT_ATTRS:
                event["interface"] = default_iface

        # 4) source ip
            if event["src_ip"] is None and 'src_ip' in event.EVENT_ATTRS:
                event["src_ip"] = event["sensor"]

        # 5) Time zone
            if 'tzone' in event.EVENT_ATTRS:
                Utils.normalizeToUTCDate(event, self._EventTimeZone)

        # 6) sensor,source ip and dest != localhost
            if event["sensor"] in ('127.0.0.1', '127.0.1.1', '::1') and not self.override_sensor:
                event["sensor"] = default_sensor

            if event["dst_ip"] in ('127.0.0.1', '127.0.1.1', '::1') and 'dst_ip' in event.EVENT_ATTRS:
                event["dst_ip"] = event['device']

            if event["src_ip"] in ('127.0.0.1', '127.0.1.1', '::1', 'localhost') and 'src_ip' in event.EVENT_ATTRS:
                event["src_ip"] = event['device']

            # Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['src_ip'] is not None and 'src_ip' in event.EVENT_ATTRS:
                # check for ipv6
                if not re.match(ipv4_reg, event['src_ip']) and not self.isIPV6(event['src_ip']):
                    data = event['src_ip']
                    event['src_ip'] = default_ip
                    self.logwarn(Lazyformat(
                        "Event's field src_ip {} is not a valid IP.v4/IP.v6 address, falling back to default: 0.0.0.0",
                        data
                    ))

            elif 'src_ip' in event.EVENT_ATTRS:
                event['src_ip'] = default_ip

            # Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['dst_ip'] is not None and 'dst_ip' in event.EVENT_ATTRS:
                if not re.match(ipv4_reg, event['dst_ip']) and not self.isIPV6(event['dst_ip']):
                    data = event['dst_ip']
                    self.logwarn(Lazyformat(
                        "Event's field dst_ip {} is not a valid IP.v4/IP.v6 address, falling back to default: 0.0.0.0",
                        data
                    ))
                    event['dst_ip'] = default_ip

            elif 'dst_ip' in event.EVENT_ATTRS:
                event['dst_ip'] = default_ip

            # Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['sensor'] is not None and not self.override_sensor:
                if not re.match(ipv4_reg, event['sensor']) and not self.isIPV6(event['sensor']):
                    data = event['sensor']
                    self.logwarn(Lazyformat(
                        "Event's field sensor {} is not a valid IP.v4/IP.v6 address, falling back to default local",
                        data
                    ))
                    event['sensor'] = self._getLocalIP()
            else:
                event['sensor'] = self._getLocalIP()

            # Check for device valid IP
            if event['device'] is not None:
                if not re.match(ipv4_reg, event['device']) and not self.isIPV6(event['device']):
                    data = event['device']
                    self.logwarn(Lazyformat(
                        "Event's device field {} is not a valid IP.v4/IP.v6 address, falling back to default local.",
                        data)
                    )
                    if event['sensor'] is not None:
                        event['device'] = event['sensor']
                    else:
                        event['device'] = self._getLocalIP()
            else:
                if event['sensor'] is not None:
                    event['device'] = event['sensor']
                else:
                    event['device'] = self._getLocalIP()

        # check port
        if event['dst_port'] is not None and 'dst_port' in event.EVENT_ATTRS:
            try:
                port = int(event['dst_port'])
                if port < 0 or port > 65535:
                    event['dst_port'] = None
                    self.logwarn(Lazyformat(
                        "Event's field dst_port is out of range: {} (PID:{} SID:{})",
                        port,
                        event["plugin_id"],
                        event["plugin_sid"]
                    ))
            except ValueError:
                # Do not show warnings on empty values
                if event['dst_port'] != '':
                    self.logwarn(Lazyformat(
                        "Event's field dst_port is not a number: {} (PID:{} SID:{})",
                        event['dst_port'],
                        event['plugin_id'],
                        event['plugin_sid']
                    ))
                event['dst_port'] = None

        if event['src_port'] is not None and 'src_port' in event.EVENT_ATTRS:
            try:
                port = int(event['src_port'])
                if port < 0 or port > 65535:
                    event['src_port'] = None
                    self.logwarn(Lazyformat(
                        "Event's field src_port is out of range: {} (PID:{} SID:{})",
                        port,
                        event['plugin_id'],
                        event['plugin_sid']
                    ))
            except ValueError:
                # Do not show warnings on empty values
                if event['src_port'] != '':
                    self.logwarn(Lazyformat(
                        "Event's field src_port is not a number: {} (PID:{} SID:{})",
                        event['src_port'],
                        event['plugin_id'],
                        event['plugin_sid']
                    ))
                event['src_port'] = None
        if event['tzone'] is None:
            event['tzone'] = 0
        # the type of this event should always be 'detector'
        if event["type"] is None and 'type' in event.EVENT_ATTRS:
            event["type"] = 'detector'

        if not str(event).startswith("idm-event") and self.should_set_system_date(event["date"], event["fdate"]):
            self.logwarn("Invalid plugin date... using system date...")
            event["date"] = time.strftime(default_date_format, time.localtime(time.time()))
            Utils.normalizeToUTCDate(event, self.systemtzone)
        return event

    def should_set_system_date(self, event_date, event_fdate):
        """Checks whether the system date should be set"""
        if event_date is None or event_fdate is None:
            return True

        if not self.patterndate.match(str(event_date)) or not self.patternISO_date.match(event_fdate):
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
#            self.loginfo(str(event).rstrip())
#
#        elif not self.consolidation.insert(event):
#            Output.event(event)
#
#        Stats.new_event(event)

    def stop(self):
        # self.consolidation.clear()
        pass

    def process(self):
        """Process method placeholder.

        NOTE: Must be overridden in child classes.
        """
        pass

    def run(self):
        self.process()

    def loginfo(self, message):
        logger.info(self.label(message))

    def logdebug(self, message):
        logger.debug(self.label(message))

    def logwarn(self, message):
        logger.warning(self.label(message))

    def logerror(self, message):
        logger.error(self.label(message))

    def label(self, message):
        return Lazyformat(
            "{}[{}] {}",
            self.__plugin_name,
            self.__plugin_id,
            message
        )


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
