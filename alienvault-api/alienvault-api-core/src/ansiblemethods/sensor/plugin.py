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

import api_log
import re
import json
import os
import yaml
from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.helper import ansible_is_valid_response, fetch_file

from apiexceptions.plugin import APICannotGetSensorPlugins

ansible = Ansible()


def get_plugin_enabled_by_sensor(system_ip):
    """
    Returns a has table of plugin_name = location
    """
    response = ""
    try:
        response = ansible.run_module(
            host_list=[system_ip],
            module="av_sensor",
            args={}
        )
    except Exception, e:
        api_log.error("get_plugin_enabled_by_sensor: Error Retrieving the plugin list by location. %s" % e)
    return response


def ansible_get_sensor_plugins(system_ip):
    """ Get the plugins of a sensor
    Args:
        system_ip
    Returns
        Dictionary with the plugins available and enable on the sensor:
        {'enabled': {'monitors': <list of monitor plugins enabled>,
                     'detectors': <list of detector plugins enabled>,
                     'devices': {<device_id>: <list of plugins enabled in the device>}
                      all': <list of all detector plugins enabled>,
                     },
         'max_allowed': '100',
         'max_available': '100',
         'plugins': { <plugin_name>: {"cfg_version": <cfg version>,
                                      "last_modification": <last modification>,
                                      "legacy": <bool>,
                                      "model": <model>,
                                      "name": <name>,
                                      "path": <plugin full file path>,
                                      "per_asset": <bool>,
                                      "plugin_id": <plugin_id>,
                                      "shipped": <bool>,
                                      "type": <detector|monitor>,
                                      "vendor": <vendor>,
                                      "source": <source>,
                                      "location": <location>,
                                      "version": <version>}}}
    """
    response = ansible.run_module(
        host_list=[system_ip],
        module="av_plugins",
        args=""
    )
    if not ansible_is_valid_response(system_ip, response):
        raise APICannotGetSensorPlugins(
            log="[ansible_get_sensor_plugins] {0}".format(response))

    try:
        plugins = response['contacted'][system_ip]['data']
    except KeyError:
        raise APICannotGetSensorPlugins(log="[ansible_get_sensor_plugins] {0}".format(response))

    return plugins


def get_plugin_package_version(system_ip):
    """
        Return the current version of package alienvault-plugin-sids in the system
    """
    command = """dpkg -s alienvault-plugin-sids | grep 'Version' | awk {'print $2'}"""
    response = ansible.run_module(
        host_list=[system_ip],
        module="shell",
        args=command
    )
    if system_ip in response['contacted']:
        version = response['contacted'][system_ip]['stdout'].split('\n')[0]  # Only first line
        result = (True, version)
    else:
        result = (False, str(response['dark'][system_ip]))
    return result


def get_plugin_package_info(system_ip):
    """
        If exists, return the md5sum of INSTALLED VERSION of package alienvault-plugin-sid
        IMPORTANT NOTE: The file must be called alienvault-plugin_<version>_all.deb
    """
    (success, version) = get_plugin_package_version(system_ip)
    if success:
        command = """md5sum /var/cache/apt/archives/alienvault-plugin-sids_%s_all.deb|awk {'print $1'}""" % version
        response = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args=command
        )
        if system_ip in response['contacted']:
            if response['contacted'][system_ip]['rc'] == 0:
                md5 = response['contacted'][system_ip]['stdout'].split('\n')[0]  # Only first line
            else:
                api_log.warning("Can't obtanin md5 for alienvault-plugin-sids")
                md5 = ''
            result = (True, {'version': version, 'md5': md5})
        else:
            result = (False, str(response['dark'][system_ip]))
    else:
        result = (False, "Can't obtain package version")
    return result


def ansible_check_plugin_integrity(system_ip):
    """
    Check if installed agent plugins and agent rsyslog files have been modified or removed locally
    :param system_ip: System IP

    :return: JSON with all integrity checks

    """
    rc = True

    try:

        # alienvault-doctor -l agent_rsyslog_conf_integrity.plg,agent_plugins_integrity.plg --output-type=ansible
        doctor_args = {
            'plugin_list': '0008_agent_rsyslog_conf_integrity.plg,0006_agent_plugins_integrity.plg',
            'output_type': 'ansible'
        }

        response = ansible.run_module(
            host_list=[system_ip],
            module="av_doctor",
            args=doctor_args
        )

        success, msg = ansible_is_valid_response(system_ip, response)

        if not success:
            return False, msg

        data = response['contacted'][system_ip]
        if data['rc'] != 0:
            return False, data['stderr']

        # Parse output
        pattern = re.compile("failed for \'(?P<plugin_name>[^\s]+)\'")

        output = {
            'command': data['cmd'],
            'rsyslog_integrity_check_passed': True,
            'rsyslog_files_changed': [],
            'all_rsyslog_files_installed': True,
            'rsyslog_files_removed': [],
            'plugins_integrity_check_passed': True,
            'plugins_changed': [],
            'all_plugins_installed': True,
            'plugins_removed': []
        }

        AGENT_PLUGINS_PATH = "/etc/ossim/agent/plugins/"
        RSYSLOG_FILES_PATH = "/etc/rsyslog.d/"
        agent_rsyslog_dict = json.loads(data['data'].strip())['0008 Agent rsyslog configuration files integrity']
        agent_plugins_dict = json.loads(data['data'].strip())['0006 Agent plugins integrity']
        if agent_rsyslog_dict['checks']['00080001']['result'] == 'failed':
            output['rsyslog_integrity_check_passed'] = False
            rsyslog_files = pattern.findall(agent_rsyslog_dict['checks']['00080001'].get('debug_detail', ''))
            for rsyslog_file in rsyslog_files:
                output['rsyslog_files_changed'].append(os.path.normpath(RSYSLOG_FILES_PATH + rsyslog_file))

        if agent_rsyslog_dict['checks']['00080002']['result'] == 'failed':
            output['all_rsyslog_files_installed'] = False
            rsyslog_files = pattern.findall(agent_rsyslog_dict['checks']['00080002'].get('debug_detail', ''))
            for rsyslog_file in rsyslog_files:
                output['rsyslog_files_removed'].append(os.path.normpath(RSYSLOG_FILES_PATH + rsyslog_file))

        if agent_plugins_dict['checks']['00060001']['result'] == 'failed':
            output['plugins_integrity_check_passed'] = False
            plugins_changed = pattern.findall(agent_plugins_dict['checks']['00060001'].get('debug_detail', ''))
            for plugin in plugins_changed:
                output['plugins_changed'].append(os.path.normpath("/" + plugin))

        if agent_plugins_dict['checks']['00060002']['result'] == 'failed':
            output['all_plugins_installed'] = False
            plugins_removed = pattern.findall(agent_plugins_dict['checks']['00060002'].get('debug_detail', ''))
            for plugin in plugins_removed:
                if AGENT_PLUGINS_PATH in plugin:
                    output['plugins_removed'].append(os.path.normpath(plugin))
                else:
                    output['plugins_removed'].append(os.path.normpath(AGENT_PLUGINS_PATH + plugin))

    except Exception, e:
        api_log.error("[ansible_check_plugin_integrity] Error: %s" % str(e))
        output = "Error checking agent plugins and agent rsyslog files integrity: %s" % str(e)
        rc = False

    return rc, output

