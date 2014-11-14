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
import os, zlib
from binascii import hexlify

#
# LOCAL IMPORTS
#
from Logger import Logger

#
# GLOBAL VARIABLES
#
logger = Logger.logger

def get_file(file, message_base):
    """
    Generate a formatted return message based on the supplied id, and optional
    message.

    This provides a single location for error message management, as well as
    providing the flexibility for custom messaging.
    """

    response = []

    # ensure file exists
    if not os.path.exists(file):
        raise Exception('File does not exist!')

    try:
        # open the file
        f = open(file, 'r')

        # queue it up line by line
        for line in f:
            contents_length = len(line)
            contents_gziphex = hexlify(zlib.compress(line))
            response.append(message_base + ' length="%d" line="%s" ack\n' % (contents_length, contents_gziphex))
                            
        f.close()

    except Exception, e:
        raise Exception(str(e))

    return response;

