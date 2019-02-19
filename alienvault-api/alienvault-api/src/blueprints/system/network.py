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
from api.lib.common import (make_ok, make_error, make_bad_request, document_using)

from api.lib.utils import accepted_url, first_init_admin_access
from apimethods.utils import is_json_boolean, is_json_true, is_valid_ipv4
from apimethods.system.network import dns_resolution, get_interfaces, get_interface, set_interfaces_roles, \
    get_interface_traffic, get_traffic_stats, put_interface, get_fqdn_api
from uuid import UUID
from json import loads
from api.lib.auth import admin_permission, logged_permission

blueprint = Blueprint(__name__, __name__)
# TODO: get the default user from a configuration file


@blueprint.route('/<system_id>/network/interface', methods=['GET'])
@document_using('static/apidocs/system.html')
@logged_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_system_network_interfaces(system_id):
    """
    Return a list of system network interfaces
    """
    (success, data) = get_interfaces(system_id)
    if not success:
        return make_error(data, 500)

    return make_ok(interfaces=data)


@blueprint.route('/<system_id>/network/interface', methods=['PUT'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'interfaces': str})
def set_system_interfaces_roles(system_id):
    try:
        interfaces = loads(request.args.get("interfaces", None))
    except ValueError:
        current_app.logger.error("network: Bad 'interfaces' parameter. Must be a correct url encoded string or bad json data")
        return make_bad_request("Bad parameter data")

    (success, data) = set_interfaces_roles(system_id, interfaces)
    if not success:
        return make_error(data, 500)

    return make_ok(jobid=data)


@blueprint.route('/<system_id>/network/interface/<iface>', methods=['GET'])
@document_using('static/apidocs/system.html')
@logged_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'iface': str})
def get_system_network_interface(system_id, iface):
    (success, data) = get_interface(system_id, iface)
    if not success:
        return make_error(data, 500)

    return make_ok(interface=data)


@blueprint.route('/<system_id>/network/interface/<iface>', methods=['PUT'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'iface': str, 'promisc': str})
def put_system_network_interface(system_id, iface):
    promisc = request.args.get("promisc")
    if promisc is not None:
        if not is_json_boolean(promisc):
            current_app.logger.error("network: put_system_network_interface error: Bad param 'promisc='%s'" % promisc)
            return make_bad_request("Bad param 'promisc=%s'" % promisc)
    else:
        current_app.logger.error("network: put_system_network_interface error: Missing parameter 'promisc'")
        return make_bad_request("Missing parameters")

    (success, msg) = put_interface(system_id, iface, is_json_true(promisc))
    if not success:
        current_app.logger.error("network: put_system_network_interface error: " + str(msg))
        return make_error(msg, 500)

    return make_ok()


@blueprint.route('/<system_id>/network/interface/<iface>/traffic', methods=['GET'])
@document_using('static/apidocs/system/network.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'iface': str, 'timeout': str})
def get_system_network_interface_traffic(system_id, iface):
    timeout = request.args.get('timeout', None)
    if timeout is None or timeout == '':
        timeout = 10
    (success, data) = get_interface_traffic(system_id, iface, timeout)
    if not success:
        current_app.logger.error("newtork: get_system_network_interface_traffic error: " + str(data))
        return make_error(data, 500)

    return make_ok(stats=data)


@blueprint.route('/<system_id>/network/traffic_stats', methods=['GET'])
@document_using('static/apidocs/system/network.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_system_network_traffic_stats(system_id):
    (success, data) = get_traffic_stats(system_id)
    if not success:
        current_app.logger.error("network: get_system_network_traffic_stats error: " + str(data))
        return make_error("Error getting iface list", 500)

    return make_ok(stats=data)


@blueprint.route('/<system_id>/network/resolve', methods=['GET'])
@document_using('static/apidocs/system/network.html')
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_system_network_resolve(system_id):
    if not first_init_admin_access():
        return make_error('Request forbidden', 403)

    (success, data) = dns_resolution(system_id)
    if not success:
        current_app.logger.error("network: get_system_network_resolve error: " + str(data))
        return make_error(data, 500)

    return make_ok(dns_resolution=data)


@blueprint.route('/<system_id>/network/fqdn', methods=['POST'])
@document_using('static/apidocs/system/network.html')
@logged_permission.require(http_exception=401)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'host_ip': str})
def get_host_fqdn(system_id):
    host_ip = request.form.get('host_ip')
    if not is_valid_ipv4(host_ip):
        return make_error("Invalid host IP address", 500)

    return make_ok(fqdn=get_fqdn_api(system_id, host_ip))
