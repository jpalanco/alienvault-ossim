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
import time

from flask_sqlalchemy import BaseQuery
from sqlalchemy import exc
from sqlalchemy import event
from sqlalchemy.pool import Pool

# Cache queries for a SQLAlchemy model class.
# To use this, set Model.query_class attribute to this one.
class CachedQuery (BaseQuery):
    def __init__ (self, entities, session = None):
        BaseQuery.__init__ (self, entities, session)
        self._prefixes = ['SQL_CACHE']

    def count (self):
        return len(list(self))

# Hook to check for dead connections and try to reconnect.
@event.listens_for(Pool, "checkout")
def ping_connection(dbapi_connection, connection_record, connection_proxy):
    cursor = dbapi_connection.cursor()
    try:
        cursor.execute("SELECT 1")

    except:
        connection_proxy._pool.dispose()
        raise exc.DisconnectionError()

    cursor.close()
# Each connect, modify the f*ck timeout wait_timeout
@event.listens_for(Pool,"connect")
def set_connect_timeout (dbapi_connection,connection_record):
    cursor = dbapi_connection.cursor()
    cursor.execute("SET SESSION wait_timeout = 57600;")
    cursor.close()
