# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2014 AlienVault
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

"""
Ansible methods to manage Ntop
"""

import api_log

from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.helper import ansible_is_valid_response

# See PEP8 http://legacy.python.org/dev/peps/pep-0008/#global-variable-names
# Pylint thinks that a variable at module scope is a constant
_ansible = Ansible()  # pylint: disable-msg=C0103

def configure_ntop (sensor_ip):
    """
    Configure ntop in a sensor profile.
    """
    try:
        command = "dpkg-reconfigure alienvault-ntop"
        response = _ansible.run_module(host_list=[sensor_ip], module="shell", args=command)
    except Exception, msg:
        api_log.error("Ansible Error: An error occurred while running 'configure_ntop' for sensor %s: %s" % (str(sensor_ip), str(msg)))
        return False, str(msg)

    return ansible_is_valid_response(sensor_ip, response)

