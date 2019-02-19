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
import re
import os
import ConfigParser
#
# LOCAL IMPORTS
#
from Logger import Logger
from OssimDB import OssimDB
from DBConstantNames import *

logger = Logger.logger

DEFAULT_CONFIG_FILE = "/etc/ossim/framework/ossim.conf"


class OssimMiniConf:
    def __init__(self, config_file=DEFAULT_CONFIG_FILE):
        self._conf = {}
        # get config only from ossim.conf file
        # (for example, when you only need database access)
        self._get_conf(config_file)

    def __setitem__(self, key, item):
        self._conf[key] = item

    def __getitem__(self, key):
        return self._conf.get(key, None)

    def __repr__(self):
        msg = ""
        for key, item in self._conf.iteritems():
            msg += "{}\t: {}\n".format(key, item)
        return msg

    def _get_conf(self, config_file):

        # Read config from file
        #
        try:
            config = open(config_file)
        except IOError, e:
            logger.error("Error opening OSSIM configuration file (%s)" % e)
            sys.exit()

        pattern = re.compile("^(\S+)\s*=\s*(\S+)")

        for line in config:
            result = pattern.match(line)
            if result is not None:
                (key, item) = result.groups()
                self[key] = item

        config.close()


class OssimConf(OssimMiniConf):
    def __init__(self, config_file=DEFAULT_CONFIG_FILE):
        self.__configfile = config_file
        OssimMiniConf.__init__(self, config_file)
        self._get_db_conf()

    def _get_db_conf(self):

        # Now, complete config info from Ossim database
        db = OssimDB(self[VAR_DB_HOST], self[VAR_DB_SCHEMA], self[VAR_DB_USER], self[VAR_DB_PASSWORD])
        db.connect()
        # Reads all the frameworkd configuration values.
        query = "select * from config"
        fmk_table_values = db.exec_query(query)
        for row in fmk_table_values:
            self._conf[row['conf']] = row['value']

        query = ''
        if not self._conf.has_key(VAR_KEY_FILE):
            self._conf[VAR_KEY_FILE] = '/etc/ossim/framework/db_encryption_key'
        keyfile = self._conf[VAR_KEY_FILE]
        useEncryption = False
        if os.path.isfile(keyfile):
            config = ConfigParser.ConfigParser()
            keyfile_fd = open(keyfile, 'r')
            try:
                config.readfp(keyfile_fd)
                self._conf[VAR_KEY] = config.get('key-value', 'key')
                useEncryption = True
            except Exception, e:
                logger.error("Invalid key file: %s" % str(e))
            finally:
                keyfile_fd.close()

        # Now read pass
        if useEncryption: 
            hash_ = db.exec_query(
                "SELECT *, AES_DECRYPT(value,%s) as dvalue FROM config where conf like '%%_pass%%'",
                (self._conf[VAR_KEY],)
            )
            for row in hash_:
                # values declared at config file override the database ones
                if row["conf"] not in self._conf:
                    if row["dvalue"] is not None:
                        self[row["conf"]] = row["dvalue"]
                    else:
                        hash_ = db.exec_query("SELECT * FROM config where conf like %s", (row["conf"],))
                        if len(hash_) > 0:
                            self[row["conf"]] = hash_[0]["value"]
                        else:
                            logger.error("No database value for conf: {}".format(row["conf"]))

# vim:ts=4 sts=4 tw=79 expandtab:
