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
from ApacheNtopProxyManager import ApacheNtopProxyManager

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
        self.__ntop_apache_manager = ApacheNtopProxyManager(self.__conf)
        self.__last_ntop_link = "" 
        threading.Thread.__init__(self)


    def __check_last_db_id(self):
        db_last_id = self.__get_last_db_id()

        if db_last_id == self.__stored_id:
            # we're up to date
            return False
        return True


    def __check_db_scheduler_count(self):
        db_id_num = self.__get_db_scheduler_count()

        if db_id_num == self.__stored_num:
            # we're up to date
            return False

        return True


    def __get_last_db_id(self):
        query = "select max(id) as id from plugin_scheduler"
        hash = self.__db.exec_query(query)

        if hash != []:
            if hash[0]["id"] is not None:
                return hash[0]["id"]

        return 0


    def __get_db_scheduler_count(self):
        query = "select count(id) as id from plugin_scheduler"
        hash = self.__db.exec_query(query)

        for row in hash:
            return row["id"]

        return 0


    def __get_crontab(self):
        crontab = []

        cmd = "crontab -l"
        output = os.popen(cmd)

        pattern = "#### OSSIM scheduling information, everything below this line will be erased. Last schedule:\s*\((\d+)\)\s* ####"

        for line in output.readlines():
            result = re.findall(pattern, line)
            if result != []:
            # We fond our header. Let's see how many entries are in there and
            # return without the header line
                self.__header_id = result[0]
                output.close()
                return crontab
            else:
            # Just append the line
                crontab.append(line)

        # We didn't find the header 
        output.close()
        return crontab


    def __set_crontab(self, crontab):
        if len(crontab) < 1:
            logger.debug("Since at least the warning line has to be present, something went wrong if crontab has less than 1 entry. Not overwriting crontab")
            return False

        tmp_name = tempfile.mktemp(".ossim.scheduler")
        outfile =  open(tmp_name, "w")
        try:
            for line in crontab:
                outfile.write(line)

        finally:
            outfile.close()

        cmd = "crontab %s" % tmp_name 
        status = os.system(cmd)
        os.unlink(tmp_name)
        if(status < 0):
            return False

        return True


    def run(self):

        self.__db.connect()
        self.__last_ntop_link = self.__ntop_apache_manager.getNtopLink()
        while 1:
            try:
                new_ntop_link = self.__ntop_apache_manager.getNtopLink()
                if self.__last_ntop_link != new_ntop_link:
                    self.__ntop_apache_manager.refreshDefaultNtopConfiguration(must_reload = True)
                # Check if we already have the latest DB id stored in memory
                # during this run
                if self.__check_last_db_id() == True or self.__check_db_scheduler_count() == True:

                    # Let's fetch the crontab up until our header (if present)
                    # and check if we have to recreate it
                    crontab = self.__get_crontab()
                    last_id = self.__get_last_db_id()
                    id_num = self.__get_db_scheduler_count()

                    for line in crontab:
                        logger.debug(line.strip())

                    # Ok, we have to redo the crontab entry
                    ossim_tag = "#### OSSIM scheduling information, everything below this line will be erased. Last schedule: (%d) ####" % int(last_id)
                    logger.debug(ossim_tag)
                    crontab.append(ossim_tag + "\n")

                    query = "SELECT * FROM plugin_scheduler"
                    hash = self.__db.exec_query(query)

                    FRAMEWORKD_DIR = self.__conf["frameworkd_dir"] or \
                        "/usr/share/ossim-framework/ossimframework"

                    for row in hash:
                        donessus_command = "python " +\
                            os.path.join(FRAMEWORKD_DIR, "DoNessus.py") +\
                            " -i " + str(row["id"])

                        entry = "%s\t%s\t%s\t%s\t%s\t%s\n" % \
                            (row["plugin_minute"],\
                             row["plugin_hour"],\
                             row["plugin_day_month"],\
                             row["plugin_month"],\
                             row["plugin_day_week"],\
                             donessus_command)
                        crontab.append(entry)
                        logger.debug(entry)
                    
                    logger.debug("Setting crontab")

                    if self.__set_crontab(crontab) == True:
                        logger.debug("Crontab successfully updated")

                        self.__stored_id = self.__header_id = last_id
                        self.__stored_num = id_num

                    else:
                        logger.debug("Crontab not updated, something went wrong (check output)")

            except Exception, e:
                logger.error(e)

            logger.debug("Iteration...")
            time.sleep(float(self.__conf[VAR_SCHEDULED_PERIOD]))

        # never reached..
        self.__db.close()


if __name__ == "__main__":

    scheduler = Scheduler()
    scheduler.start()

# vim:ts=4 sts=4 tw=79 expandtab:
