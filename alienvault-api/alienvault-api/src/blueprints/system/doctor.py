# -*- coding: utf-8 -*-
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
from api.lib.common import (
     make_ok, make_error, make_bad_request, document_using)

from uuid import UUID
from api.lib.auth import logged_permission
from api.lib.utils import accepted_url

from apimethods.system.doctor import get_support_info

blueprint = Blueprint(__name__, __name__)

@blueprint.route('/<system_id>/doctor/support', methods=['GET'])
@document_using('static/apidocs/doctor.html')
@logged_permission.require(http_exception=401)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'ticket':str})
def support(system_id):
    ticket = request.args.get('ticket')
    if ticket is None:
        return make_bad_request("Missing param ticket")

    (success, data) = get_support_info (system_id, ticket)
    if not success:
        return make_error (data, 500)

    return make_ok (**data)
