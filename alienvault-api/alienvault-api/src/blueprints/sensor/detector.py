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
from uuid import UUID
from flask import Blueprint, request, current_app
from ansiblemethods.sensor.detector import (
    get_sensor_detectors,
    set_sensor_detectors)
from api.lib.common import (
    make_ok,
    make_error,
    make_bad_request,
    make_error_from_exception,
    document_using)
from api.lib.utils import accepted_url
from apimethods.sensor.plugin import (
    get_sensor_plugins_enabled_by_asset,
    set_sensor_plugins_enabled_by_asset,
    get_sensor_detector_plugins)

from db.methods.sensor import get_sensor_ip_from_sensor_id
from celerymethods.jobs.reconfig import alienvault_reconfigure
from api.lib.auth import (
    admin_permission,
    logged_permission)

from apiexceptions import APIException

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<sensor_id>/plugins/detector/enabled', methods=['GET'])
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def bp_get_sensor_plugins_detector_enabled(sensor_id):
    """
    Return the [sensor]/plugin list from ossim_setup.conf of sensor
    """
    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        current_app.logger.error("detector: get_sensor_detector: Bad 'sensor_id'")
        return make_bad_request("Bad sensor_id")

    # Now call the ansible module to obtain the [sensor]/iface
    (success, data) = get_sensor_detectors(sensor_ip)
    if not success:
        current_app.logger.error("detector: get_sensor_detector: %s" % str(data))
        return make_error("Error getting sensor plugins", 500)

    # Now format the list by a dict which key is the sensor_id and the value if the list of ifaces
    return make_ok(plugins=data)


@blueprint.route('/<sensor_id>/plugins/detector/enabled', methods=['PUT'])
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'plugins': str})
def bp_put_sensor_plugins_detector_enabled(sensor_id):
    """
    Set the [sensor]/detectors list on ossim_setup.conf of the sensor
    plugins: Comma separate list of detector plugins to activate. Must exists in the machine
    """
    # Get the 'plugins' param list, with contains the detector plugins
    # It must be a comma separate list
    plugins = request.args.get('plugins')
    if plugins is None:
        current_app.logger.error("detector: put_sensor_detector error: Missing parameter 'plugins'")
        return make_bad_request("Missing parameter plugins")

    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        current_app.logger.error("detector: put_sensor_detector error: Bad 'sensor_id'")
        return make_bad_request("Bad sensor_id")

    (success, data) = set_sensor_detectors(sensor_ip, plugins)
    if not success:
        current_app.logger.error("detector: put_sensor_detector error %s" % data)
        return make_error("Error setting sensor detector plugins", 500)

    # Now launch reconfig task
    job = alienvault_reconfigure.delay(sensor_ip)

    # Now format the list by a dict which key is the sensor_id and the value if the list of ifaces
    return make_ok(job_id_reconfig=job.id)


@blueprint.route('/<sensor_id>/plugins/asset/enabled', methods=['GET'])
@document_using('static/apidocs/sensor/plugins.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'asset_id': {'type': UUID, 'optional': True}})
def bp_get_sensor_plugins_asset_enabled(sensor_id):
    """
    Return the plugins enabled by asset in a sensor filtered by asset_id
    :param sensor_id: The sensor which we want to get the data
    :param asset_id: Filter by asset (canonical uuid)
    """
    asset_id = request.args.get('asset_id', None)
    try:
        plugins = get_sensor_plugins_enabled_by_asset(sensor_id=sensor_id,
                                                      asset_id=asset_id)
    except APIException as e:
        return make_error_from_exception(e)

    return make_ok(plugins=plugins)


@blueprint.route('/<sensor_id>/plugins/asset/enabled', methods=['POST'])
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'plugins': str})
def bp_post_sensor_plugins_asset_enabled(sensor_id):
    """
    Set the plugins enabled by asset (config.yml) in the sensor
    plugins: JSON string: {<asset_id>: [
                              <plugin_name>,
                              ...],
                           ...}
    """
    # Get the 'plugins' param list, with contains json with the  plugins
    # It must be a comma separate list
    plugins = request.form['plugins']
    if plugins is None:
        current_app.logger.error("detector: put_sensor_detector error: Missing parameter 'plugins'")
        return make_bad_request("Missing parameter plugins")

    try:
        job_id = set_sensor_plugins_enabled_by_asset(sensor_id, plugins)
    except APIException as e:
        return make_error_from_exception(e)

    return make_ok(jobid=job_id)


@blueprint.route('/<sensor_id>/plugins/detector', methods=['GET'])
@logged_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def bp_get_sensor_detector_plugins(sensor_id):
    """
    Return the plugins of type 'detector' in a sensor
    :param sensor_id: The sensor which we want to get the data
    """
    try:
        plugins = get_sensor_detector_plugins(sensor_id)
    except APIException as e:
        return make_error_from_exception(e)

    return make_ok(plugins=plugins)
