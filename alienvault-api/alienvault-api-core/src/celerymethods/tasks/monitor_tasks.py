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
from celery_once.tasks import QueueOnce
from celerymethods.tasks import celery_instance

from api.lib.monitors.sensor import (MonitorSensorLocation,
                                     MonitorSensorIDS,
                                     MonitorVulnerabilityScans,
                                     MonitorSensorDroppedPackages,
                                     MonitorPluginsVersion,
                                     MonitorPluginIntegrity,
                                     MonitorUpdateHostPlugins,
                                     MonitorEnabledPluginsLimit,
                                     MonitorSyncCustomPlugins,
                                     )

from api.lib.monitors.assets import MonitorSensorAssetLogActivity

from api.lib.monitors.server import (MonitorServerSensorActivity,
                                     MonitorServerServerActivity,
                                     MonitorServerEPSStats)

from api.lib.monitors.system import (MonitorSystemCPULoad,
                                     MonitorDiskUsage,
                                     MonitorSystemDNS,
                                     MonitorRemoteCertificates,
                                     MonitorRetrievesRemoteSysStatus,
                                     MonitorRetrievesRemoteInfo,
                                     MonitorUpdateSysWithRemoteInfo,
                                     MonitorPendingUpdates,
                                     MonitorDownloadMessageCenterMessages,
                                     MonitorSystemCheckDB,
                                     MonitorWebUIData,
                                     MonitorSupportTunnel,
                                     MonitorSystemRebootNeeded,
                                     MonitorDownloadPulses,
                                     MonitorInsecureVPN,
                                     MonitorFederatedOTXKey,
                                     MonitorFeedAutoUpdates)

from api.lib.monitors.doctor import MonitorPlatformTelemetryData

from apimethods.otx.otx import apimethod_is_otx_enabled
from apimethods.system.status import system_status

from db.methods.system import get_system_id_from_local

from apiexceptions.system import APICannotRetrieveSystems

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
def monitor_server_sensor_activity():
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
def monitor_server_server_activity():
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
def monitor_retrieves_remote_status():
    """Monitor to retrieve remote system status
    :return: True on  successful, False otherwise
    """
    logger.info("Monitor MonitorRetrievesRemoteSysStatus started")
    monitor = MonitorRetrievesRemoteSysStatus()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor MonitorRetrievesRemoteSysStatus stopped")
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
def monitor_update_system_with_remote_info():
    """Monitor to retrieve remote system status
    :return: True on  successful, False otherwise
    """
    logger.info("Monitor MonitorUpdateSysWithRemoteInfo started")
    monitor = MonitorUpdateSysWithRemoteInfo()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor MonitorUpdateSysWithRemoteInfo stopped")
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


@celery_instance.task
def monitor_plugins_version():
    """Monitor to check the plugin versions

    Returns:
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorPluginsVersion started")
    monitor = MonitorPluginsVersion()
    rt = False

    if monitor.start():
        rt = True

    logger.info("Monitor MonitorPluginVersions stopped")
    return rt


@celery_instance.task
def monitor_check_plugin_integrity():
    """Monitor to check plugin integrity (if installed agent plugins and agent rsyslog files have been modified or removed locally)
    Returns:
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorPluginIntegrity started")
    monitor = MonitorPluginIntegrity()
    rt = False

    if monitor.start():
        rt = True

    logger.info("Monitor MonitorPluginIntegrity stopped")

    return rt


@celery_instance.task
def monitor_check_platform_telemetry_data():
    """
    Uses the AV Doctor to get data from the deployed systems, and returns telemetry data.
    Returns:
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorPlatformTelemetryData started")
    monitor = MonitorPlatformTelemetryData()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor MonitorPlatformTelemetryData stopped")

    return rt


@celery_instance.task
def monitor_feed_auto_updates():
    """Task to perform feed auto updates - when enabled from the UI.

    Returns:
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorAutoFeedUpdates started")
    monitor = MonitorFeedAutoUpdates()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor MonitorAutoFeedUpdates stopped")

    return rt


@celery_instance.task
def monitor_download_mcserver_messages():
    """Task to run periodically."""
    logger.info("Monitor monitor_download_mcserver_messages... started")
    rt = False
    monitor = MonitorDownloadMessageCenterMessages()
    if monitor.start():
        rt = True
    logger.info("Monitor monitor_download_mcserver_messages... finished")

    return rt


@celery_instance.task
def monitor_system_check_db_is_innodb():
    """
        Task to run periodically
    """
    logger.info("Monitor monitor_system_check_db... started")
    rt = False
    monitor = MonitorSystemCheckDB()
    if monitor.start():
        rt = True
    logger.info("Monitor monitor_system_check_db... finished")

    return rt


@celery_instance.task
def monitor_server_eps_stats():
    """
    Task to run periodically
    """
    logger.info("Monitor monitor_server_eps_stats... started")
    rt = False
    monitor = MonitorServerEPSStats()
    if monitor.start():
        rt = True
    logger.info("Monitor monitor_server_eps_stats... finished")

    return rt


@celery_instance.task
def monitor_web_ui_data():
    """Monitor to check all the web data

    Returns:
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorWebUIData started")
    monitor = MonitorWebUIData()
    rt = False

    if monitor.start():
        rt = True
    logger.info("Monitor MonitorWebUIData stopped")
    return rt


@celery_instance.task
def monitor_support_tunnels():
    """
        Clean keys / users if ssh tunnels is down
        Returns:
            Trus if successful false otherwise
    """
    logger.info("Monitor MonitorSupportTunnel started")
    monitor = MonitorSupportTunnel()
    rt = False
    if monitor.start():
        rt = True
    logger.info("Monitor MonitorSupportTunnel stopped")
    return rt


@celery_instance.task
def monitor_system_reboot_needed():
    """Monitor to check if a system reboot is needed.

    Returns:
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorSystemRebootNeeded started")
    monitor = MonitorSystemRebootNeeded()
    rt = False

    if monitor.start():
        rt = True
    logger.info("Monitor MonitorSystemRebootNeeded stopped")
    return rt


@celery_instance.task
def monitor_download_pulses():
    """Monitor for new pulses

    Returns:
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorDownloadPulses started")
    monitor = MonitorDownloadPulses()
    try:
        rt = monitor.start()
    except:
        rt = False

    logger.info("Monitor MonitorDownloadPulses stopped")
    return rt


@celery_instance.task
def monitor_download_pulses_ha():
    """Monitor for new pulses (HA Environments)

    Returns:
        True if successful, False otherwise
    """
    rt = False
    ha_enabled = False

    try:
        is_otx_enabled = apimethod_is_otx_enabled()

        if is_otx_enabled is True:
            system_id = get_system_id_from_local()[1]
            success, system_info = system_status(system_id)

            if success is False:
                APICannotRetrieveSystems()

            if 'ha_status' in system_info and system_info['ha_status'] == 'up':
                logger.info("Monitor MonitorDownloadPulses [HA] started")
                ha_enabled = True
                monitor = MonitorDownloadPulses()
                rt = monitor.start()
    except:
        rt = False

    if ha_enabled is True:
        logger.info("Monitor MonitorDownloadPulses [HA] stopped")
    return rt


@celery_instance.task(base=QueueOnce)
def monitor_update_host_plugins():
    """Monitor to fill host_scan table with active plugins per device
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorUpdateHostPlugins started")
    monitor = MonitorUpdateHostPlugins()
    rt = False

    if monitor.start():
        rt = True

    logger.info("Monitor MonitorUpdateHostPlugins stopped")

    return rt


@celery_instance.task(base=QueueOnce)
def monitor_enabled_plugins_limit():
    """Monitor to check enabled plugins limit and send notification in MC.
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorEnabledPlugins started")
    monitor = MonitorEnabledPluginsLimit()
    rt = False

    if monitor.start():
        rt = True

    logger.info("Monitor MonitorEnabledPlugins stopped")

    return rt


@celery_instance.task
def monitor_insecured_vpn():
    """Monitor for checking insecured VPN scenarios

    Returns:
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorInsecureVPN started")
    monitor = MonitorInsecureVPN()
    try:
        rt = monitor.start()
    except:
        return False
    logger.info("Monitor MonitorInsecureVPN stopped")
    return rt


@celery_instance.task
def monitor_federated_otx_key():
    """Monitor for checking that all the systems in a federated environment have the same OTX key.

    Returns:
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorFederatedOTXKey started")
    monitor = MonitorFederatedOTXKey()
    try:
        rt = monitor.start()
    except:
        return False
    logger.info("Monitor MonitorFederatedOTXKey stopped")
    return rt


@celery_instance.task
def monitor_sync_custom_plugins():
    """Monitor to rsync all plugins between connected sensors
        True if successful, False otherwise
    """
    logger.info("Monitor MonitorSyncCustomPlugins started")
    monitor = MonitorSyncCustomPlugins()
    rt = False

    if monitor.start():
        rt = True

    logger.info("Monitor MonitorSyncCustomPlugins stopped")

    return rt
