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

import time
import os
import json

from api.lib.monitors.monitor import Monitor, MonitorTypes
from ansiblemethods.server.server import get_server_stats

import celery.utils.log
logger = celery.utils.log.get_logger("celery")


class MonitorServerEPSStats(Monitor):
    """
    Monitor correlation EPS in the current server.
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.SERVER_EPS_STATS)
        self.message = 'Server EPS stats Monitor Enabled'

        self.__server_ip = '127.0.0.1'
        self.__server_port = '40009'
        self.__stats_dir = '/var/alienvault/server/stats'
        self.__eps_log_file = '%s/%s' % (self.__stats_dir, 'eps.log')
        self.__max_samples = 168

    def start(self):
        """
        Starts the monitor activity

        :return: True on success, False otherwise
        """
        eps_data = []

        if not os.path.isdir(self.__stats_dir):
            os.mkdir(self.__stats_dir, 0770)
        if os.path.isfile(self.__eps_log_file):
            with open(self.__eps_log_file, 'r') as f:
                try:
                    eps_data = json.loads(f.read())
                    eps_data = filter(lambda x: type(x) == int, eps_data[-self.__max_samples:])
                except:
                    eps_data = []

        args = {'server_ip': self.__server_ip, 'server_port': self.__server_port, 'server_stats': 'yes'}
        ansible_output = get_server_stats(self.__server_ip, args)
        if ansible_output['dark'] != {}:
            logger.error("Error querying server EPS stats: %s" % ansible_output['dark'])
            return False

        try:
            # Get correlation EPS only.
            eps = int(ansible_output['contacted'][self.__server_ip]['data']['sim_eps'])
        except KeyError:
            logger.error("Cannot get server EPS number")
            return False
        except ValueError:
            logger.error("Server EPS value is not an integer")
            return False

        eps_data = eps_data[-(self.__max_samples - 1):] + [eps]
        with open(self.__eps_log_file, 'w') as f:
            try:
                os.chmod(self.__eps_log_file, 0644)
            except Exception, e:
                logger.error("Cannot change file permissions: %s" % str(e))
            try:
                f.write(json.dumps(eps_data, indent=4, separators=(',', ': ')))
            except Exception, e:
                logger.error("Cannot write server EPS stats to file: %s" % str(e))

        return True


class MonitorServerSensorActivity(Monitor):
    """
    Monitor connections between sensors and a server.
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.SERVER_SENSOR_ACTIVITY)
        self.message = 'Server Sensor Activity Monitor Enabled'

    def start(self):
        """
        Starts the monitor activity

        :return: True on success, False otherwise
        """
        # TODO: This code is not being used currently. It should be refactorized
        # before use it
        # self.remove_monitor_data()
        # # Find the local server.
        # try:
        #     server_id = Alienvault_Config.query.filter(Alienvault_Config.conf == 'server_id').one().value
        # except Exception, msg:
        #     db.session.rollback()
        #     logger.error("Error retrieving server id from the database: %s" % str(msg))
        #     return False
        #
        # # List only registered sensors.
        # try:
        #     sensor_list = Alienvault_Sensor.query.filter(Alienvault_Sensor.name != '(null)').all()
        # except Exception, msg:
        #     db.session.rollback()
        #     logger.error("Error retrieving sensor information from the database: %s" % str(msg))
        #     return False
        # else:
        #     if sensor_list in [[], None]:
        #         logger.error("Error retrieving sensor information from the database: there are no sensor listed")
        #         return False
        #
        # # Retrieve server per session status.
        # args = {}
        # args['server_ip'] = SERVER_IP
        # args['server_port'] = SERVER_PORT
        # args['per_session_stats'] = 'yes'
        #
        # ansible_output = get_server_stats (SERVER_IP, args)
        # if ansible_output['dark'] != {}:
        #     logger.error("Cannot connect to Ansible: %s" % ansible_output['dark'])
        #     return False
        #
        # time_limit = int(time.time()) - 60
        #
        # for sensor in sensor_list:
        #     sensor_id = get_uuid_string_from_bytes (sensor.id)
        #     sensor_ip = get_ip_str_from_bytes (sensor.ip)
        #     sensor_name = sensor.name
        #     sensor_ansible_output = ansible_output['contacted']['127.0.0.1']['data']
        #     sensor_data = [x for x in sensor_ansible_output if x['ip_addr'] == sensor_ip and x['type'] == 'Sensor']
        #
        #     if sensor_data == []:
        #         logger.debug("Caught '%s' from sensor '%s'" % ('ALIENVAULT_SERVER_NOT_CONNECTED_TO_SENSOR', sensor_id))
        #         extra_msg = {'sensor_id': sensor_id, 'sensor_ip': sensor_ip}
        #         msg = MessageJsonComposer.get_message(ALIENVAULT_SERVER_NOT_CONNECTED_TO_SENSOR, server_id, extra_msg)
        #
        #         if not self.save_message(server_id, ALIENVAULT_SERVER_NOT_CONNECTED_TO_SENSOR, MessageLevels.INFO, msg):
        #             logger.error("Cannot store new message '%s'" % 'ALIENVAULT_SERVER_NOT_CONNECTED_TO_SENSOR')
        #
        #     # Test if this sensor hasn't sent data for more than a minute
        #     if time_limit > int(sensor_data[0]['last_data_timestamp']):
        #         logger.debug("Caught '%s' from sensor '%s'" % ('ALIENVAULT_SERVER_NOT_RECEIVING_SENSOR_DATA', sensor_id))
        #         extra_msg = {'sensor_id': sensor_id, 'reference_timestamp': time_limit, 'last_data_timestamp': sensor_data[0]['last_data_timestamp']}
        #         msg = MessageJsonComposer.get_message(ALIENVAULT_SERVER_NOT_RECEIVING_SENSOR_DATA, server_id, extra_msg)
        #
        #         if not self.save_message(server_id, ALIENVAULT_SERVER_NOT_RECEIVING_SENSOR_DATA, MessageLevels.INFO, msg):
        #             logger.error("Cannot store new message '%s'" % 'ALIENVAULT_SERVER_NOT_RECEIVING_SENSOR_DATA')
        #
        #     # Test if this sensor hasn't sent data for more than a minute
        #     if time_limit > int(sensor_data[0]['last_event_timestamp']):
        #         logger.debug("Caught '%s' from sensor '%s'" % ('ALIENVAULT_SERVER_NOT_RECEIVING_SENSOR_EVENTS', sensor_id))
        #         extra_msg = {'sensor_id': sensor_id, 'reference_timestamp': time_limit, 'last_event_timestamp': sensor_data[0]['last_event_timestamp']}
        #         msg = MessageJsonComposer.get_message(ALIENVAULT_SERVER_NOT_RECEIVING_SENSOR_EVENTS, server_id, extra_msg)
        #
        #         if not self.save_message(server_id, ALIENVAULT_SERVER_NOT_RECEIVING_SENSOR_EVENTS, MessageLevels.INFO, msg):
        #             logger.error("Cannot store new message '%s'" % 'ALIENVAULT_SERVER_NOT_RECEIVING_SENSOR_EVENTS')

        return True


class MonitorServerServerActivity(Monitor):
    """
    Monitor connections between two servers.
    """

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.SERVER_SERVER_ACTIVITY)
        self.message = 'Server Server Activity Monitor Enabled'

    def start(self):
        """
        Starts the monitor activity

        :return: True on success, False otherwise
        """
        # TODO: This code is not being used currently. It should be refactorized
        # before use it
        # self.remove_monitor_data()
        # # Find the local server.
        # try:
        #     server_id = Alienvault_Config.query.filter(Alienvault_Config.conf == 'server_id').one().value
        # except Exception, msg:
        #     db.session.rollback()
        #     logger.error("Error retrieving server id from the database: %s" % str(msg))
        #     return False
        #
        # # Find the remote servers.
        # try:
        #     rserver_list = Alienvault_Server_Forward_Role.query.filter(Alienvault_Server_Forward_Role.server_src_id == get_bytes_from_uuid(server_id)).all()
        # except Exception, msg:
        #     db.session.rollback()
        #     logger.error("Error retrieving remote server information from the database: %s" % str(msg))
        #     return False
        # else:
        #     if rserver_list in [[], None]:
        #         logger.error("Error retrieving remote server information from the database: there are no remote servers listed")
        #         return False
        #
        # # Retrieve remote server stats.
        # args = {}
        # args['server_ip'] = SERVER_IP
        # args['server_port'] = SERVER_PORT
        # args['rserver_stats'] = 'yes'
        #
        # ansible_output = get_server_stats (SERVER_IP, args)
        # if ansible_output['dark'] != {}:
        #     logger.error("Cannot connect to Ansible: %s" % ansible_output['dark'])
        #     return False
        #
        # time_limit = int(time.time()) - 120
        # data_limit = 100                     # 100 messages in cache.
        # retries_limit = 120                  # 120 attempts.
        # rserver_ansible_output = ansible_output['contacted']['127.0.0.1']['data']
        #
        # for rserver in rserver_list:
        #     rserver_id = get_uuid_string_from_bytes (rserver.server_dst_id)
        #     rserver_data = [x for x in rserver_ansible_output if x['id'] == rserver_id]
        #
        #     if rserver_data == []:
        #         logger.debug("Caught '%s' from remote server '%s'" % ('ALIENVAULT_SERVER_NOT_CONNECTED_TO_REMOTE_SERVER', rserver_id))
        #         extra_msg = {'rserver_id': rserver_id}
        #         msg = MessageJsonComposer.get_message(ALIENVAULT_SERVER_NOT_CONNECTED_TO_REMOTE_SERVER, server_id, extra_msg)
        #
        #         if not self.save_message(server_id, ALIENVAULT_SERVER_NOT_CONNECTED_TO_REMOTE_SERVER, MessageLevels.INFO, msg):
        #             logger.error("Cannot store new message '%s'" % 'ALIENVAULT_SERVER_NOT_CONNECTED_TO_REMOTE_SERVER')
        #
        #
        #     if int(rserver_data[0]['retries']) > retries_limit:
        #         logger.debug("Caught '%s' from remote server '%s'" % ('ALIENVAULT_SERVER_EXCESSIVE_CONNECTION_ATTEMPTS', rserver_id))
        #         extra_msg = {'rserver_id': rserver_id, 'retries': rserver_data[0]['retries'], 'last_conn_timestamp': rserver_data[0]['last_conn']}
        #         msg = MessageJsonComposer.get_message(ALIENVAULT_SERVER_EXCESSIVE_CONNECTION_ATTEMPTS, server_id, extra_msg)
        #
        #         if not self.save_message(server_id, ALIENVAULT_SERVER_EXCESSIVE_CONNECTION_ATTEMPTS, MessageLevels.INFO, msg):
        #             logger.error("Cannot store new message '%s'" % 'ALIENVAULT_SERVER_EXCESSIVE_CONNECTION_ATTEMPTS')
        #
        #     if int(rserver_data[0]['in_cache']) > data_limit:
        #         logger.debug("Caught '%s' from remote server '%s'" % ('ALIENVAULT_SERVER_LARGE_DATA_IN_CACHE', rserver_id))
        #         extra_msg = {'rserver_id': rserver_id, 'in_cache': rserver_data[0]['in_cache'], 'last_message_timestamp': rserver_data[0]['last_message']}
        #         msg = MessageJsonComposer.get_message(ALIENVAULT_SERVER_LARGE_DATA_IN_CACHE, server_id, extra_msg)
        #
        #         if not self.save_message(server_id, ALIENVAULT_SERVER_LARGE_DATA_IN_CACHE, MessageLevels.INFO, msg):
        #             logger.error("Cannot store new message '%s'" % 'ALIENVAULT_SERVER_LARGE_DATA_IN_CACHE')
        #
        #     if float(time_limit) > float(rserver_data[0]['last_message']):
        #         logger.debug("Caught '%s' from remote server '%s'" % ('ALIENVAULT_SERVER_NOT_FORWARDING_EVENTS', rserver_id))
        #         extra_msg = {'rserver_id': rserver_id, 'reference_timestamp': time_limit, 'last_message_timestamp': rserver_data[0]['last_message']}
        #         msg = MessageJsonComposer.get_message(ALIENVAULT_SERVER_NOT_FORWARDING_EVENTS, server_id, extra_msg)
        #
        #         if not self.save_message(server_id, ALIENVAULT_SERVER_NOT_FORWARDING_EVENTS, MessageLevels.INFO, msg):
        #             logger.error("Cannot store new message '%s'" % 'ALIENVAULT_SERVER_NOT_FORWARDING_EVENTS')

        return True
