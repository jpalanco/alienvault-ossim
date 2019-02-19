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

import datetime
import json
import os
import time
import traceback

import api_log
import celery.utils.log
from ansiblemethods.system.about import get_is_professional
from ansiblemethods.system.maintenance import system_reboot_needed
from ansiblemethods.system.network import ansible_check_insecure_vpn
from ansiblemethods.system.status import get_local_time
from ansiblemethods.system.support import check_support_tunnels
from ansiblemethods.system.system import (
    get_root_disk_usage,
    get_system_load,
    get_av_config,
    ansible_download_release_info,
    ansible_get_otx_key
)
from api.lib.mcenter import get_message_center_messages
from api.lib.monitors.messages import MessageReader
from api.lib.monitors.monitor import Monitor, MonitorTypes, ComponentTypes
from apiexceptions import APIException
from apimethods.data.status import load_external_messages_on_db
from apimethods.otx.otx import apimethod_get_open_threat_exchange_config, apimethod_is_otx_enabled
from apimethods.otx.pulse import OTXv2
from apimethods.sensor.plugin import get_sensor_plugins
from apimethods.system.backup import get_backup_list
from apimethods.system.config import (
    get_system_config_general,
    get_system_config_alienvault
)
from apimethods.system.network import dns_is_external
from apimethods.system.network import get_interfaces
from apimethods.system.status import system_all_info, network_status, alienvault_status
from apimethods.system.support import status_tunnel
from apimethods.system.system import (
    get as system_get,
    apimethod_get_update_info,
    apimethod_get_remote_software_update,
    system_is_trial,
    system_is_professional,
    get_license_devices,
    ping_system
)
from apimethods.utils import is_valid_ipv4
from celery import group
from celerymethods.jobs.system import alienvault_asynchronous_update
from db.methods.api import get_monitor_data as db_get_monitor_data
from db.methods.data import get_asset_list
from db.methods.sensor import (
    get_sensor_id_from_system_id,
    set_sensor_properties_active_inventory,
    set_sensor_properties_passive_inventory,
    set_sensor_properties_netflow,
    check_any_orphan_sensor,
    get_sensor_by_sensor_id
)
from db.methods.system import (
    get_systems,
    set_system_vpn_ip,
    set_system_ha_ip,
    set_system_ha_role,
    get_system_ip_from_local,
    get_system_id_from_local,
    db_system_update_hostname,
    db_get_hostname,
    set_system_ha_name,
    fix_system_references,
    check_any_innodb_tables,
    get_wizard_data,
    get_trial_expiration_date,
    check_backup_process_running,
    db_get_config,
    get_feed_auto_update
)

messages = MessageReader()
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
                except Exception:
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
            dummy_result, ansible_output = get_root_disk_usage(system_ip)

            if not self.save_data(system_id, ComponentTypes.SYSTEM,
                                  self.get_json_message(
                                      {'disk_usage': ansible_output})):
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
                    admin_dns_msg = "Warning: All DNS configured are externals"
                    self.save_data(system_id, ComponentTypes.SYSTEM,
                                   self.get_json_message(
                                       {'admin_dns': admin_dns_msg,
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
            try:
                reachable = ping_system(system_id, no_cache=True)
            except APIException:
                reachable = False

            if not reachable:
                # Check whether is sensor or not
                sensor, sensor_id = get_sensor_id_from_system_id(system_id)
                if not self.save_data(system_id,
                                      ComponentTypes.SYSTEM,
                                      self.get_json_message({'contacted': False,
                                                             'is_sensor': sensor})):
                    logger.error("Can't save monitor info")
            else:
                self.save_data(system_id,
                               ComponentTypes.SYSTEM,
                               self.get_json_message({'contacted': True}))
        return True


class MonitorRetrievesRemoteSysStatus(Monitor):
    """
        Monitor to retrieve the remote systems status info and update system cache with it.
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_GET_REMOTE_SYSTEM_STATUS)
        self.message = "Monitor: Get remote system status"

    def start(self):
        try:
            self.remove_monitor_data()
            rc, system_list = get_systems(directly_connected=False)
            if not rc:
                logger.error("Can't retrieve systems..%s" % str(system_list))
                return False

            for (system_id, system_ip) in system_list:
                success, result = system_all_info(system_id, no_cache=True)
                if not success:
                    logger.warning("[MonitorRetrieveRemoteSysStatus] "
                                   "system_all_info failed for system %s (%s)" % (system_ip, system_id))
                    continue

        except Exception as err:
            api_log.error(
                "Something wrong happened while running the MonitorRetrieveRemoteSysStatus monitor %s" % str(err))
            return False
        return True


class MonitorRetrievesRemoteInfo(Monitor):
    """
        Monitor to retrieve the remote systems status info and update system cache with it.
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_GET_REMOTE_SYSTEM_INFO)
        self.message = "Monitor: Get remote system info"

    def start(self):
        try:
            self.remove_monitor_data()
            rc, system_list = get_systems(directly_connected=False)
            if not rc:
                logger.error("Can't retrieve systems..%s" % str(system_list))
                return False

            for (system_id, system_ip) in system_list:
                success, result = network_status(system_id, no_cache=True)
                if not success:
                    logger.warning("[MonitorRetrievesRemoteInfo] "
                                   "network_status failed for system %s (%s)" % (system_ip, system_id))
                    continue
                success, result = alienvault_status(system_id, no_cache=True)
                if not success:
                    logger.warning("[MonitorRetrievesRemoteInfo] "
                                   "alienvault_status failed for system %s (%s)" % (system_ip, system_id))
                    continue
                success, result = status_tunnel(system_id, no_cache=True)
                if not success:
                    logger.warning("[MonitorRetrievesRemoteInfo] "
                                   "status_tunnel failed for system %s (%s)" % (system_ip, system_id))
                    continue
                # Update interfaces cache
                success, result = get_interfaces(system_id, no_cache=True)
                if not success:
                    continue

                # Backups
                success, message = get_backup_list(system_id=system_id,
                                                   backup_type="configuration",
                                                   no_cache=True)
                if not success:
                    logger.warning("[MonitorRetrievesRemoteInfo] get_backup_list failed: %s" % message)

        except Exception as err:
            api_log.error(
                "Something wrong happened while running the MonitorRetrieveRemoteSysStatus monitor %s" % str(err))
            return False
        return True


class MonitorUpdateSysWithRemoteInfo(Monitor):
    """
        Monitor to retrieve the remote systems info and update the DB with that data.
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_UPDATE_SYSTEM_WITH_REMOTE_INFO)
        self.message = "Monitor: Update system with the data from the remote system"

    def start(self):
        try:
            self.remove_monitor_data()
            rc, system_list = get_systems(directly_connected=False)
            if not rc:
                logger.error("Can't retrieve systems..%s" % str(system_list))
                return False

            for (system_id, system_ip) in system_list:
                success, sensor_id = get_sensor_id_from_system_id(system_id)
                if not success:
                    logger.warning("[MonitorUpdateSysWithRemoteInfo] "
                                   "get_sensor_id_from_system_id failed for system %s (%s)" % (system_ip, system_id))
                    sensor_id = None

                # Read that data from cache to speed up execution in large env.
                # Cache will be filled by MonitorRetrievesRemoteSysStatus monitor
                success, result = system_all_info(system_id, no_cache=False)
                if not success:
                    logger.warning("[MonitorUpdateSysWithRemoteInfo] "
                                   "system_all_info failed for system %s (%s)" % (system_ip, system_id))
                    continue

                ha_name = None
                if 'ha_status' in result:
                    ha_name = 'active' if result['ha_status'] == 'up' else 'passive'

                success, result = get_system_config_general(system_id, no_cache=True)
                if not success:
                    logger.warning("[MonitorUpdateSysWithRemoteInfo] "
                                   "get_system_config_general failed for system %s (%s)" % (system_ip, system_id))
                    continue

                hostname = result.get('general_hostname', None)
                if hostname is not None:
                    success, hostname_old = db_get_hostname(system_id)
                    if not success:
                        logger.warning("[MonitorUpdateSysWithRemoteInfo] "
                                       "db_get_hostname failed for system %s (%s)" % (system_ip, system_id))
                        continue
                    if hostname == hostname_old:
                        hostname = None

                # Getting config params from the system,
                # we do use this result var so do not change the order of the calls!
                success, config_alienvault = get_system_config_alienvault(system_id, no_cache=True)
                if not success:
                    logger.warning("[MonitorUpdateSysWithRemoteInfo] "
                                   "get_system_config_alienvault failed for system %s (%s)" % (system_ip, system_id))
                    continue

                ha_ip = None
                ha_role = None
                if 'ha_ha_virtual_ip' in config_alienvault:
                    ha_ip = config_alienvault['ha_ha_virtual_ip']
                    if not is_valid_ipv4(ha_ip):
                        ha_ip = None

                if 'ha_ha_role' in config_alienvault:
                    ha_role = config_alienvault['ha_ha_role']
                    if ha_role not in ['master', 'slave']:
                        ha_role = None

                # Update system setup data cache
                success, result = system_get(system_id, no_cache=True)
                if not success:
                    continue

                vpn_ip = None
                if "ansible_tun0" in result:
                    try:
                        vpn_ip = result['ansible_tun0']['ipv4']['address']
                    except Exception:
                        vpn_ip = None

                # Sensor exclusive
                if sensor_id is not None and sensor_id != '':
                    self.__update_sensor_properties(sensor_id=sensor_id,
                                                    config_alienvault=config_alienvault)
                    # Refresh sensor plugins cache
                    try:
                        get_sensor_plugins(sensor_id, no_cache=True)
                    except APIException:
                        logger.warning("[MonitorUpdateSysWithRemoteInfo] "
                                       "error getting plugins from sensor '{0}' {1}".format(sensor_id, system_ip))

                if vpn_ip is not None:
                    success, message = set_system_vpn_ip(system_id, vpn_ip)
                    if not success:
                        logger.warning("[MonitorUpdateSysWithRemoteInfo] set_system_vpn_ip failed: %s" % message)

                if ha_role is not None:
                    success, message = set_system_ha_role(system_id, ha_role)
                    if not success:
                        logger.warning("[MonitorUpdateSysWithRemoteInfo] set_system_ha_role failed: %s" % message)
                else:
                    success, message = set_system_ha_role(system_id, 'NULL')
                    if not success:
                        logger.warning("[MonitorUpdateSysWithRemoteInfo] set_system_ha_role failed: %s" % message)

                if ha_ip is not None:
                    success, message = set_system_ha_ip(system_id, ha_ip)
                    if not success:
                        logger.warning("[MonitorUpdateSysWithRemoteInfo] set_system_ha_ip: %s" % message)
                    success, message = fix_system_references()
                    if not success:
                        logger.warning("[MonitorUpdateSysWithRemoteInfo] fix_system_references: %s" % message)
                    if ha_name is not None:
                        success, message = set_system_ha_name(system_id, ha_name)
                        if not success:
                            logger.warning("[MonitorUpdateSysWithRemoteInfo] set_system_ha_name failed: %s" % message)
                else:
                    success, message = set_system_ha_ip(system_id, '')
                    if not success:
                        logger.warning("[MonitorUpdateSysWithRemoteInfo] set_system_ha_ip failed: %s" % message)

                if hostname is not None:
                    success, message = db_system_update_hostname(system_id, hostname)
                    if not success:
                        logger.warning(
                            "[MonitorUpdateSysWithRemoteInfo] db_system_update_hostname failed: %s" % message)

        except Exception as err:
            api_log.error(
                "Something wrong happened while running the MonitorUpdateSysWithRemoteInfo monitor %s" % str(err))
            return False
        return True

    def __update_sensor_properties(self,
                                   sensor_id,
                                   config_alienvault):
        """ Update sensor properties
        """
        # Only updates sensors with entries in sensor and sensor_properties tables
        # This situation could happen in Federated environments without forwarding enabled
        success, sensor = get_sensor_by_sensor_id(sensor_id)
        if not success or sensor is None:
            return

        sensor_detectors = config_alienvault.get('sensor_detectors', [])
        sensor_netflow = config_alienvault.get('sensor_netflow', 'no')

        prads_enabled = 'prads' in sensor_detectors
        nids_enabled = 'AlienVault_NIDS' in sensor_detectors
        netflow_enabled = sensor_netflow == 'yes'

        success, message = set_sensor_properties_active_inventory(sensor_id, nids_enabled)
        if not success:
            logger.warning("[MonitorRetrievesRemoteInfo] "
                           "set_sensor_properties_active_inventory failed: %s" % message)
        success, message = set_sensor_properties_passive_inventory(sensor_id, prads_enabled)
        if not success:
            logger.warning("[MonitorRetrievesRemoteInfo] "
                           "set_sensor_properties_pasive_inventory failed: %s" % message)
        success, message = set_sensor_properties_netflow(sensor_id, netflow_enabled)
        if not success:
            logger.warning("[MonitorRetrievesRemoteInfo] "
                           "set_sensor_properties_netflow failed: %s" % message)


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

            success, is_pro = get_is_professional(local_ip)
            if success and is_pro:
                success, is_trial = system_is_trial('local')
                if success and is_trial:
                    logger.info("[MonitorPendingUpdates] Trial version. Skipping download of release info file")
                    return rt

            success, msg = ansible_download_release_info(local_ip)
            if not success:
                logger.error("[MonitorPendingUpdates] Unable to retrieve release info file: %s" % msg)
                return False

        return rt


class MonitorDownloadMessageCenterMessages(Monitor):
    """This monitor will connect to the message center server and it will download all the new messages"""

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_PLATFORM_MESSAGE_CENTER_DATA)
        self.message = 'Pending updates monitor started'

    def start(self):
        """ Starts the monitor activity
        """
        self.remove_monitor_data()
        monitor_data = {}

        success, system_id = get_system_id_from_local()
        if not success:
            return False

        # Load all system from current_local
        logger.info("MCServer downloading messages")
        messages, conn_failed = get_message_center_messages()
        if conn_failed:
            monitor_data['mc_server_connectivity'] = False
            logger.error("Cannot connect to Message Center server")
            self.save_data(system_id,
                           ComponentTypes.SYSTEM,
                           self.get_json_message(monitor_data))
            return True

        # Save a current status message for each message on the list
        success, data = load_external_messages_on_db(messages)
        logger.info("MCServer messages donwloaded.. %s:%s" % (success, str(data)))
        return True


class MonitorSystemCheckDB(Monitor):
    """
        Check database tables. Generate a notifcation message
        if some table use a innodb engine
    """

    def __init__(self):
        """
            Init method
        """
        Monitor.__init__(self, MonitorTypes.MONITOR_SYSTEM_CHECK_DB)
        self.message = 'System check DB started'

    def start(self):
        """
            Start monitor. Connect to database is local
        """
        (success, system_id) = get_system_id_from_local()
        if not success:
            api_log.error("Can't get local system_id")
            return False

        self.remove_monitor_data()

        # OSSIM must not tell to migrate the DB
        rc, pro = system_is_professional(system_id)
        if not pro:
            return True

        (success, result) = check_any_innodb_tables()
        mresult = False
        if success:
            if len(result) > 0:
                #  I need the component ID
                # (success, result) = insert_current_status_message("00000000-0000-0000-0000-000000010017",
                #                                                  system_id, "system", str(result))
                self.save_data(system_id,
                               ComponentTypes.SYSTEM,
                               self.get_json_message({"has_innodb": True,
                                                      "innodb_tables": result}))
                if not success:
                    api_log.error("Can't insert notification into system: %s" % str(result))
                    mresult = False
                else:
                    mresult = True
            else:
                mresult = True  # No messages to insert
        else:
            api_log.error("Can't check current database engine")
            mresult = False
        return mresult


class MonitorWebUIData(Monitor):
    """
    Check several database values affecting the system and/or web integrity
    - information regarding the wizard
    - information to know whether a system has been inserted in the system
    - information regarding max number of assets for a giving license
    - information to check whether a license period has expired
    - information to check if there is contribution to OTX
    """
    __WEB_MESSAGES = {"MESSAGE_WIZARD_SHOWN": "00000000000000000000000000010019",
                      "MESSAGE_SENSOR_NOT_INSERTED": "00000000000000000000000000010020",
                      "MESSAGE_TRIAL_EXPIRED": "00000000000000000000000000010021",
                      "MESSAGE_TRIAL_EXPIRES_7DAYS": "00000000000000000000000000010022",
                      "MESSAGE_TRIAL_EXPIRES_2DAYS": "00000000000000000000000000010023",
                      "MESSAGE_LICENSE_VIOLATION": "00000000000000000000000000010024",
                      "MESSAGE_OTX_CONNECTION": "00000000000000000000000000010025",
                      "MESSAGE_BACKUP_RUNNING": "00000000000000000000000000010026"}

    def __init__(self):
        """
            Init method
        """
        Monitor.__init__(self, MonitorTypes.MONITOR_WEBUI_DATA)
        self.message = 'Web UI data monitor started'

    def start(self):
        """ Starts the monitor activity
        """
        try:
            # Remove the previous monitor data.
            self.remove_monitor_data()
            monitor_data = {}
            success, system_id = get_system_id_from_local()
            if not success:
                return False

            # Now
            now = int(time.time())

            # Firstly, wizard data!
            wizard_dict = {}
            success, start_welcome_wizard, welcome_wizard_date = get_wizard_data()
            if not success:
                api_log.error("There was an error retrieving the wizard data")

            wizard_shown = True
            if start_welcome_wizard == 2:
                # if difference between now and welcome_wizard_date is less
                # than a week, display message
                if (now - welcome_wizard_date) < 420:
                    wizard_shown = False

            wizard_dict['wizard_shown'] = wizard_shown
            monitor_data[self.__WEB_MESSAGES['MESSAGE_WIZARD_SHOWN']] = wizard_dict

            # Time to look for orphan sensors
            orphan_sensors_dict = {}
            success, message = check_any_orphan_sensor()
            orphan_sensors = False
            if not success:
                api_log.error(message)
                orphan_sensors = True

            orphan_sensors_dict['orphan_sensors'] = orphan_sensors
            monitor_data[self.__WEB_MESSAGES['MESSAGE_SENSOR_NOT_INSERTED']] = orphan_sensors_dict

            # Has the trial version expired?
            success, expires, message = get_trial_expiration_date()
            trial_expired = False
            trial_expires_7days = False
            trial_expires_2days = False
            if not success:
                rc, pro = system_is_professional()
                if rc:
                    if pro:
                        # OK, we have an error here
                        api_log.error(message)
                    else:
                        pass
            else:
                # expire=9999-12-31
                expiration_date = expires.split('=')[1]
                if expiration_date:
                    mktime_expression = datetime.datetime.strptime(expiration_date,
                                                                   "%Y-%m-%d").timetuple()
                    expires = int(time.mktime(mktime_expression))

                    one_week_left = now - 604800
                    two_days_left = now - 172800

                    if expires < one_week_left:
                        trial_expires_7days = True
                    elif expires < two_days_left:
                        trial_expires_2days = True
                    elif expires < now:
                        trial_expired = True
                    else:
                        pass
                else:
                    if os.path.isfile("/etc/ossim/ossim.lic"):
                        api_log.warning("Valid license but no web admin user found!")
                    else:
                        api_log.debug("Expiration date can't be determined: License file not found")

            monitor_data[self.__WEB_MESSAGES["MESSAGE_TRIAL_EXPIRED"]] = {'trial_checked': success,
                                                                          'trial_expired': trial_expired}
            monitor_data[self.__WEB_MESSAGES["MESSAGE_TRIAL_EXPIRES_7DAYS"]] = {'trial_checked': success,
                                                                                'trial_expired': trial_expires_7days}
            monitor_data[self.__WEB_MESSAGES["MESSAGE_TRIAL_EXPIRES_2DAYS"]] = {'trial_checked': success,
                                                                                'trial_expired': trial_expires_2days}

            # Check max number of assets
            assets = len(get_asset_list())
            contracted_devices = get_license_devices()
            over_assets = False
            exceeding_assets = 0
            # if assets > contracted_devices:
            #    exceeding_assets = assets - contracted_devices
            #    over_assets = True
            monitor_data[self.__WEB_MESSAGES["MESSAGE_LICENSE_VIOLATION"]] = {'over_assets': over_assets,
                                                                              'exceeding_assets': exceeding_assets}

            # OTX contribution
            otx_enabled = apimethod_is_otx_enabled()
            monitor_data[self.__WEB_MESSAGES["MESSAGE_OTX_CONNECTION"]] = {'otx_enabled': otx_enabled}

            # Backup in progress?
            success, running, message = check_backup_process_running()
            if not success:
                api_log.error(message)

            monitor_data[self.__WEB_MESSAGES["MESSAGE_BACKUP_RUNNING"]] = {'backup_check': success,
                                                                           'backup_running': running}

            # Save monitor data
            self.save_data(system_id,
                           ComponentTypes.SYSTEM,
                           self.get_json_message(monitor_data))

        except Exception as err:
            api_log.error("Error processing WebUIData monitor information: %s" % str(err))
            return False
        return True


class MonitorSupportTunnel(Monitor):
    """
        Run monitor every hour. If no ssh up and
        file keys stats > 1 hour kill the tunnel.
        must be checked in all systems.
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_SUPPORT_TUNNELS)
        self.message = 'Support tunnels monitor started'

    def start(self):
        """
            Check in all system if there is a tunnel up and keys exits.
            if keys exists and tunnel down, exec all tunnel shutdown process
        """
        success, systems = ret = get_systems()
        if not success:
            logger.error("Can't get systems list")
            return ret
        result = True
        for (system_id, system_ip) in systems:
            logger.info("Checking supports tunnels in system ('%s','%s')" % (system_id, system_ip))
            success, result = check_support_tunnels(system_ip)
            if not result:
                logger.error("Can't check support tunnel in system ('%s','%s')" % (system_id, system_ip))
            else:
                logger.info("Tunnel in ('%s','%s'): %s" % (system_id, system_ip, result))
        return result, ''


class MonitorSystemRebootNeeded(Monitor):
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_SYSTEM_REBOOT_NEEDED)
        self.message = 'System reboot needed monitor started'

    def start(self):
        """
        Starts the monitor activity
        """
        result, systems = get_systems()
        if not result:
            logger.error("Cannot retrieve system info: %s" % str(systems))
            return False
        self.remove_monitor_data()

        for (system_id, system_ip) in systems:
            result, msg = system_reboot_needed(system_ip)
            if result:
                if not self.save_data(system_id, ComponentTypes.SYSTEM, self.get_json_message({'reboot_needed': msg})):
                    logger.error("Cannot save monitor info")
            else:
                logger.error("Cannot retrieve system {0} information: {1}".format(system_id, msg))

        return True


class MonitorDownloadPulses(Monitor):
    """Periodic task to download pulse information"""

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_DOWNLOAD_PULSES)
        self.message = 'Download OTX Pulse data'

    def start(self):
        """ Starts the monitor activity
        """
        monitor_data = {"pulses_download_fail": False, "old_otx_key": False}

        self.remove_monitor_data()
        success, system_id = get_system_id_from_local()
        if not success:
            return False
        # Load all system from current_local
        logger.info("[MonitorDownloadPulses] downloading pulses started...")

        success, otx_config = apimethod_get_open_threat_exchange_config()
        if success:
            if otx_config["token"]:
                try:
                    otx = OTXv2(key=otx_config["token"])
                    # Checking that the key is an valid OTX v2
                    if otx_config["key_version"] < "2":
                        monitor_data['old_otx_key'] = True

                    otx.download_pulses()
                except Exception, err:
                    logger.error("Cannot Download Pulses: %s" % str(err))
                    monitor_data['pulses_download_fail'] = True
        else:
            logger.error("[MonitorDownloadPulses] Cannot download pulses. <%s> " % str(otx_config))

        self.save_data(system_id,
                       ComponentTypes.SYSTEM,
                       self.get_json_message(monitor_data))
        logger.info("[MonitorDownloadPulses] downloading pulses finished...")

        return True


class MonitorInsecureVPN(Monitor):
    """Periodic task to check if the VPN is insecured"""

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_INSECURE_VPN)
        self.message = 'Check Insecure VPN'

    def start(self):
        """
        Starts the monitor activity
        """
        result, systems = get_systems()
        if not result:
            logger.error("Cannot retrieve system info: %s" % str(systems))
            return False
        self.remove_monitor_data()

        for system_id, system_ip in systems:
            try:
                insecure = ansible_check_insecure_vpn(system_ip)
                if not self.save_data(system_id, ComponentTypes.SYSTEM,
                                      self.get_json_message({'vpn_insecure': insecure})):
                    logger.error("Cannot save monitor info")
            except Exception, exc:
                logger.error("[MonitorInsecureVPN]: %s" % str(exc))

        return True


class MonitorFederatedOTXKey(Monitor):
    """Periodic task to check that all the systems in a federated environment have the same OTX key"""

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_FEDERATED_OTX_KEY)
        self.message = 'Check Insecure VPN'

    def start(self):
        """
        Starts the monitor activity
        """
        result, systems = get_systems('server')
        if not result:
            logger.error("Cannot retrieve system info: %s" % str(systems))
            return False

        self.remove_monitor_data()

        success, main_key = db_get_config('open_threat_exchange_key')
        # if error or otx key is not activated then we don't keep checking.
        if not success or main_key == '':
            return False

        for system_id, system_ip in systems:
            try:
                key = ansible_get_otx_key(system_ip)
                if main_key != key:
                    monitor_data = {'same_otx_key': False}
                    if not self.save_data(system_id,
                                          ComponentTypes.SYSTEM,
                                          self.get_json_message(monitor_data)):
                        logger.error("Cannot save monitor info")
            except Exception, exc:
                logger.error("[MonitorFederatedOTXKey]: %s" % str(exc))

        return True


class MonitorFeedAutoUpdates(Monitor):
    """Periodic task to run automatic feed updates"""

    default_data = {
        'all_updated': False,
        'error_on_update': False,
        'number_of_hosts': 1,
        'update_results': {}
    }

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_FEED_AUTO_UPDATES)
        self.id = MonitorTypes.MONITOR_FEED_AUTO_UPDATES
        self.local_server_ip = '127.0.0.1'
        self.local_server_id = None
        self.local_server_updated = False
        self.monitor_data = self.default_data.copy()
        self.message = 'Run automatic feed updates'

    def check_and_reset_old_data(self):
        if self.monitor_data['all_updated'] and not self.monitor_data['error_on_update']:
            self.monitor_data = self.default_data.copy()

    def get_monitor_data(self):
        try:
            monitor_data = db_get_monitor_data(self.id)
            if monitor_data:
                self.monitor_data = json.loads(monitor_data[0]['data'])
            self.check_and_reset_old_data()
        except Exception as err:
            logger.error("[Feed auto updates] Failed to get monitor's data: %s" % str(err))

        return self.monitor_data

    def update_monitors_data_with_results(self, update_job_results, systems):
        """ Updates monitor with data about current feed update.

        Args:
            update_job_results (list): results of feed updates on different hosts
            systems (dict): hosts connected
        """
        results_dict = {}
        system_ip_to_id_map = dict((v, k) for k, v in systems.iteritems())

        # Reset to defaults
        self.monitor_data['error_on_update'] = False
        self.monitor_data['all_updated'] = False

        for host_result in update_job_results:
            # Convert list of results to dict for each ip
            system_ip = host_result.pop('system_ip')
            host_result['system_id'] = system_ip_to_id_map.get(system_ip)
            host_result['updated_at'] = datetime.datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")
            results_dict[system_ip] = host_result

        self.monitor_data['update_results'].update(results_dict)

        for system_ip, upd_data in self.monitor_data['update_results'].iteritems():
            # Check if there was failed update attempt
            if not upd_data.get('result'):
                self.monitor_data['error_on_update'] = True
            # If result is True and system_id == server_id notify that server was updated.
            elif upd_data.get('system_id') == self.local_server_id:
                self.local_server_updated = True

        # Check if all hosts were updated and at least one of them by auto-updates (to show message later).
        if not self.monitor_data['error_on_update']:
            self.monitor_data['all_updated'] = (
                len(self.monitor_data['update_results']) >= self.monitor_data['number_of_hosts'])

    @staticmethod
    def has_pending_feed_updates(system_id):
        """ Checks if given system_id has pending feed updates.

        Args:
            system_id: (uuid) System ID.

        Returns: True if has pending updates or False otherwise.
        """
        pending_feed_updates = False
        status, result = apimethod_get_remote_software_update(system_id, no_cache=True)

        if status:
            pending_feed_updates = result.get(system_id, {}).get('packages', {}).get('pending_feed_updates')
        else:
            logger.error('[MonitorFeedAutoUpdates] Failed to get status of remote updates: {}'.format(str(result)))

        return pending_feed_updates

    @staticmethod
    def system_could_be_updated_by_schedule(system_ip, scheduled_hour):
        time_to_update = False

        status_ok, system_local_hour = get_local_time(system_ip, date_fmt='%H')
        logger.info('[MonitorFeedAutoUpdates] System ({}) - local time is {}'.format(system_ip, system_local_hour))
        if status_ok and int(system_local_hour) == int(scheduled_hour):
            time_to_update = True

        return time_to_update

    def get_connected_systems_with_pending_updates(self):
        """
        Returns dict of the connected systems that have pending feed updates.
        """
        systems = {}

        # ATM get connected sensors only, whe can't update other servers
        success_get, connected_systems = get_systems(
            system_type="Sensor",
            convert_to_dict=True,
            exclusive=True,
            directly_connected=True
        )
        if not success_get:
            logger.error('[MonitorFeedAutoUpdates] Failed to get connected systems: {}'.format(connected_systems))
            connected_systems = {}
        else:
            logger.info('[MonitorFeedAutoUpdates] Connected systems: {}'.format(connected_systems))

        for system_id, system_ip in connected_systems.iteritems():
            if self.has_pending_feed_updates(system_id):
                logger.info('[MonitorFeedAutoUpdates] {} has pending updates'.format(system_ip))
                systems.update({system_id: system_ip})

        return systems

    def get_list_of_scheduled_update_tasks_for_systems(self, systems, scheduled_hour):
        """ Get list of task for systems that have pending updates and meet the schedule.
        Args:
            systems: (dict) {system_id: system_id,...}
            scheduled_hour: (int) Hour in local timezone when update should be performed.
        Returns:
            List of the celery tasks (in canvas representation).
        """
        pending_updates = []

        for idx, system_id in enumerate(systems, start=1):
            system_ip = systems[system_id]
            try:
                if self.system_could_be_updated_by_schedule(system_ip, scheduled_hour):
                    # http://docs.celeryproject.org/en/latest/userguide/canvas.html#signatures
                    upd_task = alienvault_asynchronous_update.s(system_ip, only_feed=True)

                    # Small offset added because sometimes job returns log file with same timestamp for all updates.
                    # And it could cause unexpected side-effects.
                    upd_task.set(countdown=idx * 2)
                    pending_updates.append(upd_task)
            except Exception as exc:
                logger.error("[MonitorFeedAutoUpdates] Failed to get list of update tasks: {}".format(str(exc)))
        return pending_updates

    def start(self):
        """ Starts the monitor activity. """
        auto_updates_enabled, scheduled_hour = get_feed_auto_update()
        if not auto_updates_enabled or scheduled_hour is None:
            return False

        success_get, local_server_id = get_system_id_from_local()
        if not success_get:
            logger.error('[MonitorFeedAutoUpdates] Cannot retrieve local system_id: {}'.format(local_server_id))
            return False

        logger.info('[MonitorFeedAutoUpdates] Scheduled time for auto-updates is: {}'.format(scheduled_hour))
        self.local_server_id = local_server_id
        self.get_monitor_data()  # Loads the data from the DB into monitor
        self.remove_monitor_data()  # Clean DB data

        # Check pending packages for local server
        self.local_server_updated = not self.has_pending_feed_updates(local_server_id)
        # Try to update it first and then recheck the status
        if not self.local_server_updated and self.system_could_be_updated_by_schedule(self.local_server_ip,
                                                                                      scheduled_hour):
            local_server_upd_result = alienvault_asynchronous_update.delay(self.local_server_ip, only_feed=True).wait()
            self.update_monitors_data_with_results([local_server_upd_result, ], {local_server_id: self.local_server_ip})

        # Update connected systems only when server updated
        if self.local_server_updated:
            systems_to_update = self.get_connected_systems_with_pending_updates()
            pending_updates = self.get_list_of_scheduled_update_tasks_for_systems(systems_to_update, scheduled_hour)
            self.monitor_data['number_of_hosts'] = len(systems_to_update) + 1  # +1 to include local server

            if pending_updates:
                logger.info('[MonitorFeedAutoUpdates] Pending update tasks: {}'.format(pending_updates))
                job_results = group(pending_updates)().join()
                self.update_monitors_data_with_results(job_results, systems_to_update)

        self.save_data(local_server_id, ComponentTypes.SYSTEM, self.get_json_message(self.monitor_data))

        return True
