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
from datetime import datetime, timedelta
import re
import traceback
import json
import uuid

from api.lib.monitors.monitor import Monitor, MonitorTypes, ComponentTypes
from db.methods.system import get_systems
from db.methods.data import get_timestamp_last_event_for_each_device, get_asset_id_from_ip
from ansiblemethods.sensor.ossec import get_ossec_agent_data
from ansiblemethods.sensor.plugin import get_plugin_enabled_by_sensor
from ansiblemethods.sensor.log import get_devices_logging, get_network_devices_for_sensor

import celery.utils.log

logger = celery.utils.log.get_logger("celery")

ossec_pattern = re.compile(".*ID:\s(?P<agent_id>\d{3}).*Name:\s(?P<agent_name>\S+).*IP:\s(?P<agent_ip>\S+).*")


def has_an_ossec_agent_active(asset_ips, sensors_ip):
    """Check if an asset ip has some ossec_agent_active"""
    rt = True
    args = {'list_available_agents': '1', 'command': "manage_agents"}
    data = get_ossec_agent_data([sensors_ip], args)
    if "failed" in data:
        logger.error("Error retrieving data from OSSEC. %s" % data)
        rt = False
    else:
        ossec_agent_connected = {}
        for key, value in data['contacted'].iteritems():
            # For each sensor, check whether the asset is connect to it.
            # data = u'\nAvailable agents: \n   ID: 001, Name: agent_w7, IP: 192.168.5.188\n\n'
            output = value['data'].split('\n')
            for line in output:
                result = ossec_pattern.match(line)
                if result:
                    result_dict = result.groupdict()
                    if 'agent_ip' in result_dict:
                        ossec_agent_connected[str(result_dict['agent_ip'])] = result_dict
        for ip in asset_ips:
            if ip in ossec_agent_connected:
                rt = True
                break
    return rt


def has_wmi_plugin_active(asset_ips, sensor_ip):
    """Check whether the given asset in the given sensor has some wmi plugin enabled
    :param asset_ips (list<"Dotted IP Address"> Asset's IP addresses
    :param sensor_ip: Sensor IP"""
    pass


class MonitorSensorAssetLogActivity(Monitor):
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_ASSET_LOG_ACTIVITY)
        self.message = 'Sensors events in the last two hours'

    def start(self):
        """
        Starts the monitor activity

        Get if an asset is sending logs to the system
        Get if an asset received log are parsed by a plugin

        Messages:
        Generate a warning message when an asset which has sent logs does not send an event in 24 hours
        Generate an info message when an asset is not sending logs to the system
        Generate an info message when an asset is sending logs but there is no plugin enable parsing the logs
        :return: True on success, False otherwise

        monitor_data = { "has_events" : True|False
                         "last_event_arrival": time in seconds from the last event
                         "has_logs" = True|False
                         "enabled_plugin" = True | False
                       }
        """

        rt = True
        try:
            # 1 - Remove old monitor data
            self.remove_monitor_data()
            # 2 - Get the sensor list
            rc, all_sensor_list = get_systems(system_type="Sensor")
            if not rc:
                logger.error("Can't retrieve sensor list: %s" % str(all_sensor_list))
                return False
            # Store those logs that are coming from a known device (var/log/alienvault/devices)
            log_files_per_sensor = {}
            # List of plugins enabled by sensor
            plugins_enabled_by_sensor = {}
            # List of last events for each device
            last_event_per_host = get_timestamp_last_event_for_each_device()

            # For each sensor we will get the list of devices that are reporting to it,
            # and the list of active plugins with locations.
            device_list = {}
            for (sensor_id, sensor_ip) in all_sensor_list:
                logger.info("Monitor asset activity... assets for sensor %s" % sensor_ip)

                # Retrieves the list of devices with its logs:
                # 192.168.2.2: /var/log/alienvault/devices/192.168.2.2/192.168.2.2.log

                # #10576 -  log_files_per_sensor[sensor_ip] =  get_hosts_in_syslog(sensor_ip)
                sensor_devices_logging = get_devices_logging(sensor_ip)
                log_files_per_sensor[sensor_ip] = sensor_devices_logging

                # Retrieves the plugins enabled in each sensor.
                response = get_plugin_enabled_by_sensor(sensor_ip)

                tmp_hash = {}
                if "contacted" in response:
                    if sensor_ip in response['contacted'] and 'data' in response['contacted'][sensor_ip]:
                        tmp_hash = response['contacted'][sensor_ip]['data']
                plugins_enabled_by_sensor[sensor_ip] = tmp_hash  # plugin_name = [log_file]

                # Retrieve network devices logging to sensor
                sensor_devices_list = get_network_devices_for_sensor(sensor_ip)
                if sensor_devices_list:
                    for device_id, device_ip in sensor_devices_list.iteritems():
                        if device_id and device_ip and device_id not in device_list:
                            device_list[device_id] = device_ip

                # The add logging devices that are not yet present in the device list
                # only if we can find the device_id in the database
                for asset_ip in sensor_devices_logging:
                    if asset_ip not in device_list.values():
                        success, asset_id = get_asset_id_from_ip(asset_ip, sensor_ip)
                        if success:
                            device_list[asset_id] = asset_ip

            # Sensors table has the vpn ip if it exists
            # #10576 -  asset_list = get_asset_list()
            # device_list = get_device_list()
            logger.info("Asset activity .... lets start working")
            n_devices = 0
            for device_id, device_ip in device_list.iteritems():
                device_id_uuid = uuid.UUID(device_id)
                n_devices += 1
                if n_devices % 1000 == 0:
                    logger.info("Number of assets that have been analyzed.. %s" % n_devices)
                monitor_data = {}
                # Get uuid string from bytes. -> ASSET_ID
                # asset_id = get_uuid_string_from_bytes(asset.id)
                # GET List of assets IPS
                # asset_ips = [get_ip_str_from_bytes(host.ip) for host in asset.host_ips]

                has_events = False
                has_logs = False
                num_of_enabled_plugins = 0
                last_event_arrival = 0

                now = datetime.utcnow()
                device_id_str_with_no_hyphen = device_id_uuid.hex.upper()
                # Are there any events in the database coming from this device?
                if device_id_str_with_no_hyphen in last_event_per_host:
                    has_events = True
                    has_logs = True
                    num_of_enabled_plugins = 1
                    # Time in seconds since the last event
                    event_date = last_event_per_host[device_id_str_with_no_hyphen]
                    td = (now - datetime(year=event_date.year,
                                         month=event_date.month,
                                         day=event_date.day))
                    # Is it been over 24 hours since the arrival of the latest event coming from this device?
                    last_event_arrival = int(td.total_seconds())
                else:
                    # No events coming from this device
                    has_events = False
                    # Is the device sending logs to the system?
                    # We will check if there are log coming from this device in each sensor
                    for sensor_ip, log_files in log_files_per_sensor.iteritems():
                        enabled_plugins = json.loads(str(plugins_enabled_by_sensor[sensor_ip]))
                        locations = []  # List of plugin locations
                        for plugin, location in enabled_plugins.iteritems():
                            locations.extend(location.split(','))
                        device_logs = []
                        # An asset could have more than one IP address.
                        # We should check each of those IP addresses
                        # One device - one ip
                        # for ip in asset_ips:
                        if device_ip in log_files:
                            device_logs.extend(log_files[device_ip])
                            has_logs = True
                        for log in device_logs:
                            if log in locations:
                                num_of_enabled_plugins += 1

                monitor_data['has_events'] = has_events
                monitor_data['last_event_arrival'] = last_event_arrival
                monitor_data['has_logs'] = has_logs
                monitor_data['enabled_plugin'] = True if num_of_enabled_plugins > 0 else False
                logger.info("Device Monitor: %s" % str(monitor_data))
                self.append_monitor_object(str(device_id_uuid), ComponentTypes.HOST,
                                           self.get_json_message(monitor_data))

            # Commit all objects
            logger.info("Monitor Done.. Committing objects")
            self.commit_data()

        except Exception, e:
            rt = False
            logger.error("Something wrong happen while running the monitor..%s, %s, %s" % (self.get_monitor_id(),
                                                                                           str(e),
                                                                                           traceback.format_exc()))
        return rt
