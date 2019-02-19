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

from ansiblemethods.ansiblemanager import Ansible, PLAYBOOKS
from ansiblemethods.helper import ansible_is_valid_response

ansible = Ansible()

def ansible_launch_compliance_procedure(system_ip):
    """ Launch compliance procedure

    Args:
        system_ip (str): The system ip where the procedure must be run

    Returns:
        success (bool): True if procedure launched OK. False elsewhere.
    """
    # Check parameters
    if not system_ip:
        return False, "[ansible_launch_compliance_procedure] Invalid parameters"

    cmd_args = "echo 'CALL compliance_aggregate();' | /usr/bin/ossim-db"
    response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo=True, args=cmd_args)
    (success, msg) = ansible_is_valid_response(system_ip, response)
    if not success:
        return False, "[ansible_launch_compliance_procedure] Failed to launch compliance procedure: %s" % msg
    if response['contacted'][system_ip]['stderr']:
        return False, "[ansible_launch_compliance_procedure] Error in compliance procedure: %s" % response['contacted'][system_ip]['stderr']
    return True, "[ansible_launch_compliance_procedure] Compliance procedure launch OK"


def remove_old_files(target=None, rm_filter="*", n_days=30):

    evars = {"target": "%s" % target,
             "n_days": "%s" % n_days,
             "filter": "%s" % rm_filter}
    return ansible.run_playbook(playbook=PLAYBOOKS['REMOVE_OLD_FILES'], host_list=[target], extra_vars=evars)

def system_reboot_needed(system_ip):
    """
    Check if the system needs to be rebooted after an update.

    Args:
    system_ip (str): The system ip where the procedure must be run

    Returns:
    'True' if the system needs to be rebooted, 'False' otherwise.
    """
    # Check parameters
    if not system_ip:
        return False, "[system_reboot_needed] Invalid parameters"

    response = ansible.run_module(host_list=[system_ip], module="av_system_reboot_needed", use_sudo=True, args={})
    success, msg = ansible_is_valid_response(system_ip, response)
    if not success:
        return False, "[system_reboot_needed] Something went wrong: %s" % msg

    try:
        needs_reboot = response['contacted'][system_ip]['data']
    except Exception, e:
        return False, "[system_reboot_needed] Something went wrong: %s" % str(e)

    return True, needs_reboot

