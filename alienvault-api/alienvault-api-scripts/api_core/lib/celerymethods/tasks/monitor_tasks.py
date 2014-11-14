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

from celery.utils.log import get_logger
from celerymethods.tasks import celery_instance

from api.lib.monitors.sensor import MonitorSensorLocation,\
                                    MonitorSensorIDS,\
                                    MonitorVulnerabilityScans,\
                                    MonitorSensorDroppedPackages
from api.lib.monitors.assets import MonitorSensorAssetLogActivity

from api.lib.monitors.server import MonitorServerSensorActivity ,\
                                    MonitorServerServerActivity

from api.lib.monitors.system import MonitorSystemCPULoad,MonitorDiskUsage,MonitorSystemDNS,MonitorRemoteCertificates,\
    MonitorRetrievesRemoteInfo, MonitorPendingUpdates



logger = get_logger("celery")


@celery_instance.task
def get_sensor_without_location():
    """Task to run periodically."""
    logger.info("Monitor get_sensor_without_location... started")
    rt = False
    monitor = MonitorSensorLocation()
    if monitor.start():
        rt = True
    logger.info("Monitor get_sensor_without_location... finished")
    return rt

@celery_instance.task
def monitor_sensor_ids():
    """Monitors sensor IDS

    :return: True on successful, False otherwise
    """
    logger.info("Monitor sensor services... started")
    rt = False
    monitor = MonitorSensorIDS()
    if monitor.start():
        rt = True
    logger.info("Monitor sensor services finished")
    return rt

@celery_instance.task
def monitor_sensor_vulnerability_scan_scheduled():
    """Monitors if the sensor IDS services are producing events

    :return: True on successful, False otherwise
    """
    logger.info("Monitor sensor Sensor Vulnerability... started")
    monitor = MonitorVulnerabilityScans()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor sensor Sensor Vulnerability... finished")
    return rt

@celery_instance.task
def monitor_server_sensor_activity ():
    """Monitor the activity between a sensor and a server.

    :return: True on successful, False otherwise
    """
    logger.info("Monitor Server-Sensor activity started")
    monitor = MonitorServerSensorActivity()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor Server-Sensor activity stopped")
    return rt

@celery_instance.task
def monitor_server_server_activity ():
    """Monitor the activity between two servers.

    :return: True on successful, False otherwise
    """
    logger.info("Monitor Server-Server activity started")
    monitor = MonitorServerServerActivity()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor Server-Server activity stopped")
    return rt

@celery_instance.task
def monitor_system_disk_usage():
    """Monitor the disk usage of a server

    :return: True on successful, False otherwise
    """
    logger.info("Monitor System Disk Usage started")
    monitor = MonitorDiskUsage()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor System Disk Usage stopped")
    return rt


@celery_instance.task
def monitor_system_cpu_load():
    """
        Monitor de CPU load 
    :return: True on successful, False otherwise
    """
    logger.info("Monitor System CPU Load started")
    monitor = MonitorSystemCPULoad()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor System CPU Load stopped")
    return rt

@celery_instance.task
def monitor_sensor_dropped_packages():
    """Monitor de CPU load
    :return: True on successful, False otherwise
    """
    logger.info("Monitor Sensor Dropped Packages Load started")
    monitor = MonitorSensorDroppedPackages()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor Sensor Dropped Packages stopped")
    return rt

@celery_instance.task
def monitor_asset_log_activity():
    """Monitor de CPU load
    :return: True on successful, False otherwise
    """
    logger.info("Monitor MonitorSensorAssetLogActivity Load started")
    monitor = MonitorSensorAssetLogActivity()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor MonitorSensorAssetLogActivity stopped")
    return rt

@celery_instance.task
def monitor_system_dns():
    """Monitor de DNS configuration
    :return: True on  successful, False otherwise
    """
    logger.info("Monitor MonitorSystemDNS started")
    monitor = MonitorSystemDNS()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor MonitorSystemDNS stopped")
    return rt

@celery_instance.task
def monitor_remote_certificates():
    """Monitor de Remote Certificates
    :return: True on  successful, False otherwise
    """
    logger.info("Monitor MonitorRemoteCertificates started")
    monitor = MonitorRemoteCertificates()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor MonitorRemoteCertificates stopped")
    return rt


@celery_instance.task
def monitor_retrieves_remote_info():
    """Monitor de Remote Certificates
    :return: True on  successful, False otherwise
    """
    logger.info("Monitor MonitorRetrievesRemoteInfo started")
    monitor = MonitorRetrievesRemoteInfo()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor MonitorRetrievesRemoteInfo stopped")
    return rt


@celery_instance.task
def monitor_check_pending_updates():
    """Monitor to check for pending updates

    Returns:
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorPendingUpdates started")
    monitor = MonitorPendingUpdates()
    rt = False

    if monitor.start():
        rt = True

    logger.info("Monitor MonitorPendingUpdates stopped")
    return rt
