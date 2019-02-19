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
from flask import Blueprint, request
from flask.ext.login import current_user
from uuid import UUID

from api.lib.auth import admin_permission, logged_permission
from api.lib.utils import accepted_url, is_admin_user
from api.lib.common import (make_ok, make_error, document_using, make_bad_request)
from apimethods.data.status import get_status_messages, set_status_message_as_viewed, set_status_message_as_suppressed, get_status_message_by_id, get_status_messages_stats,insert_custom_message
from apimethods.utils import is_valid_integer, is_json_true
from api.lib.monitors.messages import (Message)
import api_log

blueprint = Blueprint(__name__, __name__)


@blueprint.route('', methods=['GET'])
@document_using('static/apidocs/status.html')
@logged_permission.require(http_exception=401)
@accepted_url({'component_id': {'type': UUID, 'optional': True},
               'component_type': {'type': str, 'optional': True},
               'level': {'type': str, 'optional': True},
               'order_by': {'type': str, 'optional': True},
               'page': {'type': int, 'optional': True},
               'page_rows': {'type': int, 'optional': True},
               'order_desc': {'type': bool, 'optional': True},
               'message_id': {'type': UUID, 'optional': True},
               'message_type': {'type': str, 'optional': True},
               'search': {'type': str, 'optional': True},
               'only_unread': {'type': bool, 'optional': True}})
def get_data_status_messages():
    """Retrieves a list of current_status messages matching the given criteria"""
    component_id = request.args.get('component_id')
    component_type = request.args.get('component_type')
    message_id = request.args.get('message_id', None)
    search = request.args.get('search', None)
    order_desc = is_json_true(request.args.get('order_desc'))
    only_unread = is_json_true(request.args.get('only_unread'))

    level = request.args.get('level')
    if level is not None:
        level = level.split(',')
        valid_levels = ["info","warning","error"]
        if not set(level).issubset(valid_levels):
            return make_bad_request("Invalid parameter level. Allowed valeus are %s" % str(valid_levels))

    page = request.args.get('page', 1)
    if page is not None:
        if not is_valid_integer(page):
            return make_bad_request("The parameter page (%s) is not a valid integer value" % str(page))
        page = int(page)

    page_row = request.args.get('page_rows', 50)
    if page_row is not None:
        page_row = int(page_row)

    message_type = request.args.get('message_type', None)
    if message_type is not None:
        message_type = message_type.split(',')

    orderby = request.args.get('order_by')
    if orderby not in ['creation_time', 'component_type', 'message_level', 'message_type', 'message_title', '', None]:
        return make_bad_request("Invalid parameter order by. Allowed values are ('creation_time','component_type','"
                                "message_level','message_type','message_title','')")

    (success, data) = get_status_messages(component_id=component_id, message_level=level, order_by=orderby,
                                          page=page, page_row=page_row, order_desc=order_desc,
                                          component_type=component_type, message_id=message_id,
                                          message_type=message_type, search=search, only_unread=only_unread,
                                          login_user=current_user.login,
                                          is_admin=current_user.is_admin)

    if not success:
        return make_error(data, 500)
    return make_ok(**data)


@blueprint.route('/stats', methods=['GET'])
@document_using('static/apidocs/status.html')
@logged_permission.require(http_exception=401)
@accepted_url({'search': {'type': str, 'optional': True},
               'only_unread': {'type': bool, 'optional': True}})
def get_data_status_messages_stats():
    """ Retrieves a list of current status messages stats """
    search = request.args.get('search', None)
    only_unread = is_json_true(request.args.get('only_unread'))

    (success, data) = get_status_messages_stats(search=search, only_unread=only_unread, login_user=current_user.login, is_admin=current_user.is_admin)

    if not success:
        return make_error(data, 500)
    return make_ok(**data)


@blueprint.route('/<status_message_id>', methods=['PUT'])
@document_using('static/apidcos/status.html')
@logged_permission.require(http_exception=401)
@accepted_url({'status_message_id': {'type': UUID, 'optional': False},
               'viewed': {'type': str, 'optional': True, 'values': ['true', 'false']},
               'suppressed': {'type': str, 'optional': True, 'values': ['true', 'false']}})
def set_data_status_message(status_message_id):
    """Sets the status message as viewed/suppressed"""
    viewed = request.args.get('viewed', None)
    suppressed = request.args.get('suppressed', None)
    if viewed is None and suppressed is None:
        return make_bad_request("Missing parameter. viewed or suppressed are required")
    if viewed not in ['true', 'false', None]:
        return make_bad_request("Invalid value for parameter viewed")
    if suppressed not in ['true', 'false', None]:
        return make_bad_request("Invalid value for parameter suppressed")

    if viewed is not None:
        viewed = True if viewed == 'true' else False
        (success, data) = set_status_message_as_viewed(status_message_id, viewed)
        if not success:
            return make_error(data, 500)
    if suppressed is not None:
        suppressed = True if suppressed == 'true' else False
        (success, data) = set_status_message_as_suppressed(status_message_id, suppressed)
        if not success:
            return make_error(data, 500)
    return make_ok()



@blueprint.route('/<message_id>', methods=['GET'])
@document_using('static/apidocs/status.html')
@logged_permission.require(http_exception=401)
@accepted_url({'message_id': {'type': UUID, 'optional': False}})
def get_data_status_message_by_id(message_id):
    """Retrieves the given message id"""
    (success, data) = get_status_message_by_id(message_id, is_admin_user())
    if not success:
        return make_error(data, 500)
    return make_ok(**data)


@blueprint.route('/', methods=['POST'])
@document_using('static/apidcos/status.html')
@admin_permission.require(http_exception=403)
@accepted_url({'message_id': {'type': UUID, 'optional': False},
               'msg_level': {'type': int, 'optional': True},
               'msg_type': {'type': str, 'optional': False},
               'msg_message_role': {'type': str, 'optional': True},
               'msg_action_role': {'type': str, 'optional': True},
               'msg_title': {'type': str, 'optional': False},
               'msg_description': {'type': str, 'optional': False},
               'msg_actions': {'type': str, 'optional': False},
               'msg_alternative_actions': {'type': str, 'optional': True},
               'msg_source': {'type': str, 'optional': False},
               'component_id': {'type': UUID, 'optional': True},
               'component_type': {'type': str, 'optional': False},
               'additional_info': {'type': str, 'optional': True},
               'created': {'type': str, 'optional': True}
})
def save_new_message():
    message = Message()
    message.id =  request.form.get('message_id', None)
    message.level = request.form.get('msg_level', "warning")
    message.type = request.form.get('msg_type', None)
    message.message_role = request.form.get('msg_message_role', None)
    message.action_role = request.form.get('msg_action_role', None)
    message.title = request.form.get('msg_title', None)
    message.description = request.form.get('msg_description', None)
    message.actions = request.form.get('msg_actions', None)
    message.alternative_actions = request.form.get('msg_alternative_actions', None)
    message.source = request.form.get('msg_source', None)
    component_id = request.form.get('component_id', None)
    component_type = request.form.get('component_type', None)
    additional_info = request.form.get('additional_info', '{}')
    created = request.form.get('created', None)
    
    insert_custom_message(message, component_id, component_type, additional_info, False, created)
    return make_ok()

