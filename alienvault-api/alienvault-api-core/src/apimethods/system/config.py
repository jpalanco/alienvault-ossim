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
from db.methods.system import db_system_update_hostname
from db.methods.system import db_system_update_admin_ip
from celerymethods.jobs.system import alienvault_asynchronous_reconfigure
from celerymethods.tasks.tasks import Scheduler
from ansiblemethods.system.system import get_av_config
from ansiblemethods.system.system import set_av_config
from ansiblemethods.system.system import ansible_add_ip_to_inventory
from apimethods.system.cache import use_cache
from apimethods.system.cache import flush_cache


@use_cache(namespace="system_config")
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
                                                         'update_update_proxy_user': ''
                                                         })

    if not success:
        api_log.error("system: get_config_general error: " + str(config_values))
        return (False, "Cannot get general configuration info %s" % str(config_values))

    return (True, config_values)


@use_cache(namespace="system_config")
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
                                                         'ha_ha_virtual_ip': '',
                                                         'ha_ha_role': '',
                                                         })

    if not success:
        api_log.error("system: get_config_alienvault error: " + str(config_values))
        return (False, "Cannot get AlienVault configuration info %s" % str(config_values))

    return (True, config_values)


def set_system_config(system_id, set_values):
    """
    Set the configuration values to the system
    Args:
        system_id(str): The system id where the configuration will be setted
        set_values: key-value dictionary with the configuration settings
    Returns:
        (success, job_id): success=True when the operation when ok, otherwise success=False.
        On success job_id: id of the async reconfig job, error message string otherwise
    """

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return (False, system_ip)

    (success, config_values) = set_av_config(system_ip, set_values)

    if not success:
        api_log.error("system: set_config_general error: " + str(config_values))
        return (False, "Cannot set general configuration info: %s" % str(config_values))

    flush_cache(namespace="system_config")

    if 'general_hostname' in set_values:
        success, msg = db_system_update_hostname(system_id, set_values['general_hostname'])
        if not success:
            return (False, "Error setting values: %s" % msg)

    new_admin_ip = None
    if 'general_admin_ip' in set_values:
        new_admin_ip = set_values['general_admin_ip']
        success, msg = db_system_update_admin_ip(system_id, set_values['general_admin_ip'])
        if not success:
            return (False, "Error setting values: %s" % msg)

        success, msg = ansible_add_ip_to_inventory(set_values['general_admin_ip'])
        if not success:
            return (False, "Error setting the admin IP address")

    job = alienvault_asynchronous_reconfigure.delay(system_ip, new_admin_ip)

    return (True, job.id)


def get_system_sensor_configuration(system_id):

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return (False, system_ip)

    (success, config_values) = get_av_config(system_ip, {'sensor_asec': '',
                                                         'sensor_detectors': '',
                                                         'sensor_interfaces': '',
                                                         'sensor_mservers': '',
                                                         'sensor_netflow': '',
                                                         'sensor_networks': '',
                                                         'sensor_monitors': '',
                                                         })

    if not success:
        api_log.error("system: get_config_alienvault error: " + str(config_values))
        return (False, "Cannot get AlienVault configuration info %s" % str(config_values))

    return (True, config_values)


def set_system_sensor_configuration(system_id, set_values):
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return (False, system_ip)

    (success, config_values) = set_av_config(system_ip, set_values)

    if not success:
        api_log.error("system: set_config_general error: " + str(config_values))
        return (False, "Cannot set general configuration info: %s" % str(config_values))
    return True, "OK"


def set_system_config_telemetry_enabled(enabled=False):
    """
    Enable the local telemetry collection.
    """
    scheduler = Scheduler()
    task = scheduler.get_task('monitor_check_platform_telemetry_data')
    task.enabled = enabled
    scheduler.update_task(task)


def get_system_config_telemetry_enabled():
    """
    Get the local telemetry collection enabled flag.
    """
    scheduler = Scheduler()
    task = scheduler.get_task('monitor_check_platform_telemetry_data')
    return task.enabled
