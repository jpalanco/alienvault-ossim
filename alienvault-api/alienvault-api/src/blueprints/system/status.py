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
    Implement the calls to obtain several system facts
"""
from uuid import UUID

import api_log
from api.lib.auth import admin_permission
from api.lib.common import (make_ok,
                            make_error,
                            make_error_from_exception,
                            document_using)
from api.lib.utils import accepted_url, only_one_call_without_caching
from apiexceptions import APIException
from apimethods.system.status import (system_all_info,
                                      network_status,
                                      alienvault_status,
                                      package_list, ping_system)
from apimethods.system.system import (check_if_process_is_running,
                                      apimethod_get_remote_software_update,
                                      apimethod_get_pending_packges)
from apimethods.utils import is_json_true, is_json_boolean, BOOL_VALUES
from flask import Blueprint, request

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<system_id>/status/general', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'no_cache': {'optional': True, 'values': ['true', 'false']}
               })
def get_system_status(system_id):
    """
        Handle the url
        GET /av/api/1.0/system/<system_id>/status/general?no_cache=<boolean>

        Args:
            system_id (str): String with system id (uuid) or local

    """
    no_cache = True if request.args.get('no_cache', 'false') == 'true' else False
    success, result = only_one_call_without_caching(system_all_info)(system_id, no_cache=no_cache)
    if not success:
        api_log.error("Cannot retrieve system status for system_id %s. Error: %s" % (system_id, str(result)))
        api_log.error("Failed API call: remote addr = %s, host addr = %s, blueprint = %s, URL = %s" % (
            request.remote_addr, request.host, request.blueprint, request.base_url))
        return make_error("Cannot retrieve system status for system %s" % system_id, 500)
    return make_ok(**result)


@blueprint.route('/<system_id>/status/network', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'no_cache': {'optional': True, 'values': ['true', 'false']}
               })
def get_network_status(system_id):
    """
        Handle the url
        GET /av/api/1.0/system/<system_id>/status/network?no_cache=<boolean>

        Args:
            system_id (str): String with system id (uuid) or local

    """
    no_cache = True if request.args.get('no_cache', 'false') == 'true' else False
    success, result = only_one_call_without_caching(network_status)(system_id, no_cache=no_cache)
    if not success:
        api_log.error("Cannot retrieve network status for system_id %s. Error: %s" % (system_id, str(result)))
        api_log.error("Failed API call: remote addr = %s, host addr = %s, blueprint = %s, URL = %s" % (
            request.remote_addr, request.host, request.blueprint, request.base_url))
        return make_error("Cannot retrieve network status for system %s" % system_id, 500)
    return make_ok(**result)


@blueprint.route('/<system_id>/status/software', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local', 'all']},
               'no_cache': {'type': str, 'optional': False}})
def get_remote_software_status(system_id):
    """Get the software status from a given AlienVault system or all systems

    The blueprint handle the following url:
    GET /av/api/1.0/system/<system_id>/status/software

    Args:
        system_id (str): String with system id (uuid) local or all

    """
    no_cache = request.args.get('no_cache')
    if not is_json_boolean(no_cache):
        return make_error("Invalid value for the no_cache parameter", 500)
    no_cache = is_json_true(no_cache)

    success, result = only_one_call_without_caching(apimethod_get_remote_software_update)(system_id, no_cache)
    if not success:
        api_log.error("Error: " + str(result))
        return make_error("Cannot retrieve packages status " + str(result), 500)

    return make_ok(**result)


@blueprint.route('/<system_id>/status/alienvault', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'no_cache': {'optional': True, 'values': ['true', 'false']}
               })
def get_alienvault_status(system_id):
    """Get the status of each profile from a given AlienVault system

    The blueprint handle the following url:
    GET /av/api/1.0/system/<system_id>/status/alienvault?no_cache=<boolean>

    Args:
        system_id (str): String with system id (uuid) or local
        no_cache (boolean): Flag to indicate whether load cached data or fresh one.

    """
    no_cache = True if request.args.get('no_cache', 'false') == 'true' else False
    success, result = only_one_call_without_caching(alienvault_status)(system_id, no_cache=no_cache)
    if not success:
        api_log.error("Cannot retrieve AlienVault status for system_id %s. Error: %s" % (system_id, str(result)))
        api_log.error("Failed API call: remote addr = %s, host addr = %s, blueprint = %s, URL = %s" % (
            request.remote_addr, request.host, request.blueprint, request.base_url))
        return make_error("Cannot retrieve AlienVault status for system %s" % system_id, 500)
    return make_ok(**result)


@blueprint.route('/<system_id>/status/installed_packages', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_alienvault_packages(system_id):
    """Get the status of each profile from a given AlienVault system

    The blueprint handle the following url:
    GET /av/api/1.0/system/<system_id>/status/installed_packages?no_cache=<boolean>

    Args:
        system_id (str): String with system id (uuid) or local
        no_cache (boolean): Flag to indicate whether load cached data or fresh one.

    """
    success, result = package_list(system_id)
    if not success:
        api_log.error(
            "Cannot retrieve installed packages status for system_id %s. Error: %s" % (system_id, str(result)))
        api_log.error("Failed API call: remote addr = %s, host addr = %s, blueprint = %s, URL = %s" % (
            request.remote_addr, request.host, request.blueprint, request.base_url))
        return make_error("Cannot retrieve installed packages status for system %s" % system_id, 500)
    return make_ok(**result)


@blueprint.route('/<system_id>/status/pending_packages', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'no_cache': {'type': str, 'optional': False}})
def get_pending_packages(system_id):
    """Get pending update packages from a given AlienVault system

    The blueprint handle the following url:
    GET /av/api/1.0/system/<system_id>/status/pending_packages

    Args:
        system_id (str): String with system id (uuid) or local

    """
    no_cache = request.args.get('no_cache')
    if not is_json_boolean(no_cache):
        return make_error("Invalid value for the no_cache parameter", 500)
    no_cache = is_json_true(no_cache)
    success, result = apimethod_get_pending_packges(system_id, no_cache)
    if not success:
        api_log.error("Error: " + str(result))
        return make_error("Cannot retrieve packages status " + str(result), 500)
    return make_ok(available_updates=result)


@blueprint.route('/<system_id>/status/ready_for_update', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def is_system_ready_for_update(system_id):
    """ Find out if a system is ready for update.
        Determine whether openvas is still rebuilding (ENG-100405).

    The blueprint handle the following url:
    GET /av/api/1.0/system/<system_id>/status/ready_for_update

    Args:
        system_id (str): String with system id (uuid) or local

    """
    is_ready = True
    ps_filters = ['openvasmd --update', 'openvasmd --rebuild', 'openvasmd: Updating', 'openvasmd: Reloading']
    for ps_filter in ps_filters:
        if check_if_process_is_running(system_id, ps_filter)[1]:
            is_ready = False
            break
    return make_ok(is_ready=is_ready)


@blueprint.route('/<system_id>/status/ping', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'no_cache': {'optional': True, 'values': BOOL_VALUES}})
def is_system_reachable(system_id):
    """Find out if a system is reachable or not.

    The blueprint handle the following url:
    GET /av/api/1.0/system/<system_id>/status/ping

    Args:
        system_id (str): String with system id (uuid) or local

    """
    no_cache = is_json_true(request.args.get('no_cache', None))
    try:
        reachable = ping_system(system_id, no_cache=no_cache)
        return make_ok(reachable=reachable)
    except APIException as e:
        make_error_from_exception(e)
