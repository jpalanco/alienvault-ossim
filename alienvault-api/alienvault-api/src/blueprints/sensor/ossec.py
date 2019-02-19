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
    Blueprints to manage OSSEC deployment
"""
import re
from uuid import UUID
from flask import Blueprint, request
from api.lib.common import (make_ok,
                            make_error,
                            make_error_from_exception,
                            make_bad_request,
                            document_using)

from api.lib.auth import logged_permission
from api.lib.utils import accepted_url
from apimethods.sensor.ossec import ossec_get_logs
from apimethods.sensor.ossec import ossec_get_preconfigured_agent
from apimethods.sensor.ossec import ossec_rootcheck
from apimethods.sensor.ossec import ossec_get_check
from apimethods.sensor.ossec import ossec_get_available_agents
from apimethods.sensor.ossec import apimethod_hids_get_list
from apimethods.sensor.ossec import apimethod_ossec_control
from apimethods.sensor.ossec import ossec_add_agentless
from apimethods.sensor.ossec import apimethod_get_agentless_passlist
from apimethods.sensor.ossec import apimethod_put_agentless_passlist
from apimethods.sensor.ossec import apimethod_get_agentless_list
from apimethods.sensor.ossec import apimethod_ossec_get_agent_detail
from apimethods.sensor.ossec import apimethod_ossec_get_syscheck

from apiexceptions import APIException


blueprint = Blueprint(__name__, __name__)  # pylint: disable-msg=C0103


@blueprint.route('/<sensor_id>/ossec/logs', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'log': {'type': str, 'optional': False},
               'number_of_logs': {'type': int, 'optional': False}})
def get_ossec_get_logs(sensor_id):
    """
        Extract the agent key in the sensor
        @param sensor_id: Sensor id
        @param agent_id: Agent id. Must be a string that match [0-9]{1,4}
    """
    log = request.args.get("log")
    nlogs = request.args.get("number_of_logs")
    (result, data) = ossec_get_logs(sensor_id, log, nlogs)
    if result:
        return make_ok(lines=data)
    else:
        return make_error(data, 500)


@blueprint.route('/<sensor_id>/ossec/preconfigured_agent', methods=['PUT'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_id': {'type': str, 'optional': False},
               'agent_type': {'type': str, 'optional': False}})
def get_ossec_preconfigured_agent(sensor_id):
    """Creates a new preconfigured agent and return the local path
    :param sensor_id: Sensor id
    :param agent_id: Agent id. Must be a string that match [0-9]{1,4}
    :param agent_type: Type of agent to be generated.
    """
    agent_id = request.args.get("agent_id", None)
    agent_type = request.args.get("agent_type", None)
    if agent_type not in ["windows", "unix"]:
        return make_bad_request("Invalid agent_type value. Allowed values are(unix,windows)")
    if re.match(r"^[0-9]{1,4}$", agent_id) is None:
        return make_bad_request("Invalid agent_id value. Allowed values are [0-9]{1,4}")

    (result, data) = ossec_get_preconfigured_agent(sensor_id, agent_id, agent_type)
    if result:
        return make_ok(path=data)

    return make_error(data, 500)


@blueprint.route('/<sensor_id>/ossec/agent/<agent_id>/root_check', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
              'agent_id': {'type': str}})
def get_ossec_rootcheck(sensor_id, agent_id):
    """
        Extract the agent key in the sensor
        @param sensor_id: Sensor id
        @param agent_id: Agent id. Must be a string that match [0-9]{1,4}
    """
    (result, data) = ossec_rootcheck(sensor_id, agent_id)
    if result:
        return make_ok(rootcheck=data)
    else:
        return make_error(data, 500)


@blueprint.route('/<sensor_id>/ossec/check', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_name': {'type': str, 'optional': True},
               'check_type': {'type': str, 'optional': True}})
def get_ossec_check(sensor_id):
    """Get additional information(Last syscheck or rootcheck date and/or last IP used) about an agent
    :param sensor_id: Sensor id
    """
    agent_name = request.args.get("agent_name", None)
    check_type = request.args.get("check_type", None)

    if check_type not in ["lastscan", "lastip"]:
        return make_bad_request("Invalid check_type value. Allowed values are(lastscan, lastip)")
    if agent_name is None:
        return make_bad_request("Agent name not specified. Allowed characters are [^a-zA-Z0-9_\\-()]+")
    if re.match(r"[a-zA-Z0-9_\-\(\)]+", agent_name) is None:
        return make_bad_request("Invalid agent name. Allowed characters are [^a-zA-Z0-9_\\-()]+")

    (result, data) = ossec_get_check(sensor_id=sensor_id, agent_name=agent_name, check_type=check_type)
    if result:
        return make_ok(check=data)
    return make_error(data, 500)


@blueprint.route('/<sensor_id>/ossec/agent', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_ossec_available_agents(sensor_id):
    """
        Returns the agent list related to sensor
        :param sensor_id: Sensor id
    """
    try:
        agents = apimethod_hids_get_list(sensor_id)
        return make_ok(agents=agents)
    except APIException as e:
        return make_error_from_exception(e)


@blueprint.route('/<sensor_id>/ossec/active_available_agents', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_ossec_active_agents(sensor_id):
    (result, data) = ossec_get_available_agents(sensor_id, 'list_online_agents')
    if result:
        return make_ok(agents=data)
    else:
        return make_error(data, 500)


@blueprint.route('/<sensor_id>/ossec/agent/<agent_id>/restart', methods=['PUT'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_id': {'type': str, }})
def get_ossec_restart_agent(sensor_id, agent_id):
    (result, data) = ossec_get_available_agents(sensor_id, 'restart_agent', agent_id)
    if result:
        return make_ok(msg=data)
    else:
        return make_error(data, 500)


@blueprint.route('/<sensor_id>/ossec/agent/<agent_id>/integrity_check', methods=['PUT'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_id': {'type': str, }})
def get_ossec_check_integrity_agent(sensor_id, agent_id):
    (result, data) = ossec_get_available_agents(sensor_id, 'integrity_check', agent_id)
    if result:
        return make_ok(msg=data)
    else:
        return make_error(data, 500)


def ossec_control_interface(sensor_id, operation, option):
    if operation not in ["start", "stop", "restart", "enable", "disable", "status", "status"]:
        return make_bad_request("Invalid operation. Allowed values are: ['start','stop','restart','enable','disable','status']")
    if operation in ["enable", "disable"]:
        if option not in ["client-syslog", "agentless", "debug"]:
            return make_bad_request("Invalid option value. Allowed values are: ['client-syslog','agentless','debug']")

    (result, data) = apimethod_ossec_control(sensor_id, operation, option)
    if result:
        return make_ok(general_status=data['general_status'], service_status=data['service_status'], stdout=data['stdout'], raw_output_status=data['raw_output_status'])
    else:
        return make_error(data, 500)


@blueprint.route('/<sensor_id>/ossec/control/status', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_ossec_control_status(sensor_id):
    return ossec_control_interface(sensor_id, "status", "")


@blueprint.route('/<sensor_id>/ossec/control/<operation>', methods=['PUT'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'operation': {'type': str, 'optional': False},
               'option': {'type': str, 'optional': True}})
def run_ossec_control(sensor_id, operation):
    option = request.args.get('option')
    if operation not in ["start", "stop", "restart", "enable", "disable"]:
        return make_bad_request("Invalid operation. Allowed values are: ['start','stop','restart','enable','disable']")

    return ossec_control_interface(sensor_id, operation, option)


@blueprint.route('/<sensor_id>/ossec/agentless', methods=['PUT'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'host': {'type': str, 'optional': False},
               'user': {'type': str, 'optional': False},
               'password': {'type': str, 'optional': False},
               'supassword': {'type': str, 'optional': True}})
def put_ossec_add_agentless(sensor_id):
    """
        Blueprint to add a system without agent

        @param sensor_id : The sensor_id where we're going to add the system
        @param host: Mandatory. IP of the new system.
        @param user: User to connect to host
        @param password: Password of user
        @param supassword: Optional superuser/su  password
    """
    host = request.args.get('host', None)
    user = request.args.get('user', None)
    password = request.args.get('password', None)
    supassword = request.args.get('supassword', None)
    (success, data) = ossec_add_agentless(sensor_id, host, user, password, supassword)
    if not success:
        return make_error(data, 500)
    else:
        return make_ok(msg=str(data))



@blueprint.route('/<sensor_id>/ossec/agentless', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id' : {'type': UUID, 'values': ['local']}})
def get_agentless_list(sensor_id):
    (success, data) = apimethod_get_agentless_list(sensor_id)
    if not success:
        return make_error(data, 500)
    else:
        return make_ok(agents=data)


@blueprint.route('/<sensor_id>/ossec/agentless/passlist', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id' : {'type': UUID, 'values': ['local']}})
def get_agentless_passlist(sensor_id):
    (success, data) = apimethod_get_agentless_passlist(sensor_id)
    if not success:
        return make_error(data, 500)
    else:
        return make_ok(local_path=str(data))


@blueprint.route('/<sensor_id>/ossec/agentless/passlist', methods=['PUT'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id' : {'type': UUID, 'values': ['local']}})
def put_agentless_passlist(sensor_id):
    (success, data) = apimethod_put_agentless_passlist(sensor_id)
    if not success:
        return make_error(data, 500)
    else:
        return make_ok(message=str(data))


@blueprint.route('/<sensor_id>/ossec/agent/<agent_id>/control/detail', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_id': {'type': str, }})
def get_ossec_agent_control_detail(sensor_id, agent_id):
    (result, data) = apimethod_ossec_get_agent_detail(sensor_id, agent_id)
    if result:
        return make_ok(agent_detail=data)
    else:
        return make_error(data, 500)


@blueprint.route('/<sensor_id>/ossec/agent/<agent_id>/sys_check', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'agent_id': {'type': str, }})
def get_ossec_get_syscheck(sensor_id, agent_id):
    (result, data) = apimethod_ossec_get_syscheck(sensor_id, agent_id)
    if result:
        return make_ok(stdout=data)
    else:
        return make_error(data, 500)


