# -*- coding: utf-8 -*-
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

import time

from Logger import Logger
from Output import Output
#
# GLOBAL VARIABLES
#
logger = Logger.logger
class InventoryTask(object):

    def __init__(self, task_name, task_params, task_period, task_reliability, task_enable, task_type,task_type_name):
        self._enable = False
        self._validTask = True
        if task_enable.lower() in ['1', 'yes', 'true']:
            self._enable = True
        self._taskname = task_name
        self._task_params = task_params
        self._task_period = 0
        self._task_type = task_type
        self._lastRun = 0
        try:
            self._task_period = int(task_period)
        except ValueError:
            logger.warning("Invalid task period: %s" % task_period)
            self._validTask = False
        self._task_reliability = 0
        try:
            self._task_reliability = int(task_reliability)
        except ValueError:
            logger.warning("Invalid task reliability: %s" % task_reliability)
            self._validTask = False
        self._task_type_name = 'none'
        if task_type_name.lower() in ['nmap', 'wmi', 'ocs', 'ldap', 'nagios', 'nessus', 'nedi']:
            self._task_type_name = task_type_name
        else:
            self._validTask = False
    def getTaskParams(self):
        return self._task_params
    def getTaskName(self):
        return self._taskname
    def isEnable(self):
        return self._enable
    def isValid(self):
        return self._validTask
    def getLastRun(self):
        return self._lastRun
    def getPeriod(self):
        return self._task_period
    def updateLastRun(self):
        self._lastRun = time.time()
    def doJob(self):
        '''
        '''
        pass
    def _setdefaults(self,event):
        event['inventory_source'] = self._task_type#self._task_reliability
        return event
    def send_message(self,data):
        event = self._setdefaults(data)
        Output.event(event)
