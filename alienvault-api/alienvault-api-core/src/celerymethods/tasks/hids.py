#
# License:
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

from celery.utils.log import get_logger

from celerymethods.tasks import celery_instance
from celery_once.tasks import QueueOnce

from apimethods.sensor.ossec import ossec_get_available_agents

from apiexceptions import APIException
from apiexceptions.system import APICannotResolveLocalSystemID
from apiexceptions.system import APICannotRetrieveSystems
from apiexceptions.system import APICannotRetrieveSystem
from apiexceptions.hids import APICannotRunHIDSCommand

from db.methods.system import (
    get_systems,
    get_system_id_from_local,
    get_sensor_id_from_system_id,
    get_system_info)

from apimethods.host.host import refresh_hosts

from db.methods.hids import (
    get_hids_agents_by_sensor,
    delete_hids_agent,
    add_hids_agent,
    update_hids_agent_status,
    update_asset_id,
    get_hids_agent_by_sensor,
    get_linked_assets)

from db.methods.sensor import get_sensor_ctx_by_sensor_id
from db.methods.host import get_host_id_by_ip_ctx, create_host
from db.methods.data import delete_current_status_messages

from apimethods.data.status import insert_current_status_message
from apimethods.utils import is_valid_ipv4, is_valid_ipv4_cidr, is_valid_uuid, get_bytes_from_uuid
from apimethods.sensor.ossec import ossec_get_check

import json

logger = get_logger("celery")


@celery_instance.task
def update_system_hids_agents(system_id):
    """"
    Update information about HIDS agents connected to a system
    @param system_id: system_id of the sensor to update
    """

    # Getting system information
    success, system_info = get_system_info(system_id)

    # Getting sensor ID
    if success:
        sensor_id = system_info['sensor_id']
    else:
        raise APICannotRetrieveSystem(system_id)

    stored_agents = get_hids_agents_by_sensor(sensor_id)

    success, agents = ossec_get_available_agents(sensor_id=sensor_id,
                                                 op_ossec='list_available_agents',
                                                 agent_id='')

    if not success:
        raise APICannotRunHIDSCommand(sensor_id, 'list_available_agents')

    added_agents = [agent_id for agent_id in agents.keys() if agent_id not in stored_agents]
    present_agents = [agent_id for agent_id in agents.keys() if agent_id in stored_agents]
    deleted_agents = [agent for agent in stored_agents if agent not in agents.keys()]

    # Add new agents to database
    for agent_id in added_agents:
        try:
            agent = agents[agent_id]
            add_hids_agent(agent_id=agent_id,
                           sensor_id=sensor_id,
                           agent_name=agent['name'],
                           agent_ip=agent['ip'],
                           agent_status=agent['status'])
        except APIException as e:
            logger.error("Error adding hids agent: {0}".format(e))

    not_linked_assets = 0
    refresh_idm = False

    # Update agent status and check asset_id in database
    for agent_id in present_agents:
        try:
            # Update HIDS agent status
            update_hids_agent_status(agent_id=agent_id,
                                     sensor_id=sensor_id,
                                     agent_status=agents[agent_id]['status'])

            agent_data = get_hids_agent_by_sensor(sensor_id, agent_id)

            # Check HIDS agent asset id
            if agent_data['host_id'] == '':
                # Try to update HIDS agent asset id
                linked_assets = get_linked_assets()

                agent_ip_cidr = agent_data['ip_cidr']
                asset_id = None

                # Getting current IP
                if agent_ip_cidr == '127.0.0.1':
                    # Special case: Local agent
                    agent_ip_cidr = system_info['ha_ip'] if system_info['ha_ip'] else system_info['admin_ip']
                elif agent_ip_cidr.lower() == 'any' or agent_ip_cidr.lower() == '0.0.0.0' or (
                            is_valid_ipv4_cidr(agent_ip_cidr) and agent_ip_cidr.find('/') != -1):
                    # DHCP environments (Get the latest IP)
                    success, agent_ip_cidr = ossec_get_check(sensor_id, agent_data['name'], "lastip")

                # Search asset_id
                if is_valid_ipv4(agent_ip_cidr):
                    success, sensor_ctx = get_sensor_ctx_by_sensor_id(sensor_id)

                    if success:
                        success, asset_id = get_host_id_by_ip_ctx(agent_ip_cidr, sensor_ctx, output='str')

                    if not is_valid_uuid(asset_id):
                        success, new_asset_id = create_host([agent_ip_cidr], sensor_id)

                        if is_valid_uuid(new_asset_id):
                            asset_id = new_asset_id
                            refresh_idm = True

                # Linking asset to agent
                if is_valid_uuid(asset_id) and asset_id not in linked_assets:
                    update_asset_id(sensor_id=sensor_id, agent_id=agent_id, asset_id=asset_id)
                    linked_assets[asset_id] = {'ha_id': agent_id, 'sensor_id': sensor_id}
                else:
                    not_linked_assets += 1
        except APIException as e:
            logger.error('[update_system_hids_agents]: {0}'.format(e))

    # Remove deleted agents from database
    for agent_id in deleted_agents:
        try:
            delete_hids_agent(agent_id, sensor_id)
        except APIException as e:
            logger.error('[update_system_hids_agents]: {0}'.format(e))

    return not_linked_assets, refresh_idm


@celery_instance.task(base=QueueOnce)
def update_hids_agents():
    """ Task to update the info of hids agents of each sensor
    """

    insert_message = False
    send_refresh = False
    not_linked_assets = 0

    msg_id_binary = get_bytes_from_uuid("00000000-0000-0000-0000-000000010032")
    delete_current_status_messages([msg_id_binary])

    try:
        success, systems = get_systems(system_type='Sensor', directly_connected=True)
        if not success:
            logger.error("[update_hids_agents] %s" % str(systems))
            raise APICannotRetrieveSystems()

        success, local_system_id = get_system_id_from_local()
        if not success:
            logger.error("[update_hids_agents] %s" % str(local_system_id))
            raise APICannotResolveLocalSystemID()

        system_ids = [x[0] for x in systems]
        if local_system_id not in system_ids:
            system_ids.append(local_system_id)

        for system_id in system_ids:
            try:
                not_linked_assets_by_sensor, new_host = update_system_hids_agents(system_id)

                # Update counter
                not_linked_assets = not_linked_assets + not_linked_assets_by_sensor

                if not_linked_assets_by_sensor > 0:
                    insert_message = True

                if not send_refresh and new_host:
                    send_refresh = True

            except APIException as e:
                logger.error("[update_hids_agents] %s" % str(e))

    except Exception as e:
        logger.error("[update_hids_agents] %s" % str(e))
        return False

    if insert_message:
        success, local_system_id = get_system_id_from_local()
        additional_info = json.dumps({"not_linked_assets": not_linked_assets})
        insert_current_status_message("00000000-0000-0000-0000-000000010032", local_system_id, "system",
                                      additional_info)

    if send_refresh:
        refresh_hosts()

    return True
