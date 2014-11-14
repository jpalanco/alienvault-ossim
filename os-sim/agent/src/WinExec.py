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

#
# GLOBAL IMPORTS
#
import os
import re
import subprocess
import commands
import time
from Logger import Logger
#
# GLOBAL VARIABLES
#
logger = Logger.logger


class WinExeC(object):
    """winexe class wrapper"""
    def __init__(self, auth_file, config_file,dst_host):
        """
        @param auth_file: authentication file
        @param config_file: configuration file
        @param dst_host: Destination host
        """
        self.__authentication_file = auth_file
        self.__configuration_file = config_file
        self.__dst_host = dst_host
        self.__environmentRegex = re.compile("^(?P<option>[^\[\]:=\s][^\[\]:=]*)\s*(?P<vi>[:=])\s*(?P<value>.*)$")
        self.__environmentVariables = {}


    def run_command(self,command,send_key=False,sleep_time=3):
        """Runs the given command
        @param command It's the command to run
        @param send_key Indicates whether the given command waits for a hint
        @param sleep_time Time until the hint is sent"""
        cmd = "winexe -A %s -s %s //%s \"cmd /c %s\"" % (self.__authentication_file,self.__configuration_file,self.__dst_host,command)
        try:
            logger.info(cmd)
            pipe = subprocess.Popen(cmd,shell=True,stdout=subprocess.PIPE,stdin=subprocess.PIPE)
            if send_key:
                time.sleep(sleep_time)
                pipe.communicate('\n')
        except Exception,e:
            logger.error("Error retrieving the environment variables....%s" % str(e))
            return False
        return True


    def get_environment_variable(self, variablename):
        """Returns the given environment variable value"""
        cmd = "winexe -A %s -s %s //%s \"cmd /c set\"" % (self.__authentication_file,self.__configuration_file,self.__dst_host)
        try:
            #data = subprocess.check_output(cmd,shell=True) # Not supported in our version
            status,data =commands.getstatusoutput(cmd)
            self.__environmentVariables.clear()
            for line in data.split('\n'):
                regexdata = self.__environmentRegex.match(line)
                if regexdata:
                    dic = regexdata.groupdict()
                    if dic.has_key('option') and dic.has_key('value'):
                        self.__environmentVariables[dic['option']]=dic['value'].rstrip('\r')
        except Exception,e:
            logger.error("Error retrieving the environment variables....%s" % str(e))
        if self.__environmentVariables.has_key(variablename):
            return self.__environmentVariables[variablename]
        return None


    def get_working_unit(self):
        """Returns the current working unit.
        """
        work_unit = self.get_environment_variable('SystemDrive')
        # Work unit should be something like C:/
        # Remove the :/
        if work_unit:
            splitdata = work_unit.split(':')
            # After doing the split -> work_unit = ['c', '/']
            if len(splitdata)==2:
                work_unit = splitdata[0]
            elif len(splitdata) == 1:
                work_unit = splitdata[0]
        return work_unit

if __name__ == '__main__':
    print "test ossec"
    cmd = WinExeC('/mnt/datos/tickets/deployOssec/deploy.keys','/mnt/datos/tickets/deployOssec/deploy.conf','192.168.2.142')
    print cmd.get_environment_variable('ProgramFiles')
    print cmd.run_command('ipconfig')
