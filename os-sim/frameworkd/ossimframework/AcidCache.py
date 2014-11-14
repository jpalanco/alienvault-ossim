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
import os
import sys
import time
import re
import pycurl
import StringIO

from OssimConf import OssimConf
#from OssimDB import OssimDB
import threading
from DBConstantNames import *

from Logger import Logger
logger = Logger.logger

class AcidCache (threading.Thread) :

    __UPDATE_DB     = "_update_db.php"
    __STAT_ALERTS   = "_stat_alerts.php?sort_order=occur_d"
    __STAT_UADDR1   = "_stat_uaddr.php?addr_type=1%26sort_order=occur_d"
    __STAT_UADDR2   = "_stat_uaddr.php?addr_type=2%26sort_order=occur_d"
    __STAT_PORTS    = "_stat_ports.php?port_type=2%26proto=-1%26sort_order=dip_d"

    def __init__ (self) :
        self.__conf = OssimConf ()
        self.__urls = {}
        threading.Thread.__init__(self)

    def run (self) :

        # default scheme and ip values
        if self.__conf[VAR_USE_HTTPS]:
            acid_scheme = ossim_scheme = "https://"
        else :
            acid_scheme = ossim_scheme = "http://"
        acid_ip = ossim_ip = "127.0.0.1"

        # get ossim and acid links from config
        acid_link = self.__conf["acid_link"]+"/" or \
            acid_scheme + "localhost/acid/"
        ossim_link = self.__conf["ossim_link"] or \
            ossim_scheme + "localhost/ossim/"
        acid_prefix = self.__conf["event_viewer"] or "acid"

        self.__urls = { 
            acid_prefix + "_update_db" :      AcidCache.__UPDATE_DB, 
            acid_prefix + "_stat_alerts" :    AcidCache.__STAT_ALERTS,
            acid_prefix + "_stat_uaddr1" :    AcidCache.__STAT_UADDR1,
            acid_prefix + "_stat_uaddr2" :    AcidCache.__STAT_UADDR2,
            acid_prefix + "_stat_ports2" :    AcidCache.__STAT_PORTS 
        }

        # web authentication?
#        ossim_web_user = self.__conf["ossim_web_user"] or "ossim"
#        ossim_web_pass = self.__conf["ossim_web_pass"] or "ossim"

        result = re.compile("(\w+:\/\/)(.*?)(\/.*)$").search(acid_link)
        if result is not None:
            (acid_scheme, acid_ip, acid_link) = result.groups()

        result = re.compile("(\w+:\/\/)(.*?)(\/.*)$").search(ossim_link)
        if result is not None:
            (ossim_scheme, ossim_ip, ossim_link) = result.groups()

        # get target urls
        acid_user = self.__conf["acid_user"]
        acid_pass = self.__conf["acid_pass"]
        session = "/session/login.php?dest="
        for key, url in self.__urls.iteritems():
            self.__urls[key] = "%s%s%s%s%s" % (acid_scheme, ossim_ip, acid_link, acid_prefix, url)

        while 1:

            # Open ossim session
            logger.info('Login to web framework')
            contents = StringIO.StringIO()
            url = "%s%s%s/session/login.php" % (ossim_scheme, ossim_ip, ossim_link)
            curl = pycurl.Curl()
            curl.setopt(pycurl.WRITEFUNCTION, contents.write)
            curl.setopt(pycurl.URL, url)
            curl.setopt(pycurl.FOLLOWLOCATION, 1)
            curl.setopt(pycurl.SSL_VERIFYHOST, 0)
            curl.setopt(pycurl.SSL_VERIFYPEER, 0)
            curl.setopt(pycurl.COOKIEFILE, "")
            curl.setopt(pycurl.HTTPPOST, [('user', acid_user),
                                          ('pass', acid_pass)])
            try:
                curl.perform()
            except Exception, e:
                logger.error(str(e))

            # TODO: What's the reason to logging to ossim-framework?
            #       I can't get it working with this piece of code :(
            #
            #            match = re.search(r".*OSSIM Framework Login.*", contents.getvalue())
            #            contents.close()
            #            if match is not None:
            #                print >>sys.stderr, __name__, ":", "Error : Failed login to web framework"
            #                continue

            for key, url in self.__urls.iteritems():
                contents = StringIO.StringIO()
                try:
                    fname = self.__conf["acid_path"] + "/" + key + ".html"
                    logger.info('Fetching %s from "%s"' % (fname, url))

                    curl.setopt(pycurl.URL, url)
                    curl.setopt(pycurl.FOLLOWLOCATION, 1)
                    curl.setopt(pycurl.WRITEFUNCTION, contents.write)
                    curl.perform()

                    fout = open (fname, "w")
                    fout.writelines(contents.getvalue())
                    fout.close()

                except Exception, e:
                    logger.error(str(e))
                contents.close()
 
            curl.close()
            time.sleep(float(Const.SLEEP))


if __name__ == "__main__":

    cache = AcidCache()
    cache.start()

# vim:ts=4 sts=4 tw=79 expandtab:
