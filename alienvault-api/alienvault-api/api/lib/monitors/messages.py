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


class Message(object):
    """Message system

    """
    # SYSTEM - RELATED
    # SENSOR - RELATED
    ALIENVAULT_MSG_SENSOR_DROPPED_PACKAGES = 1000
    ALIENVAULT_MSG_SENSOR_IN_SATURATION = 1001

    ALIENVAULT_MSG_DISK_USAGE_LESS_THAN_10 = 2000
    ALIENVAULT_MSG_DISK_USAGE_LESS_THAN_25 = 2001
    # DATABASE - RELATED

class MessageLevels(object):
    """Message Levels
    """
    INFO = 1
    WARNING = 2
    ERROR = 3
    NOTIFICATION = 4


