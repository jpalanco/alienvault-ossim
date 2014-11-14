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
import locale, re
import commands

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

def get_vars(regex, line):
    return re.findall(regex, line)


def isIpInNet(host, net_list):
    ipv4_regex= "^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$"
    if not re.match(ipv4_regex, host):
        logger.warning("isIpInNet - Invalid ip format: %s" % host)
        return False
    if type(net_list) is not list:
        return False

    for net in net_list:

        if net == 'ANY':
            return True

        if net.count('/') != 1:
            logger.debug("Don't know what to do with malformed net (%s)" % (net))
            continue

        (base, mask) = net.split('/')
        b = base.split('.')
        h = host.split('.')

        if len(b) != 4 or len(h) != 4:
            continue

        val1 = int(b[0])*256*256*256 +\
               int(b[1])*256*256 +\
               int(b[2])*256 +\
               int(b[3])
        val2 = int(h[0])*256*256*256 +\
               int(h[1])*256*256 +\
               int(h[2])*256 +\
               int(h[3])

        if ((val1 >> (32 - int(mask))) == (val2 >> (32 - int(mask)))):
            return True

    return False

def getHostThreshold(conn,host,type):
    if type == "C":
        query = "SELECT threshold_c FROM host WHERE id = unhex('%s');" % (host)
        value = "threshold_c"
    else: 
        query = "SELECT threshold_a FROM host WHERE id = unhex('%s');" % (host)
        value = "threshold_a"
    result = conn.exec_query(query)
    if result:
        return result[0][value]
        # return this value
    else:
        net = getClosestNet(conn,host)
        threshold = getNetThreshold(conn,net,value)
        # return this value or a default global value
        return threshold

def getNetThreshold(conn,net,type):
    query = "SELECT %s FROM net WHERE name = '%s';" % (type,net)
    result = conn.exec_query(query)
    if result:
        return int(result[0][type])
    else:
        from OssimConf import OssimConf
        conf = OssimConf ()
        return int(conf["threshold"])

def getNetAsset(conn,net_id):
    """Returns the asset value for the specified net_id
    """
    query = "SELECT asset FROM net WHERE id = unhex('%s');" % (net_id)
    result = conn.exec_query(query)
    if result:
        return int(result[0]["asset"])
    else:
        return 0

def getHostAsset(conn,host_id):
    """Returns the host asset from a specified host_id
    """
    query = "SELECT asset FROM host WHERE id = unhex('%s');" % (host_id)
    result = conn.exec_query(query)
    if result:
        return int(result[0]["asset"])
    else:
        return False

def getClosestNet(conn,host):

    net_list = []
    query = "SELECT hex(id) id, name,ips FROM net;" 
    net_list = conn.exec_query(query)

    narrowest_mask = 0
    narrowest_net = ""

    for net in net_list:
        if net["ips"].count('/') != 1:
            logger.debug("Don't know what to do with malformed net (%s)" % (net["ips"]))
            continue

        (base, mask) = net["ips"].split('/')
        b = base.split('.')
        h = host.split('.')

        if len(b) != 4 or len(h) != 4:
            continue

        val1 = int(b[0])*256*256*256 +\
               int(b[1])*256*256 +\
               int(b[2])*256 +\
               int(b[3])
        val2 = int(h[0])*256*256*256 +\
               int(h[1])*256*256 +\
               int(h[2])*256 +\
               int(h[3])

        if ((val1 >> (32 - int(mask))) == (val2 >> (32 - int(mask)))):
            if int(mask) > int(narrowest_mask):
                narrowest_mask = mask
                narrowest_net = net["id"]
        if narrowest_mask > 0:
            return narrowest_net
    return False


def getLocaleFloat(value):
    # set sane default return
    ret = 0
    if isinstance(value, str):
        try:
            locale.setlocale(locale.LC_ALL, '')
            ret = locale.atof(value)
        except:
            try:
                ret = float(value)
            except:
                logger.warning("Translation did not work.")

    else:
        logger.debug("No locale conversion to float available for type %s" % str(type(value)))

    return ret

def asLocaleStr(value):
    try:
        locale.setlocale(locale.LC_ALL, '')

        if isinstance(value, int):
            return locale.str(value)
        elif isinstance(value, float):
            return locale.str(value)
        else:
            logger.debug("No locale conversion to string available for type %s" % str(type(value)))

    except:
        logger.warning("Locale translation did not work.")

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

def get_my_component_id():
    """Request for my component uuid
    """
    com = '/usr/bin/alienvault-system-id'
    result = commands.getstatusoutput(com)
    if result[0] == 0: #return ok
        return result[1]
    return None
def sanitize(s):
    return re.escape(s) 
    #return s.replace("'", "'\\''")
# vim:ts=4  sts=4 tw=79 expandtab


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

