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
import datetime
import json
import os
import pickle
import re
import socket
import time
from hashlib import md5

import GeoIP

GEOIPDB = GeoIP.open("/usr/share/geoip/GeoLiteCity.dat", GeoIP.GEOIP_STANDARD)
#
# LOCAL IMPORTS
#

from SiteProtectorMap import *
from NetScreenMap import *
from Logger import Logger

logger = Logger.logger

#
# GLOBAL VARIABLES
#
DEFAULT_ID = '99999'
DATE_FORMAT_FILE_PATH = '/etc/ossim/agent/plugins/date_config/date_formats.json'
CUSTOM_FORMATS_LOADED = False
HOST_RESOLV_CACHE = {}

PROTO_TABLE = {
    '1': 'icmp',
    '6': 'tcp',
    '17': 'udp',
}

HOST_BLACK_LIST = {}

FIXED_MONTH_TRANSLATE = {
    # ENGLISH
    'jan': 1,
    'feb': 2,
    'mar': 3,
    'apr': 4,
    'may': 5,
    'jun': 6,
    'jul': 7,
    'aug': 8,
    'sep': 9,
    'oct': 10,
    'nov': 11,
    'dec': 12,
    'january': 1,
    'february': 2,
    'march': 3,
    'april': 4,
    # 'May':5,
    'june': 6,
    'july': 7,
    'august': 8,
    'september': 9,
    'october': 10,
    'november': 11,
    'december': 12,
    # SPANISH
    'ene': 1,
    # 'feb':2,
    # 'mar':3,
    'abr': 4,
    # 'may':5,
    # 'jun':6,
    # 'jul':7,
    'ago': 8,
    # 'sep':9,
    # 'oct':10,
    # 'nov':11,
    'dic': 12,
    'enero': 1,
    'febrero': 2,
    'marzo': 3,
    'abril': 4,
    'mayo': 5,
    'junio': 6,
    'julio': 7,
    'agosto': 8,
    'septiembre': 9,
    'octubre': 10,
    'noviembre': 11,
    'diciembre': 12
}


# dict of date regexp, keys are ordered by priority.
DATE_REGEXPS = {
    # DC 2/15/2012 12:00:36 PM
    "001 - dc": re.compile(
        r'(?P<month>\d{1,2})/(?P<day>\d{1,2})/(?P<year>\d{4})\s+(?P<hour>\d{1,2}):(?P<minute>\d\d):(?P<second>\d\d)\s+(?P<pm_am>PM|AM)'),
    # Syslog -- Oct 27 10:50:46
    "002 - syslog": re.compile(
        r'^(?P<month>\w+)\s+(?P<day>\d{1,2})\s+(?P<hour>\d{1,2}):(?P<minute>\d\d):(?P<second>\d\d)'),
    # apache-error-log -- Fri Aug 07 17:52:19 2009
    "003 - apache": re.compile(
        r'(\w+)\s+(?P<month>\w+)\s+(?P<day>\d{1,2})\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)\s+(?P<year>\d\d\d\d)'),
    # syslog-ng -- Oct 27 2007 10:50:46
    "004 - syslog-ng": re.compile(
        r'(?P<month>\w+)\s+(?P<day>\d{1,2})\s+(?P<year>\d\d\d\d)\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)'),
    # bind9 -- 10-Aug-2009 07:53:44
    "005 - bind9": re.compile(
        r'(?P<day>\d{1,2})-(?P<month>\w+)-(?P<year>\d\d\d\d)\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)'),
    # Snare -- Sun Jan 28 15:15:32 2007
    "006 - snare": re.compile(
        r'\S+\s+(?P<month>\S+)\s+(?P<day>\d{1,2})\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)\s+(?P<year>\d+)'),
    # snort -- 11/08-19:19:06
    "007 - snort": re.compile(
        r'^(?P<month>\d\d)/(?P<day>\d\d)(/?(?P<year>\d\d))?-(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)'),
    # suricata - 03/20/2012-12:12:24.376349
    "008 - suricata-http": re.compile(
        r'(?P<month>\d+)/(?P<day>\d+)/(?P<year>\d+)-(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # arpwatch -- Monday, March 15, 2004 15:39:19 +0000
    "009 - arpwatch": re.compile(
        r'(\w+), (?P<month>\w+) (?P<day>\d{1,2}), (?P<year>\d{4}) (?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # heartbeat -- 2006/10/19_11:40:05
    # raslog(1581) -- 2009/03/05-11:04:36
    "010 - heartbeat": re.compile(
        r'(?P<year>\d+)/(?P<month>\d+)/(?P<day>\d{1,2})[_-](?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # netgear -- 11/03/2004 19:45:46
    "011 - etgear": re.compile(
        r'(?P<day>\d{1,2})/(?P<month>\d+)/(?P<year>\d{4})\s(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # tarantella -- 2007/10/18 14:38:03
    "012 - tarantella": re.compile(
        r'(?P<year>\d{4})/(?P<month>\d+)/(?P<day>\d{1,2})\s(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # citrix 02/28/2013:12:00:00
    "013 - citrix": re.compile(
        r'(?P<month>(0?[1-9])|(1[0-2]))/(?P<day>(1[0-9])|(2[0-9])|(3[0-1])|(0?[0-9]))/(?P<year>\d{4}):(?P<hour>\d{1,2}):(?P<minute>\d{1,2}):(?P<second>\d{1,2})'),
    # OSSEC -- 2007 Nov 17 06:26:18
    # Intrushield -- 2007-Nov-17 06:26:18 CET
    "014 - ossec": re.compile(
        r'(?P<year>\d{4})[-\s](?P<month>\w{3})[-\s](?P<day>\d{2})\s+(?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})'),
    # ibm applications -- 11/03/07 19:22:22
    # apache -- 29/Jan/2007:17:02:20
    "015 - ibm": re.compile(
        r'(?P<day>\d{1,2})/(?P<month>\w+)/(?P<year>\d+)[\s:](?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # lucent brick hhmmss
    # hhmmss,timestamp
    "016 - lucent1": re.compile(r'^(?P<hour>\d\d)(?P<minute>\d\d)(?P<second>\d\d),(?P<timestamp>\d+)$'),
    "017 - lucent2": re.compile(r'^(?P<hour>\d\d)(?P<minute>\d\d)(?P<second>\d\d)(?:\+|\-)$'),
    "018 - lucent3": re.compile(r'^(?P<hour>\d\d)(?P<minute>\d\d)(?P<second>\d\d)$'),
    # rrd, nagios -- 1162540224
    "019 - rdd": re.compile(r'^(?P<timestamp>\d+)$'),
    # FileZilla -- 11.03.2009 19:45:46
    "020 - FileZilla": re.compile(
        r'(?P<day>\d{1,2})\.(?P<month>\d+)\.(?P<year>\d{4})\s(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # hp eva -- 2 18 2009 14 9 52
    "021 - eva": re.compile(
        r'(?P<month>\d{1,2}) (?P<day>\d{1,2}) (?P<year>\d{4}) (?P<hour>\d{1,2}) (?P<minute>\d{1,2}) (?P<second>\d{1,2})'),
    # Websense -- Wed 14 Apr 2010 12:35:10
    # Websense2 -- 11 Jan 2011 09:44:18 AM
    # nessus  12 May 2012 00:00:03
    "022 - websense2": re.compile(
        r'(?P<day>\d{1,2})\s+(?P<month>\w{3})\s+(?P<year>\d{4})\s+(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d)(\s+(?P<pm_am>AM|PM))?'),
    # Exchange Message Tracking Log -- 2011-07-08T14:13:42.237Z
    "023 - exchange": re.compile(
        r'(?P<year>\d+)-(?P<month>\d+)-(?P<day>\d{1,2})T(?P<hour>\d\d):(?P<minute>\d\d):(?P<second>\d\d).+'),
    # SonicWall -- 2011-05-12 07 59 01
    "024 - sonnicwall": re.compile(
        r'(?P<year>\d{4})-(?P<month>\d+)-(?P<day>\d{1,2})\s(?P<hour>\d+)\s(?P<minute>\d+)\s(?P<second>\d+)'),
    # CSV format date -- 09/30/2011,10:56:11
    "026 - csv": re.compile(
        '(?P<month>[0-9][0-9])/(?P<day>[0-3][0-9])/(?P<year>\d{4})\,(?P<hour>[0-2][0-9]):(?P<minute>[0-6][0-9]):(?P<second>[0-6][0-9])'),
    # honeyd -- 2011-05-17-09:42:24
    "027 - honeyd": re.compile(
        r'(?P<year>\d{4})-(?P<month>\d+)-(?P<day>\d+)-(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # Epilog de logparser 2011-11-21 06: 15:02
    "028 - Epilog": re.compile(
        r'(?P<year>\d{4})-(?P<month>\d+)-(?P<day>\d+)\s+(?P<hour>\d+):\s+(?P<minute>\d+):(?P<second>\d+)'),
    # WMI -- 20111111084344.000000-000
    "029 - wmi": re.compile(
        r'(?P<year>\d{4})(?P<month>\d{2})(?P<day>\d{2})(?P<hour>\d{2})(?P<minute>\d{2})(?P<second>\d{2}).'),
    # 20120202 12:12:12
    "030 - spanish": re.compile(
        r'(?P<year>\d{4})(?P<month>\d{2})(?P<day>\d{2}) (?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})'),
    # SNMPTRAP -- mar 07 feb, 2012 - 08:39:49
    "031 - snmptrap": re.compile(
        r'\S+\s+(?P<day>\d{2})\s(?P<month>\w+),\s(?P<year>\d{4})\s-\s(?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})'),
    # CheckPoint-LML-raw - 1Feb2012;0:05:58/1Feb2012 0:05:58
    "032 - CheckPoint": re.compile(
        r'(?P<day>\d{1,2})(?P<month>\w+)(?P<year>\d{4})(?:\s|;)+(?P<hour>\d{1,2}):(?P<minute>\d{1,2}):(?P<second>\d{1,2})'),
    # Lilian Date -- 11270 02:00:16
    # Lilian is the number of days since the beginning of the Gregorian Calendar on October 15, 1582,
    "033 - lilian": re.compile(
        r'(?P<lilian>(?P<lilian_year>\d{2})(?P<lilian_days>\d+)\s+(?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2}))'),
    "034 - bluecoat": re.compile(
        r'(?P<year>\d{4})-(?P<month>\d+)-(?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    "035 - americanFMT": re.compile(
        "(?P<month>\d{2})\/(?P<day>\d{2})\/(?P<year>\d{2,4})\s+(?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})"),
    # Fortigate date=2015-03-17 time=22:03:55
    "36 - fortigate": re.compile(
        '(?P<year>\d{4})-(?P<month>\d+)-(?P<day>\d+)\s+time=(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)'),
    # Sophos UTM format date -- 2014:09:06-00:00:06
    "37 - Sophos UTM": re.compile(
        '(?P<year>\d{4}):(?:\s)?(?P<month>\d+):(?P<day>\d+)-(?P<hour>\d{1,2}):(?P<minute>\d{1,2}):(?P<second>\d{1,2})'),
}


def load_date_formats_from_file(date_format_file=DATE_FORMAT_FILE_PATH):
    """ Loads custom date format mappings into respective module level constants for further usage.

    Args:
        date_format_file: (str) Path to date formats file. Uses /etc/ossim/agent/plugins/date_formats.json by default.
    """
    global CUSTOM_FORMATS_LOADED, FIXED_MONTH_TRANSLATE, DATE_REGEXPS

    # Skip loading when custom formats are already loaded or when there is no such file.
    if CUSTOM_FORMATS_LOADED or not os.path.exists(date_format_file):
        return

    try:
        custom_date_regexps = {}
        date_fmt = ''
        with open(date_format_file) as date_fmt_file:
            date_formats = json.load(date_fmt_file)
            logger.info('[custom_date_formats] Successfully loaded from "%s"' % date_format_file)
            CUSTOM_FORMATS_LOADED = True
        try:
            for date_fmt, regexp in date_formats.get('DATE_REGEXPS').iteritems():
                custom_date_regexps[date_fmt] = re.compile(regexp)
        except Exception as err:
            logger.error('[custom_date_formats] Failed to compile regexp %s. Reason: %s.'
                         ' Default date formats will be used.' % (date_fmt, str(err)))
        else:
            # Use custom formats if not errors there.
            FIXED_MONTH_TRANSLATE = date_formats.get('FIXED_MONTH_TRANSLATE') or FIXED_MONTH_TRANSLATE
            DATE_REGEXPS = custom_date_regexps
    except Exception as err:
        logger.error('[custom_date_formats] Failed to load date formats from file "%s". '
                     'Reason: %s. Default date formats will be used.' % (date_format_file, str(err)))


# Replaces local date formats with custom ones loaded from file.
load_date_formats_from_file()


"""Set of functions to be used in plugin configuration."""


def geoip_getData(addr, field):
    field_value = ''
    if GEOIPDB is not None:
        record = GEOIPDB.record_by_addr(addr)
        if record:
            field_value = record.get(field, '')  # GEOIPDB record is a python dict
    return field_value


def geoip_getCity(addr):
    return geoip_getData(addr, 'city')


def geoip_getCountryCode(addr):
    return geoip_getData(addr, 'country_code')


def geoip_getCountryCode3(addr):
    return geoip_getData(addr, 'country_code3')


def geoip_getCountryName(addr):
    return geoip_getData(addr, 'country_name')


def geoip_getDmaCode(addr):
    return geoip_getData(addr, 'dma_code')


def geoip_getLatitude(addr):
    return geoip_getData(addr, 'latitude')


def geoip_getLongitude(addr):
    return geoip_getData(addr, 'longitude')


def geoip_getMetroCode(addr):
    return geoip_getData(addr, 'metro_code')


def geoip_getPostalCode(addr):
    return geoip_getData(addr, 'postal_code')


def geoip_getRegionCode(addr):
    return geoip_getData(addr, 'region')


def geoip_getRegionName(addr):
    return geoip_getData(addr, 'region_name')


def geoip_getTimeZone(addr):
    return geoip_getData(addr, 'time_zone')


def resolv(host):
    """Translate a host name to IPv4 address."""
    host = host.lower()
    addr = host
    if HOST_RESOLV_CACHE.has_key(host):
        return HOST_RESOLV_CACHE[host]
    # check if we have the host in my interna cache.
    if host in HostResolv.HOST_RESOLV_DYNAMIC_CACHE:
        return HostResolv.HOST_RESOLV_DYNAMIC_CACHE[host][0]  # returns the first ip assigned to the host.

    try:
        dnsquery = True
        if host in HOST_BLACK_LIST:
            if HOST_BLACK_LIST[host] - time.time() <= 120:  # timeout=5 min
                dnsquery = False
        if dnsquery:
            addr = socket.gethostbyname(host)
            HOST_RESOLV_CACHE[host] = addr
            HOST_BLACK_LIST[host] = time.time()

    except socket.gaierror:
        addr = host
        HOST_BLACK_LIST[host] = time.time()
    return addr


def resolv_ip(addr):
    """Translate an IPv4 address to host name."""

    try:
        (hostname, aliaslist, ipaddrlist) = socket.gethostbyaddr(addr)

    except socket.error:
        return addr

    return hostname


def resolv_port(port):
    """Translate a port name into it's number."""

    try:
        port = socket.getservbyname(port.lower())

    except socket.error:
        return port

    return port


def resolv_iface(iface):
    """Normalize interface name."""

    if re.match("(ext|wan1).*", iface):
        iface = "ext"
    elif re.match("(int|port|dmz|wan).*", iface):
        iface = "int"
    return iface


def md5sum(datastring, plugin_obj_id=0):
    m = md5()
    m.update(datastring)
    return m.hexdigest()


def snort_id(s_id):
    return str(1000 + int(s_id))


def normalize_protocol(protocol):
    """Fill protocols table reading /etc/protocols.

    try:
        fd = open('/etc/protocols')
    except IOError:
        pass
    else:
        pattern = re.compile("(\w+)\s+(\d+)\s+\w+")
        for line in fd.readlines():
            result = pattern.search(line)
            if result:
                proto_name   = result.groups()[0]
                proto_number = result.groups()[1]
                if not proto_table.has_key(proto_number):
                    proto_table[proto_number] = proto_name
        fd.close()
    """
    str_protocol = str(protocol)
    return PROTO_TABLE.get(str_protocol, str_protocol.lower())


def convert_month_name_to_digit(month):
    """ Converts month_name into digit from 1 to 12 respectively or leave it as is if fails to convert.

    Args:
        month: (str) with month name, e.g. 'Nov', 'November', 'noviembre'
    Returns:
        (str) month as digit string.
    """
    try:
        month = time.strftime('%m', time.strptime(month, "%b"))
    except ValueError:
        try:
            month = time.strftime('%m', time.strptime(month, "%B"))
        except ValueError:
            month = str(FIXED_MONTH_TRANSLATE.get(month.lower(), month))
    return month


# normalize_date function #
# convert date strings to isoformat
# you must tag regular expressions with the following names:
# <year>, <month>, <minute>, <hour>, <minute>, <second>
# or <timestamp> for timestamps

def normalize_date_american(string_date):
    date = normalize_date(string_date, True)
    return date


def normalize_date(string, american_format=False):
    """For adding new date formats you should only
    add a new regexp in the above array
    """
    if not isinstance(string, basestring) or string == "":
        return ""

    try_other = True
    date_match_name = ""
    result = None
    current_year, current_month, current_day = time.localtime(time.time())[:3]

    if american_format:
        result = DATE_REGEXPS["035 - americanFMT"].search(string)
        if result:
            date_match_name = "american_syslog"
            try_other = False

    if try_other:
        for name in sorted(DATE_REGEXPS.keys()):
            result = DATE_REGEXPS[name].search(string)
            if result is not None:
                date_match_name = name
                break

    if not result:
        return string

    groups = result.groupdict()

    # put here all sanity transformations you need
    if 'hour' in groups and 'pm_am' in groups:
        try:
            hour = int(groups['hour'])
            if groups['pm_am'].lower() == "pm":
                groups['hour'] = str(0 if hour == 12 else hour + 12)
        except Exception:
            pass

    if 'timestamp' in groups:
        (groups['year'], groups['month'], groups['day'],
         groups['hour'], groups['minute'], groups['second'], a, b, c) = time.localtime(float(groups['timestamp']))

    elif 'lilian' in groups:
        try:
            datetime.datetime.strptime(groups['lilian'], "%y%j %H:%M:%S")
        except ValueError:
            return None

    # Fix year
    year = str(groups.get('year') or current_year)
    if len(year) == 2:
        year = '20%s' % year

    # Fix month
    month = str(groups.get('month') or current_month)
    if not month.isdigit():
        month = convert_month_name_to_digit(month)

    # 31st Dic fix
    try:
        if int(month) == 12 and int(current_month) == 1:
            year = str(int(current_year) - 1)
    except:
        pass

    # end of transformations
    # now, let's go to translate string
    try:
        return datetime.datetime(year=int(year),
                                 month=int(month),
                                 day=int(groups.get('day') or current_day),
                                 hour=int(groups['hour']),
                                 minute=int(groups['minute']),
                                 second=int(groups.get('second') or 0)).isoformat(' ')
    except Exception as err:
        logger.error("There was an error in normalize_date(), "
                     "match_regex:%s function-> InputString: %s Error: %s" % (date_match_name, string, str(err)))

    return string


def upper(string):
    return string.upper()


def sanitize(data):
    return data.replace("\n", "\r")


def hextoint(string):
    try:
        return int(string, 16)

    except ValueError:
        pass


def intrushield_sid(mcafee_sid, mcafee_name):
    # All McAfee Intrushield id are divisible by 256, and this length doesn't fit in OSSIM's table
    mcafee_sid = hextoint(mcafee_sid) / 256
    mcafee_name = mcafee_name.replace('-', ':')

    # Calculate hash based in event name
    mcafee_subsid = abs(mcafee_name.__hash__())

    # Ugly method to avoid duplicated sids
    mcafee_hash2 = 0

    for i in range(0, len(mcafee_name)):
        mcafee_hash2 += ord(mcafee_name[i])

    ossim_sid = int(str(mcafee_hash2)[-1:] + str(int(str(mcafee_subsid)[-7:]) + mcafee_sid))

    return str(ossim_sid)


def netscreen_idp_sid(message):
    return NETSCREEN_IDP_SID_TRANSLATION_TABLE.get(message, DEFAULT_ID)


# Dummy function
def checkValue(val):
    if val is not None and val != 0 and val != "0" and val != "" and val != "" and val != 1 and val != "1":
        return 1

    elif val is not None:
        return 0

    else:
        return None


def iss_siteprotector_sid(message):
    return ISS_SITEPROTECTOR_SID_TRANSLATION_MAP.get(message, DEFAULT_ID)


# Function to translate Windows Security Audit Event Accesses IDs to text
# From http://my.opera.com/Lee_Harvey/blog/2008/10/14/microsoft-windows-security-audit-event-accesses-ids
def translate_wsaea_IDs(string):
    string_translated = ''
    ids = {'%%279': 'Undefined Access (no effect) Bit 7',
           '%%1536': 'Unused message ID',
           '%%1537': 'DELETE',
           '%%1538': 'READ_CONTROL',
           '%%1539': 'WRITE_DAC',
           '%%1540': 'WRITE_OWNER',
           '%%1541': 'SYNCHRONIZE',
           '%%1542': 'ACCESS_SYS_SEC',
           '%%1543': 'MAX_ALLOWED',
           '%%1552': 'Unknown specific access (bit 0)',
           '%%1553': 'Unknown specific access (bit 1)',
           '%%1554': 'Unknown specific access (bit 2)',
           '%%1555': 'Unknown specific access (bit 3)',
           '%%1556': 'Unknown specific access (bit 4)',
           '%%1557': 'Unknown specific access (bit 5)',
           '%%1558': 'Unknown specific access (bit 6)',
           '%%1559': 'Unknown specific access (bit 7)',
           '%%1560': 'Unknown specific access (bit 8)',
           '%%1561': 'Unknown specific access (bit 9)',
           '%%1562': 'Unknown specific access (bit 10)',
           '%%1563': 'Unknown specific access (bit 11)',
           '%%1564': 'Unknown specific access (bit 12)',
           '%%1565': 'Unknown specific access (bit 13)',
           '%%1566': 'Unknown specific access (bit 14)',
           '%%1567': 'Unknown specific access (bit 15)',
           '%%1601': 'Not used',
           '%%1603': 'Assign Primary Token Privilege',
           '%%1604': 'Lock Memory Privilege',
           '%%1605': 'Increase Memory Quota Privilege',
           '%%1606': 'Unsolicited Input Privilege',
           '%%1607': 'Trusted Computer Base Privilege',
           '%%1608': 'Security Privilege',
           '%%1609': 'Take Ownership Privilege',
           '%%1610': 'Load/Unload Driver Privilege',
           '%%1611': 'Profile System Privilege',
           '%%1612': 'Set System Time Privilege',
           '%%1613': 'Profile Single Process Privilege',
           '%%1614': 'Increment Base Priority Privilege',
           '%%1615': 'Create Pagefile Privilege',
           '%%1616': 'Create Permanent Object Privilege',
           '%%1617': 'Backup Privilege',
           '%%1618': 'Restore From Backup Privilege',
           '%%1619': 'Shutdown System Privilege',
           '%%1620': 'Debug Privilege',
           '%%1621': 'View or Change Audit Log Privilege',
           '%%1622': 'Change Hardware Environment Privilege',
           '%%1623': 'Change Notify (and Traverse) Privilege',
           '%%1624': 'Remotely Shut System Down Privilege',
           '%%4352': 'Device Access Bit 0',
           '%%4353': 'Device Access Bit 1',
           '%%4354': 'Device Access Bit 2',
           '%%4355': 'Device Access Bit 3',
           '%%4356': 'Device Access Bit 4',
           '%%4357': 'Device Access Bit 5',
           '%%4358': 'Device Access Bit 6',
           '%%4359': 'Device Access Bit 7',
           '%%4360': 'Device Access Bit 8',
           '%%4361': 'Undefined Access (no effect) Bit 9',
           '%%4362': 'Undefined Access (no effect) Bit 10',
           '%%4363': 'Undefined Access (no effect) Bit 11',
           '%%4364': 'Undefined Access (no effect) Bit 12',
           '%%4365': 'Undefined Access (no effect) Bit 13',
           '%%4366': 'Undefined Access (no effect) Bit 14',
           '%%4367': 'Undefined Access (no effect) Bit 15',
           '%%4368': 'Query directory',
           '%%4369': 'Traverse',
           '%%4370': 'Create object in directory',
           '%%4371': 'Create sub-directory',
           '%%4372': 'Undefined Access (no effect) Bit 4',
           '%%4373': 'Undefined Access (no effect) Bit 5',
           '%%4374': 'Undefined Access (no effect) Bit 6',
           '%%4375': 'Undefined Access (no effect) Bit 7',
           '%%4376': 'Undefined Access (no effect) Bit 8',
           '%%4377': 'Undefined Access (no effect) Bit 9',
           '%%4378': 'Undefined Access (no effect) Bit 10',
           '%%4379': 'Undefined Access (no effect) Bit 11',
           '%%4380': 'Undefined Access (no effect) Bit 12',
           '%%4381': 'Undefined Access (no effect) Bit 13',
           '%%4382': 'Undefined Access (no effect) Bit 14',
           '%%4383': 'Undefined Access (no effect) Bit 15',
           '%%4384': 'Query event state',
           '%%4385': 'Modify event state',
           '%%4386': 'Undefined Access (no effect) Bit 2',
           '%%4387': 'Undefined Access (no effect) Bit 3',
           '%%4388': 'Undefined Access (no effect) Bit 4',
           '%%4389': 'Undefined Access (no effect) Bit 5',
           '%%4390': 'Undefined Access (no effect) Bit 6',
           '%%4391': 'Undefined Access (no effect) Bit 7',
           '%%4392': 'Undefined Access (no effect) Bit 8',
           '%%4393': 'Undefined Access (no effect) Bit 9',
           '%%4394': 'Undefined Access (no effect) Bit 10',
           '%%4395': 'Undefined Access (no effect) Bit 11',
           '%%4396': 'Undefined Access (no effect) Bit 12',
           '%%4397': 'Undefined Access (no effect) Bit 13',
           '%%4398': 'Undefined Access (no effect) Bit 14',
           '%%4399': 'Undefined Access (no effect) Bit 15',
           '%%4416': 'ReadData (or ListDirectory)',
           '%%4417': 'WriteData (or AddFile)',
           '%%4418': 'AppendData (or AddSubdirectory or CreatePipeInstance)',
           '%%4419': 'ReadEA',
           '%%4420': 'WriteEA',
           '%%4421': 'Execute/Traverse',
           '%%4422': 'DeleteChild',
           '%%4423': 'ReadAttributes',
           '%%4424': 'WriteAttributes',
           '%%4425': 'Undefined Access (no effect) Bit 9',
           '%%4426': 'Undefined Access (no effect) Bit 10',
           '%%4427': 'Undefined Access (no effect) Bit 11',
           '%%4428': 'Undefined Access (no effect) Bit 12',
           '%%4429': 'Undefined Access (no effect) Bit 13',
           '%%4430': 'Undefined Access (no effect) Bit 14',
           '%%4431': 'Undefined Access (no effect) Bit 15',
           '%%4432': 'Query key value',
           '%%4433': 'Set key value',
           '%%4434': 'Create sub-key',
           '%%4435': 'Enumerate sub-keys',
           '%%4436': 'Notify about changes to keys',
           '%%4437': 'Create Link',
           '%%4438': 'Undefined Access (no effect) Bit 6',
           '%%4439': 'Undefined Access (no effect) Bit 7',
           '%%4440': 'Undefined Access (no effect) Bit 8',
           '%%4441': 'Undefined Access (no effect) Bit 9',
           '%%4442': 'Undefined Access (no effect) Bit 10',
           '%%4443': 'Undefined Access (no effect) Bit 11',
           '%%4444': 'Undefined Access (no effect) Bit 12',
           '%%4445': 'Undefined Access (no effect) Bit 13',
           '%%4446': 'Undefined Access (no effect) Bit 14',
           '%%4447': 'Undefined Access (no effect) Bit 15',
           '%%4448': 'Query mutant state',
           '%%4449': 'Undefined Access (no effect) Bit 1',
           '%%4450': 'Undefined Access (no effect) Bit 2',
           '%%4451': 'Undefined Access (no effect) Bit 3',
           '%%4452': 'Undefined Access (no effect) Bit 4',
           '%%4453': 'Undefined Access (no effect) Bit 5',
           '%%4454': 'Undefined Access (no effect) Bit 6',
           '%%4455': 'Undefined Access (no effect) Bit 7',
           '%%4456': 'Undefined Access (no effect) Bit 8',
           '%%4457': 'Undefined Access (no effect) Bit 9',
           '%%4458': 'Undefined Access (no effect) Bit 10',
           '%%4459': 'Undefined Access (no effect) Bit 11',
           '%%4460': 'Undefined Access (no effect) Bit 12',
           '%%4461': 'Undefined Access (no effect) Bit 13',
           '%%4462': 'Undefined Access (no effect) Bit 14',
           '%%4463': 'Undefined Access (no effect) Bit 15',
           '%%4464': 'Communicate using port',
           '%%4465': 'Undefined Access (no effect) Bit 1',
           '%%4466': 'Undefined Access (no effect) Bit 2',
           '%%4467': 'Undefined Access (no effect) Bit 3',
           '%%4468': 'Undefined Access (no effect) Bit 4',
           '%%4469': 'Undefined Access (no effect) Bit 5',
           '%%4470': 'Undefined Access (no effect) Bit 6',
           '%%4471': 'Undefined Access (no effect) Bit 7',
           '%%4472': 'Undefined Access (no effect) Bit 8',
           '%%4473': 'Undefined Access (no effect) Bit 9',
           '%%4474': 'Undefined Access (no effect) Bit 10',
           '%%4475': 'Undefined Access (no effect) Bit 11',
           '%%4476': 'Undefined Access (no effect) Bit 12',
           '%%4477': 'Undefined Access (no effect) Bit 13',
           '%%4478': 'Undefined Access (no effect) Bit 14',
           '%%4479': 'Undefined Access (no effect) Bit 15',
           '%%4480': 'Force process termination',
           '%%4481': 'Create new thread in process',
           '%%4482': 'Unused access bit',
           '%%4483': 'Perform virtual memory operation',
           '%%4484': 'Read from process memory',
           '%%4485': 'Write to process memory',
           '%%4486': 'Duplicate handle into or out of process',
           '%%4487': 'Create a subprocess of process',
           '%%4488': 'Set process quotas',
           '%%4489': 'Set process information',
           '%%4490': 'Query process information',
           '%%4491': 'Set process termination port',
           '%%4492': 'Undefined Access (no effect) Bit 12',
           '%%4493': 'Undefined Access (no effect) Bit 13',
           '%%4494': 'Undefined Access (no effect) Bit 14',
           '%%4495': 'Undefined Access (no effect) Bit 15',
           '%%4496': 'Control profile',
           '%%4497': 'Undefined Access (no effect) Bit 1',
           '%%4498': 'Undefined Access (no effect) Bit 2',
           '%%4499': 'Undefined Access (no effect) Bit 3',
           '%%4500': 'Undefined Access (no effect) Bit 4',
           '%%4501': 'Undefined Access (no effect) Bit 5',
           '%%4502': 'Undefined Access (no effect) Bit 6',
           '%%4503': 'Undefined Access (no effect) Bit 7',
           '%%4504': 'Undefined Access (no effect) Bit 8',
           '%%4505': 'Undefined Access (no effect) Bit 9',
           '%%4506': 'Undefined Access (no effect) Bit 10',
           '%%4507': 'Undefined Access (no effect) Bit 11',
           '%%4508': 'Undefined Access (no effect) Bit 12',
           '%%4509': 'Undefined Access (no effect) Bit 13',
           '%%4510': 'Undefined Access (no effect) Bit 14',
           '%%4511': 'Undefined Access (no effect) Bit 15',
           '%%4512': 'Query section state',
           '%%4513': 'Map section for write',
           '%%4514': 'Map section for read',
           '%%4515': 'Map section for execute',
           '%%4516': 'Extend size',
           '%%4517': 'Undefined Access (no effect) Bit 5',
           '%%4518': 'Undefined Access (no effect) Bit 6',
           '%%4519': 'Undefined Access (no effect) Bit 7',
           '%%4520': 'Undefined Access (no effect) Bit 8',
           '%%4521': 'Undefined Access (no effect) Bit 9',
           '%%4522': 'Undefined Access (no effect) Bit 10',
           '%%4523': 'Undefined Access (no effect) Bit 11',
           '%%4524': 'Undefined Access (no effect) Bit 12',
           '%%4525': 'Undefined Access (no effect) Bit 13',
           '%%4526': 'Undefined Access (no effect) Bit 14',
           '%%4527': 'Undefined Access (no effect) Bit 15',
           '%%4528': 'Query semaphore state',
           '%%4529': 'Modify semaphore state',
           '%%4530': 'Undefined Access (no effect) Bit 2',
           '%%4531': 'Undefined Access (no effect) Bit 3',
           '%%4532': 'Undefined Access (no effect) Bit 4',
           '%%4533': 'Undefined Access (no effect) Bit 5',
           '%%4534': 'Undefined Access (no effect) Bit 6',
           '%%4535': 'Undefined Access (no effect) Bit 7',
           '%%4536': 'Undefined Access (no effect) Bit 8',
           '%%4537': 'Undefined Access (no effect) Bit 9',
           '%%4538': 'Undefined Access (no effect) Bit 10',
           '%%4539': 'Undefined Access (no effect) Bit 11',
           '%%4540': 'Undefined Access (no effect) Bit 12',
           '%%4541': 'Undefined Access (no effect) Bit 13',
           '%%4542': 'Undefined Access (no effect) Bit 14',
           '%%4543': 'Undefined Access (no effect) Bit 15',
           '%%4544': 'Use symbolic link',
           '%%4545': 'Undefined Access (no effect) Bit 1',
           '%%4546': 'Undefined Access (no effect) Bit 2',
           '%%4547': 'Undefined Access (no effect) Bit 3',
           '%%4548': 'Undefined Access (no effect) Bit 4',
           '%%4549': 'Undefined Access (no effect) Bit 5',
           '%%4550': 'Undefined Access (no effect) Bit 6',
           '%%4551': 'Undefined Access (no effect) Bit 7',
           '%%4552': 'Undefined Access (no effect) Bit 8',
           '%%4553': 'Undefined Access (no effect) Bit 9',
           '%%4554': 'Undefined Access (no effect) Bit 10',
           '%%4555': 'Undefined Access (no effect) Bit 11',
           '%%4556': 'Undefined Access (no effect) Bit 12',
           '%%4557': 'Undefined Access (no effect) Bit 13',
           '%%4558': 'Undefined Access (no effect) Bit 14',
           '%%4559': 'Undefined Access (no effect) Bit 15',
           '%%4560': 'Force thread termination',
           '%%4561': 'Suspend or resume thread',
           '%%4562': 'Send an alert to thread',
           '%%4563': 'Get thread context',
           '%%4564': 'Set thread context',
           '%%4565': 'Set thread information',
           '%%4566': 'Query thread information',
           '%%4567': 'Assign a token to the thread',
           '%%4568': 'Cause thread to directly impersonate another thread',
           '%%4569': 'Directly impersonate this thread',
           '%%4570': 'Undefined Access (no effect) Bit 10',
           '%%4571': 'Undefined Access (no effect) Bit 11',
           '%%4572': 'Undefined Access (no effect) Bit 12',
           '%%4573': 'Undefined Access (no effect) Bit 13',
           '%%4574': 'Undefined Access (no effect) Bit 14',
           '%%4575': 'Undefined Access (no effect) Bit 15',
           '%%4576': 'Query timer state',
           '%%4577': 'Modify timer state',
           '%%4578': 'Undefined Access (no effect) Bit 2',
           '%%4579': 'Undefined Access (no effect) Bit 3',
           '%%4580': 'Undefined Access (no effect) Bit 4',
           '%%4581': 'Undefined Access (no effect) Bit 5',
           '%%4582': 'Undefined Access (no effect) Bit 6',
           '%%4584': 'Undefined Access (no effect) Bit 8',
           '%%4585': 'Undefined Access (no effect) Bit 9',
           '%%4586': 'Undefined Access (no effect) Bit 10',
           '%%4587': 'Undefined Access (no effect) Bit 11',
           '%%4588': 'Undefined Access (no effect) Bit 12',
           '%%4589': 'Undefined Access (no effect) Bit 13',
           '%%4590': 'Undefined Access (no effect) Bit 14',
           '%%4591': 'Undefined Access (no effect) Bit 15',
           '%%4592': 'AssignAsPrimary',
           '%%4593': 'Duplicate',
           '%%4594': 'Impersonate',
           '%%4595': 'Query',
           '%%4596': 'QuerySource',
           '%%4597': 'AdjustPrivileges',
           '%%4598': 'AdjustGroups',
           '%%4599': 'AdjustDefaultDacl',
           '%%4600': 'Undefined Access (no effect) Bit 8',
           '%%4601': 'Undefined Access (no effect) Bit 9',
           '%%4602': 'Undefined Access (no effect) Bit 10',
           '%%4603': 'Undefined Access (no effect) Bit 11',
           '%%4604': 'Undefined Access (no effect) Bit 12',
           '%%4605': 'Undefined Access (no effect) Bit 13',
           '%%4606': 'Undefined Access (no effect) Bit 14',
           '%%4607': 'Undefined Access (no effect) Bit 15',
           '%%4608': 'Create instance of object type',
           '%%4609': 'Undefined Access (no effect) Bit 1',
           '%%4610': 'Undefined Access (no effect) Bit 2',
           '%%4611': 'Undefined Access (no effect) Bit 3',
           '%%4612': 'Undefined Access (no effect) Bit 4',
           '%%4613': 'Undefined Access (no effect) Bit 5',
           '%%4614': 'Undefined Access (no effect) Bit 6',
           '%%4615': 'Undefined Access (no effect) Bit 7',
           '%%4616': 'Undefined Access (no effect) Bit 8',
           '%%4617': 'Undefined Access (no effect) Bit 9',
           '%%4618': 'Undefined Access (no effect) Bit 10',
           '%%4619': 'Undefined Access (no effect) Bit 11',
           '%%4620': 'Undefined Access (no effect) Bit 12',
           '%%4621': 'Undefined Access (no effect) Bit 13',
           '%%4622': 'Undefined Access (no effect) Bit 14',
           '%%4623': 'Undefined Access (no effect) Bit 15',
           '%%4864': 'Query State',
           '%%4865': 'Modify State',
           '%%5120': 'Channel read message',
           '%%5121': 'Channel write message',
           '%%5122': 'Channel query information',
           '%%5123': 'Channel set information',
           '%%5124': 'Undefined Access (no effect) Bit 4',
           '%%5125': 'Undefined Access (no effect) Bit 5',
           '%%5126': 'Undefined Access (no effect) Bit 6',
           '%%5127': 'Undefined Access (no effect) Bit 7',
           '%%5128': 'Undefined Access (no effect) Bit 8',
           '%%5129': 'Undefined Access (no effect) Bit 9',
           '%%5130': 'Undefined Access (no effect) Bit 10',
           '%%5131': 'Undefined Access (no effect) Bit 11',
           '%%5132': 'Undefined Access (no effect) Bit 12',
           '%%5133': 'Undefined Access (no effect) Bit 13',
           '%%5134': 'Undefined Access (no effect) Bit 14',
           '%%5135': 'Undefined Access (no effect) Bit 15',
           '%%5136': 'Assign process',
           '%%5137': 'Set Attributes',
           '%%5138': 'Query Attributes',
           '%%5139': 'Terminate Job',
           '%%5140': 'Set Security Attributes',
           '%%5141': 'Undefined Access (no effect) Bit 5',
           '%%5142': 'Undefined Access (no effect) Bit 6',
           '%%5143': 'Undefined Access (no effect) Bit 7',
           '%%5144': 'Undefined Access (no effect) Bit 8',
           '%%5145': 'Undefined Access (no effect) Bit 9',
           '%%5146': 'Undefined Access (no effect) Bit 10',
           '%%5147': 'Undefined Access (no effect) Bit 11',
           '%%5148': 'Undefined Access (no effect) Bit 12',
           '%%5149': 'Undefined Access (no effect) Bit 13',
           '%%5150': 'Undefined Access (no effect) Bit 14',
           '%%5151': 'Undefined Access (no effect) Bit 15',
           '%%5376': 'ConnectToServer',
           '%%5377': 'ShutdownServer',
           '%%5378': 'InitializeServer',
           '%%5379': 'CreateDomain',
           '%%5380': 'EnumerateDomains',
           '%%5381': 'LookupDomain',
           '%%5382': 'Undefined Access (no effect) Bit 6',
           '%%5383': 'Undefined Access (no effect) Bit 7',
           '%%5384': 'Undefined Access (no effect) Bit 8',
           '%%5385': 'Undefined Access (no effect) Bit 9',
           '%%5386': 'Undefined Access (no effect) Bit 10',
           '%%5387': 'Undefined Access (no effect) Bit 11',
           '%%5388': 'Undefined Access (no effect) Bit 12',
           '%%5389': 'Undefined Access (no effect) Bit 13',
           '%%5390': 'Undefined Access (no effect) Bit 14',
           '%%5391': 'Undefined Access (no effect) Bit 15',
           '%%5392': 'ReadPasswordParameters',
           '%%5393': 'WritePasswordParameters',
           '%%5394': 'ReadOtherParameters',
           '%%5395': 'WriteOtherParameters',
           '%%5396': 'CreateUser',
           '%%5397': 'CreateGlobalGroup',
           '%%5398': 'CreateLocalGroup',
           '%%5399': 'GetLocalGroupMembership',
           '%%5400': 'ListAccounts',
           '%%5401': 'LookupIDs',
           '%%5402': 'AdministerServer',
           '%%5408': 'ReadInformation',
           '%%5409': 'WriteAccount',
           '%%5410': 'AddMember',
           '%%5411': 'RemoveMember',
           '%%5412': 'ListMembers',
           '%%5424': 'AddMember',
           '%%5425': 'RemoveMember',
           '%%5426': 'ListMembers',
           '%%5427': 'ReadInformation',
           '%%5428': 'WriteAccount',
           '%%5440': 'ReadGeneralInformation',
           '%%5441': 'ReadPreferences',
           '%%5442': 'WritePreferences',
           '%%5443': 'ReadLogon',
           '%%5444': 'ReadAccount',
           '%%5445': 'WriteAccount',
           '%%5446': 'ChangePassword (with knowledge of old password)',
           '%%5447': 'SetPassword (without knowledge of old password)',
           '%%5448': 'ListGroups',
           '%%5449': 'ReadGroupMembership',
           '%%5450': 'ChangeGroupMembership',
           '%%5632': 'View non-sensitive policy information',
           '%%5633': 'View system audit requirements',
           '%%5634': 'Get sensitive policy information',
           '%%5635': 'Modify domain trust relationships',
           '%%5636': 'Create special accounts (for assignment of user rights)',
           '%%5637': 'Create a secret object',
           '%%5638': 'Create a privilege',
           '%%5639': 'Set default quota limits',
           '%%5640': 'Change system audit requirements',
           '%%5641': 'Administer audit log attributes',
           '%%5642': 'Enable/Disable LSA',
           '%%5643': 'Lookup Names/SIDs',
           '%%5648': 'Change secret value',
           '%%5649': 'Query secret value',
           '%%5664': 'Query trusted domain name/SID',
           '%%5665': 'Retrieve the controllers in the trusted domain',
           '%%5666': 'Change the controllers in the trusted domain',
           '%%5667': 'Query the Posix ID offset assigned to the trusted domain',
           '%%5668': 'Change the Posix ID offset assigned to the trusted domain',
           '%%5680': 'Query account information',
           '%%5681': 'Change privileges assigned to account',
           '%%5682': 'Change quotas assigned to account',
           '%%5683': 'Change logon capabilities assigned to account',
           '%%6656': 'Enumerate desktops',
           '%%6657': 'Read attributes',
           '%%6658': 'Access Clipboard',
           '%%6659': 'Create desktop',
           '%%6660': 'Write attributes',
           '%%6661': 'Access global atoms',
           '%%6662': 'Exit windows',
           '%%6663': 'Unused Access Flag',
           '%%6664': 'Include this windowstation in enumerations',
           '%%6665': 'Read screen',
           '%%6672': 'Read Objects',
           '%%6673': 'Create window',
           '%%6674': 'Create menu',
           '%%6675': 'Hook control',
           '%%6676': 'Journal (record)',
           '%%6677': 'Journal (playback)',
           '%%6678': 'Include this desktop in enumerations',
           '%%6679': 'Write objects',
           '%%6680': 'Switch to this desktop',
           '%%6912': 'Administer print server',
           '%%6913': 'Enumerate printers',
           '%%6930': 'Full Control',
           '%%6931': 'Print',
           '%%6948': 'Administer Document',
           '%%7168': 'Connect to service controller',
           '%%7169': 'Create a new service',
           '%%7170': 'Enumerate services',
           '%%7171': 'Lock service database for exclusive access',
           '%%7172': 'Query service database lock state',
           '%%7173': 'Set last-known-good state of service database',
           '%%7184': 'Query service configuration information',
           '%%7185': 'Set service configuration information',
           '%%7186': 'Query status of service',
           '%%7187': 'Enumerate dependencies of service',
           '%%7188': 'Start the service',
           '%%7189': 'Stop the service',
           '%%7190': 'Pause or continue the service',
           '%%7191': 'Query information from service',
           '%%7192': 'Issue service-specific control commands',
           '%%7424': 'DDE Share Read',
           '%%7425': 'DDE Share Write',
           '%%7426': 'DDE Share Initiate Static',
           '%%7427': 'DDE Share Initiate Link',
           '%%7428': 'DDE Share Request',
           '%%7429': 'DDE Share Advise',
           '%%7430': 'DDE Share Poke',
           '%%7431': 'DDE Share Execute',
           '%%7432': 'DDE Share Add Items',
           '%%7433': 'DDE Share List Items',
           '%%7680': 'Create Child',
           '%%7681': 'Delete Child',
           '%%7682': 'List Contents',
           '%%7683': 'Write Self',
           '%%7684': 'Read Property',
           '%%7685': 'Write Property',
           '%%7686': 'Delete Tree',
           '%%7687': 'List Object',
           '%%7688': 'Control Access'}

    for id, text in ids.iteritems():
        if string.find(id) >= 0:
            string_translated = string_translated + text + ", "

    return string_translated


class HostResolv:
    HOST_RESOLV_DYNAMIC_CACHE = {}

    # Dynamic host-ip cache.
    def refreshCache(data):
        """ Refresh the HOST dynamic cache """
        # action="refresh_asset_list" list={ossim-unstable-pro=192.168.2.18,crosa=192.168.2.130} id=all transaction="50653"
        logger.debug("Updating dynamic host cache... %s" % data)
        # HostResolv.HOST_RESOLV_DYNAMIC_CACHE.clear()
        pattern = "action=\"refresh_asset_list\"\s+list={(?P<list>.*)}"
        ipv4_reg = "\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}"
        hostname_valid = "(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])"
        reg_comp = re.compile(pattern)
        res = reg_comp.match(data)
        host_list = []
        new_cache = {}
        if res is not None:
            tmp_list = res.group('list')
            if tmp_list is not None:
                host_list = tmp_list.split(';')
                logger.debug("HOST_LIST: %s" % host_list)
                for asset in host_list:
                    if asset == '':
                        continue
                    ip, hostnames = asset.split('=')
                    hostname_list = hostnames.split(',')
                    logger.debug("IP = %s , hostnamelist: %s" % (ip, hostname_list))
                    for hostname in hostname_list:
                        hostname = hostname.strip()
                        hostname = hostname.lower()
                        if re.match(ipv4_reg, ip) and re.match(hostname_valid, hostname):
                            if new_cache.has_key(hostname):
                                if ip not in new_cache[hostname]:
                                    new_cache[hostname].append(ip)
                            else:
                                new_cache[hostname] = []
                                new_cache[hostname].append(ip)

        HostResolv.HOST_RESOLV_DYNAMIC_CACHE = new_cache

        HostResolv.printCache()
        HostResolv.saveHostCache()

    refreshCache = staticmethod(refreshCache)

    def saveHostCache():
        logger.info("Saving dynamic host cache in /etc/ossim/agent/host_cache.dic")
        pickle.dump(HostResolv.HOST_RESOLV_DYNAMIC_CACHE, open("/etc/ossim/agent/host_cache.dic", "wb"))

    saveHostCache = staticmethod(saveHostCache)

    def loadHostCache():
        if os.path.isfile("/etc/ossim/agent/host_cache.dic"):
            try:
                logger.debug("Loading dynamic host cache from '/etc/ossim/agent/host_cache.dic'")
                HostResolv.HOST_RESOLV_DYNAMIC_CACHE = pickle.load(open("/etc/ossim/agent/host_cache.dic"))
                HostResolv.printCache()
            except:
                logger.warning("Deleting corrupt file host_cache_pro.dic")
                os.remove("/etc/ossim/agent/host_cache_pro.dic")
                return False
        else:
            return False

        return True

    loadHostCache = staticmethod(loadHostCache)

    def printCache():
        logger.debug("------------------ Dynamic cache ---------------------")
        for host, ip in HostResolv.HOST_RESOLV_DYNAMIC_CACHE.items():
            logger.debug("%s  -------->> %s" % (host, ip))
        logger.debug("------------------------------------------------------")

    printCache = staticmethod(printCache)
