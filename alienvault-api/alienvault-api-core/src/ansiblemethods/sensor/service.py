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
Ansible methods for host services (suricata, prads and ossec)
"""

import api_log
import re

from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.helper import ansible_is_valid_response

# See PEP8 http://legacy.python.org/dev/peps/pep-0008/#global-variable-names
# Pylint thinks that a variable at module scope is a constant
_ansible = Ansible()  # pylint: disable-msg=C0103


def get_service_status_by_ip(system_ip):
    """Retrieves the processes status for suricata, prads, and ossec
    Args:
        system_ip(str): System IP

    Returns:
        It returns a hastable with the services status

    """
    try:
        processes = {
            'AlienVault_NIDS': 'down',
            'AlienVault_HIDS': 'down',
            'prads': 'down'
        }

        command = "ps ax | egrep 'suricata|prads|ossec' | awk '{print $5}' | grep -v egrep"
        response = _ansible.run_module(host_list=[system_ip], module="shell", args=command)

        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False, msg

        lines = response['contacted'][system_ip]['stdout'].split("\n")

        for line in lines:
            if re.search('suricata', line) is not None:
                processes['AlienVault_NIDS'] = 'up'
            elif re.search('ossec', line) is not None:
                processes['AlienVault_HIDS'] = 'up'
            elif re.search('prads', line) is not None:
                processes['prads'] = 'up'

    except Exception, msg:
        api_log.error("Ansible Error: An error occurred while retrieving processes status for sensor %s: %s" % (str(system_ip), str(msg)))
        return False, str(msg)

    return True, processes
