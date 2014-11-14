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
import nmap
import xml.dom.minidom

from InventoryTask import InventoryTask
from Event import HostInfoEvent
from Logger import Logger
#
# GLOBAL VARIABLES
#
logger = Logger.logger


class NMAP_TASK(InventoryTask):
    '''
    NMAP-OPTIONS
    -O Sistema operativo
    -sV Deteccion de version
    --allports :No excluir ningún puerto de la deteccion de versiones
    -sS (sondeo TCP SYN)
    -PE; -PP; -PM (Tipos de ping ICMP)
    -PA [lista de puertos] (Ping TCP ACK)
    -PS [lista de puertos] (Ping TCP SYN)
    -P0 (No realizar ping)
    -sP (Sondeo ping)
    -sL (Sondeo de lista)
    '''


    def __init__(self, task_name, task_params, task_period, task_reliability, task_enable, task_type,task_type_name):
        '''
        Constructor
        '''
        self._running = False
        self._nm = nmap.PortScanner()
        InventoryTask.__init__(self, task_name, task_params, task_period, task_reliability, task_enable, task_type,task_type_name)


    def runQuery(self):
        #print "Query: hosts: %s - args: %s" % (query.get_hosts(),query.get_args())
        try:
            host_arg, args_arg = self._task_params.split ('#', 1)
            self._nm.scan(hosts=host_arg, arguments=args_arg)
            xmldata = self._nm.get_nmap_last_output()
        except Exception, e:
            logger.error("ERRROR :%s" % str(e))
            return
        dom = xml.dom.minidom.parseString(xmldata)
        for nmaphost in  dom.getElementsByTagName('host'):
            host = HostInfoEvent()
            for status in nmaphost.getElementsByTagName('status'):
                # States: (up|down|unknown|skipped)
                host['state'] = status.getAttributeNode('state').value
            for address in nmaphost.getElementsByTagName('address'):
                if address.getAttributeNode('addrtype').value == 'ipv4' or address.getAttributeNode('addrtype').value == 'ipv6':
                    host['ip'] = address.getAttributeNode('addr').value
                if address.getAttributeNode('addrtype').value == 'mac':
                    host['mac'] = address.getAttributeNode('addr').value
            hostnames = nmaphost.getElementsByTagName('hostnames')
            if hostnames:
                for hn in nmaphost.getElementsByTagName('hostname'):
                    host['hostname'] = hn.getAttributeNode('name').value

            str_ports = ''
            software = set()
            hardware = set()

            ports = nmaphost.getElementsByTagName('ports')
            if ports:
                for port in nmaphost.getElementsByTagName('port'):
                    protocol = port.getAttributeNode('protocol').value
                    portnumber = port.getAttributeNode('portid').value
                    portstates = port.getElementsByTagName('state')
                    state = 'unknown'
                    if portstates:
                        if portstates[0].getAttributeNode('state'):
                            state = portstates[0].getAttributeNode('state').value
                    portservices = port.getElementsByTagName('service')
                    if state != "open":
                        continue
                    str_services = 'unknown'
                    services = []
                    str_cpe = ''
                    for ps in portservices:
                        service_name = ps.getAttributeNode('name').value
                        if service_name == '' or service_name is None:
                            service_name = 'unknown'
                        services.append(service_name)

                        for cpe in ps.getElementsByTagName('cpe'):
                            if str_cpe:
                                str_cpe += ','
                            str_cpe += cpe.firstChild.nodeValue
                            software.add (cpe.firstChild.nodeValue)

                    if len(services) > 0:
                        str_services = ','.join(["%s" % s for s in services])
                    if str_ports:
                        str_ports += ','
                    if str_cpe:
                        str_ports += '%s|%s|%s|%s' % (protocol, portnumber, str_services, str_cpe)
                    else:
                        str_ports += '%s|%s|%s' % (protocol, portnumber,str_services)

            os = nmaphost.getElementsByTagName('os')
            if os:
                str_os = ''
                last_accuracy = 0
                for os in nmaphost.getElementsByTagName('osclass'):
                    # por aqui dentro se saca el cpe hardware
                    accuracy = 0
                    try:
                        accuracy = os.getAttributeNode('accuracy').value
                    except:
                        pass
                    if accuracy > last_accuracy:
                        last_accuracy = accuracy
                        if os.getAttributeNode('osfamily') and os.getAttributeNode('osgen'):
                            str_os = '%s|%s' % (os.getAttributeNode('osfamily').value, os.getAttributeNode('osgen').value)
                        hardware.clear()
                        for cpe in os.getElementsByTagName('cpe'):
                            hardware.add(cpe.firstChild.nodeValue)

                if str_os != '':
                    host['os'] = str_os

            str_software = ''
            software.update(hardware)
            for s in software:
                if str_software == '':
                    str_software += '%s|' % (s)
                else:
                    str_software += ',%s|' % (s)

            host['service'] = str_ports
            host['software'] = str_software

            host['inventory_source'] = 5; # SELECT id FROM host_source_reference WHERE name = 'NMAP';
            self.send_message(host)

    def doJob(self):
        self._running = True 
        logger.info("Starting NMAP")
        self.runQuery()
        logger.info("NMAP collector ending..")
        self._running = False
    def get_running(self):
        self._running

