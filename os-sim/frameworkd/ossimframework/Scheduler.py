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
import os, re, sys, tempfile, threading, time

#
# LOCAL IMPORTS
#
from DBConstantNames import *
from Logger import Logger
from OssimDB import OssimDB
from OssimConf import OssimConf
import Util

#
# GLOBAL VARIABLES
#
logger = Logger.logger



class Scheduler(threading.Thread):

    def __init__(self):
        self.__conf = OssimConf()
        self.__db = OssimDB(self.__conf[VAR_DB_HOST],
                            self.__conf[VAR_DB_SCHEMA],
                            self.__conf[VAR_DB_USER],
                            self.__conf[VAR_DB_PASSWORD])
        self.__stored_id = 0
        self.__stored_num = 0
        self.__header_id = 0
        threading.Thread.__init__(self)


    def run(self):

        pass
# vim:ts=4 sts=4 tw=79 expandtab:
