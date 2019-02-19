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
"""
    Apimetods to deal with the plugin information from API
"""
import json
import os
import os.path
import shutil
import uuid
from tempfile import NamedTemporaryFile

import api_log
from ansiblemethods.sensor.detector import set_sensor_detectors_from_yaml
from ansiblemethods.sensor.plugin import (get_plugin_package_info,
                                          ansible_check_plugin_integrity,
                                          ansible_get_sensor_plugins)
from ansiblemethods.system.about import get_is_professional
from ansiblemethods.system.system import install_debian_package
from ansiblemethods.system.util import fetch_if_changed
from apiexceptions.common import APIInvalidInputFormat
from apiexceptions.plugin import APICannotSetSensorPlugins
from apiexceptions.sensor import APICannotResolveSensorID
from apimethods.system.cache import flush_cache
from apimethods.system.cache import use_cache
from apimethods.system.system import check_update_and_reconfig_status
from celery_once import AlreadyQueued
from celerymethods.jobs.alienvault_agent_restart import restart_alienvault_agent
from db.methods.data import get_asset_ip_from_id
from db.methods.sensor import get_newest_plugin_system, get_sensor_ip_from_sensor_id
from db.methods.system import (get_system_id_from_local,
                               get_system_ip_from_local,
                               get_system_ip_from_system_id)


def get_plugin_package_info_from_sensor_id(sensor_id):
    """
        Get the alienvault-plugins-sids version and md5 from sensor with id sensor_id
        :param: sensor_id
    """
    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if success:
        (success, info) = get_plugin_package_info(sensor_ip)
        if not success:
            result = (False, "Can't get plugins version/md5 information")
        else:
            if info != '':
                result = (True, info)
            else:
                result = (False, "Can't obtain version information")
    else:
        result = (False, "Bad sensor id: %s" % str(sensor_id))
    return result


def get_plugin_package_info_from_system_id(system_id):
    """
        Get the alienvault-plugin-sids version from system with id system_id
        :param: system_id
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if success:
        (success, info) = get_plugin_package_info(system_ip)
        if not success:
            result = (False, "Can't get plugins version/md5 information")
        else:
            result = (True, info)
    else:
        result = (False, "Bad system id: %s" % str(system_id))
    return result


def get_plugin_package_info_local():
    """
        Get the alienvault-plugin-sids version from local system
    """
    (success, system_id) = get_system_id_from_local()
    if success:
        result = get_plugin_package_info_from_system_id(system_id)
    else:
        api_log.error(str(system_id))
        result = (False, "Can't get plugins version/md5 information for local system")
    return result


def check_plugin_integrity(system_id="local"):
    """
        Check if installed agent plugins and agent rsyslog files have been modified or removed locally
    """
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error(str(system_ip))
        return False, "Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))

    success, is_pro = get_is_professional(system_ip)
    if not (success and is_pro):
        return (True, "Skipping plugin integrity check in non professional system")

    success, data = ansible_check_plugin_integrity(system_ip)
    if not success:
        return False, data

    return True, data

def get_plugin_sids_package(system_id, md5):
    """
        Check the :system_id: system if its alienvault-plugin-sids
        package has md5 sum of :md5:. Download the package from remote system.
        check if not reconfig / update is running. Install package
    """
    # First, check remote md5
    rt = False
    emsg = ''
    try:
        result, info = get_plugin_package_info_from_system_id(system_id)
        if not result:
            raise Exception("Can't obtain alienvault-plugin-sid info for system %s : %s" % (system_id, str(info)))
        if info['md5'] != md5:
            raise Exception("md5 provided doesn't match with stored md5")
        # Use ansible to download file to temp directory
        result, ipremote = get_system_ip_from_system_id(system_id)
        if not result:
            raise Exception("Can't obtain remote system ip")
        result, iplocal = get_system_ip_from_local()
        if not result:
            raise Exception("Can't obtain local system ip")
        result, idlocal = get_system_id_from_local()
        if not result:
            raise Exception("Can't obtain local system id")
            # Create a temp file
        temp = NamedTemporaryFile(delete=True)
        tempname = temp.name
        plugin_package = "alienvault-plugin-sids_" + info['version'] + "_all.deb"
        remote_path = "/var/cache/apt/archives"
        result, emsg = fetch_if_changed(ipremote,
                                        os.path.join(remote_path, plugin_package),
                                        iplocal,
                                        tempname)
        if not result:
            raise Exception("Can't copy remote from %s file name %s Error: %s" % (ipremote, os.path.join(remote_path, plugin_package), emsg))
        shutil.copy(tempname, remote_path)
        # Atomic rename
        os.rename(os.path.join(remote_path, os.path.basename(tempname)),
                  os.path.join(remote_path, plugin_package))
        # Check if we're not updaing / configuring
        result, status = check_update_and_reconfig_status(idlocal)
        if not result:
            raise Exception("Can't check current status reconfig / update")
        if status['alienvault-update']['job_status'] == 'running':
            raise Exception("alienvault-update running")
        if status['alienvault-reconfig']['job_status'] == 'running':
            raise Exception("alienvault-reconfig running")
        if status['ossim-reconfig']['job_status'] == 'running':
            raise Exception("ossim-reconfig running")
        # Okey, install package
        result, status = install_debian_package([iplocal], os.path.join(remote_path, plugin_package))
        if not result:
            raise Exception("Can't install %s" % os.path.join(remote_path, plugin_package))
        rt = True
        emsg = ''
    except Exception as excep:
        emsg = str(excep)
        rt = False
    return (rt, emsg)


def update_newest_plugin_sids():
    """
        Update plugins in the local system
    """
    result = False
    emsg = ''
    try:
        # Get the local system_id
        result, local_system_id = get_system_id_from_local()
        if not result:
            raise Exception("Can't obtain the local system_id")
        remote_system_id, md5 = get_newest_plugin_system()
        if remote_system_id is None or local_system_id == remote_system_id:
            raise Exception('Nothing to update')
        result, emsg = get_plugin_sids_package(remote_system_id, md5)
        if not result:
            raise Exception(emsg)
        result = True
        emsg = 'System update correctly'
    except Exception as excep:
        result = False
        emsg = str(excep)
    return (result, emsg)


@use_cache(namespace='sensor_plugins')
def get_sensor_plugins(sensor_id, no_cache=False):
    """ Get the plugins of a sensor
    Raise:
        APICannotGetSensorPlugins
    """
    success, sensor_ip = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        raise APICannotResolveSensorID(
            sensor_id=sensor_id,
            log='[get_sensor_plugins] Error getting sensor ip: {0}'.format(str(sensor_ip)))

    plugins = ansible_get_sensor_plugins(system_ip=sensor_ip)

    return plugins


def get_sensor_plugins_enabled_by_asset(sensor_id, asset_id=None, no_cache=False):
    """ Get the list of plugins enabled in a sensor by asset
    Params:
        sensor_id (UUID): sensor id
        asset_id (UUID): filter for a specific asset
    Return:
        dictionary with the plugins enabled by asset in the sensor
        filtered by asset_id if provided
    Raises:
        APICannotResolveSensorID
        APICannotGetSensorPlugins
    """
    asset_plugins = {}
    sensor_data = get_sensor_plugins(sensor_id=sensor_id,
                                     no_cache=no_cache)
    if 'enabled' in sensor_data:
        asset_plugins = sensor_data['enabled'].get('devices', {})
    if asset_id is not None:
        asset_plugins = dict((key, value) for key, value in asset_plugins.iteritems() if key == asset_id)

    # Fill the plugin info
    plugins = {}
    for (asset, plugin_list) in asset_plugins.iteritems():
        for plugin in plugin_list:
            if plugin in sensor_data['plugins']:
                if asset not in plugins:
                    plugins[asset] = {}
                plugins[asset][plugin] = sensor_data['plugins'][plugin]
            else:
                api_log.warning("[get_sensor_plugins_enabled_by_asset] "
                                "plugin '{0}' enabled in asset '{1}' in sensor '{2}' Not found".format(
                                    plugin, asset, sensor_id))
    return plugins


def set_sensor_plugins_enabled_by_asset(sensor_id, assets_info):
    """ Set the list of plugins enabled in a sensor by asset
    Params:
        sensor_id (UUID): sensor id
        assets_info (dict or json string):
           {"<asset_id>": ["<plugin_1>",
                           "<plugin_2>",
                           ...],
            ...}
    Return:
        the id of the agent restart job
    """
    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        raise APICannotResolveSensorID(
            sensor_id=sensor_id,
            log="[set_sensor_plugins_enabled_by_asset] "
            "Error getting Sensor ip: %s".format(sensor_ip))

    try:
        plugins = {}
        if isinstance(assets_info, basestring):
            assets_info = json.loads(assets_info)

        for asset_id, asset_plugins in assets_info.iteritems():
            asset_id = str(uuid.UUID(asset_id))
            asset_ips = get_asset_ip_from_id(asset_id=asset_id)
            if not asset_ips:
                api_log.error("Cannot resolve ips for asset '{0}'".format(asset_id))
                continue

            plugins[asset_id] = {'device_ip': asset_ips[0],
                                 'plugins': asset_plugins}
    except Exception as e:
        raise APIInvalidInputFormat(
            log="[set_sensor_plugins_enabled_by_asset] "
            "Invalid asset_info format: '{0}'".format(str(e)))

    try:
        (success, data) = set_sensor_detectors_from_yaml(sensor_ip, str(plugins))
    except Exception as e:
        raise APICannotSetSensorPlugins(
            log="[set_sensor_plugins_enabled_by_asset] "
            "Cannot set asset plugins: '{0}'".format(str(e)))
    if not success:
        api_log.error("[set_sensor_plugins_enabled_by_asset] "
                      "Cannot set asset plugins: '{0}'".format(str(data)))
        raise APICannotSetSensorPlugins(
            log="[set_sensor_plugins_enabled_by_asset] "
            "Cannot set asset plugins: '{0}'".format(str(data)))

    # Flush sensor plugin cache and Update host plugin info
    flush_cache("sensor_plugins")
    # Import here to avoid circular imports
    from celerymethods.tasks.monitor_tasks import (monitor_update_host_plugins, monitor_enabled_plugins_limit)
    try:
        monitor_update_host_plugins.delay()
    except AlreadyQueued:
        api_log.info("[set_sensor_plugins_enabled_by_asset] monitor update host plugins already queued")
    try:
        monitor_enabled_plugins_limit.delay()
    except AlreadyQueued:
        api_log.info("[set_sensor_plugins_enabled_by_asset] monitor for enabled plugins already queued")

    # Restart the alienvault agent
    job = restart_alienvault_agent.delay(sensor_ip=sensor_ip)

    return job.id


def get_sensor_detector_plugins(sensor_id):
    """ Get the list of plugins with type 'detector' in a sensor
    Args:
        sensor_id (UUID): sensor is
    Return:
        dictionary with the detector plugins in the sensor
    Raise:
        APICannotResolveSensorID
        APICannotGetSensorPlugins
    """
    plugin_info = get_sensor_plugins(sensor_id)
    all_plugins = plugin_info.get('plugins', {})
    plugins = dict((key, value) for key, value in all_plugins.iteritems() if value.get('type', '') == 'detector')

    return plugins
