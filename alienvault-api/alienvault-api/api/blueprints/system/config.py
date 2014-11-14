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
from flask import Blueprint, request, current_app
from uuid import UUID
from api.lib.common import make_ok, make_error, document_using
from api.lib.auth import admin_permission
from api.lib.utils import accepted_url
from apimethods.system.config import (get_system_config_general,
                                      get_system_config_alienvault)

from db.methods.system import get_system_ip_from_system_id, db_system_update_admin_ip, db_system_update_hostname
from ansiblemethods.system.system import set_av_config
from ansiblemethods.system.system import ansible_add_ip_to_inventory
from celerymethods.jobs.system import alienvault_asynchronous_reconfigure
from apimethods.system.cache import flush_cache


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
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'general_admin_dns': str, 'general_admin_gateway': str, 'general_admin_ip': str,
'general_admin_netmask': str, 'general_hostname': str, 'general_mailserver_relay': str, 'general_mailserver_relay_passwd': str,
'general_mailserver_relay_port': str, 'general_mailserver_relay_user': str, 'general_ntp_server': str, 'firewall_active': str})
def set_config_general(system_id):
    param_names = ['general_admin_dns', 'general_admin_gateway', 'general_admin_ip', 'general_admin_netmask', 'general_hostname',
            'general_mailserver_relay', 'general_mailserver_relay_passwd', 'general_mailserver_relay_port', 'general_mailserver_relay_user',
            'general_ntp_server', 'firewall_active']
    
    (success, system_ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return make_error(system_ip, 500)

    set_values = {}
    for key, value in request.args.iteritems():
        if key not in param_names:
            return make_error ("Bad param %s" % key, 400)
        else:
            set_values[key] = value

    (success, config_values) = set_av_config(system_ip, set_values)
    
    if not success:
       current_app.logger.error("system: set_config_general error: " + str(config_values))
       return make_error("Cannot set general configuration info %s" % str(config_values), 500)

    flush_cache(namespace="system")
    
    if 'general_hostname' in set_values:
        success, msg = db_system_update_hostname(system_id, set_values['general_hostname'])
        if not success:
            return make_error("Error setting values: %s" % msg, 500)
            
    if 'general_admin_ip' in set_values:
        success, msg = db_system_update_admin_ip(system_id, set_values['general_admin_ip'])
        if not success:
            return make_error("Error setting values: %s" % msg, 500)

        success, msg = ansible_add_ip_to_inventory(set_values['general_admin_ip'])
        if not success:
            return make_error("Error setting the admin IP address", 500)
    job = alienvault_asynchronous_reconfigure.delay(system_id)
    return make_ok(job_id=job.id)


@blueprint.route('/<system_id>/config_alienvault', methods=['PUT'])
@document_using('static/apidocs/config.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'framework_framework_ip': str, 'sensor_detectors': str, 'sensor_interfaces': str, 'sensor_mservers': str, 'sensor_networks': str, 'server_server_ip': str})
def set_config_alienvault(system_id):
    param_names = ['framework_framework_ip', 'sensor_detectors', 'sensor_interfaces', 'sensor_mservers', 'sensor_networks', 'server_server_ip']
 
    (success, system_ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return make_error(system_ip, 500)

    set_values = {}
    for key, value in request.args.iteritems():
        if key not in param_names:
            return make_error ("Bad param %s" % key, 400)
        else:
            set_values[key] = value
    
    (success, config_values) = set_av_config(system_ip, set_values)

    if not success:
       current_app.logger.error("system: set_config_alienvault error: " + str(config_values))
       return make_error("Cannot set AlienVault configuration info %s" % str(config_values), 500)
       
    flush_cache(namespace="system")
    
    job = alienvault_asynchronous_reconfigure.delay(system_id)
    return make_ok(job_id=job.id)
