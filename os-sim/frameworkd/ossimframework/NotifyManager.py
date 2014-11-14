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
import os
import time
import datetime
#
# LOCAL IMPORTS
#

from DBConstantNames import *
from Logger import Logger
from OssimConf import OssimConf
logger = Logger.logger

_CONF = OssimConf()
class NotifyManager(object):

    _notifyClassInstance = None
    def __new__(class_obj, *args, **kargs):
        if not class_obj._notifyClassInstance:
            class_obj._notifyClassInstance = super(NotifyManager, class_obj).__new__(class_obj, *args, **kargs)
             
        return class_obj._notifyClassInstance
    def __init__(self):        
        self.__openedFile = False
        self.__notifyFile = _CONF[VAR_NOTIFYMANAGER_FILE]
        self.__openNotifyFile() 
    def __openNotifyFile(self):
        try:
            self.__fd = open(self.__notifyFile,'a')
            os.chmod(self.__notifyFile,0644)
        except Exception,e:
            logger.error("Error opening notify file!")
    def warning(self, msg):
        #UTC: datetime.datetime.utcnow().isoformat(' ')
        #self.__fd.write("%s [FRAMEWORKD] -- WARNING -- %s\n" % (datetime.datetime.now().isoformat(' '),msg))
        self.__fd.write("%s [FRAMEWORKD] -- WARNING -- %s\n" % (datetime.datetime.utcnow().isoformat(' '),msg))
        self.__fd.flush()
    def error(self, msg):
        #self.__fd.write("%s [FRAMEWORKD] -- ERROR -- %s\n" % (datetime.datetime.now().isoformat(' '),msg))
        self.__fd.write("%s [FRAMEWORKD] -- ERROR -- %s\n" % (datetime.datetime.utcnow().isoformat(' '),msg))
        self.__fd.flush()
    def info(self,msg):
        #self.__fd.write("%s [FRAMEWORKD] -- INFO -- %s\n" % (datetime.datetime.now().isoformat(' '),msg))
        self.__fd.write("%s [FRAMEWORKD] -- INFO -- %s\n" % (datetime.datetime.utcnow().isoformat(' '),msg))
        self.__fd.flush()
   
if __name__ == "__main__":
    print "Test notify class"
    notifier = NotifyManager()
    notifier2 = NotifyManager()
    notifier.warning("warning message")
    notifier.info("info message")
    notifier.error("error mensaje")

    notifier2.warning("2 - warning message")
    notifier2.info("2 - info message")
    notifier2.error("2 - error message")
