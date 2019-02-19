# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2015 AlienVault
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

import db

from db.models.alienvault import Config
from apimethods.decorators import require_db
from sqlalchemy.orm.exc import NoResultFound
import api_log
@require_db
def has_admin_users():
    """
    Tells if there are any admin users in the database or not.
    Returns:
        result(bool):True if success, False otherwise
    """
    result = False
    try:
        result = db.session.query(Config).filter(Config.conf == 'first_login').one().value.lower() == 'no'
    except NoResultFound:
        result = False
        api_log.warning("[has_admin_users] No first_login row found.")
    except Exception as error:
        api_log.error("[has_admin_users] %s" % str(error))
        result = False
    return result
