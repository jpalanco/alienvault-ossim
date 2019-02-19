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

import MySQLdb.cursors
from sqlalchemy import create_engine
from sqlalchemy.orm import scoped_session, sessionmaker
from sqlalchemy.pool import NullPool

engines = {}
Session = scoped_session(sessionmaker(autocommit=True))
session = Session()


def get_engine(database='any'):
    global engines
    if engines == {}:
        from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler
        config_file = "/etc/ossim/ossim_setup.conf"
        ossim_setup = AVOssimSetupConfigHandler(config_file)

        uri = "mysql://%s:%s@%s/" % (ossim_setup.get_database_user(),
                                     ossim_setup.get_database_pass(),
                                     ossim_setup.get_database_db_ip())

        kwargs = {'echo': False,
                  'poolclass': NullPool,
                  'pool_reset_on_return': None,
                  'connect_args': {'cursorclass': MySQLdb.cursors.SSCursor, 'autocommit': True}}

        engines = {'alienvault': create_engine(uri + 'alienvault', **kwargs),
                   'alienvault_siem': create_engine(uri + 'alienvault_siem', **kwargs),
                   'alienvault_api': create_engine(uri + 'alienvault_api', **kwargs)}

    if database == 'any':
        # Return any engine, as it is not really needed to specify one.
        return engines.get('alienvault')

    return engines.get(database)
