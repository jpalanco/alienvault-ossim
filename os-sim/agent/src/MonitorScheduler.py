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
import threading
import time

#
# LOCAL IMPORTS
#
from EventList import EventList
from Logger import Logger
from Config import Conf, Plugin, Aliases, CommandLineOptions
import Config
#
# GLOBAL VARIABLES
#
logger = Logger.logger



class MonitorScheduler(threading.Thread):

    def __init__(self):
        self.monitor_list = EventList()
        threading.Thread.__init__(self)


    def new_monitor(self, type, plugin, watch_rule):
        if type in ('socket', 'unix_socket'):
            from MonitorSocket import MonitorSocket
            monitor = MonitorSocket(plugin, watch_rule)
            self.monitor_list.appendRule(monitor)

        elif type == 'database':
            from MonitorDatabase import MonitorDatabase
            monitor = MonitorDatabase(plugin, watch_rule)
            self.monitor_list.appendRule(monitor)

        elif type == ('command'):
            from MonitorCommand import MonitorCommand
            monitor = MonitorCommand(plugin, watch_rule)
            self.monitor_list.appendRule(monitor)

        elif type == ('http'):
            from MonitorHTTP import MonitorHTTP
            monitor = MonitorHTTP(plugin, watch_rule)
            self.monitor_list.appendRule(monitor)

        elif type == ('session'):
            from MonitorSession import MonitorSession
            monitor = MonitorSession(plugin, watch_rule)
            self.monitor_list.appendRule(monitor)
#
#        TODO: still not implemented
#
#        elif type in ('log', 'file'):
#            from MonitorFile import MonitorFile
#            monitor = MonitorFile(plugin, watch_rule)
#            self.monitor_list.appendRule(monitor)
#
         

    def run(self):
        logger.debug("Monitor Scheduler started")

        while 1:
            remove_monitors = []

            for monitor in self.monitor_list:
                # get monitor from monitor list
                # process watch-rule and remove from list

                # TODO: check if monitor is a Monitor instance
                # if isinstance(monitor, Monitor)

                if monitor.process():
                    remove_monitors.append(monitor)

            for m in remove_monitors:
                self.monitor_list.removeRule(m)

            # don't overload agent
            time.sleep(2)

# vim:ts=4 sts=4 tw=79 expandtab:
