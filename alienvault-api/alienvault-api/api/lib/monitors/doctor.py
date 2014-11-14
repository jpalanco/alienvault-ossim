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

from api.lib.monitors.monitor import Monitor, MonitorTypes, ComponentTypes
from api import db
from apimethods.utils import get_uuid_string_from_bytes, get_ip_str_from_bytes, get_bytes_from_uuid

from ansiblemethods.system.system import get_doctor_data

import time
import subprocess

#class MonitorDiskUsage(Monitor):
#    """
#    Monitor disk usage in the local server.
#    """
#
#    def __init__(self):
#        Monitor.__init__(self, MonitorTypes.SYSTEM_DISK_USAGE)
#        self.message = 'Disk Usage Monitor Enabled'
#
#    def start(self):
#        """
#        Starts the monitor activity
#
#        :return: True on success, False otherwise
#        """
#
#        # Find the local server.
#        system_list = []
#        try:
#            system_list = Avcenter_Current_Local.query.all()
#        except Exception, msg:
#            db.session.rollback()
#            return False
#
#        args = {}
#        args['plugin_list'] = 'disk_usage.plg'
#        args['output_type'] = 'ansible'
#        for system in system_list:
#            system_ip = system.vpn_ip
#            if system_ip == "":
#                system_ip = system.admin_ip
#            ansible_output = get_doctor_data([system_ip], args)
#            print ansible_output
#        if ansible_output['dark'] != {}:
#            return False
#        try:
#            disk_usage_results = ansible_output['contacted'][system_ip]['data'][0]['checks']
#        except Exception, msg:
#            return False
#
#        return True

