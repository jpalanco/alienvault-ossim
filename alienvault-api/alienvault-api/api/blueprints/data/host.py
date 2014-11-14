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
from flask import (Blueprint)
from api.lib.auth import admin_permission
from api.lib.common import (
    make_ok, make_error, document_using)
from api.lib.utils import accepted_url
from uuid import UUID
from apimethods.data.host import (
    delete_host_references, delete_orphan_status_message)

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<host_id>', methods=['DELETE'])
@document_using('static/apidocs/status.html')
@admin_permission.require(http_exception=403)
@accepted_url({'host_id': {'type': UUID}})
def delete_host(host_id):

    (success, data) = delete_host_references(host_id)
    if not success:
        make_error(data, 500)

    return make_ok()


@blueprint.route('', methods=['DELETE'])
@document_using('static/apidocs/status.html')
@admin_permission.require(http_exception=403)
def delete_status_message():

    (success, data) = delete_orphan_status_message()
    if not success:
        make_error(data, 500)

    return make_ok(data=data)
