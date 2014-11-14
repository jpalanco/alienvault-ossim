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
import socket
import sys
import time

#
# LOCAL IMPORTS
#
from Config import Plugin
from Detector import Detector
from Event import Event, EventOS, EventMac, EventService, EventHids,EventIdm
from Logger import Logger
import pdb

#
# GLOBAL VARIABLES
#
logger = Logger.logger

#
# CRITICAL IMPORTS
#
#try:
#    import MySQLdb
#
#except ImportError:
#    logger.info("You need python mysqldb module installed")
#try:
#    import pymssql
#
#except ImportError:
#    logger.info("You need python pymssql module installed")
#    
#try:
#    import cx_Oracle
#except ImportError:
#    logger.info("You need python cx_Oracle module installed. This is not an error if you aren't using an Oracle plugin")
#try:
#    import ibm_db
#except ImportError:
#    logger.info("You need python ibm_db module installed. This is not an error if you aren't using an IBM plugin")

"""
Parser Database
"""

try:
    import ibm_db
    db2notloaded = False
except ImportError:
    db2notloaded = True

try:
    import MySQLdb
    mysqlnotloaded = False
except ImportError:
    mysqlnotloaded = True
try:
    import pymssql
    mssqlnotloaded = False
except ImportError:
    mssqlnotloaded = True
try:
    import cx_Oracle
    oraclenotloaded = False
except ImportError:
    oraclenotloaded = True
MAX_TRIES_DB_CONNECT = 10
DEFAULT_SLEEP = 10 #10 seconds. Default sleep between attemps
class ParserDatabase(Detector):
    def __init__(self, conf, plugin, conn,idm=False):
        Detector.__init__(self, conf, plugin, conn)
        self._conf = conf
        self._plugin = plugin
        self.rules = [] # list of RuleMatch objects
        self.conn = conn
        self.__myDataBaseCursor = None
        self.__objDBConn = None        
        self.__tries = 0
        self.stop_processing = False
        self._databasetype = self._plugin.get("config", "source_type")
        self._canrun = True
        self.__idm = True if self._plugin.get("config", "idm") == "true" else False
        logger.info ("IDM is %s for plugin %s" % ("enabled" if self.__idm else "disabled", self._plugin.get("DEFAULT", "plugin_id")))

        if self._databasetype == "db2" and db2notloaded:
            logger.info("You need python ibm_db module installed. This is not an error if you aren't using an IBM plugin")
            self._canrun = False
        elif self._databasetype == "mysql" and mysqlnotloaded:
            logger.info("You need python mysqldb module installed")
            self._canrun = False
            self.stop()
        elif self._databasetype == "oracle" and oraclenotloaded:
            logger.info("You need python cx_Oracle module installed. This is not an error if you aren't using an Oracle plugin")
            self._canrun = False
        elif self._databasetype == "mssql" and mssqlnotloaded:
            logger.info("You need python pymssql module installed")
            self._canrun = False


    def runStartQuery(self, plugin_source_type, rules):
        cVal = "NA"
        if self.__myDataBaseCursor is None:
            return cVal
        try:
            if plugin_source_type != "db2":
                sql = rules['start_query']['query']
                logger.debug("Running Start query: %s" % sql)
                self.__myDataBaseCursor.execute(sql)
                rows = self.__myDataBaseCursor.fetchone()
                if not rows:
                    logger.warning("Initial query empty, please double-check")
                    return cVal
                cVal = str((rows[0]))
    
                sql = rules['query']['query']
            elif plugin_source_type == "db2":
                sql = rules['start_query']['query']
                logger.debug("Start query: %s" % sql)
                result = ibm_db.exec_immediate(self.__objDBConn, sql)
                dictionary = ibm_db.fetch_both(result)
                if not dictionary:
                    logger.warning("Initial query empty, please double-check")
                    return cVal
                cVal = str((dictionary[0]))
                logger.info("Connection closed")
            if cVal==None or cVal =="None" or len(cVal)<=0:
                cVal="NA"
        except Exception, e:
            cVal ="NA"
            logger.error("Error running the start query: %s" % str(e))
        return cVal


    def openDataBaseCursor(self, database_type):
        opennedCursor = False
        if database_type == "mysql":
            #Test Connection
            try:
                self.connectMysql()
                if self.__myDataBaseCursor:
                    opennedCursor = True
            except:
                logger.info("Can't connect to MySQL database")
        elif database_type == "mssql":
            try:
                self.connectMssql()
                if self.__myDataBaseCursor:
                    opennedCursor = True
            except:
                logger.info("Can't connect to MS-SQL database")
        elif database_type == "oracle":
            try:
                self.connectOracle()
                if self.__myDataBaseCursor:
                    opennedCursor = True
            except:
                logger.info("Can't connect to Oracle database")
        elif database_type == "db2":
            self.connectDB2()
            if self.__myDataBaseCursor:
                opennedCursor = True
        else:
            logger.info("Database not supported")
        return opennedCursor


    def closeDBCursor(self):
        if self.__myDataBaseCursor is not None:
            self.__myDataBaseCursor.close()


    def stop(self):
        logger.info("Stopping database parser...")
        self.stop_processing = True
        try:
            self.closeDBCursor()
            self.join(1)
        except RuntimeError:
            logger.warning("Stopping thread that likely hasn't started.")


    def tryConnectDB(self):
        connected = False
        while not connected and self.__tries < MAX_TRIES_DB_CONNECT:
            time.sleep(10)
            connected = self.openDataBaseCursor(self._plugin.get("config", "source_type"))
            if not connected:
                logger.info("We cant connect to data base, retrying in 10 seconds....try:%d"% self.__tries)
            self.__tries += 1
        else:
            if connected:
                logger.info("Connected to DB after %s tries" % self.__tries)
            if self.__tries >= MAX_TRIES_DB_CONNECT:
                logger.info("Max connection attempts reached")
            self.__tries = 0
        return connected


    def process(self):
        tSleep = DEFAULT_SLEEP
        try:
            tSleep = int(self._plugin.get("config", "sleep"))
        except ValueError:
            logger.error("sleep should be an integer number...using default value :%d" % DEFAULT_SLEEP)
        if not self._canrun:
            logger.info("We can't start the process,needed modules")
            return

        logger.info("Starting Database plugin")
        rules = self._plugin.rules()
        run_process = False

        if not self.tryConnectDB():
            self.stop()
            return

        cVal = "NA"
        plugin_source_type = self._plugin.get("config", "source_type")
        while cVal == "NA" and not self.stop_processing:
            cVal = self.runStartQuery(plugin_source_type, rules)
            if cVal == "NA":
                logger.info("No data retrieved in the start quere, waiting 10s until the next attempt")
                time.sleep(10)
            else:
                run_process = True
        ref = int(rules['query']['ref'])
        while run_process and not self.stop_processing:
            if self._plugin.get("config", "source_type") != "db2":
                try:
                    if self.__myDataBaseCursor:
                        self.__myDataBaseCursor.close()
                    if self.__objDBConn:
                        self.__objDBConn.close()
                except Exception, e:
                    logger.info ('Anomaly found while closing cursor: %s' % str(e))

                if self.tryConnectDB():
                    sql = rules['query']['query']
                    sql = sql.replace("$1", str(cVal))
                    logger.debug(sql)
                    try:
                        self.__myDataBaseCursor.execute(sql)
                        ret = self.__myDataBaseCursor.fetchall()
                    except Exception,e:
                        logger.error("Error running query: %s -> %s" %(sql,str(e)))
                        time.sleep(1)
                        continue
                    try:
                        if len(ret) > 0:
                            #We have to think about event order when processing
                            cVal = ret[len(ret) - 1][ref]
                            for e in ret:
                                self.generate(e)
                    except Exception,e:
                        logger.error("Error building the event: %s" %(str(e)))
                        time.sleep(tSleep)
                else:
                    self.error ("Couldn't connect to database, maximum retries exceeded")
                    return
            else:
                sql = rules['query']['query']
                sql = sql.replace("$1", str(cVal))
                logger.debug(sql)
                result = ibm_db.exec_immediate(self.__objDBConn, sql)
                row  = ibm_db.fetch_tuple(result)
                ret = []
                while row:
                    logger.info(str(row))
                    ret.append(row)
                    row = ibm_db.fetch_tuple(result)
                logger.info("len ret %s y ref %s" % (len(ret),ref))
                if len(ret) > 0:
                    cVal = ret[len(ret) - 1][ref]
                    for e in ret:
                        logger.info("-.-->", e)
                        self.generate(e)

            time.sleep(tSleep)


    def connectMysql(self):
        #logger.info("here")
        host = self._plugin.get("config", "source_ip")
        user = self._plugin.get("config", "user")
        passwd = self._plugin.get("config", "password")
        db = self._plugin.get("config", "db")
        try:
            self.__objDBConn = MySQLdb.connect(host=host, user=user, passwd=passwd, db=db)
        except Exception, e:
            logger.error("We can't connecto to database: %s" % e)
            return None
        self.__myDataBaseCursor = self.__objDBConn.cursor()


    def connectMssql(self):
        host = self._plugin.get("config", "source_ip")
        user = r'%s' % self._plugin.get("config","user")#self._plugin.get("config", "user")
        passwd = self._plugin.get("config", "password")
        db = self._plugin.get("config", "db")
        self.__objDBConn = pymssql.connect(host=host, user=user, password=passwd, database=db)
        self.__myDataBaseCursor = self.__objDBConn.cursor()



    def connectOracle(self):
        dsn = self._plugin.get("config", "dsn")
        user = self._plugin.get("config", "user")
        passwd = self._plugin.get("config", "password")
        self.__objDBConn = cx_Oracle.connect(user, passwd, dsn)
        self.__myDataBaseCursor = self.__objDBConn.cursor()


    def connectDB2(self):
        dsn = self._plugin.get("config", "dsn")
        self.__objDBConn = ibm_db.connect(dsn, "", "")
        self.__myDataBaseCursor = self.__objDBConn


    def generate(self, groups):
        if self.__idm:
            event = EventIdm()
        else:
            event = Event()
        rules = self._plugin.rules()
        for key, value in rules['query'].iteritems():
            if key != "query" and key != "regexp" and key != "ref":
                data = None
                data = self._plugin.get_replace_array_value(value.encode('utf-8'), groups)
                if data is not None:
                    event[key] = data
        if event is not None:
            self.send_message(event)
