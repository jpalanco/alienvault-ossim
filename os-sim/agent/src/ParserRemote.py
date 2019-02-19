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
import os
import sys
import time

#
# LOCAL IMPORTS
#
from Detector import Detector
from Logger import Lazyformat
from SSHConnection import SSHConnection
from ParserLog import RuleMatch
import select


class ParserRemote(Detector):

    def __init__(self, conf, plugin, conn):
        self._conf = conf        # config.cfg info
        self._plugin = plugin    # plugins/X.cfg info
        self.rules = []          # list of RuleMatch objects
        self.conn = conn
        Detector.__init__(self, conf, plugin, conn)

        self.stop_processing = False


    def check_file_path(self, location):
        if self._plugin.has_option("config", "create_file"):
            create_file = self._plugin.getboolean("config", "create_file")
        else:
            create_file = False

        if not os.path.exists(location) and create_file:
            if not os.path.exists(os.path.dirname(location)):
                self.logwarn(Lazyformat("Creating the {} directory...", os.path.dirname(location)))
                os.makedirs(os.path.dirname(location), 0755)

            self.logwarn(Lazyformat("The {} file is missing. Creating a new one...", location))
            fd = open(location, 'w')
            fd.close()

        # open file
        try:
            fd = open(location, 'r')

        except IOError, e:
            self.logerror(Lazyformat("Failed to read the file {}: {}", location, e))
            sys.exit()

        fd.close()


    def stop(self):
        self.logdebug("Scheduling plugin stop")
        self.stop_processing = True

        try:
            self.join()
        except RuntimeError:
            self.logwarn("Stopping thread that likely hasn't started.")


    def process(self):
        locations = self._plugin.get("config", "location")
        locations = locations.split(',')
        #REMOTE????
        # first check if file exists
        #for location in locations:
        #    self.check_file_path(location)

        # compile the list of regexp
        unsorted_rules = self._plugin.rules()
        keys = unsorted_rules.keys()
        keys.sort()
        for key in keys:
            item = unsorted_rules[key]
            self.rules.append(RuleMatch(key, item, self._plugin))

        conns = []
    
        host = self._plugin.get("config", "host")
        user = self._plugin.get("config", "user")
        passwd = self._plugin.get("config", "passwd")
        conn = SSHConnection(host, 22, user, passwd)
        connected = False
        while not connected:
            connected = conn.connect()
            if not connected:
                self.loginfo(Lazyformat(
                    "Error connecting to {} for remote logging, retry in 30 seconds",
                    host
                ))
                time.sleep(30)
        self.loginfo(Lazyformat("Connection to {} established", host))
        conns.append(conn)
        while not self.stop_processing:
            # is plugin enabled?
            if not self._plugin.getboolean("config", "enable"):
                # wait until plugin is enabled
                while not self._plugin.getboolean("config", "enable"):
                    time.sleep(1)
                # plugin is now enabled, skip events generated on
                # 'disable' state, so move to the end of file
            self._thresholding()
            for c in conns:
                # stop processing tails if requested
                if self.stop_processing:
                    break
            transport = c.client.get_transport()
            channel = transport.open_session()
            if self._plugin.getboolean("config", "readAll"):
                cmd = "tail -f -n 10000000000000000000 %s" % locations[0]
            else:
                cmd = "tail -f -n 0 %s" % locations[0]
            channel.exec_command(cmd)
            tmp_data = ""
            while True:
                if self.stop_processing:
                    break
                rl, wl, xl = select.select([channel],[],[],0.0)
                if len(rl) > 0:
                    data = tmp_data + channel.recv(1024)
                    data = data.split("\n")
                    tmp_data = data[len(data)-1]
                    for d in data:
                        matches = 0
                        rules = 0
                        if self.stop_processing:
                            break
                        for rule in self.rules:
                            rules += 1
                            rule.feed(d)
                            if rule.match():
                                matches += 1
                                self.logdebug(Lazyformat("Match rule: [{}] -> {}", rule.name, d))
                                event = rule.generate_event()
                                if event is not None:
                                    self.send_message(event)
                                    break
                    time.sleep(0.1)
        for c in conns:
            c.closeConnection()
        self.logdebug("Processing completed.")
# vim:ts=4 sts=4 tw=79 expandtab:
