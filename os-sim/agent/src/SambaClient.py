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
from Logger import Logger
#
# GLOBAL VARIABLES
#
logger = Logger.logger


class SambaClient(object):
    """Simple smbclient class wrapper"""
    def __init__(self, auth_file, config_file, dst_host, work_unit='c'):
        """
        @param auth_file: authentication file
        @param config_file: configuration file
        @param dst_host: Destination host
        @param work_unit: Working hard drive unit
        """
        self.__authentication_file = auth_file
        self.__configuration_file = config_file
        self.__dst_host = dst_host
        self.__work_unit = work_unit


    def put_file(self,localdir, localfile,dstdir,dstfile):
        """Copy the local file to the destination file.
        @param localfile: The local file to copy
        @param dstfile: The destination path
        """
        cmd = "smbclient //%s/%s$ -A %s -s %s -c \"lcd %s; cd \\\"%s\\\"; put %s %s \"" % \
                        (self.__dst_host, self.__work_unit, self.__authentication_file, 
                         self.__configuration_file,localdir,dstdir.encode('string-escape'),localfile, dstfile)
        result = True
        try:
            logger.info(cmd)
            #data = subprocess.check_output(cmd, shell=True) #Not supported in our python installation
            status,data = commands.getstatusoutput(cmd)
        except subprocess.CalledProcessError, e:
            result = False
            logger.error("Error running the put command: %s" % str(e))
        except Exception, e:
            result = False
            logger.error("Error retrieving the environment variables....%s" % str(e))
        return result


    def remove_file(self, filename):
        """Removes the given filename on the remote system.
        @param filename: file to remove"""
        cmd = "smbclient //%s/%s$ -A %s -s %s -c \"rm %s \"" % (self.__dst_host, self.__work_unit, self.__authentication_file, self.__configuration_file, filename)
        result = True
        try:
            #data = subprocess.check_output(cmd, shell=True)
            status,data = commands.getstatusoutput(cmd)  #Not supported in our python installation
        except subprocess.CalledProcessError, e:
            result = False
            logger.error("Error running the remove command: %s" % str(e))
        except Exception, e:
            result = False
            logger.error("Error retrieving the environment variables....%s" % str(e))
        return result


    def get_file(self, remote_file, local_file):
        """Retrieves the given remote file and copies it to 'local_file'"""
        cmd = "smbclient //%s/%s$ -A %s -s %s -c \"get %s %s\"" % (self.__dst_host, self.__work_unit, self.__authentication_file, self.__configuration_file, remote_file, local_file)
        result = True
        try:
            #data = subprocess.check_output(cmd, shell=True)
            status,data = commands.getstatusoutput(cmd)  #Not supported in our python installation
        except subprocess.CalledProcessError, e:
            result = False
            logger.error("Error running the remove command: %s" % str(e))
        except Exception, e:
            logger.error("Error retrieving the environment variables....%s" % str(e))
            result = False
        return False

if __name__ == '__main__':
    print "test ossec"
    cmd = SambaClient('/mnt/datos/tickets/deployOssec/deploy.keys', '/mnt/datos/tickets/deployOssec/deploy.conf', '192.168.2.108')
    print cmd.put_file('/etc/ossim/agent/','aliases.cfg', '','aliases.cfg')
    print cmd.get_file('aliases.cfg', '/tmp/aliases.cfg')
    print cmd.remove_file('aliases.cfg')


