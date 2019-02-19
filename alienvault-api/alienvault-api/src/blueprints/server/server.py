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
from flask.ext.login import current_user

from schema import (Schema,Optional)
from db.methods.system import get_systems_full, get_server_id_from_system_id
from db.methods.server import get_server_ip_from_server_id
from api.lib.http_conn import HttpConn
from apimethods.utils import get_bytes_from_uuid, get_ip_str_from_bytes, get_bytes_from_uuid
from apimethods.server.server import apimethod_run_nfsen_reconfig
from api.lib.utils import  accepted_url
from api.lib.common import (
    http_method_dispatcher, make_ok, make_error, check,
    document_using, validate_json, if_content_exists_then_is_json)

from ansiblemethods.server.server import get_server_stats

from uuid import UUID
from api.lib.auth import admin_permission
from apimethods.server.server import add_server
import api_log
import json

blueprint = Blueprint(__name__, __name__)

SERVER_PORT = 40009


def get_server_status(server_ip):
    rc = True
    data = ""
    try:
        http_conn = HttpConn(current_user.login, current_user.av_pass, server_ip, SERVER_PORT)
        url_request = "http://%s:40009/server/status" % server_ip
        data = http_conn.do_request(url_request, "")
    except:
        rc = False
        data ="An error occurred while retrieving the server status %s" % server_ip
    return rc, data

@blueprint.route('', methods=['GET'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
def get_servers():
    ret, server_data = get_systems_full (system_type = 'Server')
    if ret is True:
        server_list = []
        for server in server_data:
            ret, server_id = get_server_id_from_system_id (server[0])
            if ret:
                server_list.append((server_id, {'admin_ip': server[1]['admin_ip'], 'hostname': server[1]['hostname'],'system_id': server[1]['uuid']}))

        return make_ok(servers=dict(server_list))
    current_app.logger.error ("server: get_servers error: " + str(server_data))
    return make_error("Cannot retrieve servers info", 500)

@blueprint.route('/<server_id>/status', methods=['GET'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_id': UUID})
def get_status(server_id):
    rc, server_ip = get_server_ip_from_server_id(server_id)
    if not rc:
        return make_error("Error while retrieving the server ip:%s" % server_ip, 500)
    rc, data = get_server_status(server_ip)
    if not rc:
        return make_error(data,500)
    return make_ok(result=rc, data=data)

def get_data_from_status(server_id, dataname):
    rc, server_ip = get_server_ip_from_server_id(server_id)
    if not rc:
        return make_error("Error while retrieving the server ip:%s" % server_ip, 500)

    rc, data = get_server_status(server_ip)
    if not rc:
        return make_error(data, 500)
    try:
        json_data = json.loads(data)
        if not json_data.has_key("result"):
            return make_error("Invalid Json Data from the server. Result Not found", 500)

        if not json_data["result"].has_key(dataname):
            return make_error("Invalid Json Data from the server. %s Not found" % dataname, 500)

        return make_ok(serverid=server_id, registered_sensors=json_data['result'][dataname])
    except Exception as e:
        return make_error("An error occurred while parsing the status message from the server", 500)

    return make_error("Unexpected behaviour", 500)

@blueprint.route('/<server_id>/registered_sensors', methods=['GET'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_id': UUID})
def get_registered_sensors(server_id):
    return get_data_from_status(server_id,'rsensors')

@blueprint.route('/<server_id>/unregistered_sensors', methods=['GET'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_id': UUID})
def get_unregistered_sensors(server_id):
    return get_data_from_status(server_id,'unrsensors')


@blueprint.route('/<server_id>/connected_sensors', methods=['GET'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_id': UUID})
def get_connected_sensors(server_id):
    return get_data_from_status(server_id,'csensors')



@blueprint.route('/<server_id>/engine_stats', methods=['GET'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_id': UUID})
def get_engine_stats(server_id):
    return get_data_from_status(server_id,'engine_stats')


@blueprint.route('/<server_id>/server_stats', methods=['GET'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_id': UUID})
def get_server_stats(server_id):
    return get_data_from_status(server_id,'server_stats')


@blueprint.route('/<server_id>/per_session_stats', methods=['GET'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_id': UUID})
def per_session_stats(server_id):
    return get_data_from_status(server_id, 'per_session_stats')

@blueprint.route('/<server_id>/forwarding_stats', methods=['GET'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_id': UUID})
def forwarding_stats(server_id):
    return get_data_from_status(server_id, 'forwarding_stats')

@blueprint.route('/<server_id>/rserver_stats', methods=['GET'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_id': UUID})
def rserver_stats(server_id):
    return get_data_from_status(server_id, 'rserver_stats')


@blueprint.route('', methods=['POST'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_ip': str, 'password': str})
def add_server_to_system():
    password = request.args.get('password', None)
    server_ip = request.args.get('server_ip', None)

    success, msg = add_server(server_ip, password)
    if not success:
        return make_error(msg, 500)
    make_ok(data=msg)


@blueprint.route('/<server_id>/nfsen/reconfigure', methods=['PUT'])
@document_using('static/apidocs/server.html')
@admin_permission.require(http_exception=403)
@accepted_url({'server_id': {'type': UUID, 'values': ['local']}})
def reconfigure_nfsen(server_id):
    """Runs a nfsen reconfig
    Args:
        server_id(str): The server uuid or local
    """
    success, msg = apimethod_run_nfsen_reconfig(server_id)
    if not success:
        return make_error(msg, 500)
    return make_ok(rc=msg)

