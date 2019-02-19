# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2015 AlienVault
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

import time
import json
import urllib2
import requests
import celery.utils.log

from api.lib.monitors.monitor import (Monitor,
                                      MonitorTypes,
                                      ComponentTypes)
from ansiblemethods.system.system import get_doctor_data
from db.methods.system import get_systems, get_system_id_from_local, get_system_ip_from_system_id
from apimethods.system.proxy import AVProxy

logger = celery.utils.log.get_logger("celery")

PROXY = AVProxy()
if PROXY is None:
    logger.error("Connection error with AVProxy")


class MonitorPlatformTelemetryData(Monitor):
    """
    Get platform telemetry data using the AV Doctor.
    This basically runs the Doctor on all suitable systems, and delivers the
    output data to a server.
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_PLATFORM_TELEMETRY_DATA)
        self.message = 'Platform Telemetry Data Monitor Enabled'
        self.__strike_zone_plugins = ['0005_agent_plugins_exist.plg',
                                      '0006_agent_plugins_integrity.plg',
                                      '0008_agent_rsyslog_conf_integrity.plg',
                                      '0009_alienvault_dummies.plg',
                                      '0013_bash_history.plg',
                                      '0018_current_network_config.plg',
                                      '0019_licensed_devices.plg',
                                      '0025_default_hw.plg',
                                      '0026_default_repositories.plg',
                                      '0027_default_server_packages.plg',
                                      '0029_disk_size.plg',
                                      '0031_hosts_file.plg',
                                      '0033_kernel_configuration.plg',
                                      '0034_mysql_history.plg',
                                      '0035_netlink_status.plg',
                                      '0037_network_routing.plg',
                                      '0041_pkg_checksum.plg',
                                      '0045_resolv_file.plg',
                                      '0047_schema_version.plg',
                                      '0054_unsupported_installation.plg',
                                      '0056_vm_requirements.plg']

    def __check_internet_connection__(self, url='https://telemetry.alienvault.com:443'):
        """
        Checks there is connection with the telemetry server
        """
        try:
            request = urllib2.Request(url)
            request.add_header('pragma', 'no-cache')
            response = PROXY.open(request, timeout=30, retries=1)
        except Exception as e:
            # 404 means there is connection with the server
            if str(e) == "HTTP Error 404: NOT FOUND":
                pass
            else:
                return False
        return True

    def __send_data__(self, system_id, data, url='https://telemetry.alienvault.com:443'):
        """
        Sends the collected data.
        """
        try:
            payload = json.dumps({'data': data})
            url = url + '/%s/%s/%s' % ('platform_report', system_id, int(time.time()))
            request = urllib2.Request(url)
            request.add_data(payload)
            request.add_header('Content-Type', 'application/json')
            response = PROXY.open(request, timeout=10)

        except Exception as e:
            logger.error('Error sending telemetry data: %s' % str(e))
            return False
        return True

    def start(self):
        """
        Starts the monitor activity

        :return: True on success, False otherwise
        """
        self.remove_monitor_data()
        monitor_data = {}

        success, system_id = get_system_id_from_local()
        if not success:
            return False

        # Just return if there is no internet connection.
        if not self.__check_internet_connection__():
            logger.error("Cannot connect to the Telemetry Server")
            monitor_data['telemetry_server_connectivity'] = False
            self.save_data(system_id,
                           ComponentTypes.SYSTEM,
                           self.get_json_message(monitor_data))
            return True

        # Find the list of connected systems.
        (result, sensor_dict) = get_systems('Sensor', convert_to_dict=True, exclusive=True)
        if not result:
            logger.error("Cannot retrieve connected sensors")
            return False
        (result, database_dict) = get_systems('Database', convert_to_dict=True, exclusive=True)
        if not result:
            logger.error("Cannot retrieve connected databases")
            return False
        system_dict = dict(sensor_dict, **database_dict)

        result, local_system_id = get_system_id_from_local()
        if not result:
            logger.error("Cannot retrieve the local id")
            return False
        result, local_system_ip = get_system_ip_from_system_id(local_system_id)
        if not result:
            logger.error("Cannot retrieve the local IP address")
            return False
        system_dict = dict({local_system_id: local_system_ip}, **system_dict)

        args = {'output_type': 'ansible',
                'plugin_list': ','.join(self.__strike_zone_plugins),
                'verbose': 2}
        ansible_output = get_doctor_data(system_dict.values(), args)
        if ansible_output.get('dark'):
            logger.error('Cannot collect telemetry data: %s' % str(ansible_output.get('dark')))
            return False

        return self.__send_data__(local_system_id, ansible_output)
