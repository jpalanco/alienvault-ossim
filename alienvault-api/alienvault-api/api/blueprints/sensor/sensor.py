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
import api_log
from uuid import UUID
from flask import Blueprint, request, current_app, abort
from db.methods.sensor import get_sensor_by_sensor_id, get_sensor_ip_from_sensor_id
from db.methods.system import (get_systems_full,
                               get_sensor_id_from_system_id)
from api.lib.common import (make_ok,
                            make_error,
                            make_bad_request,
                            document_using)

from api.lib.utils import accepted_url
from api.lib.auth import admin_permission, logged_permission
from celerymethods.jobs.reconfig import alienvault_reconfigure
from apimethods.sensor.sensor import set_sensor_context, add_sensor, get_service_status_by_id
from ansiblemethods.sensor.network import set_sensor_networks, get_sensor_networks
from ansiblemethods.sensor.ossec import get_ossec_rule_filenames

CONFIG_FILE = "/etc/ossim/ossim_setup.conf"

blueprint = Blueprint(__name__, __name__)


@blueprint.route('', methods=['GET'])
@document_using('static/apidocs/sensors.html')
@admin_permission.require(http_exception=403)
def get_sensors():
    ret, sensor_data = get_systems_full(system_type='Sensor')
    if ret is True:
        sensor_list = []
        for sensor in sensor_data:
            ret, sensor_id = get_sensor_id_from_system_id(sensor[0])
            if ret:
                sensor_list.append((sensor_id, {'admin_ip': sensor[1]['admin_ip'],
                                                'hostname': sensor[1]['hostname'],
                                                'system_id': sensor[1]['uuid']}))

        return make_ok(sensors=dict(sensor_list))

    current_app.logger.error("sensor: get_sensors error: " + str(sensor_data))
    return make_error("Cannot retrieve sensors info", 500)


@blueprint.route('/<sensor_id>', methods=['GET'])
@document_using('static/apidocs/sensors.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_sensor(sensor_id):
    rc, data = get_sensor_by_sensor_id(sensor_id)
    if rc and data is not None:
        return make_ok(sensor=data.serialize)
    return make_bad_request(data)


@blueprint.route('/<sensor_id>', methods=['PUT'])
@document_using('static/apidocs/center.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}, 'ctx': UUID})
def put_sensor(sensor_id):

    password = request.args.get('password', None)
    if password is not None:
        (success, response) = add_sensor(sensor_id, request.args.get('password'))
        if not success:
            api_log.error(str(response))
            return make_error("Error adding sensor, please check the system is reachable and the password is correct", 500)

    (success, job_id) = set_sensor_context(sensor_id,
                                           request.args.get('ctx').lower())
    if not success:
        return make_error("Error setting sensor context", 500)

    return make_ok(job_id=job_id)


@blueprint.route('/<sensor_id>/network', methods=['PUT'])
@document_using('static/apidocs/sensor.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}, 'nets': str})
def set_sensor_network(sensor_id):
    netlist = request.args.get('nets').split(",")
    (ret, admin_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not ret:
        current_app.logger.error("sensor: auth_sensor error: " + str(admin_ip))
        return make_bad_request(sensor_id)

    (success, data) = set_sensor_networks(admin_ip, netlist)
    if not success:
        current_app.logger.error("sensor: Can't set sensor networks to " + str(netlist))
        return make_bad_request(sensor_id)
    # Launch configure
    job = alienvault_reconfigure.delay(admin_ip)
    # Now format the list by a dict which key is the sensor_id and the value if the list of ifaces
    return make_ok(job_id_reconfig=job.id)


@blueprint.route('/<sensor_id>/network', methods=['GET'])
@document_using('static/apidocs/sensor.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_sensor_network(sensor_id):
    (ret, admin_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not ret:
        current_app.logger.error("sensor: auth_sensor error: " + str(admin_ip))
        return make_bad_request(sensor_id)

    (success, data) = get_sensor_networks(admin_ip)
    if not success:
        current_app.logger.error("sensor: Can't get  sensor networks for  " + str(sensor_id) + " msg: " + str(data))
        return make_bad_request(sensor_id)
    else:
        return make_ok(networks=data)


@blueprint.route('/credential/<host_ip>', methods=['GET'])
@document_using('static/apidocs/sensor.html')
@admin_permission.require(http_exception=403)
@accepted_url({'host_ip': {'type': str}, 'user': {'type': str}, 'pass': {'type': str},
               'method': {'type': str, 'values': ['smb', 'ssh']}})
def check_credentials(host_ip):
    (ret, admin_ip) = get_sensor_ip_from_sensor_id('local')

    if not ret:
        abort(500, "local sensor not found")
    #TODO: the method check_credentials_from_sensor doesn't exist
    #(success, data) = check_credentials_from_sensor(admin_ip, host_ip, request.args.get('user'),
    #                                               request.args.get('pass'), request.args.get('method'))
    success, data = True, "OK"
    if not success:
        current_app.logger.error("Cannot check host " + str(host_ip) + " credentials; msg: " + str(data))
        abort(500, "Cannot check host " + str(host_ip) + " credentials; msg: " + str(data))

    return make_ok(result=data)



@blueprint.route('/<sensor_id>/ossec/rules', methods=['GET'])
@document_using('static/apidocs/sensor.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_ossec_rules_filenames(sensor_id):
    (ret, admin_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not ret:
        current_app.logger.error("sensor: auth_sensor error: " + str(admin_ip))
        return make_bad_request(sensor_id)

    (success, data) = get_ossec_rule_filenames(admin_ip)
    if not success:
        current_app.logger.error("sensor: Can't get  sensor networks for  " + str(sensor_id) + " msg: " + str(data))
        return make_bad_request(sensor_id)
    else:
        return make_ok(rules=data)
        
@blueprint.route('/<sensor_id>/service_status', methods=['GET'])
@document_using('static/apidocs/sensor.html')
@logged_permission.require(http_exception=401)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_service_status(sensor_id):
    (success, data) = get_service_status_by_id(sensor_id)
    if not success:
        current_app.logger.error("sensor: Can't get services status " + str(sensor_id) + " msg: " + str(data))
        return make_bad_request(sensor_id)
    else:
        return make_ok(**data)
