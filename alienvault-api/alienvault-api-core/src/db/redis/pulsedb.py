# -*- coding: utf-8 -*-
#
# License:
#
#  Copyright (c) 2014 AlienVault
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
PULSE_REDIS_DB = 99
# Just in case, add a namespace.
PULSE_DB_NAMESPACE = "PulseNS"


class PulseInvalidKey(Exception):
    def __init__(self, key):
        super(PulseInvalidKey, self).__init__("Invalid Key {0}".format(key))


class PulseNotFound(Exception):
    def __init__(self, key):
        super(PulseNotFound, self).__init__("Pulse not found. Key = '{0}'".format(key))


class PulseCannotBeSaved(Exception):
    def __init__(self, msg):
        super(PulseCannotBeSaved, self).__init__("Pulse cannot be saved. Reason = '{0}'".format(msg))


class PulseKeysCannotBeLoaded(Exception):
    def __init__(self, msg):
        super(PulseKeysCannotBeLoaded, self).__init__("Pulse keys cannot be loaded. Reason = '{0}'".format(msg))


class PulseDB(RedisDB):
    """Pulse database redis abstraction
    Pulse JSON schema:
            {
            "author_name": "AlienVault",
            "created": "2015-04-21T16:09:29.291000",
            "description": "More information from PWC about Sofacy",
            "extract_source": "",
            "id": "55367639b45ff51548a4b58c",
            "indicators": [
                {
                    "_id": "55367639b45ff51548a4b56a",
                    "created": "2015-04-21T16:09:29.291",
                    "description": "",
                    "indicator": "67ecc3b8c6057090c7982883e8d9d0389a8a8f6e8b00f9e9b73c45b008241322",
                    "type": "FileHash-SHA256"
                },
                {
                    "_id": "55367639b45ff51548a4b57b",
                    "created": "2015-04-21T16:09:29.291",
                    "description": "",
                    "indicator": "nato-hq.com",
                    "type": "domain"
                },

            ],
            "modified": "2015-04-21T16:09:29.291000",
            "name": "The Sofacy plot thickens",
            "references": [
                ""
            ],
            "revision": 1.0,
            "tags": [
                "sofacy",
                "fancy bear",
                "apt28",
                "PWC"
            ]
        }
    By default, this redis object will connect to the port 6380 (that is the redis used by the rhythm)
    """

    def __init__(self, host="localhost", port=6380, password=None):
        super(PulseDB, self).__init__(host=host,
                                      port=port,
                                      password=password,
                                      db=PULSE_REDIS_DB,
                                      namespace=PULSE_DB_NAMESPACE,
                                      data_type='zset')

    def merge(self, pulses):
        """Merges the current pulses with the new ones"""
        for pulse in pulses:
            try:
                self.store(pulse['id'], pulse)
            except Exception as err:
                print "Pulse cannot be saved %s" % str(err)
