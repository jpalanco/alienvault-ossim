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


from datetime import datetime
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from sqlalchemy import text as sqltext
import db
import uuid
from sqlalchemy import desc, asc, and_, or_, func
from db.models.alienvault_api import Monitor_Data, Current_Status, Logged_Actions, UserPermissions
from apimethods.utils import get_bytes_from_uuid, get_uuid_string_from_bytes
from apimethods.decorators import require_db


@require_db
def save_current_status_message(component_id, component_type, message_code, data=""):
    """Saves or merges the status_message
    Args:
        component_id(uuid canonical string)
        message_code(uuid cannonical string):Message id
        data (str): Message data
        component_type(str): Component tyep
    """
    result = True
    try:
        message = get_current_status_message(component_id, message_code)
        db.session.begin()
        if message is not None:

            message_data = message
            #message_data.creation_time = datetime.now()
            message_data.additional_info = data
            db.session.merge(message_data)
        else:
            message_data = Current_Status()
            message_data.id = uuid.uuid4().bytes
            message_data.component_id = get_bytes_from_uuid(component_id)
            message_data.component_type = component_type
            message_data.creation_time = datetime.utcnow()
            message_data.message_id = get_bytes_from_uuid(message_code)
            message_data.additional_info = data
            message_data.supressed = 0
            message_data.viewed = 0
            db.session.add(message_data)
        db.session.commit()
    except Exception as error:
        db.session.rollback()
        result = False

    return result


@require_db
def get_current_status_message(component_id, message_id):
    """
    Returns the message object from a message code and a component id
    Args:
        component_id(UUID canonical string)
        message id(UUID cannonical string)
    """
    try:
        component_id_binary = get_bytes_from_uuid(component_id)
        message = db.session.query(Current_Status).filter(and_(
                                                        Current_Status.message_id == get_bytes_from_uuid(message_id),
                                                        Current_Status.component_id == get_bytes_from_uuid(component_id)
                                                        )).one()
    except NoResultFound:
        message = None
    except MultipleResultsFound:
        message = message[0]
    except Exception as e:
        message = None
    return message


@require_db
def get_all_monitor_data(monitor_id_list):
    """
    Returns all the monitor data regarding to all the monitors in the list
    """
    try:
        if monitor_id_list is not None and len(monitor_id_list) == 0:
            monitors = db.session.query(Monitor_Data).all()
        else:
            monitors = db.session.query(Monitor_Data).filter(Monitor_Data.monitor_id.in_(monitor_id_list)).all()
    except Exception:
        monitors = None
    return monitors

@require_db
def get_monitor_data(monitor_id):
    """
        Return data from :monitor_id: monitor
    """
    try:
        monitors_raw = db.session.query(Monitor_Data).filter(Monitor_Data.monitor_id == monitor_id).all()
        monitors_data = [x.serialize for x in   monitors_raw]
    except Exception:
        monitors_data = None
    return monitors_data


@require_db
def purge_current_status_message(message_id, component_ids):
    """
    Removes those messages with the given message id with components not in the list of component_ids
    Args:
        message_id(cannonical uuid string): The message id
        component_ids([uuid-string]: List of components to not delete
    :return: True on success false otherwise
    """
    result = True
    try:
        db.session.begin()
        # We cannot send a canonical uuid string to the database.
        # We have to remove 
        normalized_msg_id = message_id.replace('-', '').upper()
        # When the component_ids list is 0 for the given message id, that means that there
        # is no messages for this message id, so we should remove all of them.
        if len(component_ids) == 0:
            cmd = "delete from alienvault_api.current_status  where message_id=unhex('%s')" % (normalized_msg_id)
        else:
            component_ids_str = ','.join(["'%s'" % x.replace('-','').upper() for x in component_ids])
            cmd = "delete from alienvault_api.current_status  where message_id=unhex('%s') and hex(component_id) not in (%s)" % (normalized_msg_id, component_ids_str)
        db.session.connection(mapper=Current_Status).execute(cmd)
        db.session.commit()
    except Exception as e:
        db.session.rollback()
        result = False
    return result


@require_db
def add_current_status_messages(message_list=[]):
    """Add messages to the current status table"""
    rc = True
    try:
        db.session.begin()
        for message in message_list:
            db.session.add(message)
        db.session.commit()
    except Exception:
        db.session.rollback()
        rc = False
    return rc


@require_db
def remove_monitor_data_by_id(monitor_id):
    """
    Removes the monitor data with the given monitor id
    :param monitor_id: The monitor id
    :return: True on success false otherwise
    """
    rc = True
    try:
        db.session.begin()
        db.session.query(Monitor_Data).filter(Monitor_Data.monitor_id == monitor_id).delete()
        db.session.commit()
    except Exception:
        db.session.rollback()
        rc = False
    return rc


@require_db
def save_monitor_data(monitor_id, component_id, component_type, data):
    result = True
    try:
        db.session.begin()
        monitor_data = Monitor_Data()
        monitor_data.component_id = get_bytes_from_uuid(component_id)
        monitor_data.timestamp = datetime.now()
        monitor_data.monitor_id = monitor_id
        monitor_data.data = data
        monitor_data.component_type = component_type
        db.session.add(monitor_data)
        db.session.commit()
    except Exception:
        db.session.rollback()
        result = False

    return result


@require_db
def add_monitor_data_objects(monitor_data_list=[]):
    """Add messages to the current status table"""
    rc = True
    try:
        db.session.begin()
        for monitor in monitor_data_list:
            db.session.add(monitor)
        db.session.commit()
    except Exception:
        db.session.rollback()
        rc = False
    return rc


@require_db
def add_log_action_message(user, description):
    result = True
    try:
        db.session.begin()
        log = Logged_Actions()
        log.logged_user = user
        log.datetime = datetime.utcnow()
        log.action_description = description
        db.session.add(log)
        db.session.commit()
    except:
        db.session.rollback()
        result = False

    return result


@require_db
def db_populate_user_permissions_table(login_user):
    """Populates the user_perm table
    Args:
        login_user(str): The user logged in
    Returns:
        result(bool):True if success, False otherwise
    """
    result = True
    sp_call = sqltext("CALL alienvault_api.fill_user_perms('%s')" % login_user)
    try:
        db.session.begin()
        result_set = db.session.connection(mapper=UserPermissions).execute(sp_call)
        data = result_set.fetchall()
        db.session.commit()
        if len(data) <= 0:
            return False
    except Exception as err:
        db.session.rollback()
        result = False
    return result
