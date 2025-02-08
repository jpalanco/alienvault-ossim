# -*- coding: utf-8 -*-
#
# License:
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

import json
import time

from celery.utils.log import get_logger

from ansiblemethods.sensor.ossec import ossec_win_deploy as ansible_ossec_win_deploy

from apimethods.data.status import insert_current_status_message
from apimethods.sensor.ossec import ossec_add_new_agent as apimethod_ossec_add_new_agent
from apimethods.sensor.ossec import apimethod_ossec_get_agent_detail
from apimethods.utils import get_ip_str_from_bytes,  get_uuid_string_from_bytes, is_valid_ipv4, is_valid_uuid, \
    is_valid_ossec_agent_id

from api.lib.utils import is_valid_windows_user, is_valid_user_password

from db.methods.host import get_name_by_host_id
from db.methods.sensor import get_sensor_by_sensor_id
from db.methods.hids import update_hids_agent_status, get_hids_agent_by_sensor, get_hids_agents_by_asset

from apiexceptions.asset import APICannotResolveAssetID
from apiexceptions.sensor import APICannotResolveSensorID
from apiexceptions.hids import APICannotDeployHIDSAgent
from apiexceptions.hids import APICannotCreateHIDSAgent
from apiexceptions.hids import APICannotGetHIDSAgentByAsset
from apiexceptions.hids import APIInvalidDeploymentIP
from apiexceptions.hids import APIInvalidWindowsUsername
from apiexceptions.hids import APIInvalidWindowsPassword
from apiexceptions.hids import APIInvalidAgentID


from celerymethods.tasks import celery_instance
from celerymethods.utils import only_one_hids_task

logger = get_logger("celery")


@celery_instance.task
@only_one_hids_task(key="deploy_agent", timeout=60)
def ossec_win_deploy(sensor_id, asset_id, windows_ip, windows_username, windows_password, windows_domain,
                     agent_id=None):
    """ Deploy HIDS agent on a Windows System
    Args:
        sensor_id(str): Sensor ID
        asset_id(str): Asset ID
        windows_ip(str) : Deployment IP (where we are going to deploy the HIDS Agent)
        windows_username(str) : Windows Username
        windows_password(str) : Windows Password
        windows_domain(str) : Windows Domain
        agent_id(str) : Agent ID

    Returns:
        True if HIDS agent was properly deployed

    Raises:
        APICannotResolveAssetID
        APICannotCreateHIDSAgent
        APICannotGetHIDSAgentByAsset
        APICannotResolveSensorID
        APICannotDeployHIDSAgent
        APIInvalidDeploymentIP
        APIInvalidWindowsUsername
        APIInvalidWindowsPassword
        APIInvalidAgentID
    """

    # Setting default values
    agent_name = None
    sensor_ip = None
    sensor_name = None
    asset_name = None
    try:
        # Validate deployment parameters
        if not is_valid_uuid(asset_id):
            raise APICannotResolveAssetID(asset_id)

        if not is_valid_ipv4(windows_ip):
            raise APIInvalidDeploymentIP(windows_ip)

        if not is_valid_windows_user(windows_username):
            raise APIInvalidWindowsUsername(windows_username)

        if not is_valid_user_password(windows_password):
            raise APIInvalidWindowsPassword()

        if agent_id and not is_valid_ossec_agent_id(agent_id):
            raise APIInvalidAgentID(agent_id)

        # Getting Sensor Information
        (success, sensor) = get_sensor_by_sensor_id(sensor_id)
        if not success:
            raise APICannotResolveSensorID(sensor_id)

        sensor_id = get_uuid_string_from_bytes(sensor.id)
        sensor_id = sensor_id.replace('-', '').upper()
        sensor_ip = get_ip_str_from_bytes(sensor.ip)
        sensor_name = sensor.name

        # Getting agent related to assets
        hids_agents = get_hids_agents_by_asset(asset_id, sensor_id)

        # Creating agent if doesn't exists
        if len(hids_agents) == 0:
            # Agent name will be the asset name
            agent_name = get_name_by_host_id(asset_id)
            (success, data) = apimethod_ossec_add_new_agent(sensor_id, agent_name, windows_ip, asset_id)

            if not success:
                raise APICannotCreateHIDSAgent(agent_id, sensor_id)
            else:
                agent_id = data
        else:
            # Getting agent information
            if agent_id:
                agent_key = sensor_id + '#' + agent_id
            else:
                agent_key = hids_agents.keys().pop(0)

            if agent_key in hids_agents:
                agent_name = hids_agents[agent_key].get('name')
                agent_id = hids_agents[agent_key].get('id')
            else:
                raise APICannotGetHIDSAgentByAsset(asset_id)

        # Deploy HIDS Agent
        ansible_result = ansible_ossec_win_deploy(sensor_ip, agent_name, windows_ip, windows_username, windows_domain,
                                                  windows_password)
        if ansible_result[sensor_ip]['unreachable'] == 0 and ansible_result[sensor_ip]['failures'] == 0:
            # No error, update agent status in database
            time.sleep(2)
            (success, data) = apimethod_ossec_get_agent_detail(sensor_id, agent_id)

            if success:
                agent_info = data[0].split(',')
                agent_status = agent_info[3]

                update_hids_agent_status(agent_id, sensor_id, agent_status)
        else:
            ans_last_error = ""
            if ansible_result[sensor_ip]['unreachable'] == 1:
                ans_last_error = "System is unreachable"
            elif 'msg' in ansible_result['alienvault']['lasterror'][sensor_ip] and ansible_result['alienvault']['lasterror'][sensor_ip]['msg']!="":
                ans_last_error = ansible_result['alienvault']['lasterror'][sensor_ip]['msg']
            elif 'stderr' in ansible_result['alienvault']['lasterror'][sensor_ip] and ansible_result['alienvault']['lasterror'][sensor_ip]['stderr']!="":
                ans_last_error = ansible_result['alienvault']['lasterror'][sensor_ip]['stderr']
            elif 'stdout' in ansible_result['alienvault']['lasterror'][sensor_ip] and ansible_result['alienvault']['lasterror'][sensor_ip]['stdout']!="":
                ans_last_error = ansible_result['alienvault']['lasterror'][sensor_ip]['stdout']
            error_msg = 'HIDS Agent cannot be deployed.  Reason: {0}'.format(ans_last_error)

            raise APICannotDeployHIDSAgent(error_msg)

        res = True
        message = 'HIDS agent successfully deployed'
    except APICannotDeployHIDSAgent as err:
        message = str(err)
        res = False
    except Exception as err:
        message = str(err)
        logger.error(message)
        res = False

    # Create message in Message Center
    mc_id = "00000000-0000-0000-0000-000000010033" if res is True else "00000000-0000-0000-0000-000000010031"

    additional_info = {
        "asset_id": asset_id,
        "sensor_id": sensor_id,
        "agent_id": agent_id,
        "asset_name": asset_name,
        "asset_ip": windows_ip,
        "sensor_ip": sensor_ip,
        "sensor_name": sensor_name,
        "deploy_status": message
    }

    additional_info = json.dumps(additional_info)
    insert_current_status_message(mc_id, asset_id, "host", additional_info)

    return res, message
