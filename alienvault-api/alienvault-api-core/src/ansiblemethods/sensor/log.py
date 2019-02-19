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

import api_log
from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.helper import parse_av_config_response
from ansiblemethods.helper import ansible_is_valid_response

ansible = Ansible()

def get_devices_logging(system_ip):
    """Returns the list of devices logging to alienvault-sensor

    Args:
        system_ip (str): System IP

    Returns:
        On success, it returns a hash table with the correct values, otherwise it returns
        an empty dict

    """
    # Added #10576
    device_hash = {}
    try:

        command="""find /var/log/alienvault/devices/* -type d -exec basename {} \;"""
        response = ansible.run_module(host_list=[system_ip], module="shell", args=command)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if success:
            response = response['contacted'][system_ip]['stdout'].split('\n')
            for device in response:
                device_hash[device] = ["/var/log/alienvault/devices/%s/%s.log" % (device, device)]
    except Exception, e:
        api_log.error("get_hosts_in_syslog error: %s, %s" % (str(e), traceback.format_exc()))
    return device_hash


def get_network_devices_for_sensor(sensor_ip):
    """Returns the list of devices logging to alienvault-sensor

    Args:
        sensor_ip (str): Sensor IP

    Returns:
        On success, it returns a hash table with device_id:device_ip, otherwise it returns
        an empty dict
    """
    dev_hash = {}
    try:
        command = "grep cpe /etc/ossim/agent/config.yml | sed 's/.*device: \([^,]*\), device_id: \([^,]*\).*/\\2:\\1/g'"
        response = ansible.run_module([sensor_ip], module="shell", args=command)
        (success, msg) = ansible_is_valid_response(sensor_ip, response)
        if success:
            response = response['contacted'][sensor_ip]['stdout'].split('\n')
            for i in response:
                if i and ':' in i:
                    k, v = i.split(':')
                    if k and v and k not in dev_hash.keys():
                        dev_hash[k] = v
    except Exception, e:
        api_log.error("[get_network_devices_for_sensor error]: %s, %s" % (str(e), traceback.format_exc()))
    return dev_hash


def get_hosts_in_syslog(system_ip):
    """Returns a hash table of host_ip = log filename found under the folder /var/log/*

    Args:
        system_ip (str): System IP

    Returns:
        On success, it returns a hash table with the correct values, otherwise it returns
        an empty dict

    Note:
        DEPRECATED (Not used)
        4
    """
    hostlist = {}

    try:
        #command = """executable=/bin/bash grep -rEo '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}' /var/log/* | sort -u """
        command = """grep --exclude=\*.{tar.gz,dat,gz} -rIEo '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}' /var/log/* | sort -u """
        response = ansible.run_module(host_list=[system_ip], module="shell", args=command)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if success:
            response = response['contacted'][system_ip]['stdout'].split('\n')
            for line in response:
                split_line = line.split(':')
                if len(split_line) == 2:
                    log_filename = split_line[0]
                    host_ip = split_line[1]
                else:
                    continue
                if not hostlist.has_key(host_ip):
                    hostlist[host_ip] = []
                if log_filename not in  hostlist[host_ip]:
                    hostlist[host_ip].append(log_filename)
    except Exception, e:
        api_log.error("get_hosts_in_syslog error: %s, %s" % (str(e), traceback.format_exc()))
    return hostlist

def get_logfiles_for_host(system_ip, host_ips):
    """Returns a list of log files where the host ip have been found

    Args:
        system_ip(str): System IP
        host_ips(): list of hosts

    Returns:
        On success, it returns a hash table with the correct values, otherwise it returns
        an empty dict

    Note:
        DEPRECATED (Not used)

    """
    logfiles = []

    try:
        grep_filter = "|".join(ip for ip in host_ips)
        command = """executable=/bin/bash grep --exclude=\*.{tar.gz,dat,gz} -rIEo '%s' /var/log/ | sort -u """ % grep_filter
        response = ansible.run_module(host_list=[system_ip], module="shell", args=command)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if success:
            response = response['contacted'][system_ip]['stdout'].split('\n')
            for line in response:
                splitted_line = line.split(':')
                if len(splitted_line) != 2:
                    continue
                log_filename = splitted_line[0]
                if log_filename not in logfiles:
                    logfiles.append(log_filename)
    except Exception, e:
        api_log.error("get_logfiles_for_host: %s, r: %s" % (str(e), response))
    return logfiles
