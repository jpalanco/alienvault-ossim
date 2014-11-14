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
from flask.ext.principal import UserNeed, identity_loaded, RoleNeed, Permission, Principal
from flask.ext.login import current_user

# Needs
be_admin = RoleNeed('admin')
be_logged = RoleNeed('loginUser')

# Permissions
# Permission objects are used mostly to check for requirements.
# e.g. @admin_permission.require(http_exception=401)
# decorates a function, checks if the calling user is an administrator
# and returns a HTTP 401 code if it is not.
admin_perm = Permission(be_admin)
logged_perm = Permission(be_logged)


class AdminPermission:
    """
    Class for wrap permission require decorator
    """
    def require(self, http_exception=403, func=None):
        if func is not None:
            def permission_func(func):
                func._permission = 'admin'
                req = admin_perm.require(http_exception=403)
                func = req.__call__(func)
                return func
            return permission_func(func)

        else:
            def partial_check(func):
                return self.require(http_exception, func)
            return partial_check


class LoggedPermission:
    """
    Class for wrap permission require decorator
    """
    def require(self, http_exception=403, func=None):
        if func is not None:
            def permission_func(func):
                func._permission = 'logged'
                req = logged_perm.require(http_exception=403)
                func = req.__call__(func)
                return func
            return permission_func(func)

        else:
            def partial_check(func):
                return self.require(http_exception, func)
            return partial_check


admin_permission = AdminPermission()
logged_permission = LoggedPermission()
