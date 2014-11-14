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
import db
from db.models.alienvault_api import Monitor_Data, Current_Status, Logged_Actions
from apimethods.utils import get_bytes_from_uuid
from apimethods.decorators import require_db


@require_db
def save_current_status_message(component_id, component_type, message_code, data=""):
    result = True
    try:
        db.session.begin()
        message = get_current_status_message(component_id,message_code)
        if message is not None:
            message_data = message
            message_data.creation_time = datetime.now()
            db.session.merge(message_data)
        else:
            message_data = Current_Status()
            message_data.component_id = component_id
            message_data.component_type = component_type
            message_data.creation_time = datetime.now()
            message_data.message_id = message_code
            message_data.additional_info = data
            message_data.supressed = 0
            message_data.viewed = 'false'
            db.session.add(message_data)
        db.session.commit()
    except:
        db.session.rollback()
        result = False

    return result


@require_db
def get_current_status_message(component_id, message_code):
    """
    Returns the message object from a message code and a component id
    :param component_id: The component id - uuid canonical string
    :param message_code: Message type
    """
    try:
        message = db.session.query(Current_Status).filter(Current_Status.message_id == message_code).\
                  filter(Current_Status.component_id == component_id).first()
    except NoResultFound:
        message = None
    except MultipleResultsFound:
        message = message[0]
    except Exception:
        db.session.rollback()
        message = None
    return message


@require_db
def get_all_monitor_data():
    """
    Returns all the monitor data
    """
    try:
        monitors = db.session.query(Monitor_Data).all()
    except Exception:
        db.session.rollback()
        monitors = None
    return monitors


@require_db
def purge_current_status_message(message_id):
    """
    Removes the message with the given message id
    :param message_id: The message id
    :return: True on success false otherwise
    """
    rc = True
    try:
        db.session.begin()
        db.session.query(Current_Status).filter(Current_Status.message_id == message_id).delete()
        db.session.commit()
    except Exception:
        db.session.rollback()
        rc = False
    return rc


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
        import traceback
        print traceback.format_exc()
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
