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
# Replaces and improves the 'etc/cron.daily/ossim-backup' script
#
#  -  ossim, phpgacl and snort database backups using mysqldump
#  -  backup files are compressed with gzip and stored in conf[backup_dir]
#  -  if purge=True, outdated files (conf[backup_day]) are removed
#

#
# GLOBAL IMPORTS
#
import glob
import gzip
import os
import signal
import threading
import time
import commands
#
# LOCAL IMPORTS
#

from Logger import Logger
from OssimDB import OssimDB
from OssimConf import OssimConf
from NotifyManager import NotifyManager
from DBConstantNames import *
#
# GLOBAL VARIABLES
#
logger = Logger.logger

# TODO: global CONF and DB object.
# They are used in almost all threads
_CONF = OssimConf()
_DB = OssimDB(_CONF[VAR_DB_HOST],
              _CONF[VAR_DB_SCHEMA],
              _CONF[VAR_DB_USER],
              _CONF[VAR_DB_PASSWORD])
_DB.connect()

DATABASES = [ 'ossim' ]

class Backup(threading.Thread):

    def __init__(self, purge=True):
        self._databases = {}
        for db in DATABASES:
            self._databases[db] = {}
        self.__notifier = NotifyManager()
        self.backup_dir = _CONF['backup_dir']
        self.purge = purge
        self.__interval = 300.0
        try:
            self.__interval = int(_CONF[VAR_BACKUP_PERIOD])
        except ValueError:
            logger.error("Invalid value for: %s" % _CONF[VAR_BUSINESSPROCESSES_PERIOD])
        threading.Thread.__init__(self)


    def get_database_properties(self, db):

        properties = {}

        if not db:
            return properties

        if _CONF[db + '_type'] == 'mysql':
            for key in ['base', 'host', 'user', 'pass']:
                properties[key] = _CONF[db + '_' + key]

        return properties


    def compress_backup_file(self, backup_file):

        try:
            fo = open(backup_file)

        except IOError, e:
            logger.error("Error opening backup file: %s" % (e))
            return

        fd = gzip.GzipFile(backup_file + ".gz", 'wb')
        fd.write(fo.read())
        fo.close()
        fd.close()
        os.chmod(backup_file + ".gz", 0644)
        try:
            os.unlink(backup_file)
        except OSError, e:
            logger.error("Error removing file: %s" % (e))
            return

        logger.info("Created backup file %s.gz [%s bytes]" % (backup_file, str(os.path.getsize(backup_file + ".gz"))))


    def purge_outdated_files(self):

        # purge files matching (ossim|phpgacl|snort.*?\sql.gz)
        def file_ok_to_purge(file):

            for base in DATABASES:
                if file.startswith(base):
                    return True

            return False

        # cleanup old files
        purge_date = time.time() - int(_CONF['backup_day']) * 60 * 60 * 24
        for file in glob.glob(os.path.join(self.backup_dir, '*.sql.gz')):
            if file_ok_to_purge(os.path.basename(file)):
                if os.path.getctime(os.path.join(self.backup_dir, file)) < purge_date:
                    logger.info("Removing outdated file %s.." % \
                        (os.path.join(self.backup_dir, file)))

                    os.unlink(os.path.join(self.backup_dir, file))


    def run(self):
        #DEPRECTED -  #9082 - Moved to Alienvault API
        return
        while 1:
            for db in self._databases.iterkeys():
                self._databases[db] = self.get_database_properties(db)

            for db_name, db_properties in self._databases.iteritems():
                date_string = time.strftime("%F", time.localtime())
                backup_file = os.path.join(self.backup_dir, \
                    db_name + "-backup_" + date_string + ".sql")

                if os.path.isfile(backup_file + ".gz"):
                    logger.info("backup file already created (%s)" % (backup_file))
                    #self.__notifier.info("backup file already created (%s)" % (backup_file))
                    continue

                logger.info("database backup [%s] in progress.." % (db_name))

                # mysqldump -h $HOST -u $USER -p$PASS $BASE | gzip -9c > $BACKUP
                if db_properties['pass'] is None:
                    os.system('mysqldump -c -h %s -u %s %s > %s' % \
                    (db_properties['host'],
                     db_properties['user'],
                     db_properties['base'],
                     backup_file))

                else:
                    bk_cmd = 'mysqldump -c -h %s -u %s -p%s %s > %s' % \
                        (db_properties['host'],
                         db_properties['user'],
                         db_properties['pass'],
                         db_properties['base'],
                         backup_file)
                    status,output = commands.getstatusoutput(bk_cmd)
                    logger.info("database backup ...%s status:%s, output:%s" % (db_properties['base'],status,output))
                    maxTries = 5
                    back_upOK = False
                    if status == 0:
                        back_upOK = True
                        logger.info("Successfull Backup :%s - status:%s" % (db_properties['base'],status))
                        self.__notifier.info("Successfull Backup :%s - status:%s" % (db_properties['base'],status))
                        time.sleep(10)
                    while status!=0 and maxTries>0:
                        back_upOK = False
                        status,output = commands.getstatusoutput(bk_cmd)
                        if status == 0:
                            back_upOK = True
                            self.__notifier.info("Successfull Backup :%s - status:%s" % (db_properties['base'],status))
                        else:
                            maxTries = maxTries -1
                            logger.warning("Error creating backup :%s - status:%s" % (db_properties['base'],status))
                            self.__notifier.warning("Error creating backup :%s - status:%s" % (db_properties['base'],status))
                            time.sleep(10)
                        
#                    os.system('mysqldump -h %s -u %s -p%s %s > %s' % \
#                        (db_properties['host'],
#                         db_properties['user'],
#                         db_properties['pass'],
#                         db_properties['base'],
#                         backup_file))
                    if back_upOK:
                        # compress the .sql file
                        self.compress_backup_file(backup_file)

            if self.purge:
                self.purge_outdated_files()

            time.sleep(self.__interval)



if __name__ == "__main__":
    backup = Backup(purge=True)
    backup.start()

    while 1:
        try:
            time.sleep(1)
        except KeyboardInterrupt:
            pid = os.getpid()
            os.kill(pid, signal.SIGTERM)


