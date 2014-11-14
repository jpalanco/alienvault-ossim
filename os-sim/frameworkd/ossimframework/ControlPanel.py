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

import os, sys, time, re

from OssimConf import OssimConf
from OssimDB import OssimDB
import threading
import Const


class ControlPanel (threading.Thread) :

    def __init__ (self) :
        self.__conf = None      # ossim configuration values (ossim.conf)
        self.__conn = None      # cursor to ossim database
        self.__rrd_path = {}    # path for global, net, host and level rrds
        threading.Thread.__init__(self)


    def __startup (self) :

        # configuration values
        self.__conf = OssimConf (Const.CONFIG_FILE)

        # database connection
        self.__conn = OssimDB()
        self.__conn.connect (self.__conf["ossim_host"],
                              self.__conf["ossim_base"],
                              self.__conf["ossim_user"],
                              self.__conf["ossim_pass"])

        # rrd paths
        if self.__conf["rrdtool_path"]:
            Const.RRD_BIN = os.path.join(self.__conf["rrdtool_path"], "rrdtool")

        try:
            for dest in [ "global", "net", "host", "level" ] :
                self.__rrd_path[dest] = \
                    os.path.join(self.__conf["mrtg_rrd_files_path"],
                        '%s_qualification' % (dest))
        except OSError, e:
            logger.error("Error reading RRD path: " + str(e))
            sys.exit()
 

    # close db connection
    def __cleanup (self) :
        self.__conn.close()


    def __get_delete_query(self, type, info, rrd_name, range):
        """Returns the delete query
        """
        query = """
            DELETE FROM control_panel 
                WHERE id = '%s' AND rrd_type = '%s' AND time_range = '%s';
            """ % (rrd_name, type, range)
        return query


    def __get_insert_query(self, type, info, rrd_name, range):
        """
            Returns the insert query
        """
        query = """
        INSERT INTO control_panel 
        (id, rrd_type, time_range, max_c, max_a, max_c_date, max_a_date)
        VALUES ('%s', '%s', '%s', %f, %f, '%s', '%s');
        """ % (rrd_name, type, range, info["max_c"], info["max_a"],
              info["max_c_date"], info["max_a_date"])
        return query


    def __update_query_string(self, type, info, rrd_name, range):
        """Returns the update rdd query string
        """
        querystring = self.__get_delete_query(type, info, rrd_name, range)
        if (type == 'host') and not (info["max_c"] > 1 or info["max_a"] > 1):
            # Ignore hosts which don't have at least one of their values
            # greater than 1.
            # TODO: Compare against their thresholds instead of inserting
            # almost everything anyway.
            pass
        else:
            querystring = self.__get_insert_query(type, info, rrd_name, range)
        return querystring

    def __update_db(self, type, info, rrd_name, range):

        query = """
            DELETE FROM control_panel 
                WHERE id = '%s' AND rrd_type = '%s' AND time_range = '%s'
            """ % (rrd_name, type, range)

        self.__conn.exec_query(query)

        if (type == 'host') and not (info["max_c"] > 1 or info["max_a"] > 1):
            # Ignore hosts which don't have at least one of their values
            # greater than 1.
            # TODO: Compare against their thresholds instead of inserting
            # almost everything anyway.
            pass
        else:


            query = """
                INSERT INTO control_panel 
                (id, rrd_type, time_range, max_c, max_a, max_c_date, max_a_date)
                VALUES ('%s', '%s', '%s', %f, %f, '%s', '%s')
                """ % (rrd_name, type, range, info["max_c"], info["max_a"],
                       info["max_c_date"], info["max_a_date"])

            self.__conn.exec_query(query)

            logger.info("(%s) Updating %s (%s):    \tC=%f, A=%f" % (type, rrd_name, range, info["max_c"], info["max_a"]))
            sys.stdout.flush()


    # return a tuple with the date of C and A max values
    def __get_max_date(self, start, end, rrd_file):
        
        max_c = max_a = 0.0
        max_c_date = max_a_date = c_date = a_date = ""
        
        # execute rrdtool fetch and obtain max c & a date
        cmd = "%s fetch %s MAX -s %s -e %s" % \
            (Const.RRD_BIN, rrd_file, start, end)
        output = os.popen(cmd)
        pattern = "(\d+):\s+(\S+)\s+(\S+)"
        for line in output.readlines():
            result = re.findall(pattern, line)
            if result != []:
                (date, compromise, attack) = result[0]
                if compromise not in ("nan", "") and attack not in ("nan", ""):
                    if float(compromise) > max_c:
                        c_date = date
                        max_c = float(compromise)
                    if float(attack) > max_a:
                        a_date = date
                        max_a = float(attack)

        output.close()

        # convert date to datetime format
        if c_date:
            max_c_date = time.strftime('%Y-%m-%d %H:%M:%S',
                time.localtime(float(c_date)))
        else:
            # no date, so use the oldest
            max_c_date = time.strftime('%Y-%m-%d %H:%M:%S',
                time.localtime(0))
        if a_date:
            max_a_date = time.strftime('%Y-%m-%d %H:%M:%S',
                time.localtime(float(a_date)))
        else:
            # no date, so use the oldest
            max_a_date = time.strftime('%Y-%m-%d %H:%M:%S',
                time.localtime(0))
        
        return (max_c_date, max_a_date)


    # get a rrd C & A value
    def __get_rrd_value(self, start, end, rrd_file):
       
        rrd_info = {}

        # C max 
        # (2nd line of rrdtool graph ds0)
        cmd = "%s graph /dev/null -s %s -e %s -X 2 DEF:obs=%s:ds0:AVERAGE PRINT:obs:MAX:%%lf" % (Const.RRD_BIN, start, end, rrd_file)
        output = os.popen(cmd)
        output.readline()
        c_max = output.readline()
        output.close()
       
        # ignore 'nan' values
        if c_max not in ("nan\n", ""):
            rrd_info["max_c"] = float(c_max)
        else:
            rrd_info["max_c"] = 0
        
        # A max 
        # (2nd line of rrdtool graph ds1)
        cmd = "%s graph /dev/null -s %s -e %s -X 2 DEF:obs=%s:ds1:AVERAGE PRINT:obs:MAX:%%lf" % (Const.RRD_BIN, start, end, rrd_file)
        output = os.popen(cmd)
        output.readline()
        a_max = output.readline()
        output.close()

        # ignore 'nan' values
        if a_max not in ("nan\n", ""):
            rrd_info["max_a"] = float(a_max)
        else:
            rrd_info["max_a"] = 0

        (rrd_info["max_c_date"], rrd_info["max_a_date"]) = \
            self.__get_max_date(start, end, rrd_file)

        return rrd_info


    def __sec_update(self):

        for rrd_file in os.listdir(self.__rrd_path["level"]):

            result = re.findall("level_(\w+)\.rrd$", rrd_file)
            if result != []:

                user = result[0]

                rrd_file = os.path.join(self.__rrd_path["level"], rrd_file)
                rrd_name = os.path.basename(rrd_file.split(".rrd")[0])

                threshold = self.__conf["threshold"]

                # calculate average for day, week, month and year levels
                range2date = {
                    "day"  : "N-1D",
                    "week" : "N-7D",
                    "month": "N-1M",
                    "year" : "N-1Y",
                }
                
                pattern = "(\d+):\s+(\S+)\s+(\S+)"

                for range in range2date.keys():

                    output = os.popen("%s fetch %s AVERAGE -s %s -e N" % \
                        (Const.RRD_BIN, rrd_file, range2date[range]))

                    C_level = A_level = count = 0
                    for line in output.readlines():
                        result = re.findall(pattern, line)
                        if result != []:
                            (date, compromise, attack) = tuple(result[0])
                            if compromise not in ("nan", "") \
                              and attack not in ("nan", ""):
                                C_level += float(compromise)
                                A_level += float(attack)
                            else:
                                # when there is no data we suppose 
                                # the level is 100
                                C_level += 100
                                A_level += 100
                            count += 1
                    output.close
                    if count == 0:
                        # when there is no data we suppose the level is 100
                        query = """
                            UPDATE control_panel 
                                SET c_sec_level = 100, a_sec_level = 100
                                WHERE id = 'global_%s' and time_range = '%s'
                        """ % (user, range)

                        logger.info(" Updating %s (%s):  C=100%%, A=100%%" % (rrd_name, range))

                    else:
                        query = """
                            UPDATE control_panel 
                                SET c_sec_level = %f, a_sec_level = %f
                                WHERE id = 'global_%s' and time_range = '%s'
                        """ % (C_level / count, A_level / count, user, range)
                        logger.info(": Updating %s (%s):  C=%s%%, A=%s%%" % (rrd_name, range, C_level / count, A_level / count))
                    self.__conn.exec_query(query)


    # update all rrds in rrd_path
    def __update(self, type):

        for rrd_file in os.listdir(self.__rrd_path[type]):
            
            if rrd_file.endswith(".rrd"):
            
                try:
                    rrd_file = os.path.join(self.__rrd_path[type], rrd_file)
                    rrd_name = os.path.basename(rrd_file.split(".rrd")[0])
                    
                    rrd_info_day = self.__get_rrd_value("N-1D", "N", rrd_file)
                    rrd_info_week = self.__get_rrd_value("N-7D", "N", rrd_file)
                    rrd_info_month = self.__get_rrd_value("N-1M", "N", rrd_file)
                    rrd_info_year = self.__get_rrd_value("N-1Y", "N", rrd_file)

                    if rrd_info_day["max_c"] == rrd_info_day["max_a"] == \
                        rrd_info_week["max_c"] == rrd_info_week["max_a"] == \
                        rrd_info_month["max_c"] == rrd_info_month["max_a"] == \
                        rrd_info_year["max_c"] == rrd_info_year["max_a"] == 0 \
                        and type != "net":

                        # Only remove rrd files with ctime older than 1 day
                        if int(time.time()) - \
                           int(os.path.getctime(rrd_file)) > int(24 * 60 * 60):

                            logger.info("Removing unused rrd file (%s).." % (rrd_file))
                            try:
                                os.remove(rrd_file)
                            except OSError, e:
                                print e

                    else:
                        update_query_string=""
                        update_query_string = self.__update_query_string(type, rrd_info_day, rrd_name, 'day')
                        update_query_string += self.__update_query_string(type, rrd_info_week, rrd_name, 'week')
                        update_query_string += self.__update_query_string(type, rrd_info_month, rrd_name, 'month')
                        update_query_string += self.__update_query_string(type, rrd_info_year, rrd_name, 'year')
                        self.__conn.execute_non_query(query=update_query_string,autocommit=False)
# 
#                         self.__update_db(type, rrd_info_day, rrd_name, 'day')
#                         self.__update_db(type, rrd_info_week, rrd_name, 'week')
#                         self.__update_db(type, rrd_info_month, rrd_name, 'month')
#                         self.__update_db(type, rrd_info_year, rrd_name, 'year')

                except Exception, e:
                   logger.error("Unexpected exception: %s" % str(e))
                   


    # Delete hosts from control_panel when they're too old
    def delete_old_rrds(self, type, range):

        interval = 1
        # Compatibility with MySQL < 5.0.0
        # Value 'week' is available beginning with MySQL 5.0.0
        if range == "week":
            range = "day"
            interval = 7

        query = """
            DELETE FROM control_panel
                WHERE rrd_type = '%s' AND time_range = '%s'
                    AND (max_c_date is NULL OR
                         max_c_date<=SUBDATE(now(),INTERVAL %i %s))
                    AND (max_a_date is NULL OR
                         max_a_date<=SUBDATE(now(),INTERVAL %i %s))
                   """ % (type, range, interval, range, interval, range)

        self.__conn.exec_query(query)


    def run (self) :
        self.__startup()
        
        while 1:

            try:

                # let's go to party...
                for path in [ "host", "net", "global" ]:
                    self.__update(path)
                self.__sec_update()

                # clean up host's rrds
                for range in ["day", "week", "month", "year"]:
                    self.delete_old_rrds("host", range)

                # sleep to next iteration
                print __name__, ": ** Update finished at %s **" % \
                    time.strftime('%Y-%m-%d %H:%M:%S',
                                  time.localtime(time.time()))
                print __name__, ": Next iteration in %d seconds...\n\n" % \
                    (int(Const.SLEEP))
                sys.stdout.flush()

                time.sleep(float(Const.SLEEP))

            except KeyboardInterrupt:
                sys.exit()


        # never reached..
        self.__cleanup()

# vim:ts=4 sts=4 tw=79 expandtab:
