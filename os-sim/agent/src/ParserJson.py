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
import threading
import simplejson as json
#
# LOCAL IMPORTS
#
from Detector import Detector
from TailFollowBookmark import TailFollowBookmark
from Event import Event
from ParserUtil import normalize_date, normalize_protocol
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

    def _parse_line(self, line):
        """Getting a json log line will transform to a valid event.
                * It will capture the default plugins_sid from the *.cfg file
                * *.cfg file will contain a mapping section where we can define the match between json and event

       @type line: str
       @param line: The file location of the spreadsheet
       @rtype: json
       @returns: a list of strings representing the header columns
       """
        json_event = json.loads(line)
        event = Event()
        if 'plugin_sid' not in json_event:
            event['plugin_sid'] = self.__plugin_configuration.get("DEFAULT", 'plugin_sid') if  self.__plugin_configuration.has_option("DEFAULT", 'plugin_sid') else 1

        for key, value in json_event.iteritems():
            event_key = key
            if self.__plugin_configuration.has_section("mapping"):
                if self.__plugin_configuration.has_option("mapping", key):
                    event_key = self.__plugin_configuration.get("mapping", key)
                    if event_key == "date":
                        value = normalize_date(value)
            event[event_key] = value
        event['log'] = json.dumps(json_event)

        return event


    def process(self):
        """Starts to process events!
        """
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
                        event = self._parse_line(line)
                        if not event:
                            continue

                        event['plugin_id'] = self.__plugin_configuration.get("DEFAULT", 'plugin_id')
                        self.send_message(event)
                    except Exception as exp:
                        self.logwarn(Lazyformat("Invalid Json event: {}", line))
                # Added small sleep to avoid the excessive cpu usage
                time.sleep(0.01)
            except Exception, e:
                self.logerror(Lazyformat("Processing failed: {}", e))
        tail.close()
        self.logdebug("Processing completed.")

class ParserJsonEve(ParserJson):
    """
    A class used to parse suricata eve events lines. It is a specific json format

    ...

    Attributes
    ----------
    @attr dns_translation : dict. it will transform the dns type into a plugin_sid id
    @attr _DEFAULT_PLUGIN_SID : int. Default plugin_sid in case it is not detected
    @attr filename_limit: int. Number of char allowed for this kind of DB fields
    @attr userdata_limit: int. Number of char allowed for this kind of DB fields

    Methods
    -------
    _parse_line(line)
        It will parse the log line and it will transform it to a valid event
    """

    dns_translation = {
        "query":20,
        "answer": 21
    }

    _DEFAULT_PLUGIN_SID = 20000000

    plugins_sid_translation = {
        "fileinfo":22,
        "ssh":23,
        "tls":24,
        "http":25
    }

    filename_limit = 256
    userdata_limit = 1024

    def _parse_line(self, line):
        """Getting a line will transform into a JSON event. It will parse suricata eve events.
        This function will override the parent function since suricata-eve it is an especial JSON case

        @type line: str
        @param line: The file location of the spreadsheet
        @rtype: json
        @returns: a list of strings representing the header columns
        """

        json_event = json.loads(line)
        event = Event()

        if json_event['event_type'] == 'alert':
            event["userdata7"] = str(json_event["icmp_type"]) if "icmp_type" in json_event else ""
            event["userdata8"] = str(json_event["icmp_code"]) if "icmp_code" in json_event else ""

            if 'alert' in json_event:
                event['plugin_sid'] = json_event['alert']["signature_id"] if "signature_id" in json_event["alert"] else self._DEFAULT_PLUGIN_SID
                event["userdata1"] = str(json_event["alert"]["action"]) if "action" in json_event["alert"] else ""
                event["userdata2"] = str(json_event["alert"]["signature"])[:self.userdata_limit] if "signature" in json_event["alert"] else ""
                event["userdata3"] = str(json_event["alert"]["rev"]) if "rev" in json_event["alert"] else ""
                event["userdata5"] = str(json_event["alert"]["severity"]) if "severity" in json_event["alert"] else ""
                event["userdata6"] = str(json_event["alert"]["category"]) if "category" in json_event["alert"] else ""
            else:
                event['plugin_sid'] =  self._DEFAULT_PLUGIN_SID
        else:
            return False

        event['date'] = normalize_date(json_event['timestamp']) if 'timestamp' in json_event else ""
        event['src_ip'] = json_event['src_ip'] if 'src_ip' in json_event else ""
        event['dst_ip'] = json_event['dest_ip'] if 'dest_ip' in json_event else ""
        event['src_port'] = json_event['src_port'] if 'src_port' in json_event else ""
        event['dst_port'] = json_event['dest_port'] if 'dest_port' in json_event else ""
        event['protocol'] = normalize_protocol(json_event['proto']) if 'proto' in json_event else ""
        event['userdata4'] = str(json_event['in_iface']) if "type" in json_event["in_iface"] else ""

        event['log'] = json.dumps(json_event)

        return event
# vim:ts=4 sts=4 tw=79 expandtab:
