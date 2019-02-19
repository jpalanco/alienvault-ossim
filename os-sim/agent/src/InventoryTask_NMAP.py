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
import nmap
import xml.dom.minidom
import re

from InventoryTask import InventoryTask
from Event import HostInfoEvent
from Logger import Logger
#
# GLOBAL VARIABLES
#
logger = Logger.logger


class NMAP_TASK(InventoryTask):
    """
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
    """

    def __init__(self, task_name, task_params, task_period, task_reliability, task_enable, task_type, task_type_name):
        """
        Constructor
        """
        self._running = False
        self._nm = nmap.PortScanner()
        InventoryTask.__init__(self, task_name, task_params, task_period, task_reliability, task_enable,
                               task_type, task_type_name)

    def runQuery(self):
        try:
            host_arg, args_arg = self._task_params.split('#', 1)
            if "!" in host_arg:
                # Prepare exclude parameter with IPs to exclude
                excludes = ','.join([host.lstrip('!') for host in host_arg.split(' ') if host.startswith('!')])
                host_arg = ' '.join([host for host in host_arg.split(' ') if not host.startswith('!')])
                args_arg += ' --exclude={}'.format(excludes)

            logger.debug("NMAP scan: %s %s" % (str(host_arg), str(args_arg)))
            self._nm.scan(hosts=host_arg, arguments=args_arg)
            xmldata = self._nm.get_nmap_last_output()
        except Exception, e:
            logger.error("ERRROR :%s" % str(e))
            return

        dom = xml.dom.minidom.parseString(xmldata)
        for nmaphost in dom.getElementsByTagName('host'):
            host = HostInfoEvent()
            for status in nmaphost.getElementsByTagName('status'):
                # States: (up|down|unknown|skipped)
                host['state'] = status.getAttributeNode('state').value
            for address in nmaphost.getElementsByTagName('address'):
                addrtype = address.getAttributeNode('addrtype').value
                if addrtype == 'ipv4' or addrtype == 'ipv6':
                    host['ip'] = address.getAttributeNode('addr').value
                if address.getAttributeNode('addrtype').value == 'mac':
                    host['mac'] = address.getAttributeNode('addr').value
            hostnames = nmaphost.getElementsByTagName('hostnames')
            if hostnames:
                for hn in nmaphost.getElementsByTagName('hostname'):
                    host['hostname'] = hn.getAttributeNode('name').value

            str_ports = ''
            software = set()
            operative_system = set()
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
                    str_services = ''
                    product = ''
                    version = ''
                    extrainfo = ''
                    services = []
                    str_cpe = ''
                    for ps in portservices:
                        try:
                            product = ps.getAttributeNode('product').value
                        except AttributeError:
                            pass
                        try:
                            version = ps.getAttributeNode('version').value
                        except AttributeError:
                            pass
                        try:
                            extrainfo = ps.getAttributeNode('extrainfo').value
                        except AttributeError:
                            pass

                        service_name = ps.getAttributeNode('name').value
                        if service_name == '' or service_name is None:
                            continue
                            
                        try:
                            tunnel = ps.getAttributeNode('tunnel').value
                            if tunnel == 'ssl' and service_name == 'http':
                                service_name = 'https'
                        except AttributeError:
                            pass
                            
                        services.append(service_name)
                            
                        # create banner
                        banner = []
                        if product:
                            banner.append(product)
                        if version:
                            banner.append(version)
                        if extrainfo:
                            banner.append(extrainfo)

                        for cpe in ps.getElementsByTagName('cpe'):
                            if not banner:
                                banner.append(self.get_pretty_cpe(cpe))

                            ocpe = cpe.firstChild.nodeValue  # save the original cpe

                            cpe.firstChild.nodeValue += '|'
                            cpe.firstChild.nodeValue += (' '.join(banner)).lstrip(' ')

                            if cpe.firstChild.nodeValue.startswith('cpe:/o:'):
                                operative_system.add(cpe.firstChild.nodeValue)
                            elif cpe.firstChild.nodeValue.startswith('cpe:/h:'):
                                hardware.add(cpe.firstChild.nodeValue)
                            else:
                                if str_cpe:
                                    str_cpe += ','
                                str_cpe += ocpe

                                software.add(cpe.firstChild.nodeValue)

                        if not str_cpe and banner:
                            str_cpe = (' '.join(banner)).lstrip(' ')

                    if len(services) > 0:
                        str_services = ','.join(["%s" % s for s in services])
                    if str_ports:
                        str_ports += ','
                    if str_cpe:
                        str_ports += '%s|%s|%s|%s' % (protocol, portnumber, str_services, str_cpe)
                    else:
                        str_ports += '%s|%s|%s|unknown' % (protocol, portnumber, str_services)

            os = nmaphost.getElementsByTagName('os')
            if os:
                str_os = ''
                last_accuracy = 0
                for os in nmaphost.getElementsByTagName('osclass'):
                    osfamily = ''
                    try:
                        osfamily = os.getAttributeNode('osfamily').value
                    except:
                        pass

                    if osfamily not in ['embedded', '', 'unknown']:
                        accuracy = 0
                        try:
                            accuracy = os.getAttributeNode('accuracy').value
                        except:
                            pass
                        if accuracy > last_accuracy:
                            last_accuracy = accuracy
                            if os.getAttributeNode('osfamily') and os.getAttributeNode('osgen'):
                                str_os = '%s %s' % (osfamily, os.getAttributeNode('osgen').value)
                            operative_system_new = set()
                            hardware_new = set()
                            for cpe in os.getElementsByTagName('cpe'):
                                banner = self.get_pretty_cpe(cpe)
                                if cpe.firstChild.nodeValue.startswith('cpe:/o:'):
                                    operative_system_new.add(cpe.firstChild.nodeValue + '|' + banner)
                                elif cpe.firstChild.nodeValue.startswith('cpe:/h:'):
                                    hardware_new.add(cpe.firstChild.nodeValue + '|' + banner)
                            if len(operative_system_new) > 0 or len(hardware_new) > 0:
                                operative_system = operative_system_new
                                hardware = hardware_new

                if str_os != '':
                    host['os'] = str_os

            str_software = ''
            software.update(operative_system)
            software.update(hardware)
            for s in software:
                if str_software == '':
                    str_software += '%s' % s
                else:
                    str_software += ',%s' % s

            host['service'] = str_ports
            host['software'] = str_software

            host['inventory_source'] = 5  # SELECT id FROM host_source_reference WHERE name = 'NMAP';
            self.send_message(host)

    @staticmethod
    def get_pretty_cpe(cpe):
        data_source = re.sub(r"^cpe:/.:", '', re.sub(r":+", ':', cpe.firstChild.nodeValue))
        return ' '.join([s[0].upper() + s[1:] for s in re.sub(':', ' ', data_source).split(' ')])

    def doJob(self):
        self._running = True 
        logger.info("Starting NMAP")
        self.runQuery()
        logger.info("NMAP collector ending..")
        self._running = False

    def get_running(self):
        return self._running
