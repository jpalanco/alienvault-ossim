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
from sqlalchemy import or_

import db
from db.models.alienvault import Server, Sensor, Config, System, Server_Hierarchy, Server_Forward_Role

import api_log
from apimethods.decorators import accepted_values, accepted_types, require_db
from apimethods.utils import get_uuid_string_from_bytes, get_bytes_from_uuid, get_ip_bin_from_str, is_valid_ipv4, get_ip_str_from_bytes
from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler
ossim_setup = AVOssimSetupConfigHandler()

DEFAULT_BACKUP_DAYS = 30


@require_db
#@accepted_values(['Server', 'Sensor', 'Database'])
def get_systems(system_type='', convert_to_dict=False):
    """
    Return a list of id/admin ip address pairs for systems in the system table.
    If the 'convert_to_dict' parameter is True, return a dict instead of a list of pairs.
    """
    if system_type.lower() not in ['sensor', 'server', 'database', '']:
        return False, "Invalid system type <%s>. Allowed values are ['sensor','server','database']" % system_type
    try:
        system_list = db.session.query(System).filter(System.profile.ilike('%' + system_type + '%')).all()
    except Exception, msg:
        db.session.rollback()
        return (False, "Error while querying for '%s' systems: %s" % (system_type if system_type != '' else 'all', str(msg)))

    if convert_to_dict:
        return (True, dict([(get_uuid_string_from_bytes(x.id), get_ip_str_from_bytes(x.vpn_ip) if x.vpn_ip else get_ip_str_from_bytes(x.admin_ip)) for x in system_list]))

    return (True, [(get_uuid_string_from_bytes(x.id), get_ip_str_from_bytes(x.vpn_ip) if x.vpn_ip else get_ip_str_from_bytes(x.admin_ip)) for x in system_list])

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
        db.session.rollback()
        return (False, "Error while querying systems. error: %s" % str(err))
    return (True, result)


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
        db.session.rollback()
        return (False, "Error while querying for children servers: %s" % str(msg))

    return (True, [get_uuid_string_from_bytes(x.id) for x in server_list])


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
        db.session.rollback()
        return (False, "Error while querying for '%s' systems: %s" % (system_type if system_type != '' else 'all', str(msg)))

    if convert_to_dict:
        return (True, dict([(get_uuid_string_from_bytes(x.id), x.serialize) for x in system_list]))

    return (True, [(get_uuid_string_from_bytes(x.id), x.serialize) for x in system_list])


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
        return (False, "No system found with ip address '%s'" % str(system_ip))
    except MultipleResultsFound, msg:
        return (False, "More than one system found with ip address '%s'" % str(system_ip))
    except Exception, msg:
        db.session.rollback()
        return (False, "Unknown error for ip address '%s': %s" % (str(system_ip), str(msg)))

    return (True, system_id)


@require_db
@accepted_values([], ['str', 'bin'])
def get_system_id_from_local(output='str'):
    """
    Return the system id of the local machine
    """
    try:
        #framework_ip = db.session.query(Config).filter(Config.conf == 'frameworkd_address').one().value
        local_system_ip = ossim_setup.get_general_admin_ip()
    except NoResultFound, msg:
        return (False, "There is no admin_ip on your system setup")
    except MultipleResultsFound, msg:
        return (False, "More than one framework ip found for local")
    except Exception, msg:
        db.session.rollback()
        return (False, "Error captured while querying for local system id: %s" % str(msg))

    return (get_system_id_from_system_ip(local_system_ip, output=output))


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
        return (False, "No server id found with system id '%s'" % str(system_id))
    except MultipleResultsFound, msg:
        return (False, "More than one server id found with system id '%s'" % str(system_id))
    except Exception, msg:
        db.session.rollback()
        return (False, "Error captured while querying for system id '%s': %s" % (str(system_id), str(msg)))

    if output == 'str':
        try:
            server_id_str = get_uuid_string_from_bytes(server_id)
        except Exception, msg:
            return (False, "Cannot convert supposed server id '%s' to its string form: %s" % (str(server_id), str(msg)))
        return (True, server_id_str)

    return (True, server_id)


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
        return (False, "No sensor ip address found with system id '%s'" % str(system_id))
    except MultipleResultsFound, msg:
        return (False, "More than one sensor ip address found with system id '%s'" % str(system_id))
    except Exception, msg:
        db.session.rollback()
        return (False, "Error captured while querying for system id '%s': %s" % (str(system_id), str(msg)))

    if output == 'str':
        try:
            sensor_id_str = get_uuid_string_from_bytes(sensor_id)
        except Exception, msg:
            return (False, "Cannot convert supposed sensor id '%s' to its string form: %s" % (str(sensor_id), str(msg)))
        return (True, sensor_id_str)

    return (True, sensor_id)


@require_db(accept_local=True)
@accepted_values([], ['str', 'bin'])
def get_system_ip_from_system_id(system_id, output='str', local_loopback=True):
    """
    Return the ip of a system using its id.
    """
    try:
        system_id_lower = system_id.lower()

        if system_id_lower == 'local':
            (success, system_ip) = get_system_ip_from_local(output='bin', local_loopback=local_loopback)
            if not success:
                return (success, system_ip)

        else:
            system_id_bin = get_bytes_from_uuid(system_id_lower)
            system = db.session.query(System).filter(System.id == system_id_bin).one()
            system_ip = system.vpn_ip if system.vpn_ip else system.admin_ip
    except NoResultFound, msg:
        return (False, "No system found with id '%s'" % str(system_id))
    except MultipleResultsFound, msg:
        return (False, "More than one system found with id '%s'" % str(system_id))
    except Exception, msg:
        db.session.rollback()
        return (False, "Error captured while querying for system id '%s': %s" % (str(system_id), str(msg)))

    if output == 'str':
        try:
            system_ip_str = get_ip_str_from_bytes(system_ip)
        except Exception, msg:
            return (False, "Cannot convert supposed system ip '%s' to its string form: %s" % (str(system_ip), str(msg)))
        system_ip = system_ip_str

    return (True, system_ip)


@accepted_values([], ['str', 'bin'])
def get_system_ip_from_local(output='str', local_loopback=True):
    """
    Return the ip of the local machine
    """
    if local_loopback:
        local_ip = '127.0.0.1'
    else:
        (success, system_id) = get_system_id_from_local()
        if not success:
            return (success, system_id)
        (success, local_ip) = get_system_ip_from_system_id(system_id)
        if not success:
            return(success, local_ip)

    if output == 'bin':
        try:
            local_ip_str = get_ip_bin_from_str(local_ip)
        except Exception, msg:
            return (False, "Cannot convert supposed local ip '%s' to its binary form: %s" % (str(local_ip_str), str(msg)))
        local_ip = local_ip_str

    return (True, local_ip)


@require_db
def get_config_backup_days():
    """Returns the backup days parameter
    :returns backup_days<int> Default value = 30
    """
    try:
        result = db.session.query(Config).filter(Config.conf == 'frameworkd_backup_storage_days_lifetime').one()
        backup_days = int(result.value)
    except:
        db.session.rollback()
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
        db.session.rollback()
        logger_storage_days = 0
    return logger_storage_days


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
        db.session.rollback()
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
        return (False, 'Something wrong happened while adding the system into the database')

    return (True, '')

@require_db
def db_system_update_admin_ip(system_id, admin_ip):

    if not is_valid_ipv4(admin_ip):
        api_log.error('Invalid admin_ip %s' % str(admin_ip))
        return (False, 'Invalid admin ip %s' % str(admin_ip))

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
        return (False, 'Something wrong happened while updating system info in the database')

    return (True, '')

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
        return (False, 'Something wrong happened while updating system info in the database')

    return (True, '')

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
    return set_system_value(system_id, "ha_ip", get_ip_bin_from_str(value))


def set_system_ha_role(system_id, value):
    return set_system_value(system_id, "ha_role", value)


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
    accepted = ['alienvault', 'alienvault_siem', 'alienvault_api', 'alienvault_asec', 'datawarehouse', 'ocsweb', 'ISO27001An', 'PCI']
    
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
