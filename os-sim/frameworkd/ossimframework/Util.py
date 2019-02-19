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
import locale, re
import commands
import socket
from datetime import datetime
from pytz import timezone

#
# LOCAL IMPORTS
#
from Logger import Logger

#
# GLOBAL VARIABLES
#
logger = Logger.logger
PROTO_TABLE = {}
def get_var(regex, line):
    result = re.findall(regex, line)

    if result != []:
        return result[0]

    else:
        return ""

def isIPV4(string_ip):
    ipv4 = True
    try:
        socket.inet_pton(socket.AF_INET, string_ip)
    except:
        ipv4 = False
    return ipv4

def getProtoByNumber(number):
    p = 0
    try:
        p = int(number)
    except Exception,e:
        return number
    if len(PROTO_TABLE) == 0:
        try:
            fd = open('/etc/protocols')
        except:
            pass
        else:
            #load protocols..
            pattern = re.compile("(\w+)\s+(\d+)\s+\w+")
            for line in fd.readlines():
                result = pattern.search(line)
                if not result:
                    continue
                proto_name = result.groups()[0] 
                proto_number = result.groups()[1]
                if not PROTO_TABLE.has_key(proto_number):
                    PROTO_TABLE[proto_number] = proto_name
            fd.close()
    if PROTO_TABLE.has_key(number):
        return PROTO_TABLE[number]
    else:
        return number
    return number

def sanitize(s):
    return re.escape(s) 

def change_datetime_timezone(date_time, from_zone, to_zone):
    """
    Changes datetime timezone

    :param date_time: datetime to convert
    :type date_time: str
    :param from_zone: initial timezone
    :type from_zone: str
    :param to_zone: final timezone
    :type to_zone: str
    :return: datetime converted
    :rtype: str
    """

    from_zone = timezone(from_zone)
    to_zone = timezone(to_zone)

    date_time = datetime.strptime(date_time, '%Y-%m-%d %H:%M:%S')
    date_time = date_time.replace(tzinfo=from_zone)
    date_time = date_time.astimezone(to_zone)

    return date_time.strftime('%Y-%m-%d %H:%M:%S')

