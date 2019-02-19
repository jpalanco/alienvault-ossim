# -*- coding: utf-8 -*-
#
# License:
#
#  Copyright (c) 2015 AlienVault
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

import redis
import api_log

class PulseCorrelationDB (object):
    """
    Convert OTX data to a format more friendly to AVRhythm.
    """
    def __init__(self,
                 unix_socket_path="/var/run/redis/redis-server-otx.sock"):
        self.__unix_socket_path = unix_socket_path

        # Please note that IoC types have a positional meaning, so each tuple
        # in this list is linked to a Redis database, according to its
        # position.
        #
        # If you are looking at the alienvault-rhythm code, below you can
        # find the relationships between the rhythm types, and the
        # Redis databases:
        #  * ('IPv4', 'CIDR') -> IP_ADDRESS
        #  * ('FileHash-*') -> FILE_HASH
        #  * ('hostname', 'domain') -> DNS
        #  * ('hostname') -> HTTP
        #
        self.__ioc_types = [('IPv4',
                             'CIDR'),
                            ('FileHash-IMPHASH',
                             'FileHash-MD5',
                             'FileHash-PEHASH',
                             'FileHash-SHA1',
                             'FileHash-SHA256'),
                            ('hostname',
                             'domain'),
                            ('hostname')]
        self.__iocs_by_conn = []

        try:
            self.__iocs_by_conn = [{'ioc_type': ioc_type,
                                    'db': index,
                                    'pipeline': redis.Redis(
                                        unix_socket_path=self.__unix_socket_path,
                                        db=index).pipeline()}
             for index, ioc_type in enumerate(self.__ioc_types)]
        except Exception:
            return

    def purge(self, db=0):
        """
        USE WITH CAUTION!!!
        Will purge all data in 'db'.
        """
        try:
            self.__iocs_by_conn[db]['pipeline'].flushdb()
            self.__iocs_by_conn[db]['pipeline'].execute()
        except:
            pass

    def purge_all(self):
        """
        USE WITH CAUTION!!!
        Will purge all data from *all* databases.
        """
        try:
            self.__iocs_by_conn[0]['pipeline'].flushall()
            self.__iocs_by_conn[0]['pipeline'].execute()
        except:
            pass

    def store(self, pulses=[]):
        """
        Stores OTX data, using the IoC as key, and the Pulse ID list as
        members.
        """
        for pulse in pulses:
            for ioc in pulse['indicators']:
                try:
                    ioc_by_conn = next(
                        (x for x in self.__iocs_by_conn
                         if ioc['type'] in x['ioc_type']),
                        None)
                    if ioc_by_conn:
                        ioc_by_conn['pipeline'].sadd(
                            ioc['indicator'],
                            pulse['id'])
                except:
                    pass

        for ioc_by_conn in self.__iocs_by_conn:
            try:
                ioc_by_conn['pipeline'].execute()
            except Exception as e:
                api_log.error('Failure while saving data in redis-server: {0}'.format(str(e)))

    def delete_pulse(self, pulse):
        """Delete all the pulse data from the DB.
        Args:
            pulse(dict): Pulse Dict
        Returns:
            void.
        """
        for ioc in pulse['indicators']:
            try:
                ioc_by_conn = next(
                    (x for x in self.__iocs_by_conn
                     if ioc['type'] in x['ioc_type']),
                    None)
                if ioc_by_conn:
                    ioc_by_conn['pipeline'].srem(
                        ioc['indicator'],
                        pulse['id'])
            except:
                pass

        for ioc_by_conn in self.__iocs_by_conn:
            try:
                ioc_by_conn['pipeline'].execute()
            except Exception as e:
                api_log.error('Failure while saving data in redis-server: {0}'.format(str(e)))

    def sync(self):
        """
        Sends a 'sync' command to indicate that all the pulses are downloaded & stored.
        """
        for ioc_by_conn in self.__iocs_by_conn:
            try:
                ioc_by_conn['pipeline'].publish('__keyspace@%d__:cmd' % ioc_by_conn['db'], 'sync')
                ioc_by_conn['pipeline'].execute()
            except Exception, msg:
                api_log.error('Failure while syncing data in redis-server: {0}'.format(str(msg)))
