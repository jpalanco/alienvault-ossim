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

import glob
from itertools import chain

import celery.utils.log
from ansiblemethods.system.about import get_is_professional
from ansiblemethods.system.util import rsync_push
from api.lib.monitors.monitor import Monitor, MonitorTypes, ComponentTypes
from apiexceptions import APIException
from apimethods.sensor.network import get_network_stats
from apimethods.sensor.plugin import (get_plugin_package_info_from_sensor_id,
                                      get_plugin_package_info_local,
                                      check_plugin_integrity,
                                      get_sensor_plugins,
                                      get_sensor_plugins_enabled_by_asset)
from apimethods.utils import compare_dpkg_version
from db.methods.host import update_host_plugins
from db.methods.system import (get_systems,
                               get_sensor_id_from_system_id,
                               get_system_ip_from_local)

logger = celery.utils.log.get_logger("celery")


class MonitorSensorDroppedPackages(Monitor):
    """Class to monitor dropped packets on every sensor in the system"""
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_DROPPED_PACKAGES)
        self.message = 'Sensor Dropped Packets monitor started'

    def start(self):
        """
            Starts the monitor activity
        """
        rt = True
        try:
            self.remove_monitor_data()
            logger.info("Monitor %s Working..." % self.monitor_id)
            rc, sensor_list = get_systems(system_type="Sensor")
            if not rc:
                logger.error("Can't retrieve sensor list: %s" % str(sensor_list))
                return False
            for (sensor_id, sensor_ip) in sensor_list:
                if sensor_id == '':
                    logger.warning("Sensor (%s) ID not found" % sensor_ip)
                    continue
                logger.info("Getting dropped packets for sensor_ip %s" % sensor_ip)
                sensor_stats = get_network_stats(sensor_ip)
                # print sensor_stats
                try:
                    packet_lost_average = sensor_stats["contacted"][sensor_ip]["stats"]["packet_lost_average"]
                    monitor_data = {'packet_loss': packet_lost_average}
                    logger.info("Lost packet average for sensor: %s =  %s" % (sensor_ip, packet_lost_average))
                    # Save data component_id = canonical uuid
                    if not self.save_data(sensor_id, ComponentTypes.SENSOR, self.get_json_message(monitor_data)):
                        logger.error("Can't save monitor info")
                except KeyError:
                    pass

        except Exception, e:
            logger.error("Something wrong happen while running the monitor..%s, %s" % (self.get_monitor_id(),
                                                                                       str(e)))
            rt = False

        return rt


class MonitorPluginsVersion(Monitor):
    """
        Contact with each sensor, download the alienvault-plugins packages, compare
        version with the local alienvault-plugins-sid package and store data in
        monitor data
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_PLUGINS_VERSION)
        self.message = 'Sensor Plugin Monitor info started'

    def start(self):
        """
            Start monitor
        """
        rt = True
        try:
            self.remove_monitor_data()
            logger.info("Monitor %s Working..." % self.monitor_id)
            rc, sensor_list = get_systems(system_type="Sensor")
            (success, version) = get_plugin_package_info_local()
            if not success:
                raise Exception(str(version))
            (success, local_version) = get_plugin_package_info_local()
            for (system_id, _) in sensor_list:
                (success, sensor_id) = get_sensor_id_from_system_id(system_id)
                # logger.info("INFO => " + str(sensor_id))
                if success:
                    if sensor_id == '':
                        logger.warning("Sensor (%s) ID not found" % sensor_id)
                        continue
                    (success, info) = get_plugin_package_info_from_sensor_id(sensor_id)
                    if success:
                        if info['version'] != '':
                            data_sensor = {'version': info['version'],
                                           'md5': info['md5'],
                                           'comparison': compare_dpkg_version(info['version'], local_version['version'])}
                        else:
                            data_sensor = {'version': info['version'],
                                           'md5': info['md5'],
                                           'comparison': ''}
                        if not self.save_data(sensor_id, ComponentTypes.SENSOR, self.get_json_message(data_sensor)):
                            logger.error("Can't save monitor info for sensor '%s'" % sensor_id)
                    else:
                        logger.warning("Can't obtain plugin version for sensor '%s'", sensor_id)
                else:
                        logger.warning("Can't obtain sensor_id for system_id '%s'", system_id)

        except Exception, e:
            logger.error("Something wrong happen while running the monitor..%s, %s" % (self.get_monitor_id(),
                         str(e)))
            rt = False
        return rt


class MonitorPluginIntegrity(Monitor):
    """
        Check if installed sensor plugins and sensor rsyslog files have been modified or removed locally
    """
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_PLUGINS_CHECK_INTEGRITY)
        self.message = 'Plugin Integrity Monitor started'

    def start(self):
        """ Starts the monitor activity
        """
        #Remove the previous monitor data.
        self.remove_monitor_data()

        success, local_ip = get_system_ip_from_local(local_loopback=False)
        if not success:
            logger.error("Cannot retrieve local system IP: %s" % str(local_ip))
            return False

        # Check if this is professional or not.
        success, is_pro = get_is_professional(local_ip)
        if not (success and is_pro):
            return True

        # Iterate over the sensors.
        result, systems = get_systems(system_type="Sensor")

        if not result:
            logger.error("Can't retrieve the system info: %s" % str(systems))
            return False

        for (system_id, system_ip) in systems:
            (success, info) = check_plugin_integrity(system_id)

            if success:
                try:
                    #Create the JSON data to store the monitor info
                    monitor_data = info

                    #Save the data to the monitor_data table
                    self.save_data(system_id, ComponentTypes.SENSOR, self.get_json_message(monitor_data))
                except Exception as e:
                    logger.error("[MonitorPluginIntegrity] Error: %s" % str(e))
            else:
                logger.error("Can't obtain integrity plugin information from system '%s'", system_id)

        return True


class MonitorUpdateHostPlugins(Monitor):
    """
        Check all /etc/ossim/agent/config.yml and update host_scan table
    """
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_UPDATE_HOST_PLUGINS)
        self.message = 'Update Host Plugins Monitor started'

    def start(self):
        """ Starts the monitor activity
        """
        # Remove the previous monitor data.
        self.remove_monitor_data()

        # Iterate over the sensors.
        success, systems = get_systems(system_type="Sensor")

        if not success:
            logger.error("[MonitorUpdateHostPlugins] "
                         "Can't retrieve the system info: {0}".format(str(systems)))
            return False

        assets = {}
        for (system_id, system_ip) in systems:
            success, sensor_id = get_sensor_id_from_system_id(system_id)
            if not success:
                logger.error("[MonitorUpdateHostPlugins] "
                             "Can't resolve senor_id of system {0}: {1}".format(system_id, sensor_id))
                continue

            try:
                sensor_plugins = get_sensor_plugins_enabled_by_asset(sensor_id=sensor_id, no_cache=True)
            except APIException as e:
                logger.error("[MonitorUpdateHostPlugins] "
                             "Can't obtain plugin information from system {0}: {1}".format(system_id, str(e)))
                continue

            # Add asset plugin sids to assets list
            try:
                for asset, asset_plugins in sensor_plugins['plugins'].iteritems():
                    if asset not in assets:
                        assets[asset] = []
                    assets[asset] += [plugin['plugin_id'] for plugin in asset_plugins.values()]
            except KeyError as e:
                logger.warning("[MonitorUpdateHostPlugins] "
                               "Bad format in plugins enabled by asset: {0}".format(str(e)))

        success, msg = update_host_plugins(data=assets)
        if not success:
            logger.error("[MonitorUpdateHostPlugins] "
                         "Can't update host plugin information: {0}".format(msg))
            return False

        return True


class MonitorEnabledPluginsLimit(Monitor):
    """
        Check number of enabled plugins per sensor
    """
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_ENABLED_PLUGINS_LIMIT)
        self.message = 'Enabled Plugins Limit Monitor started'

    def start(self):
        """ Starts the monitor activity """
        # Remove the previous monitor data.
        self.remove_monitor_data()

        # Iterate over the sensors.
        success, systems = get_systems(system_type="Sensor")

        if not success:
            logger.error("[MonitorEnabledPluginsLimit] Can't retrieve the system info: {0}".format(str(systems)))
            return False

        for (system_id, system_ip) in systems:
            success, sensor_id = get_sensor_id_from_system_id(system_id)
            if not success:
                logger.error("[MonitorEnabledPluginsLimit] "
                             "Can't resolve sensor_id of system {0}: {1}".format(system_id, sensor_id))
                continue

            try:
                sensor_plugins = get_sensor_plugins(sensor_id=sensor_id, no_cache=True)
                max_limit_threshold = sensor_plugins.get('max_allowed')
                enabled_plugins = sensor_plugins.get('enabled', {})
                enabled_global_count = len(enabled_plugins.get('detectors', []))
                enabled_per_asset_count = len(list(chain.from_iterable(enabled_plugins.get('devices', {}).values())))
                enabled_total = enabled_global_count + enabled_per_asset_count
                warning_threshold = int((max_limit_threshold * 85) / 100)

                plugin_allowed_to_add = max_limit_threshold - enabled_total
                if plugin_allowed_to_add < 0:
                    plugin_allowed_to_add = 0

                monitor_data = {
                    'system_id': system_id,
                    'system_ip': system_ip,
                    'max_limit_threshold': max_limit_threshold,
                    'plugins_enabled_total': enabled_total,
                    'plugins_allowed_to_add': plugin_allowed_to_add,
                    'limit_reached': enabled_total >= max_limit_threshold,
                    'warning_reached': (warning_threshold <= enabled_total) and (enabled_total < max_limit_threshold)
                }
                if not self.save_data(sensor_id, ComponentTypes.SENSOR, self.get_json_message(monitor_data)):
                    logger.error("[MonitorEnabledPluginsLimit] Cannot save monitor info")
            except APIException as e:
                logger.error("[MonitorEnabledPluginsLimit] "
                             "Can't obtain plugin information from system {0}: {1}".format(system_id, str(e)))
                continue

        return True


class MonitorSyncCustomPlugins(Monitor):
    """
    rsync all the custom_plugins over all connected sensors
    """
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_SYNC_CUSTOM_PLUGINS)
        self.message = "Sync Custom Plugins Monitor started"

    def start(self):
        """ Starts the monitor activity
        """
        # Remove the previous monitor data.
        self.remove_monitor_data()

        # Check if we have custom plugins. We need only cfg
        local_path = "/etc/alienvault/plugins/custom/"
        plugins_to_sync = glob.glob(local_path + '*.cfg')
        if not plugins_to_sync:
            logger.info('Nothing to sync...')
            return True

        # Iterate over the sensors.
        # We should specify exclusive=True to fetch only sensors instead of
        # every machine with sensor profile
        result, systems = get_systems(system_type="Sensor", exclusive=True)

        if not result:
            logger.error("[MonitorSyncCustomPlugins] Can't retrieve the system info: {}".format(str(systems)))
            return False

        for (system_id, system_ip) in systems:
            for plugin_file_path in plugins_to_sync:
                success, msg = rsync_push(local_ip="127.0.0.1",
                                          remote_ip=system_ip,
                                          local_file_path=plugin_file_path,
                                          remote_file_path=plugin_file_path)
                if not success:
                    logger.error("[MonitorSyncCustomPlugins] Can't rsync with {}".format(system_ip))
                    return False
        return True
