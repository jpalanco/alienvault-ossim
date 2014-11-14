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
import time
import json

#
# LOCAL IMPORTS
#
from Logger import Logger

#
# GLOBAL VARIABLES
#
logger = Logger.logger



class Stats:

    dates = { 'startup': '', 'shutdown': '' }
    events = { 'total': 0, 'detector': 0, 'monitor': 0, 'plugins': {} }
    consolidation = {'total': 0, 'consolidated': 0}
    watchdog = { 'total': 0 }
    server_reconnects = {}
    file = ''
    init_time = 0

    json_file = '/var/log/alienvault/agent/agent_stats_json.log'

    def set_file(file):
        Stats.file = file

    set_file = staticmethod(set_file)


    def startup():
        Stats.dates['startup'] = time.ctime(time.time())

    startup = staticmethod(startup)


    def shutdown():
        Stats.dates['shutdown'] = time.ctime(time.time())

    shutdown = staticmethod(shutdown)


    def new_event(event):
        if not event or event =="":
            return

        if not Stats.events['plugins'].has_key(event['plugin_id']):
            Stats.events['plugins'][event['plugin_id']] = {'n_events': 0, 'last': time.time()}
        if Stats.events['total'] == 0 :
            Stats.init_time = time.time()
        # total events
        Stats.events['total'] += 1

        # detector|monitor events
        for _type in [ 'detector', 'monitor' ]:
            if event['type'] == _type:
                Stats.events[_type] += 1
        # events by plugin_id
        Stats.events['plugins'][event['plugin_id']]['n_events'] += 1

        # Update 'last' time every 100 events received.
        if (Stats.events['plugins'][event['plugin_id']]['last'] % 100) == 0:
            Stats.events['plugins'][event['plugin_id']]['last'] = time.time()

    new_event = staticmethod(new_event)


    def watchdog_restart(plugin):
        Stats.watchdog['total'] += 1

        if not Stats.watchdog.has_key(plugin):
            Stats.watchdog[plugin] = 0

        Stats.watchdog[plugin] += 1

    watchdog_restart = staticmethod(watchdog_restart)

    def add_server(server_ip):
        Stats.server_reconnects[server_ip] = 0
    add_server = staticmethod(add_server)

    def server_reconnect(server_ip):
        if Stats.server_reconnects.has_key(server_ip):
            Stats.server_reconnects[server_ip] += 1
        else:
            Stats.server_reconnects[server_ip] = 1


    server_reconnect = staticmethod(server_reconnect)


    def log_stats():
        logger.debug("Agent was started at: %s" % (Stats.dates['startup']))
        current_time = time.time()
        eps = Stats.events['total'] / (current_time - Stats.init_time)
        logger.info("Total events captured: %d - eps:%0.2f " % (Stats.events['total'],eps))        
        if Stats.watchdog['total'] > 0:
            logger.warning("Apps restarted by watchdog: %d" % \
                (Stats.watchdog['total']))

        for server_ip, reconnects in Stats.server_reconnects.items():
            logger.debug("Server %s reconnections attempts: %d" % (server_ip, reconnects))

    log_stats = staticmethod(log_stats)


    def __summary():
        summary = "\n-------------------------\n"
        summary += " Agent execution summary:\n"

        # startup and shutdown dates
        summary += "  + Startup date: %s\n" % (Stats.dates['startup'])
        if Stats.dates['shutdown']:
            summary += "  + Shutdown date: %s\n" % (Stats.dates['shutdown'])

        # events
        summary += "  + Total events: %d" % (Stats.events['total'])
        summary += " (Detector: %d, Monitor: %d)\n" % \
            (Stats.events['detector'], Stats.events['monitor'])

        for plugin_id in Stats.events['plugins'].keys():
            n_events = Stats.events['plugins'][plugin_id]['n_events']
            last = Stats.events['plugins'][plugin_id]['last']

            if not plugin_id:
                if n_events:
                    summary += "    - plugin_id unkown: %d\n" % (int(n_events))

            elif plugin_id.isdigit():
                summary += "    - plugin_id %s: %d\n" % (plugin_id, n_events)

        # consolidation
        summary += "  + Events consolidated: %d (%d processed)\n" % \
            (Stats.consolidation['consolidated'], Stats.consolidation['total'])

        # wathdog restarts
        summary += "  + Apps restarted by watchdog: %d\n" % \
            (Stats.watchdog['total'])

        for process, n_restarts in Stats.watchdog.iteritems():
            if process != 'total':
                summary += "    - process %s: %d\n" % (process, n_restarts)

        # server reconnets
        for server_ip, reconnects in Stats.server_reconnects.items():
            summary += "  + Server %s reconnections attempts: %d\n" % (server_ip, reconnects)
        summary += "-------------------------"

        logger.info(summary)
        return summary

    __summary = staticmethod(__summary)


    def stats():
        summary = Stats.__summary()
        if not Stats.file:
            logger.error("There is no [log]->stats entry at configuration")
            return

        dir = Stats.file.rstrip(os.path.basename(Stats.file))
        if not os.path.isdir(dir):
            try:
                os.makedirs(dir, 0755)

            except OSError, e:
                logger.error(
                    "Can not create stats directory (%s): %s" % (dir, e))
                return

        try:
            fd = open(Stats.file, 'a+')

        except IOError, e:
            logger.warning("Error opening stats file: " + str(e))

        else:
            fd.write(summary)
            fd.write ("\n\n");
            fd.flush()
            fd.close()
            logger.info("Agent statistics written in %s" % (Stats.file))

    stats = staticmethod(stats)

    @staticmethod
    def get_client_stats (signum, frame):
        pass

    @staticmethod
    def get_plugin_stats (signum, frame):
        logger.info ("Storing plugin stats...")
        json_file_desc = open (Stats.json_file, 'w')
        json_file_desc.write (json.dumps(Stats.events['plugins']))
        json_file_desc.close ()

    @staticmethod
    def get_all_stats (signum, frame):
        pass
