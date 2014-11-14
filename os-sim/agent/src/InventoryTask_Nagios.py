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

import time
import re
import socket
from xml.dom import minidom, Node

from InventoryTask import InventoryTask
from Event import HostInfoEvent
from Logger import Logger

#
# GLOBAL VARIABLES
#
logger = Logger.logger

class NAGIOS_TASK(InventoryTask):
    def __init__(self, task_name, task_params, task_period, task_reliability, task_enable, task_type,task_type_name, fmkip, fmkport):
        '''
        Constructor
        '''
        self._running = False
        self._fmkSocket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self._framework_ip = '192.168.2.18'#fmkip
        self._framework_port = fmkport
        #self._fmkSocket.connect((self._framework_ip, int(self._framework_port)))
        InventoryTask.__init__(self, task_name, task_params, task_period, task_reliability, task_enable, task_type,task_type_name)
        
    def getNagiosInventory(self):
        data = []
        try:
            logger.info("Connecting to fmkd %s:%s" % (self._framework_ip, int(self._framework_port)))
            self._fmkSocket.connect((self._framework_ip, int(self._framework_port)))
            self._fmkSocket.send("control action=\"getNagiosInventory\"\n")
            continue_reading = True
            data = ''
            while continue_reading:
                char = self._fmkSocket.recv(1)
                data += char
                if char == '\n':
                    continue_reading = False
            logger.info("Connected..")
            self._fmkSocket.close()
        except:
            logger.warning("Error retrieving ocs inventory data..")
        return data
    def getText(self, nodelist):
        rc = ""
        for node in nodelist:
            if node.nodeType == node.TEXT_NODE:
                rc = rc + node.data
        return rc
    def doJob(self):
        self._running = True
        logger.info("Nagios Process")
        data = self.getNagiosInventory()
        '''
        control action="getNagiosInventory" nagiosinventory="
        <nagiosdiscovery>
            <host>
                <ip>127.0.0.1</ip>
                <hostname>localhost</hostname>
                <host_state>0</host_state>
                <services>
                    <service>
                        <name>Current Load</name>
                        <state>2</state>
                    </service>
                    <service>
                        <name>Current Users</name>
                        <state>0</state>
                    </service>
                    <service>
                        <name>Disk Space</name>
                        <state>0</state>
                    </service>
                    <service>
                        <name>HTTP</name>
                        <state>0</state>
                    </service>
                    <service>
                        <name>SSH</name>
                        <state>0</state>
                    </service>
                    <service>
                        <name>Total Processes</name>
                        <state>0</state>
                    </service>
                </services>
            </host>
        </nagiosdiscovery>"
        '''
        if not data or data == '':
            return
        xml = re.findall("nagiosinventory=\"([^\"]+)\"", data)
        if len(xml) > 0:
            try:
                dom = minidom.parseString(xml[0])
            except Exception, e:
                logger.warning("Nagios: invalid data:%s,%s" % (xml[0], str(e)))
            else:
                for host in  dom.getElementsByTagName('host'):
                    hostData = HostInfoEvent()
                    hostData['ip'] = 'unknown'
                    hostData['hostname'] = 'unknown'
                    
                    tmp = host.getElementsByTagName('ip')
                    if tmp and len(tmp) == 1:
                        tmp = tmp[0]
                        hostData['ip'] = self.getText(tmp.childNodes)
                    tmp = host.getElementsByTagName('hostname')
                    if tmp and len(tmp) == 1:
                        tmp = tmp[0]
                        hostData['hostname'] = self.getText(tmp.childNodes)
                    tmp = host.getElementsByTagName('host_state')
                    if tmp and len(tmp) == 1:
                        tmp = tmp[0]
                        hostData['state'] = self.getText(tmp.childNodes)
                    str_ports = ''
                    first = True
                    for service in  host.getElementsByTagName('service'):
                        
                        service_state = 'unknown'
                        service_name = 'unknown'
                        tmp = service.getElementsByTagName('name')
                        if tmp and len(tmp) == 1:
                            service_name = self.getText(tmp[0].childNodes)
                        tmp = service.getElementsByTagName('state')
                        if tmp and len(tmp) == 1:
                            service_state = self.getText(tmp[0].childNodes)
                        if not first:
                            str_ports += ','
                        str_ports += '%s|%s|%s|%s' % ('unknown', 'unknown', service_name, service_state)
                        first = False
                    hostData['service'] = str_ports
                    self.send_message(hostData)
        self._running = False
        logger.info("End nagios inventory job")
    def get_running(self):
        return self._running
