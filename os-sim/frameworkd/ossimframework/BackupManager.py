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
import subprocess
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


class DoRestore(threading.Thread):
    """This class is designed to do the restore jobs. 
    It runs on a separate thread in process the restores jobs without stops the frameworkd work.
    """
    STATUS_ERROR = -1
    STATUS_OK = 0
    STATUS_WORKING = 1
    STATUS_PENDING_JOB = 2
    PURGE_STATUS_OK = 0
    PURGE_STATUS_WORKING = 1
    PURGE_STATUS_PENDING_JOB= 2
    PURGE_STATUS_ERROR = -1
    def __init__(self):
        threading.Thread.__init__(self)
        self.__status = DoRestore.STATUS_OK
        self.__keepWorking = True
        self.__myDB = OssimDB(_CONF[VAR_DB_HOST],
                              _CONF[VAR_DB_SCHEMA],
                              _CONF[VAR_DB_USER],
                              _CONF[VAR_DB_PASSWORD])
        self.__myDB_connected = False
        self.__bkConfig = {}
        self.__loadConfiguration()
        self.__resetJobParams()
        self.__mutex = Lock()
        self.__mutexPurge = Lock()
        self.__tables = ['acid_event',
                        'reputation_data',
                        'idm_data',
                        'extra_data',]
                        #'ac_acid_event',]#Changes #4376
                        #'ac_alerts_signature', 
                        #'ac_sensor_sid']
        self.__msgerror = ""
        self.__purgeStatus = DoRestore.PURGE_STATUS_OK
    def purge_status(self):
        """Returns the purge status code
        """
        return self.__purgeStatus

    def string_status(self):
        if self.__status == DoRestore.STATUS_ERROR:
            string = 'status="%s" error="%s"' %(self.__status,self.__msgerror)
        else:
            string ='status="%s"' % self.__status
        return string

    def __resetPurgeParams(self):
        """Resets all the purge job parameters.
        """
        self.__setStatusPurgeFlag( DoRestore.PURGE_STATUS_OK)
        self.__datelist_to_purge = []
        self.__bbddhost_to_purge = _CONF[VAR_DB_HOST]
        self.__bbdduser_to_purge = _CONF[VAR_DB_USER]
        self.__bbddpasswd_to_purge = _CONF[VAR_DB_PASSWORD]



    def __resetJobParams(self):
        """Resets all the job parameters.
        """
        self.__beginDate = None
        self.__endDate = None
        self.__entity = ""
        self.__newbbdd = False
        self.__bbddhost = _CONF[VAR_DB_HOST]
        self.__bbdduser =  _CONF[VAR_DB_USER]
        self.__bbddpasswd = _CONF[VAR_DB_PASSWORD]



    def __loadConfiguration(self):
        """Load the backup configuration from the database
        """
        if not self.__myDB_connected:
            if self.__myDB.connect():
                self.__myDB_connected = True
            else:
                logger.error("Can't connect to database")
                return
        query = 'select * from config where conf like "%backup%"'
        data = None
        data = self.__myDB.exec_query(query)
        tmpConfig = {}
        if data is not None:
            for row in data:
                if row['conf'] in ['backup_base', 'backup_day', 'backup_dir', 'backup_events', 'backup_store','frameworkd_backup_storage_days_lifetime']:
                    self.__bkConfig[row['conf']] = row['value']


    def __setStatusPurgeFlag(self,value):
        """Set the purge task status
        """
        self.__mutexPurge.acquire()
        logger.info("purge status :%s" % value)
        self.__purgeStatus = value
        self.__mutexPurge.release()


    def __setStatusFlag(self,value):
        """Sets the status flag
        """
        self.__mutex.acquire()
        self.__status = value
        logger.info("Change status status = %s" %(self.__status))
        self.__mutex.release()

    def status(self):
        """Returns the job status.
        """
        return self.__status


    def setJobParams(self,dtbegin,dtend,entity,newbbdd,bbddhost,bbdduser,bbddpasswd):
        """Set the params for  a restore job.
        @param dtbegin datetime: restore begin date
        @param dtend datetime: restore end date
        @param entity uuid string: entity whose events we want to restore
        @param newbbdd string: Indicates if we want to use a new database to restore the backup
        @param bbddhost string:[used only when newbbdd = 1] Database host
        @param bbdduser string: [used only when newbbdd = 1] Database user
        @param bbddpasswd string: [used only when newbbdd =1] Database password
        """
        self.__msgerror=""
        logger.info("""
        backup restore
        begin %s
        end %s
        entity %s
        newbbdd: %s
        bbddhost:%s
        bbdduser:%s
        
        """ %(dtbegin,dtend,entity,newbbdd,bbddhost,bbdduser))
        self.__beginDate = dtbegin
        self.__endDate = dtend
        self.__newbbdd = newbbdd
        self.__entity = entity
        if bbddhost!= None and bbddhost!= "":
            self.__bbddhost = bbddhost
        if bbdduser != None and bbdduser!="":
            self.__bbdduser =  bbdduser
        if bbddpasswd != None and bbddpasswd!="":
            self.__bbddpasswd = bbddpasswd
        self.__setStatusFlag(DoRestore.STATUS_PENDING_JOB)


    def emptyString(self,value):
        """Check if a string is empty or none.
        """
        if value== None or value == "":
            return True
        else:
            return False

    def setPurgeJobParams(self,datelist,bbddhost,bbdduser,bbddpasswd):
        """Set the data to the job params.
        """
        self.__setStatusPurgeFlag( DoRestore.PURGE_STATUS_PENDING_JOB)
        self.__datelist_to_purge = datelist
        self.__bbddhost_to_purge = bbddhost
        self.__bbdduser_to_purge = bbdduser
        self.__bbddpasswd_to_purge = bbddpasswd


    def __do_purgeEvents(self):
        """Removes the events from the datelist.
        """
        bbddhost = self.__bbddhost_to_purge
        bbdduser =  self.__bbdduser_to_purge
        bbddpasswd = self.__bbddpasswd_to_purge
        datelist = self.__datelist_to_purge

        self.__setStatusPurgeFlag( DoRestore.PURGE_STATUS_WORKING)
        if self.emptyString(bbddhost):
            bbddhost = self.__bbddhost
        if self.emptyString(bbdduser):
            bbdduser = self.__bbdduser
        if self.emptyString(bbddpasswd):
            bbddpasswd = self.__bbddpasswd
        deletes = []
        for date in datelist:
              deletestr = "delete-%s.sql.gz" % date.replace('-','')
              logger.info("Adding delete: %s" % deletestr)
              deletes.append(os.path.join(self.__bkConfig['backup_dir'],deletestr))

        for filename in glob.glob(os.path.join(self.__bkConfig['backup_dir'], 'delete-*.sql.gz')):
            if filename in deletes:
                logger.info("Running delete...%s" % filename)
                cmd = "mysql --host=%s --user=%s --password=%s  alienvault_siem < $FILE" % (bbddhost,bbdduser,bbddpasswd)
                random_string = ''.join(random.choice(string.ascii_uppercase) for x in range(10))
                ff = gzip.open(filename, 'rb')
                data = ff.read()
                ff.close()
                tmpfile = '/tmp/%s.sql' % random_string
                fd = open(tmpfile,'w')
                fd.write(data)
                fd.close()
                os.chmod(tmpfile, 0644)
                cmd = cmd.replace('$FILE',tmpfile)
                logger.info("Running purge eventes %s" % cmd)
                status, output = commands.getstatusoutput(cmd)
                if status != 0:
                    logger.error("Error running purge: %s" % output)
                    self.__msgerror = "Error running purge: %s" % output
                    self.__setStatusFlag(DoRestore.STATUS_ERROR)
                    return
                else:
                    logger.info("purge :%s ok" % cmd)
                os.remove(tmpfile)
        #self.__setStatusPurgeFlag(DoRestore.PURGE_STATUS_OK)
        self.__resetPurgeParams()


    def __getCreateTableStatement(self,host,user,password,database,tablename,gettemporal):
        """Returns the create table statement.
        @param host string: Database host
        @param user string: Database user
        @param password string: Database password
        @param database string: Database scheme name
        @param tablename string: Database table name
        @param gettemporal bool: Indicates if we want the real create table statment 
                                 or we want an create temporary table statement.
        """
        #mysqldump -u root -pnMMZ9yFuSu alienvault_siem acid_event --no-data
        create_table_statement = ""
        cmd = "mysqldump -h %s -u %s -p%s %s %s --no-data" % (host,\
                user,password,database,tablename)

        try:
            status,output = commands.getstatusoutput(cmd)
            if status == 0:
                logger.info("create table statement retreived ok")
                create_table_statement = output
            else:
                logger.error("create table statement fail status:%s output:%s" %(status,output))
        except Exception,e:
            logger.error("Create table statment fail: %s" % str(e))

        create_table_statement = create_table_statement.lower()
        lines = []
        if gettemporal:
            lines.append('use alienvault_siem_tmp;')
        for line in create_table_statement.split('\n'):
            if not  line.startswith('/*!') and not line.startswith('--'):
                lines.append(line)
        create_table_statement = '\n'.join(lines)
        return create_table_statement


    def __getIsOldDump(self, backupfile):
        """Checks if a backup file is a backup from 
        the alienvault 4 or older.
        @param backupfile - File to restore
        """
        cmd_check = "zcat %s | grep \"Database: alienvault_siem\"" % backupfile
        status,output = commands.getstatusoutput(cmd_check)
        logger.info("status: %s output: %s" %(status,output))
        if status!=0:
            return True
        return False


    def __setError(self,msg):
        """Sets the error status
        """
        logger.error(msg)
        self.__msgerror = msg
        self.__setStatusFlag(DoRestore.STATUS_ERROR)


    def __unzipBackupToFile(self,backupfile,outputfile):
        """Unzip a backup file to the outputfile
        """
        rt = True
        try:
            ff = gzip.open(backupfile, 'rb')
            data = ff.read()
            ff.close()
            fd = open(outputfile,'w')
            fd.write(data)
            fd.close()
            os.chmod(outputfile, 0644)
        except Exception,e:
            logger.error("Error decompressing the file %s " % backupfile)
            rt = False
        return rt


    def __doOldRestore(self,backupfile):
        """Restore an alienvault 3 backup inside the alienvault4 database.
        @param backupfile: File to restore
        """
        logger.info("OLDRESTORE Running restore database from version 3 to version 4")
        # 1 - Create a temporal schemes
        cmd = "mysql -h %s -u %s -p%s -e \"drop database IF EXISTS snort_tmp;create database IF NOT EXISTS snort_tmp;\"" % \
            (self.__bbddhost,self.__bbdduser,self.__bbddpasswd)
        status, output = commands.getstatusoutput(cmd)
        if status != 0:
            self.__setError("OLDRESTORE Error creating the temporal snort database (%s:%s)" %(status,output))
            return False
        
        # 2 - Create the database schema
        cmd = "mysqldump --no-data -h %s -u %s -p%s snort > /tmp/snort_schema.sql" % \
            (self.__bbddhost,self.__bbdduser,self.__bbddpasswd)
        status, output = commands.getstatusoutput(cmd)
        if status != 0:
            self.__setError("OLDRESTORE Can't dump the database schema (%s:%s)" %(status,output))
            return False
        logger.info("OLDRESTORE schema dumped ok..")
        
        cmd = "mysql --host=%s --user=%s --password=%s  snort_tmp < /tmp/snort_schema.sql" \
            % (self.__bbddhost, self.__bbdduser,self.__bbddpasswd)
        
        status, output = commands.getstatusoutput(cmd)
        if status != 0:
            self.__setError("OLDRESTORE Can't dump the database estructure (%s:%s)" %(status,output))
            return False

        
        # 3 - Dump the backup file to the temporal database
        logger.info("OLDRESTORE - Dumping the file to the temporal database...")
        random_string = ''.join(random.choice(string.ascii_uppercase) for x in range(10))
        tmpfile = '/tmp/%s.sql' % random_string
        if not self.__unzipBackupToFile(backupfile,tmpfile):
            self.__setError("Error building the temporal file...")
            return False

        restore_command = "mysql --host=%s --user=%s --password=%s  snort_tmp < $FILE" \
            % (self.__bbddhost, self.__bbdduser,self.__bbddpasswd)
        cmd = restore_command.replace('$FILE',tmpfile)
        status, output = commands.getstatusoutput(cmd)

        if status != 0:
            self.__setError("OLDRESTORE Can't dump the database data (%s:%s)" %(status,output))
            return False
        
        
        # 4 - Running migrate script
        
        cmd = "/usr/share/ossim/scripts/migrate_snort.pl snort_tmp"
        if not self.emptyString(self.__entity):
            cmd = "/usr/share/ossim/scripts/migrate_snort.pl snort_tmp %s" % self.__entity
        status, output = commands.getstatusoutput(cmd)
        if status != 0:
            self.__setError("OLDRESTORE Migrate script fails: %s-%s" %(status,output))
            return False
        logger.info("OLDRESTORE Restore has been successfully executed")
        try:
            os.remove(tmpfile)
            os.remove('/tmp/snort_schema.sql')
        except:
            pass
        return True


    def __doRestoreWithoutEntity(self,backupfile):
        """Restores the backup file regardless of the entity
        @param bakcupfile file to restore
        """
        restore_command = "mysql --host=%s --user=%s --password=%s  alienvault_siem < $FILE" % \
            (self.__bbddhost, self.__bbdduser,self.__bbddpasswd)
        random_string = ''.join(random.choice(string.ascii_uppercase) for x in range(10))
        tmpfile = '/tmp/%s.sql' % random_string
        if not self.__unzipBackupToFile(backupfile,tmpfile):
            self.__setError("Unable to decompress the backup file")
            return False
        cmd = restore_command.replace('$FILE',tmpfile)
        logger.info("Running restore ")
        status, output = commands.getstatusoutput(cmd)
        if status != 0:
            self.__setError("Error running restore: %s" % output)
            return False
        else:
            logger.info("Restore :%s ok" % cmd)
        os.remove(tmpfile)
        return True


    def __createTmpAlienvaultSiemDB(self):
        """Creates the alienvault_siem_tmp database
        """
        tmpfile = "/tmp/createdb.sql"
        createtmpdatabase = open(tmpfile,'w')
        createtmpdatabase.write("DROP DATABASE IF EXISTS alienvault_siem_tmp;\n")
        createtmpdatabase.write("CREATE DATABASE alienvault_siem_tmp;\n")
        # Create the temporary tables.
        for table in self.__tables:
            createtmpdatabase.write(self.__getCreateTableStatement(self.__bbddhost,self.__bbdduser,self.__bbddpasswd,'alienvault_siem',table,True))
        createtmpdatabase.close()
        createdb_command = "mysql --host=%s --user=%s --password=%s < %s" % (self.__bbddhost, self.__bbdduser,self.__bbddpasswd,tmpfile)
        status, output = commands.getstatusoutput(createdb_command)
        if status!=0:
            self.__setError("Can't create temporal db %s" % output)
            return False
        return True


    def __doRestore(self,filename):
        """Restores the backup file taiking into account the entity
        @param bakcupfile file to restore
        """
        try:
            restore_command = "mysql --host=%s --user=%s --password=%s alienvault_siem_tmp < $FILE" % \
                        (self.__bbddhost, self.__bbdduser,self.__bbddpasswd)
            # 1 - Create a temporal database
            if not self.__createTmpAlienvaultSiemDB():
                return False
            db = MySQLdb.connect(host=self.__bbddhost, user=self.__bbdduser,\
                                                   passwd=self.__bbddpasswd)
            db.autocommit(True)
            cursor  = db.cursor()
            
            # 2 - Unzip the backup file
            random_string = ''.join(random.choice(string.ascii_uppercase) for x in range(10))
            tmpfile = '/tmp/%s.sql' % random_string
            if not self.__unzipBackupToFile(filename,tmpfile):
                self.__setError("Unable to decompress the backup file")
                return False
            # 3 - Dumps the backup over the tmp database
            try:
                cmd = restore_command.replace('$FILE',tmpfile)
                status, output = commands.getstatusoutput(cmd)
                os.remove(tmpfile)
            except Exception,e:
                self.__setError(str(e))
                return False
            # 4 - Now remove all the data that do not belongs to the entity.A
            logger.info("Removing data from other entities")
            query_remove_acid_event = "delete from alienvault_siem_tmp.acid_event where ctx!=unhex('%s')"% self.__entity.replace('-','')
            logger.info("Removing data from acid event: %s" % query_remove_acid_event)
            cursor.execute(query_remove_acid_event)
            cursor.fetchall()


            query_remove_reptutation= "delete from alienvault_siem_tmp.reputation_data where event_id not in (select event_id from alienvault_siem_tmp.acid_event)"
            logger.info(query_remove_reptutation)
            cursor.execute(query_remove_reptutation)
            cursor.fetchall()


            query_remove_idm_data = "delete from alienvault_siem_tmp.idm_data where event_id not in (select event_id from alienvault_siem_tmp.acid_event)"
            logger.info(query_remove_idm_data)
            cursor.execute(query_remove_idm_data)
            cursor.fetchall()

            query_remove_extra_data = "delete from alienvault_siem_tmp.extra_data where event_id not in (select event_id from alienvault_siem_tmp.acid_event)"
            logger.info(query_remove_extra_data)
            cursor.execute(query_remove_extra_data)
            cursor.fetchall()
            # 5 - finally record all the data and insert it on alienvault_siem
            #table: acid_event
            querytmp = "select * into outfile '/tmp/acid_event.sql' from alienvault_siem_tmp.acid_event"
            cursor.execute(querytmp)
            cursor.fetchall()

            querytmp =" load data infile '/tmp/acid_event.sql' into table alienvault_siem.acid_event"
            self.__myDB.exec_query(querytmp)

            logger.info("Restored data to acid_event")


            #table: reputation_data:
            querytmp = "select * into outfile '/tmp/reputation_data.sql' from alienvault_siem_tmp.reputation_data"
            cursor.execute(querytmp)
            cursor.fetchall()
            
            querytmp =" load data infile '/tmp/reputation_data.sql' into table alienvault_siem.reputation_data"
            self.__myDB.exec_query(querytmp)
            logger.info("Restored data to reputation_data")


            #table: idm_data:
            querytmp = "select * into outfile '/tmp/idm_data.sql' from alienvault_siem_tmp.idm_data"
            cursor.execute(querytmp)
            cursor.fetchall()
            querytmp =" load data infile '/tmp/idm_data.sql' into table alienvault_siem.idm_data"
            self.__myDB.exec_query(querytmp)
            logger.info("Restored data to idm_data")
            # 6 - Remove the temporary database and the temporal files
            try:
                query ="DROP DATABASE IF EXISTS alienvault_siem_tmp"
                cursor.execute(query)
                cursor.fetchall()
                os.remove('/tmp/acid_event.sql')
                os.remove('/tmp/reputation_data.sql')
                os.remove('/tmp/idm_data.sql')
                cursor.close()
                db.close()
            except Exception,e:
                self.__setError("Error cleaning the data used to restore")
                return False
        except Exception,e:
            self.__setError("Can't do the restore :%s" % str(e))
            return False
        return True


    def __dojob(self):
        """Runs the restore job.
        """
        self.__setStatusFlag(DoRestore.STATUS_WORKING)
        logger.info("Running restore job ....")
        filestorestore = []
        insertfile = "insert-%s.sql.gz" % str(self.__beginDate.date()).replace('-','')
        insertfile = os.path.join(self.__bkConfig['backup_dir'],insertfile)
        filestorestore.append(insertfile)
        total_files = []

        while  self.__endDate>self.__beginDate:
            self.__beginDate = self.__beginDate + timedelta(days=1)
            insertfile = "insert-%s.sql.gz" % str(self.__beginDate.date()).replace('-','')
            insertfile = os.path.join(self.__bkConfig['backup_dir'],insertfile)
            filestorestore.append(insertfile)

        for filename in glob.glob(os.path.join(self.__bkConfig['backup_dir'], 'insert-*.sql.gz')):
            if filename in filestorestore:
                logger.info("Appending file to restore job: %s" % filename)
                total_files.append(filename)

        for filename in total_files:
            rt = True
            if self.__getIsOldDump(filename):
                logger.info("Detected old database...")
                rt = self.__doOldRestore(filename)
            elif self.emptyString(self.__entity):
                rt = self.__doRestoreWithoutEntity(filename)
            else:
                rt = self.__doRestore(filename)
            if not rt:
                return
        self.__setStatusFlag(DoRestore.STATUS_OK)


    def run(self):
        """Thread entry point. 
        Waits until new jobs arrives
        """
        while self.__keepWorking:
            if  self.__status == DoRestore.STATUS_PENDING_JOB:                
                self.__dojob()
            if self.__purgeStatus == DoRestore.PURGE_STATUS_PENDING_JOB:
                self.__do_purgeEvents()
            sleep(1)


class BackupRestoreManager():
    """Class to manage all the restore request from the web.
    """
    
    def __init__(self,conf):
        """ Default Constructor.
        @param conf OssimConf: Configuration object.
        """
        logger.info("Initializing  BackupRestoreManager")
        self.__conf = conf
        self.__worker = DoRestore()
        self.__worker.start()


    def process(self,message):
        """ Process the requests:
        @param message string: Request to process. 
            Examples:
            backup action="backup_restore"  begin="YYYY-MM-DD" end="YYYY-MM-DD" entity="ab352ced-c83d-4c9b-bc55-aae6c3e0069d" newbbdd="1" bbddhost="192.168.2.1" bbdduser="pepe" bbddpasswd="kktua" \n
            backup action="purge_events" dates="2012-05-12,2012-05-14,2012-05-15" bbddhost="host" bbdduser="kkka2" bbddpasswd="agaag"
            backup action="backup_status"

        """
        response =""
        action = Util.get_var("action=\"([a-z\_]+)\"", message)

        if action=="backup_restore":
            logger.info("Restoring")
            begindate = Util.get_var("begin=\"(\d{4}\-\d{2}\-\d{2})\"",message)
            enddate = Util.get_var("end=\"(\d{4}\-\d{2}\-\d{2})\"",message)
            entity = Util.get_var("entity=\"([a-f0-9]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12})\"",message.lower())
            newbbdd = Util.get_var("newbbdd=\"(0|1|yes|true|no|false)\"",message.lower())
            bbddhost = Util.sanitize(Util.get_var("bbddhost=\"(\S+)\"",message))
            bbdduser = Util.sanitize(Util.get_var("bbdduser=\"(\S+)\"",message))
            bbddpasswd = Util.sanitize(Util.get_var("bbddpasswd=\"(\S+)\"",message))
            dtbegin=None
            dtend = None
            isnewbbdd = False
            if newbbdd in ["yes","1","True"]:
                isnewbbdd = True
            try:
                dtbegin =  datetime.strptime(begindate,'%Y-%m-%d')
            except Exception,e:
                response = message + ' errno="-1" error="Invalid begin date. Format YYYY-MM-DD"  ackend\n'
                return response
            try:
                dtend= datetime.strptime(enddate,'%Y-%m-%d')
            except Exception,e:
                response = message +  ' errno="-2" error="Invalid end date. Format YYYY-MM-DD" ackend\n'
                return response
            if dtend < dtbegin:
                response = message +  ' errno="-3" error="End date < Begin Date" ackend\n'
                return response

            self.__worker.setJobParams(dtbegin,dtend,entity,newbbdd,bbddhost,bbdduser,bbddpasswd)
            response = message + ' status="%s" ackend\n' % self.__worker.status()
        elif action=="purge_events":
            logger.info("Restoring")
            dates = Util.get_var("dates=\"(\S+)\"",message)
            bbddhost = Util.sanitize(Util.get_var("bbddhost=\"(\S+)\"",message))
            bbdduser = Util.sanitize(Util.get_var("bbdduser=\"(\S+)\"",message))
            bbddpasswd = Util.sanitize(Util.get_var("bbddpasswd=\"(\S+)\"",message))
            datelist = dates.split(',')
            self.__worker.setPurgeJobParams(datelist,bbddhost,bbdduser,bbddpasswd)
            response = message + ' status="%s" ackend\n' % self.__worker.purge_status()

        elif action == "backup_status":
            logger.info("status")
            response = message + ' %s ackend\n' % self.__worker.string_status()
        else:
            response = message + ' errno="-4" error="Unknown command" ackend\n' 

        return response


class BackupManager(threading.Thread):
    """Manage the periodic backups.
    """
    UPDATE_BACKUP_FILE = '/etc/ossim/framework/lastbkday.fkm'
    LISTEN_SOCK = '/etc/ossim/framework/bksock.sock'


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
        #self.__mutex = Lock()
        self.__bkConfig = {}
        self.__loadConfiguration()
        self.__stopE = threading.Event()
        self.__stopE.clear()


    def __loadConfiguration(self):
        """Loads the backup manager configuration and updates it with the database values.
        """
        self.__loadBackupConfig()
        if not self.__myDB_connected:
            if self.__myDB.connect():
                self.__myDB_connected = True
            else:
                logger.error("Can't connect to database")
                return
        query = 'select * from config where conf like "%backup%"'
        data = None
        data = self.__myDB.exec_query(query)
        tmpConfig = {}
        if data is not None:
            for row in data:
                if row['conf'] in ['backup_base', 'backup_day', 'backup_dir', 'backup_events', 'backup_store','frameworkd_backup_storage_days_lifetime']:
                    tmpConfig [row['conf']] = row['value']
        if not self.__bkConfig.has_key('last_run'):
            logger.info("Loading new backup config...")
            self.__bkConfig = tmpConfig
            self.__bkConfig['last_run'] = date(year=1,month=1,day=1)
        else:
            for key, value in self.__bkConfig.iteritems():
                if key != 'last_run' and  tmpConfig[key] != value:
                    logger.info('Backup Config value has changed %s=%s and old value %s=%s' % (key, tmpConfig[key], key, value))
                    self.__bkConfig[key] = tmpConfig[key]
        if not self.__bkConfig.has_key('backup_day'):
            self.__bkConfig['backup_day'] = 30
        if not self.__bkConfig.has_key('frameworkd_backup_storage_days_lifetime'):
            self.__bkConfig['frameworkd_backup_storage_days_lifetime'] = 5

        self.__updateBackupConfigFile()


    def __updateBackupConfigFile(self):
        """Update the backup configuration file
        """
        try:
            bk_configfile = open(BackupManager.UPDATE_BACKUP_FILE, "wb")
            pickle.dump(self.__bkConfig, bk_configfile)
            bk_configfile.close()
            os.chmod(BackupManager.UPDATE_BACKUP_FILE,0644)
        except Exception, e:
            logger.error("Error dumping backup config update_file...:%s" % str(e))


    def __loadBackupConfig(self):
        """Load the backup configuration from the backup file
        """
        if os.path.isfile(BackupManager.UPDATE_BACKUP_FILE):
            try:
                bk_configfile = open(BackupManager.UPDATE_BACKUP_FILE)
                self.__bkConfig = pickle.load(bk_configfile)
                bk_configfile.close()
            except Exception, e:
                logger.error("Error loading postcorrelation update_file...:%s" % str(e))
                self.__bkConfig = {}
        else:
            self.__bkConfig = {}


    def purgeOldBackupfiles(self):
        """Purge old backup files.
        """
        #logger.info("Purge old bakcups")
        backup_files = []
        bkdays = 5
        try:
            bkdays = int(_CONF[VAR_BACKUP_DAYS_LIFETIME])
        except ValueError,e:
            logger.warning("Invalid value for backup_day in config table")
        today = datetime.now() - timedelta(days=1)
        while bkdays>0:
            dt = today.date().isoformat()
            dtstr = "%s" % dt
            dtstr = dtstr.replace('-','')
            str_insert = '%s/insert-%s.sql.gz' % (self.__bkConfig['backup_dir'], dtstr)
            str_delete = '%s/delete-%s.sql.gz' % (self.__bkConfig['backup_dir'], dtstr)
            backup_files.append(str_insert)
            backup_files.append(str_delete)
            bkdays = bkdays - 1
            today = today -timedelta(days=1)

        for bkfile in glob.glob(os.path.join(self.__bkConfig['backup_dir'], '[insert|delete]*.sql.gz')):
            bkfilep=os.path.join(self.__bkConfig['backup_dir'], bkfile)
            if bkfilep not in backup_files:
                logger.info("Removing outdated backfile :%s" % bkfilep)
                try:
                    os.unlink(bkfilep)
                except Exception,e:
                    logger.error("Error removing outdated files: %s" % bkfilep)


    def get_current_backup_files(self):
        backup_files = []
        try:
            #for bkfile in glob.glob(os.path.join(self.__bkConfig['backup_dir'], 'insert*.sql.gz')):
            backup_files = glob.glob(os.path.join(self.__bkConfig['backup_dir'], 'insert*.sql.gz'))
        except Exception as err:
            logger.error("An error occurred while reading the current database backups %s" % str(err))
        return backup_files
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


    def __createDeleteBkFile(self):
        """Creates the delete file for today
        """
        
        last_day = datetime.now() - timedelta(days=int(self.__bkConfig[VAR_BACKUP_DAYS_LIFETIME]))
        today_date = datetime.now().replace(hour=0, minute=0, second=0, microsecond=0)
        while last_day < today_date:
            date = last_day.date()
            daystr = '%s' % date
            daystr = daystr.replace('-', '')
            deletefilename = '%s/delete-%s.sql.gz' % (self.__bkConfig['backup_dir'], daystr)
            if not os.path.exists(deletefilename):
                delete_file = gzip.open(deletefilename,'w')
                initd = last_day.replace(hour=0, minute=0, second=0, microsecond=0)
                endd = last_day.replace(hour=23, minute=59, second=59, microsecond=0)
                try:
                    
                    #delete_file.write("DELETE FROM alienvault_siem.ac_acid_event WHERE day='%s';\n" % date.isoformat())
                    delete_file.write("DELETE FROM alienvault_siem.idm_data WHERE event_id IN (select id from alienvault_siem.acid_event where timestamp between '%s' and '%s');\n" % (initd, endd))
                    delete_file.write("DELETE FROM alienvault_siem.reputation_data WHERE event_id IN (select id from alienvault_siem.acid_event where timestamp between '%s' and '%s');\n" % (initd, endd))
                    delete_file.write("DELETE FROM alienvault_siem.extra_data WHERE event_id IN (select id from alienvault_siem.acid_event where timestamp between '%s' and '%s');\n" % (initd, endd))
                    delete_file.write("DELETE FROM alienvault_siem.acid_event WHERE timestamp between '%s' and '%s';\n" % (initd, endd))
                    delete_file.close()
                    os.chmod(deletefilename, 0644)
                except Exception,e:
                    logger.info("Error creating delete backup file")
            last_day = last_day + timedelta(days=1)

        return True


    def __shouldRunBackup(self):
        """Checks if it should runs a new backup.
        Backup Hour: Every day at 01:00:00
        """
        rt = False
        utc_dt = datetime.utcnow()
        limit_date = datetime.utcnow().replace(hour=1, minute=0, second=0, microsecond=0)
        last_bk_day = self.__bkConfig['last_run']
        #Alow backup now if it's after 01:00 pm and last_bk_day is less than today
        if utc_dt > limit_date and last_bk_day < utc_dt.date():
            rt = True
        return rt


    def __deleteByBackupDays(self,oldestEventDT):
        """Runs the delete command using the backup_day threshold
        @param datetime oldestEventDT: oldest event in the database
        """
        deletes = []
        #
        # Check by backup days.
        #
        backupdays=0
        try:
            backupdays = int(self.__bkConfig['backup_day'])
        except Exception,e:
            logger.warning("Invalid value for: Events to keep in the Database (Number of days) -> %s" % self.__bkConfig['backup_day'])
            backupdays = 0

        if backupdays>0:#backupdays = 0 unlimited.
            threshold_day = datetime.now().replace(hour=0, minute=0, second=0, microsecond=0) - timedelta(days=int(self.__bkConfig['backup_day']))
            begindt = oldestEventDT

            # Runs the deletes...
            while begindt < threshold_day:
                enddt = begindt.replace(hour=23, minute=59, second=59, microsecond=0)
                #Change #4376
                #deletes.append("DELETE FROM (alienvault_siem.ac_alerts_signature, alienvault_siem.ac_sensor_sid) WHERE day='%s';" % begindt.isoformat())
                #deletes.append("DELETE FROM (alienvault_siem.ac_acid_event) WHERE day='%s';" % begindt.isoformat())
                deletes.append("DELETE FROM alienvault_siem.reputation_data WHERE event_id IN (select id from alienvault_siem.acid_event where timestamp between '%s' and '%s');" % (begindt, enddt))
                deletes.append("DELETE FROM alienvault_siem.idm_data WHERE event_id IN (select id from alienvault_siem.acid_event where timestamp between '%s' and '%s');" % (begindt, enddt))
                deletes.append("DELETE FROM alienvault_siem.extra_data WHERE event_id IN (select id from alienvault_siem.acid_event where timestamp between '%s' and '%s');" % (begindt, enddt))
                deletes.append("DELETE FROM alienvault_siem.acid_event WHERE timestamp between '%s' and '%s';" % (begindt, enddt))
                begindt = self.__getOldestEventInDatabaseDateTime(begindt + timedelta(days=1))
        else:
            logger.info("Unlimited number of events.  Events to keep in the Database (Number of days) = %s" % backupdays)
        for delete in deletes:
            logger.info("Running delete :%s" % delete)
            self.__myDB.exec_query(delete)


    def __deleteByNumberOfEvents(self):
        """Runs the delete using the maximum number of events in the database 
        as threshold.
        """
        limit_date = None
        deletes = []
        #
        # Check by max number of events in the Database.
        #
        max_events=0
        try:
            max_events = int(self.__bkConfig['backup_events'])
        except Exception,e:
            logger.info("Invalid value for: Events to keep in the Database (Number of events) -> %s" % self.__bkConfig['backup_events'])
            max_events = 0

        if max_events>0: # backup_events = 0 -> unlimited
            query = "select timestamp from alienvault_siem.acid_event order by timestamp desc limit %s,1;" % self.__bkConfig['backup_events']
            data = self.__myDB.exec_query(query)

            if len(data) == 1:
                limit_date = data[0]['timestamp']
                limit_date.replace(hour=0, minute=0, second=0, microsecond=0)

                deletestr = "DELETE FROM alienvault_siem.reputation_data WHERE event_id IN (SELECT id FROM alienvault_siem.acid_event where timestamp < '%s');" % limit_date
                deletes.append(deletestr)
                deletestr = "DELETE FROM alienvault_siem.idm_data WHERE event_id IN (SELECT id FROM alienvault_siem.acid_event where timestamp < '%s');" % limit_date
                deletes.append(deletestr)
                deletestr = "DELETE FROM alienvault_siem.extra_data WHERE event_id IN (SELECT id FROM alienvault_siem.acid_event where timestamp < '%s');" % limit_date
                deletes.append(deletestr)
                deletestr = "DELETE FROM  alienvault_siem.acid_event WHERE timestamp < '%s';" % limit_date
                deletes.append(deletestr)
        else:
            logger.info("Unlimited number of events.  Events to keep in the Database (Number of events) = %s" % max_events)

        for delete in deletes:
            logger.info("Running delete :%s" % delete)
            self.__myDB.exec_query(delete)


    def __getOldestEventInDatabaseDateTime(self, min_timestamp):
        """Returns the datetime of the oldest event in the database
        """
        oldestEvent = datetime.now()

        query = 'SELECT min(timestamp) as lastEvent FROM alienvault_siem.acid_event WHERE timestamp > \'%s\';' % (min_timestamp)
        # Get the oldest event from the database and do the backup from that day
        # until yesterday (do if not exists yet).
        data = self.__myDB.exec_query(query)
        if len(data) == 1:
            oldestEvent = data[0]['lastEvent']
            if oldestEvent is None:
                oldestEvent = datetime.now()
            oldestEvent = oldestEvent.replace(hour=0, minute=0, second=0, microsecond=0)
        return oldestEvent


    def __storeBackups(self):
        """Returns if we have to store the backups.
        """
        rt = False
        if self.__bkConfig['backup_store'].lower() in ['1', 'yes', 'true']:
            rt= True
        return rt


    def __isBackupsEnabled(self):
        """Returns if the backups are enabled...
        """
        max_events=0
        try:
            max_events = int(self.__bkConfig['backup_events'])
        except Exception,e:
            logger.info("Invalid value for: Events to keep in the Database (Number of events) -> %s" % self.__bkConfig['backup_events'])
            max_events = 0
        backupdays=0
        try:
            backupdays = int(self.__bkConfig['backup_day'])
        except Exception,e:
            logger.warning("Invalid value for: Events to keep in the Database (Number of days) -> %s" % self.__bkConfig['backup_day'])
            backupdays = 0
        backup_enabled = True
        if max_events  ==0 and backupdays == 0:
            logger.info("Backups are disabled  MaxEvents = 0, BackupDays =0")
            backup_enabled = False
        return backup_enabled


    def __run_backup(self):
        """Run the backup job. 
        """
            #Check the disk space
        if not self.checkDiskUsage():
            logger.warning("[ALERT DISK USAGE] Can not run backups due to low free disk space")
            return
        #Purge old backup files
        self.purgeOldBackupfiles()
        backupCMD = "mysqldump alienvault_siem $TABLE -h %s -u %s -p%s -c -n -t -f --no-autocommit --skip-triggers --single-transaction --quick --hex-blob --insert-ignore -w $CONDITION" % (_CONF[VAR_DB_HOST], _CONF[VAR_DB_USER], _CONF[VAR_DB_PASSWORD])

        #Should I  store the backups? -> only if store is true.
        if self.__shouldRunBackup():#Time to do the backup?
            logger.info("Running backups system...")
            #do backup
            if not self.__myDB_connected:
                if self.__myDB.connect():
                    self.__myDB_connected = True
                else:
                    logger.info("Can't connect to database..")
                    return

            self.__bkConfig ['last_run'] = datetime.utcnow().date()
            firstEventDateTime = '1900-01-01 00:00:00'
            firstEventDateTime = self.__getOldestEventInDatabaseDateTime(firstEventDateTime)
            oldestEventDT = firstEventDateTime
            try:
                bkdays = int(_CONF[VAR_BACKUP_DAYS_LIFETIME])
            except ValueError:
                bkdays = 5
            threshold_day = datetime.today()-timedelta(days=bkdays+1)

            if self.__storeBackups() and self.__isBackupsEnabled():
                try:
                    today = datetime.now().replace(hour=0, minute=0, second=0, microsecond=0)
                    current_backups = self.get_current_backup_files()
                    while firstEventDateTime < today:
                        # Changes: #8312
                        # We should create a dump file only for the last bkdays
                        if firstEventDateTime < threshold_day:
                            logger.info("Do not make backup becuase threshold day: first event datetime: %s, thresholday:%s" % (firstEventDateTime,threshold_day))
                            firstEventDateTime = firstEventDateTime + timedelta(days=1)
                            continue
                        logger.info("="*50+"BACKUP: %s" % firstEventDateTime)
                        backupCmds = {}
                        dateBackup = '%s' % firstEventDateTime.date().isoformat()
                        insert_backupFile = '%s/insert-%s.sql' % (self.__bkConfig['backup_dir'], dateBackup.replace('-', ''))
                        if insert_backupFile+".gz" in current_backups:
                            firstEventDateTime = firstEventDateTime + timedelta(days=1)
                            logger.info("BACKUP %s Ignoring.... backup has already been done" % insert_backupFile)
                            continue
                        #if file is already created, continue
                        if os.path.exists(dateBackup):
                            continue
                        logger.info("New backup file: %s" % insert_backupFile)
                        #######################
                        # ACID EVENT
                        #######################
                        backupACIDEVENT_cmd = backupCMD.replace('$TABLE', 'acid_event')
                        condition = '"timestamp BETWEEN \'%s 00:00:00\' AND \'%s 23:59:59\'"' % (dateBackup, dateBackup)
                        backupACIDEVENT_cmd = backupACIDEVENT_cmd.replace('$CONDITION', condition)
                        backupCmds["%s_%s" % ('acid_event',dateBackup)] = backupACIDEVENT_cmd
                        condition = '"event_id in (SELECT id FROM alienvault_siem.acid_event WHERE timestamp BETWEEN \'%s 00:00:00\' AND \'%s 23:59:59\')"' % (dateBackup, dateBackup)
                        for table in ['reputation_data', 'idm_data', 'extra_data']:
                            cmd = backupCMD.replace('$TABLE', table)
                            cmd = cmd.replace('$CONDITION', condition)
                            backupCmds['%s_%s' %(table,dateBackup)] = cmd

                        condition = '"day=\'%s\'"' % (dateBackup)
                        #Changes #4376 
                        #for table in ['ac_alerts_signature', 'ac_sensor_sid']:
                        #    #cmd = 'mysqldump alienvault_siem %s -h %s -u %s -p%s -c -n -t -f --no-autocommit --single-transaction --quick  --insert-ignore -w "day=\'%s\'"' % (table, _CONF[VAR_DB_HOST], _CONF[VAR_DB_USER], _CONF[VAR_DB_PASSWORD], dateBackup)
                        #    cmd = backupCMD.replace('$TABLE', table)
                        #    cmd = cmd.replace('$CONDITION', condition)
                        #    backupCmds.append(cmd)
                        #cmd = backupCMD.replace('$TABLE', 'ac_acid_event')
                        #cmd = cmd.replace('$CONDITION', condition)
                        #backupCmds.append(cmd)

                        backup_data = ""
                        for table_day, cmd in backupCmds.iteritems():
                            cmd = cmd + ">> %s" % insert_backupFile
                            status, output = commands.getstatusoutput(cmd)
                            if status == 0:
                                logger.info("Running Backup for day %s  OK" % table_day)
                            else:
                                logger.error("Error (%s) running: %s" % (status, table_day))
                                return
                        try:
                            status, output = commands.getstatusoutput("gzip -f %s" % insert_backupFile)
                            if status == 0:
                                logger.info("Backup file has been compressed")
                                os.chmod(insert_backupFile + ".gz", 0640)

                        except Exception, e:
                            logger.error("Error writting backup file :%s" % str(e))
                            return
                        firstEventDateTime = firstEventDateTime + timedelta(days=1)
                except Exception, e:
                    logger.error("Error running the backup: %s" % str(e))

            self.__createDeleteBkFile()
            #
            # Deletes....
            #

            self.__deleteByBackupDays(oldestEventDT)
            self.__deleteByNumberOfEvents()
            self.__updateBackupConfigFile()


    def run(self):
        """ Entry point for the thread.
        """
        while not self.__stopE.isSet():
            logger.info("BackupManager - Checking....")
            self.__run_backup()
            sleep(30)


    def stop(self):
        """Stop the current thread execution
        """
        self.__stopE.set()


if __name__ == "__main__":
    bkm = BackupManager()
    bkm.start()
    try:
        while True:
            sleep(1)
    except KeyboardInterrupt:
        print "Ctrl-c received! Stopping BackupManager..."
        bkm.stop()
        bkm.join(1)
    sys.exit(0)
