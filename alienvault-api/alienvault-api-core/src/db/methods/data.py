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
from uuid import UUID, uuid4
from datetime import datetime, timedelta
from traceback import format_exc
import json

from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from sqlalchemy import (desc,
                        asc,
                        and_,
                        or_,
                        func,
                        case,
                        literal_column)

from apimethods.utils import (get_bytes_from_uuid,
                              get_ip_str_from_bytes,
                              get_uuid_string_from_bytes)

from apimethods.decorators import (accepted_values,
                                   accepted_types,
                                   require_db)

import db
from db.models.alienvault import (Host,
                                  Host_Ip,
                                  Host_Group_Reference,
                                  Server)

from db.models.alienvault_siem import Acid_Event

from db.models.alienvault_api import (Current_Status,
                                      Status_Message,
                                      Monitor_Data,
                                      UserPermissions)
from db.utils import paginate
import api_log


@require_db
def get_device_list():
    """Returns the device list retrieved from the table host
    :returns sensor_list list<Alienvault_Host> or [] on error/or no sensors
    """
    devices = {}
    query = "select hex(h.id) as host_id,inet6_ntoa(hi.ip) as host_ip from host h,host_ip hi,host_types ht" \
            " where h.id=hi.host_id and " \
            "h.id=ht.host_id and ht.type=4 union distinct select hex(h.id),inet6_ntoa(hi.ip) " \
            "from host h,host_ip hi,alienvault_siem.device d where h.id=hi.host_id and hi.ip=d.device_ip;"
    try:
        data = db.session.connection(mapper=Host).execute(query)
        for row in data:
            devices[row[0]] = row[1]
    except Exception:
        devices = {}
    return devices


@require_db
def get_asset_list():
    """Returns the asset list retrieved from the table host
    :returns sensor_list list<Alienvault_Host> or [] on error/or no sensors
    """
    assets = []
    try:
        assets = db.session.query(Host).all()
    except Exception:
        assets = []
    return assets


@require_db
@accepted_types(UUID, UUID)
def get_asset_events(asset_id, asset_ctx, order_by=True):
    """Returns a list of events of an asset, order by timestamp
    :param asset_id (uuid binary) Asset asset_id
    :param asset_ctx (uuid binary) Asset Context
    :param order_by (Boolean) on True order = DESC  otherwise order = ASC
    """

    events = []
    try:
        if order_by:
            events = db.session.query(Acid_Event). \
                filter(Acid_Event.ctx == asset_ctx). \
                filter(Acid_Event.src_host == asset_id). \
                order_by(Acid_Event.timestamp.desc()).all()
        else:
            events = db.session.query(Acid_Event). \
                filter(Acid_Event.ctx == asset_ctx). \
                filter(Acid_Event.src_host == asset_id). \
                order_by(Acid_Event.timestamp.asc()).all()

    except Exception:
        events = []
    return events


@require_db
def get_timestamp_last_event_for_each_device():
    """Get the last event for each device"""
    host_last_event = {}
    query = """
    SELECT hex(h.host_id), max(a.timestamp)
    FROM alienvault_siem.device d, alienvault_siem.ac_acid_event a, alienvault.host_ip h
    WHERE a.device_id = d.id AND d.device_ip = h.ip GROUP BY h.host_id;
    """
    try:
        data = db.session.connection(mapper=Host).execute(query)
        for row in data:
            host_last_event[row[0]] = row[1]
    except Exception:
        host_last_event = {}

    return host_last_event


@require_db
@accepted_types(UUID)
def get_asset_ip_from_id(asset_id):
    """Returns a list of IPs for a given ASSET"""
    ips = []
    try:
        data = db.session.query(Host_Ip).filter(Host_Ip.host_id == get_bytes_from_uuid(asset_id)).all()
        ips = [get_ip_str_from_bytes(i.ip) for i in data]

    except Exception:
        ips = []
    return ips


@require_db
@accepted_values([], [], ['str', 'bin'])
def get_asset_id_from_ip(asset_ip, sensor_ip, output='str'):
    """
    Return the uuid of an asset using its ip.

    Args:
        asset_ip (str): Ip of the asset
        sensor_ip (str): Ip of the sensor for the asset

    Returns:
        success (bool): True if successful, False elsewhere
        msg (str): Result message
    """

    if not asset_ip or not sensor_ip:
        return False, "Invalid parameters"

    try:
        query = """
        SELECT hex(host.id) FROM host, host_ip,host_sensor_reference,sensor
        WHERE host_sensor_reference.host_id=host.id AND host_ip.host_id=host.id
        AND host_ip.ip=inet6_aton('%s')
        AND host_sensor_reference.sensor_id=sensor.id
        AND sensor.ip=inet6_aton('%s');
        """ % (asset_ip, sensor_ip)
        data = db.session.connection(mapper=Host).execute(query)
        asset_id = None
        for row in data:
            asset_id = row[0]
        if output == 'bin':
            asset_id = get_bytes_from_uuid(asset_id)
    except Exception, msg:
        return False, "Unknown error obtaining host id for ip address '%s': %s" % (str(asset_ip), str(msg))

    if asset_id is None:
        return False, "No asset with asset ip '%s'" % asset_ip

    return True, asset_id


@require_db
@accepted_types(UUID)
def host_clean_orphan_ref(host_id):
    """
    Clean hosts marked as orphaned
    """
    try:
        records = db.session.query(Current_Status).filter(and_(Current_Status.component_type == 'host',
                                                               Current_Status.component_id == get_bytes_from_uuid(
                                                                   host_id))).all()
        db.session.begin()
        for record in records:
            db.session.delete(record)
        db.session.commit()
    except NoResultFound:
        db.session.rollback()
    try:
        records = db.session.query(Monitor_Data).filter(Monitor_Data.component_id == get_bytes_from_uuid(host_id)).all()
        db.session.begin()
        for record in records:
            db.session.delete(record)
        db.session.commit()
    except NoResultFound:
        db.session.rollback()
    return True


@require_db
def get_snort_suricata_events_in_the_last_24_hours():
    try:
        d = datetime.now() - timedelta(hours=24)
        data = db.session.query(Acid_Event).filter(Acid_Event.plugin_id >= 1001). \
            filter(Acid_Event.plugin_id <= 1505). \
            filter(Acid_Event.timestamp >= d).all()
    except:
        data = []
    return data


@require_db
@accepted_types(UUID, )
def get_asset_ids_in_group(group_id, user):
    """
    Return the list of asset ids in the group that are visible for the user.

    Args:
        group_id (UUID): Id of the asset group
        user (proxy object): User proxy object

    Returns:
        success (bool): True if successful, False elsewhere
        assets ([UUID]): List of member asset ids.
    """

    if not group_id or not user:
        return False, "Invalid parameters"

    assets = []
    try:
        data = db.session.query(Host, Host_Ip, Host_Group_Reference).filter(and_(Host.id == Host_Ip.host_id,
                                                                                 Host.id == Host_Group_Reference.host_id,
                                                                                 Host_Group_Reference.host_group_id
                                                                                 == get_bytes_from_uuid(
                                                                                     group_id))).all()
        assets = [get_uuid_string_from_bytes(i[0].id) for i in data if
                  user.is_allowed(get_uuid_string_from_bytes(i[0].id).replace('-', ''))]
        success = True

    except Exception:
        success = False

    return success, assets


@require_db
def get_current_status_messages(component_id=None,
                                message_level=None,
                                order_by=None,
                                page=None,
                                page_row=None,
                                order_desc=None,
                                component_type=None,
                                message_id=None,
                                message_type=None,
                                search=None,
                                only_unread=None,
                                login_user=None,
                                is_admin=False):
    """Returns the list of current_status messages matching the given criteria.
    Args:
        component_id(UUID string): Component ID related with the message
        message_level(list<str>): Message level
        order_by(str): Current status field by which you want to sort the results.
        page(int): Page number
        page_row(int): Number of items per page
        order_desc(Boolean or None): Specify whether you want to sort the results in descendig order or not.
        component_type(str): Component Type related with the message
        message_id(UUID string): The message ID you are looking for
        message_type(list<str>): Kind of message you want to retrieve.
        search(str): It's a free text to search for message title
        only_unread(Boolean or None): If true, retrieve only unread messages
        login_user (admin): logged user on the system
        is_admin(bool): Whether the current user is admin or not.
    Returns:
       [Current_Status] A list of Current_Status Items

    """
    if login_user is None or login_user == "":
        return True, {'messages': {}, 'total': 0}

    query = db.session.query(Current_Status)
    # message_type and message_level are fields belonging to the related table status_message
    # We need to deal with this situation when we set the build the order_by clause
    if order_by is not None:
        if order_by not in ['message_level', 'message_type', 'message_title']:
            if order_desc:
                query = query.order_by(desc(order_by))
            else:
                query = query.order_by(asc(order_by))
        else:
            order_by_field = Status_Message.type
            if order_by == 'message_level':
                order_by_field = Status_Message.level
            if order_by == 'message_title':
                order_by_field = Status_Message.title
            if order_desc:
                query = query.join(Status_Message).order_by(desc(order_by_field))
            else:
                query = query.join(Status_Message).order_by(asc(order_by_field))

    if login_user != "admin" and not is_admin:  # neither user admin nor is_admin
        query = query.join(UserPermissions, UserPermissions.component_id == Current_Status.component_id)
        query = query.filter(and_(UserPermissions.login == login_user))
    query = query.order_by(asc(Current_Status.viewed))

    if component_id is not None:
        query = query.filter(and_(Current_Status.component_id == get_bytes_from_uuid(component_id)))
    if message_id is not None:
        query = query.filter(and_(Current_Status.message_id == get_bytes_from_uuid(message_id)))
    if message_level is not None:
        new_filter = [
            Current_Status.message.has(Status_Message.level.like(Status_Message.get_level_integer_from_string(x))) for x
            in message_level]
        query = query.filter(or_(*new_filter))
    if message_type is not None:
        new_filter = [Current_Status.message.has(Status_Message.type.like(x)) for x in message_type]
        query = query.filter(or_(*new_filter))
    if component_type is not None:
        query = query.filter(and_(Current_Status.component_type == component_type))
    if search is not None:
        query = query.filter(or_(Current_Status.message.has(Status_Message.title.like("%" + search + "%")),
                                 Current_Status.message.has(Status_Message.description.like("%" + search + "%"))))
    if only_unread:
        query = query.filter(and_(Current_Status.viewed == 0))

    query = query.filter(or_(Current_Status.suppressed == None, Current_Status.suppressed == 0))
    # Always order by creationtime
    if order_by != "creation_time":
        query = query.order_by(desc(Current_Status.creation_time))
    msgs = {}
    total = 0

    try:
        if page is None:  # return all
            data = query.all()
            msgs = [x.serialize for x in data]
            total = len(data)
        else:
            current_page = paginate(query, page, page_row, error_out=False)
            msgs = [x.serialize for x in current_page['items']]
            total = current_page['total']
    except Exception as err:
        api_log.error("status: get_status_messages: %s" % format_exc())
        return False, "Internal error %s" % str(err)

    return True, {'messages': msgs, 'total': total}


@require_db
def get_current_status_messages_stats(search=None, only_unread=False, login_user=None, is_admin=False):
    """
    Returns the list of current status messages stats
        all messages count
        unread messages count
        info level messages count
        warning level messages count
        error level messages count
        update type messages count
        deployment type messages count
        information type messages count
        announcement type messages count
        is_admin(bool): Whether the current user is admin or not.
    :return: (bool, {stats})
    """
    m_levels = ['info', 'warning', 'error']
    m_types = ['update', 'deployment', 'information', 'alienvault', 'ticket', 'alarm', 'security']

    stats = {}
    if login_user is None or login_user == '':
        return {'all': 0, 'unread': 0, 'type': 0}
    try:
        query = db.session.query(Current_Status)  # db.session.query(func.count(Current_Status.id)).scalar()
        query = query.filter(or_(Current_Status.suppressed == None, Current_Status.suppressed == 0))
        if search is not None:
            query = query.filter(and_(or_(Current_Status.message.has(Status_Message.title.like("%" + search + "%")),
                                          Current_Status.message.has(
                                              Status_Message.description.like("%" + search + "%")))))
        if login_user != "admin" and not is_admin:  # neither user admin nor is_admin
            query = query.join(UserPermissions, UserPermissions.component_id == Current_Status.component_id)
            query = query.filter(and_(UserPermissions.login == login_user))
        stats['all'] = query.count()

        query = db.session.query(Current_Status).filter(Current_Status.viewed == 0)
        query = query.filter(or_(Current_Status.suppressed == None, Current_Status.suppressed == 0))
        if search is not None:
            query = query.filter(and_(or_(Current_Status.message.has(Status_Message.title.like("%" + search + "%")),
                                          Current_Status.message.has(
                                              Status_Message.description.like("%" + search + "%")))))
        if login_user != "admin" and not is_admin:  # neither user admin nor is_admin
            query = query.join(UserPermissions, UserPermissions.component_id == Current_Status.component_id)
            query = query.filter(and_(UserPermissions.login == login_user))
        stats['unread'] = query.count()

        stats['level'] = {}
        for m_level in m_levels:
            query = db.session.query(Current_Status).join(Status_Message).filter(
                Status_Message.level == Status_Message.get_level_integer_from_string(m_level))
            query = query.filter(or_(Current_Status.suppressed == None, Current_Status.suppressed == 0))
            if only_unread:
                query = query.filter(and_(Current_Status.viewed == 0))
            if search is not None:
                query = query.filter(and_(or_(Current_Status.message.has(Status_Message.title.like("%" + search + "%")),
                                          Current_Status.message.has(
                                              Status_Message.description.like("%" + search + "%")))))
            if login_user != "admin" and not is_admin:  # neither user admin nor is_admin
                query = query.join(UserPermissions, UserPermissions.component_id == Current_Status.component_id)
                query = query.filter(and_(UserPermissions.login == login_user))
            stats['level'][m_level] = query.count()

        stats['type'] = {}
        for m_type in m_types:
            query = db.session.query(Current_Status).join(Status_Message).filter(Status_Message.type == m_type)
            query = query.filter(or_(Current_Status.suppressed == None, Current_Status.suppressed == 0))
            if only_unread:
                query = query.filter(and_(Current_Status.viewed == 0))
            if search is not None:
                query = query.filter(and_(or_(Current_Status.message.has(Status_Message.title.like("%" + search + "%")),
                                          Current_Status.message.has(
                                              Status_Message.description.like("%" + search + "%")))))
            if login_user != "admin" and not is_admin:  # neither user admin nor is_admin
                query = query.join(UserPermissions, UserPermissions.component_id == Current_Status.component_id)
                query = query.filter(and_(UserPermissions.login == login_user))
            stats['type'][m_type] = query.count()

    except Exception as err:
        api_log.error('status: get_status_messages_stats: %s' % format_exc())
        return False, 'Internal error %s' % str(err)

    return True, {'stats': stats}


@require_db
def purge_current_status_messages():
    """
    Remove messages from Current_Status with component_type != external.
    Remove messages from Status_Message not present

    Returns:
        success (bool): True if successful, False elsewhere
        result  (str): Error message (if any)
    """
    success = True
    result = ""
    try:
        # Delete messages in current_status with component_type != external
        #del_cs = "DELETE FROM current_status WHERE component_type!='external';"
        #db.session.connection(mapper=Current_Status).execute(del_cs)
        # Delete messages from status_message that are not present in current_status
        db.session.begin()
        del_sm = "DELETE sm FROM status_message sm LEFT JOIN current_status cs ON sm.id=cs.message_id WHERE cs.message_id is null;"
        db.session.connection(mapper=Current_Status).execute(del_sm)
        db.session.commit()
    except Exception, e:
        db.session.rollback()
        success = False
        result = "[purge_current_status_messages] Error: %s" % str(e)

    return success, result


@require_db
@accepted_values([])
def load_messages_to_db(msg_list):
    """
    Load list of messages into DB Status_Message table

    Args:
        msg_list ([Message]): Message list
        purge (boolean): Indicates when the fucntion should purge old messages.

    Returns:
        success (bool): True if successful, False elsewhere
        result  (str): Error message (if any)
    """
    success = True
    result = ""
    try:
        if msg_list:
            db.session.begin()

            for msg in msg_list:
                db_msg = Status_Message(id=get_bytes_from_uuid(msg.id),
                                        level=Status_Message.get_level_integer_from_string(msg.level),
                                        type=msg.type,
                                        message_role=msg.message_role,
                                        action_role=msg.action_role,
                                        title=msg.title,
                                        description=msg.description,
                                        actions=msg.actions,
                                        alternative_actions=msg.alternative_actions,
                                        source=msg.source)
                db.session.merge(db_msg)  # insert or update
            # remove those messages that have been dissapeared
            messsages_ids_str = ','.join(["'%s'" % msg.id.replace('-', '').upper() for msg in msg_list])
            cmd = "delete from status_message  where  hex(id) not in (%s) and source='monitor'" % (messsages_ids_str)
            db.session.connection(mapper=Status_Message).execute(cmd)
            #cmd = "delete current_status from current_status left join status_message on current_status.message_id=status_message.id where status_message.id is null;"
            #db.session.connection(mapper=Current_Status).execute(cmd)
            #success, result = purge_current_status_messages()
            db.session.commit()
    except Exception, e:
        success = False
        result = "[load_messages_to_db] Error: %s" % str(e)
        db.session.rollback()

    return success, result


@require_db
def set_current_status_property(current_status_id, viewed, suppressed):
    """Sets the values of the attributtes viewed and suppresed"""
    try:
        status_message = db.session.query(Current_Status).filter(
            Current_Status.id == get_bytes_from_uuid(current_status_id)).one()
    except NoResultFound, msg:
        api_log.error("No Result: %s" % str(msg))
        return (False, "No result: Bad Current_Status ID")
    except MultipleResultsFound, msg:
        api_log.error("Multiple results: %s" % msg)
        return (False, "Multiple Results:Bad Current_Status ID")
    except Exception, msg:
        db.session.rollback()
        return (False, "Cannot retrieve status message")
    if viewed is not None:
        status_message.viewed = viewed
    delete_status_message = False

    if suppressed is not None:
        status_message.suppressed = suppressed
        status_message.suppressed_time = datetime.utcnow()
        if status_message.component_type == "external":
            delete_status_message = True
    try:
        db.session.begin()
        if delete_status_message:
            db.session.delete(status_message)
            cmd = "delete from status_message where id=0x%s" % get_uuid_string_from_bytes(
                status_message.message_id).replace('-', '').upper()
            db.session.connection(mapper=Status_Message).execute(cmd)
        else:
            db.session.merge(status_message)
        db.session.commit()
    except Exception as msg:
        db.session.rollback()
        api_log.error("message: put_status_message: Cannot commit status_message: %s" % str(msg))
        return (False, "Cannot update status message")
    return (True, None)


@require_db
def set_current_status_message_as_viewed(current_status_id, viewed=False):
    """Sets the value of viewed for a given current status message
    Args:
        current_status_id (canonical uuid string): The ID of the Current_Status message you want to modify
        viewed (boolean): The value you want to set.
    """
    return set_current_status_property(current_status_id=current_status_id, viewed=viewed, suppressed=None)


@require_db
def set_current_status_message_as_suppressed(current_status_id, suppressed=False):
    """Marks a given current status message as suppresed
    Args:
        current_status_id (canonical uuid string): The ID of the Current_Status message you want to modify
        suppresed (boolean): The value you want to set.
    """
    return set_current_status_property(current_status_id=current_status_id, viewed=None, suppressed=suppressed)


@require_db
def get_status_message_from_id(message_id, is_admin=False, serialize=True):
    """Retrieves the message id with the given message id"""
    # TODO: Pensar como filtrar por usuairo y por accion
    try:
        status_message = db.session.query(Status_Message).filter(Status_Message.id == message_id).one()
    except NoResultFound:
        return (False, "No message found with id '%s'" % str(message_id))
    except MultipleResultsFound:
        return (False, "More than one message found with id '%s'" % str(message_id))
    except Exception, msg:
        db.session.rollback()
        return (False, "Unknown error while querying for status message '%s': %s" % (str(message_id), str(msg)))
    if serialize:
        return (True, status_message.serialize)
    return (True, status_message)


@require_db
def get_current_status_message_from_id(current_status_id):
    """Retrieves the Current_Status message id with the given id"""
    try:
        data = db.session.query(Current_Status).filter(
            Current_Status.message_id == get_bytes_from_uuid(current_status_id)).one()
    except NoResultFound:
        return (False, "No Current_Status found with id '%s'" % str(current_status_id))
    except MultipleResultsFound:
        return (False, "More than one Current_Status found with id '%s'" % str(current_status_id))
    except Exception, msg:
        db.session.rollback()
        return (False, "Unknown error while querying for status message '%s': %s" % (str(current_status_id), str(msg)))
    return (True, data.serialize)


@require_db
def delete_current_status_messages(messages):
    """Delete a given list of messages ids from current_status
    Args:
        messages[Message.id]
    """

    success = True
    msg = ""
    try:
        db.session.begin()
        records = db.session.query(Current_Status).filter(Current_Status.message_id.in_(messages)).all()
        for record in records:
            db.session.delete(record)
        db.session.commit()
    except NoResultFound:
        db.session.rollback()
    except Exception as error:
        db.session.rollback()
        success = False
        msg = "%s" % str(error)
        api_log.error("[delete_current_status_messages]: Cannot delete current_status: %s" % str(msg))
    return success, msg


@require_db
def delete_status_messages(messages):
    """Delete a given list of messages ids from status_messages
    Args:
        messages[Message.id]
    """
    success = True
    msg = ""
    try:
        db.session.begin()
        records = db.session.query(Status_Message).filter(Status_Message.id.in_(messages)).all()
        for record in records:
            db.session.delete(record)
        db.session.commit()
    except NoResultFound:
        db.session.rollback()
    except Exception as error:
        db.session.rollback()
        success = False
        msg = "%s" % str(error)
        api_log.error("[delete_status_messages]: Cannot delete status_message: %s" % str(msg))
    return success, msg


@require_db
def delete_messages(messages):
    """Delete a given list of messages ids from message status and from current_status
    Args:
        messages[Message.id]
    """
    success, msg = delete_current_status_messages(messages)
    if not success:
        return success, msg

    success, msg = delete_status_messages(messages)
    return success, msg


@require_db
def db_insert_current_status_message(message_id, component_id, component_type, additional_info, replace, created=None):
    """Inserts a new notification on the system. The related message id should exists.
    Args:
        message_id (str:uuid string): Message id related with the notification
        component_id(str:uuid string): Component id related with the notification (Could be none for external messages)
        component_type(str): Component type. Allowed values: ('net','host','user','sensor','server','system','external')
        additional_info (str:json): Additional information you want to store.
    Returns:
        success(bool): True if the operation went well, False otherwise
        msg(str): A message string that will contain some kind of information in case of error"""

    if created is None:
        created = datetime.utcnow()

    if component_type not in ['net', 'host', 'user', 'sensor', 'server', 'system', 'external']:
        return False, "Invalid component_type"
    if component_type != "external" and component_id is None:
        return False, "Component id cannot be none for the given component_type"

    msg_id_binary = get_bytes_from_uuid(message_id)
    success, status_message = get_status_message_from_id(message_id=msg_id_binary, is_admin=True, serialize=False)
    if not success:
        return False, "The given message_id doesn't exist"
    if status_message is None:
        return False, "The given message_id doesn't exist. Message is None"
    component_id_binary = get_bytes_from_uuid(component_id)
    if (component_id_binary is None or component_id_binary == "") and component_type != "external":
        return False, "Invalid component_id"
    if replace is True:
        success, msg = delete_current_status_messages([msg_id_binary])
        if not success:
            return success, "Unable to remove previous messages for the given message ID."
    try:
        db.session.begin()
        current_status_message = Current_Status()
        current_status_message.id = uuid4().bytes
        current_status_message.component_type = component_type
        current_status_message.creation_time = created
        current_status_message.message_id = msg_id_binary
        current_status_message.additional_info = additional_info
        current_status_message.suppressed = 0
        current_status_message.viewed = 0
        current_status_message.component_id = component_id_binary
        db.session.add(current_status_message)
        db.session.commit()
    except Exception, e:
        db.session.rollback()
        return False, "%s" % str(e)
    return True, ""


@require_db
def get_current_status_from_message_id(message_id):
    """Return a list of current_status objects related with the given message_id"""
    try:
        query = db.session.query(Current_Status).filter(Current_Status.message_id == get_bytes_from_uuid(message_id))
        data = query.all()
    except Exception, e:
        return False, []
    return True, data


@require_db
def load_mcserver_messages(message_list):
    """Adds or updates messages coming from the mcserver

    Args:
        message_list[Status_Message]

    Returns:
        success (bool): True if successful, False elsewhere
        result  (str): Error message (if any)
    """
    result = ""
    success = True
    try:
        db.session.begin()
        for msg in message_list:
            msg_id_str = str(msg['msg_id'])
            msg_id_binary = get_bytes_from_uuid(msg_id_str)
            additional_info_json = ""
            if msg['additional_info'] is not None and msg['additional_info'] != "":
                try:
                    additional_info_json = json.dumps(msg['additional_info'])
                except Exception as e:
                    api_log.warning("Message with an invalid additional_info %s -  %s" % (msg_id_str, str(e)))
                    additional_info_json = ""
            success, status_message = get_status_message_from_id(message_id=msg_id_binary, is_admin=True,
                                                                 serialize=False)
            if success:
                #update values:
                status_message.level = Status_Message.get_level_integer_from_string(str(msg['level']))
                status_message.title = msg['title']
                status_message.description = msg['description']
                status_message.type = msg['type']
                success, current_status_message = get_current_status_from_message_id(msg_id_str)
                if not success or len(current_status_message) != 1:
                    api_log.error("Invalid external message %s. Current_Status: %s, tuples(%s)" % (
                        msg_id_str, success, len(current_status_message)))
                    continue
                current_status_message[0].additional_info = additional_info_json
                db.session.merge(current_status_message[0])
                db.session.merge(status_message)
            else:
                new_msg = Status_Message()
                new_msg.id = msg_id_binary
                new_msg.level = Status_Message.get_level_integer_from_string(str(msg['level']))
                new_msg.title = msg['title']
                new_msg.description = msg['description']
                new_msg.type = msg['type']
                new_msg.expire = datetime.strptime(msg['valid_to'], "%Y-%m-%dT%H:%M:%S")
                new_msg.actions = ""
                new_msg.alternative_actions = ""
                new_msg.source = "external"
                current_status_message = Current_Status()
                current_status_message.id = uuid4().bytes
                current_status_message.component_type = 'external'
                current_status_message.creation_time = datetime.strptime(msg['valid_from'], "%Y-%m-%dT%H:%M:%S")
                current_status_message.message_id = new_msg.id
                current_status_message.additional_info = ""
                current_status_message.suppressed = 0
                current_status_message.viewed = 0
                current_status_message.additional_info = additional_info_json
                db.session.add(new_msg)
                db.session.add(current_status_message)
        db.session.commit()
    except Exception, e:
        success = False
        result = "[load_mcserver_messages(] Error: %s" % str(e)
        db.session.rollback()
    return success, result


@require_db
def get_local_alarms(delay=1, delta=3):
    """
        Get the local alarms
        By default alarms older than 1 hours and delta 3
    """
    data = []
    try:
        query = "select hex(event_id), timestamp, hex(backlog_id) FROM alarm WHERE status=0 AND timestamp " \
                "between DATE_SUB(utc_timestamp(), interval %u hour) AND DATE_SUB(utc_timestamp(), interval %u hour) " \
                "UNION  select hex(event_id), timestamp, hex(backlog_id) FROM alarm WHERE status=1 AND " \
                "timestamp between DATE_SUB(utc_timestamp(), interval %u hour) AND DATE_SUB(utc_timestamp(), interval %u hour) " \
                "ORDER BY timestamp DESC;" % (delta + delay, delay, delta + delay, delay)
        api_log.debug("[get_local_alarms] Query:" + query)
        rows = db.session.connection(mapper=Server).execute(query)
        data = [row[0] for row in rows]
    except NoResultFound:
        pass
    except Exception, msg:
        api_log.error("[get_local_alarms] %s" % str(msg))
        return False, str(msg)
    return True, data
