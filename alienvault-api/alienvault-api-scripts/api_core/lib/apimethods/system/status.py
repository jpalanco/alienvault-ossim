# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2014 AlienVault
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
"""
    API methods to call the facts
"""
from db.methods.system import get_system_ip_from_system_id

from ansiblemethods.system.status import system_all_info as ans_system_all_info
from ansiblemethods.system.status import system_status as ans_system_status
from ansiblemethods.system.status import network_status as ans_network_status
from ansiblemethods.system.status import alienvault_status as ans_alienvault_status
from ansiblemethods.system.status import cpu as ans_cpu
from ansiblemethods.system.status import disk_usage as ans_disk_usage
from ansiblemethods.system.status import package_list as ans_package_list
from collections import namedtuple
from ansiblemethods.system.system import get_system_setup_data
from apimethods.system.cache import use_cache
from apimethods.system.proxy import AVProxy
from ansiblemethods.system.network import get_iface_list

APIResult = namedtuple('APIResult', ['success', 'data'])

import api_log


@use_cache(namespace="system")
def system_all_info(system_id, no_cache=False):
    """
        Return all the facts. The results are cache in the "system" namespace, return from the
        cache if there is data. The "system" namespace is flushed when the configuration is modified

        Args:
            system_id (str): A valid uuid or local
            no_cache (bool): Not used, but we need it declared to make happy the  @use_cache decorator

        Returns:
            A tuple (success, data) where success is a boolean informing the success (True) or failure (False) of the call
            the data member return all the facts as a dict.

            On error, a message about it is returned in the *data* field.
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if success:
        # Get the network_status_facts
        facts = ans_system_all_info(system_ip)
        return facts
    else:
        return APIResult(success, system_ip)


def system_status(system_id):
    """
        Return system facts.

        Args:
            system_id (str): A valid uuid or local

        Returns:
            A tuple (success, data) where success is a boolean informing the success (True) or failure (False) of the call
            the data member return the system facts as a dict.

            On error, a message about it is returned in the *data* field.
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if success:
        return ans_system_status(system_ip)
    else:
        return APIResult(success, system_ip)


@use_cache(namespace="system")
def network_status(system_id, no_cache=False):
    """
       Return the network facts.

        Args:
            system_id (str): A valid uuid or local
            no_cache (bool): Not used, but we need it declared to make happy the  @use_cache decorator

        Returns:
            A tuple (success, data) where *success* is a boolean informing the success (True) or failure (False) of the call
            the *data* member return the network facts as a dict.

            On error, a message about it is returned in the *data* field.
    """
    
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        return False, system_ip
    
    success, ifaces = get_iface_list(system_ip)
    if success:
        # Get the iface disk
        # ifaces = setup_data['ansible_interfaces']
        # ipv4default = setup_data['ansible_default_ipv4']
        # Get the network_status_facts
        success, facts = ans_network_status(system_ip)
        if success:
            for iface in facts['interfaces'].keys():
                if iface in ifaces:
                    # iface_data = setup_data['ansible_' + iface]
                    if ifaces[iface].get('ipv4', None) is not None:
                        facts['interfaces'][iface]['ipv4'] = ifaces[iface]['ipv4']
                        
                    facts['interfaces'][iface]['role'] = ifaces[iface]['role']
                    # Add the a "UP" flags
                    # if iface_data['active'] is True:
                    #    facts.data['interfaces'][iface]['status'] = 'UP'
                    # else:
                    #    facts.data['interfaces'][iface]['status'] = 'DOWN'
                    # Check gateway
                    # if ipv4default.get('interface', None) == iface and ipv4default.get('gateway', None) is not None:
                    #    facts.data['gateway'] = ipv4default.get('gateway')
                    pass

            return True, facts
            
        else:
            return False, facts
            
    else:
        return False, ifaces

@use_cache(namespace="system")
def alienvault_status(system_id, no_cache=False):
    """
        Return the alienvault_status facts

        Args:
            system_id (str): A valid uuid or local

        Returns:
            A tuple (success, data) where *success* is a boolean informing the success (True) or failure (False) of the call
            the *data* member return the alienvault status facts as a dict.

            On error, a message about it is returned in the *data* field.

    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if success:
        return ans_alienvault_status(system_ip)
    else:
        return APIResult(success, system_ip)


def disk_usage(system_id):
    """
        Return the disk_usage facts

        Args:
            system_id (str): A valid uuid or local

        Returns:
            A tuple (success, data) where *success* is a boolean informing the success (True) or failure (False) of the call
            the *data* member return the disk usage facts as a dict.

            On error, a message about it is returned in the *data* field.


    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if success:
        return ans_disk_usage(system_ip)
    else:
        return APIResult(success, system_ip)


def cpu(system_id):
    """
        Return the cpu facts

        Args:
            system_id (str): A valid uuid or local

        Returns:
            A tuple (success, data) where *success* is a boolean informing the success (True) or failure (False) of the call
            the *data* member return the cpu usage facts as a dict.

            On error, a message about it is returned in the *data* field.
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if success:
        return ans_cpu(system_ip)
    else:
        return APIResult(success, system_ip)
        
           
def package_list(system_id):
    """
    Add a system usign a system id. Already in database
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error(str(system_ip))
        return False, "Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))

    success, msg = ans_package_list(system_ip)
    if not success:
        api_log.error(str(msg))
        return False, msg

    return True, msg
