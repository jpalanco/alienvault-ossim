#!/usr/bin/env python
# encoding: utf-8
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

import time
import xml.dom.minidom

#
# LOCAL IMPORTS
#
from Detector import Detector
from Event import Event
from Logger import Lazyformat
from pySDEE import SDEE


"""
Parser for Cisco SDEE

Cisco Network Prevention Systems (IPS)

Cisco Network Detection Systems (IPS)

Cisco Switch IDS

Cisco IOS routers with Inline Intrusion Prevention System (IPS) functions

Cisco IDS modules for routers

Cisco PIX Firewalls

Cisco Catalyst 6500 Series firewall services modules (FWSMs)

Cisco Management Center for Cisco security agents

CiscoWorks Monitoring Center for Security servers

"""



class ParserSDEE(Detector):
    def __init__(self, conf, plugin, conn, hostname=None, username=None, password=None):
        self._conf = conf
        self._plugin = plugin
        self.rules = []
        self.conn = conn
        self.__hostname = hostname
        self.__username = username
        self.__password = password
        if hostname:
            self.sIdFile = '/etc/ossim/agent/sdee_sid_%s.data' % hostname
        else:
            self.sIdFile = '/etc/ossim/agent/sdee_sid.data'
        Detector.__init__(self, conf, plugin, conn)

    def parse(self, data):
        doc = xml.dom.minidom.parseString(data)
        alertlist = doc.getElementsByTagName('sd:evIdsAlert')

        alert_obj_list = []

        for alert in alertlist:
            sig = alert.getElementsByTagName('sd:signature')[0]
            self.logdebug("SDEE Parsing Alert")
            #Plugin sid
            sid = sig.attributes['id'].nodeValue

            desc = sig.attributes['description'].nodeValue

            participants = alert.getElementsByTagName('sd:participants')[0]

            if not participants.hasChildNodes():
                self.logdebug("Ignoring SDEE alert. Possible TCP/UDP/ARP DoS")
                continue

            attacker = participants.getElementsByTagName('sd:attacker')[0]
            #Src addr
            attAddr = attacker.getElementsByTagName('sd:addr')[0].firstChild.data

            #Src port
            try:
                attPort = attacker.getElementsByTagName('sd:port')[0].firstChild.data

            except:
                attPort = 0

            for dst in alert.getElementsByTagName('sd:target'):
                data1 = self.sanitize(alert.toxml())
                self.logdebug(Lazyformat("SDEE: {}", data1))
                #Dst Address
                dstAddr = dst.getElementsByTagName('sd:addr')[0].firstChild.data

                #Dst Port
                try:
                    dstPort = dst.getElementsByTagName('sd:port')[0].firstChild.data

                except:
                    dstPort = 0

                self.logdebug(Lazyformat("{}:{}, {}:{}, {}:{}", sid, desc, attAddr, attPort, dstAddr, dstPort))
                self.generate(sid, attAddr, attPort, dstAddr, dstPort, data1)


    def sanitize(self, data):
        data = data.replace("\n","").replace("<"," ").replace(">"," ").replace("/","").replace('"', "")
        return data


    def generate(self, sid, attAddr, attPort, dstAddr, dstPort, data):
        event = Event()
        event["plugin_id"] = self.plugin_id
        event["plugin_sid"] = sid
        event["log"] = data
        if self._plugin.has_option("config", "sensor"):
            event["sensor"]= self._plugin.get("config","sensor")
        else:
            event["sensor"] = self.host
        event["src_ip"] = attAddr
        event["src_port"] = attPort
        event["dst_ip"] = dstAddr
        event["dst_port"] = dstPort
        if event is not None:
            self.send_message(event)

        #FIXME: Process timestamp and escaped log data


    def process(self):
        self.loginfo("Started SDEE Collector")
        if self.__hostname:
            self.host = self.__hostname
        else:
            self.host = self._plugin.get("config", "source_ip")
        if self.__username:
            self.username = self.__username
        else:
            self.username = self._plugin.get("config", "user")
        if self.__password:
            self.password = self.__password
        else:
            self.password = self._plugin.get("config", "password")
        self.sleepField = self._plugin.get("config", "sleep")
        self.plugin_id = self._plugin.get("DEFAULT", "plugin_id")

        sdee = SDEE(user=self.username,password=self.password,host=self.host,method='https', force='yes')
        try:
            sdee.open()
            self.loginfo(Lazyformat("SDEE subscriberId: {}", sdee._subscriptionid))
            f = open(self.sIdFile, 'w')
            f.write("%s\n" % sdee._subscriptionid)
            f.close()

        except:
            self.logerror(Lazyformat("Failed to open SDEE connection to device {}", self.host))
            self.loginfo("SDEE: Trying to close last session")
            try:
                f = open(self.sIdFile, 'r')
            except IOError:
                self.logerror("SDEE: Cannot read subscriber ID")
                return
            subs = f.readline()

            try:
                sdee = SDEE(user=self.username,password=self.password,host=self.host,method='https', force='yes')
                sdee._subscriptionid = subs
                sdee.close()

            except:
                self.logerror("SDEE: losing last session Failed")
                return

            try:
                sdee = SDEE(user=self.username,password=self.password,host=self.host,method='https', force='yes')
                sdee.open()
                self.loginfo(Lazyformat("SDEE subscriberId: {}", sdee._subscriptionid))
                f = open(self.sIdFile, 'w')
                f.write("%s\n" % sdee._subscriptionid)
                f.close()

            except:
                self.logerror("SDEE Failed")
                return

        while 1:
            sdee.get()
            self.loginfo("Requesting SDEE Data...")
            data = sdee.data()
            self.logdebug(data)
            self.parse(data)
            time.sleep(int(self.sleepField))

