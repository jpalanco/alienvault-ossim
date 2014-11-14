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
from flask import Blueprint, request
from api.lib.utils import accepted_url
from uuid import UUID
from api.lib.common import make_ok, make_bad_request, make_error, document_using
from api.lib.auth import admin_permission
from apimethods.system.status import system_all_info
from apimethods.system.status import system_status
from apimethods.system.status import network_status
from apimethods.system.status import alienvault_status
from apimethods.system.status import cpu
from apimethods.system.status import disk_usage
from apimethods.system.status import package_list
import api_log


blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<system_id>/status/general', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
              'no_cache': {'optional': True, 'values':['true', 'false']}
               })
def get_system_status(system_id):
    """
        Handle the url
        GET /av/api/1.0/system/<system_id>/status/general?no_cache=<boolean>

        Args:
            system_id (str): String with system id (uuid) or local

    """
    no_cache = True if request.args.get('no_cache', 'false') == 'true' else False
    success, result = system_all_info(system_id, no_cache=no_cache)
    if not success:
        api_log.error("facts: error: " + str(result))
        return make_error("Cannot retrieve system status", 500)
    return make_ok(**result)


@blueprint.route('/<system_id>/status/network', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
              'no_cache': {'optional': True, 'values':['true', 'false']}
               })
def get_network_status(system_id):
    """
        Handle the url
        GET /av/api/1.0/system/<system_id>/status/network?no_cache=<boolean>

        Args:
            system_id (str): String with system id (uuid) or local

    """
    no_cache = True if request.args.get('no_cache', 'false') == 'true' else False
    success, result = network_status(system_id, no_cache=no_cache)
    if not success:
        api_log.error("facts: error: " + str(result))
        return make_error("Cannot retrieve network status " + str(result), 500)
    return make_ok(**result)
    
    
@blueprint.route('/<system_id>/status/alienvault', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
              'no_cache': {'optional': True, 'values':['true', 'false']}
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
    success, result = alienvault_status(system_id, no_cache=no_cache)
    if not success:
        api_log.error("facts: error: " + str(result))
        return make_error("Cannot retrieve AlienVault status " + str(result), 500)
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
        api_log.error("facts: error: " + str(result))
        return make_error("Cannot retrieve AlienVault status " + str(result), 500)
        
    return make_ok(**result)
    
