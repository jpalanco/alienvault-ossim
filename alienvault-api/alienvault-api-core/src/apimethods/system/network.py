# -*- coding: utf-8 -*-
#
# License:
#
#    Copyright (c) 2014 AlienVault
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

import ipaddress
import ansiblemethods.system.network
from db.methods.system import get_system_ip_from_system_id, get_system_ip_from_local
from celerymethods.jobs.reconfig import alienvault_reconfigure
from ansiblemethods.system.system import get_av_config
from apimethods.system.cache import use_cache, flush_cache

def dns_resolution(system_id):
    """
    Check the DNS name resolution.
    """
    using_proxy = False
    dns_lookup = 'data.alienvault.com'

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return (False, "Error translating system id to ip")

    (success, data) = get_av_config(system_ip, {'update_update_proxy': ''})
    if not success:
        return (False, "Error getting proxy configuration")


    if 'update_update_proxy' not in data:
        return (False, "Error getting proxy dns. 'update_proxy_key_not_found'")

    using_proxy = data['update_update_proxy'] !='disabled'
    if using_proxy:
        (success, data) = get_av_config(system_ip, {'update_update_proxy_dns': ''})
        if not success:
            return (False, "Error getting proxy dns")
        if 'update_update_proxy_dns' not in data:
            return (False, "Error getting proxy dns. 'update_update_proxy_dns not found'")
        dns_lookup = data['update_update_proxy_dns']

    (success, data) = ansiblemethods.system.network.resolve_dns_name(system_ip, dns_lookup)
    if not success:
        return (False, "Error resolving DNS name")

    return (True, data)


def dns_is_external(param_ip):
    """ Check if the DNS ip is in the RFC1918 range
        return 0  if check ok
        return -2  if check not ok
        return -1 if ip param  is not a ip
    """
    result = 0
    try:
        ip = ipaddress.ip_address(unicode(param_ip))
        if not ip.is_private:
            result = -2
    except ValueError:
        result = -1

    return result

@use_cache(namespace="sensor_network")
def get_interfaces(system_id, no_cache=False):
    """
    Return a list of the system network interfaces and its properties.
    """
    (success, ip) = ret = get_system_ip_from_system_id (system_id)
    if not success:
        return ret

    (success, ifaces) = ret = ansiblemethods.system.network.get_iface_list(ip)
    if not success:
        return ret

    return (True, ifaces)

def get_interface(system_id, iface):
    """
    Return the properties of a single network interface.
    """
    (success, ip) = ret = get_system_ip_from_system_id (system_id)
    if not success:
        return ret

    (success, ifaces) = ret = ansiblemethods.system.network.get_iface_list(ip)
    if not success:
        return ret

    if not iface in ifaces:
        return (False, "Invalid network interface")

    return (True, ifaces[iface])

def set_interfaces_roles(system_id, interfaces):
    """
    Set roles for the system network interfaces.
    """
    (success, ip) = ret = get_system_ip_from_system_id (system_id)
    if not success:
        return ret

    # Flush caches
    flush_cache(namespace="sensor_network")
    # Next verify that the interfaces param exists, correct decode a base64 string
    # and this string is a json object
    (success, msg) = ret = ansiblemethods.system.network.set_interfaces_roles(ip, interfaces)

    if not success:
        return ret

    job = alienvault_reconfigure.delay(ip)
    if job.state is 'FAILURE':
        return (False, "Can't start task to delete orphan status message")

    return (True, job.id)

def put_interface (system_id, iface, promisc):
    """
    Modify network interface properties (currently, only sets promisc mode)
    """
    # Flush the cache "sensor_network"
    flush_cache(namespace="sensor_network")
    (success, ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret

    return ansiblemethods.system.network.set_iface_promisc_status (ip, iface, promisc)

def get_interface_traffic (system_id, iface, timeout):
    """
    Get the inbound and outbound network traffic in an interface for the last 'timeout' seconds.
    """
    try:
        timeout_int = int(timeout)
    except ValueError:
        return (False, "Invalid value for timeout: %s" % timeout)

    if timeout_int not in range(1, 60):
        return (False, "Timeout value of %d out of range, it should be between 1 and 60" % timeout_int)

    (success, ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret

    return ansiblemethods.system.network.get_iface_traffic(ip, iface, timeout=timeout_int)

def get_traffic_stats (system_id):
    """
    Get traffic statistics for a system.
    """
    (success, ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret

    return ansiblemethods.system.network.get_iface_stats(ip)


def get_fqdn_api(system_id, host_ip):
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        success, system_ip = get_system_ip_from_local()
    return ansiblemethods.system.network.get_fqdn(system_ip, host_ip)
