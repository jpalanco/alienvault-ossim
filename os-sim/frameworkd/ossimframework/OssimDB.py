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
import sys
import time
from threading import Lock

try:
    import MySQLdb
    import MySQLdb.cursors
    import _mysql_exceptions
    from _mysql import escape
    from MySQLdb.converters import conversions
except ImportError:
    sys.exit("You need python MySQLdb module installed")

#
# LOCAL IMPORTS
#


from Logger import Logger

logger = Logger.logger


class OssimDB:
    def __init__(self, host, database, user, password):
        self._host = host
        self._database = database
        self._user = user
        self._password = ""
        if password is not None:
            self._password = password
        self._conn = None
        self._connected = False
        self._mutex = Lock()

    def connect(self):
        if self._connected:
            return

        self._connected = False
        attempt = 1
        while not self._connected and attempt <= 5:
            try:
                self._conn = MySQLdb.connect(host=self._host, user=self._user, passwd=self._password,
                                             db=self._database, cursorclass=MySQLdb.cursors.DictCursor)
                self._conn.autocommit(True)
                self._connected = True
            except Exception, e:
                logger.info("Can't connect to database (%s@%s) error: %s" % (self._user, self._host, e))
                logger.info("Retry in 2 seconds...")
                attempt += 1
                time.sleep(2)
        return self._connected

    # execute query and return the result in a hash
    def exec_query(self, query, params=None):
        self._mutex.acquire()
        arr = []
        max_retries = 3
        retries = 0
        retry_query = False
        cursor = None
        continue_working = True
        while continue_working:
            try:
                if not self._connected or self._conn is None:
                    self.connect()

                cursor = self._conn.cursor()
                cursor.execute(query, params)
                arr = cursor.fetchall()
                continue_working = False
                retries = max_retries + 1
                cursor.close()
            except _mysql_exceptions.OperationalError, (exc, msg):
                logger.debug(
                    'MySQL Operational Error executing query:\n----> %s \n----> [(%d, %s)]' % (query, exc, msg))
                if exc != 2006:
                    logger.error('MySQL Operational Error executing query')
                self.__close()
            except Exception, e:
                logger.error(
                    'Error executing query:\n----> [{0}]'.format(e)
                )
                self.__close()
            if retries >= max_retries:
                continue_working = False
            else:
                retries += 1
                time.sleep(1)
        self._mutex.release()

        if not arr:
            arr = []
        # We must return a hash table for row:
        return arr

    def execute_non_query(self, query, autocommit=False, params=None):
        """Executes a non query statement. 
        @autocommit: Sets the autocommit value by default it's True
        """
        self._mutex.acquire()
        max_retries = 3
        retries = 0
        retry_query = False
        cursor = None
        continue_working = True
        returnvalue = False
        while continue_working:
            try:
                if not self._connected or self._conn is None:
                    self.connect()
                self._conn.autocommit(autocommit)
                cursor = self._conn.cursor()
                cursor.execute(query, params)
                if not autocommit:
                    self._conn.commit()
                continue_working = False
                retries = max_retries + 1
                cursor.close()
                returnvalue = True
            except _mysql_exceptions.OperationalError, e:
                logger.error('Operation Error:\n%s\n[%s]' % (query, e))
                self._conn.rollback()
                self.__close()
                returnvalue = False
            except Exception, e:
                logger.error('Error executing query:\n %s \n [%s]' % (query, e))
                self._conn.rollback()
                self.__close()
                returnvalue = False
            if retries >= max_retries:
                continue_working = False
            else:
                retries += 1
                time.sleep(1)
        # self.__close()
        self._mutex.release()
        return returnvalue

    @staticmethod
    def format_query(query, params):
        """Formats the parametrized query in the same fashion as MySQLdb.cursor.execute(query, params) does. It is used
           for explicit parameters conversion/escaping to avoid SQL injection. Although MySQLdb.cursor.execute() does
           the same automatically, it is not possible to get the query out of there before execution, i.e. for logging
           purposes. The solution is to format the query before execute() and then simply pass it and use in logs or
           wherever.
        """
        if isinstance(params, dict):
            return query % dict((key, escape(param, conversions)) for key, param in params.iteritems())
        else:
            return query % tuple((escape(param, conversions) for param in params))

    def __close(self):
        if self._conn:
            try:
                self._conn.close()
            except _mysql_exceptions.ProgrammingError as err:
                logger.error("Error while closing the connection: {}".format(err))
            except AttributeError as err:
                logger.info("Trying to close the connection to mysql: {}".format(err))
            except Exception as err:
                logger.error("Can't close the connection to the database: {}".format(err))
            finally:
                self._conn = None
                self._connected = False
        else:
            logger.info("Trying to execute close method on connection which doesn't exist")
