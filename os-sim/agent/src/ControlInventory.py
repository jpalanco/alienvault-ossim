# -*- coding: utf-8 -*-
#!/usr/bin/python
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
import datetime, os, re, threading, time
from threading import Lock
#
# LOCAL IMPORTS
#
import ControlError
import ControlUtil
from Logger import Logger
from InventoryTask_NMAP import NMAP_TASK
from InventoryTask_WMI import WMI_TASK
from InventoryTask_LDAP import LDAP_TASK
from InventoryTask_OCS import OCS_TASK
from InventoryTask_Nagios import NAGIOS_TASK
import Utils

#
# GLOBAL VARIABLES
#
logger = Logger.logger
class InventoryManager(object):


    def __init__(self,fmkip,fmkport):
        self.__inventory = None
        self._fmkip = fmkip
        self._fmkport = fmkport
        if self.__inventory == None:            
            self.__inventory = DoInventory()
            self.__inventory.start()
            self.__taskRegex = re.compile("task_name=(?P<task_name>[^,]+),task_type=(?P<task_type>[^,]+),task_type_name=(?P<task_type_name>[^,]+),task_params=(?P<task_params>.*),task_period=(?P<task_period>[^,]+),task_reliability=(?P<task_reliability>[^,]+),task_enable=(?P<task_enable>[^,]+)")
    
    def process(self, data, base_response):
        logger.info("Inventory Manager: Processing: %s" % data)
        response = []
        action = Utils.get_var("action=\"([A-Za-z_]+)\"", data)
        if action == "refresh_inventory_task":
            tmp_tasks = Utils.get_var("inventory_task_list=\{(?P<task_list>.*)\}", data)
            task_list = tmp_tasks.split('|')
            inventory_task_list = []
            for task in task_list:
                task_values = self.__taskRegex.match(task)
                if task_values:
                    groupdict = task_values.groupdict()
                    if groupdict['task_type_name'].lower() =='nmap':
                        inventory_task_list.append(NMAP_TASK(groupdict['task_name'], \
                                                             groupdict['task_params'], \
                                                             groupdict['task_period'], \
                                                             groupdict['task_reliability'], \
                                                             groupdict['task_enable'], \
                                                             groupdict['task_type'],\
                                                             groupdict['task_type_name']))
                    elif groupdict['task_type_name'].lower() =='wmi':
                        inventory_task_list.append(WMI_TASK(groupdict['task_name'], \
                                                             groupdict['task_params'], \
                                                             groupdict['task_period'], \
                                                             groupdict['task_reliability'], \
                                                             groupdict['task_enable'], \
                                                             groupdict['task_type'],\
                                                             groupdict['task_type_name']))
                    elif groupdict['task_type_name'].lower() =='ldap':
                        inventory_task_list.append(LDAP_TASK(groupdict['task_name'], \
                                                             groupdict['task_params'], \
                                                             groupdict['task_period'], \
                                                             groupdict['task_reliability'], \
                                                             groupdict['task_enable'], \
                                                             groupdict['task_type'],\
                                                             groupdict['task_type_name']))
                    elif groupdict['task_type_name'].lower() =='ocs':
                        inventory_task_list.append(OCS_TASK(groupdict['task_name'], \
                                                             groupdict['task_params'], \
                                                             groupdict['task_period'], \
                                                             groupdict['task_reliability'], \
                                                             groupdict['task_enable'], \
                                                             groupdict['task_type'],\
                                                             groupdict['task_type_name'],\
                                                             self._fmkip,\
                                                             self._fmkport))
                    elif groupdict['task_type_name'].lower() =='nagios':
                        inventory_task_list.append(NAGIOS_TASK(groupdict['task_name'], \
                                                             groupdict['task_params'], \
                                                             groupdict['task_period'], \
                                                             groupdict['task_reliability'], \
                                                             groupdict['task_enable'], \
                                                             groupdict['task_type'],\
                                                             groupdict['task_type_name'],\
                                                             self._fmkip,\
                                                             self._fmkport))
                    else:
                        logger.warning("task not implemented:%s" % groupdict['task_type_name'])
                else:
                    logger.warning("Invalid task: %s" % task)
            self.__inventory.set_tasks(inventory_task_list)
            response.append(base_response + ' status="%d" %s ackend\n' % (0, ControlError.get(0)))
        else:
            response.append(base_response + ' %s ackend\n' % ControlError.get(2002))
        return response

class DoInventory (threading.Thread):
    
    
    def __init__(self):
        threading.Thread.__init__(self)
        self._keepWorking = True
        self._taskListMutex = Lock()
        self._taskList = []
        self._updatingTaskList = threading.Event()
        self._workingEvent = threading.Event()
    def set_tasks(self, tasklist):
        '''
        Set the inventory task list.
        '''
        self._taskListMutex.acquire()
        while self._workingEvent.isSet():
            logger.info("Waiting to finish current work before updating the task list")
            time.sleep(1)
        logger.info("Updating task list..")
        self._updatingTaskList.set()
        del self._taskList[:]
        self._taskList = tasklist
        self._updatingTaskList.clear()
        self._taskListMutex.release()

    def _run_task(self, task):

        if not task.isEnable() or not task.isValid():
            return
        current_time = time.time()
        period = task.getPeriod()
        last_run = task.getLastRun()
        elapsed_time = current_time - last_run
        if elapsed_time>period and not task.get_running():
            logger.info("Running task...%s - Period:%s" % (task.getTaskName(),task.getPeriod()))
            task.doJob()
            task.updateLastRun()


    def run(self):

        while self._keepWorking:
            if not self._updatingTaskList.isSet():
                self._workingEvent.set()
                for task in self._taskList:
                    #logger.info("Try running task: %s" % task.getTaskName())
                    self._run_task(task)
                self._workingEvent.clear()
            else:
                logger.info("Waiting to update..")
            time.sleep(10)
