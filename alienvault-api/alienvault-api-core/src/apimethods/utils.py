#
#  License:
#
#  Copyright (c) 2013 AlienVault
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


import re
import os
import pwd
import grp
import stat
import socket

from uuid import UUID
from binascii import hexlify
from datetime import datetime
from subprocess import check_call, CalledProcessError

import pytz

import api_log

valid_ip4_cidr_regex = re.compile('^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/(\d|[1-2]\d|3[0-2]))?$')
valid_ossec_agent_id_regex = re.compile('^[0-9]{1,4}$')

BASE_PATH = "/var/alienvault/%s"
BOOL_VALUES = ['0', '1', 'false', 'FALSE', 'False', 'true', 'True', 'TRUE']


def get_float_value_from_string(value):
    """Returns a float value from a given string"""
    floatvalue = 0.0
    try:
        floatvalue = float(value)
    except Exception:
        floatvalue = 0.0
    return floatvalue


def get_uuid_string_from_bytes(uuid_bytes):
    """Returns the uuid canonical string from a set of bytes"""
    if uuid_bytes is not None:
        return str(UUID(bytes=uuid_bytes))
    else:
        return ""


def get_ip_str_from_bytes(ip_bytes):
    ip_str = ""
    if ip_bytes:
        try:
            ip_str = socket.inet_ntop(socket.AF_INET6, ip_bytes)
        except:
            ip_str = socket.inet_ntop(socket.AF_INET, ip_bytes)
    return ip_str


def get_ip_bin_from_str(ip_str):
    ip_bin = None
    if ip_str:
        try:
            ip_bin = socket.inet_pton(socket.AF_INET6, ip_str)
        except:
            ip_bin = socket.inet_pton(socket.AF_INET, ip_str)
    return ip_bin


def get_ip_hex_from_str(ip_str):
    ip_hex = None
    ip_bin = get_ip_bin_from_str(ip_str)
    if ip_bin:
        ip_hex = hexlify(ip_bin)
    return ip_hex


def get_bytes_from_uuid(uuid_str):
    """Returns the uuid bytes from a canonical uuid string"""
    id_str = ""
    try:
        id = UUID(uuid_str)
        id_str = str(id.bytes)
    except Exception, e:
        print "Invalid uuid %s" % str(uuid_str)
    return id_str


def get_hex_string_from_uuid(uuid_str):
    """Returns the uuid bytes from a canonical uuid string"""
    id_str = ""
    try:
        id = UUID(uuid_str)
        id_str = str(id.hex)
    except Exception, e:
        print "Invalid uuid %s" % str(e)
    return id_str


def get_mac_str_from_bytes(mac_bytes):
    """Returns the MAC address from a binary MAC"""
    if mac_bytes is None:
        return ''
    else:
        return (':'.join('%02x' % ord(b) for b in str(mac_bytes))).upper()


def is_json_true(key):
    """
    Checking for json boolean params
    """
    if key is None:
        return False
    return key == '1' or key.lower() == 'true'


def is_json_false(key):
    if key is None:
        return False
    return key == '0' or key.lower() == 'false'


def is_json_boolean(key):
    return is_json_true(key) or is_json_false(key)


def is_valid_ipv4(string_ip):
    """Returns true if the given string is a valid ip v4 address, otherwise returns false"""
    ipv4 = True
    try:
        socket.inet_pton(socket.AF_INET, string_ip)
    except:
        ipv4 = False
    return ipv4


def is_valid_ipv4_cidr(string_cidr):
    """Returns true if the given string is a valid ip v4 CIDR, otherwise returns false"""
    if valid_ip4_cidr_regex.match(string_cidr):
        return True
    return False


def is_valid_uuid(uuid_str):
    """Returns True if the given string is a valid canonical uuid"""
    try:
        UUID(uuid_str)
    except:
        return False
    return True


def get_base_path_from_system_id(system_id):
    return BASE_PATH % system_id.lower()


def is_valid_ossec_agent_id(string_id):
    """Returns true if the given string is a valid Ossec Agent ID, otherwise returns false"""
    if valid_ossec_agent_id_regex.match(string_id):
        return True
    return False


def create_local_directory(path):
    """
    Create local directory with path
    """
    try:
        if not os.path.exists(path):
            os.makedirs(path)
    except Exception, msg:
        api_log.error(str(msg))
        return False, "Something wrong happened creating directory %s" % path

    return True, ""


def set_owner_and_group(user, group, filename):
    try:
        uid = pwd.getpwnam(user).pw_uid
        gid = grp.getgrnam(group).gr_gid
        os.chown(filename, int(uid), int(gid))
    except Exception as err:
        api_log.error("Error setting the owner/group to the file %s <%s>" % (filename, str(err)))
        return False, "Error setting the owner/group to the file %s <%s>" % (filename, str(err))
    return True, ""


def set_file_permissions(filename, mode):
    try:
        os.chmod(filename, mode)
    except Exception as err:
        api_log.error("Error setting the permissions to the file %s <%s>" % (filename, str(err)))
        return False, "Error setting the permissions to the file %s <%s>" % (filename, str(err))
    return True, ""


def set_ossec_file_permissions(filename):
    success, result = set_owner_and_group("avapi", "alienvault", filename)
    if not success:
        return success,result
    # Read/write for user and group
    success, result = set_file_permissions(filename, stat.S_IRGRP | stat.S_IWGRP | stat.S_IRUSR | stat.S_IWUSR)
    if not success:
        return success,result
    return True, ""


def touch_file(path):
    try:
        with open(path, 'a'):
            os.utime(path, None)
    except:
        return False
    return True


def is_valid_integer(value):
    try:
        value = int(value)
    except:
        return False

    return True


def compare_dpkg_version(v1, v2):
    """
        Compare two dpkg version v1 and v2
        Execute on LOCAL!!!!
    """
    try:
        check_call(["dpkg", "--compare-versions", v1, "eq", v2])
        return "equal"
    except CalledProcessError:
        pass
    try:
        check_call(["dpkg", "--compare-versions", v1, "gt", v2])
        return "greater"
    except CalledProcessError:
        pass
    return "less"


def secure_path_join(base_path, *args):
    """ Avoid directory traversal attacks
    """
    try:
        absolute_path = os.path.join(base_path, *args)
        normalized_path = os.path.normpath(absolute_path)

        if not normalized_path.startswith(base_path):
            return False, "Invalid path"
    except Exception as e:
        return False, "Error building path: %s" % str(e)

    return True, normalized_path


def utc_to_local(utc_date, timezone):
    """Transform a utc date to a given timezone date:

     To convert from string to date: datetime.strptime('2015-05-26 15:00:00', '%Y-%m-%d %H:%M:%S')

    Args:
        utc_date(date)   :  Date in UTC format
        timezone(string) :  Local Timezone (IE: "Europe/Madrid")

    Returns:
        date (date) : Date converted to local timezone
    """
    try:
        local_tz = pytz.timezone(timezone)
        local_date = utc_date.replace(tzinfo=pytz.utc).astimezone(local_tz)
    except Exception as err:
        api_log.error("[utc_to_local] There was an error converting the date to local time: %s" % str(err))
        # Saving the utc_date as local date to avoid further errors
        local_date = utc_date

    return local_date


def get_tz_offset(tz):
    """Transform a timezone string to timezone offset:
    Example: "Europe/Madrid" --> "+02:00"

    Args:
        tz(string): Timezone string ('Europe/Madrid')

    Returns:
        format_offset (string): Offset Timezone ('+03:00')
    """
    try:
        offset = datetime.now(pytz.timezone(tz)).strftime('%z')
        regexp = re.compile('(\+|\-)(\d\d)(\d\d)')
        matcher = regexp.search(offset)
        format_offset = "%s%s:%s" % (matcher.group(1), matcher.group(2), matcher.group(3))
    except Exception as err:
        api_log.error("[get_tz_offset] There was an error getting the timezone offset: %s" % str(err))
        format_offset = '+00:00'

    return format_offset
