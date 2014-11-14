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
# LOCAL IMPORTS
#
from Logger import Logger

#
# GLOBAL VARIABLES
#
logger = Logger.logger

#
# CRITICAL IMPORTS
#
try:
    from adodb import adodb
except ImportError:
    try:
        import adodb
    except ImportError:
        logger.critical("You need python adodb module installed")



class DatabaseConn:

    def __init__(self):
        self.__conn = None


    def connect(self, db_type, host, db_name, user, password):

        self.__conn = adodb.NewADOConnection(db_type)

        # if db_type != 'mysql':
        #     logger.error("Database (%s) not supported" % (db_type))
        #     return None

        if password is None:
            password = ""

        try:
            self.__conn.Connect(host, user, password, db_name)

        except Exception, e_message:
            logger.error("Can't connect to database (%s@%s): %s" % \
                (user, host, e_message))
            self.__conn = None

        return self.__conn


    def exec_query (self, query) :
        """ Execute query and return the result in a full string."""

        result = ""
        try:
            cursor = self.__conn.Execute(query)

        except Exception, e:
            logger.error("Error executing query (%s)" % (e))
            return []

        while not cursor.EOF:
            for r in cursor.fields:
                result += str(r) + ' '

            cursor.MoveNext()

        self.__conn.CommitTrans()
        cursor.Close()
        return result


    def close(self):

        if self.__conn is not None:
            self.__conn.Close()


