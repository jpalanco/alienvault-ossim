#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

#
# LOCAL IMPORTS
#
from Database import DatabaseConn
from Logger import Logger
from Monitor import Monitor
from Config import Conf, Plugin, Aliases, CommandLineOptions
import Config
#
# GLOBAL VARIABLES
#
logger = Logger.logger



class MonitorDatabase(Monitor):

    def open(self):

        location = self.plugin.get("config", "location")
        (db_type, host, db_name, user, password) = location.split(':')

        self.conn = DatabaseConn()
        self.conn.connect(db_type, host, db_name, user, password)


    def get_data(self, rule_name):
        """Get data from monitor."""
        
        if self.conn is None:
            return None

        data = ''
        query = self.queries[rule_name]

        logger.debug("Sending query to monitor: %s" % (query))
        result = self.conn.exec_query(query)
        logger.debug("Received data from monitor: %s" % (str(result)))
        return result


    def close(self):
        """Close monitor connection."""
        self.conn.close()

