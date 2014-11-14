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
from uuid import UUID

from api.lib.auth import admin_permission, logged_permission
from api.lib.utils import accepted_url, is_admin_user
from api.lib.common import (make_ok, make_error, document_using,make_bad_request)
from apimethods.data.status import get_status_messages, put_status_message, get_status_message_by_id
from apimethods.utils import is_valid_integer
import api_log
import re

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
               'message_id':{'type':int, 'optional':True}})
def get_data_status_messages():

    component_id = request.args.get('component_id')
    component_type = request.args.get('component_type')
    message_id = request.args.get('message_id',None)

    level = request.args.get('level')
    if level is not None:
        level = level.split(',')
    order_desc = request.args.get('order_desc')

    page = request.args.get('page', 1)
    if page is not None:
        if not is_valid_integer(page):
            return make_error("The parameter page (%s) is not a valid integer value" % str(page),500)
        page = int(page)

    if message_id is not None:
        if not is_valid_integer(message_id):
            return make_error("The parameter message_id (%s) is not a valid integer value" % str(message_id),500)
        message_id = int(message_id)

    page_row = request.args.get('page_rows', 50)
    if page_row is not None:
        page_row = int(page_row)

    orderby = request.args.get('order_by')

    if orderby not in ['creation_time','component_type','level','', None]:
        return make_bad_request("Invalid parameter order by. Allowed values are ('creation_time','component_type','level','')")

    (success, data) = get_status_messages(component_id=component_id, level=level, orderby=orderby,
                                              page=page, page_row=page_row, order_desc=order_desc,
                                              component_type=component_type, message_id=message_id)
    if not success:
        return make_error(data, 500)

    return make_ok(**data)


@blueprint.route('/<int:message_id>', methods=['PUT'])
@document_using('static/apidcos/status.html')
@admin_permission.require(http_exception=403)
@accepted_url({'message_id': {'type': int, 'optional': False},
               'component_id': {'type': UUID, 'optional': False},
               'viewed': {'type': str, 'optional': False, 'values': ['true', 'false']}})
def put_data_status_message(message_id):

    component_id = request.args.get('component_id')
    viewed = request.args.get('viewed')

    (success, data) = put_status_message(message_id, component_id, viewed)

    if not success:
        return make_error(data, 500)

    return make_ok()


@blueprint.route('/<int:message_id>', methods=['GET'])
@document_using('static/apidocs/status.html')
@logged_permission.require(http_exception=401)
@accepted_url({'message_id': {'type': int, 'optional': False}})
def get_data_status_message_by_id(message_id):

    (success, data) = get_status_message_by_id(message_id, is_admin_user())
    if not success:
        return make_error(data, 500)

    return make_ok(**data)
