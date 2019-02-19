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
from db.methods.sensor import get_sensor_ip_from_sensor_id
from db.methods.system import get_system_id_from_local
from db.methods.sensor import get_base_path
from apimethods.utils import get_base_path_from_system_id
from ansiblemethods.helper import fire_trigger
from ansiblemethods.system.system import set_av_config, ansible_add_system
from ansiblemethods.sensor.detector import get_sensor_detectors_from_yaml
from ansiblemethods.sensor.service import get_service_status_by_ip
from ansiblemethods.sensor.plugin import get_plugin_package_version as ans_get_plugin_package_info
from celerymethods.jobs.reconfig import job_alienvault_reconfigure
from apimethods.system.cache import use_cache
from apiexceptions.sensor import (APICannotAddSensor,
                                  APICannotSetSensorContext)

import api_log


def set_sensor_context(sensor_id, context):
    """
    Set the context for the sensor with sensor_id
    @param sensor_id: sensor id
    @param context: sensor context
    """
    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return (False, system_ip)

    # Set av_config values and apply new configuration
    set_values = {}
    set_values['sensor_sensor_ctx'] = context
    (success, data) = set_av_config(system_ip=system_ip,
                                    path_dict=set_values)
    if not success:
        api_log.error(data)
        return (False, data)

    (success, job_id) = job_alienvault_reconfigure(system_ip)
    return (success, job_id)


def add_sensor(sensor_id, password):
    """
    Add the system for sensor_id
    """

    (success, system_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return (False, system_ip)

    (success, local_system_id) = ret = get_system_id_from_local()
    if not success:
        return ret

    (success, response) = ansible_add_system(local_system_id=local_system_id,
                                             remote_system_ip=system_ip,
                                             password=password)

    return (success, response)


def apimethod_add_sensor(sensor_id, password, ctx):

    if password is not None:
        (success, response) = add_sensor(sensor_id, password)
        if not success:
            raise APICannotAddSensor(sensor_id,
                                     log=str(response))

    (success, job_id) = set_sensor_context(sensor_id, ctx)
    if not success:
        raise APICannotSetSensorContext(sensor_id)

    trigger_success, msg = fire_trigger(system_ip="127.0.0.1",
                                        trigger="alienvault-new-sensor")

    if not trigger_success:
        api_log.error(msg)

    return job_id


def get_base_path_from_sensor_id(sensor_id):
    if sensor_id == 'local':
        rt, system_id = get_system_id_from_local()
        if not rt:
            return False, "Can't retrieve the system id"
        return True, get_base_path_from_system_id(system_id)

    # rt, system_id = get_system_id_from_sensor_id(sensor_id)
    # if not rt:
    #    return False, "Can't retrieve the system id"
    # return True, get_base_path_from_system_id(system_id)
    return get_base_path(sensor_id)


@use_cache(namespace="sensor_plugins")
def get_plugins_from_yaml(sensor_id, no_cache=False):
    (rt, admin_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not rt:
        return False, "Can't retrieve the system id"
    return get_sensor_detectors_from_yaml(admin_ip)


def get_service_status_by_id(sensor_id):
    """
    Return a list of processes with their statuses (suricata, prads and ossec)
    """
    (success, ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return False, ip

    return get_service_status_by_ip(ip)


def get_plugin_package_info(sensor_id):
    """
        Return the current version of package alienvault-api-sids in
        sensor with id sensor_id
    """
    (success, ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if success:
        return ans_get_plugin_package_info(ip)
    else:
        return (False, ip)
