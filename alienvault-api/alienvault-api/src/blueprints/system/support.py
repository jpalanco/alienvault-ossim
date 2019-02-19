# -*- coding: utf-8 -*-
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
    Several support calls
"""
from flask import Blueprint, request
from api.lib.utils import accepted_url
from uuid import UUID
from api.lib.common import make_ok, make_bad_request, make_error, document_using
from api.lib.auth import admin_permission
from apimethods.system.support import connect_tunnel
from apimethods.system.support import status_tunnel
from apimethods.system.support import delete_tunnel
import api_log

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<system_id>/support/tunnel', methods=['POST'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'ticket': str})
def post_enable_tunnel(system_id):
    """
        Handle the url
        POST /av/api/1.0/system/<system_id>/support/tunnel

        Args:
            system_id (str): String with system id (uuid) or local
    """
    case_id = request.form.get('ticket')
    if case_id is None:
        return make_bad_request("Missing param ticket")
    success, result = connect_tunnel(system_id, case_id)
    if not success:
        api_log.error("Failed API call: remote addr = %s, host addr = %s, blueprint = %s, URL = %s method = %s result = %s" % (request.remote_addr, request.host, request.blueprint, request.base_url, request.method, str(result)))
        return make_error(result,  500)
    return make_ok()


@blueprint.route('/<system_id>/support/tunnel', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_status_tunnel(system_id):
    """
        Handle the url
        GET /av/api/1.0/system/<system_id>/support/tunnel

        Args:
            system_id (str): String with system id (uuid) or local

        Return the status of tunnels
    """
    success, result = status_tunnel(system_id)
    if not success:
        api_log.error("Failed API call:  remotee addr = %s, host addr = %s, blueprint = %s, URL = %s method = %s" % (request.remote_addr, request.host, request.blueprint, request.base_url, request.method))
        return make_error("Cannot get tunnel status for system %s" % system_id, 500)
    return make_ok(pids=result)


@blueprint.route('/<system_id>/support/tunnel', methods=['DELETE'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def del_tunnel(system_id):
    """
        Remove all tunnels if the system is called without parameters or the tunnel
        with PID tunnel_pid if passed
    """
    success, result = delete_tunnel(system_id)
    if not success:
        api_log.error("Failed API call:  remotee addr = %s, host addr = %s, blueprint = %s, URL = %s method = %s result = %s" % (request.remote_addr, request.host, request.blueprint, request.base_url, request.method, str(result)))
        return make_error("Cannot stop support tunnels for system %s" % system_id, 500)
    return make_ok()
