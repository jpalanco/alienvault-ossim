#  License:
#
#  Copyright (c) 2015 AlienVault
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

import api_log
import uuid
import db.methods
from apimethods.data.idmconn import IDMConnection


def get_host_details(host_id):
    """
    Get the details of a host
    Args:
        host_id (UUID str): Host ID
    Return:
        Tuple (boolean,data)
        - boolean indicates whether the operation was successful or not
        - data will contain the data in case the operation was successful,
          or the error string otherwise
    """

    return db.methods.host.get_host_by_host_id(host_id)


def get_host_details_list():
    """
    Get the list of hosts
    Args:
    Return:
        Tuple (boolean,data)
        - boolean indicates whether the operation was successful or not
        - data will contain the data in case the operation was successful,
          or the error string otherwise
    """

    host_ids = []
    rc = False
    try:
        rc, host_ids = db.methods.host.get_all_hosts()
    except Exception, msg:
        api_log.error("Error retrieving the list of hosts: %s" % str(msg))
        return False, host_ids

    hosts_output = {}
    if rc:
        for host_id in host_ids:
            host_data = {}
            try:
                rc_det, host_data = get_host_details(host_id)
                if rc_det:
                    hosts_output[host_id] = host_data
            except Exception, msg:
                api_log.error("Error retrieving host details for %s: %s" % (host_id, str(msg)))

    return True, hosts_output


def refresh_hosts():
    """
    Send reload message to the Server
    Args:
    Return:
        - boolean indicates whether the operation was successful or not
    """
    result = True

    conn = IDMConnection(port=40001)
    if conn.connect():
        conn.reload_hosts()
        conn.close()
    else:
        api_log.error('Cannot send host refresh to server')
        result = False

    return result


# def create_host(ctx, hostname, ips, sensors, fqdns, asset_value, threshold_c, threshold_a, alert, persistence, nat,
#                 rrd_profile, desc, lat, lon, icon, country, external_host, permissions, av_component):
#     """
#     Creates a new host
#     Args:
#     Return:
#         Tuple (boolean,data)
#         - boolean indicates whether the operation was successful or not
#         - data will contain the data in case the operation was successful,
#           or the error string otherwise
#     """
#     # Generates host_id
#     host_id = str(uuid.uuid1())

#     # Now save the host in the DB
#     rc, data = db_add_host(host_id=host_id,
#                            ctx=ctx,
#                            hostname=hostname,
#                            ips=ips,
#                            sensors=sensors,
#                            fqdns=fqdns,
#                            asset_value=asset_value,
#                            threshold_c=threshold_c,
#                            threshold_a=threshold_a,
#                            alert=alert,
#                            persistence=persistence,
#                            nat=nat,
#                            rrd_profile=rrd_profile,
#                            desc=desc,
#                            lat=lat,
#                            lon=lon,
#                            icon=icon,
#                            country=country,
#                            external_host=external_host,
#                            permissions=permissions,
#                            av_component=av_component)

#     if not rc:
#         return False, data

#     host_creation_output = {'host_id': host_id}
#     return True, host_creation_output


# def modify_host_details(host_id):
#     """
#     Modifies an existing host
#     Args:
#         host_id (UUID str): Host ID
#     Return:
#         Tuple (boolean,data)
#         - boolean indicates whether the operation was successful or not
#         - data will contain the data in case the operation was successful,
#           or the error string otherwise
#     """
