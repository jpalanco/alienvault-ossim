#!/usr/bin/env python
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
import re
import socket
import commands
from time import sleep
#
# LOCAL IMPORTS
#

from Detector import Detector
from Event import Event, EventOS, EventMac, EventService, EventHids
from Logger import Logger
from Config import Plugin
#
# GLOBAL VARIABLES
#
logger = Logger.logger

class ParserWMI(Detector):
    '''
    WMI EventLog Parser, adapted from Database Parser

    TODO:
    TRANSLATIONS support
    Testing
    Make sure it works with more wmi stuff, not just windows log files
    Test with different languages / windows versions
    '''
    
    LAST_RECORD_FILE_TMP = "/etc/ossim/agent/wmi_%s_%s"
    VALID_SECTIONS = ["Application", "Security","System"]
    CMD_CHECK_SECTION = "wmic -U %s%%%s //%s \"SELECT LogfileName FROM Win32_NTEventLogFile where LogfileName='%s'\""
    # To get las record by time: "Select TimeWritten from Win32_NTLogEvent Where Logfile = 'Application' and TimeWritten >\"20110803103502.000000-000\""
    CMD_GET_LAST_RECORD = "wmic -U %s%%%s //%s  \"Select LogFile,RecordNumber from Win32_NTLogEvent Where Logfile = '%s'\" | head -n 3 | tail -n 1 | cut -f 2 -d \|"
    CMD = "wmic -U %s%%%s //%s %s"
    def __init__(self, conf, plugin, conn, hostname, username, password):
        self.__conf = conf
        self.__plugin = plugin
        self.__rules = []          # list of RuleMatch objects
        self.__conn = conn
        self.__hostname = hostname
        self.__username = username
        self.__password = password.strip()
        self.__section = self.__plugin.get("config", "section")
        self.__last_record_time = ""
        if self.__section == "":
            #search into the command to find the section
            rules = self.__plugin.rules()
            cmd_str = rules['cmd']['cmd']
            for sec in ParserWMI.VALID_SECTIONS:
                if cmd_str.find(sec)>=0:
                    self.__section = sec
                    logger.warning("section doesn't found in [config].Section deduced: %s " % self.__section)
                    break
            if self.__section == "":
                self.__section = "Security"
                logger.warning("section doesn't found in [config].It can't be deduced: Setting it to default value: %s" % self.__section)
        self.__pluginID = self.__plugin.get("DEFAULT", "plugin_id")
        self.__stop_processing = False
        self.__sectionExists = False
        Detector.__init__(self, conf, plugin, conn)

    def existsLogForSeciton(self):
        """
        Checks whether the specified section exists.        
        """
        returnValue = False
        """
        Example query output:
        CLASS: Win32_NTEventlogFile
        LogfileName|Name
        Application|C:\WINDOWS\system32\config\AppEvent.Evt
        Security|C:\WINDOWS\System32\config\SecEvent.Evt
        System|C:\WINDOWS\system32\config\SysEvent.Evt
        """
        self.__plugin.get("config", "section")
        query= ParserWMI.CMD_CHECK_SECTION % (self.__username, self.__password, self.__hostname,self.__section)
        status,output = commands.getstatusoutput(query)
        if status != 0 or output =="":
            logger.warning("An error occurred while trying to get logs from: %s - status:%s --output: %s, pluginid: %s" % (self.__hostname,status,output,self.__pluginID))
        else:
            returnValue = True
        return returnValue


    def getLastRecordTimeString(self):
        """
        Gets the last record time
            # To get las record by time: "Select TimeWritten from Win32_NTLogEvent Where Logfile = 'Application' and TimeWritten >\"20110803103502.000000-000\""
        """
        last_record_file = ParserWMI.LAST_RECORD_FILE_TMP % (self.__hostname,self.__section)
        
        self.__last_record_time = ""        
        if os.path.exists(last_record_file):
            file = open(last_record_file,'r')
            data = file.readline()
            logger.debug("Last record time: %s" % data.rstrip())
            self.__last_record_time = data.rstrip()
            file.close()
            if self.__last_record_time != "":
                return
        #cmd_run = "wmic -U %s%%%s //%s  \"Select TimeWritten from Win32_NTLogEvent Where Logfile = '%s'\"  | sort | head -n 1 | tr \"|\" \" \" |awk '{print$3;}'" % (self.__username, self.__password, self.__hostname, self.__section)
        cmd_run = "wmic -U %s%%%s //%s  \"Select TimeWritten from Win32_NTLogEvent Where Logfile = '%s'\" | grep %s | tr \"\|\" \" \" |awk '{print$3;}'  | sort -r | head -n 1" % (self.__username, self.__password, self.__hostname, self.__section,self.__section)
        status, output = commands.getstatusoutput(cmd_run)
        if status != 0 or output == "":
            logger.warning("[GET_LAST_RECORD] An error occurred while trying to get logs from: %s, section:%s, pluginid: %s" % (self.__hostname, self.__section, self.__pluginID))
            self.__last_record_time = ""
        else:
            logger.debug("[GET_LAST_RECORD] Last record time: %s" % output)
            self.__last_record_time = output
            self.updateLastRecordTimeString()
        
    def getLastRecord(self):
        """
        Gets the last record.
        """

        last_record = 0        
        query = ParserWMI.CMD_GET_LAST_RECORD % (self.__username, self.__password, self.__hostname, self.__section)
        status, output = commands.getstatusoutput(query)
        if status != 0:
            logger.warning("[GET_LAST_RECORD] An error occurred while trying to get logs from: %s, section:%s, pluginid: %s" % (self.__hostname,self.__section,self.__pluginID))
        elif output =="":
            last_record = 0
        else:
            last_record = output
        return last_record

    def updateLastRecordTimeString(self):
        last_record_file = ParserWMI.LAST_RECORD_FILE_TMP % (self.__hostname,self.__section)
        thefile = open(last_record_file,'w')
        thefile.write(self.__last_record_time + "\n")
        logger.debug( "Updating last_record_file : %s" % self.__last_record_time)
        thefile.close()

    def process(self):
        if self.__section not in ParserWMI.VALID_SECTIONS:
            logger.error("[%s] Specified section invalid: - %s - Bye!" % (self.__pluginID,self.__section))
            return
        #if not self.existsLogForSeciton():
        #    logger.error("[%s] Specified section: %s doesn't exist! Bye!" % (self.__pluginID,self.__section))
        #    return
        
        rules = self.__plugin.rules()
        sleep_time = self.__plugin.get("config", "sleep")

        cmd = rules['cmd']['cmd']
        real_cmd = rules['cmd']['cmd']
        if cmd == "":
            logger.error("[%s] Invalid command: %s .Bye!" % (cmd,self.__pluginID))
            return
        
        old_mode_active = True
        last_record = 0
        if cmd.find("OSS_TIME") > 0:
            old_mode_active = False
            self.getLastRecordTimeString()
        else:
            last_record = self.getLastRecord()
            
         
        cmd = cmd.replace("OSS_WMI_USER", self.__username)
        cmd = cmd.replace("OSS_WMI_PASS", self.__password)
        cmd = cmd.replace("OSS_WMI_HOST", self.__hostname)
        cmd = cmd.replace("OSS_COUNTER", str(last_record))
        cmd = cmd.replace("OSS_TIME", self.__last_record_time)
        regex = rules['cmd']['regexp']
        start_regexp = rules['cmd']['start_regexp']
        splitter = re.compile('(?<!\r)\n') # Split on \n unless it's preceded by \r
        cregexp = re.compile(regex)
        while not self.__stop_processing:
            """
            CLASS: Win32_NTLogEvent
            ComputerName|EventCode|Logfile|Message|RecordNumber|SourceName|TimeWritten|User
            <domainName>|15|Application|La inscripcion de certificados automotica para Sistema local no puede ponerse en contacto con el directorio activo (0x8007054b) El dominio especificado no existe o no se pudo establecer conexion con el.
            . La inscripcion no se efectuar.
            |68|AutoEnrollment|20110708052118.000000+120|(null)
            """
            logger.debug("[%s] Fetching WMI data, section:%s" % (self.__section,self.__pluginID))
            status,output = commands.getstatusoutput(cmd)
            
            if output != "" and len(output) > 1:
                data = splitter.split(output)
                cval_helper = 1
                for log in data:
                    log = log.encode('string_escape')
                    log = log.replace("\r\n"," ")
                    result = cregexp.search(log)
                    if result is None:
                        continue
                    else:
                        if cval_helper == 1:
                        # Only calculate cVal for first row since logs come out reversed
                            if old_mode_active:
                                last_record = str(int(result.groups()[4]))
                            else:
                                self.__last_record_time = result.groups()[6]
                            cval_helper = 0
                        groups =[]
                        for group in result.groups():
                            groups.append(group.decode('utf-8'))
                        self.generate(groups,log)
            else:
                logger.debug("[%s] Fetching WMI data, section:%s - No data" % (self.__section,self.__pluginID))
            
            if not self.__stop_processing:
                if not old_mode_active:
                    self.updateLastRecordTimeString()
                time.sleep(int(sleep_time))
            cmd = real_cmd
            cmd = cmd.replace("OSS_WMI_USER", self.__username)
            cmd = cmd.replace("OSS_WMI_PASS", self.__password)
            cmd = cmd.replace("OSS_WMI_HOST", self.__hostname)
            cmd = cmd.replace("OSS_COUNTER", str(last_record))            
            cmd = cmd.replace("OSS_TIME", self.__last_record_time)
        
        self.updateLastRecordTimeString()
        logger.info("Finish process")
            
    def generate(self, groups,log):
        event = Event()
        rules = self._plugin.rules()
        for key, value in rules['cmd'].iteritems():
            if key != "cmd" and key != "regexp" and key != "ref" and key != "start_regexp":
                #logger.info("Request")
                event[key] = self._plugin.get_replace_array_value(value.encode('utf-8'), groups)
                #event[key] = self.get_replace_value(value, groups)
                #self.plugin.get_replace_value
        if log and not event['log'] and "log" in event.EVENT_ATTRS:
            event['log'] = log.encode('utf-8')
        if event is not None:
            self.send_message(event)


    def stop(self):
        logger.info("Scheduling stop of ParserWMI.")
        self.__stop_processing = True
        try:
            self.join()
        except RuntimeError:
            logger.warning("Stopping thread that likely hasn't started.")
