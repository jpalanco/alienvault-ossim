#
#  License:
#
#  Copyright (c) 2014 AlienVault
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
    Blueprints to manage OSSEC configuration
"""
from uuid import UUID
from flask import Blueprint, request
from api.lib.common import (make_ok,
                            make_error,
                            make_bad_request,
                            document_using)

from api.lib.auth import admin_permission, logged_permission
from api.lib.utils import accepted_url
from apimethods.sensor.ossec import ossec_get_agent_config, ossec_put_agent_config
from apimethods.sensor.ossec import ossec_get_server_config, ossec_put_server_config
from apimethods.sensor.ossec import (apimethod_get_configuration_rule_file,
                                     apimethod_put_ossec_configuration_file)

blueprint = Blueprint(__name__, __name__)  # pylint: disable-msg=C0103


@blueprint.route('/<sensor_id>/ossec/configuration/agent', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_ossec_configuration_agent(sensor_id):
    success, data = ossec_get_agent_config(sensor_id)
    if not success:
        return make_error(data, 500)

    return make_ok(local_path=str(data))


@blueprint.route('/<sensor_id>/ossec/configuration/agent', methods=['PUT'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def put_ossec_configuration_agent(sensor_id):
    success, msg = ossec_put_agent_config(sensor_id)
    if not success:
        return make_error(msg, 500)

    return make_ok()


@blueprint.route('/<sensor_id>/ossec/configuration/server', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_ossec_configuration_server(sensor_id):
    success, data = ossec_get_server_config(sensor_id)
    if not success:
        return make_error(data, 500)

    return make_ok(local_path=str(data))


@blueprint.route('/<sensor_id>/ossec/configuration/server', methods=['PUT'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def put_ossec_configuration_server(sensor_id):
    success, msg = ossec_put_server_config(sensor_id)
    if not success:
        return make_error(msg, 500)

    return make_ok()


@blueprint.route('/<sensor_id>/ossec/configuration/rule', methods=['GET'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'rule': {'type': str, 'optional': False}})
def get_configuration_rule_file(sensor_id):
    rule_filename = request.args.get("rule")
    if rule_filename is None or rule_filename == "":
        return make_bad_request("Invalid rule filename <%s>" % rule_filename)
    (success, data) = apimethod_get_configuration_rule_file(sensor_id, rule_filename)
    if not success:
        return make_error(data, 500)
    else:
        return make_ok(local_path=str(data))


@blueprint.route('/<sensor_id>/ossec/configuration/rule', methods=['PUT'])
@document_using('static/apidocs/ossec.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'rule': {'type': str, 'optional': False}})
def put_configuration_rule_file(sensor_id):
    rule_filename = request.args.get("rule")
    if rule_filename not in ['local_rules.xml','rules_config.xml']:
        return make_bad_request("Invalid value for rule name. Allowed values are ['local_rules.xml','rules_config.xml'] ")
    (success, data) = apimethod_put_ossec_configuration_file(sensor_id,rule_filename)
    if not success:
        return make_error(data, 500)
    else:
        return make_ok(message=str(data))
