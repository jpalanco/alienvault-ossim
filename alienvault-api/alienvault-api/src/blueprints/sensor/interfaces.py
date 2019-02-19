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
from ansiblemethods.sensor.network import get_sensor_interfaces, set_sensor_interfaces
from flask import Blueprint, request, current_app
from api.lib.common import (
    http_method_dispatcher, make_ok, make_error, make_bad_request, check,
    document_using, validate_json, if_content_exists_then_is_json)
from apimethods.utils import get_bytes_from_uuid, get_ip_str_from_bytes
from api.lib.utils import accepted_url
from db.methods.sensor import get_sensor_ip_from_sensor_id
from celerymethods.jobs.reconfig import alienvault_reconfigure
from uuid import UUID
from api.lib.auth import admin_permission

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<sensor_id>/interface', methods=['GET'])
@document_using('static/apidocs/center.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_sensor_interface(sensor_id):
    """
    Return the [sensor]/interfaces list from ossim_setup.conf of sensor
    """
    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        current_app.logger.error("interfaces: get_sensor_interface  error: Bad 'sensor_id'")
        return make_bad_request("Bad sensor_id")

    # Now call the ansible module to obtain the [sensor]/iface
    (success, data) = get_sensor_interfaces(sensor_ip)
    if not success:
        current_app.logger.error("interfaces: get_sensor_interfaces_from_conf error: %s" % data)
        return make_error("Error getting sensor interfaces", 500)

    # Now format the list by a dict which key is the sensor_id and the value if the list of ifaces
    return make_ok(interfaces=data)


@blueprint.route('/<sensor_id>/interface', methods=['PUT'])
@document_using('static/apidocs/center.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}, 'ifaces': str})
def put_sensor_interface(sensor_id):
    """
    Set the [sensor]/interfaces list on ossim_setup.conf of the sensor
    """
    # Get the 'ifaces' param list, with contains the ifaces
    # It must be a comma separate list
    ifaces = request.args.get('ifaces')
    if ifaces is None:
        current_app.logger.error("interfaces: put_sensor_interface error: Missing parameter 'ifaces'")
        return make_bad_request("Missing parameter ifaces")

    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        current_app.logger.error("interfaces: put_sensor_interface  error: Bad 'sensor_id'")
        return make_bad_request("Bad sensor_id")

    # Call the ansible module to obtain the [sensor]/iface
    (success, data) = set_sensor_interfaces(sensor_ip, ifaces)
    if not success:
        current_app.logger.error("interfaces: put_sensor_interfaces_from_conf error: %s" % data)
        return make_error("Error setting sensor interfaces", 500)

    # Now launch reconfig task
    job = alienvault_reconfigure.delay(sensor_ip)

    # Now format the list by a dict which key is the sensor_id and the value if the list of ifaces
    return make_ok(job_id_reconfig=job.id)

