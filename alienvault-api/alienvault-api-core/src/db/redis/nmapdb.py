# -*- coding: utf-8 -*-
#
# License:
#
# Copyright (c) 2014 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

from db.redis.redisdb import RedisDB
# Be careful with this number. The number of databases by default is 16.
# Database 0 is the default one, used by the alienvault-api and the ossim-agent.
NMAP_REDIS_DB = 0
# Just in case, add a namespace.
NMAP_SCANS_DB_NAMESPACE = "NMAP-SCANS-NS"


class NMAPScanCannotBeSaved(Exception):
    def __init__(self, msg):
        super(NMAPScanCannotBeSaved, self).__init__("NMAPScanCannotBeSaved item cannot be saved. Reason = '{0}'".format(msg))


class NMAPScansDB(RedisDB):
    """NMAP Scans database redis abstraction
    """

    def __init__(self, host="localhost", port=6379, password=None):
        super(NMAPScansDB, self).__init__(host=host, port=port, password=password, db=NMAP_REDIS_DB, namespace=NMAP_SCANS_DB_NAMESPACE)

    def add(self, job_id, scan):
        """Add a new nmap scan"""
        try:
            self.store(key=job_id, value=scan, expire=86400)
        except Exception as e:
            raise NMAPScanCannotBeSaved(str(e))

    def update(self, job_id, scan):
        self.add(job_id, scan)
