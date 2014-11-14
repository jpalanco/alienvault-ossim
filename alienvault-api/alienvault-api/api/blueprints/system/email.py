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
from flask import Blueprint, request, current_app


from api.lib.common import (
    http_method_dispatcher, make_ok, make_error, check,
    document_using, validate_json, if_content_exists_then_is_json)

from apimethods.system.email import run_send_email

from api.lib.utils import accepted_url
from api.lib.auth import admin_permission
from uuid import UUID
blueprint = Blueprint(__name__, __name__)

@blueprint.route('/<system_id>/email', methods=['GET'])
@document_using('static/apidocs/apps/backup.html')
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'host': str,'port':str,'sender':str,'recipients':str,'body':str,'subject':str,'use_ssl':str})
@admin_permission.require(http_exception=403)
def send_mail(system_id):
    # TODO: If the user wants to attach some files, this files should be
    # on our system. So, we need a way to allow the user to upload files to our system
    # Be aware of the file permissions.
    host = request.args.get('host',None)
    port = request.args.get('port',None)

    sender = request.args.get('sender',None)
    recipients = request.args.get("recipients",None)
    #b64 data
    subject = request.args.get("subject",None)
    #b64 data
    body = request.args.get("body",None)

    user = request.args.get("user",None)
    passwd = request.args.get("passwd",None)
    use_ssl = request.args.get("use_ssl",None)
    # NOTE: Think about this.....
    attachments = request.args.get("attachments","") # Comma separated file list

    (success, data) = run_send_email (system_id, host,port,sender,recipients,subject, body, user,passwd, use_ssl, attachments)
    if not success:
        return make_error(data, 404)
    return make_ok(result=data)
