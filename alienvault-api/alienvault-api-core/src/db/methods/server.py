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

from uuid import UUID

from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound

import db
import api_log
from db.models.alienvault import System, Server, Config, Server_Hierarchy
from db.methods.system import get_system_ip_from_local

from apimethods.decorators import accepted_values, accepted_types, require_db
from apimethods.utils import get_bytes_from_uuid, get_ip_str_from_bytes, get_uuid_string_from_bytes

from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler
ossim_setup = AVOssimSetupConfigHandler()


@require_db
@accepted_types(UUID)
@accepted_values([], ['str', 'bin'])
def get_system_id_from_server_id(server_id, output='str'):
    """
    Return the system_id of a server using the server_id
    """

    server_id_bin = get_bytes_from_uuid(server_id)

    try:
        system_id = db.session.query(System).filter(System.server_id == server_id_bin).first().id
    except NoResultFound, msg:
        return (False, "No system id found with server id '%s'" % str(server_id))
    except MultipleResultsFound, msg:
        return (False, "More than one system id found with server id '%s'" % str(server_id))
    except Exception, msg:
        return (False, "Error captured while querying for server id '%s': %s" % (str(server_id), str(msg)))

    if output == 'str':
        try:
            system_id_str = get_uuid_string_from_bytes(system_id)
        except Exception, msg:
            return (False, "Cannot convert supposed system id '%s' to its string form: %s" % (str(system_id), str(msg)))
        return (True, system_id_str)

    return (True, system_id)


@require_db(accept_local=True)
@accepted_values([], ['str', 'bin'])
def get_server_ip_from_server_id(server_id, output='str', local_loopback=True):
    """
    Return the ip of a server using the server_id
    """

    try:
        if server_id.lower() == 'local':
            if AVOssimSetupConfigHandler.PROFILE_NAME_SERVER not in ossim_setup.get_general_profile_list():
                return (False, "Local system is not a server")
            (success, server_ip) = get_system_ip_from_local(output='bin', local_loopback=local_loopback)
            if not success:
                return (success, server_ip)
        else:
            server_id_bin = get_bytes_from_uuid(server_id.lower())
            system = db.session.query(System).filter(System.server_id == server_id_bin).first()
            if system:
                if system.ha_ip:
                    server_ip = system.ha_ip
                elif system.vpn_ip:
                    server_ip = system.vpn_ip
                else:
                    server_ip = system.admin_ip
            else:
                return (False, "No server ip address found with server id '%s'" % str(server_id))
    except Exception, msg:
        return (False, "Error captured while querying for server id '%s': %s" % (str(server_id), str(msg)))

    if output == 'str':
        try:
            server_ip_str = get_ip_str_from_bytes(server_ip)
        except Exception, msg:
            return (False, "Cannot convert supposed server ip '%s' to its string form: %s" % (str(server_ip), str(msg)))
        return (True, server_ip_str)

    return (True, server_ip)


@require_db
@accepted_values([], ['str', 'bin'])
def get_server_id_from_local(output='str'):
    """
    Return the system id of the local machine
    """
    try:
        server_id = db.session.query(Config).filter(Config.conf == 'server_id').one().value
    except NoResultFound, msg:
        return (False, "No server ip found for local")
    except MultipleResultsFound, msg:
        return (False, "More than one server ip found for local")
    except Exception, msg:
        return (False, "Error captured while querying for local system id: %s" % str(msg))

    return (True, server_id)


@require_db
def db_get_server(server_id):
    """
    Return the Server db object with server_id
    """
    server = None

    if server_id.lower() == 'local':
        success, server_id = get_server_id_from_local()
        if not success:
            api_log.error(str(server_id))
            return False, server_id

    try:
        server_id_bin = get_bytes_from_uuid(server_id)
        server = db.session.query(Server).filter(Server.id == server_id_bin).one()
    except NoResultFound, msg:
        return (False, "No server entry found with server id '%s'" % str(server_id))
    except MultipleResultsFound, msg:
        return (False, "More than one server found with server id '%s'" % str(server_id))
    except Exception, msg:
        return (False, "Error captured while querying for server id '%s': %s" % (str(server_id), str(msg)))

    return (True, server.serialize)


@require_db
def db_add_child_server(server_id):
    """
    Add a child Server in the server hierarchy
    """
    success, local_server_id = get_server_id_from_local()
    if not success:
        api_log.error(str(server_id))
        return False, server_id

    try:
        hierarchy = Server_Hierarchy()
        hierarchy.parent_id = get_bytes_from_uuid(local_server_id)
        hierarchy.child_id = get_bytes_from_uuid(server_id)
    except Exception, msg:
        api_log.error(str(msg))
        return False, "Error adding the child server"

    try:
        db.session.begin()
        db.session.merge(hierarchy)
        db.session.commit()
    except Exception, e:
        api_log.error(str(e))
        db.session.rollback()
        return (False, 'Something wrong happend while adding the hierarchy into the database')

    return (True, '')
