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
# This file monitors 
#

#
# GLOBAL IMPORTS
#
import os, sys
import time
#
# LOCAL IMPORTS
#
from Detector import Detector
from Logger import Logger
from ParserSnort import ParserSnort
from Event import Snort
logger = Logger.logger

class ParserUnifiedSnort(Detector):
    """This class read the events from a directory, following all the events"""

    def __init__(self, conf, plugin, conn):

        self._conf = conf       # main agent config file
        self._plugin = plugin   # plugin config file
        self.conn = conn
        self._prefix = ""

        Detector.__init__(self, conf, plugin, self.conn)


    def process(self):
        self._dir = self._plugin.get("config", "directory")
        self._linklayer = self._plugin.get("config", "linklayer")
        self._unified_version = int(self._plugin.get("config", "unified_version"))

        if self._linklayer in ['ethernet', 'cookedlinux']:
            if os.path.isdir(self._dir):
                self._prefix = self._plugin.get("config", "prefix")

                if self._prefix != "":
                    snort = ParserSnort(linklayer=self._linklayer, unified_version=self._unified_version)
                    snort.init_log_dir(self._dir, self._prefix)

                    while 1:
                        # get next snort event (blocking)
                        ev = snort.get_snort_event()
                        # create the Snort event
                        event = Snort()
                        event["event_type"] = Snort.EVENT_TYPE

                        if event['interface'] is None:
                            event["interface"] = self._plugin.get("config", "interface")

                        (event["unziplen"], event["gzipdata"]) = ev.strgzip()

                        if event['plugin_id'] is None:
                            event['plugin_id'] = self._plugin.get("config", "plugin_id")

                        if event['type'] is None:
                            event['type'] = self._plugin.get("config", "type")

                        if ev.isIPV6():
                            event['src_ip'] = ev.sip
                            event['dst_ip'] = ev.dip
                            event['ipv6'] = "1"
                            self.send_message(event)
                        else:
                            self.send_message(event)

                else:
                    logger.error("Bad config parameter: directory (%s)" % dir)
                    sys.exit(-1)

            else:
                logger.error("Unknown link layer")
                sys.exit(-1)

# vim:ts=4 sts=4 tw=79 expandtab:
