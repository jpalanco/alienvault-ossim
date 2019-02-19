# -*- coding: utf-8 -*-
#
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

from sqlalchemy.orm.exc import (NoResultFound,
                                MultipleResultsFound)

import db
import uuid

from db.models.alienvault import (
    Host,
    Host_Scan,
    Host_Ip,
    Host_Sensor_Reference,
    Host_Net_Reference
)

# Importing full package to avoid circular imports
import apimethods

from apimethods.utils import (
    get_bytes_from_uuid,
    get_uuid_string_from_bytes,
    get_ip_str_from_bytes,
    get_ip_bin_from_str,
    is_valid_uuid,
    get_hex_string_from_uuid
)

from apimethods.decorators import (
    accepted_values,
    accepted_types,
    require_db
)

from db.methods.sensor import get_sensor_ctx_by_sensor_id

from apiexceptions.asset import APICannotGetAssetName

import api_log


@require_db
def get_host_by_host_id(host_id):
    """
    Returns a Host object given its host_id
    Args:
        host_id (uuid): Host ID
    Return:
        Tuple (boolean,data)
        - boolean indicates whether the operation was successful or not
        - data will contain the data in case the operation was successful,
          or the error string otherwise
    """

    host_id_bin = get_bytes_from_uuid(host_id)

    try:
        host = db.session.query(Host).filter(Host.id == host_id_bin).one()
    except NoResultFound:
        return True, None
    except Exception as err_detail:
        return False, "Error captured while querying for host id '%s': %s" % (str(host_id), str(err_detail))

    # Build the output
    host_output = {}
    if host is not None:

        host_dict = host.__dict__
        for key, value in host_dict.iteritems():
            if key in ('_sa_instance_state',):
                continue
            if key in ('ctx', 'id'):
                host_output[key] = get_uuid_string_from_bytes(value)
                continue
            if key == "permissions":
                host_output[key] = str(value)
            if key == 'asset':
                host_output['asset_value'] = value
            else:
                host_output[key] = value

        host_output['os'] = ""
        host_output['model'] = ""
        for host_property in host.host_properties:
            if host_property.property_ref == 3:
                host_output['os'] = host_property.value
                continue
            if host_property.property_ref == 14:
                host_output['model'] = host_property.value
                continue

        host_output['ips'] = [get_ip_str_from_bytes(x.ip) for x in host.host_ips]
        host_output['sensors'] = [get_uuid_string_from_bytes(x.sensor_id) for x in host.host_sensor_reference]
        host_output['services'] = [x.service for x in host.host_services]
        host_output['networks'] = [get_uuid_string_from_bytes(x.net_id) for x in host.host_net_reference]

    return True, host_output


@require_db
def get_all_hosts():
    """
    Returns a list of hosts currently existing in the db
    Args:
    Return:
        Tuple (boolean,data)
        - boolean indicates whether the operation was successful or not
        - data will contain the data in case the operation was successful,
          or the error string otherwise
    """
    try:
        hosts = db.session.query(Host).all()
    except NoResultFound:
        return True, None
    except Exception as err_detail:
        return False, "Error captured while querying for hosts': %s" % (str(err_detail))

    host_ids = []
    if hosts is not None:
        host_ids = [get_uuid_string_from_bytes(x.id) for x in hosts]

    return True, host_ids


@require_db
def update_host_plugins(data):
    """
        Save data{device_id: [plugin_id, ...]} in alienvault.host_scan table
    """
    result = True
    msg = ''
    try:
        if data:
            db.session.begin()
            for device_id, plugin_ids in data.iteritems():
                host_id = get_bytes_from_uuid(device_id)
                db.session.query(Host_Scan).filter(Host_Scan.host_id == host_id).delete()
                if plugin_ids:
                    for pid in plugin_ids:
                        host_scan = Host_Scan(host_id=host_id,
                                              plugin_id=int(pid),
                                              plugin_sid=0)
                        db.session.merge(host_scan)
            db.session.commit()
    except Exception as err_detail:
        result = False
        db.session.rollback()
        msg = "Unable to save data into database {0}".format(str(err_detail))

    return result, msg


# def set_host_property(host_id, host_property, value):
#     """
#     Updates an existing host property with a new value
#     Args:
#         host_id (uuid) Host identifier
#         host_property (str) Host property to update
#         value (*) New value to set to the host_property
#     Return:
#         Tuple (boolean, msg)
#         - boolean indicates whether the operation was successful or not
#         - msg will be empty in case the operation was successful,
#           or the error string otherwise
#     """


@require_db
def create_host(ips, sensor_id, hostname='', fqdns='', asset_value=2, threshold_c=30, threshold_a=30, alert=0, persistence=0, nat=None,
                rrd_profile=None, descr='', lat=0, lon=0, icon=None, country=None, external_host=0, permissions=0, av_component=0, output='str', refresh=False):
    """
    Creates a new host in the database
     Args:
        Host data
     Return:
        Tuple (boolean, msg)
        - boolean indicates whether the operation was successful or not
        - msg will be the host ID,
           or the error string otherwise
    """

    if len(ips) == 0:
        return False, "At least one IP is required"

    succes, ctx = get_sensor_ctx_by_sensor_id(sensor_id)

    if not is_valid_uuid(ctx):
        return False, "ctx is not a valid canonical uuid"

    ctx = get_bytes_from_uuid(ctx)

    host_id = str(uuid.uuid4())

    if hostname == '':
        hostname = "Host-%s" % (ips[0].replace(".", "-"))

    try:
        db.session.begin()
        for host_ip in ips:
            host_ip_object = Host_Ip(host_id=get_bytes_from_uuid(host_id),
                                     ip=get_ip_bin_from_str(host_ip),
                                     mac=None,
                                     interface=None)
            db.session.merge(host_ip_object)

        host = Host(id=get_bytes_from_uuid(host_id),
                    ctx=ctx,
                    hostname=hostname,
                    fqdns=fqdns,
                    asset=asset_value,
                    threshold_c=threshold_c,
                    threshold_a=threshold_a,
                    alert=alert,
                    persistence=persistence,
                    nat=nat,
                    rrd_profile=rrd_profile,
                    descr=descr,
                    lat=lat,
                    lon=lon,
                    icon=icon,
                    country=country,
                    external_host=external_host,
                    permissions=permissions,
                    av_component=av_component)

        db.session.merge(host)

        hs_reference = Host_Sensor_Reference(host_id=get_bytes_from_uuid(host_id),
                                             sensor_id=get_bytes_from_uuid(sensor_id))
        db.session.merge(hs_reference)

        db.session.commit()

    except Exception as err_detail:
        db.session.rollback()
        message = "There was a problem adding new Host %s to the database: %s" % (hostname, str(err_detail))
        api_log.error(message)
        return False, message

    update_host_net_reference(hostid=host_id)

    # Send refresh to server
    if refresh:
        # Updated because original function was changed and previous version will not work.
        apimethods.host.host.refresh_hosts()

    return True, host_id


@require_db
def update_host_net_reference(hostid=None):
    """
        Update host_net_reference table with hosts data.
        Modified to only update host provided.  This query locks the asset db,
        if you have a large number of assets this can cause issues when adding hosts.
        Will default to previous behavior if no host is passed.
    """
    # Original Query
    query = ("REPLACE INTO host_net_reference "
             "SELECT host.id, net_id FROM host, host_ip, net_cidrs "
             "WHERE host.id = host_ip.host_id AND host_ip.ip >= net_cidrs.begin AND host_ip.ip <= net_cidrs.end")

    # Check if hostid is passed and valid, if yes modify original query
    if hostid is not None and is_valid_uuid(hostid):
        query += " AND host.id = unhex(\'%s\')" % get_hex_string_from_uuid(hostid)

    try:
        db.session.begin()
        db.session.connection(mapper=Host_Net_Reference).execute(query)
        db.session.commit()
    except Exception as err_detail:
        db.session.rollback()
        api_log.error("There was a problem while updating host net reference: %s" % str(err_detail))
        return False
    return True


@require_db
@accepted_values([], [], ['str', 'bin'])
def get_host_id_by_ip_ctx(ip, ctx, output='str'):
    """
        Returns an Asset ID given an IP address and context
    """
    host_id = None

    try:
        if ip and ctx:
            ip_bin = get_ip_bin_from_str(ip)
            ctx_bin = get_bytes_from_uuid(ctx)

            query = db.session.query(Host.id).filter(Host.ctx == ctx_bin)
            host = query.join(Host_Ip, Host_Ip.host_id == Host.id).filter(Host_Ip.ip == ip_bin).one()
            host_id = host.id
        else:
            return False, "IP address and/or context could not be empty"
    except NoResultFound:
        return True, host_id
    except Exception as err_detail:
        api_log.error(str(err_detail))
        return False, "Asset ID not found in the system"

    if output == 'str':
        return True, get_uuid_string_from_bytes(host_id)
    else:
        return True, host_id


@require_db
def get_name_by_host_id(host_id):
    """
        Returns an asset name given an asset ID
    """
    host_name = ''

    try:
        host_id_hex = get_hex_string_from_uuid(host_id)

        query = "SELECT hostname FROM host WHERE id = UNHEX('{0}')".format(host_id_hex)
        host_data = db.session.connection(mapper=Host).execute(query).first()

        if host_data:
            host_name = host_data.hostname
    except Exception as err_detail:
        api_log.error("[get_name_by_host_id] {0}".format(str(err_detail)))
        raise APICannotGetAssetName(host_id)

    return host_name
