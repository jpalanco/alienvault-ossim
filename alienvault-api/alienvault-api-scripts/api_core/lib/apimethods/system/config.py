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

import api_log
from db.methods.system import get_system_ip_from_system_id
from ansiblemethods.system.system import get_av_config
from apimethods.system.cache import use_cache


@use_cache(namespace="system")
def get_system_config_general(system_id, no_cache=False):
    (success, system_ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret

    (success, config_values) = get_av_config(system_ip, {'general_admin_dns': '',
                                                         'general_admin_gateway': '',
                                                         'general_admin_ip': '',
                                                         'general_admin_netmask': '',
                                                         'general_hostname': '',
                                                         'general_interface': '',
                                                         'general_mailserver_relay': '',
                                                         'general_mailserver_relay_passwd': '',
                                                         'general_mailserver_relay_port': '',
                                                         'general_mailserver_relay_user': '',
                                                         'general_ntp_server': '',
                                                         'general_profile': '',
                                                         'firewall_active': '',
                                                         'update_update_proxy': '',
                                                         'update_update_proxy_dns': '',
                                                         'update_update_proxy_pass': '',
                                                         'update_update_proxy_port': '',
                                                         'update_update_proxy_user': '',
                                                         'vpn_vpn_infraestructure': ''
                                                         })

    if not success:
        api_log.error("system: get_config_general error: " + str(config_values))
        return (False, "Cannot get general configuration info %s" % str(config_values))

    return (True, config_values)


@use_cache(namespace="system")
def get_system_config_alienvault(system_id, no_cache=False):

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return (False, system_ip)

    (success, config_values) = get_av_config(system_ip, {'framework_framework_ip': '',
                                                         'sensor_detectors': '',
                                                         'sensor_interfaces': '',
                                                         'sensor_mservers': '',
                                                         'sensor_netflow': '',
                                                         'sensor_networks': '',
                                                         'server_server_ip': '',
                                                         'server_alienvault_ip_reputation': '',
                                                         'ha_ha_virtual_ip':'',
                                                         'ha_ha_role':'',
                                                         })

    if not success:
        api_log.error("system: get_config_alienvault error: " + str(config_values))
        return (False, "Cannot get AlienVault configuration info %s" % str(config_values))

    return (True, config_values)
