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

"""@package BackupManager
This module designed to run all the mysql backups operations
"""
import socket
import threading
import random
from  time import time, sleep
import os
import sys
import signal
import glob
import re, string, struct
import Queue
import codecs
from base64 import b64encode
import pickle
from threading import Lock
from datetime import datetime, timedelta,date
import commands
import gzip

import MySQLdb
import MySQLdb.cursors

#
#    LOCAL IMPORTS
#
import Util
from DBConstantNames import *
from OssimDB import OssimDB
from OssimConf import OssimConf, OssimMiniConf
from ConfigParser import ConfigParser
from Logger import Logger


logger = Logger.logger
_CONF = OssimConf()



class AlarmCleaner(threading.Thread):
    """Manage the periodic backups.
    """
    UPDATE_ALARM_DAY = '/etc/ossim/framework/alarmcleaner.fkm'
    
    def __init__(self):
        """Default constructor.
        """
        threading.Thread.__init__(self)
        self.__myDB = OssimDB(_CONF[VAR_DB_HOST],
                              _CONF[VAR_DB_SCHEMA],
                              _CONF[VAR_DB_USER],
                              _CONF[VAR_DB_PASSWORD])
        self.__myDB_connected = False
        self.__keepWorking = True
        self.__lastUpdate = None
        #self.__mutex = Lock()
        self.__stopE = threading.Event()
        self.__stopE.clear()




    def checkDiskUsage(self):
        """Check max disk usage.
        """
        mega = 1024 * 1024
        disk_state = os.statvfs('/var/ossim/')
        #free space in megabytes.
        capacity = float((disk_state.f_bsize * disk_state.f_blocks)/mega)
        free_space = float( (disk_state.f_bsize * disk_state.f_bavail) / mega)
        percentage_free_space = (free_space * 100)/capacity
        min_free_space_allowed  = 10
        try:
            min_free_space_allowed = 100 - int(_CONF[VAR_BACKUP_MAX_DISKUSAGE])
        except Exception,e:
            logger.error("Error when calculating free disk space: %s" % str(e))

        logger.debug("Min free space allowed: %s - current free space: %s" %(min_free_space_allowed,percentage_free_space))
        if percentage_free_space < min_free_space_allowed:
            return False
        return True




    def __run_job(self):
        """Run the backup job. 
        """
        #Check the disk space
        if not self.checkDiskUsage():
            logger.warning("[ALERT DISK USAGE] Can not run backups due to low free disk space")
            return



    def run(self):
        """ Entry point for the thread.
        """
        while not self.__stopE.isSet():
            self.__run_job()
            sleep(30)

    def stop(self):
        """Stop the current thread execution
        """
        self.__stopE.set()


if __name__ == "__main__":
    bkm = AlarmCleaner()
    bkm.start()
    try:
        while True:
            sleep(1)
    except KeyboardInterrupt:
        print "Ctrl-c received! Stopping BackupManager..."
        bkm.stop()
        bkm.join(1)
        sys.exit(0)
    
