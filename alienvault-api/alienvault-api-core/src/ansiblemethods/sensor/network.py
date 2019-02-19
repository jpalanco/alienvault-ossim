# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2013 AlienVault
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

import traceback
import ipaddress
from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.helper import parse_av_config_response
from xml.dom.minidom import parseString

ansible = Ansible()


def get_network_stats(sensor_ip):
    """Retrieves network stats
    :param sensor_ip (String dotted)
    """
    return ansible.run_module(host_list=[sensor_ip],
                              module="av_netstats",
                              args="")


def get_sensor_interfaces(sensor_ip):
    """
    @param sensor_ip: The system IP where you want to get the [sensor]/interfaces from ossim_setup.conf
    @return  A tuble (sucess|error, data | msgerror)
    """
    result = False
    data = None
    try:
        response = ansible.run_module(host_list=[sensor_ip],
                                      module="av_config",
                                      args="sensor_interfaces=True op=get",
                                      use_sudo=True)

        return parse_av_config_response(response, sensor_ip)
    except Exception, e:
        trace = traceback.format_exc()
        data = "Ansible Error: Can't get [sensor]/interfaces from ossim_setup.conf: %s \n trace: %s" % (
            str(e), trace)
        result = False
    return result, data


def set_sensor_interfaces(sensor_ip, interfaces):
    """
    @param sensor_ip: The system IP where you want to get the [sensor]/interfaces from ossim_setup.conf
    @param Comma separate list of interfaces to activate. Must exists in the machine
    @return  A tuble (sucess|error, data | msgerror)
    """
    result = False
    try:
        response = ansible.run_module(host_list=[sensor_ip],
                                      module="av_config",
                                      args="sensor_interfaces=%s op=set" % interfaces)

        return parse_av_config_response(response, sensor_ip)
    except Exception, e:
        trace = traceback.format_exc()
        data = "Ansible Error: Can't get [sensor]/interfaces from ossim_setup.conf: %s \n trace: %s" % (
            str(e), trace)

        result = False
    return result, data


def get_sensor_iface_traffic(sensor_ip, sensor_iface, timeout=2):
    """
    @param sensor_ip: The sensor IP
    @param sensor_iface: Interface machine to check
    @param timeout: Timeout to tshark. Default 2 seconds
    """
    try:
        ansible_iface = "ansible_%s" % sensor_iface
        response = ansible.run_module(host_list=[sensor_ip],
                                      module='av_setup',
                                      args="filter=" + ansible_iface)
        if sensor_ip in response['dark']:
            return (False, "check_iface_traffic : " + response['dark'][sensor_ip]['msg'])

        # Get the MAC to construct the filter
        mac = response['contacted'][sensor_ip]['ansible_facts'][ansible_iface]['macaddress']

        # Construct the params
        params = "-i %s -T psml -f \"not broadcast and not multicast and not ether dst %s and not ether src %s \" -a duration:%d" % \
                 (sensor_iface, mac, mac, timeout)
        response = ansible.run_module(host_list=[sensor_ip],
                                      module="command",
                                      args="/usr/bin/tshark " + params)
        # Check result
        if sensor_ip in response['dark']:
            return (False, "check_iface_traffic : " + response['dark'][sensor_ip]['msg'])

        # Parse the response
        packets = parseString(response['contacted'][sensor_ip]['stdout'])
        if packets.documentElement.tagName != "psml":
            return (False, "check_iface_traffic : Bad XML response\n" + packets.toprettyxml())

        # Check if we have packets inside
        for children in packets.documentElement.childNodes:
            if children.nodeType == children.ELEMENT_NODE and children.tagName == "packet":
                return (True, {'has_traffic': True})
        return (True, {'has_traffic': False})

    except Exception as e:
        return (False, "Ansible Error: " + str(e) + "\n" + traceback.format_exc())


def get_sensor_networks(sensor_ip):
    """Return the [sensor]/network field from the ossim_setup.conf"""
    rc = False
    networks = []
    response = ansible.run_module(host_list=[sensor_ip],
                                  module="av_config",
                                  args="sensor_networks=True op=get")
    # Verify the response
    if sensor_ip in response['dark']:
        networks = response['dark'][sensor_ip].get('msg', "Unknown Ansible error")
    elif response['contacted'][sensor_ip].get('failed', False):
        networks = response['contacted'][sensor_ip].get('msg', "Unknown error from Ansible Module av_config")
    else:
        # Ok, we have the networks
        networks = response['contacted'][sensor_ip]['data']['sensor_networks'].split(',')
        rc = True
    return (rc, networks)


def set_sensor_networks(sensor_ip, nets=[]):
    """Set the [sensor]/network field in ossim_setup.conf in machine sensor_ip
    """
    rc = False
    msg = ""
    # Verify each network
    try:
        for net in nets:
            n = ipaddress.ip_network(unicode(net))
    except ValueError:
        return (False, "Bad network => %s" % net)
    # Join
    args = ",".join(nets)
    response = ansible.run_module(host_list=[sensor_ip],
                                  module="av_config",
                                  args="sensor_networks=%s op=set" % args)
    if sensor_ip in response['dark']:
        msg = response['dark'][sensor_ip].get('msg', "Unknown Ansible error")
    elif response['contacted'][sensor_ip].get('failed', False):
        msg = response['contacted'][sensor_ip].get('msg', "Unknown error from Ansible Module av_config")
    else:
        msg = response['contacted'][sensor_ip].get('data')
        rc = True
    return (rc, msg)
