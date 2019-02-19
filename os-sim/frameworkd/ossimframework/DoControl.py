# -*- coding: utf-8 -*-
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2017 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

#
# GLOBAL IMPORTS
#
import ast
import os
import random
import socket
import threading
import time
from collections import defaultdict
from threading import Timer, Lock

#
# LOCAL IMPORTS
#
from Logger import Logger
import Util
from OssimDB import OssimDB
from DBConstantNames import *
#
# GLOBAL VARIABLES
#
logger = Logger.logger


class ControlManager:
    def __init__(self, conf):
        logger.debug("Initialising ControlManager...")
        self.control_agents = {}
        self.__control_agents_connection_ip_vs_sensor_ip = {}
        self.transaction_map = {}
        self.__myconf = conf
        self.__myDB = OssimDB(conf[VAR_DB_HOST],
                              conf[VAR_DB_SCHEMA],
                              conf[VAR_DB_USER],
                              conf[VAR_DB_PASSWORD])
        self.__myDB_connected = self.__myDB.connect()
        self.__transaction_timeout = 60
        self.__control = DoControl(self)
        self.__control.start()
        self.__mutexRequest = Lock()

    def refreshAgentInventoryTasks(self, requestor, agent_ip, agent_name):
        """ Send inventory task to the sensors
        """
        try:
            if not self.__myDB_connected:
                self.__myDB_connected = self.__myDB.connect()

            agent_id = requestor.get_sensorID()

            # read host list
            logger.info("Refresh agent inventory task agentID: %s agentname:%s" % (agent_ip, agent_name))

            # v3 backward compatibility
            if agent_id == "":
                query = ('SELECT task_inventory.task_name, '
                         'host_source_reference.id AS task_type, '
                         'host_source_reference.name AS task_type_name, '
                         'task_inventory.task_period, '
                         'host_source_reference.relevance AS task_reliability, '
                         'task_inventory.task_enable, '
                         'task_inventory.task_params, '
                         'task_inventory.task_last_run '
                         'FROM host_source_reference, sensor '
                         'INNER JOIN task_inventory ON sensor.id = task_inventory.task_sensor '
                         'WHERE sensor.ip = INET6_ATON(%s) '
                         'AND host_source_reference.id=task_inventory.task_type;')
                tmp = self.__myDB.exec_query(query, (agent_ip,))
            else:
                query = ('SELECT task_inventory.task_name, '
                         'host_source_reference.id AS task_type, '
                         'host_source_reference.name AS task_type_name, '
                         'task_inventory.task_period, '
                         'host_source_reference.relevance AS task_reliability, '
                         'task_inventory.task_enable, '
                         'task_inventory.task_params, '
                         'task_inventory.task_last_run '
                         'FROM host_source_reference, sensor '
                         'INNER JOIN task_inventory ON sensor.id = task_inventory.task_sensor '
                         'WHERE sensor.id = UNHEX(%s) '
                         'AND host_source_reference.id=task_inventory.task_type;')
                tmp = self.__myDB.exec_query(query, (agent_id.replace('-', ''),))

            new_command = 'action="refresh_inventory_task" inventory_task_list={'
            tasks = []
            for task in tmp:
                # Remove -A when the scan is a ping scan.
                params = task['task_params']
                if params and params.find("-sP") > 0:
                    params = params.replace("-A", "")

                task_cmd_params = ('task_name=%s',
                                   'task_type=%s',
                                   'task_type_name=%s',
                                   'task_params=%s',
                                   'task_period=%s',
                                   'task_reliability=%s',
                                   'task_enable=%s',
                                   'task_last_run=%s')
                # e.g: task_name=%s,task_type=%s,task_type_name=%s,task_params=%s,task_period=%s and so on...
                task_cmd_params_str = ','.join(task_cmd_params)
                tasks.append(task_cmd_params_str % (task['task_name'], task['task_type'], task['task_type_name'],
                                                    params, str(task['task_period']), str(task['task_reliability']),
                                                    str(task['task_enable']), task['task_last_run']))
            if len(tasks) > 0:
                task_str = '|'.join(["%s" % s for s in tasks])
                new_command += task_str
                new_command += "}"
                if agent_ip in self.control_agents:
                    try:
                        if self.control_agents[agent_ip]:
                            self.control_agents[agent_ip].wfile.write(new_command + ' transaction="NA"\n')
                            logger.info("Updating inventory task agent: %s " % agent_ip)
                    except socket.error:
                        logger.warning("it can't send messages to :%s" % agent_ip)
                else:
                    logger.warning("No agent :%s" % agent_ip)
            else:
                logger.info("Empty inventory task list for sensor :%s!" % agent_ip)
        except Exception, e:
            logger.info(str(e))

    def update_agent_inventory_task_last_run_time(self, line):
        logger.debug('Got update task_last_run command: {}'.format(line))

        sensor_id = Util.get_var(
            "sensor_id=\"([0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12})\"", line)
        task_last_run_time = Util.get_var("task_last_run=\"([^\"]+)\"", line)
        task_type = Util.get_var("task_type=\"([^\"]+)\"", line)
        task_name = Util.get_var("task_name=\"([^\"]+)\"", line)

        query = ('UPDATE task_inventory SET task_last_run=%s '
                 'WHERE task_sensor=UNHEX(%s) AND task_name=%s AND task_type=%s;')
        try:
            sensor_id = sensor_id.replace('-', '')
            self.__myDB.exec_query(query, (int(task_last_run_time), sensor_id, task_name, task_type))
            logger.info('Task "{}" was updated with last_run time: {}'.format(task_name, task_last_run_time))
        except Exception as err:
            logger.error('Failed to update last execution time of "{}" task. Reason: {}'.format(task_name, str(err)))

    def get_agent_ip_from_sensor_table(self, requestor_ip):
        """When an agent connects to the framework it could use an virtual ip or the physical one.
        we need to know the one in the sensor table"""
        if not Util.isIPV4(requestor_ip):
            return requestor_ip

        query = ("SELECT INET6_NTOA(ip) AS ip FROM alienvault.system, alienvault.sensor "
                 "WHERE sensor_id = sensor.id "
                 "AND (admin_ip = INET6_ATON(%s) OR vpn_ip = INET6_ATON(%s) OR ha_ip = INET6_ATON(%s)) "
                 "ORDER BY ha_name LIMIT 1;")
        if not self.__myDB_connected:
            self.__myDB_connected = self.__myDB.connect()

        data = self.__myDB.exec_query(query, (requestor_ip, requestor_ip, requestor_ip))
        agent_ip = requestor_ip
        if len(data) == 1:
            if 'ip' in data[0]:
                agent_ip = data[0]['ip']
        return agent_ip

    def get_alert_netflow_setup(self):
        query = ("SELECT conf, value FROM config "
                 "WHERE conf IN ('tcp_max_download', 'tcp_max_upload', 'udp_max_download', "
                 "'udp_max_upload', 'agg_function', 'inspection_window');")
        if not self.__myDB_connected:
            self.__myDB_connected = self.__myDB.connect()

        try:
            raw_nf_data = self.__myDB.exec_query(query)
            nf_data = dict((record['conf'], record['value']) for record in raw_nf_data)
        except Exception as err:
            nf_data = defaultdict(lambda: 0)  # will return 0 by default instead of raising KeyError
            logger.error('Failed to get netflow setup data. Reason: {}'.format(str(err)))

        return nf_data

    def refreshAgentCache(self, requestor, agent_ip, agent_name):
        if not self.__myDB_connected:
            self.__myDB_connected = self.__myDB.connect()

        agent_id = requestor.get_sensorID()

        # read host list

        # v3 backward compatibility
        if agent_id == "":
            query = ('SELECT host.hostname AS hostname,'
                     'INET6_NTOA(host_ip.ip) AS ip,'
                     'host.fqdns AS fqdns '
                     'FROM host,host_ip '
                     'WHERE host.id=host_ip.host_id AND host.id IN '
                     '(SELECT host_id FROM sensor '
                     'INNER JOIN host_sensor_reference ON sensor.id = host_sensor_reference.sensor_id '
                     'WHERE sensor.ip = INET6_ATON(%s));')
            tmp = self.__myDB.exec_query(query, (agent_ip,))
        else:
            query = ('SELECT host.hostname AS hostname,'
                     'INET6_NTOA(host_ip.ip) AS ip,'
                     'host.fqdns AS fqdns '
                     'FROM host,host_ip '
                     'WHERE host.id=host_ip.host_id AND host.id IN '
                     '(SELECT host_id FROM host_sensor_reference '
                     'WHERE sensor_id=UNHEX(%s));')
            tmp = self.__myDB.exec_query(query, (agent_id.replace('-', ''),))

        # TODO: rewrite this part
        new_command = 'action="refresh_asset_list" list={'
        sendCommand = False
        for host in tmp:
            host_cmd = "%s=%s," % (host['ip'], host['hostname'])
            if host['fqdns'] is not None and host['fqdns'] != '':
                fqdns_list = host['fqdns'].split(',')
                for name in fqdns_list:
                    host_cmd += "%s," % name
            host_cmd = host_cmd[:-1]
            host_cmd += ';'
            sendCommand = True
            new_command += host_cmd
        new_command += '}'
        if sendCommand:
            requestor_ip = requestor.getRequestorIP()
            if requestor_ip in self.control_agents:
                try:
                    if self.control_agents[requestor_ip].wfile is not None:
                        logger.info("Updating asset list to agent: %s " % agent_ip)
                        try:
                            self.control_agents[requestor_ip].wfile.write(new_command + ' transaction="NA"\n')
                        except Exception, e:
                            logger.error("Can't send data to the socket: %s" % str(e))
                    else:
                        logger.info("[Not wfile] It can't send messages to: %s " % agent_ip)
                except socket.error:
                    logger.warning("it can't send messages to: %s" % agent_ip)
            else:
                logger.warning("No agent: %s" % agent_ip)
        else:
            logger.info("Empty asset list for sensor: %s!" % agent_ip)

    @staticmethod
    def getNagiosInventory():
        response = 'control action="getNagiosInventory" '
        strxml = ''
        nagios3_path = '/var/lib/nagios3/rw/live'
        if os.path.exists(nagios3_path):
            try:
                queryhost = "GET hosts\nColumns: address name state\nOutputFormat: python\n"
                connection = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
                connection.connect(nagios3_path)
                connection.send(queryhost)
                connection.shutdown(socket.SHUT_WR)
                datahost = connection.recv(100000000)
                connection.close()
                dd = ast.literal_eval(datahost)
                strxml = "<nagiosdiscovery>"
                for row in dd:
                    if len(row) == 3:
                        hostip = row[0]
                        hostname = row[1]
                        host_state = row[2]
                        strxml += "<host><ip>%s</ip><hostname>%s</hostname><host_state>%s</host_state>" % (hostip,
                                                                                                           hostname,
                                                                                                           host_state)
                        services = "GET services\nColumns: display_name state host_address\nOutputFormat: python\n"
                        connection = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
                        connection.connect(nagios3_path)
                        connection.send(services)
                        connection.shutdown(socket.SHUT_WR)
                        data = connection.recv(100000000)
                        connection.close()
                        host_services = ast.literal_eval(data)
                        strxml += "<services>"
                        for s in host_services:
                            if len(s) == 3:
                                if s[2] == hostip:
                                    strxml += "<service><name>%s</name><state>%s</state></service>" % (s[0], s[1])
                        strxml += "</services>"
                        strxml += "</host>"
                strxml += "</nagiosdiscovery>"
            except socket.error, e:
                logger.error("Can't connect with nagios mklivestatus socket: %s" % str(e))
            except Exception, e:
                logger.error("An error occurred while connecting with mklivestatus socket: %s" % str(e))
        else:
            logger.warning("%s doesn't exists. MkLiveStatus doesn't work" % nagios3_path)
        response += 'nagiosinventory="%s" \n' % strxml
        return response

    def printSensorList(self):
        for sensor_ip in self.control_agents:
            logger.info("Sensor: %s " % sensor_ip)

    def process(self, requestor, command, line):
        """Process all the requests coming from the webgui
        """
        self.__mutexRequest.acquire()
        response = ""
        try:
            action = Util.get_var("action=\"([^\"]+)\"", line)

            if action == "connect":
                id_ = Util.get_var("id=\"([^\"]+)\"", line)
                sensor_id = Util.get_var(
                    "sensor_id=\"([0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12})\"",
                    line
                )
                if id_ != "":
                    requestor.set_id(id_)
                else:
                    requestor.set_id("%s_%i" % requestor.client_address)

                requestor.set_sensorID(sensor_id)
                requestor_ip = requestor.getRequestorIP()
                logger.debug("Adding control agent %s to the list., with key %s [%s]" % (id_,
                                                                                         requestor_ip,
                                                                                         requestor.get_sensorID()))
                # add this connection to our control agent collection
                self.control_agents[requestor_ip] = requestor
                agent_ip = self.get_agent_ip_from_sensor_table(requestor_ip)
                self.__control_agents_connection_ip_vs_sensor_ip[agent_ip] = requestor_ip
                # indicate we're good to go
                response = 'ok id="%s"\n' % id_
                timer = Timer(5.0, self.refreshAgentCache, (requestor, requestor_ip, id_,))
                timer.start()
                timer = Timer(10.0, self.refreshAgentInventoryTasks, (requestor, requestor_ip, id_,))
                timer.start()

            elif action == "getconnectedagents":

                # set up response
                response = "control getconnectedagents"

                # indicate the number of agents connected
                keys = self.control_agents.keys()
                response += ' count="%d"' % len(keys)

                # build the connected list
                if keys:
                    # sort list before sending
                    keys.sort()
                    names = ""
                    for key in keys:
                        names += "%s=%s|" % (self.control_agents[key].getRequestorID(),
                                             self.get_agent_ip_from_sensor_table(key))
                    names = names[:-1]
                else:
                    names = ""

                response += ' names="%s" errno="0" error="Success." ackend\n' % names

            elif action == "gettotalagentsconnected":

                # set up response
                response = "control gettotalagentsconnected"

                # indicate the number of agents connected
                keys = self.control_agents.keys()
                response += ' count="%d" errno="0" error="Success." ackend\n' % len(keys)

            elif action == "getconnectedagents_p":

                # set up response
                response = "control getconnectedagents_p"
                try:
                    begin_index = int(Util.get_var("begin_index=\"([^\"]+)\"", line))
                except Exception:
                    begin_index = 0
                try:
                    end_index = int(Util.get_var("end_index=\"([^\"]+)\"", line))
                except Exception:
                    end_index = 0

                pag_size = end_index - begin_index
                real_size = 0
                if pag_size > 0:
                    # indicate the number of agents connected
                    keys = self.control_agents.keys()
                    response += ' count="%d"' % len(keys)
                    # build the connected list
                    if keys:
                        # sort list before sending
                        keys.sort()
                        if end_index >= len(keys):
                            page_keys = keys[begin_index:]
                        else:
                            page_keys = keys[begin_index:end_index]
                        real_size = len(page_keys)
                        names = ""
                        for key in page_keys:
                            names += "%s=%s|" % (self.control_agents[key].getRequestorID(),
                                                 self.get_agent_ip_from_sensor_table(key))
                        names = names[:-1]
                    else:
                        names = ""
                    response += ' page_size="%d" names="%s" errno="0" error="Success." ackend\n' % (real_size, names)
                else:
                    response += 'errno="-1" error="Invalid page size requested." ackend\n'
            elif action == "refresh_asset_list":
                for agent_id in self.control_agents.keys():
                    self.refreshAgentCache(self.control_agents[agent_id],
                                           self.control_agents[agent_id].getRequestorIP(),
                                           self.control_agents[agent_id].getRequestorID())
            elif action == "refresh_inventory_task":
                for agent_id in self.control_agents.keys():
                    self.refreshAgentInventoryTasks(self.control_agents[agent_id],
                                                    self.control_agents[agent_id].getRequestorIP(),
                                                    self.control_agents[agent_id].getRequestorID())
                response = "%s ok\n" % line

            # TODO refactoring needed: too many IFs
            elif action == 'update_task_last_run_time':
                logger.info('Got update request from sensor')
                self.update_agent_inventory_task_last_run_time(line)

            elif action == 'get_alert_nf_setup':
                response += 'control get_alert_nf_setup ' \
                            'agg_function="%(agg_function)s" inspection_window="%(inspection_window)s" ' \
                            'tcp_max_download="%(tcp_max_download)s" tcp_max_upload="%(tcp_max_upload)s" ' \
                            'udp_max_download="%(udp_max_download)s" udp_max_upload="%(udp_max_upload)s" ' \
                            'ackend\n' % self.get_alert_netflow_setup()

            elif action == "getNagiosInventory":
                logger.info("Request for Nagios inventory")
                response = self.getNagiosInventory()
            else:
                # check if we are a transaction
                transaction = Util.get_var("transaction=\"([^\"]+)\"", line)
                if transaction != "":
                    if transaction not in self.transaction_map:
                        logger.error("Transaction %s has no apparent originator!", transaction)

                    else:
                        # respond to the original requester
                        try:
                            self.transaction_map[transaction]["socket"].wfile.write(line + "\n")
                        except socket.error:
                            logger.warning("It can't write on requestor socket...")

                        # remove from map if end of transaction
                        if Util.get_var("(ackend)", line) != "":
                            logger.debug("Closing transaction: %s" % transaction)
                            if transaction in self.transaction_map:
                                del self.transaction_map[transaction]

                # assume we are a command request to an agent
                else:
                    id_ = Util.get_var("id=\"([^\"]+)\"", line)

                    if id_ == "" or id_ == "all":
                        logger.debug("Broadcasting to all ...")

                        if len(self.control_agents) == 0:
                            response = line + ' errno="-1" error="No agents available." ackend\n'

                        else:
                            # send line to each control agent
                            for key in self.control_agents:

                                # add this connection to the transaction map
                                transaction = self.__transaction_id_get()
                                self.transaction_map[transaction] = {'socket': requestor, 'time': time.time()}

                                # append the transaction to the message for tracking
                                try:
                                    self.control_agents[key].wfile.write(line + ' transaction="%s"\n' % transaction)
                                    logger.info("Sending command to agents: %s" % key)
                                except socket.error:
                                    logger.warning("It can't write on requestor socket...")

                    elif id_ in self.control_agents or id_ in self.__control_agents_connection_ip_vs_sensor_ip:
                        logger.debug("Broadcasting to %s ..." % id_)
                        if id_ not in self.control_agents:
                            id_ = self.__control_agents_connection_ip_vs_sensor_ip[id_]  # retrieve the connection ip
                        # add this connection to the transaction map
                        transaction = self.__transaction_id_get()
                        self.transaction_map[transaction] = {'socket': requestor, 'time': time.time()}

                        # append the transaction to the message for tracking
                        try:
                            self.control_agents[id_].wfile.write(line + ' transaction="%s"\n' % transaction)
                            logger.info("Sending command to agent:%s " % id_)
                        except socket.error:
                            logger.warning("It can't write on requestor socket...")
                    else:
                        response = line + ' errno="-1" error="Agent not available." ackend\n'
                        logger.warning('Agent "%s" is not connected! ' % id_)
        except Exception, e:
            logger.error(str(e))
        self.__mutexRequest.release()
        # send back our response
        return response

    def finish(self, requestor):
        req_id = requestor.get_id()

        # check if we were a control agent and cleanup
        if req_id is not None and req_id in self.control_agents:
            logger.debug('Removing control agent "%s" from the list.' % req_id)
            del self.control_agents[req_id]

        # clean up outstanding transactions
        for transaction in self.transaction_map:
            if self.transaction_map[transaction]["socket"] == requestor:
                logger.debug('Removing outstanding transaction: %s' % transaction)
                del self.transaction_map[transaction]

    def __transaction_id_get(self):
        # generate a transaction id to ensure returns are sent to the
        # original requester
        transaction = str(random.randint(0, 65535))
        while transaction in self.transaction_map:
            transaction = str(random.randint(0, 65535))
            logger.debug("Choosing transaction ID: %s" % transaction)

        return transaction

    def check_transaction_timeouts(self):
        if len(self.transaction_map) > 0:
            now = time.time()

            for transaction in self.transaction_map:
                delta = int(now - self.transaction_map[transaction]["time"])
                # return a timeout response and close the transaction as required
                if delta > self.__transaction_timeout:
                    response = ('control transaction="%s" errno="-1" '
                                'error="Transaction timed out due to inactivity for at least %d seconds." '
                                'ackend\n' % (transaction, delta))
                    if transaction in self.transaction_map:
                        if self.transaction_map[transaction]["socket"] is not None:
                            self.transaction_map[transaction]["socket"].wfile.write(response)
                            del self.transaction_map[transaction]


class DoControl(threading.Thread):

    def __init__(self, manager):
        self.__manager = manager
        threading.Thread.__init__(self)

    def run(self):
        while True:
            time.sleep(1)
            self.__manager.check_transaction_timeouts()
