# -*- coding: utf-8 -*-
#
# License:
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

from uuid import UUID
import json
import os.path

from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from sqlalchemy import desc, asc, and_, or_, func

import db
from db.models.alienvault import (
    Sensor,
    Host_Sensor_Reference,
    System,
    Sensor_Properties,
    Acl_Sensors,
    Acl_Entities
)
from db.methods.system import (
    get_system_id_from_system_ip,
    get_system_ip_from_local,
    get_system_id_from_local,
    get_sensor_id_from_system_id
)
from db.methods.api import get_monitor_data
import api_log
from apimethods.decorators import (
    accepted_values,
    accepted_types,
    require_db
)
from apimethods.utils import (
    get_bytes_from_uuid,
    get_ip_str_from_bytes,
    get_ip_bin_from_str,
    get_uuid_string_from_bytes,
    compare_dpkg_version,
    is_valid_uuid
)

from apiexceptions.sensor import APICannotResolveSensorID
from apiexceptions.system import APICannotResolveLocalSystemID

from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler

ossim_setup = AVOssimSetupConfigHandler()
#
# I would prefer this constant defined in one file
#
MONITOR_PLUGINS_VERSION = 12


@require_db
@accepted_types(UUID)
@accepted_values([], ['str', 'bin'])
def get_system_id_from_sensor_id(sensor_id, output='str'):
    """
    Return the system id of a sensor using its id
    """
    sensor_id_bin = get_bytes_from_uuid(sensor_id)

    try:
        sensor_ids = [x.id for x in
                      db.session.query(System).filter(System.sensor_id == sensor_id_bin).order_by(System.id).all()]
    except NoResultFound, msg:
        return (False, "No sensor ip address found with sensor id '%s'" % str(sensor_id))
    except MultipleResultsFound, msg:
        return (False, "More than one sensor ip address found with sensor id '%s'" % str(sensor_id))
    except Exception, msg:
        return (False, "Error captured while querying for sensor id '%s': %s" % (str(sensor_id), str(msg)))
    if output == 'str':
        return (True, get_uuid_string_from_bytes(sensor_ids[0]))
    else:
        return (True, sensor_ids[0])


@require_db
def get_sensor_by_sensor_id(sensor_id):
    """Returns a Sensor object given a Sensor ID"""
    try:
        # Getting Sensor ID for local system
        if sensor_id.lower() == 'local':
            (success, system_id) = get_system_id_from_local()

            if not success:
                raise APICannotResolveLocalSystemID()

            (success, local_sensor_id) = get_sensor_id_from_system_id(system_id)

            if success and local_sensor_id:
                sensor_id = local_sensor_id

        if not is_valid_uuid(sensor_id):
            raise APICannotResolveSensorID(sensor_id)

        # Getting sensor information
        success = True
        sensor_id_bin = get_bytes_from_uuid(sensor_id.lower())
        data = db.session.query(Sensor).filter(Sensor.id == sensor_id_bin).one()
    except NoResultFound:
        success = False
        data = "No sensor found with the given ID"
    except MultipleResultsFound:
        success = False
        data = "More than one sensor found with the given ID"
    except Exception as ex:
        success = False
        data = "Something wrong happen while retrieving the sensor {0}".format(ex)

    return success, data


@require_db
def get_sensor_from_alienvault(ip=""):
    """Returns the sensor list from alienvault.sensor table
    :param ip(dotted string) filter by ip. Whether ip is empty returns all the sensors."""
    sensor_list = []
    try:
        if ip is "":
            sensor_list = db.session.query(Sensor).all()
        else:
            sensor = db.session.query(Sensor).filter(Sensor.ip == get_ip_bin_from_str(ip)).one()
            sensor_list.append(sensor)
    except Exception:
        sensor_list = []
    return sensor_list


@require_db
def get_sensors_for_asset(host_id):
    """Retrieves the sensor list for a given context
    """
    sensor_list = []
    try:

        tmp_list = db.session.query(Host_Sensor_Reference).filter(Host_Sensor_Reference.host_id == host_id).all()
        sensor_ids = [s.sensor_id for s in tmp_list]
        if len(sensor_ids) > 0:
            sensor_list = db.session.query(Sensor).filter(Sensor.id.in_(sensor_ids)).all()
    except Exception:
        sensor_list = []
    return sensor_list


@require_db
def get_ids_of_logging_devices_per_sensor(sensor_ip, device_ips):
    devices = {}
    ip_list = ','.join("inet6_aton(\"%s\")" % i for i in device_ips)
    query = "SELECT DISTINCT hex(host.id), inet6_ntoa(host_ip.ip) FROM host, host_ip, acl_sensors, sensor WHERE " \
            "acl_sensors.sensor_id=sensor.id AND host.ctx=acl_sensors.entity_id AND host.id=host_ip.host_id AND " \
            "host_ip.ip IN (%s) AND sensor.ip=inet6_aton(\"%s\");" % (ip_list, sensor_ip)
    try:
        data = db.session.connection(mapper=Sensor).execute(query)
        for row in data:
            devices[row[0]] = row[1]
    except Exception:
        devices = {}
    return devices


@require_db
def get_list_of_device_ids_per_sensor(sensor_ip):
    devices = {}
    query = "SELECT DISTINCT hex(host.id), inet6_ntoa(host_ip.ip) FROM host, host_ip, acl_sensors, sensor WHERE " \
            "acl_sensors.sensor_id=sensor.id AND host.ctx=acl_sensors.entity_id AND host.id=host_ip.host_id AND " \
            "sensor.ip=inet6_aton(\"%s\");" % sensor_ip
    try:
        data = db.session.connection(mapper=Sensor).execute(query)
        for row in data:
            devices[row[0]] = row[1]
    except Exception:
        devices = {}
    return devices


@require_db
def get_sensor_ip_from_sensor_id(sensor_id, output='str', local_loopback=True):
    try:
        if sensor_id.lower() == 'local':
            if AVOssimSetupConfigHandler.PROFILE_NAME_SENSOR not in ossim_setup.get_general_profile_list():
                return False, "Local system is not a sensor"
            (success, sensor_ip) = get_system_ip_from_local(output='bin', local_loopback=local_loopback)
            if not success:
                return success, sensor_ip
        else:
            sensor_id_bin = get_bytes_from_uuid(sensor_id.lower())
            system = db.session.query(System).filter(System.sensor_id == sensor_id_bin).first()
            if system:
                if system.ha_ip:
                    sensor_ip = system.ha_ip
                elif system.vpn_ip:
                    sensor_ip = system.vpn_ip
                else:
                    sensor_ip = system.admin_ip
            else:
                return (False, "No system found with id '%s'" % str(sensor_id))
    except Exception, msg:
        return (False, "Error captured while querying for system id '%s': %s" % (str(sensor_id), str(msg)))

    if output == 'str':
        try:
            sensor_ip_str = get_ip_str_from_bytes(sensor_ip)
        except Exception, msg:
            return (False, "Cannot convert supposed system ip '%s' to its string form: %s" % (str(sensor_ip), str(msg)))
        sensor_ip = sensor_ip_str

    return (True, sensor_ip)


@require_db
def get_sensor_id_from_sensor_ip(sensor_ip):
    try:
        sensor = db.session.query(Sensor).filter(Sensor.ip == get_ip_bin_from_str(sensor_ip)).one()
        sensor_id = get_uuid_string_from_bytes(sensor.id)
    except NoResultFound:
        return False, "No sensor id found for the given sensor ip"
    except MultipleResultsFound:
        return False, "More than one sensor with the same sensor ip"
    except Exception as msg:
        api_log.error(str(msg))
        return False, msg

    return True, sensor_id


@require_db
def get_sensor_id_from_system_id(system_id):
    try:
        system_id_binary = get_bytes_from_uuid(system_id.lower())
        sensor_id = db.session.query(System).filter(System.id == system_id_binary).one()
        sensor_id_str = get_uuid_string_from_bytes(sensor_id.sensor_id)
    except NoResultFound:
        return False, "No sensor id found for the given system id"
    except MultipleResultsFound:
        return False, "More than one sensor id for the same system id"
    except Exception as msg:
        return False, msg

    return True, sensor_id_str


@require_db
def get_sensor_properties(sensor_id):
    result = True
    try:
        properties = db.session.query(Sensor_Properties).filter(
            Sensor_Properties.sensor_id == get_bytes_from_uuid(sensor_id)).one()
        message = {'has_nagios': bool(properties.has_nagios),
                   'has_ntop': bool(properties.has_ntop),
                   'has_vuln_scanner': bool(properties.has_vuln_scanner),
                   'has_kismet': bool(properties.has_kismet),
                   'ids': bool(properties.ids),
                   'passive_inventory': bool(properties.passive_inventory),
                   'netflows': bool(properties.netflows)}

    except NoResultFound:
        result = False
        message = "No properties found for sensor %s" % str(sensor_id)
    except MultipleResultsFound:
        result = False
        message = "More than one set of properties found for sensor %s" % str(sensor_id)
    except Exception, msg:
        result = False
        message = "An error occurred while retrieving properties for sensor %s: %s" % (str(sensor_id), str(msg))

    return (result, message)


@require_db
def set_sensor_properties_value(sensor_id, property_name, value):
    result = True
    try:
        message = ""
        db.session.begin()
        sensor_id_binary = get_bytes_from_uuid(sensor_id)
        property = db.session.query(Sensor_Properties).filter(Sensor_Properties.sensor_id == sensor_id_binary).one()
        if property is not None:
            if hasattr(property, property_name):
                setattr(property, property_name, value)
                db.session.merge(property)
            else:
                result = False
                message = "Invalid property name <%s>" % property_name
        db.session.commit()
    except NoResultFound:
        db.session.rollback()
        result = False
        message = "No property found "
    except MultipleResultsFound:
        db.session.rollback()
        result = False
        message = "More than one value for the property!! "
    except Exception as err:
        db.session.rollback()
        result = False
        message = "An error occurred while setting the sensor_property value %s" % str(err)

    return result, message


def set_sensor_properties_active_inventory(sensor_id, value):
    return set_sensor_properties_value(sensor_id, 'ids', value)


def set_sensor_properties_passive_inventory(sensor_id, value):
    return set_sensor_properties_value(sensor_id, 'passive_inventory', value)


def set_sensor_properties_netflow(sensor_id, value):
    return set_sensor_properties_value(sensor_id, 'netflows', value)


def get_newest_plugin_system():
    """
        Get the current stored plugin packages version. Check all sensor
        and compared with all sensors. Return the newest information. Here we can have several
        scenarios we have to manage. This function can be called in a system with framework and without
        sensors - no sense, but this scenario can exists in a instalation -, we must be sure that
    """
    current_sensors = get_monitor_data(MONITOR_PLUGINS_VERSION)
    system_id = None
    md5 = None
    max_sensor = None
    system_id = None
    if current_sensors is not None:
        system_id = current_sensors[0]['component_id']
        monitor_data = json.loads(current_sensors[0]['data'])
        md5 = monitor_data['md5']
        version = monitor_data['version']
        for sensor in current_sensors[1:]:
            check_monitor_data = json.loads(sensor['data'])
            check_version = check_monitor_data['version']
            if compare_dpkg_version(check_version, version) == "greater":
                result, system_id = get_system_id_from_sensor_id(sensor['component_id'])
                if result:
                    md5 = check_monitor_data['md5']
                    version = check_version
                else:
                    system_id = None
                    md5 = None

    return (system_id, md5)


@require_db
def check_any_orphan_sensor():
    """
        Checks the existance of sensors which have not been inserted in the system
    """
    try:
        result = db.session.query(Sensor, Sensor_Properties).filter(Sensor_Properties.sensor_id == Sensor.id).filter(
            Sensor.name == '(null)').filter(Sensor_Properties.version != '').one()
        success = False
        message = "There seems to be orphan sensors which have not been added to a server"
    except NoResultFound:
        message = "There are no orphan sensors"
        success = True
    except MultipleResultsFound:
        message = "There seems to be more than one orphan sensor"
        success = False
    except Exception as e:
        message = "An error occurred while looking for orphan sensors: %s" % (str(e))
        success = False

    return success, message


@require_db
def get_base_path(sensor_id):
    """
        Return the base PATH taking into account the ha configuration
    """
    try:
        data = db.session.query(System).filter(or_(System.sensor_id == get_bytes_from_uuid(sensor_id),
                                                   System.server_id == get_bytes_from_uuid(sensor_id))).order_by(
            asc(System.ha_name)).limit(1).one()
        result = True, os.path.join("/var/alienvault/", data.serialize['uuid'])
    except NoResultFound:
        result = False, "No sensor identified by " + sensor_id
    return result


@require_db
@accepted_types(UUID)
@accepted_values([], ['str', 'bin'])
def get_sensor_ctx_by_sensor_id(sensor_id, output='str'):
    """
        Returns a sensor CTX given a sensor ID
    """
    sensor_ctx = None

    try:
        if sensor_id:
            sensor_id_bin = get_bytes_from_uuid(sensor_id)

            query = db.session.query(Acl_Sensors.entity_id).filter(Acl_Sensors.sensor_id == sensor_id_bin)
            sensor = query.join(Acl_Entities, Acl_Entities.id == Acl_Sensors.entity_id).filter(
                Acl_Entities.entity_type == 'context').one()
            sensor_ctx = sensor.entity_id
        else:
            return False, "Sensor ID could not be empty"
    except NoResultFound:
        return True, sensor_ctx
    except Exception as msg:
        msg = str(msg)
        api_log.error(msg)
        return False, msg

    if output == 'str':
        return True, get_uuid_string_from_bytes(sensor_ctx)
    else:
        return True, sensor_ctx
