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
from apimethods.system.av_license import (register_appliance_trial,
                                          register_appliance_pro,
                                          get_current_version)
from apimethods.system.system import asynchronous_update
from api.lib.common import make_ok, make_error, document_using
from api.lib.utils import accepted_url, first_init_admin_access

from uuid import UUID

import api_log

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<system_id>/license/trial', methods=['GET'])
@document_using('static/apidocs/license.html')
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'email': str})
def get_license_trial(system_id):
    if not first_init_admin_access():
        return make_error ('Request forbidden -- authorization will not help', 403)

    # Retrieve URL parameters.
    email = request.args.get('email')
    if email is None:
        current_app.logger.error("license: get_license_trial error: Bad param 'email'")
        return make_error('Bad parameter email', 400)

    (success, msg) = register_appliance_trial(email, system_id, False)
    if not success:
        current_app.logger.error("license: get_license_trial error: " + str(msg))
        return make_error(msg, 500)

    return make_ok()


@blueprint.route('/<system_id>/license/pro', methods=['GET'])
@document_using('static/apidocs/license.html')
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'key': str})
def get_license_pro(system_id):
    if not first_init_admin_access():
        return make_error ('Request forbidden -- authorization will not help', 403)

    # Retrieve URL parameters.
    key = request.args.get('key')
    if key is None:
        current_app.logger.error("license: get_license_pro error: Missing param 'key'")
        return make_error('Missing param key', 400)

    (success, msg) = register_appliance_pro(key, system_id, False)
    if not success:
        current_app.logger.error("license: get_license_pro error: " + str(msg))
        return make_error(msg, 500)

    return make_ok()


@blueprint.route('/<system_id>/license/version', methods=['GET'])
@document_using('static/apidocs/license.html')
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_license_version(system_id):
    """
    Get the current versions
    """
    if not first_init_admin_access():
        return make_error ('Request forbidden -- authorization will not help', 403)

    (success, msg) = get_current_version(system_id)
    if not success:
        api_log.error("license: get_license_versions error: " + str(msg))
        return make_error("An internet connection is needed in order to activate your version.", 500)

    return make_ok(**msg)
