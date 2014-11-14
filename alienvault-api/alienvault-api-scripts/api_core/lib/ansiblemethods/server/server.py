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
from ansiblemethods.helper import ansible_is_valid_response
from apimethods.utils import get_hex_string_from_uuid, get_ip_hex_from_str

import api_log

import uuid
import re

ansible = Ansible()


def get_server_stats (host, args = {}):
    return ansible.run_module ([host], 'av_server', args)

def get_remote_server_id_from_server_ip(server_ip):

    cmd = "echo \"select hex(id) from alienvault.server where server.ip=inet6_pton('%s')\" | ossim-db | tail -1" % server_ip
    response = ansible.run_module(host_list=[server_ip],
                                  module="shell",
                                  args=cmd)

    if server_ip in response['dark']:
        return False, "Failed to get the remote server ID. %s" % response['dark'][server_ip]['msg']

    if server_ip not in response['contacted']:
        return False, "Failed to get the remote server ID"

    if 'unreachabe' in response['contacted'][server_ip] and response['contacted'][server_ip]['unreachable'] !=0:
        return False, "Failed to get the remote server ID. Server unreachable"

    if response['contacted'][server_ip]['stderr'] !="":
        return False, "Failed to get the remote server ID. %s" % response['contacted'][server_ip]['stderr']
    uuid_str = response['contacted'][server_ip]['stdout']
    try:
        server_uuid = uuid.UUID(uuid_str)
    except:
        return False, "Failed to get the remote server ID. Invalid UUID"

    return True, str(server_uuid)


def ans_add_server(system_ip, server_id,
                   server_name, server_ip,
                   server_port, server_descr=''):
    """
    Add server entry on system_ip
    """
    hex_server_id = None
    hex_server_ip = None
    try:
        hex_server_id = get_hex_string_from_uuid(server_id)
        hex_server_ip = get_ip_hex_from_str(server_ip)
    except Exception, msg:
        api_log.error(str(msg))
        return False, "[ans_add_server] Bad params: %s" % str(msg)

    cmd = "echo \"INSERT IGNORE INTO alienvault.server (id, name, ip, port, descr) " \
          "VALUES (0x%s, '%s', 0x%s, %d, '%s')\" | ossim-db" % (hex_server_id,
                                                                re.escape(server_name),
                                                                hex_server_ip,
                                                                server_port,
                                                                re.escape(server_descr))
    response = ansible.run_module(host_list=[system_ip],
                                  module="shell",
                                  args=cmd)

    success, msg = ansible_is_valid_response(system_ip, response)
    if not success:
        api_log.error(msg)
        return False, "Error setting server data on remote server"

    if response['contacted'][system_ip]['rc'] != 0:
        api_log.error(response['contacted'][system_ip]['stderr'])
        return False, "Error setting server data on remote server database"

    return True, ''


def ans_add_server_hierarchy(system_ip, parent_id, child_id):
    """
    Add server_hierarchy entry on system_ip
    """
    hex_parent_id = None
    hex_child_id = None
    try:
        hex_parent_id = get_hex_string_from_uuid(parent_id)
        hex_child_id = get_hex_string_from_uuid(child_id)
    except Exception, msg:
        api_log.error(str(msg))
        return False, "[ans_add_server_hierarchy] Bad params: %s" % str(msg)

    cmd = "echo \"INSERT IGNORE INTO alienvault.server_hierarchy (parent_id, child_id) " \
          "VALUES (0x%s, 0x%s)\" | ossim-db" % (hex_parent_id, hex_child_id)
    response = ansible.run_module(host_list=[system_ip],
                                  module="shell",
                                  args=cmd)

    success, msg = ansible_is_valid_response(system_ip, response)
    if not success:
        api_log.error(msg)
        return False, "Error setting server_hierarchy data on remote server"

    if response['contacted'][system_ip]['rc'] != 0:
        api_log.error(response['contacted'][system_ip]['stderr'])
        return False, "Error setting server_hierarchy data on remote server database"

    return True, ''


def ansible_nfsen_reconfigure(system_ip):
    """Runs a nfsen reconfigure
    Args:
      system_ip(str): The system IP where we would like to run the command
    Returns:
      (boolean,int): A tuple containing whether the operation was well or not
    """
    try:
        cmd ='/usr/bin/nfsen reconfig'
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo="True", args=cmd)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        rc = int(response['contacted'][system_ip]['rc'])
        if rc != 0:
            success = False
            rc = response['contacted'][system_ip]['stderr']
    except Exception as exc:
        api_log.error("ansible_nfsen_reconfigure <%s>" % str(exc))
        return False, 0

    return success, rc


