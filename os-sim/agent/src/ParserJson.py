#
# License:
#
# Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2015 AlienVault
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
import time
import signal
import threading
import simplejson as json
#
# LOCAL IMPORTS
#
from Detector import Detector
from TailFollowBookmark import TailFollowBookmark
from Event import Event
from ParserUtil import normalize_date
from Logger import Lazyformat


class ParserJson(Detector):
    def __init__(self, conf, plugin, conn):
        Detector.__init__(self, conf, plugin, conn)
        self.__conf = conf
        self.__plugin_id = plugin.get("DEFAULT", "plugin_id")
        self.__shutdown_event = threading.Event()
        self.__location = self._plugin.get("config", "location")
        self.__plugin_configuration = plugin

    def check_file_path(self, location):
        can_read = True
        create_file = self.__plugin_configuration.getboolean("config", "create_file") if self.__plugin_configuration.has_option("config", "create_file") else False
        if not os.path.exists(location) and create_file:
            if not os.path.exists(os.path.dirname(location)):
                self.logdebug(Lazyformat("Creating directory {}...", os.path.dirname(location)))
                os.makedirs(os.path.dirname(location), 0755)
            self.logwarn(Lazyformat("The {} file is missing. Creating a new one...", location))
            fd = open(location, 'w')
            fd.close()

        # Open file
        fd = None
        try:
            # check if file exist.
            if os.path.isfile(location):
                fd = open(location, 'r')
            else:
                self.logwarn(Lazyformat("File: {} does not exist!", location))
                can_read = False

        except IOError, e:
            self.logerror(Lazyformat("Can not read from file {}: {}", location, e))
            can_read = False
        if fd is not None:
            fd.close()
        return can_read

    def stop(self):
        self.loginfo("Scheduling plugin stop")
        self.__shutdown_event.set()

    def process(self):
        """Starts to process events!
        """
        number_of_lines = 0
        # Check if we have to create the location file
        self.check_file_path(self.__location)
        while not self.__shutdown_event.is_set() and not os.path.isfile(self.__location):
            time.sleep(1)
        tail = TailFollowBookmark(filename=self.__location,
                                  track=True,
                                  encoding=self.__plugin_configuration.get('config', 'encoding'))
        self.loginfo(Lazyformat("Reading from {}", self.__location))
        while not self.__shutdown_event.is_set():
            try:
                # stop processing tails if requested
                if self.__shutdown_event.is_set():
                    break
                for line in tail:
                    try:
                        json_event = json.loads(line)
                        event = Event()
                        if 'plugin_sid' not in json_event:
                            event['plugin_sid'] = self.__plugin_configuration.get("DEFAULT", 'plugin_sid') if  self.__plugin_configuration.has_option("DEFAULT", 'plugin_sid') else 1
                        event['plugin_id'] = self.__plugin_configuration.get("DEFAULT", 'plugin_id')
                        for key, value in json_event.iteritems():
                            event_key = key
                            if self.__plugin_configuration.has_section("mapping"):
                                if self.__plugin_configuration.has_option("mapping", key):
                                    event_key = self.__plugin_configuration.get("mapping", key)
                                    if event_key == "date":
                                        value = normalize_date(value)
                            event[event_key] = value
                        event['log'] = json.dumps(json_event)
                        self.send_message(event)
                        number_of_lines += 1
                    except Exception as exp:
                        print "CRG %s" % exp
                        self.logwarn(Lazyformat("Invalid Json event: {}", line))
                # Added small sleep to avoid the excessive cpu usage
                time.sleep(0.01)
            except Exception, e:
                self.logerror(Lazyformat("Processing failed: {}", e))
        tail.close()
        self.logdebug("Processing completed.")

# vim:ts=4 sts=4 tw=79 expandtab:
