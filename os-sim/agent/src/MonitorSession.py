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
# GLOBAL IMPORTS
#
import re
import sgmllib
import socket
import urllib

#
# LOCAL IMPORTS
#
from Logger import Logger
from Monitor import Monitor
from SessionParser import SessionParser
from Config import Conf, Plugin, Aliases, CommandLineOptions
import Config
#
# GLOBAL VARIABLES
#
logger = Logger.logger



class MonitorSession(Monitor):

    def open(self):
        """Connect to monitor."""
        return


    def get_data(self, rule_name):
        """Get data from monitor."""

        data = ''
        location = self.plugin.get("config", "location")
        query = self.queries[rule_name]

        logger.debug("Sending query to monitor: %s" % (query))

        try:
            f = urllib.urlopen(location + query)
            s = f.read()

        except IOError, e:
            logger.warning("Error connecting to monitor: %s" % (e))
            return data

        sessionparser = SessionParser()
        sessionparser.parse(s)

        data = sessionparser.get_sessions()

        return data


    def close(self):
        """Close monitor connection."""
        return
