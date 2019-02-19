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
"""
    Support routines exec by Ansible
"""
import re
from ansiblemethods.ansiblemanager import Ansible, PLAYBOOKS
from ansiblemethods.helper import ansible_is_valid_playbook_response
from ansiblemethods.system.system import ansible_pgrep, ansible_pkill
import api_log
ansible = Ansible()


def connect_tunnel(system_ip, case_id):
    """
        Connect to :system_ip: and enable the reverse tunnel
        with case :case_id:
    """
    evars = {'ca_root': '/etc/ansible/playbooks/cacert.pem',
             'remote_server': 'tractorbeam.alienvault.com',
             'remote_port': '443',
             'remote_user': 'support',
             'case_id': case_id,
             'target': system_ip}

    response = ansible.run_playbook(playbook=PLAYBOOKS['ENABLE_TUNNEL'],
                                    host_list=[system_ip],
                                    extra_vars=evars,
                                    use_sudo=True)
    success, msg = ansible_is_valid_playbook_response(system_ip, response)
    if not success:
        # Log all the error to api_log
        # First
        api_log.error("ERROR: ansible.run_playbook " + msg)
        try:
            err = response['alienvault']['lasterror']
            if type(err) == dict:
                return False, response['alienvault']['lasterror'][system_ip]['msg']
            else:
                return False, msg
        except KeyError:
            return False, msg
    else:
        return True, ''


def status_tunnel(system_ip):
    """
        Connect to :system_ip:, and check for the ssh that launch the
        tunnels. The command line is like  this:
        ssh -o StrictHostKeyChecking=no,ServerAliveInterval=120 -fNnT -R 5001:localhost:22 -i /tmp/ansible.qZa0182fr support@tractorbeam.alienvault.com'), (u'32755', u'ssh -o StrictHostKeyChecking=no -fN -R 5001:localhost:22 -i /tmp/ansible.qZa0182fr support@tractorbeam.alienvault.com')]


    """
    success, pidlist = ret = ansible_pgrep(system_ip, r"ssh\s+-o\s+StrictHostKeyChecking=no\s+-fNnT\s+-R\s+[0-9]+:localhost:(22|443)\s+.*?\s+support@tractorbeam.alienvault.com")
    if not success:
        return ret
    # return a list tuple with (pid, remote_port, http / ssh)
    result = []
    if len(pidlist) > 0:
        for pid, match in [(x[0], re.match(r'.*?\s+(\d+):localhost:(\d+)\s+.*', x[1])) for x in pidlist]:
            if match:
                result.append({"pid": pid, "remote_port": match.group(1), "channel": "ssh" if match.group(2) == "22" else "http"})
    return True, result


def delete_tunnel(system_ip):
    """
        Stop the tunnel in system_ip. Also we must REMOVE the generate rsa keys
    """
    (success, result) = ret = ansible_pkill(system_ip, r"ssh\s+-o\s+StrictHostKeyChecking=no\s+-fNnT\s+-R\s+[0-9]+:localhost:(22|443)\s+.*?\s+support@tractorbeam.alienvault.com")
    if not success:
        return ret
    evars = {
        'target': system_ip}

    response = ansible.run_playbook(playbook=PLAYBOOKS['DISABLE_TUNNEL'],
                                    host_list=[system_ip],
                                    extra_vars=evars,
                                    use_sudo=True)
    success, msg = ansible_is_valid_playbook_response(system_ip, response)
    if not success:
        return False, msg
    else:
        return True, ''


def check_support_tunnels(system_ip):
    """
        Check the tunnels in machine :system_ip:
    """
    success, tunnels = ret = status_tunnel(system_ip)
    if not success:
        return ret
    if len(tunnels) > 0:  # Tunnels UP
        return True, "tunnel(s) up"
    # Okey tunnels down, I'm not going to check
    # if user / keys exists. Directy clean the remote system?
    evars = {
        'target': system_ip}
    response = ansible.run_playbook(playbook=PLAYBOOKS['DISABLE_TUNNEL'],
                                    host_list=[system_ip],
                                    extra_vars=evars,
                                    use_sudo=True)
    success, msg = ansible_is_valid_playbook_response(system_ip, response)
    if not success:
        return False, msg
    else:
        return True, 'Clean up ok'
