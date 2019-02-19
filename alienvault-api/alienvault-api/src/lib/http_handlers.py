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

from api.lib.common import make_error

def configure_errors (app = None):

    # Handler for HTTP code 400 (Bad Request)
    @app.errorhandler(400)
    def bad_request (e):
        return make_error(e.description, 400)

    # Handler for HTTP code 401 (Unauthorized)
    @app.errorhandler(401)
    def unauthorized (e):
        return make_error(e.description, 401)

    # Handler for HTTP code 403 (Forbidden)
    @app.errorhandler(403)
    def forbidden (e):
        return make_error(e.description, 403)

