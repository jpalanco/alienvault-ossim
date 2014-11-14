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
import re, string, struct
from datetime import datetime, timedelta
from pytz import timezone, all_timezones,UnknownTimeZoneError
import pytz
import calendar
from time import time, mktime, gmtime, strftime
from Logger import Logger
logger = Logger.logger



def dumphexdata(data):
    l = len(data)
    offset = 0
    blocks = l / 16
    rest = l % 16
    pchar = string.letters + string.digits + string.punctuation
    for i in range(0, blocks):
        c = "%08x\t" % offset
        da = ""
        for j in range(0, 16):
            (d,) = struct.unpack("B", data[16 * i + j])
            cs = "%02x " % d
            if string.find(pchar, chr(d)) != -1:
                da = da + chr(d)
            else:
                da = da + "."
            c = c + cs
        print c + da
        offset = offset + 16
    da = ""
    c = "%08x\t" % offset
    for i in range(0, rest):
        (d,) = struct.unpack("B", data[blocks * 16 + i])
        cs = "%02x " % d
        if string.find(pchar, chr(d)) != -1:
            da = da + chr (d)
        else:
            da = da + "."
        c = c + cs
    c = c + "   " * (16 - rest) + da + " " * (16 - rest)
    print c



def get_var(regex, line):
    result = re.findall(regex, line)

    if result != []:
        return result[0]

    else:
        return ""



def get_vars(regex, line):
    return re.findall(regex, line)

patternISO_date = re.compile('(?P<year>\d+)[\s-](?P<month>\d+)[\s-](?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)')
patternUTClocalized = re.compile('(?P<year>\d+)[\s-](?P<month>\d+)[\s-](?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)(?P<tzone_symbol>[-|+])(?P<tzone_hour>\d{2}):(?P<tzone_min>\d{2})')

def normalizeToUTCDate(event, used_tzone):
    if used_tzone is None:
        logger.warning("Invalid tzone.....%s",used_tzone)
        return
    if event["fdate"] == "" or event["fdate"] is None:
        logger.debug("Warning: fdate key doesn't exist in event object!")
        return
    plugin_date_str = event["fdate"]
    #2011-02-01 17:00:16
    matchgroup1 = patternISO_date.match(event["fdate"])
    plugin_dt = datetime(year=int(matchgroup1.group("year")), month=int(matchgroup1.group("month")), day=int(matchgroup1.group("day")), hour=int(matchgroup1.group("hour")), minute=int(matchgroup1.group("minute")), second=int(matchgroup1.group("second")))
    logger.debug("Plugin localtime date: %s and used time zone: %s", plugin_dt, used_tzone)

    try:
        plugin_tz = timezone(used_tzone)
    except UnknownTimeZoneError, e:
        logger.info("Error: Unknow tzone, %s may be not valid" % used_tzone)
        plugin_tz = timezone('GMT')

    logger.debug("Plugin tzone: %s" % plugin_tz.zone)
    plugin_localized_date = plugin_tz.localize(plugin_dt)
    logger.debug("Plugin localized time: %s" % plugin_localized_date)
    matchgroup2 = patternUTClocalized.match(str(plugin_localized_date))
    tzone_symbol = matchgroup2.group("tzone_symbol")
    tzone_hour = matchgroup2.group("tzone_hour")
    tzone_min = matchgroup2.group("tzone_min")
    tzone_float = (float(tzone_hour) * 60 + float(tzone_min)) / 60

    if tzone_symbol == "-":
        tzone_float = -1 * tzone_float
    logger.debug("Calculated float timezone: %s" % tzone_float)
    utc_tz = pytz.utc
    plugin_utc_dt = plugin_localized_date.astimezone(utc_tz)
    logger.debug("Plugin UTC Date: %s", plugin_utc_dt)
    dateformat = "%Y-%m-%d %H:%M:%S"
    logger.debug("Plugin UTC ISO Normalized date: %s" % plugin_utc_dt.strftime(dateformat))
    event['tzone'] = tzone_float
    if 'fdate' in event.EVENT_ATTRS:
        event["date"] = calendar.timegm(plugin_utc_dt.timetuple()) #int(mktime(plugin_utc_dt.timetuple()))
        event["fdate"] = plugin_utc_dt.strftime(dateformat)
