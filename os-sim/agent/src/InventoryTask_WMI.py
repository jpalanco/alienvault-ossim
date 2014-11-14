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
import re
import sys, subprocess
from random import choice
from InventoryTask import InventoryTask
from Event import HostInfoEvent
from Logger import Logger


#
# GLOBAL VARIABLES
#
logger = Logger.logger


class WMI_TASK(InventoryTask):
    def __init__(self, task_name, task_params, task_period, task_reliability, task_enable, task_type,task_type_name):
        '''
        Constructor
        '''
        self._running = False
        self._wmicPath = '/usr/bin/wmic'
        #wmihost:192.168.2.121;wmiuser:wmiuser;wmipass:alien4ever
        self._pattern = re.compile("wmihost:(?P<wmihost>[^;]+);wmiuser:(?P<wmiuser>[^;]+);wmipass:(?P<wmipass>[^;]+)")
        values = self._pattern.match(task_params)
        self._win32User = ''
        self._win32Password = ''
        self._remoteIPAddress = ''
        if values:
            groupdict = values.groupdict()
            self._win32User = groupdict['wmiuser']
            self._win32Password = groupdict['wmipass']
            self._remoteIPAddress = groupdict['wmihost']
        else:
            logger.warning("Invalid wmi task")
            self._validTask = False

        #query ('query','parser funtion pointer)
        self._queries = [("Select * from Win32_UserAccount",self.getEventFromWin32_UserAccountQuery),]
        InventoryTask.__init__(self, task_name, task_params, task_period, task_reliability, task_enable, task_type,task_type_name)


    def getWMIData (self, wmlQuery):
        
        wmicCommand = '%s -U %s%%%s //%s "%s"' % (self._wmicPath, self._win32User, self._win32Password, self._remoteIPAddress, wmlQuery)
        wmicRun = subprocess.Popen(wmicCommand, shell=True, stdout=subprocess.PIPE).stdout
        wmicData = wmicRun.read().split('\n')
        lineCount = 0

        columnNames = []
        rowlist = []
        for wmicOutput in wmicData :
            if wmicOutput not in (None, '') :
                if lineCount == 1 : # Column names
                    columnNames = wmicOutput.split('|')
                elif lineCount > 1 : #Data
                    data = wmicOutput.split('|')
                    i = 0
                    if len(data) == len(columnNames):
                        tmpdic = {}
                        for value in data:
                            tmpdic[columnNames[i]] = value 
                            i = i + 1
                        rowlist.append(tmpdic)
                lineCount += 1
        return rowlist
    
    def getEventFromWin32_UserAccountQuery(self,data):
        '''
        Query parser:
        Example data - 
        {'Status': 'OK', 
        'Domain': 'LOCAL', 
        'Description': '', 
        'InstallDate': '(null)', 
        'PasswordChangeable': 'True', 
        'Disabled': 'False', 
        'Caption': 'LOCAL\\wmiuser',
        'Lockout': 'False', 
        'AccountType': '512', 
        'SID': 'S-1-5-21-2973305993-3644778160-3199891575-1003', 
        'LocalAccount': 'True', 
        'FullName': 'wmiuser', 
        'SIDType': '1', 
        'PasswordRequired': 'True', 
        'PasswordExpires': 'False', 
        'Name': 'wmiuser'}
        '''
        
        event = HostInfoEvent()
        try:
            event['ip'] = self._remoteIPAddress
            event['domain'] = data['Domain']
            event['username'] = data['Name']
        except:
            event = ''
        return event



    def doJob(self):
        yes_no = [1, 0]
        logger.info("Starting WMI collector ")
        for query in self._queries:
            wmiData = self.getWMIData (query[0])
            for row in wmiData:
                event = query[1](row)
                if event != '':
                    self.send_message(event)
        logger.info("End WMI collector")
    def get_running(self):
        self._running
