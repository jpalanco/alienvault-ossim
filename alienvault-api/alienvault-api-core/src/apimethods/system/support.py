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
"""
    Apimethods to support routines
"""
import api_log
from db.methods.system import get_system_ip_from_system_id
from ansiblemethods.system.support import connect_tunnel as ans_connect_tunnel
from ansiblemethods.system.support import status_tunnel as ans_status_tunnel
from ansiblemethods.system.support import delete_tunnel as ans_delete_tunnel
from apimethods.system.cache import use_cache


def connect_tunnel(system_id, case_id):
    """
        Enable the reverse tunnel on the
    """
    (success, system_ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret
    (success, ret) = ans_connect_tunnel(system_ip, case_id)
    if not success:
        api_log.error("system:  connect_tunnel: " + str(ret))
        return False, str(ret)
    (succes, result) = ret = status_tunnel(system_id, no_cache=True)
    if not success:
        api_log.error("system: status_tunnel: " + str(result))
        return ret
    return True, ''


@use_cache(namespace="support_tunnel")
def status_tunnel(system_id, no_cache=False):
    """
        Get the status of tunnels in system :system_id:
    """
    (success, system_ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret
    (success, ret) = ans_status_tunnel(system_ip)
    if not success:
        api_log.error("system: status_tunnel: " + str(ret))
        return False, str(ret)
    return True, ret


def delete_tunnel(system_id):
    """
        Delete all tunnels or the tunnel with :tunnel_pid: in
        system identified by :system_id:
    """
    (success, system_ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret
    (success, result) = ret = ans_delete_tunnel(system_ip)
    if not success:
        api_log.error("system: delete_tunnel: " + str(result))
        return ret
    (succes, result) = ret = status_tunnel(system_id, no_cache=True)
    if not success:
        api_log.error("system: status_tunnel: " + str(result))
        return ret
    return True, ''
