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

from sqlalchemy.orm.exc import NoResultFound
from sqlalchemy import and_

from apimethods.utils import get_bytes_from_uuid, get_ip_str_from_bytes
from apimethods.decorators import accepted_types, require_db

import db
from db.models.alienvault import Host, Host_Ip
from db.models.alienvault_siem import Acid_Event
from db.models.alienvault_api import Current_Status, Monitor_Data

from datetime import datetime, timedelta


@require_db
def get_device_list():
    """Returns the device list retrieved from the table host
    :returns sensor_list list<Alienvault_Host> or [] on error/or no sensors
    """
    devices = {}
    query = "select hex(h.id) as host_id,inet6_ntop(hi.ip) as host_ip from host h,host_ip hi,host_types ht" \
            " where h.id=hi.host_id and " \
            "h.id=ht.host_id and ht.type=4 union distinct select hex(h.id),inet6_ntop(hi.ip) " \
            "from host h,host_ip hi,alienvault_siem.device d where h.id=hi.host_id and hi.ip=d.device_ip;"
    try:
        data = db.session.connection(mapper=Host).execute(query)
        for row in data:
            devices[row[0]] = row[1]
    except Exception:
        devices = {}
        db.session.rollback()
    return devices


@require_db
def get_asset_list():
    """Returns the asset list retrieved from the table host
    :returns sensor_list list<Alienvault_Host> or [] on error/or no sensors
    """
    assets = []
    try:
        assets = db.session.query(Host).all()
    except Exception:
        assets = []
        db.session.rollback()
    return assets


@require_db
@accepted_types(UUID, UUID)
def get_asset_events(asset_id, asset_ctx, order_by=True):
    """Returns a list of events of an asset, order by timestamp
    :param asset_id (uuid binary) Asset asset_id
    :param asset_ctx (uuid binary) Asset Context
    :param order_by (Boolean) on True order = DESC  otherwise order = ASC
    """

    events = []
    try:
        if order_by:
            events = db.session.query(Acid_Event).\
                     filter(Acid_Event.ctx == asset_ctx).\
                     filter(Acid_Event.src_host == asset_id).\
                     order_by(Acid_Event.timestamp.desc()).all()
        else:
            events = db.session.query(Acid_Event).\
                     filter(Acid_Event.ctx == asset_ctx).\
                     filter(Acid_Event.src_host == asset_id).\
                     order_by(Acid_Event.timestamp.asc()).all()

    except Exception:
        events = []
        db.session.rollback()
    return events


@require_db
def get_timestamp_last_event_for_each_device():
    """Get the last event for each device"""
    host_last_event = {}
    query = "select max(timestamp),hex(h.id) from alienvault_siem.acid_event a, alienvault.host h where h.id=a.src_host group by h.id;"
    try:
        data = db.session.connection(mapper=Host).execute(query)
        for row in data:
            host_last_event[row[1]] = row[0]
    except Exception:
        host_last_event = {}
        db.session.rollback()

    return host_last_event


@require_db
@accepted_types(UUID)
def get_asset_ip_from_id(asset_id):
    """Returns a list of IPs for a given ASSET"""
    ips = []
    try:
        data = db.session.query(Host_Ip).filter(Host_Ip.host_id == get_bytes_from_uuid(asset_id)).all()
        ips = [get_ip_str_from_bytes(i.ip) for i in data]

    except Exception:
        ips = []
        db.session.rollback()
    return ips


@require_db
@accepted_types(UUID)
def host_clean_orphan_ref(host_id):
    """
    Clean hosts marked as orphaned
    """
    try:
        records = db.session.query(Current_Status).filter(and_(Current_Status.component_type=='host',
                                                                      Current_Status.component_id ==  get_bytes_from_uuid (host_id))).all()
        db.session.begin()
        for record in records:
            db.session.delete(record)
        db.session.commit()
    except NoResultFound:
        db.session.rollback()
    try:
        records = db.session.query(Monitor_Data).filter(Monitor_Data.component_id == get_bytes_from_uuid (host_id)).all()
        db.session.begin()
        for record in records:
            db.session.delete(record)
        db.session.commit()
    except NoResultFound:
        db.session.rollback()
    return True


@require_db
def get_snort_suricata_events_in_the_last_24_hours():
    try:
        d = datetime.now() - timedelta(hours=24)
        data = db.session.query(Acid_Event).filter(Acid_Event.plugin_id >= 1001).\
                        filter(Acid_Event.plugin_id <= 1505).\
                        filter(Acid_Event.timestamp >= d).all()
    except:
        db.session.rollback()
        data = []
    return data
