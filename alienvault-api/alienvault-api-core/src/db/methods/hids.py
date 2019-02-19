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

from sqlalchemy import and_

import db
from db.models.alienvault import Hids_Agents

from apimethods.decorators import require_db

from apimethods.utils import get_bytes_from_uuid
from apimethods.utils import get_hex_string_from_uuid
from apimethods.utils import get_uuid_string_from_bytes

from apiexceptions.sensor import APICannotResolveSensorID
from apiexceptions.asset import APICannotResolveAssetID
from apiexceptions.hids import APICannotGetHIDSAgents
from apiexceptions.hids import APIInvalidHIDSAgentID
from apiexceptions.hids import APICannotAddHIDSAgent
from apiexceptions.hids import APICannotUpdateHIDSAgent
from apiexceptions.hids import APICannotDeleteHIDSAgent
from apiexceptions.hids import APICannotDeleteHIDSAgentList
from apiexceptions.hids import APICannotGetHIDSAgentByAsset

import api_log


@require_db
def get_hids_agents_by_asset(asset_id, sensor_id=None):
    """ Get HIDS agents by asset
    Args:
        asset_id(str): Asset ID
        sensor_id(str): Sensor ID
    Returns:
        Dictionary with HIDS agents related to asset in the database

    Raises:
        APICannotGetHIDSAgentByAsset
        APICannotResolveAssetID
    """

    hids_agents = {}

    if asset_id is None:
        api_log.error("[get_hids_agents_by_asset]: Asset ID could not be empty")
        raise APICannotResolveAssetID(asset_id)

    query = "SELECT HEX(ha.sensor_id) AS sensor_id, ha.agent_id, ha.agent_name, ha.agent_ip, " \
                "ha.agent_status, HEX(ha.host_id) AS host_id " \
                "FROM hids_agents ha WHERE ha.host_id = UNHEX('{0}')".format(get_hex_string_from_uuid(asset_id))

    if sensor_id is not None:
        query = query + " AND ha.sensor_id = UNHEX('{0}')".format(get_hex_string_from_uuid(sensor_id))

    try:
        ha_list = db.session.connection(mapper=Hids_Agents).execute(query)

        for hids_agent in ha_list:
            ha_id = hids_agent.agent_id
            ha_name = hids_agent.agent_name
            ha_ip = hids_agent.agent_ip
            ha_status = hids_agent.agent_status
            ha_sensor_id = hids_agent.sensor_id
            ha_host_id = hids_agent.host_id if hids_agent.host_id is not None else ''

            ha_key = ha_sensor_id + '#' + ha_id

            hids_agents[ha_key] = {
                'id': ha_id,
                'name': ha_name,
                'ip_cidr': ha_ip,
                'status': {
                    'id': ha_status,
                    'descr': Hids_Agents.get_status_string_from_integer(ha_status)
                },
                'sensor_id': ha_sensor_id,
                'host_id': ha_host_id
            }

    except Exception as msg:
        api_log.error("[get_hids_agents_by_asset]: %s" % str(msg))
        raise APICannotGetHIDSAgentByAsset(asset_id)

    return hids_agents


@require_db
def get_linked_assets():
    """ Get assets
    Returns:
        Dictionary with linked assets

    Raises:
        APICannotGetLinkedAssets
    """

    assets_ids = {}

    query = "SELECT ha.host_id AS host_id, ha.sensor_id AS sensor_id, ha.agent_id FROM hids_agents ha WHERE ha.host_id is not NULL"

    try:
        asset_list = db.session.connection(mapper=Hids_Agents).execute(query)

        for asset in asset_list:
            ha_id = asset.agent_id
            ha_sensor_id = asset.sensor_id
            ha_host_id = asset.host_id

            ha_host_id = get_uuid_string_from_bytes(ha_host_id)

            assets_ids[ha_host_id] = {
                'ha_id': ha_id,
                'sensor_id': get_uuid_string_from_bytes(ha_sensor_id)
            }

    except Exception as msg:
        api_log.error("[get_linked_assets]: %s" % str(msg))
        raise APICannotGetLinkedAssets()

    return assets_ids


@require_db
def get_hids_agents_by_sensor(sensor_id):
    """ Get HIDS agents by sensor
    Args:
        sensor_id(str): Sensor ID
    Returns:
        Dictionary with HIDS agents of the sensor in the database

    Raises:
        APICannotResolveSensorID
        APICannotGetHIDSAgents
    """

    hids_agents = {}

    if sensor_id is None:
        api_log.error("[get_hids_agents_by_sensor]: Sensor ID could not be empty")
        raise APICannotResolveSensorID(sensor_id)

    try:
        sensor_id_hex = get_hex_string_from_uuid(sensor_id)
        query = "SELECT HEX(ha.sensor_id) AS sensor_id, ha.agent_id, ha.agent_name, ha.agent_ip, " \
                "ha.agent_status, HEX(ha.host_id) AS host_id " \
                "FROM hids_agents ha WHERE ha.sensor_id = UNHEX('{0}')".format(sensor_id_hex)
        ha_list = db.session.connection(mapper=Hids_Agents).execute(query)

        for hids_agent in ha_list:
            ha_id = hids_agent.agent_id
            ha_name = hids_agent.agent_name
            ha_ip = hids_agent.agent_ip
            ha_status = hids_agent.agent_status
            ha_host_id = hids_agent.host_id if hids_agent.host_id is not None else ''

            hids_agents[ha_id] = {
                'id': ha_id,
                'name': ha_name,
                'ip_cidr': ha_ip,
                'status': {
                    'id': ha_status,
                    'descr': Hids_Agents.get_status_string_from_integer(ha_status)
                },
                'host_id': ha_host_id
            }

    except Exception as msg:
        api_log.error("[get_hids_agents_by_sensor]: %s" % str(msg))
        raise APICannotGetHIDSAgents(sensor_id)

    return hids_agents


@require_db
def get_hids_agent_by_sensor(sensor_id, agent_id):
    """ Get HIDS agent by sensor
    Args:
        sensor_id(str): Sensor ID
        agent_id(str): Agent ID
    Returns:
        Dictionary with the HIDS agent of the sensor in the database

    Raises:
        APICannotResolveSensorID
        APIInvalidHIDSAgentID
        APICannotGetHIDSAgents
    """

    if sensor_id is None:
        api_log.error("[get_hids_agent_by_sensor]: Sensor ID could not be empty")
        raise APICannotResolveSensorID(sensor_id)

    if agent_id is None:
        api_log.error("[get_hids_agent_by_sensor]: Agent ID could not be empty")
        raise APIInvalidHIDSAgentID(agent_id)

    hids_agent = {}

    try:
        sensor_id_hex = get_hex_string_from_uuid(sensor_id)

        query = "SELECT HEX(ha.sensor_id) AS sensor_id, ha.agent_id, ha.agent_name, ha.agent_ip, " \
                "ha.agent_status, HEX(ha.host_id) AS host_id " \
                "FROM hids_agents ha WHERE ha.sensor_id = UNHEX('{0}') AND ha.agent_id = '{1}' " \
                "LIMIT 1".format(sensor_id_hex, agent_id)

        ha_list = db.session.connection(mapper=Hids_Agents).execute(query).fetchall()

        if ha_list:
            ha_list = ha_list[0]

            ha_id = ha_list.agent_id
            ha_name = ha_list.agent_name
            ha_ip = ha_list.agent_ip
            ha_status = ha_list.agent_status
            ha_host_id = ha_list.host_id if ha_list.host_id is not None else ''

            hids_agent = {
                'id': ha_id,
                'name': ha_name,
                'ip_cidr': ha_ip,
                'status': {
                    'id': ha_status,
                    'descr': Hids_Agents.get_status_string_from_integer(ha_status)
                },
                'host_id': ha_host_id
            }
    except Exception as msg:
        api_log.error("[get_hids_agent_by_sensor]: %s" % str(msg))
        raise APICannotGetHIDSAgents(sensor_id)

    return hids_agent


@require_db
def delete_hids_agent(agent_id, sensor_id):
    """ Delete a HIDS agent
    Args:
        agent_id(str): HIDS agent ID
        sensor_id(str): Sensor ID

    Raises:
        APICannotResolveSensorID
        APIInvalidHIDSAgentID
        APICannotDeleteHIDSAgent
    """
    if sensor_id is None:
        api_log.error("[delete_hids_agent]: Sensor ID could not be empty")
        raise APICannotResolveSensorID(sensor_id)

    if agent_id is None:
        api_log.error("[delete_hids_agent]: Agent ID could not be empty")
        raise APIInvalidHIDSAgentID(agent_id)

    try:
        sensor_id_bin = get_bytes_from_uuid(sensor_id)
        db.session.begin()
        db.session.query(Hids_Agents).filter(and_(Hids_Agents.agent_id == agent_id,
                                                  Hids_Agents.sensor_id == sensor_id_bin)).delete()
        db.session.commit()
    except Exception as msg:
        db.session.rollback()
        api_log.error("[delete_hids_agent] %s" % str(msg))
        raise APICannotDeleteHIDSAgent(agent_id, sensor_id)


@require_db
def delete_orphan_hids_agents(agent_list, sensor_id):
    """ Delete orphan HIDS agents
    Args:
        agent_list(list): List of active HIDS agents
        sensor_id(str): Sensor ID

    Raises:
        APICannotResolveSensorID
        APICannotDeleteHIDSAgentList
    """
    if sensor_id is None:
        api_log.error("[delete_orphan_hids_agents]: Sensor ID could not be empty")
        raise APICannotResolveSensorID(sensor_id)

    try:
        if agent_list:
            q_agent_list = "'" + "','".join(agent_list) + "'"
            sensor_id_hex = get_hex_string_from_uuid(sensor_id)
            query = "DELETE FROM hids_agents WHERE sensor_id = UNHEX('{0}') " \
                    "AND agent_id NOT IN ({1})".format(sensor_id_hex, q_agent_list)
            db.sesion.begin()
            db.session.connection(mapper=Hids_Agents).execute(query)
            db.session.commit()

    except Exception as msg:
        db.session.rollback()
        api_log.error("[delete_orphan_hids_agents]: %s" % str(msg))
        raise APICannotDeleteHIDSAgentList(agent_list, sensor_id)


@require_db
def add_hids_agent(agent_id, sensor_id, agent_name, agent_ip, agent_status, host_id=None):
    """ Add a new HIDS agent

    Raises:
        APICannotResolveSensorID
        APIInvalidHIDSAgentID
        APICannotAddHIDSAgent
    """

    if sensor_id is None:
        api_log.error("[add_hids_agent]: Sensor ID could not be empty")
        raise APICannotResolveSensorID(sensor_id)

    if agent_id is None:
        api_log.error("[add_hids_agent]: Agent ID could not be empty")
        raise APIInvalidHIDSAgentID(agent_id)

    try:
        db.session.begin()

        sensor_id_bin = get_bytes_from_uuid(sensor_id)

        if host_id:
            hex_id_bin = get_bytes_from_uuid(host_id)
        else:
            hex_id_bin = None

        status_integer = Hids_Agents.get_status_integer_from_string(agent_status)

        hids_agent = Hids_Agents()
        hids_agent.agent_id = agent_id
        hids_agent.sensor_id = sensor_id_bin
        hids_agent.agent_name = agent_name
        hids_agent.agent_ip = agent_ip
        hids_agent.agent_status = status_integer
        hids_agent.host_id = hex_id_bin

        db.session.merge(hids_agent)
        db.session.commit()
    except Exception as msg:
        db.session.rollback()
        api_log.error("[add_hids_agent]: %s" % str(msg))
        raise APICannotAddHIDSAgent(agent_id, sensor_id)


@require_db
def update_hids_agent_status(agent_id, sensor_id, agent_status):
    """ Update status of HIDS agent

    Raises:
        APICannotResolveSensorID
        APIInvalidHIDSAgentID
        APICannotUpdateHIDSAgent
    """

    if sensor_id is None:
        api_log.error("[update_hids_agent_status]: Sensor ID could not be empty")
        raise APICannotResolveSensorID(sensor_id)

    if agent_id is None:
        api_log.error("[update_hids_agent_status]: Agent ID could not be empty")
        raise APIInvalidHIDSAgentID(agent_id)

    try:
        sensor_id_bin = get_bytes_from_uuid(sensor_id)
        status_integer = Hids_Agents.get_status_integer_from_string(agent_status)

        db.session.begin()
        db.session.query(Hids_Agents).filter(
            and_(Hids_Agents.agent_id == agent_id,
                 Hids_Agents.sensor_id == sensor_id_bin)).update({"agent_status": status_integer})
        db.session.commit()
    except Exception as msg:
        db.session.rollback()
        api_log.error("[update_hids_agent_status]: %s" % str(msg))
        raise APICannotUpdateHIDSAgent(agent_id, sensor_id)


@require_db
def update_asset_id(sensor_id, agent_id, asset_id):
    """ Update Asset ID related to agent

    Raises:
        APICannotResolveSensorID
        APIInvalidHIDSAgentID
        APICannotUpdateHIDSAgent
        APICannotResolveAssetID
    """

    if sensor_id is None:
        api_log.error("[update_asset_id]: Sensor ID could not be empty")
        raise APICannotResolveSensorID(sensor_id)

    if agent_id is None:
        api_log.error("[update_asset_id]: Agent ID could not be empty")
        raise APIInvalidHIDSAgentID(agent_id)

    if asset_id is None:
        api_log.error("[update_asset_id]: Asset ID could not be empty")
        raise APICannotResolveAssetID(asset_id)

    try:
        sensor_id_bin = get_bytes_from_uuid(sensor_id)
        asset_id_bin = get_bytes_from_uuid(asset_id)

        db.session.begin()
        db.session.query(Hids_Agents).filter(
            and_(Hids_Agents.agent_id == agent_id,
                 Hids_Agents.sensor_id == sensor_id_bin)).update({"host_id": asset_id_bin})
        db.session.commit()
    except Exception as msg:
        db.session.rollback()
        api_log.error("[update_asset_id]: %s" % str(msg))
        raise APICannotUpdateHIDSAgent(agent_id, sensor_id)
