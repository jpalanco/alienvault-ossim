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
"""
    Implementation of system monitors
        MonitorSystemCPULoad
        MonitorDiskUsage
        MonitorSystemDNS
        MonitorRemoteCertificates
        MonitorRetrievesRemoteInfo
        MonitorPendingUpdates
"""

import traceback
import celery.utils.log
from api.lib.monitors.monitor import Monitor, MonitorTypes, ComponentTypes
from ansiblemethods.system.system import get_root_disk_usage, get_system_load, \
    get_av_config, ping_system, ansible_download_release_info
from apimethods.system.network import dns_is_external
from db.methods.system import get_systems, set_system_vpn_ip, set_system_ha_ip, set_system_ha_role, \
    get_system_ip_from_local
from db.methods.sensor import get_sensor_id_from_system_id, set_sensor_properties_active_inventory, \
    set_sensor_properties_passive_inventory,\
    set_sensor_properties_netflow
from apimethods.sensor.sensor import get_plugins_from_yaml
from apimethods.system.config import get_system_config_general, get_system_config_alienvault
from apimethods.system.network import get_interfaces
from apimethods.system.system import get as system_get
from apimethods.system.system import apimethod_get_update_info
from apimethods.system.cache import flush_cache
from apimethods.utils import is_valid_ipv4
from apimethods.system.status import system_all_info, network_status, alienvault_status
import api_log

logger = celery.utils.log.get_logger("celery")


class MonitorSystemCPULoad(Monitor):
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_CPU_LOAD)
        self.message = 'System CPU Load monitor started'

    def start(self):
        """
            Starts the monitor activity
        """
        rt = True
        self.remove_monitor_data()
        # Load all system from current_local
        logger.info("Checking systems cpu load")
        result, systems = get_systems()
        if not result:
            logger.error("Can't retrieve the system info: %s" % str(systems))
            return False
        for (system_id, system_ip) in systems:
            (result, load) = get_system_load(system_ip)
            if result:
                try:
                    logger.info("CPU Load: %s %f" % (system_ip, load))
                    monitor_data = {"cpu_load": load}
                    self.save_data(system_id, ComponentTypes.SYSTEM, self.get_json_message(monitor_data))
                except Exception as e:
                    logger.error("Error==>> " + traceback.format_exc())
                    rt = False
                    break
            else:
                logger.error("MonitorSystemCPULoad: %s" % load)
                rt = False
                break
        return rt


class MonitorDiskUsage(Monitor):
    """
    Monitor disk usage in the local server.
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_DISK_SPACE)
        self.message = 'Disk Usage Monitor Enabled'

    def start(self):
        """
        Starts the monitor activity

        :return: True on success, False otherwise
        """
        self.remove_monitor_data()
        # Find the local server.
        rc, system_list = get_systems()
        if not rc:
            logger.error("Can't retrieve systems..%s" % str(system_list))
            return False
        args = {}
        args['plugin_list'] = 'disk_usage.plg'
        args['output_type'] = 'ansible'
        for (system_id, system_ip) in system_list:
            result, ansible_output = get_root_disk_usage(system_ip)

            if not self.save_data(system_id, ComponentTypes.SYSTEM,
                                  self.get_json_message({'disk_usage': ansible_output})):
                logger.error("Can't save monitor info")
        return True


class MonitorSystemDNS(Monitor):
    """
        Monitor the current system DNS.
    """
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_SYSTEM_DNS)
        self.message = "Monitor the current system DNS"

    def start(self):
        self.remove_monitor_data()
        rc, system_list = get_systems()
        if not rc:
            logger.error("Can't retrieve systems..%s" % str(system_list))
            return False

        for (system_id, system_ip) in system_list:
            # Use ansible to get the DNS config.
            result, ansible_output = get_av_config(system_ip, {'general_admin_dns': ''})
            logger.info("DNS returned from ossim_setup.conf %s" % str(ansible_output))
            if result:
                dnslist = []
                if 'general_admin_dns' in ansible_output:
                    dnslist = ansible_output['general_admin_dns'].split(',')
                count = 0
                for ip in dnslist:
                    r = dns_is_external(ip)
                    if r == -2:
                        count += 1
                    elif r == -1:
                        logger.error("Bad data in admin_dns field of ossim_setup.conf: " + str(ip))
                # logger.info("DNS IP count = " + str(count))
                if count == len(dnslist):
                    self.save_data(system_id, ComponentTypes.SYSTEM,
                                   self.get_json_message({'admin_dns':
                                                         'Warning: All DNS configured are externals',
                                                         'internal_dns': False}))
                else:
                    self.save_data(system_id, ComponentTypes.SYSTEM,
                                   self.get_json_message({'admin_dns': 'DNS ok. You have at least one internal DNS',
                                                          'internal_dns': True}))

            else:
                if not self.save_data(system_id, ComponentTypes.SYSTEM,
                                      self.get_json_message({'admin_dns': 'Error: %s' % str(ansible_output),
                                                             'internal_dns': True})):
                    logger.error("Can't save monitor info")
        return True


class MonitorRemoteCertificates(Monitor):
    """
        Monitor the remote certificates.
    """
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_REMOTE_CERTIFICATES)
        self.message = "Monitor the remote certificates"

    def start(self):
        self.remove_monitor_data()
        rc, system_list = get_systems()
        if not rc:
            logger.error("Can't retrieve systems..%s" % str(system_list))
            return False
        for (system_id, system_ip) in system_list:
            result, ansible_output = ping_system(system_ip)
            if not result:
                # Check whether is sensor or not
                sensor, sensor_id = get_sensor_id_from_system_id(system_id)
                if not self.save_data(system_id, ComponentTypes.SYSTEM,
                                      self.get_json_message({'remote_certificates': 'Error: %s' % str(ansible_output),
                                                             'contacted': False,
                                                             'is_sensor': sensor})):
                    logger.error("Can't save monitor info")
            else:
                self.save_data(system_id, ComponentTypes.SYSTEM,
                               self.get_json_message({'remote_certificates': 'Ping OK',
                                                      'contacted': True}))
        return True


class MonitorRetrievesRemoteInfo(Monitor):
    """
        Monitor the remote certificates.
    """
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_GET_REMOTE_SYSTEM_INFO)
        self.message = "Monitor: Get remote system information"

    def start(self):
        try:
            self.remove_monitor_data()
            rc, system_list = get_systems()
            if not rc:
                logger.error("Can't retrieve systems..%s" % str(system_list))
                return False

            for (system_id, system_ip) in system_list:
                success, sensor_id = get_sensor_id_from_system_id(system_id)
                if not success:
                    continue
                success, result = get_plugins_from_yaml(sensor_id, no_cache=True)
                if not success:
                    continue
                success, result = system_all_info(system_id, no_cache=True)
                if not success:
                    continue
                success, result = network_status(system_id, no_cache=True)
                if not success:
                    continue
                success, result = alienvault_status(system_id, no_cache=True)
                if not success:
                    continue
                success, result = get_system_config_general(system_id, no_cache=True)
                if not success:
                    continue
                
                #Getting config params from the system, we do use this result var so do not change the order of the calls!
                success, result = get_system_config_alienvault(system_id, no_cache=True)
                if not success:
                    continue
                    
                prads_enabled = False
                suricata_snort_enabled = False
                netflow_enabled = False
                ha_ip = None
                ha_role = None
                
                if 'sensor_detectors' in result:
                    prads_enabled = True if 'prads' in result['sensor_detectors'] else False
                    suricata_snort_enabled = True if 'snort' in result['sensor_detectors'] or 'suricata' in result['sensor_detectors'] else False
                if 'sensor_netflow' in result:
                    netflow_enabled = True if result['sensor_netflow'] == 'yes' else False

                if 'ha_ha_virtual_ip' in result:
                    ha_ip = result['ha_ha_virtual_ip']
                    if not is_valid_ipv4(ha_ip):
                        ha_ip = None
                if 'ha_ha_role' in result:
                    ha_role = result['ha_ha_role']
                    if ha_role not in ['master', 'slave']:
                        ha_role = None

                success, result = get_interfaces(system_id, no_cache=True)
                if not success:
                    continue
                success, result = system_get(system_id, no_cache=True)
                if not success:
                    continue
                    
                vpn_ip = None
                if "ansible_tun0" in result:
                    try:
                        vpn_ip = result['ansible_tun0']['ipv4']['address']
                    except:
                        vpn_ip = None
                        
                # TO DB; vpn_ip, netflow, active inventory, passive inventory
                # ha_ip
                success, message = set_sensor_properties_active_inventory(sensor_id, suricata_snort_enabled)
                if not success:
                    continue
                success, message = set_sensor_properties_passive_inventory(sensor_id, prads_enabled)
                if not success:
                    continue
                success, message = set_sensor_properties_netflow(sensor_id, netflow_enabled)
                if not success:
                    continue

                if vpn_ip is not None:
                    success, message = set_system_vpn_ip(system_id, vpn_ip)
                    if not success:
                        continue
                if ha_role is not None:
                    success, message = set_system_ha_role(system_id, ha_role)
                    if not success:
                        continue
                if ha_ip is not None:
                    success, message = set_system_ha_ip(system_id, ha_ip)
                    if not success:
                        continue
                        
        except Exception as err:
            api_log.error("Something wrong happened while running the MonitorRetrievesRemoteInfo monitor %s" % str(err))
            return False
        return True


class MonitorPendingUpdates(Monitor):
    """ Monitor for pending updates

    """
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_PENDING_UPDATES)
        self.message = 'Pending updates monitor started'

    def start(self):
        """ Starts the monitor activity
        """
        rt = True
        self.remove_monitor_data()
        # Clear caché
        flush_cache(namespace='system')
        # Load all system from current_local
        logger.info("Checking for pending updates")
        result, systems = get_systems()
        if not result:
            logger.error("Can't retrieve the system info: %s" % str(systems))
            return False

        pending_updates = False
        for (system_id, system_ip) in systems:
            (success, info) = apimethod_get_update_info(system_id)
            if success:
                try:
                    sys_pending_updates = info['pending_updates']
                    pending_updates = pending_updates or sys_pending_updates
                    logger.info("Pending Updates for system %s (%s): %s" % (system_id, system_ip, sys_pending_updates))
                    monitor_data = {"pending_updates": sys_pending_updates}
                    self.save_data(system_id, ComponentTypes.SYSTEM, self.get_json_message(monitor_data))
                except Exception as e:
                    logger.error("[MonitorPendingUpdates] Error: %s" % str(e))
                    rt = False
                    break
            else:
                logger.error("MonitorPendingUpdates: %s" % info)
                rt = False
                break

        if pending_updates:
            success, local_ip = get_system_ip_from_local()
            if not success:
                logger.error("[MonitorPendingUpdates] Unable to get local IP: %s" % local_ip)
                return False

            success, msg = ansible_download_release_info(local_ip)
            if not success:
                logger.error("[MonitorPendingUpdates] Unable to retrieve release info file: %s" % msg)
                return False

        return rt
