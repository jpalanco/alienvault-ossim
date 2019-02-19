# -*- coding: utf-8 -*-
#
# License:
#
# Copyright (c) 2015 AlienVault
# All rights reserved.
#
# This package is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; version 2 dated June, 1991.
# You may not use, modify or distribute this program under any other version
# of the GNU General Public License.
#
# This package is distributed in the hope that it will be useful,
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

# Functions to deal with custom plugins.

import os
from shutil import copy
from os.path import splitext, basename

import api_log
from db.methods.plugin import (
    get_plugin_list_from_plugin_data,
    get_plugin_data_for_plugin_id,
    get_plugin_sids_for_plugin_id,
    insert_plugin_data,
    update_plugin_data,
    remove_plugin_data,
    save_plugin_from_raw_sql
)
from apimethods.apps.plugins.pfile import PluginFile
from db.models.alienvault import PluginDataType, PluginData
from db.methods.system import get_systems
from apiexceptions import APIException

from apiexceptions.plugin import (
    APICannotUploadPlugin,
    APIPluginListCannotBeLoaded,
    APICannotSavePlugin,
    APIInvalidPlugin,
    APIPluginFileNotFound,
    APICannotBeRemoved,
)
from ansiblemethods.sensor.detector import (
    get_sensor_detectors,
    set_sensor_detectors,
    get_sensor_detectors_from_yaml,
    set_sensor_detectors_from_yaml,
    disable_plugin_globally,
    disable_plugin_per_assets,
)
from ansiblemethods.helper import remove_file

TEMPORAL_FOLDER = "/var/lib/asec/plugins/"
PLUGINS_FOLDER = "/etc/ossim/agent/plugins/"
END_FOLDER = "/etc/alienvault/plugins/custom/"


def apimethod_get_plugin_list():
    """Returns the current list of plugins.
    The list of plugins is read from the alienvault.plugin_data table
    Returns:
        a list of dicts containing al the plugins data.
    Raises:
        APIPluginListCannotBeLoaded: When the list of plugins cannot be loaded.
    """
    try:
        raw_data = get_plugin_list_from_plugin_data()
        plugin_list = [plugin.serialize for plugin in raw_data]
    except Exception as e:
        api_log.error("[apimethod_get_plugin_list] Cannot load the list of the plugins: {}".format(e))
        raise APIPluginListCannotBeLoaded()
    # Removing True to avoid creation of the excess list after json serialization
    # http://flask.pocoo.org/docs/0.12/api/#flask.json.jsonify
    return plugin_list


def apimethod_upload_plugin(plugin_file, vendor, model, version, product_type, overwrite=False):
    """Uploads and verifies a given plugin file"""

    # 1 - check whether the plugin is a valid file or not
    try:
        temporal_plg_path = os.path.join(TEMPORAL_FOLDER, plugin_file)
        plugin_destination_path = os.path.join(END_FOLDER, plugin_file)
        temporal_plg_sql_path = temporal_plg_path + '.sql'
        plugin_asec_path = os.path.join(TEMPORAL_FOLDER, plugin_file)

        # The PluginCheck object will be able to check the syntax of a given plugin
        # return the available set of rules, etc.
        plugin = PluginFile()
        plugin.read(temporal_plg_path, encoding='latin1')
        data = plugin.check()

        data["need_overwrite"] = False
        if data["error_count"] > 0:
            raise APIInvalidPlugin(plugin.get_latest_error_msg())

        if os.path.exists(plugin_destination_path) and not overwrite:
            data["need_overwrite"] = True
            return data

        # Choose what to do: insert or update
        need_to_update = get_plugin_data_for_plugin_id(plugin.plugin_id) and overwrite
        save_plugin_data_func = update_plugin_data if need_to_update else insert_plugin_data

        # Load plugin SQl into the DB.
        with open(temporal_plg_sql_path) as plugin_raw_sql:
            success, msg = save_plugin_from_raw_sql(plugin_raw_sql.read())
            if not success:
                raise APICannotSavePlugin(msg)

        # Save plugin data.
        success, msg = save_plugin_data_func(plugin.plugin_id,
                                             plugin_name=plugin_file,
                                             vendor=vendor,
                                             model=model,
                                             version=version,
                                             nsids=len(data["rules"]),
                                             product_type=product_type)
        if not success:
            raise APICannotSavePlugin(msg)

        # 2 - Save plugin with the appropriate headers (vendor:model:version)
        if not plugin.save(destination=plugin_destination_path, vendor=vendor, model=model,
                           product_type=product_type, version=version):
            remove_plugin_data(plugin.plugin_id)
            raise APICannotSavePlugin(message=plugin.get_latest_error_msg() or "Cannot save plugin file.")

        # Copy plugin sql file to plugins custom dir
        copy(temporal_plg_sql_path, END_FOLDER)

        # Remove via ansible due to file permissions
        remove_file(['127.0.0.1'], plugin_asec_path)
        remove_file(['127.0.0.1'], plugin_asec_path + '.sql')
        # TODO: Is the plugin fd already in use? What is the next free plugin id?
        # 3 - Synchronize Plugins.
        from celerymethods.tasks.monitor_tasks import monitor_sync_custom_plugins
        # Force synchronization
        job = monitor_sync_custom_plugins.delay()
        if job.id is None:
            raise APICannotSavePlugin("Cannot synchronize the plugin.")
        data["synchronization_job"] = job.id
    except Exception as e:
        api_log.error("[apimethod_upload_plugin] {}".format(str(e)))
        if not isinstance(e, APIException):
            raise APICannotSavePlugin()
        raise

    # The method should return a python dic with the job id (the one that is synchronizing the plugins) and
    # the list of plugin sids for the plugin.
    return data


def apimethod_download_plugin(plugin_file):
    """Returns the content of a given plugin file
    Args:
        plugin_file (str) = The plugin you want to download
    Returns:
        Returns the content of the given plugin file
    """
    try:
        plugin_path = "{}{}".format(END_FOLDER, plugin_file)
        if not os.path.isfile(plugin_path):
            plugin_path = "{}{}".format(PLUGINS_FOLDER, plugin_file)
            if not os.path.isfile(plugin_path):
                raise APIPluginFileNotFound(plugin_file)
        with open(plugin_path) as plugin_file:
            data = plugin_file.read()
    except:
        raise
    return data


def remove_plugin_from_sensors(plugin_file):
    """ Disable and remove custom plugin from all systems.
    Args:
        plugin_file: (str) Full path to plugin file.

    Returns: (bool) Status
    """
    plugin_name = splitext(basename(plugin_file))[0]
    result, added_sensors = get_systems(system_type="Sensor", exclusive=True, convert_to_dict=True)
    # In [3]: systems
    # Out[3]: {'564d1731-5369-d912-e91b-61c1fff3cf6c': '192.168.87.197'}

    if not result:
        api_log.error('Cannot get list of connected sensors: {}'.format(added_sensors))
        return False

    # Add local check
    if isinstance(added_sensors, dict):
        added_sensors['local'] = '127.0.0.1'

    if added_sensors:
        for sensor_id, sensor_ip in added_sensors.iteritems():
            api_log.info('Trying to disable global plugin "{}" plugin on - {}'.format(plugin_name, sensor_ip))
            result, msg = disable_plugin_globally(plugin_name, sensor_ip)
            if not result:
                api_log.error(msg)
            api_log.info('Trying to disable per-asset plugin "{}" plugin on - {}'.format(plugin_name, sensor_ip))
            result, msg = disable_plugin_per_assets(plugin_name, sensor_ip)
            if not result:
                api_log.error(msg)

        # Remove plugin file from disk
        api_log.info('Removing plugin file: {} on sensors {}'.format(plugin_file, added_sensors))
        result = remove_file(host_list=added_sensors.values(), file_name=plugin_file)

    return result


def apimethod_remove_plugin(plugin_file):
    """Removes a custom plugin from the systems"""
    try:
        plugin_path = os.path.join(END_FOLDER, plugin_file)
        if not os.path.isfile(plugin_path):
            raise APIPluginFileNotFound(plugin_file)
        plugin = PluginFile()
        # TODO: make some handy wrapper to combine read and check
        plugin.read(plugin_path, encoding='latin1')
        plugin.check()  # validate and load all the plugins data
        plugin_data = get_plugin_data_for_plugin_id(plugin.plugin_id)
        if plugin_data is not None:
            if plugin_data.plugin_type == PluginDataType.ALIENVAULT_PLUGIN:
                raise APICannotBeRemoved("This is an AlienVault Plugin. It cannot be removed")
        # Remove the sids
        remove_plugin_data(plugin.plugin_id)
        remove_plugin_from_sensors(plugin_path)
        # Remove sql file locally (it's located only on server)
        os.remove(plugin_path + '.sql')
    except Exception as e:
        api_log.error("[apimethod_remove_plugin] {}".format(e))
        if not isinstance(e, APIException):
            raise APICannotBeRemoved("{}".format(e))
        else:
            raise
