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
from flask import Blueprint, request
from uuid import UUID
from api.lib.common import make_ok, make_error, document_using
from api.lib.common import make_error_from_exception
from api.lib.auth import admin_permission
from api.lib.utils import accepted_url, first_init_admin_access
from apimethods.utils import is_json_true
from apimethods.utils import BOOL_VALUES
from apimethods.system.config import (get_system_config_general,
                                      get_system_config_alienvault)
from apimethods.system.config import set_system_config
from apimethods.system.config import set_system_config_telemetry_enabled
from apimethods.system.config import get_system_config_telemetry_enabled

from apiexceptions import APIException

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<system_id>/config', methods=['GET'])
@document_using('static/apidocs/config.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_config_general(system_id):

    (success, config_values) = get_system_config_general(system_id)
    if not success:
        return make_error(config_values, 500)

    return make_ok(**config_values)


@blueprint.route('/<system_id>/config_alienvault', methods=['GET'])
@document_using('static/apidocs/config.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_config_alienvault(system_id):

    (success, config_values) = get_system_config_alienvault(system_id)
    if not success:
        return make_error(config_values, 500)

    return make_ok(**config_values)


@blueprint.route('/<system_id>/config', methods=['PUT'])
@document_using('static/apidocs/config.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'general_admin_dns': {'type': str, 'optional': True},
               'general_admin_gateway': {'type': str, 'optional': True},
               'general_admin_ip': {'type': str, 'optional': True},
               'general_admin_netmask': {'type': str, 'optional': True},
               'general_hostname': {'type': str, 'optional': True},
               'general_mailserver_relay': {'type': str, 'optional': True},
               'general_mailserver_relay_passwd': {'type': str, 'optional': True},
               'general_mailserver_relay_port': {'type': str, 'optional': True},
               'general_mailserver_relay_user': {'type': str, 'optional': True},
               'general_ntp_server': {'type': str, 'optional': True},
               'firewall_active': {'type': str, 'optional': True}})
def set_config_general(system_id):

    param_names = ['general_admin_dns',
                   'general_admin_gateway',
                   'general_admin_ip',
                   'general_admin_netmask',
                   'general_hostname',
                   'general_mailserver_relay',
                   'general_mailserver_relay_passwd',
                   'general_mailserver_relay_port',
                   'general_mailserver_relay_user',
                   'general_ntp_server',
                   'firewall_active']

    set_values = {}
    for key, value in request.args.iteritems():
        if key not in param_names:
            return make_error("Bad param %s" % key, 400)
        else:
            set_values[key] = value

    (success, job_id) = set_system_config(system_id, set_values)
    if not success:
        return make_error("Error setting new configuration: %s" % job_id, 500)

    return make_ok(job_id=job_id)


@blueprint.route('/<system_id>/config_alienvault', methods=['PUT'])
@document_using('static/apidocs/config.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'framework_framework_ip': str,
               'sensor_detectors': {'type': str, 'optional': True},
               'sensor_interfaces': str,
               'sensor_mservers': str,
               'sensor_networks': str,
               'server_server_ip': str})
def set_config_alienvault(system_id):
    param_names = ['framework_framework_ip',
                   'sensor_detectors',
                   'sensor_interfaces',
                   'sensor_mservers',
                   'sensor_networks',
                   'server_server_ip']

    set_values = {}
    for key, value in request.args.iteritems():
        if key not in param_names:
            return make_error("Bad param %s" % key, 400)
        else:
            set_values[key] = value

    (success, job_id) = set_system_config(system_id, set_values)
    if not success:
        return make_error("Cannot set AlienVault configuration info %s" % str(job_id), 500)

    return make_ok(job_id=job_id)


@blueprint.route('/telemetry', methods=['PUT'])
@accepted_url({'enabled': {'type': str, 'values': BOOL_VALUES}})
def set_telemetry_collection_config():
    if not first_init_admin_access():
        return make_error('Request forbidden -- authorization will not help', 403)

    enabled = is_json_true(request.args.get('enabled'))
    try:
        set_system_config_telemetry_enabled(enabled=enabled)
    except APIException as e:
        return make_error_from_exception(e)

    return make_ok()


@blueprint.route('/telemetry', methods=['GET'])
def get_telemetry_collection_config():
    if not first_init_admin_access():
        return make_error('Request forbidden -- authorization will not help', 403)

    try:
        enabled = get_system_config_telemetry_enabled()
    except APIException as e:
        return make_error_from_exception(e)

    return make_ok(enabled=enabled)
