#!/usr/bin/python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
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
import random, threading, time
import os
from threading import Timer, Lock

import socket
#
# LOCAL IMPORTS
#
from Logger import Logger
import Util
from OssimDB import OssimDB
from OssimConf import OssimConf
from DBConstantNames import *
from ApacheNtopProxyManager import ApacheNtopProxyManager
#
# GLOBAL VARIABLES
#
logger = Logger.logger

class ControlManager:
    def __init__(self, conf):
        logger.debug("Initialising ControlManager...")
        self.control_agents = {}
        self.transaction_map = {}
        self.__myconf = conf
        self.__myDB = OssimDB(conf[VAR_DB_HOST],
                              conf[VAR_DB_SCHEMA],
                              conf[VAR_DB_USER],
                              conf[VAR_DB_PASSWORD])
        self.__myDB_connected = self.__myDB.connect ()
        self.__transaction_timeout = 60
        self.__ntop_apache_manager = ApacheNtopProxyManager(conf)
        self.__control = DoControl(self)
        self.__control.start()
        self.__ntop_configuration_checked = False
        self.__mutexRquest = Lock()


    def refreshAgentInventoryTasks(self, requestor, agent_ip, agent_name):
        '''
        Send inventory task to the sensors
        '''
        try:
            if not self.__myDB_connected:
                self.__myDB_connected = self.__myDB.connect ()

            agent_id = requestor.get_sensorID()

            #read host list
            logger.info("Refresh agent inventory task agentID: %s agentname:%s" % (agent_ip, agent_name))

            # v3 backward compatibility
            if agent_id == "":
                query = 'select task_inventory.task_name, host_source_reference.id as task_type, host_source_reference.name as task_type_name, task_inventory.task_period, host_source_reference.relevance as task_reliability, task_inventory.task_enable, task_inventory.task_params from host_source_reference, sensor inner join task_inventory on sensor.id = task_inventory.task_sensor where sensor.ip = inet6_pton(\'%s\') and host_source_reference.id=task_inventory.task_type;' % agent_ip
            else:
                query = 'select task_inventory.task_name, host_source_reference.id as task_type, host_source_reference.name as task_type_name, task_inventory.task_period, host_source_reference.relevance as task_reliability, task_inventory.task_enable, task_inventory.task_params from host_source_reference, sensor inner join task_inventory on sensor.id = task_inventory.task_sensor where sensor.id = unhex("%s") and host_source_reference.id=task_inventory.task_type;' % agent_id.replace('-','')

            tmp = self.__myDB.exec_query(query)
            logger.info(tmp)
            new_command = 'action="refresh_inventory_task" inventory_task_list={'
            tasks = []
            logger.info(tmp)
            for task in tmp:
                #Remove -A when the scan is a ping scan.
                params = task['task_params']
                if params and params.find("-sP") > 0:
                    params = params.replace("-A","")
                tasks.append("task_name=%s,task_type=%s,task_type_name=%s,task_params=%s,task_period=%s,task_reliability=%s,task_enable=%s" % \
                             (task['task_name'], task['task_type'],task['task_type_name'], params, \
                             str(task['task_period']), str(task['task_reliability']), str(task['task_enable'])))
            if len(tasks) > 0:
                task_str = '|'.join(["%s" % s for s in tasks])
                new_command += task_str
                new_command += "}"
                if self.control_agents.has_key(agent_ip):
                    try:
                        if self.control_agents[agent_ip]:
                            self.control_agents[agent_ip].wfile.write(new_command + ' transaction="NA"\n')
                            logger.info("Updating inventory task agent: %s " % (agent_ip))
                    except socket.error, e:
                        logger.warning("it can't send messages to :%s" % agent_ip)
                else:
                    logger.warning("No agent :%s" % agent_ip)
            else:
                logger.info("Empty inventory task list for sensor :%s!" % agent_ip)
        except Exception,e:
            logger.info(str(e))


    def refreshAgentCache(self, requestor, agent_ip, agent_name):
        if not self.__myDB_connected:
            self.__myDB_connected = self.__myDB.connect ()

        agent_id = requestor.get_sensorID()

        #read host list

        # v3 backward compatibility
        if agent_id == "":
            query = 'select host.hostname as hostname,inet6_ntop(host_ip.ip) as ip,host.fqdns as fqdns from host,host_ip \
                    where host.id=host_ip.host_id and host.id in (select host_id from sensor inner join host_sensor_reference on sensor.id = host_sensor_reference.sensor_id where sensor.ip = inet6_pton(\'%s\'));' % agent_ip
        else:
            query = 'select host.hostname as hostname,inet6_ntop(host_ip.ip) as ip,host.fqdns as fqdns from host,host_ip \
                    where host.id=host_ip.host_id and host.id in (select host_id from host_sensor_reference where sensor_id=unhex("%s"));' % agent_id.replace('-','')
        
        tmp = self.__myDB.exec_query(query)
        logger.info(query)
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
        new_command[:-1]
        new_command += '}'
        # add this connection to the transaction map
        #transaction = self.__transaction_id_get()
        #self.transaction_map[transaction] = {'socket':requestor, 'time':time.time()}
        # append the transaction to the message for tracking
        if sendCommand:
            if self.control_agents.has_key(requestor.getRequestorIP()):
                try:
                    if self.control_agents[requestor.getRequestorIP()].wfile is not None:
                        logger.info("Updating asset list to agent: %s " % (agent_ip))
                        try:
                            self.control_agents[requestor.getRequestorIP()].wfile.write(new_command + ' transaction="NA"\n')
                        except Exception,e:
                            logger.error("Can't send data to the socket: %s" % str(e))
                    else:
                        logger.info("[Not wfile] It can't send messages to :%s " % agent_ip)
                except socket.error, e:
                    logger.warning("it can't send messages to :%s" % agent_ip)
                
            else:
                logger.warning("No agent :%s" % agent_ip)
        else:
            logger.info("Empty asset list for sensor :%s!" % agent_ip)


    def getOCSInventoryData(self):
        if not self.__myDB_connected:
            self.__myDB_connected = self.__myDB.connect ()
        #read host list
        host_query = "SELECT hw.ipaddr AS ip, hw.workgroup AS domain, hw.name AS hostname, CONCAT(hw.osname, '_', hw.osversion) AS os, nets.macaddr AS mac, CONCAT(hw.processort, ' ', hw.processors) AS cpu, hw.memory, GROUP_CONCAT(vs.name SEPARATOR ', ') AS video FROM ocsweb.hardware AS hw LEFT JOIN ocsweb.networks AS nets ON hw.ipaddr = nets.ipaddress LEFT JOIN ocsweb.videos AS vs ON hw.id = vs.hardware_id GROUP BY vs.hardware_id;"

        tmp = self.__myDB.exec_query(host_query)
        response = 'control action="getOCSInventory" '
        #{'ip': '170.100.50.6', 'domain': 'ferrominera.com', 'hostname': 'ABERNAS', 'os': 'Microsoft Windows Server 2003 R2 Enterprise Edition_5.2.3790', 'mac': '00:1D:60:E5:F4:EC'}
        ocsinventory_str = "<ocsinventory>"
        for row in tmp:
            ocsinventory_str += "<host><ip>%s</ip><domain>%s</domain><hostname>%s</hostname><os>%s</os><mac>%s</mac><cpu>%s Hz</cpu><memory>%s</memory><video>%s</video></host>" % (row['ip'], \
                                                                                                                             row['domain'], \
                                                                                                                             row['hostname'], \
                                                                                                                             row['os'], \
                                                                                                                             row['mac'], \
															     row['cpu'], \
															     row['memory'], \
															     row['video'])
        ocsinventory_str += "</ocsinventory>"
        response += 'ocsinventory="%s" \n' % ocsinventory_str 
        return response


    def getNagiosInventory(self):
        response = 'control action="getNagiosInventory" '
        strxml = ''
        if os.path.exists("/var/lib/nagios3/rw/live"):
            try:
                queryhost = "GET hosts\nColumns: address name state\nOutputFormat: python\n"
                connection = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
                connection.connect("/var/lib/nagios3/rw/live")
                connection.send(queryhost)
                connection.shutdown(socket.SHUT_WR)
                datahost = connection.recv(100000000)
                connection.close()
        #        print datahost
                dd = eval(datahost)
                strxml = "<nagiosdiscovery>"
                for row in dd:
                    if len(row) == 3:
                        
                        
                        hostip = row[0]
                        hostname = row[1]
                        host_state = row[2]
                        strxml += "<host><ip>%s</ip><hostname>%s</hostname><host_state>%s</host_state>" % (hostip, hostname, host_state)
                        services = "GET services\nColumns: display_name state host_address\nOutputFormat: python\n"
                        connection = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
                        connection.connect("/var/lib/nagios3/rw/live")
                        connection.send(services)
                        connection.shutdown(socket.SHUT_WR)
                        data = connection.recv(100000000)
                        connection.close()
                        host_services = eval(data)
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
            logger.warning("%s doesn't exists. MkLiveStatus doesn't work" % "/var/lib/nagios3/rw/live")
        response += 'nagiosinventory="%s" \n' % strxml
        return response


    def printSensorList(self):
        for sensor_ip, requestor_obj in self.control_agents.items():
            logger.info("Sensor: %s " % (sensor_ip))


    def process(self, requestor, command, line):
        """Process all the requests coming from the webgui
        """
        self.__mutexRquest.acquire()
        try:
            response = ""
            action = Util.get_var("action=\"([^\"]+)\"", line)

            if action == "connect":
                id = Util.get_var("id=\"([^\"]+)\"", line)
                sensor_id = Util.get_var("sensor_id=\"([0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12})\"", line)
                if id != "":
                    requestor.set_id(id)
                else:
                    requestor.set_id("%s_%i" % (requestor.client_address))
                requestor.set_sensorID(sensor_id)
                logger.debug("Adding control agent %s to the list., with key %s [%s]" % (id, requestor.getRequestorIP(),requestor.get_sensorID()))
                # add this connection to our control agent collection
                self.control_agents[requestor.getRequestorIP()] = requestor
                # indicate we're good to go
                response = 'ok id="%s"\n' % id
                self.__ntop_apache_manager.refreshDefaultNtopConfiguration(first_sensor_name=id, must_reload=True)
                timer = Timer(5.0, self.refreshAgentCache, (requestor, requestor.getRequestorIP(), id,))
                timer.start()
                timer = Timer(10.0, self.refreshAgentInventoryTasks, (requestor, requestor.getRequestorIP(), id,))
                timer.start()

            elif action == "getconnectedagents":

                # set up response
                response = "control getconnectedagents"

                # indicate the number of agents connected
                keys = self.control_agents.keys()
                response += ' count="%d"' % len(keys)

                # build the connected list
                if keys != None:
                    # sort list before sending
                    keys.sort()
                    names = ""
                    for key in keys:
                        names += "%s=%s|" % (self.control_agents[key].getRequestorID(), key)
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
                begin_index = 0 
                end_index = 0 
                try:
                    begin_index = int(Util.get_var("begin_index=\"([^\"]+)\"", line))
                except:
                    begin_index = 0
                try:
                    end_index = int(Util.get_var("end_index=\"([^\"]+)\"", line))
                except:
                    end_index = 0

                pag_size = end_index - begin_index
                real_size = 0
                if pag_size > 0:
                    # indicate the number of agents connected
                    keys = self.control_agents.keys()
                    response += ' count="%d"' % len(keys)
                    # build the connected list
                    if keys != None:
                        # sort list before sending
                        keys.sort()
                        if end_index >= len(keys):
                            page_keys = keys[begin_index:]
                        else:
                            page_keys = keys[begin_index:end_index]
                        real_size = len(page_keys)
                        names = ""
                        for key in page_keys:
                            names += "%s=%s|" % (self.control_agents[key].getRequestorID(), key)
                        names = names[:-1]
                        #names = "|".join(page_keys)
                    else:
                        names = ""
                    response += ' page_size="%d" names="%s" errno="0" error="Success." ackend\n' % (real_size, names)
                else:
                    response += 'errno="-1" error="Invalid page size requested." ackend\n' 
            elif action == "refresh_asset_list":
                for agent_id in self.control_agents.keys():
                    self.refreshAgentCache(self.control_agents[agent_id], self.control_agents[agent_id].getRequestorIP(), self.control_agents[agent_id].getRequestorID())
            elif action == "refresh_inventory_task":
                for agent_id in self.control_agents.keys():
                    self.refreshAgentInventoryTasks(self.control_agents[agent_id], self.control_agents[agent_id].getRequestorIP(), self.control_agents[agent_id].getRequestorID())
                response="%s ok\n" % line
            elif action == "getOCSInventory":
                logger.info("Request for ocs inventory")
                response = self.getOCSInventoryData()
            elif action == "getNagiosInventory":
                logger.info("Request for ocs inventory")
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
                        except socket.error, e:
                            logger.warning("It can't write on requestor socket...")

                        # remove from map if end of transaction
                        if Util.get_var("(ackend)", line) != "":
                            logger.debug("Closing transaction: %s" % transaction)
                            if self.transaction_map.has_key(transaction):
                                del self.transaction_map[transaction]

                # assume we are a command request to an agent
                else:
                    id = Util.get_var("id=\"([^\"]+)\"", line)

                    if id == "" or id == "all":
                        logger.debug("Broadcasting to all ...")

                        if len(self.control_agents) == 0:
                            response = line + ' errno="-1" error="No agents available." ackend\n'

                        else:
                            # send line to each control agent
                            for key in self.control_agents:

                                # add this connection to the transaction map
                                transaction = self.__transaction_id_get()
                                self.transaction_map[transaction] = {'socket':requestor, 'time':time.time()}

                                # append the transaction to the message for tracking
                                try:
                                    self.control_agents[key].wfile.write(line + ' transaction="%s"\n' % transaction)                                    
                                    logger.info("Sending command to agents: %s" % ( key))
                                except socket.error, e:
                                    logger.warning("It can't write on requestor socket...")
                                    
                    elif id in self.control_agents:
                        logger.debug("Broadcasting to %s ..." % id)

                        # add this connection to the transaction map
                        transaction = self.__transaction_id_get()
                        self.transaction_map[transaction] = {'socket':requestor, 'time':time.time()}

                        # append the transaction to the message for tracking
                        try:
                            self.control_agents[id].wfile.write(line + ' transaction="%s"\n' % transaction)
                            logger.info("Sending command to agent:%s " % (id))
                        except socket.error, e:
                            logger.warning("It can't write on requestor socket...")
                    else:
                        response = line + ' errno="-1" error="Agent not available." ackend\n'
                        logger.warning('Agent "%s" is not connected! ' % (id))
        except Exception, e:
            logger.error(str(e))
        self.__mutexRquest.release()
        # send back our response
        return response


    def finish(self, requestor):
        id = requestor.get_id()

        # check if we were a control agent and cleanup
        if id is not None and id in self.control_agents:
            logger.debug('Removing control agent "%s" from the list.' % id)
            del self.control_agents[id]

        # clean up outstanding transactions
        for t in self.transaction_map.keys():
            if self.transaction_map[t]["socket"] == requestor:
                logger.debug('Removing outstanding transaction: %s' % t)
                del self.transaction_map[t]


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

            for t in self.transaction_map.keys():
                delta = int(now - self.transaction_map[t]["time"])
                # return a timeout response and close the transaction as required
                if delta > self.__transaction_timeout:
                    response = 'control transaction="%s" errno="-1" error="Transaction timed out due to inactivity for at least %d seconds." ackend\n' % (t, delta)
                    if self.transaction_map.has_key(t):
                        if self.transaction_map[t]["socket"] is not None:
                            self.transaction_map[t]["socket"].wfile.write(response)
                            del self.transaction_map[t]



class DoControl(threading.Thread):

    def __init__(self, manager):
        self.__manager = manager
        threading.Thread.__init__(self)


    def run(self):
        while 1:
            time.sleep(1)
            self.__manager.check_transaction_timeouts()



