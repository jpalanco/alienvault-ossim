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
import json
from uuid import UUID
import os
from flask import Blueprint, request, current_app
from ansiblemethods.sensor.detector import get_sensor_detectors, \
    set_sensor_detectors, \
    get_sensor_detectors_from_yaml, \
    set_sensor_detectors_from_yaml
from ansiblemethods.sensor.plugin import get_all_available_plugins
from api.lib.common import make_ok, \
    make_error, \
    make_bad_request, \
    document_using
from api.lib.utils import accepted_url
from db.methods.sensor import get_sensor_ip_from_sensor_id
from db.methods.data import get_asset_ip_from_id
from celerymethods.jobs.reconfig import alienvault_reconfigure
from celerymethods.jobs.alienvault_agent_restart import restart_alienvault_agent
from api.lib.auth import admin_permission

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<sensor_id>/detector', methods=['GET'])
@document_using('static/apidocs/sensor/detector.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def get_sensor_detector(sensor_id):
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


@blueprint.route('/<sensor_id>/detector', methods=['PUT'])
@document_using('static/apidocs/sensor/detector.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}, 'plugins': str})
def put_sensor_detector(sensor_id):
    """
    Set the [sensor]/detectors list on ossim_setup.conf of the sensor
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


def get_plugin_get_request_from_yml(yml_data, device_id=None):
    if yml_data == {} or yml_data is None or yml_data == "":
        return {}
    device_plugins = {}
    for plugin in yml_data['plugins']:
        for key, value in plugin.iteritems():
            device = value['DEFAULT']['device_id']
            cpe = value['DEFAULT'].get('cpe', "cpe:none")
            pid = value['DEFAULT'].get('pid', "none")
            if not device_plugins.has_key(device):
                device_plugins[device] = {}
            device_plugins[device][os.path.splitext(os.path.basename(key))[0]] = {'cpe': cpe, 'plugin_id': pid}
    result_data = {}
    if device_id is not None:
        if device_plugins.has_key(device_id):
            print device_plugins.has_key(device_id)
            result_data = {str(device_id): device_plugins[str(device_id)]}
    else:
        result_data = device_plugins
    return result_data


@blueprint.route('/<sensor_id>/plugins', methods=['GET'])
@document_using('static/apidocs/sensor/plugins.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}, 'device_id': UUID})
def get_sensor_detector_by_device(sensor_id):
    """
    Return the [sensor]/plugin list for a given sensor
    :param sensor_id: The sensor which we want to get the data
    :param device_id: Filter by device (canonical uuid)
    """
    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        current_app.logger.error("detector: get_sensor_detector: Bad 'sensor_id'")
        return make_bad_request("Bad sensor_id")

    device_id = request.args.get('device_id', None)

    # Now call the ansible module to obtain the [sensor]/iface
    (success, data) = get_sensor_detectors_from_yaml(sensor_ip)
    if not success:
        current_app.logger.error("detector: get_sensor_detector_by_device: %s" % str(data))
        return make_error("Error getting sensor plugins", 500)
    try:
        yaml_data = get_plugin_get_request_from_yml(data['contacted'][sensor_ip]['plugins'], device_id)
    except:
        return make_error("Something wrong while parsing the yml file. %s" % data, 500)
    # Now format the list by a dict which key is the sensor_id and the value if the list of ifaces
    return make_ok(plugins=yaml_data)


@blueprint.route('/<sensor_id>/plugins', methods=['POST'])
@document_using('static/apidocs/sensor/detector.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}, 'plugins': str})
def put_sensor_detector_by_device(sensor_id):
    """
    Set the [sensor]/detectors list on config.yml of the sensor
    """
    # Get the 'plugins' param list, with contains the detector plugins
    # It must be a comma separate list
    plugins = request.form['plugins']
    if plugins is None:
        current_app.logger.error("detector: put_sensor_detector error: Missing parameter 'plugins'")
        return make_bad_request("Missing parameter plugins")

    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        current_app.logger.error("detector: put_sensor_detector error: Bad 'sensor_id'")
        return make_bad_request("Bad sensor_id")
    plugins_hash = {}
    try:
        plugins = json.loads(plugins)
        for device_id, plugins in plugins.iteritems():
            ips = get_asset_ip_from_id(device_id)
            if len(ips) > 0:
                plugins_hash[device_id] = {"device_ip": ips[0],  # A device  should never have more than one IP
                                           "plugins": plugins}
    except Exception, e:
        return make_bad_request("Invalid JSON: %s , p=%s" % ("", str(plugins)))
    try:
        (success, data) = set_sensor_detectors_from_yaml(sensor_ip, str(plugins_hash))
    except Exception as err:
        return make_error("Something wrong happen while getting the yaml information %s" % str(err))
    if not success:
        current_app.logger.error("detector: put_sensor_detector error %s" % data)
        return make_error("Error setting sensor detector plugins: %s" % data, 500)

    # Now restart the alienvault agent
    job = restart_alienvault_agent.delay(sensor_ip=sensor_ip)
    # Now format the list by a dict which key is the sensor_id and the value if the list of ifaces
    return make_ok(result=data, jobid=job.id)


@blueprint.route('/<sensor_id>/detector/all', methods=['GET'])
@document_using('static/apidocs/sensor/all.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}, 'device_id': UUID})
def get_available_plugins(sensor_id):
    """
    Return the [sensor]/plugin list for a given sensor
    :param sensor_id: The sensor which we want to get the data
    :param device_id: Filter by device (canonical uuid)
    """
    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        current_app.logger.error("detector: get_sensor_detector: Bad 'sensor_id'")
        return make_bad_request("Bad sensor_id")

    device_id = request.args.get('device_id', None)

    # Now call the ansible module to obtain the [sensor]/iface
    (success, data) = get_all_available_plugins(sensor_ip, only_detectors=True)
    if not success:
        current_app.logger.error("detector: get_all_available_detector_plugins: %s" % str(data))
        return make_error("Error getting available plugins", 500)
    return make_ok(plugins=data)
