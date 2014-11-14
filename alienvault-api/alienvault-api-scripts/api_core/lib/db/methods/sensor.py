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

from uuid import UUID

from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound

import db
from db.models.alienvault import Sensor, Host_Sensor_Reference, System, Sensor_Properties
from db.methods.system import get_system_id_from_system_ip, get_system_ip_from_local

import api_log
from apimethods.decorators import accepted_values, accepted_types, require_db
from apimethods.utils import (get_bytes_from_uuid,
                              get_ip_str_from_bytes,
                              get_ip_bin_from_str,
                              get_uuid_string_from_bytes)

from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler
ossim_setup = AVOssimSetupConfigHandler()


@require_db
@accepted_types(UUID)
@accepted_values([], ['str', 'bin'])
def get_system_id_from_sensor_id(sensor_id, output='str'):
    """
    Return the system id of a sensor using its id
    """
    sensor_id_bin = get_bytes_from_uuid(sensor_id)

    try:
        sensor_ip = db.session.query(Sensor).filter(Sensor.id == sensor_id_bin).one().ip
    except NoResultFound, msg:
        return (False, "No sensor ip address found with sensor id '%s'" % str(sensor_id))
    except MultipleResultsFound, msg:
        return (False, "More than one sensor ip address found with sensor id '%s'" % str(sensor_id))
    except Exception, msg:
        db.session.rollback()
        return (False, "Error captured while querying for sensor id '%s': %s" % (str(sensor_id), str(msg)))

    try:
        sensor_ip_str = get_ip_str_from_bytes(sensor_ip)
    except Exception, msg:
        return (False, "Cannot convert supposed sensor ip '%s' to its string form: %s" % (str(sensor_ip), str(msg)))

    return (get_system_id_from_system_ip(sensor_ip_str, output=output))


@require_db
def get_sensor_by_sensor_id(sensor_id):
    """Returns a Sensor object given a sensor id"""
    sensor = None
    try:
        rc, sensor_ip = get_sensor_ip_from_sensor_id(sensor_id, local_loopback=False)
        if not rc:
            return False, "Can't retrieve the sensor ip"
        sensors = get_sensor_from_alienvault(sensor_ip)
        if len(sensors) > 0:
            sensor = sensors[0]
    except NoResultFound:
        return False, "No sensor found with the given ID"
    except MultipleResultsFound:
        return False, "More than one sensor found with the given ID"
    except Exception as ex:
        db.session.rollback()
        return False, "Something wrong happen while retrieving the sensors  %s" % str(ex)
    return True, sensor


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
        db.session.rollback()
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
        db.session.rollback()
        sensor_list = []
    return sensor_list


@require_db
def get_devices_ids_list_per_sensor(sensor_ip, device_ips):
    devices = {}
    ip_list = ','.join("inet6_pton(\"%s\")" % i for i in device_ips)
    query = "SELECT DISTINCT hex(host.id), inet6_ntop(host_ip.ip) FROM host, host_ip, acl_sensors, sensor WHERE " \
            "acl_sensors.sensor_id=sensor.id AND host.ctx=acl_sensors.entity_id AND host.id=host_ip.host_id AND " \
            "host_ip.ip IN (%s) AND sensor.ip=inet6_pton(\"%s\");" % (ip_list, sensor_ip)
    try:
        data = db.session.connection(mapper=Sensor).execute(query)
        for row in data:
            devices[row[0]] = row[1]
    except Exception:
        devices = {}
        db.session.rollback()
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
        db.session.rollback()
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
        properties = db.session.query(Sensor_Properties).filter(Sensor_Properties.sensor_id == get_bytes_from_uuid(sensor_id)).one()
        message = {'has_nagios': bool(properties.has_nagios),
                   'has_ntop': bool(properties.has_ntop),
                   'has_vuln_scanner': bool(properties.has_vuln_scanner),
                   'has_kismet': bool(properties.has_kismet),
                   'ids': bool(properties.ids),
                   'passive_inventory': bool(properties.passive_inventory),
                   'netflows': bool(properties.netflows)}

    except NoResultFound:
        db.session.rollback()
        result = False
        message = "No properties found for sensor %s" % str(sensor_id)
    except MultipleResultsFound:
        db.session.rollback()
        result = False
        message = "More than one set of properties found for sensor %s" % str(sensor_id)
    except Exception, msg:
        db.session.rollback()
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
