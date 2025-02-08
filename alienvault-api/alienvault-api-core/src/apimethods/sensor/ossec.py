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
    Several methods to manage a ossec deployment from API
"""

from db.methods.sensor import get_sensor_ip_from_sensor_id
from db.methods.sensor import get_system_id_from_sensor_id

from db.methods.hids import delete_hids_agent
from db.methods.hids import add_hids_agent
from db.methods.hids import get_hids_agents_by_sensor
from db.methods.hids import get_hids_agent_by_sensor
from db.methods.hids import update_asset_id

from db.models.alienvault import Hids_Agents

from apimethods.system.cache import use_cache

from ansiblemethods.sensor.ossec import ossec_extract_agent_key as ans_ossec_extract_agent_key
from ansiblemethods.sensor.ossec import ossec_add_new_agent as ans_ossec_add_new_agent
from ansiblemethods.sensor.ossec import ossec_delete_agent as ans_ossec_delete_agent
from ansiblemethods.sensor.ossec import ossec_delete_agentless as ans_ossec_delete_agentless
from ansiblemethods.sensor.ossec import ossec_get_logs as ans_ossec_get_logs
from ansiblemethods.sensor.ossec import ossec_create_preconfigured_agent
from ansiblemethods.sensor.ossec import ossec_get_available_agents as ans_ossec_get_available_agents
from ansiblemethods.sensor.ossec import ossec_rootcheck as ans_ossec_rootcheck
from ansiblemethods.sensor.ossec import ossec_get_check as ans_ossec_get_check
from ansiblemethods.sensor.ossec import ossec_control as ans_ossec_control
from ansiblemethods.sensor.ossec import ossec_add_agentless as ans_ossec_add_agentless
from ansiblemethods.sensor.ossec import ossec_get_modified_registry_entries as ans_ossec_get_modified_registry_entries
from ansiblemethods.sensor.ossec import ossec_get_configuration_rule as ans_ossec_get_configuration_rule
from ansiblemethods.sensor.ossec import ossec_put_configuration_rule_file as ans_ossec_put_configuration_rule_file
from ansiblemethods.sensor.ossec import ossec_verify_agent_config_file
from ansiblemethods.sensor.ossec import ossec_verify_server_config_file
from ansiblemethods.sensor.ossec import ossec_get_agentless_passlist as ans_ossec_get_agentless_passlist
from ansiblemethods.sensor.ossec import ossec_put_agentless_passlist as ans_ossec_put_agentless_passlist
from ansiblemethods.sensor.ossec import ossec_get_agentless_list as ans_ossec_get_agentless_list
from ansiblemethods.sensor.ossec import ossec_get_ossec_agent_detail as ans_ossec_get_ossec_agent_detail
from ansiblemethods.sensor.ossec import ossec_get_syscheck as ans_ossec_get_syscheck

from ansiblemethods.helper import fetch_file, copy_file, remove_file

from apimethods.utils import create_local_directory, set_ossec_file_permissions, touch_file
from apimethods.sensor.sensor import get_base_path_from_sensor_id

from apiexceptions import APIException

import api_log

import os
import re
OSSEC_CONFIG_AGENT_FILE_NAME = "agent.conf"
OSSEC_CONFIG_SERVER_FILE_NAME = "ossec.conf"
OSSEC_CONFIG_AGENT_PATH = "/var/ossec/etc/shared/agent.conf"
OSSEC_CONFIG_AGENT_PATH_TMP = "/tmp/tmp_agent.conf"
OSSEC_CONFIG_SERVER_PATH = "/var/ossec/etc/ossec.conf"
OSSEC_CONFIG_SERVER_PATH_TMP = "/tmp/tmp_ossec.conf"
OSSEC_LOG_TEST_BIN = "/var/ossec/bin/ossec-logtest"


def get_ossec_directory(sensor_id):
    success, base_path = get_base_path_from_sensor_id(sensor_id)
    if not success:
        return False, "Can't retrieve the destination path: %s" % base_path
    destination_path = base_path + "/ossec/"

    # Create directory if not exists
    success, msg = create_local_directory(destination_path)
    if not success:
        api_log.error(str(msg))
        return False, "Error creating directory '%s'" % destination_path

    return True, destination_path


def ossec_add_new_agent(sensor_id, agent_name, agent_ip, asset_id):
    """
        Add a new agent
    """
    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Bad sensor_id"

    (success, data) = ans_ossec_add_new_agent(sensor_ip, agent_name, agent_ip)

    # Add HIDS information to database and restart ossec server if it is necessary
    if success:
        # Default values
        agent_id = data
        agent_status = 'Never connected'

        try:
            add_hids_agent(agent_id, sensor_id, agent_name, agent_ip, agent_status, asset_id)
        except APIException as e:
            success = False
            data = str(e)

        (result, status) = ans_ossec_control(sensor_ip, 'status', '')

        if result and status['general_status']['remoted'] == 'DOWN':
            ans_ossec_control(sensor_ip, 'restart', '')

    return success, data


def apimethod_link_agent_to_asset(sensor_id, agent_id, asset_id):
    """
        This method binds an asset with an agent
        @param sensor_id: Sensor id
        @param agent_id: Agent ID
        @param asset_id: Asset related to agent
    """

    success = True
    data = ''

    try:
        update_asset_id(sensor_id, agent_id, asset_id)
    except APIException as e:
        success = False
        data = str(e)

    return success, data


def ossec_delete_agent(sensor_id, agent_id):
    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Bad sensor_id"
    (success, data) = ans_ossec_delete_agent(sensor_ip, agent_id)

    if success:
        try:
            delete_hids_agent(agent_id, sensor_id)
        except APIException as e:
            data = str(e)
            success = False

    return success, data


def ossec_delete_agentless(sensor_id, agent_ip):
    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Bad sensor_id"
    (success, data) = ans_ossec_delete_agentless(sensor_ip, agent_ip)
    return success, data


def ossec_extract_agent_key(sensor_id, agent_id):
    """
        Extract the agente key
        @param sensor_id: sensor id
        @param agent_id:  A string between 0 and 9999 and [0-9]{1,4}
        @return: Nothig is OK or the error message
    """
    # Check the agent_id
    if re.match(r"^[0-9]{1,4}$", agent_id) is None:
        return (False, "Bad agent_id %s" % agent_id)
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return (False, system_ip)
    return ans_ossec_extract_agent_key(system_ip, agent_id)


def ossec_get_logs(sensor_id, ossec_log, number_of_lines):
    """
       Return lines from ossec_log
       @param sensor_id: Sensor id
       @param ossce_log: alert or ossec , the where we're going to red
       @param number_of_logs: Number of line to read from the logs
    """
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return (False, system_ip)
    return ans_ossec_get_logs(system_ip, ossec_log, number_of_lines)


def ossec_get_preconfigured_agent(sensor_id, agent_id, agent_type):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id"

    success, destination_path = get_ossec_directory(sensor_id)
    if not success:
        api_log.error(str(destination_path))
        return False, destination_path

    return ossec_create_preconfigured_agent(system_ip, agent_id, agent_type, destination_path)


def ossec_rootcheck(sensor_id, agent_id):
    """
        Rootcheck
        @param sensor_id: Sensor id
        @param agent_id: Agent id [0-9]{1,4}
    """
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return (False, system_ip)
    return ans_ossec_rootcheck(system_ip, agent_id)


@use_cache(namespace="sensor_ossec_agents")
def ossec_get_check(sensor_id, agent_id, check_type, no_cache=False):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id

    return ans_ossec_get_check(system_ip=system_ip, check_type=check_type, agent_id=agent_id)


def apimethod_hids_get_list(sensor_id):
    """
        Get HIDS agent list
        @param sensor_id: Sensor id

    Raises:
        APICannotResolveSensorID
        APICannotGetHIDSAgents
    """

    all_hids_agents = get_hids_agents_by_sensor(sensor_id)
    hids_agents = {}

    for (agent_id, agent_data) in all_hids_agents.items():
        #HIDS Agent with an unlinked asset will be exclude since they will be removed
        if agent_data['status']['id'] != Hids_Agents.AGENT_STATUS_MAP['unlinked']:
            hids_agents[agent_id] = agent_data

    return hids_agents


def ossec_get_available_agents(sensor_id, op_ossec, agent_id=''):
    """
        Exec several ops for a ossec agent
        @param sensor_id: Sensor id
        @param op_ossec: Operation. One in list_available_agents,  list_online_agents,
        restart_agent, integrity_check
        @param agent_id: Agent id [0-9]{1,4}

    """
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return (False, system_ip)
    return ans_ossec_get_available_agents(system_ip, op_ossec, agent_id)


def apimethod_ossec_control(sensor_id, operation, option):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id

    (result, ans_result) = ans_ossec_control(system_ip=system_ip, operation=operation, option=option)

    if result and operation == "restart":
        # Update status of all HIDS Agents
        from celerymethods.tasks.hids import update_system_hids_agents

        try:
            (success, system_id) = get_system_id_from_sensor_id(sensor_id)
            if success:
                update_system_hids_agents.delay(system_id)
        except Exception as e:
            api_log.error("[update_system_hids_agents]: {0}".format(e))

    return (result, ans_result)


def ossec_add_agentless(sensor_id, host, user, password, supassword):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id
    return ans_ossec_add_agentless(system_ip, host, user, password, supassword)


def apimethod_ossec_get_modified_registry_entries(sensor_id, agent_id):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id
    return ans_ossec_get_modified_registry_entries(system_ip=system_ip, agent_id=agent_id)


def apimethod_get_configuration_rule_file(sensor_id, rule_filename):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id
    success, base_path = get_base_path_from_sensor_id(sensor_id)
    if not success:
        return False, "Can't retrieve the destination path: %s" % base_path
    destination_path = base_path + "/ossec/rules/"

    success, msg = create_local_directory(destination_path)
    if not success:
        api_log.error(str(msg))
        return False, "Error creating directory '%s'" % destination_path

    success, msg = ans_ossec_get_configuration_rule(
        system_ip=system_ip, rule_filename=rule_filename, destination_path=destination_path
    )
    if not success and str(msg).find('the remote file does not exist') >= 0 and touch_file(destination_path+rule_filename):
        msg = destination_path+rule_filename

    success, result = set_ossec_file_permissions(destination_path+rule_filename)
    if not success:
        return False, str(result)

    return success, msg


def apimethod_put_ossec_configuration_file(sensor_id, filename):
    if filename not in ['local_rules.xml', 'rules_config.xml']:
        return False, "Invalid configuration file to put: %s" % str(filename)
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id
    success, base_path = get_base_path_from_sensor_id(sensor_id)
    if not success:
        return False, "Can't retrieve the destination path: %s" % base_path
    src_file = base_path + "/ossec/rules/%s" % filename
    return ans_ossec_put_configuration_rule_file(system_ip=system_ip, local_rule_filename=src_file, remote_rule_name=filename)


def ossec_get_agent_config(sensor_id):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id

    success, ossec_directory = get_ossec_directory(sensor_id)
    if not success:
        api_log.error(str(ossec_directory))
        return False, ossec_directory
    agent_config_file = os.path.join(ossec_directory, OSSEC_CONFIG_AGENT_FILE_NAME)

    success, filename = fetch_file(system_ip=system_ip,
                                   src_file_path=OSSEC_CONFIG_AGENT_PATH,
                                   dst_file_path=agent_config_file,
                                   fail_on_missing=True,
                                   flat=True)
    try:
        if not success:
            if str(filename).find('the remote file does not exist') >= 0:
                if touch_file(agent_config_file):
                    success = True
                    filename = agent_config_file
    except Exception as err:
        import traceback
        api_log.error("EX: %s, %s" % (str(err), traceback.format_exc()))

    if not success:
        api_log.error(str(filename))
        return False, "Something wrong happened getting the HIDS agent configuration file"

    success, result = set_ossec_file_permissions(agent_config_file)
    if not success:
        return False, str(result)

    return True, filename


def ossec_put_agent_config(sensor_id):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id

    success, ossec_directory = get_ossec_directory(sensor_id)
    if not success:
        api_log.error(str(ossec_directory))
        return False, ossec_directory
    agent_config_file = os.path.join(ossec_directory, OSSEC_CONFIG_AGENT_FILE_NAME)

    success, msg = copy_file(host_list=[system_ip],
                             args="src=%s dest=%s owner=root group=ossec mode=644" % (agent_config_file, OSSEC_CONFIG_AGENT_PATH_TMP))
    if not success:
        api_log.error(str(msg))
        return False, "Error copying the HIDS agent configuration file"

    # Sanity Check of the file
    success, msg = ossec_verify_agent_config_file(system_ip, OSSEC_CONFIG_AGENT_PATH_TMP)
    if not success:
        success, msg1 = remove_file(host_list=[system_ip], file_name=OSSEC_CONFIG_AGENT_PATH_TMP)
        if not success:
            api_log.error(str(msg1))
        api_log.error(str(msg))
        return False, "Error verifiying the HIDS agent configuration file\n%s" % msg

    success, msg = copy_file(host_list=[system_ip],
                             args="src=%s dest=%s owner=root group=ossec mode=644" % (agent_config_file, OSSEC_CONFIG_AGENT_PATH))
    if not success:
        success, msg1 = remove_file(host_list=[system_ip], file_name=OSSEC_CONFIG_AGENT_PATH_TMP)
        if not success:
            api_log.error(str(msg1))
        api_log.error(str(msg))
        return False, "Error setting the HIDS agent configuration file"

    success, msg1 = remove_file(host_list=[system_ip], file_name=OSSEC_CONFIG_AGENT_PATH_TMP)
    if not success:
        api_log.error(str(msg1))

    return True, ''


def ossec_get_server_config(sensor_id):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id

    success, ossec_directory = get_ossec_directory(sensor_id)
    if not success:
        api_log.error(str(ossec_directory))
        return False, ossec_directory
    server_config_file = os.path.join(ossec_directory, OSSEC_CONFIG_SERVER_FILE_NAME)

    success, filename = fetch_file(system_ip=system_ip,
                                   src_file_path=OSSEC_CONFIG_SERVER_PATH,
                                   dst_file_path=server_config_file,
                                   fail_on_missing=True,
                                   flat=True)

    if not success:
        if str(filename).find('the remote file does not exist') >= 0:
            if touch_file(server_config_file):
                filename = server_config_file
            else:
                return False, "Error creating config file %s" % server_config_file
        else:
            api_log.error(str(filename))
            return False, "Something wrong happened getting the HIDS server configuration file"

    success, result = set_ossec_file_permissions(server_config_file)
    if not success:
        return False, str(result)
    return True, filename


def ossec_put_server_config(sensor_id):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id

    success, ossec_directory = get_ossec_directory(sensor_id)
    if not success:
        api_log.error(str(ossec_directory))
        return False, ossec_directory
    server_config_file = os.path.join(ossec_directory, OSSEC_CONFIG_SERVER_FILE_NAME)


    success, msg = copy_file(host_list=[system_ip],
                             args="src=%s dest=%s owner=root group=ossec mode=644" % (server_config_file, OSSEC_CONFIG_SERVER_PATH_TMP))
    if not success:
        api_log.error(str(msg))
        return False, "Error copying the HIDS server configuration file"

    # Sanity Check of the file
    success, msg = ossec_verify_server_config_file(system_ip, OSSEC_CONFIG_SERVER_PATH_TMP)
    if not success:
        success, msg1 = remove_file(host_list=[system_ip], file_name=OSSEC_CONFIG_SERVER_PATH_TMP)
        if not success:
            api_log.error(str(msg1))

        api_log.error(str(msg))
        return False, "Error verifiying the ossec server configuration file\n%s" % msg

    success, msg = copy_file(host_list=[system_ip],
                             args="src=%s dest=%s owner=root group=ossec mode=644" % (server_config_file, OSSEC_CONFIG_SERVER_PATH))
    if not success:
        success, msg1 = remove_file(host_list=[system_ip], file_name=OSSEC_CONFIG_SERVER_PATH_TMP)
        if not success:
            api_log.error(str(msg1))

        api_log.error(str(msg))
        return False, "Error setting the HIDS server configuration file"

    success, msg1 = remove_file(host_list=[system_ip], file_name=OSSEC_CONFIG_SERVER_PATH_TMP)
    if not success:
        api_log.error(str(msg1))

    return True, ''


def apimethod_get_agentless_passlist(sensor_id):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id
    success, base_path = get_base_path_from_sensor_id(sensor_id)
    if not success:
        return False, "Can't retrieve the destination path: %s" % base_path
    destination_path = base_path + "/ossec/agentless/"

    success, msg = create_local_directory(destination_path)
    if not success:
        api_log.error(str(msg))
        return False, "Error creating directory '%s'" % destination_path
    dst_filename = destination_path+".passlist"
    success, msg = ans_ossec_get_agentless_passlist(system_ip=system_ip,
                                                    destination_path=dst_filename)
    if not success:
        if str(msg).find('the remote file does not exist') >= 0:
            if touch_file(dst_filename):
                success = True
                msg = dst_filename

    if not success:
        return False, "Error creating config file %s" % dst_filename

    success, result = set_ossec_file_permissions(dst_filename)
    if not success:
        return False, str(result)

    return success, msg


def apimethod_put_agentless_passlist(sensor_id):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id
    success, base_path = get_base_path_from_sensor_id(sensor_id)
    if not success:
        return False, "Can't retrieve the destination path: %s" % base_path
    src_file = base_path + "/ossec/agentless/.passlist"
    return ans_ossec_put_agentless_passlist(system_ip=system_ip, local_passfile=src_file)


def apimethod_get_agentless_list(sensor_id):
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, "Invalid sensor id %s" % sensor_id
    return ans_ossec_get_agentless_list(system_ip=system_ip)


def apimethod_ossec_get_agent_detail(sensor_id, agent_id):
    """Retrieves information about a given agent_id
    :param sensor_id of the sensor we are going to consult
    :param agent_id: Agent id [0-9]{1,4}

    """
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return (False, "Invalid sensor id %s" % sensor_id)
    return ans_ossec_get_ossec_agent_detail(system_ip, agent_id)


def apimethod_ossec_get_agent_from_db(sensor_id, agent_id):
    """
        This method gets HIDS agent information from database
        @param sensor_id: Sensor id
        @param agent_id: Agent ID
    """

    try:
        success = True
        data = get_hids_agent_by_sensor(sensor_id, agent_id)
    except APIException as e:
        success = False
        data = str(e)

    return success, data


def apimethod_ossec_get_syscheck(sensor_id, agent_id):
    """
        Return the modified file list detected by ossec agent
        :param sensor_id of the sensor we are going to consult
        :param agent_id: Agente id \d{1,4}
    """
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return (False, "Invalid sensor id %s" % sensor_id)
    return ans_ossec_get_syscheck(system_ip, agent_id)
