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


from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from sqlalchemy import desc, asc, or_
from traceback import format_exc
from apimethods.utils import get_bytes_from_uuid
from apimethods.decorators import require_db

import api_log
import db
from db.utils import paginate
from db.models.alienvault_api import Current_Status, Status_Message, Status_Action

from sqlalchemy.orm import aliased

@require_db
def get_status_messages(component_id=None, level=None, orderby=None, page=None,
                        page_row=None, order_desc=None, component_type=None, message_id=None):

    query = db.session.query(Current_Status)
    if orderby is not None:
        if orderby != 'level':
            if order_desc is not None and order_desc != 'true':
                query = query.order_by(asc(orderby))
            else:
                query = query.order_by(desc(orderby))
        else:
            # level case
            alias = aliased(Status_Message)
            if order_desc is not None and not order_desc != 'true':
                query = query.join(alias, Status_Message).order_by(desc(alias.level))
            else:
                query = query.join(alias, Status_Message).order_by(asc(alias.level))

    query = query.order_by(asc('viewed'))

    if component_id is not None:
        query = query.filter(Current_Status.component_id == get_bytes_from_uuid(component_id))
    if message_id is not  None:
        query  = query.filter(Current_Status.message_id == message_id)
    if level is not None:
        filter = [Current_Status.message.has(level=x) for x in level]
        query = query.filter(or_(*filter))

    if component_type is not None:
        query = query.filter(Current_Status.component_type == component_type)

    msgs = {}
    total = 0
    try:
        if page is None: #return all
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
def put_status_message(message_id, component_id, viewed):

    component_id_bin = get_bytes_from_uuid(component_id)

    try:
        status_message = db.session.query(Current_Status).filter(Current_Status.message_id == message_id,
                                                                        Current_Status.component_id == component_id_bin).one()
    except NoResultFound, msg:
        api_log.error("No Result: %s" % str(msg))
        return (False, "No result: Bad message_id, component_id")
    except MultipleResultsFound, msg:
        api_log.error("Multiple results: %s" % msg)
        return (False, "Multiple Results: Bad message_id, component_id")
    except Exception, msg:
        db.session.rollback()
        return (False, "Cannot retrieve status message")

    status_message.viewed = viewed

    try:
        db.session.begin()
        db.session.merge(status_message)
        db.session.commit()
    except Exception, msg:
        db.session.rollback()
        api_log.error("message: put_status_message: Cannot commit status_message: %s" % str(msg))
        return (False, "Cannot update status message")

    return (True, None)

@require_db
def get_status_message_by_id(message_id, is_admin=False):

    try:
        status_message = db.session.query(Status_Message).filter(Status_Message.id == message_id).one()
    except NoResultFound:
        return (False, "No message found with id '%d'" % message_id)
    except MultipleResultsFound:
        return (False, "More than one message found with id '%d'" % message_id)
    except Exception, msg:
        db.session.rollback()
        return (False, "Unknown error while querying for status message '%d': %s" % (message_id, str(msg)))

    # Assign the action '0' when the user does not have an administrator role.
    if not is_admin:
        try:
            action = db.session.query(Status_Action).filter(Status_Action.action_id == 0).one()
        except NoResultFound, msg:
            return (False, "No action found with id '0'")
        except MultipleResultsFound, msg:
            return (False, "More than one action found with id '0'")
        except Exception, msg:
            db.session.rollback()
            return (False, "Unknown error while querying for status message '0': %s" % str(msg))
        status_message.actions = [action]

    return (True, status_message.serialize)
