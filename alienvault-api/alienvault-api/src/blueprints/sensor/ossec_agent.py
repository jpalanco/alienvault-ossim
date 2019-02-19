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
from flask import Blueprint, request, current_app
from ansiblemethods.sensor.ossec import get_ossec_agent_data
from apimethods.sensor.ossec import ossec_add_new_agent as api_ossec_add_new_agent
from apimethods.sensor.ossec import ossec_delete_agent as api_ossec_delete_agent
from apimethods.sensor.ossec import ossec_delete_agentless as api_ossec_delete_agentless
from apimethods.sensor.ossec import apimethod_ossec_get_modified_registry_entries
from apimethods.sensor.ossec import ossec_extract_agent_key
from apimethods.sensor.ossec import apimethod_ossec_get_agent_detail
from apimethods.sensor.ossec import apimethod_ossec_get_agent_from_db
from apimethods.sensor.ossec import apimethod_link_agent_to_asset
from apimethods.utils import is_valid_ipv4, is_valid_ipv4_cidr, \
    is_valid_ossec_agent_id

from db.methods.hids import update_hids_agent_status

from apiexceptions import APIException

from api.lib.utils import accepted_url

from api.lib.common import (
    make_ok, make_error, make_bad_request, document_using, make_error_from_exception)

from uuid import UUID
from api.lib.auth import admin_permission, logged_permission

import re

blueprint = Blueprint(__name__, __name__)


# @blueprint.route('/<sensor_id>/ossec/agent', methods=['GET'])
# @document_using('static/apidocs/sensor/ossec/agent.html')
# @admin_permission.require(http_exception=403)
# @accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
#                'command': str,
#                'list_available_agents': {'type': str, 'optional': True},
#                'list_online_agents': {'type': str, 'optional': True},
#                'list_offline_agents': {'type': str, 'optional': True},
#                'get_info': {'type': str, 'optional': True},
#                'restart_agent': {'type': str, 'optional': True}})
# def run(sensor_id):
#     args = {}
#
#     (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
#     if not success:
#         current_app.logger.error("ossec_agent:run error: Bad sensor_id")
#         return make_bad_request("Bad sensor id")
#
#     # Retrieve URL parameters.
#     args['command'] = request.args.get('command')
#
#     if request.args.get('list_available_agents'):
#         args['list_available_agents'] = '1'
#     if request.args.get('list_online_agents'):
#         args['list_online_agents'] = '1'
#     if request.args.get('list_offline_agents'):
#         args['list_offline_agents'] = '1'
#     if request.args.get('get_info'):
#         args['get_info'] = request.args.get('get_info')
#     if request.args.get('restart_agent'):
#         args['restart_agent'] = request.args.get('restart_agent')
#
#     data = get_ossec_agent_data([sensor_ip], args)
#
#     # Check for errors in the returned data
#     if data['dark'] != {}:
#         current_app.logger.error("ossec_agent:run error: " + str(data['dark']))
#         return make_error(data['dark'], 500)
#
#     return make_ok(messages=data)


@blueprint.route('/<sensor_id>/ossec/agent', methods=['PUT'])
@document_using('static/apidocs/sensor/ossec/agent.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_name': {'type': str},
               'agent_ip': {'type': str},
               'asset_id': {'type': UUID, 'optional': True}})
def ossec_add_new_agent(sensor_id):
    """
    Call API method to run ossec_create_new_agent script
    """

    agent_name = request.args.get('agent_name', None)
    agent_ip = request.args.get('agent_ip', None)
    asset_id = request.args.get('asset_id', None)

    # Check valid input
    valid_str = re.compile('^[-.\w]+$')
    if not valid_str.match(agent_name) or not (is_valid_ipv4(agent_ip) or is_valid_ipv4_cidr(agent_ip)):
        return make_bad_request("Invalid agent name or address")

    # Now call the api method to create the new agent - If everything is right it returns the agent id of the new agent
    (success, data) = api_ossec_add_new_agent(sensor_id, agent_name, agent_ip, asset_id)
    if not success:
        current_app.logger.error("ossec_agent: error creating new agent: " + str(data))
        return make_error(data, 500)

    # Now we get the agent detail
    try:
        agent_id = data
        (success, data) = apimethod_ossec_get_agent_from_db(sensor_id, agent_id)
    except APIException as e:
        return make_error_from_exception(e)

    if success:
        return make_ok(agent_detail=data)
    else:
        return make_error(data, 500)


@blueprint.route('/<sensor_id>/ossec/agent/<agent_id>/link_to_asset', methods=['PUT'])
@document_using('static/apidocs/sensor/ossec/agent.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_id': {'type': str},
               'asset_id': {'type': UUID}})
def ossec_link_to_asset(sensor_id, agent_id):
    """
    Call API method to run apimethod_link_agent_to_asset
    """

    asset_id = request.args.get('asset_id', None)

    # Now call the api method to link agent with asset
    (success, data) = apimethod_link_agent_to_asset(sensor_id, agent_id, asset_id)
    if not success:
        current_app.logger.error("HIDS agent: error binding agent with asset: " + str(data))
        return make_error(data, 500)

    return make_ok(messages=data)


@blueprint.route('/<sensor_id>/ossec/agent/<agent_id>', methods=['DELETE'])
@document_using('static/apidocs/sensor/ossec/agent.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_id': {'type': str}})
def ossec_delete_agent(sensor_id, agent_id):
    """
    Call API method to run ossec_delete_agent script
    """

    # Check valid input
    if not is_valid_ossec_agent_id(agent_id):
        return make_bad_request("Invalid agent ID")

    # Now call the api method to create the new agent
    (success, data) = api_ossec_delete_agent(sensor_id, agent_id)
    if not success:
        current_app.logger.error("ossec_agent: error deleting agent: " + str(data))
        return make_error(data, 500)

    return make_ok(messages=data)


@blueprint.route('/<sensor_id>/ossec/agentless', methods=['DELETE'])
@document_using('static/apidocs/sensor/ossec/agent.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_ip': {'type': str}})
def ossec_delete_agentless(sensor_id):
    """
    Call API method to run ossec_delete_agentless script
    """

    agent_ip = request.args.get('agent_ip', None)

    # Check valid input
    if not is_valid_ipv4(agent_ip):
        return make_bad_request("Invalid agent IP")

    # Now call the api method to create the new agent
    (success, data) = api_ossec_delete_agentless(sensor_id, agent_ip)
    if not success:
        current_app.logger.error("ossec_agent: error deleting agentless queue: " + str(data))
        return make_error(data, 500)

    return make_ok(messages=data)


@blueprint.route('/<sensor_id>/ossec/agent/<agent_id>/sys_check/windows_registry', methods=['GET'])
@document_using('static/apidocs/sensor/ossec/agent.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_id': {'type': str}})
def get_modified_registry_entries(sensor_id, agent_id):
    """
    Retrieves the list of modified registry entries.
    """
    # Now call the api method to create the new agent
    (success, data) = apimethod_ossec_get_modified_registry_entries(sensor_id, agent_id)
    if not success:
        return make_error(data, 500)

    return make_ok(stdout=data)


@blueprint.route('/<sensor_id>/ossec/agent/<agent_id>/key', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_id': {'type': str}})
def get_ossec_extract_agent_key(sensor_id, agent_id):
    """
        Extract the agent key in the sensor
        @param sensor_id: Sensor id
        @param agent_id: Agent id. Must be a string that match [0-9]{1,4}
    """
    (result, data) = ossec_extract_agent_key(sensor_id, agent_id)
    if result:
        return make_ok(agent_key=data)
    else:
        return make_error(data, 500)
