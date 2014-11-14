#!/usr/bin/env python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2013 AlienVault
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
'''
ALIENVAULT_API_PATHS
'''
CURRENT_API_VERSION = "1.0"

PATH_ROOT ="/av/api/%s/" % CURRENT_API_VERSION

#CONFIGURATION PATHS
PATH_CONFIG_SENSORS = PATH_ROOT + 'config/sensors'
PATH_CONFIG_SYSTEM = PATH_ROOT + 'config/system'
PATH_CONFIG_SERVER = PATH_ROOT + 'config/server'

#DATA PATHS
PATH_DATA_EVENTS= PATH_ROOT + 'data/events'
PATH_DATA_MESSAGES = PATH_ROOT + 'data/messages'

#APPS PATHS
PATH_APPS_BACKUP = PATH_ROOT + 'apps/backup'
PATH_APPS_DOCTOR = PATH_ROOT + 'apps/doctor'