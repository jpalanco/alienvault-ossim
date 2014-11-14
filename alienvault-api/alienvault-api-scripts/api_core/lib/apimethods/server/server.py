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


from db.methods.system import get_system_id_from_local
from db.methods.server import get_server_ip_from_server_id
from db.methods.server import get_system_id_from_server_id
from apimethods.utils import is_valid_ipv4
from ansiblemethods.system.system import ansible_add_system
from ansiblemethods.server.server import get_remote_server_id_from_server_ip, ansible_nfsen_reconfigure
from apimethods.utils import  get_base_path_from_system_id
import api_log

def add_server(server_ip, password):
    """
    Add a new system.
    """
    if not is_valid_ipv4(server_ip):
        return False, "Invalid IP format: %s" % server_ip
    (success, local_system_id) = get_system_id_from_local()
    if not success:
        return success, "Error retrieving the local system id"

    (success, response) = ansible_add_system(local_system_id=local_system_id,
                                             remote_system_ip=server_ip,
                                             password=password)
    if not success:
        return success, "Cannot add the server to the system"

    (success, response) = get_remote_server_id_from_server_ip(server_ip)

    return (success, response)


def get_base_path_from_server_id(server_id):
    """ Get base path from server ID

    Args:
        server_id (str): Server ID

    Returns:
        String with the corresponding base path
    """

    if server_id == 'local':
        rt, system_id = get_system_id_from_local()
        if not rt:
            return False, "Can't retrieve the system id"
        return True, get_base_path_from_system_id(system_id)

    rt, system_id = get_system_id_from_server_id(server_id)
    if not rt:
        return False, "Can't retrieve the system id for server id %s: %s" % (server_id, system_id)
    return True, get_base_path_from_system_id(system_id)


def apimethod_run_nfsen_reconfig(server_id):
    """Runs a nfsen reconfig
    Args:
      server_id(str): the server uuid or local
    """
    success, system_ip = get_server_ip_from_server_id(server_id)
    if not success:
        return False, "Cannot retrieve the system ip from a the given server id: <%s>" % str(system_ip)
    return ansible_nfsen_reconfigure(system_ip)
