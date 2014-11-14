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
import time

#
# LOCAL IMPORTS
#
import Config
from EventList import EventList
from Logger import Logger
from Output import Output
from Stats import Stats

#
# GLOBAL VARIABLES
#
logger = Logger.logger



class EventConsolidation:

    # name of consolidation section at config.cfg
    CONSOLIDATION_SECTION = "event-consolidation"
    MAX_TIME = 60.0
    EVENT_OCCURRENCES = "occurrences" # event field for occurrences value


    def __init__(self, conf):
        self._conf = conf
        self.__event_list = EventList()
        self.start_time = time.time()
        self.enable = self.__is_consolidation_enable()


    def __is_consolidation_enable(self):

        section = EventConsolidation.CONSOLIDATION_SECTION

        for option in ("enable", "time"):
            if not self._conf.has_option(section, option):
                logger.warning("There is no %s option in %s section" % \
                    (option, EventConsolidation.CONSOLIDATION_SECTION))
                return False 
        
        # exit if enable = False
        if not self._conf.getboolean(section, "enable"):
            logger.info("Consolidation is not enabled")
            return False

        # "time" must be a float number
        if not self._conf.getfloat(section, "time"):
            logger.warning("There is no time variable in %s section" % \
                           (EventConsolidation.CONSOLIDATION_SECTION))
            return False

        return True


    def __pass_filters(self, event):

        section = EventConsolidation.CONSOLIDATION_SECTION

        # 1) consolidation by plugin
        if self._conf.has_option(section, "by_plugin"):
            plugins = Config.split_sids(self._conf.get(section, "by_plugin"))

            if str(event["plugin_id"]) in plugins:
                return True

        # 2) consolidation by src_ip
        for target in ("src_ip", "dst_ip", "sensor"):
            option = "by_" + target

            if self._conf.has_option(section, option):
                ips = Config.split_sids(self._conf.get(section, option))

                if event[target]:
                    if str(event[target]) in ips:
                        return True

        return False


    def insert(self, event):

        if not self.enable:
            return False

        if not self.__pass_filters(event):
            return False

        self.__event_list.appendRule(event)
        logger.debug("Added event (id:%s, sid:%s) to consolidation queue" % \
                     (event["plugin_id"], event["plugin_sid"]))
        Stats.consolidation['total'] += 1

        return True


    # clear consolidation queue processing its events
    # and removing them from the list
    def clear(self):

        events_to_remove = []

        for event in self.__event_list:
            Output.event(event)
            events_to_remove.append(event)
            Stats.consolidation['consolidated'] += 1

        for e in events_to_remove:
            self.__event_list.removeRule(e)

        del events_to_remove


    def __process(self):

        # tmp set to store event representation
        event_tmp = {}

        events_to_remove = []

        for event in self.__event_list:

            # save non comparable attributes
            date = event["date"]
            log = event["log"]
            event["date"] = event["log"] = ""

            str_event = str(event).strip()

            if event_tmp.has_key(str_event):
                events_to_remove.append(event)
                event_tmp[str_event] += 1
            else:
                event_tmp[str_event] = 1

            # restore non comparable attributes
            if date:
                event["date"] = date

            if log:
                event["log"] = log

        # remove duplicated events
        for e in events_to_remove:
            self.__event_list.removeRule(e)

        del events_to_remove

        # fill "occurrences" field
        for event in self.__event_list:

            date = event["date"]
            log = event["log"]
            event["date"] = event["log"] = ""

            str_event = str(event).strip()
            if event_tmp.has_key(str_event):
                occurrences = int(event_tmp[str_event])

                if occurrences > 1:
                    event[EventConsolidation.EVENT_OCCURRENCES] = \
                        str(occurrences)

            if date:
                event["date"] = date

            if log:
                event["log"] = log

        self.clear()


    def process(self):

        if not self.enable:
            return False

        section = EventConsolidation.CONSOLIDATION_SECTION
        restart_time = self._conf.getfloat(section, "time")

        if restart_time > EventConsolidation.MAX_TIME:
            restart_time = EventConsolidation.MAX_TIME

        current_time = time.time()

        if self.start_time + restart_time < current_time:
            if len(self) > 0:
                self.__process()

            # back to begining
            self.start_time = time.time()


    def __len__(self):
        return len(self.__event_list)



if __name__ == "__main__":

    Logger.set_verbose("debug")

    from Config import Conf

    conf = Conf()
    #conf.read(['../etc/agent/config.cfg'])
    conf.read(['/etc/ossim/agent/config.cfg'])

    from Event import Event

    event1 = Event()
    event1["src_ip"] = "127.0.0.1"
    event1["dst_ip"] = "127.0.0.1"
    event1["sensor"] = "127.0.0.1"
    event1["plugin_id"] = "6001"
    event1["plugin_sid"] = "1"
    event1["src_port"] = "22"
    event1["dst_port"] = "80"

    event2 = Event()
    event2["src_ip"] = event1["src_ip"]
    event2["dst_ip"] = event1["dst_ip"]
    event2["sensor"] = event1["sensor"]
    event2["plugin_id"] = event1["plugin_id"]
    event2["plugin_sid"] = event1["plugin_sid"]
    event2["src_port"] = event1["src_port"]
    event2["dst_port"] = "85"

    event3 = Event()
    event3["src_ip"] = event1["src_ip"]
    event3["dst_ip"] = event1["dst_ip"]
    event3["sensor"] = event1["sensor"]
    event3["plugin_id"] = event1["plugin_id"]
    event3["plugin_sid"] = "2"
    event3["src_port"] = event1["src_port"]
    event3["dst_port"] = event1["dst_port"]

    c = EventConsolidation(conf)

    # enable event consolidation in all plugins (testing purposes)
    c.enable = True
    conf.set(EventConsolidation.CONSOLIDATION_SECTION,
             "by_plugin", "1001-7001")

    c.insert(event2)
    c.insert(event2)
    c.insert(event2)
    c.insert(event2)
    c.insert(event2)
    c.insert(event1)
    c.insert(event1)
    c.insert(event2)
    c.insert(event2)
    c.insert(event2)
    c.insert(event2)
    c.insert(event2)
    c.insert(event3)
    c.insert(event2)
    c.insert(event2)
    c.insert(event2)

    time.sleep(5)
    c.process()


