# -*- coding: utf-8 -*-
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

from ansiblemethods.ansiblemanager import Ansible

ansible = Ansible()


def delete_raw_logs(system_ip, start=None, end=None, path="/var/ossim/logs"):
    rc = True
    params = ""
    if start is not None:
        params += "start={} ".format(start)
    if end is not None:
        params += "end={} ".format(end)
    if path is not None:
        params += "path={} ".format(path)
    response = ansible.run_module(host_list=[system_ip], module="av_logger", args=params)
    if system_ip in response['dark'] or response['contacted'][system_ip].get('failed', False) == True:
        # We depend of the error
        if response['dark'].get(system_ip) is not None:
            return False, response['dark'][system_ip]['msg']
        else:
            return False, response['contacted'][system_ip]['msg']
    else:
        return True, response['contacted'][system_ip]
