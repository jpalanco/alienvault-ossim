# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2013 AlienVault
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

import time
import os
import json

from api.lib.monitors.monitor import Monitor, MonitorTypes
from ansiblemethods.server.server import get_server_stats

import celery.utils.log
logger = celery.utils.log.get_logger("celery")


class MonitorServerEPSStats(Monitor):
    """
    Monitor correlation EPS in the current server.
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.SERVER_EPS_STATS)
        self.message = 'Server EPS stats Monitor Enabled'

        self.__server_ip = '127.0.0.1'
        self.__server_port = '40009'
        self.__stats_dir = '/var/alienvault/server/stats'
        self.__eps_log_file = '%s/%s' % (self.__stats_dir, 'eps.log')
        self.__max_samples = 168

    def start(self):
        """
        Starts the monitor activity

        :return: True on success, False otherwise
        """
        eps_data = []

        if not os.path.isdir(self.__stats_dir):
            os.mkdir(self.__stats_dir, 0770)
        if os.path.isfile(self.__eps_log_file):
            with open(self.__eps_log_file, 'r') as f:
                try:
                    eps_data = json.loads(f.read())
                    eps_data = filter(lambda x: type(x) == int, eps_data[-self.__max_samples:])
                except:
                    eps_data = []

        args = {'server_ip': self.__server_ip, 'server_port': self.__server_port, 'server_stats': 'yes'}
        ansible_output = get_server_stats(self.__server_ip, args)
        if ansible_output['dark'] != {}:
            logger.error("Error querying server EPS stats: %s" % ansible_output['dark'])
            return False

        try:
            # Get correlation EPS only.
            eps = int(ansible_output['contacted'][self.__server_ip]['data']['sim_eps'])
        except KeyError:
            logger.error("Cannot get server EPS number")
            return False
        except ValueError:
            logger.error("Server EPS value is not an integer")
            return False

        eps_data = eps_data[-(self.__max_samples - 1):] + [eps]
        with open(self.__eps_log_file, 'w') as f:
            try:
                os.chmod(self.__eps_log_file, 0644)
            except Exception, e:
                logger.error("Cannot change file permissions: %s" % str(e))
            try:
                f.write(json.dumps(eps_data, indent=4, separators=(',', ': ')))
            except Exception, e:
                logger.error("Cannot write server EPS stats to file: %s" % str(e))

        return True
