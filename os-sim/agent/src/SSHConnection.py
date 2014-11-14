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

import base64
import getpass
import os
import socket
import sys
import traceback
#import paramiko
import re
import logging
from Logger import Logger
logger = Logger.logger
#gather-package-list.nasl

class SSHConnection:
    def __init__(self, host, port, user, password):
        self.host = host
        self.user = user
        self.password = password
        self.port = port
        self.connected = False
#        from paramiko import rng_posix
#        try:
#            close(rng_device)
#        except Exception,e:
#            logger.info("kk")
#        rng_device = rng_posix.open_rng_device()
#        self.client = paramiko.SSHClient()
#        paramiko.util.log_to_file('/var/log/ossim/ssh-remote.log',level=logging.DEBUG)
#        self.client.set_missing_host_key_policy(paramiko.AutoAddPolicy())


    def connect(self):
        try:
            import paramiko
            try:
                close(rng_device)
            except Exception,e:
                pass
            from paramiko import rng_posix
            rng_device = rng_posix.open_rng_device()
            self.client = paramiko.SSHClient()
            self.client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            paramiko.util.log_to_file('/var/log/ossim/ssh-remote.log',level=logging.DEBUG)
            self.client.connect(hostname=self.host, port=self.port, username=self.user, password=self.password, look_for_keys=False)
            logger.info("Connected remotely to %s " % self.host)
            self.connected = True
        except Exception, e:
            logger.error("Error conecting to %s->%s" % (self.host, str(e)))
            self.client.close()
            self.connected = False
        return self.connected
    def closeConnection(self):
        if self.client:
            try:
                self.client.close()
            except Exception,e:
                logger.warning("SSHRemote Close error: %s" % str(e))
# vim:ts=4 sts=4 tw=79 expandtab:
