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

import re
from collections import defaultdict
from os.path import splitext, basename
from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.helper import parse_av_config_response
from apimethods.system.cache import flush_cache

ansible = Ansible()


def get_sensor_detectors(system_ip):
    """
    @param system_ip: The system IP where you want to get the [sensor]/detectors from ossim_setup.conf
    @return A tuple (sucess|error, data|msgerror)
    """
    response = ansible.run_module(host_list=[system_ip],
                                  module="av_config",
                                  args="sensor_detectors=True op=get",
                                  use_sudo=True)
    parsed_return = parse_av_config_response(response, system_ip)
    # Fugly hack to replace ossec and suricata references in enabled plugins
    parsed_return[1]['sensor_detectors'] = ["AlienVault_NIDS" if p == "suricata" else p for p in parsed_return[1]['sensor_detectors']]
    parsed_return[1]['sensor_detectors'] = ["AlienVault_HIDS" if p == "ossec-single-line" else p for p in parsed_return[1]['sensor_detectors']]
    parsed_return[1]['sensor_detectors'] = ["AlienVault_HIDS-IDM" if p == "ossec-idm-single-line" else p for p in parsed_return[1]['sensor_detectors']]
    parsed_return[1]['sensor_detectors'] = ["availability_monitoring" if p == "nagios" else p for p in parsed_return[1]['sensor_detectors']]

    return parsed_return


def set_sensor_detectors(system_ip, plugins):
    """
    @param system_ip: The system IP where you want to get the [sensor]/detectors from ossim_setup.conf
    @param Comma separate list of detector plugins to activate. Must exists in the machine
    @return A tuple (sucess|error, data|msgerror)
    """
    # Need to flush namespace "system" as alienvault_config is cached in that namespace and
    # is used to show the active plugins, so we flush it to refresh the active plugins
    flush_cache(namespace="system")

    response = ansible.run_module(host_list=[system_ip],
                                  module="av_config",
                                  args="sensor_detectors=%s op=set" % plugins)
    return parse_av_config_response(response, system_ip)


def get_sensor_detectors_from_yaml(system_ip):
    rc = True

    try:
        response = ansible.run_module(host_list=[system_ip],
                                      module='av_sensor_yaml',
                                      args="op=get")
    except Exception as msg:
        rc = False
        response = str(msg)
    return True, response


def set_sensor_detectors_from_yaml(system_ip, plugins):
    # Patch to match with the real plugin file nagios.cfg
    plugins = re.sub(r"availability_monitoring", "nagios", plugins)

    rc = True
    try:
        response = ansible.run_module(host_list=[system_ip],
                                      module='av_sensor_yaml',
                                      args="op=set plugins=\"%s\"" % plugins)

        if response['dark'] != {}:
            return False, "Something wrong happened while running the set plugin module %s" % str(response)
        if "failed" in response['contacted'][system_ip]:
            try:
                msg = response['contacted'][system_ip]['msg']
            except:
                msg = response
            return False, msg
        if "unreachable" in response:
            return False, "%s is unreachable" % system_ip

    except Exception as msg:
        response = str(msg)
        rc = False
    return rc, response


def convert_detectors_dict(original_plgs):
    """Function for dict conversion for remove_plugin_from_detectors_list method

    Input dict structure example :
    [{u'/etc/ossim/agent/plugins/asterisk-voip.cfg': 
       {u'DEFAULT': {u'device': u'192.168.96.65',
          u'device_id': u'52ac0a69-9be1-55f9-6d11-2709f347d731'},
          u'config': {u'location': u'/var/log/alienvault/devices/192.168.96.65/192.168.96.65.log'}}},
    {u'/etc/ossim/agent/plugins/airport-extreme.cfg': 
       {u'DEFAULT': {u'device': u'192.168.96.65',
          u'device_id': u'52ac0a69-9be1-55f9-6d11-2709f347d731'},
          u'config': {u'location': u'/var/log/alienvault/devices/192.168.96.65/192.168.96.65.log'}}}]

    Output structure example:
    {'52ac0a69-9be1-55f9-6d11-2709f347d731':
        {'device_ip': '192.168.96.65', 'plugins': [u'airport-extreme', u'asterisk-voip']}, 
    'ecc0d560-a579-1428-6b85-80d412ae5742': 
        {'device_ip': '192.168.99.254', 'plugins': [u'alcatel', u'avast']}}
    """
    final_res = {}
    result = defaultdict(list)
    for pl_data in original_plgs:
        for pl, df in pl_data.iteritems():
            device_data = df.get('DEFAULT')
            result[device_data.get('device'), device_data.get('device_id')].append(splitext(basename(pl))[0])

    for device_key, device_plugins in result.iteritems():
        device_ip, device_id = device_key
        final_res[device_id] = {'device_ip': device_ip, 'plugins': device_plugins}
    return final_res


def disable_plugin_globally(plugin_name, sensor_ip):
    """ Disables plugin in ossim_setup.conf and config.cfg
    Args:
        plugin_name: (str) Plugin name
        sensor_ip: (str) Sensor IP

    Returns: (tuple) Bool status, error_msg
    """
    # Get the global plugin list
    global_plugins_list = get_sensor_detectors(sensor_ip)[1]['sensor_detectors']
    if plugin_name in global_plugins_list:
        global_plugins_list.remove(plugin_name)
        # Set the global plugin list
        success, data = set_sensor_detectors(sensor_ip, ','.join(global_plugins_list))
        if success:
            return True, "{} plugin was disabled in the detectors list on sensor: {}".format(plugin_name, sensor_ip)
        else:
            return False, "Error while saving global list of detectors: {}, sensor: {}, {}".format(global_plugins_list,
                                                                                                   sensor_ip, data)

    return True, "Nothing to disable on sensor {}".format(sensor_ip)


def disable_plugin_per_assets(plugin_name, sensor_ip):
    """Disables plugin from the list of the detectors in config.yml
    Args:
        plugin_name: (str) Plugin name
        sensor_ip: (str) Sensor IP

    Returns: (tuple) Bool status, error_msg
    """
    # Get plugins list by assets
    try:
        plugins_per_assets = get_sensor_detectors_from_yaml(sensor_ip)[1]['contacted'][sensor_ip]['plugins']['plugins']
    except TypeError:
        return True, "Nothing to disable on sensor {}".format(sensor_ip)

    fetch_plg_names = [splitext(basename(plg.keys()[0]))[0] for plg in plugins_per_assets]
    if plugin_name in fetch_plg_names:
        # Collect plugins per asset in one dict
        plugins_per_asset = convert_detectors_dict(plugins_per_assets)

        # Remove plugin from the list
        for asset, plg_list in plugins_per_asset.iteritems():
            if plugin_name in plg_list['plugins']:
                plg_list['plugins'].remove(plugin_name)

        # Set the new plugin list
        success, data = set_sensor_detectors_from_yaml(sensor_ip, str(plugins_per_asset))
        if not success:
            return False, "Error while saving the list of detectors: {}, sensor: {}".format(plugins_per_asset,
                                                                                            sensor_ip)
        else:
            return True, "Plugin: {} was disabled on sensor: {}".format(plugin_name, sensor_ip)

    return True, "Nothing to disable on sensor {}".format(sensor_ip)
