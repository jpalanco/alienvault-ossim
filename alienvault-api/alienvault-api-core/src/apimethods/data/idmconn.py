# License:
#
# Copyright (c) 2007 - 2015 AlienVault
# All rights reserved.
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
from apimethods.data.event import HostInfoEvent
from apimethods.data.connection import Connection
import uuid
import api_log
import re

NMAP_INVENTORY_SOURCE_ID = 5


class IDMConnection():
    """Class that manages the connection with an ossim-server"""

    CONNECT_MESSAGE = 'connect id="{0}" type="web" sensor_id="{1}"\n'
    CONNECT_MESSAGE_2 = 'connect id="{0}" type="web"\n'
    CONNECTION_TIMEOUT = 30

    def __init__(self, ip="127.0.0.1", port=40002, sensor_id=None):
        """Constructor
        :param ip (string) String-dotted IP address
        :param port(int) Service port
        """
        self.__conn = None
        self.__ip = ip
        self.__port = port
        self.__sequence_id = 1
        self.__sensor_id = sensor_id if sensor_id is None else str(uuid.UUID(sensor_id))


    #################################################################
    #                   GETTERS/SETTERS
    #################################################################

    @property
    def conn(self):
        return self.__conn

    @conn.setter
    def conn(self, value):
        self.__conn = value

    @property
    def ip(self):
        return self.__ip

    @property
    def port(self):
        return self.__port

    @port.setter
    def port(self, value):
        self.__port = value

    @property
    def sequence_id(self):
        return self.__sequence_id

    @sequence_id.setter
    def sequence_id(self, value):
        self.__sequence_id = value

    @property
    def sensor_id(self):
        return self.__sensor_id

    @property
    def connected(self):
        if self.conn is not None:
            return self.conn.connected
        return False

    def __process_connection_message_response(self, response):
        api_log.debug("IDM connector - Recv: %s" % response)
        success = False
        if response == 'ok id="' + str(self.sequence_id) + '"\n':
            success = True
        else:
            api_log.error("IDM connector - Bad response from %s (seq_exp:%s): %s " % (self.ip, self.sequence_id, str(response)))
        return success

    #################################################################
    #                   PUBLIC METHODS
    #################################################################

    def connect(self, attempts=3, wait_time=10):
        """Connects to the server and starts the handshake"""
        try:
            tries = 0
            connected = False
            self.close()
            while tries < attempts and connected is False:
                if tries > 0:
                    time.sleep(wait_time)
                tries += 1
                api_log.debug("IDM connector - Tries: %s connected: %s" % (tries, connected))
                api_log.debug("IDM connector - Conn: %s" % self.conn)
                if self.conn is not None:
                    self.conn.close()
                    self.conn = None
                self.conn = Connection(ip=self.ip, port=self.port)

                api_log.debug("IDM connector - Creating a new Connection object")

                if not self.conn.connect(attempts=attempts, default_wait=wait_time):
                    continue
                # Send the connection message:
                self.sequence_id += 1

                if self.sensor_id is None:
                    connect_message = self.CONNECT_MESSAGE_2.format(self.sequence_id)
                else:
                    connect_message = self.CONNECT_MESSAGE.format(self.sequence_id, self.sensor_id)

                api_log.error("IDM connector - Send: {0}".format(connect_message))
                if not self.send(connect_message):
                    api_log.error("IDM connector - Cannot send the connection message")
                    self.conn.close()
                    continue

                connection_message_response = self.recv()
                if not self.__process_connection_message_response(connection_message_response):
                    api_log.error("IDM connector - Cannot connect to the server, invalid response")
                    self.conn.close()
                    continue
                connected = True
            if not connected:
                return False
        except Exception as err:
            api_log.debug("IDM connector - Cannot connect to the server.... %s" % str(err))
            self.close()
            return False
        return True

    def recv(self):
        return self.conn.receive()

    def send(self, data):
        """Uses the connection and sends the data"""
        success = True
        if self.conn is None:
            return False
        try:
            if not self.conn.send(data):
                success = False
        except Exception as err:
            api_log.error("IDM connector - Cannot send data {0}".format(err))
            success = False
        return success

    def close(self):
        try:
            if self.conn is not None:
                self.conn.close()
                time.sleep(1)
        except Exception as err:
            api_log.error("IDM connector - Cannot close the connection properly... %s" % str(err))

    def send_events_from_hosts(self, host_list):
        """Builds an IDM Event from a host dictionary and send it to the IDM"""
        for host, host_data in host_list.iteritems():
            try:
                h = HostInfoEvent()
                h["ip"] = host
                h["mac"] = host_data["host_mac"]
                h["hostname"] = host_data["hostname"]
                os_family_data = None
                software_cpe = set()
                hardware_cpe = set()
                so_cpe = set()
                if "osclass" in host_data:
                    accuracy = -1
                    for osfamily in host_data["osclass"]:
                        if osfamily["accuracy"] == '' or osfamily["accuracy"] is None:
                            current_accuracy = 0
                        else:
                            current_accuracy = int(osfamily["accuracy"])
                        if current_accuracy > accuracy:
                            os_family_data = osfamily
                            accuracy = current_accuracy
                if os_family_data is not None:
                    # The same parsing the the ossim-agent does.
                    h["os"] = '{0} {1}'.format(os_family_data['osfamily'], os_family_data['osgen'])

                port_protocol_info = []
                for protocol in ["udp", "tcp", "sctp"]:
                    if protocol in host_data:
                        for port, port_data in host_data[protocol].iteritems():
                            port_state = port_data["state"]
                            if port_state != "open":
                                continue
                            port_cpe = port_data["cpe"]
                            port_service_name = port_data["name"]
                            banner = []
                            for value in ["product", "version", "extrainfo"]:
                                banner_data = port_data[value]
                                if banner_data is not None:
                                    banner.append(banner_data)
                            local_software = []
                            if port_cpe:
                                for cpe in port_cpe:
                                    if not banner:
                                        banner.append(' '.join([s[0].upper() + s[1:] for s in re.sub(':',' ',re.sub(r"^cpe:/.:", '', re.sub(r":+", ':', cpe))).split(' ')]))
                                    cpe += '|'
                                    cpe += (' '.join(banner)).lstrip(' ')
                                    if cpe.startswith('cpe:/o:'):
                                        so_cpe.add(cpe)
                                    elif cpe.startswith('cpe:/h:'):
                                        hardware_cpe.add(cpe)
                                    else:
                                        software_cpe.add(cpe)
                                        local_software.append(cpe)

                            if software_cpe:
                                port_protocol_info.append("{0}|{1}|{2}|{3}".format(protocol,port, port_service_name, ','.join(local_software)))
                            else:
                                port_protocol_info.append("{0}|{1}|{2}".format(protocol,port, port_service_name))

                h["service"] = ','.join(port_protocol_info)
                all_software = set()
                all_software.update(software_cpe)
                all_software.update(so_cpe)
                all_software.update(hardware_cpe)
                h["software"] = ','.join(all_software)
                h["inventory_source"] = NMAP_INVENTORY_SOURCE_ID
                api_log.info("IDM Event: {0}".format(str(h)))
                self.send(str(h))
            except Exception as e:
                api_log.error("IDM connector, cannot send the event {0}".format(str(e)))

    def reload_hosts(self):
        """Builds an IDM message to reload the hosts"""
        try:
            self.sequence_id += 1

            message = 'reload-hosts id="' + str(self.sequence_id) + '"\n'

            api_log.info("Sending the reload host message")

            self.send(message)

            connection_message_response = self.recv()

            if not self.__process_connection_message_response(connection_message_response):
                api_log.error("Server connector - Cannot connect to the server, invalid response")

        except Exception as e:
            api_log.debug("Server connector, cannot send the reload host message")
