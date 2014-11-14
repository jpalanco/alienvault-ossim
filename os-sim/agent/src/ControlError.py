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
# GLOBAL VARIABLES
#

ERROR_CODE_MAP = {
    0: "Success.", \
    1: "Exception:", \
    1000: "Unsupported control action.", \
    1001: "Unable to find path variable.", \
    1002: "Requested backup file is not available for restore.", \
    1003: "Unable to find path or type parameter.", \
    1004: "Unable to find length or contents parameter for write operation.", \
    1005: "Decoded contents length does not match the specified length.", \
    2001: "Scan is already in progress.", \
    2002: "Unable to find the target parameter.", \
    2003: "Unable to generate report.", \
    2004: "Unable to find the requested report.", \
    2005: "The requested report is that we are working now", \
    2006: "Custom scan needs a port list parameter", \
    2007: "Report prefix it's mandatory", \
    3001: "Net scan is already in progress", \
    3002: "Invalid device parameter", \
    3003: "Unable to generate pcap file", \
    3004: "Unable to find the requested pcap.", \
    3005: "The requested pcap is that we are working now", \
    3006: "Low disk space (< 5GB available)", \
    3007: "Scan job already finished", \
}



def get(id, message=""):
    """
    Generate a formatted return message based on the supplied id, and optional
    message.

    This provides a single location for error message management, as well as
    providing the flexibility for custom messaging.

    """

    err_message = ""

    if ERROR_CODE_MAP.has_key(id):
        if message != "":
            err_message += 'errno="%d" error="%s %s"' % (id, ERROR_CODE_MAP[id], message)

        else:
            err_message += 'errno="%d" error="%s"' % (id, ERROR_CODE_MAP[id])

    elif message != "":
        err_message += 'errno="%d" error="%s"' % (id, message)

    else:
        err_message += 'errno="-1" error="Unknown error encountered!"'

    return err_message


