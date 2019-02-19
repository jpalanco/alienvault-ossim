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
import os, sys, time, signal
from optparse import OptionParser
import subprocess as sub
import atexit
import re
import Queue
import uuid
import stat
import pwd
import ConfigParser
from datetime import datetime

#
# LOCAL IMPORTS
#

from NagiosMkLiveManager import NagiosMkLiveManager
from OssimConf import OssimConf
from OssimDB import OssimDB
from DBConstantNames import *
from BackupManager import BackupManager
from Listener import Listener
from Logger import Logger

logger = Logger.logger


class Framework:
    def __init__(self):
        """
        Default Constructor
        """
        self.__classes = [
            "Scheduler",
            "DoNagios",
            "NagiosMkLiveManager",
            "BackupManager",
        ]
        self.__encryptionKey = ''
        self.__options = None
        self.__conf = None
        self.__listener = None

    def __parse_options(self):
        """
            Parse the command line options.
        """
        usage = "%prog [-d] [-v] [-s delay] [-c config_file]"
        parser = OptionParser(usage=usage)
        parser.add_option("-v", "--verbose", dest="verbose", action="count",
                          help="make lots of noise")
        parser.add_option("-L", "--log", dest="log", action="store",
                          help="user FILE for logging purposes", metavar="FILE")
        parser.add_option("-d", "--daemon", dest="daemon", action="store_true",
                          help="Run script in daemon mode")
        parser.add_option("-s", "--sleep", dest="sleep", action="store",
                          help="delay between iterations (seconds)", metavar="DELAY")
        parser.add_option("-c", "--config", dest="config_file", action="store",
                          help="read config from FILE", metavar="FILE")
        parser.add_option("-p", "--port", dest="listener_port", action="store",
                          help="use PORT as listener port", metavar="PORT")
        parser.add_option("-l", "--listen-address", dest="listener_address", action="store",
                          help="use ADDRESS as listener IP address", metavar="ADDRESS")

        (options, args) = parser.parse_args()

        if options.verbose and options.daemon:
            parser.error("incompatible options -v -d")

        return options

    def __daemonize__(self):
        """
           Daemon method by Sander Marechal at: http://www.jejik.com/articles/2007/02/a_simple_unix_linux_daemon_in_python/
           Do the UNIX double-fork magic, see Stevens' "Advanced
           Programming in the UNIX Environment" for details (ISBN 0201563177)
           http://www.erlenstar.demon.co.uk/unix/faq_2.html#SEC16
        """
        signal.signal(signal.SIGTERM, self.stop)
        try:
            pid = os.fork()
            if pid > 0:
                # exit first parent
                sys.exit(0)
        except OSError, e:
            sys.stderr.write("fork #1 failed: %d (%s)\n" % (e.errno, e.strerror))
            sys.exit(1)
        # decouple from the parent environment
        os.chdir("/")
        os.setsid()
        os.umask(0)

        # try second fork
        try:
            pid = os.fork()
            if pid > 0:
                # exit from the second parent.
                sys.exit(0)
        except OSError, e:
            sys.stderr.write("fork #2 failed: %d (%s)\n" % (e.errno, e.strerror))
            sys.exit(1)
        # redirect standard file descriptors
        sys.stdout.flush()
        sys.stderr.flush()

        si = file('/dev/null', 'r')
        so = file('/dev/null', 'a+')
        se = file('/dev/null', 'a+', 0)

        os.dup2(si.fileno(), sys.stdin.fileno())
        os.dup2(so.fileno(), sys.stdout.fileno())
        os.dup2(se.fileno(), sys.stderr.fileno())

        # write the pid ile
        atexit.register(self.delpid)
        pid = str(os.getpid())
        file(self.pidfile, 'w+').write("%s\n" % pid)
        os.chmod(self.pidfile, 0644)

    def delpid(self):
        """
        Delete the pid file 
        """
        os.remove(self.pidfile)

    def stop(self, sig, params):
        """
        Stop the frameworkd process
        """
        try:
            pf = file(self.pidfile, 'r')
            pid = int(pf.read().strip())
            pf.close()
        except IOError:
            pid = None
        if not pid:
            message = "pidfile %s does not exist. Daemon not running? or not in daemon mode?\n"
            sys.stderr.write(message % self.pidfile)
            return

        # try to kill the daemon process.
        try:
            while 1:
                os.kill(pid, signal.SIGTERM)
                time.sleep(0.1)
        except OSError, err:
            err = str(err)
            if err.find("No such process") > 0:
                if os.path.exists(self.pidfile):
                    os.remove(self.pidfile)
            else:
                print str(err)
                sys.exit(1)

    def waitforever(self):
        """Wait for a Control-C and kill all threads"""

        while 1:
            try:
                time.sleep(1)
                if not self.__listener.isAlive():
                    logger.info("Listener in down... exiting...")
                    pid = os.getpid()
                    os.kill(pid, signal.SIGKILL)
                    break
            except KeyboardInterrupt:
                pid = os.getpid()
                os.kill(pid, signal.SIGKILL)

    def __init_log(self, daemon_mode):
        """
        Initializes the logger.
        """

        verbose = "info"
        Logger.add_file_handler('%s/frameworkd.log' % self.__conf[VAR_LOG_DIR])
        Logger.add_error_file_handler('%s/frameworkd_error.log' % self.__conf[VAR_LOG_DIR])

        if daemon_mode:
            Logger.remove_console_handler()
        if self._options.verbose is not None:
            # -v or -vv command line argument
            #  -v -> self.options.verbose = 1
            # -vv -> self.options.verbose = 2
            for i in range(self._options.verbose):
                verbose = Logger.next_verbose_level(verbose)
            Logger.set_verbose(verbose)
        try:
            os.chmod('%s/frameworkd.log' % self.__conf[VAR_LOG_DIR], 0644)
            os.chmod('%s/frameworkd_error.log' % self.__conf[VAR_LOG_DIR], 0644)
        except Exception, e:
            print str(e)

    def checkEncryptionKey(self):
        """Check for the encrytion key file.
        """
        # 1 -check if file exist or if the key is in the database.
        mydb = OssimDB(self.__conf[VAR_DB_HOST], self.__conf[VAR_DB_SCHEMA],
                       self.__conf[VAR_DB_USER], self.__conf[VAR_DB_PASSWORD])
        mydb.connect()
        select_query = "select value from config where conf=\"encryption_key\";"
        insert_query = "REPLACE INTO config VALUES ('encryption_key', %s)"
        data = mydb.exec_query(select_query)
        keyFilePath = self.__conf[VAR_KEY_FILE]
        if keyFilePath == "" or keyFilePath is None:
            logger.error("Frameworkd can't start. Please check the value of %s in the config table" % VAR_KEY_FILE)
            sys.exit(2)

        if not os.path.isfile(self.__conf[VAR_KEY_FILE]) or data is None or data == "" or len(data) == 0:
            logger.info(
                "Encryption key file doesn't exist... making it at .. %s and save it to db" % self.__conf[VAR_KEY_FILE])
            output = sub.Popen('/usr/bin/alienvault-system-id', stdout=sub.PIPE)
            s_uuid = output.stdout.read().upper()
            reg_str = "(?P<uuid>[a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{12})"
            pt = re.compile(reg_str, re.M)
            match_data = pt.search(s_uuid)
            key = ""
            extra_data = ""
            d = datetime.today()
            if match_data is not None:
                key = match_data.group('uuid')
                extra_data = "#Generated using alienvault-system-id\n"
            else:
                logger.error(
                    "I can't obtain system uuid. Generating a random uuid. Please do backup your encrytion key file: %s" %
                    self.__conf[VAR_KEY_FILE])
                extra_data = "#Generated using random uuid on %s\n" % d.isoformat(' ')
                key = uuid.uuid4()
            newfile = open(self.__conf[VAR_KEY_FILE], 'w')
            mydb.exec_query(insert_query, (key,))
            key = "key=%s\n" % key
            newfile.write("#This file is generated automatically by ossim. Please don't modify it!\n")
            newfile.write(extra_data)
            newfile.write("[key-value]\n")
            newfile.write(key)
            newfile.close()
            # insert the key in db..
            pw = pwd.getpwnam('www-data')
            os.chown(self.__conf[VAR_KEY_FILE], pw.pw_uid, pw.pw_gid)
            os.chmod(self.__conf[VAR_KEY_FILE], stat.S_IRUSR)

    def __check_pid(self):
        """ Check if pidfile exists.
        """
        try:

            pf = file(self.pidfile, 'r')
            pid = int(pf.read().strip())
            pf.close()
        except IOError:
            pid = None
        if pid:
            message = "pidfile %s already exist. Daemon already running?\n"
            sys.stderr.write(message % self.pidfile)
            sys.exit(1)

    def main(self):
        """
        Frameworkd main method. It's the entry point.
        """
        self.pidfile = '/var/run/ossim-framework.pid'
        self._options = self.__parse_options()
        self.__conf = OssimConf()

        if self._options.daemon is not None:
            self.__check_pid()
            self.__daemonize__()

        self.__init_log(self._options.daemon)
        logger.info("Frameworkd is starting up...")
        logger.info("Start Listener...")
        self.__listener = Listener()
        self.__listener.start()

        # BackupManager
        t = None
        bkm = BackupManager(self.__conf)
        bkm.start()

        for c in self.__classes:
            conf_entry = "frameworkd_" + c.lower()
            logger.debug("Conf entry:%s value: %s" % (conf_entry, self.__conf[conf_entry]))
            if str(self.__conf[conf_entry]).lower() in ('1', 'yes', 'true'):
                logger.info(c.upper() + " is enabled")
                exec "from %s import %s" % (c, c)
                exec "t = %s()" % (c)
                t.start()

        self.waitforever()

# vim:ts=4 sts=4 tw=79 expandtab:
