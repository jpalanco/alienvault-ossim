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

import hashlib
import re
from flask import Blueprint, request, current_app
from flask.ext.login import login_user, logout_user, current_user
from flask.ext.principal import Identity, identity_loaded, identity_changed, AnonymousIdentity
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from api.lib.common import make_ok, make_error, make_bad_request
from api.lib.utils import accepted_url
from api import login_manager, app
from api.lib.auth import be_logged, be_admin, logged_permission
import db
from db.models.alienvault import Users
from api import API_i18n
from api.lib.utils import is_valid_user, is_valid_user_password, is_admin_user
from api.api_i18n import messages as i18nmsgs
from apimethods.system.user import populate_user_permissions_table

blueprint = Blueprint(__name__, __name__)

check_md5_regex = re.compile("[0-9a-fA-F]{32}")
check_sha256_regex = re.compile("[0-9a-fA-F]{64}")
@blueprint.route('/login', methods=['GET'])
@accepted_url({'username': str, 'password': str})
def login():
    username = request.args.get('username')
    password = request.args.get('password')
    if username is None:
        return make_bad_request(API_i18n.error(i18nmsgs.MISSING_PARAMETER_USERNAME))
    if password is None:
        return make_bad_request(API_i18n.error(i18nmsgs.MISSING_PARAMETER_PASSWORD))
    if not is_valid_user(username):
        return make_bad_request(API_i18n.error(i18nmsgs.INVALID_USERNAME))

    if not is_valid_user_password(password):
        return make_bad_request(API_i18n.error(i18nmsgs.INVALID_PASSWORD))
    try:
        user = db.session.query(Users).filter_by(login=username).one()
    except NoResultFound:
        return make_error(API_i18n.error(i18nmsgs.INVALID_USERNAME_OR_PASSWORD), 401)
    except MultipleResultsFound:
        return make_error(API_i18n.error(i18nmsgs.TOO_MANY_USERNAMES_MATCHING), 500)
    except Exception, e:
        return make_error(API_i18n.error(i18nmsgs.TOO_MANY_USERNAMES_MATCHING, {"exception": str(e)}), 500)

    if user is not None and user.enabled == 1:
        login_valid = 0
        #check if the password is blank
        if password == user.av_pass:
            login_valid = 1
        else:
            password_sha256_hex = password
            if check_sha256_regex.match(password) is None:
                password_sha256 = hashlib.sha256()
                password_sha256.update(password.encode('latin-1'))
                password_sha256_hex = password_sha256.hexdigest()

            if password_sha256_hex.lower() == user.av_pass.lower():
                login_valid = 1
            else:
                # check if the password is already md5
                password_md5_hex = password
                if check_md5_regex.match(password) is None:
                    password_md5 = hashlib.md5()
                    password_md5.update(password.encode('latin-1'))
                    password_md5_hex = password_md5.hexdigest()
                if password_md5_hex.lower() == user.av_pass.lower():
                    login_valid = 1

        if login_valid == 1:
            login_user(user)
            identity_changed.send(app, identity=Identity(user.login))
            if not (current_user.is_admin == 1 or current_user.login == 'admin'):
                success = populate_user_permissions_table(user.login)
                app.logger.warning("user_perm table for the user %s has been populated successfully? %s" % (user.login, success))
            return make_ok()

    return make_error(API_i18n.error(i18nmsgs.INVALID_USERNAME_OR_PASSWORD), 401)


# WARNING:
# The decorator order is strictly like this.
# First, the route, then the login constraint.
@blueprint.route('/logout', methods=['GET'])
@logged_permission.require(http_exception=401)
def logout():
    logout_user()
    identity_changed.send(current_app._get_current_object(), identity=AnonymousIdentity())
    return make_ok()


@login_manager.user_loader
def load_user(username):
    try:
        user = db.session.query(Users).filter_by(login=username).one()
    except Exception:
        return None

    return user


@login_manager.unauthorized_handler
def unauthorized():
    return make_error('Authentication required', 401)


@identity_loaded.connect_via(app)
def on_identity_loaded(sender, identity):
    # Set the identity user object
    identity.user = current_user

    # Add the needs to the identity.
    if hasattr(current_user, 'login'):
        identity.provides.add(be_logged)

        if current_user.is_admin != 0 or current_user.login == 'admin':
            identity.provides.add(be_admin)
