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

from sqlalchemy import text as sqltext
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from sqlalchemy import or_, and_

import re
import db

from db.models.alienvault import (
    Server,
    Sensor,
    Config,
    System,
    Server_Hierarchy,
    Server_Forward_Role,
    Restoredb_Log,
    Acl_Sensors,
    Acl_Entities
)

import api_log
from apimethods.decorators import accepted_values, accepted_types, require_db
from apimethods.utils import (
    get_uuid_string_from_bytes,
    get_bytes_from_uuid,
    get_ip_bin_from_str,
    is_valid_ipv4,
    get_ip_str_from_bytes,
    get_hex_string_from_uuid
)

from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler
ossim_setup = AVOssimSetupConfigHandler()

DEFAULT_BACKUP_DAYS = 30


@require_db
def db_get_hostname(system_id):
    try:
        system_id_bin = get_bytes_from_uuid(system_id)
        system = db.session.query(System).filter(System.id == system_id_bin).one()
    except Exception, msg:
        return False, "Error while querying for system with id '%s': %s" % (system_id, str(msg))
    return True, system.name


@require_db
# @accepted_values(['Server', 'Sensor', 'Database'])
def get_systems(system_type='', convert_to_dict=False, exclusive=False, directly_connected=True):
    """
    Return a list of id/admin ip address pairs for systems in the system table.
    :param convert_to_dict(boolean) parameter is True, return a dict instead of a list of pairs.
    :param exclusive(boolean) Means that system should have only the given system_type
                            For example an AIO has the profiles [Sensor, Server,Database,Framework]
                            if the function recieves system_type = Sensor it won't return this system.
    :param directly_connected(boolean) Whether it returns systems that are directly connected to this one or not.
    """
    if not isinstance(system_type, str) or system_type.lower() not in ['sensor', 'server', 'database', '']:
        return False, "Invalid system type <%s>. Allowed values are ['sensor','server','database']" % system_type

    try:
        if exclusive:
            system_list = db.session.query(System).filter(System.profile.ilike(system_type)).all()
        else:
            system_list = db.session.query(System).filter(System.profile.ilike('%' + system_type + '%')).all()
    except Exception, msg:
        return False, "Error while querying for '%s' systems: %s" % (system_type if system_type != '' else 'all', str(msg))

    if directly_connected:
        try:
            server_ip = get_ip_bin_from_str(db.session.query(Config).filter(Config.conf == 'server_address').one().value)
            server_id = get_bytes_from_uuid(db.session.query(Config).filter(Config.conf == 'server_id').one().value)
            connected_servers = [x.child_id for x in db.session.query(Server_Hierarchy).filter(Server_Hierarchy.parent_id == server_id).all()]
            connected_servers.append(server_id)
        except Exception, msg:
            return False, "Error while querying for server: '%s'" % str(msg)

        if not system_type or system_type.lower() == 'server':
            system_list = filter(lambda x: x.server_id in connected_servers or 'server' not in x.profile.lower(), system_list)

        if not system_type or system_type.lower() == 'sensor':
            try:
                context_ids = [x.id for x in db.session.query(Acl_Entities).filter(and_(Acl_Entities.server_id == server_id, Acl_Entities.entity_type == 'context')).all()]
                connected_sensors = [x.sensor_id for x in db.session.query(Acl_Sensors).filter(Acl_Sensors.entity_id.in_(context_ids)).all()]
            except Exception, msg:
                return False, "Error while querying for connected sensors: '%s'" % str(msg)

            system_list = filter(lambda x: x.sensor_id in connected_sensors or
                                           ('server' in x.profile.lower() and x.server_id in connected_servers and not system_type),
                                 system_list)

        if not system_type or system_type.lower() == 'database':
            try:
                database_ip = get_ip_bin_from_str(db.session.query(Config).filter(and_(Config.conf == 'snort_host', Config.value != '127.0.0.1', Config.value != 'localhost')).one().value)
            except NoResultFound, msg:
                pass
            except Exception, msg:
                return False, "Error while querying for connected databases: '%s'" % str(msg)
            else:
                system_list = filter(lambda x: (x.admin_ip == database_ip or x.vpn_ip == database_ip) or 'database' not in x.profile.lower(), system_list)

    if convert_to_dict:
        return True, dict([(get_uuid_string_from_bytes(x.id), get_ip_str_from_bytes(x.vpn_ip) if x.vpn_ip else get_ip_str_from_bytes(x.admin_ip)) for x in system_list])

    return True, [(get_uuid_string_from_bytes(x.id), get_ip_str_from_bytes(x.vpn_ip) if x.vpn_ip else get_ip_str_from_bytes(x.admin_ip)) for x in system_list]


@require_db
def get_all_ip_systems():
    """
        Return a dict whose keys are the system id and each value is another dict with keys
            \*admin_ip\* always present and two optional keys \*ha_ip\* and \*vpn_ip\*

        Returns:
            A tuple whose first members indicates whether the operation was successful (true or false)
            and the second member is a dictionary is success or a text error if false
    """
    result = {}
    try:
        system_list = db.session.query(System).all()
        for system in system_list:
            system_uuid = get_uuid_string_from_bytes(system.id)
            res = {'admin_ip': get_ip_str_from_bytes(system.admin_ip)}
            if system.vpn_ip:
                res['vpn_ip'] = get_ip_str_from_bytes(system.vpn_ip)
            if system.ha_ip:
                res['ha_ip'] = get_ip_str_from_bytes(system.ha_ip)
            result[system_uuid] = res
    except Exception as err:
        return False, "Error while querying systems. error: %s" % str(err)
    return True, result


@require_db
def get_system_info(system_id):
    """
    Return all information related to system
    :param System ID
    """
    system_info = {}
    try:
        system_id_bin = get_bytes_from_uuid(system_id)
        system = db.session.query(System).filter(System.id == system_id_bin).one()

        if system:
            system_info = {
                'id': get_uuid_string_from_bytes(system.id),
                'name': system.name,
                'admin_ip': get_ip_str_from_bytes(system.admin_ip) if system.admin_ip is not None else None,
                'vpn_ip': get_ip_str_from_bytes(system.vpn_ip) if system.vpn_ip is not None else None,
                'profile': system.profile,
                'sensor_id': get_uuid_string_from_bytes(system.sensor_id) if system.sensor_id is not None else None,
                'server_id': get_uuid_string_from_bytes(system.server_id) if system.server_id is not None else None,
                'database_id': get_uuid_string_from_bytes(
                    system.database_id) if system.database_id is not None else None,
                'host_id': get_uuid_string_from_bytes(system.host_id) if system.host_id is not None else None,
                'ha_ip': get_ip_str_from_bytes(system.ha_ip) if system.ha_ip is not None else None,
                'ha_name': system.ha_name,
                'ha_role': system.ha_role
            }
    except Exception as err:
        return False, "Error while querying system {0}.  Reason: {1}".format(system_id, err)

    return True, system_info


@require_db
def get_children_servers(parent_id):
    """Return a list of server_ids for children servers of the given parent_id

    Args:
        parent_id (str): server_id of the parent server

    Returns:
        list of server_ids of the children servers.
    """

    # query = 'select * from system, server_hierarchy where system.id = server_hierarchy.child_id and server_hierarchy.parent_id = "%s" and system.id != "%s"' % (parent_id, parent_id)

    # This query may cause troubles with binary characters. Use this instead:
    #query = "select * from system, server_hierarchy where system.id = server_hierarchy.child_id and server_hierarchy.parent_id = UNHEX(CONCAT(LEFT('%s', 8), MID('%s', 10, 4), \
    #MID('%s', 15, 4), MID('%s', 20, 4), RIGHT('%s', 12))) and system.id != UNHEX(CONCAT(LEFT('%s', 8), MID('%s', 10, 4), MID('%s', 15, 4), MID('%s', 20, 4), RIGHT('%s', 12)))" % \
    #(parent_id, parent_id, parent_id, parent_id, parent_id, parent_id, parent_id, parent_id, parent_id, parent_id)

    # Or better yet, compare the server_id from the hierarchy table with the one in the server table, and then with the one in the system table.
    query = 'select server.id from server, server_hierarchy where server.id = server_hierarchy.child_id and server_hierarchy.parent_id = unhex(replace("%s","-","")) and server.id != unhex(replace("%s","-",""))' % (parent_id, parent_id)

    try:
        server_list = db.session.connection(mapper=System).execute(query)
    except Exception, msg:
        return False, "Error while querying for children servers: %s" % str(msg)

    return True, [get_uuid_string_from_bytes(x.id) for x in server_list]


@require_db
#@accepted_values(['Server', 'Sensor', 'Database'])
def get_systems_full(system_type='', convert_to_dict=False):
    """
    Return a list of system ids and all their properties in a dictionary.
    If the 'convert_to_dict' parameter is True, return a dict instead of a list.
    """
    if system_type.lower() not in ['sensor', 'server', 'database', '']:
        return False, "Invalid system type <%s>. Allowed values are ['sensor','server','database']" % system_type
    try:
        system_list = db.session.query(System).filter(System.profile.ilike('%' + system_type + '%')).all()
    except Exception, msg:
        return False, "Error while querying for '%s' systems: %s" % (system_type if system_type != '' else 'all', str(msg))

    if convert_to_dict:
        return True, dict([(get_uuid_string_from_bytes(x.id), x.serialize) for x in system_list])

    return True, [(get_uuid_string_from_bytes(x.id), x.serialize) for x in system_list]


@require_db
@accepted_values([], ['str', 'bin'])
def get_system_id_from_system_ip(system_ip, output='str'):
    """
    Return the system id of an appliance using its ip.
    """
    system_ip_bin = get_ip_bin_from_str(system_ip)
    try:
        system_id = None
        if output == 'str':
            system_id = get_uuid_string_from_bytes(db.session.query(System).filter(or_(System.admin_ip == system_ip_bin, System.vpn_ip == system_ip_bin)).one().id)
        elif output == 'bin':
            system_id = db.session.query(System).filter(or_(System.admin_ip == system_ip_bin, System.vpn_ip == system_ip_bin)).one().id
    except NoResultFound, msg:
        return False, "No system found with ip address '%s'" % str(system_ip)
    except MultipleResultsFound, msg:
        return False, "More than one system found with ip address '%s'" % str(system_ip)
    except Exception, msg:
        return False, "Unknown error for ip address '%s': %s" % (str(system_ip), str(msg))

    return True, system_id


@require_db
@accepted_values([], ['str', 'bin'])
def get_system_id_from_local(output='str'):
    """
    Return the system id of the local machine
    """
    try:
        #framework_ip = db.session.query(Config).filter(Config.conf == 'frameworkd_address').one().value
        local_system_ip = ossim_setup.get_general_admin_ip(refresh=True)
    except NoResultFound, msg:
        return False, "There is no admin_ip on your system setup"
    except MultipleResultsFound, msg:
        return False, "More than one framework ip found for local"
    except Exception, msg:
        return False, "Error captured while querying for local system id: %s" % str(msg)

    return get_system_id_from_system_ip(local_system_ip, output=output)


@require_db
@accepted_types(UUID)
@accepted_values([], ['str', 'bin'])
def get_server_id_from_system_id(system_id, output='str'):
    """
    Return the id of a server using its system id
    """
    try:
        system_id_bin = get_bytes_from_uuid(system_id)
        system = db.session.query(System).filter(System.profile.ilike('%Server%')).filter(System.id == system_id_bin).one()
        server_id = system.server_id
    except NoResultFound, msg:
        return False, "No server id found with system id '%s'" % str(system_id)
    except MultipleResultsFound, msg:
        return False, "More than one server id found with system id '%s'" % str(system_id)
    except Exception, msg:
        return False, "Error captured while querying for system id '%s': %s" % (str(system_id), str(msg))

    if output == 'str':
        try:
            server_id_str = get_uuid_string_from_bytes(server_id)
        except Exception, msg:
            return False, "Cannot convert supposed server id '%s' to its string form: %s" % (str(server_id), str(msg))
        return True, server_id_str

    return True, server_id


@require_db
@accepted_types(UUID)
@accepted_values([], ['str', 'bin'])
def get_sensor_id_from_system_id(system_id, output='str'):
    """
    Return the id of a sensor using its system id
    """
    try:
        system_id_bin = get_bytes_from_uuid(system_id)
        system = db.session.query(System).filter(System.profile.ilike('%Sensor%')).filter(System.id == system_id_bin).one()
        sensor_id = system.sensor_id

    except NoResultFound, msg:
        return False, "No sensor ip address found with system id '%s'" % str(system_id)
    except MultipleResultsFound, msg:
        return False, "More than one sensor ip address found with system id '%s'" % str(system_id)
    except Exception, msg:
        return False, "Error captured while querying for system id '%s': %s" % (str(system_id), str(msg))

    if output == 'str':
        try:
            sensor_id_str = get_uuid_string_from_bytes(sensor_id)
        except Exception, msg:
            return False, "Cannot convert supposed sensor id '%s' to its string form: %s" % (str(sensor_id), str(msg))
        return True, sensor_id_str

    return True, sensor_id


@require_db(accept_local=True)
@accepted_values([], ['str', 'bin'])
def get_system_ip_from_system_id(system_id, output='str', local_loopback=True):
    """
    Return the ip of a system using its id.
    """
    try:
        if is_local(system_id):
            success, system_ip = get_system_ip_from_local(output='bin', local_loopback=local_loopback)
            if not success:
                return success, system_ip

        else:
            system_id_bin = get_bytes_from_uuid(system_id.lower())
            system = db.session.query(System).filter(System.id == system_id_bin).one()
            system_ip = system.vpn_ip if system.vpn_ip else system.admin_ip
    except NoResultFound, msg:
        return False, "No system found with id '%s'" % str(system_id)
    except MultipleResultsFound, msg:
        return False, "More than one system found with id '%s'" % str(system_id)
    except Exception, msg:
        return False, "Error captured while querying for system id '%s': %s" % (str(system_id), str(msg))

    if output == 'str':
        try:
            system_ip_str = get_ip_str_from_bytes(system_ip)
        except Exception, msg:
            return False, "Cannot convert supposed system ip '%s' to its string form: %s" % (str(system_ip), str(msg))
        system_ip = system_ip_str

    return True, system_ip


@accepted_values([], ['str', 'bin'])
def get_system_ip_from_local(output='str', local_loopback=True):
    """
    Return the ip of the local machine
    """
    if local_loopback:
        local_ip = '127.0.0.1'
    else:
        success, system_id = get_system_id_from_local()
        if not success:
            return success, system_id
        success, local_ip = get_system_ip_from_system_id(system_id)
        if not success:
            return success, local_ip

    if output == 'bin':
        try:
            local_ip_str = get_ip_bin_from_str(local_ip)
        except Exception, msg:
            return False, "Cannot convert supposed local ip '%s' to its binary form: %s" % (str(local_ip_str), str(msg))
        local_ip = local_ip_str

    return True, local_ip


@require_db
def get_config_backup_days():
    """Returns the backup days parameter
    :returns backup_days<int> Default value = 30
    """
    try:
        result = db.session.query(Config).filter(Config.conf == 'frameworkd_backup_storage_days_lifetime').one()
        backup_days = int(result.value)
    except:
        backup_days = DEFAULT_BACKUP_DAYS
    return backup_days


@require_db
def get_logger_storage_days_life_time():
    """Returns the logger_storage_days_lifetime parameter
    :returns logger_storage_days_lifetime<int> Default value = 0
    """
    try:
        result = db.session.query(Config).filter(Config.conf == 'logger_storage_days_lifetime').one()
        logger_storage_days = int(result.value)
    except Exception as e:
        api_log.error("[get_logger_storage_days_life_time] {0}".format(str(e)))
        logger_storage_days = 0
    return logger_storage_days


@require_db
def get_feed_auto_update():
    """ Returns True if feed auto updates enabled or False otherwise.
    """
    updates_enabled = False
    schedule = None
    try:
        raw_result = db.session.query(Config).filter(Config.conf == 'feed_auto_updates').one()
        updates_enabled = True if raw_result.value.lower() == 'yes' else False
        raw_schedule = db.session.query(Config).filter(Config.conf == 'feed_auto_update_time').one()
        schedule = int(raw_schedule.value)
    except Exception as err:
        api_log.error("[get_feed_auto_updates] {}".format(str(err)))

    return updates_enabled, schedule


@require_db
def get_server_address_from_config():
    """Returns the server_address parameter
    :returns the server ip address string or None when the ip is not a valid ip or
    a problem happen while getting it from the database
    """
    try:
        result = None
        data = db.session.query(Config).filter(Config.conf == 'server_address').one()
        if data.value is not None:
            if is_valid_ipv4(data.value):
                result = data.value
    except Exception as e:
        api_log.error("[get_server_address_from_config] {0}".format(str(e)))
        result = None
    return result


@require_db
def db_remove_system(system_id):
    try:
        #system_id_bin = get_bytes_from_uuid(system_id)
        #rc = db.session.query(System).filter(System.id == system_id_bin).delete()
        sp_call = sqltext("CALL system_delete('%s')" % system_id)
        db.session.begin()
        result = db.session.connection(mapper=System).execute(sp_call)
        data = result.fetchall()
        db.session.commit()
        if len(data) <= 0:
            return False, "Something wrong happened while removing the system from the database: %s" % str(data)
        if str(data[0]).find("System deleted") < 0:
            return False, "Something wrong happened while removing the system from the database: %s" % str(data[0])
    except Exception as err:
        db.session.rollback()
        return False, "Something wrong happened while removing the system from the database: %s" % str(err)

    return True, ""


@require_db
def db_add_system(system_id, name, admin_ip, vpn_ip=None, profile='', server_id=None, sensor_id=None):
    try:
        sp_call = sqltext("CALL system_update('%s','%s','%s','%s','%s','','','','%s','%s')" % (system_id, name, admin_ip, vpn_ip, profile, sensor_id, server_id))
        db.session.begin()
        result = db.session.connection(mapper=System).execute(sp_call)
        data = result.fetchall()
        db.session.commit()
        if len(data) <= 0:
            return False, "Something wrong happened while adding the system into the database: %s" % str(data)
        if str(data[0]).find("updated") < 0 and str(data[0]).find("created") < 0:
            return False, "Something wrong happened while adding the system into the database: %s" % str(data[0])
    except Exception, e:
        api_log.error(str(e))
        db.session.rollback()
        return False, 'Something wrong happened while adding the system into the database'
    return True, ''


@require_db
def db_get_systems():
    system = db.session.query(System)
    ip_addresses = []
    try:
        for machine in system:
            if machine.vpn_ip:
                ip_addresses.append(get_ip_str_from_bytes(machine.vpn_ip))
            else:
                ip_addresses.append(get_ip_str_from_bytes(machine.admin_ip))
    except Exception as e:
        api_log.error(str(e))
        db.session.rollback()
        return False, 'Something wrong happened while retrieving system IPs from the database'
    return bool(ip_addresses), ip_addresses


@require_db
def db_system_update_admin_ip(system_id, admin_ip):

    if not is_valid_ipv4(admin_ip):
        api_log.error('Invalid admin_ip %s' % str(admin_ip))
        return False, 'Invalid admin ip %s' % str(admin_ip)

    try:
        sp_call = sqltext("CALL system_update('%s','','%s','','','','','','','')" % (system_id, admin_ip))
        db.session.begin()
        result = db.session.connection(mapper=System).execute(sp_call)
        data = result.fetchall()
        db.session.commit()
        if len(data) <= 0:
            return False, "Something wrong happened while updating system info in the database: %s" % str(data)
        if str(data[0]).find("updated") < 0 and str(data[0]).find("created") < 0:
            return False, "Something wrong happened while updating system info in the database: %s" % str(data[0])
    except Exception, e:
        api_log.error(str(e))
        db.session.rollback()
        return False, 'Something wrong happened while updating system info in the database'

    return True, ''


@require_db
def db_system_update_hostname(system_id, hostname):

    try:
        sp_call = sqltext("CALL system_update('%s','%s','','','','','','','','')" % (system_id, hostname))
        db.session.begin()
        result = db.session.connection(mapper=System).execute(sp_call)
        data = result.fetchall()
        db.session.commit()
        if len(data) <= 0:
            return False, "Something wrong happened while updating system info in the database: %s" % str(data)
        if str(data[0]).find("updated") < 0 and str(data[0]).find("created") < 0:
            return False, "Something wrong happened while updating system info in the database: %s" % str(data[0])
    except Exception, e:
        api_log.error(str(e))
        db.session.rollback()
        return False, 'Something wrong happened while updating system info in the database'

    return True, ''


@require_db
def set_system_value(system_id, property_name, value):
    result = True
    try:
        message = ""
        db.session.begin()
        system_id_binary = get_bytes_from_uuid(system_id.lower())
        property = db.session.query(System).filter(System.id == system_id_binary).one()
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


def set_system_vpn_ip(system_id, value):
    return set_system_value(system_id, "vpn_ip", get_ip_bin_from_str(value))


def set_system_ha_ip(system_id, value):
    if value == 'NULL':
        return set_system_value(system_id, "ha_ip", value)
    return set_system_value(system_id, "ha_ip", get_ip_bin_from_str(value))


def set_system_ha_role(system_id, value):
    return set_system_value(system_id, "ha_role", value)


def set_system_ha_name(system_id, value):
    return set_system_value(system_id, "ha_name", value)


@require_db
def has_forward_role(server_id):
    """Check if server_id has forward role

    Checks the Server_Forward_Role table and returns True/False depending
    on wether the server_id appears as server_src_id on that table

    Args:
        server_id (str): server_id to check for forward role

    Returns:
        bool: True if server_id has forward role, False elsewhere

    """
    is_forwarder = False

    try:
        forwarder_id = db.session.query(Server_Forward_Role).filter(Server_Forward_Role.server_src_id == get_bytes_from_uuid(server_id)).first()
        if forwarder_id:
            is_forwarder = True
    except Exception, e:
        api_log.error("An error occurred when checking server_forward_role: %s" % str(e))

    return is_forwarder


@require_db
def get_database_size(databases=[]):
    """
    Return the system id of an appliance using its ip.
    """
    accepted = ['alienvault', 'alienvault_siem', 'alienvault_api', 'alienvault_asec', 'datawarehouse', 'ISO27001An', 'PCI']

    validation = set(databases) - set(accepted)
    if len(validation) > 0:
        return False, "Invalid database(s) %s. Accepted values are: %s" % (str(list(validation)), str(accepted))

    where = ''
    if len(databases) > 0:
        where = "WHERE table_schema IN ('%s')" % "','".join(databases)

    query = "SELECT table_schema as db, sum( data_length + index_length ) as size FROM information_schema.TABLES %s GROUP BY table_schema" % where

    try:
        av_db = db.session.connection(mapper=System).execute(query)
    except Exception, e:
        msg = "An error occurred getting the size of the system databases: %s" % str(e)
        return False, msg

    db_sizes = dict((_db.db, float(_db.size)) for _db in av_db)

    return True, db_sizes


@require_db
def fix_system_references():
    """
    Fix sensor_id and server_id columns from system table with HA environments
    """

    queries = ["update system set sensor_id=(select id from sensor where ip=admin_ip or ip=ha_ip) where ha_ip is not NULL and profile like 'sensor' and sensor_id is NULL",
               "update system s1,system s2 set s1.sensor_id=s2.sensor_id where s1.id!=s2.id and s1.ha_ip=s2.ha_ip and s2.profile like 'sensor' and s1.sensor_id is NULL",
               "update system set server_id=(select id from server where ip=ha_ip) where ha_ip is not NULL and server_id is NULL",
               "update system s1,system s2 set s1.server_id=s2.server_id where s1.id!=s2.id and s1.ha_ip=s2.ha_ip and s2.profile like '%%server%%' and s1.server_id is NULL"]

    try:
        db.session.begin()
        for query in queries:
            db.session.connection(mapper=System).execute(query)
        db.session.commit()
    except Exception, msg:
        db.session.rollback()
        return False, str(msg)

    return True, ''


@require_db
def check_any_innodb_tables():
    """
        Check if any Alienvault system database is ussing an innodb engine
        Return  a tuple (success, result), where success signals that there
        ins't any error and result, True or False. Return the list
        of databases with innodb tables
    """
    tables = ['datawarehouse.incidents_ssi',
              'datawarehouse.ip2country',
              'datawarehouse.report_data',
              'datawarehouse.ssi',
              'alienvault.sem_stats_events',
              'alienvault.event',
              'alienvault.extra_data',
              'alienvault.idm_data',
              'alienvault_siem.acid_event',
              'alienvault_siem.extra_data',
              'alienvault_siem.idm_data',
              'alienvault_siem.reputation_data',
              'alienvault_siem.po_acid_event',
              ]
    result = []
    for table in tables:
        try:
            (schema, t) = table.split(".")
            query = """SELECT  table_schema,table_name FROM INFORMATION_SCHEMA.TABLES""" \
                    """ WHERE engine = 'innodb' AND """ \
                    """ table_schema = '%s' AND table_name = '%s'""" % (schema, t)
            databases = db.session.connection(mapper=System).execute(query)
            result = result + [(row[0], row[1]) for row in databases.fetchall()]
        except NoResultFound:
            pass
        except Exception, msg:
            return False, str(msg)
    return True, result


@require_db
def get_wizard_data():
    """
        Returns all the wizard data
    """
    success = True
    start_welcome_wizard = 0
    welcome_wizard_date = 0
    try:
        try:
            result = db.session.query(Config).filter(Config.conf == 'start_welcome_wizard').one()
            start_welcome_wizard = int(result.value)
        except NoResultFound as e:
            start_welcome_wizard = 0

        try:
            result = db.session.query(Config).filter(Config.conf == 'welcome_wizard_date').one()
            welcome_wizard_date = int(result.value)
        except NoResultFound as e:
            welcome_wizard_date = 0
    except Exception as e:
        welcome_wizard_date = 0
        start_welcome_wizard = 0
        success = False
    return success, start_welcome_wizard, welcome_wizard_date


@require_db
def get_trial_expiration_date():
    """
        Checks if a Trial version has expired, based on the license stored in the database
    """
    # mysql> select * from config where conf='license'\G;

    #     *************************** 1. row ***************************
    #      conf: license
    #     value: [sign]
    #     sign=MC0CFQDX91hNahI2ZpRuxvJ7R0ht6A5+3gIUA4XYcYqdYZt/j0kOzc9yPWIPSlw=
    #     [appliance]
    #     key=YOUR_KEY
    #     system_id=564d3bf3-e1ae-e32b-4dc0-83e45a48d02d
    #     expire=9999-12-31

    #     1 row in set (0.00 sec)
    success = True
    try:
        result = db.session.query(Config).filter(Config.conf == 'license').one()
        if result.value:
            params = [param for param in str(result.value).split() if "expire=" in param]
            expires = params[0] if params else "expire="
        else:
            expires = "expire="
        message = ""
    except NoResultFound:
        success = False
        expires = ""
        message = "There is no license parameter stored in the database"
    except Exception as e:
        success = False
        expires = ""
        message = "There has been an error checking the license parameter in the database: %s" % str(e)

    return success, expires, message


@require_db
def check_backup_process_running():
    """
    Checks if a backup purge/restore process is running
    """
    running = False
    success = False
    try:
        result = db.session.query(Restoredb_Log).filter(Restoredb_Log.status == 1).one()
        running = True
        success = True
        message = ""
    except NoResultFound:
        running = False
        success = True
        message = ""
    except Exception as e:
        success = False
        running = False
        message = "There has been an error checking if there is a backup process running: %s" % str(e)

    return success, running, message


@require_db
def db_get_config(key):
    """
        Returns a config value
    """

    query = "SELECT value, AES_DECRYPT(value, (SELECT value FROM config WHERE conf='encryption_key')) AS value_decrypt FROM config where conf = :conf"

    try:
        data = db.session.connection(mapper=Config).execute(sqltext(query), conf=key).fetchall()
        success = True
        result = ""
        if len(data) > 0:
            result = data[0][1] if re.search('(_key$|_pass$)', key) and data[0][1] else data[0][0]

    except NoResultFound:
        success = True
        result = ""
    except Exception as e:
        success = False
        result = "There has been an error retrieving the config value: %s" % str(e)
        api_log.error("[db_get_config] %s" % str(result))

    return success, result


@require_db
def db_set_config(key, value):
    """
        Set a Config Value
    """
    success = True
    result = ""

    try:
        if re.search('(_key$|_pass$)', key) and len(value) > 0:
            status, uuid = db_get_config('encryption_key')
            if not status:
                return False, "There has been an error setting the config value"

            query = "REPLACE INTO config (conf, value) VALUES (:conf, AES_ENCRYPT(:val, :crypt))"
            db.session.begin()
            db.session.connection(mapper=Config).execute(sqltext(query), conf=key, val=value, crypt=uuid)
        else:
            query = "REPLACE INTO config (conf, value) VALUES (:conf, :val)"
            db.session.begin()
            db.session.connection(mapper=Config).execute(sqltext(query), conf=key, val=value)
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        success = False
        result = "There has been an error setting the config value: %s" % str(e)
        api_log.error("[db_set_config] %s" % str(result))

    return success, result


def is_local(system_id):
    if system_id.lower() == 'local':
        return True

    success, local_system_id = get_system_id_from_local()
    return success and get_hex_string_from_uuid(local_system_id) == get_hex_string_from_uuid(system_id)
