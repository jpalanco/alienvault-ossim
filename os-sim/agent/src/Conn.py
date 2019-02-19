#
# License:
#
# Copyright (c) 2003-2006 ossim.net
# Copyright (c) 2007-2014 AlienVault
# All rights reserved.
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
import re
import socket
import time
import threading
from base64 import b64decode
from struct import unpack
from bson import BSON, InvalidBSON
from bson.binary import STANDARD
from bson.codec_options import CodecOptions
from StringIO import StringIO
#
# LOCAL IMPORTS
#
from Control import ControlManager
from Event import WatchRule
from base64 import b64decode
from Logger import *
from MonitorScheduler import MonitorScheduler
from Stats import Stats
from Watchdog import Watchdog
from command import AppendPlugin, \
    AgentServerConnectionMessage, \
    AgentFrameworkConnectionMessage, \
    AgentServerCommandPong, \
    AgentFrameworkCommandPong, \
    AgentFrameworkCommand
from __init__ import __version__

#
# GLOBAL VARIABLES
#
logger = Logger.logger
MAX_TRIES = 3
CONNECTION_TYPE_SERVER = 1
CONNECTION_TYPE_FRAMEWORK = 2
CONNECTION_TYPE_IDM = 3

g_lock_uuid = threading.Lock()


class Connection(object):
    """Generic connection class to manage AlienVault servers connections
    """

    def __init__(self,
                 connection_id,
                 connection_ip,
                 connection_port,
                 sensor_id,
                 system_id_file,
                 connection_type):
        self.__is_alive = False
        self.__ip = connection_ip
        self.__id = connection_id
        self.__port = connection_port
        self.__socket_conn = None
        self.__connection_type = connection_type
        self.__sequence = 1
        self.__close_lock = threading.RLock()
        self.__send_lock = threading.RLock()
        self.__connect_lock = threading.RLock()
        self.__sensor_id = sensor_id
        self.__system_id_file = system_id_file
        self.__pattern_change_sensor_id = re.compile(
            'noack id="(?P<sec>\d+)" your_sensor_id="(?P<uuid>[a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{12})"')
        # Runs using bson protocol. By default try to use BSON.
        self.__bson_protocol = True
        self.__server_name = "AlienVault Server"
        if self.__connection_type == CONNECTION_TYPE_IDM:
            self.__server_name = "AlienVault IDM"
        elif self.__connection_type == CONNECTION_TYPE_FRAMEWORK:
            self.__server_name = "AlienVault Framework Daemon"
        self.__sensor_id_change_request_received = False

    @property
    def ip(self):
        return self.__ip

    @property
    def port(self):
        return self.__port

    @property
    def bson_protocol(self):
        return self.__bson_protocol

    def __get_connection_message(self, bson_protocol):
        if self.__connection_type in [CONNECTION_TYPE_SERVER, CONNECTION_TYPE_IDM]:
            msg = AgentServerConnectionMessage(sequence_id=self.__sequence, sensor_id=str(self.__sensor_id))
            if bson_protocol:
                return msg.to_bson()
            else:
                return msg.to_string()
        elif self.__connection_type == CONNECTION_TYPE_FRAMEWORK:
            return AgentFrameworkConnectionMessage(connection_id=self.__id,
                                                   sensor_id=str(self.__sensor_id)).to_string()

    def __process_connection_message_bson(self, response):
        # Must BE a string that can be decoded to BSON
        message_ok = False
        try:
            bson_msg = BSON.decode(BSON(response), codec_options=CodecOptions(uuid_representation=STANDARD))
            if bson_msg.get('ok') is not None:
                if bson_msg['ok'].get('id', None) == self.__sequence:
                    message_ok = True
            elif bson_msg.get('noack') is not None:
                your_sensor_id = bson_msg['noack'].get('your_sensor_id', None)
                if your_sensor_id is not None:
                    self.__sensor_id_change_request_received = True
                    logger.debug("UUID change request from :%s " % self.__server_name)
                    g_lock_uuid.acquire()
                    self.__write_new_system_id(str(your_sensor_id))
                    self.__sensor_id = str(your_sensor_id)
                    g_lock_uuid.release()
                else:
                    logger.error("Bad response from server")
        except InvalidBSON:
            logger.error("Bad response from server {0}".format(response))
        return message_ok

    def __process_connection_message_plain(self, response):
        message_ok = False
        if response == 'ok id="' + str(self.__sequence) + '"\n':
            message_ok = True
        else:
            match_data = self.__pattern_change_sensor_id.match(response)
            if match_data:
                self.__sensor_id_change_request_received = True
                dic = match_data.groupdict()
                logger.debug("UUID change request from :%s " % self.__server_name)
                g_lock_uuid.acquire()
                self.__write_new_system_id(dic['uuid'])
                self.__sensor_id = str(dic['uuid'])
                g_lock_uuid.release()
            else:
                logger.error(
                    "Bad response from %s (seq_exp:%s): %s " % (self.__server_name, self.__sequence, str(response)))
        return message_ok

    def __process_connection_message_framework(self, response):
        expected_response = 'ok id="' + str(self.__id) + '"\n'
        if response == expected_response:
            return True
        return False

    def __check_connection_message_response(self, response, bson_protocol=False):
        message_ok = False
        if response is None:
            return message_ok
        if self.__connection_type in [CONNECTION_TYPE_SERVER, CONNECTION_TYPE_IDM]:
            if bson_protocol:
                message_ok = self.__process_connection_message_bson(response)
            else:
                message_ok = self.__process_connection_message_plain(response)
        elif self.__connection_type == CONNECTION_TYPE_FRAMEWORK:
            message_ok = self.__process_connection_message_framework(response)
        return message_ok

    def __get_socket(self, blocking=1, timeout=None):
        """Returns a socket object with the blocking and timeout configured
        Args:
            blocking: 0 or 1
            timeout: Socket timeout
        Returns:
            the socket object
        """
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.setblocking(blocking)
        sock.settimeout(timeout)
        return sock

    def __connect_bson(self):
        """Attempts to connect using BSON protocol"""
        # When the ossim-server < 5.1 receives an unknown connect message it doesn't respond anything
        # so the socket keeps blocked. Modify socket parameters to avoid that:
        # wait 30s for a response, if no response break out the connection
        connected = False
        self.__socket_conn = self.__get_socket(blocking=1, timeout=60)
        self.__socket_conn.connect((self.__ip, int(self.__port)))
        connection_message = self.__get_connection_message(bson_protocol=True)
        bytes_sent = self.__socket_conn.send(connection_message)
        if bytes_sent != len(connection_message):
            self.close()
            logger.error("Cannot send all the bytes in the message")
        data = self.recv_bson()

        if self.__check_connection_message_response(data, bson_protocol=True):
            logger.info("Connected to %s at  %s:%s!" % (self.__server_name, self.__ip, self.__port))
            connected = True
            self.__socket_conn.setblocking(1)
            self.__socket_conn.settimeout(None)
        else:
            self.close()
        return connected

    def __connect_plain(self):
        """Attempts to connect using plain protocol"""
        # When the ossim-server < 5.1 receives an unknown connect message it doesn't respond anything
        # so the socket keeps blocked. Modify socket parameters to avoid that:
        connected = False
        self.__socket_conn = self.__get_socket(blocking=1, timeout=60)
        self.__socket_conn.connect((self.__ip, int(self.__port)))
        connection_message = self.__get_connection_message(bson_protocol=False)
        bytes_sent = self.__socket_conn.send(connection_message)
        if bytes_sent != len(connection_message):
            self.close()
            logger.error("Cannot send all the bytes in the message")
        data = self.recv_line_text()
        if self.__check_connection_message_response(data, bson_protocol=False):
            logger.info("Connected to %s at  %s:%s!" % (self.__server_name, self.__ip, self.__port))
            connected = True
            self.__socket_conn.setblocking(1)
            self.__socket_conn.settimeout(None)
        else:
            self.close()
        return connected

    def __connect(self):
        if self.__is_alive:
            return
        if self.__connection_type in [CONNECTION_TYPE_SERVER, CONNECTION_TYPE_IDM]:
            while True:
                connected_bson = self.__connect_bson()
                if connected_bson:
                    logger.info("Connected using BSON protocol")
                    self.__is_alive = True
                    return self.__is_alive
                elif not self.__sensor_id_change_request_received:
                    break
                # If the system cannot connect but an new sensor id is received,
                # try again
                self.__sensor_id_change_request_received = False
                logger.info("New sensor id provided, trying connection again")
            # We cannot connect using bson protocol, try using plain connection
            logger.warning("Cannot connect using BSON protocol. Trying plain connection.")
            while True:
                connected_plain = self.__connect_plain()
                if connected_plain:
                    logger.info("Connected using PLAIN protocol")
                    self.__is_alive = True
                    return self.__is_alive
                elif not self.__sensor_id_change_request_received:
                    break
                # If the system cannot connect but an new sensor id is received,
                # try again
                self.__sensor_id_change_request_received = False
                logger.info("New sensor id provided, trying connection again")

        else:
            # It's a framework-daemon connection
            if not self.__connect_plain():
                logger.warning("Cannot connect ")
            else:
                self.__is_alive = True
        return self.__is_alive

    def connect(self, attempts=3, wait=10.0):
        """Establishes a connection:
            - attempts == 0 means that agent try to connect forever
            - wait = seconds between attempts
        """

        self.__connect_lock.acquire()
        try:
            if self.__socket_conn is None:
                logger.info("Connecting to (%s, %s).." % (self.__ip, self.__port))
                while attempts > 0:
                    if self.__connect():
                        break
                    logger.error("Can't connect to server ({0}:{1}), retrying in {2} seconds".format(self.__ip,
                                                                                                     self.__port,
                                                                                                     wait))
                    time.sleep(wait)
                    # check #attempts
                    attempts -= 1
            else:
                logger.warning("Reusing server connection (%s, %s).." % (self.__ip, self.__port))
        except Exception as exp:
            import traceback
            print traceback.format_exc()
            logger.error("Cannot connect {0}".format(str(exp)))
            self.close()
        finally:
            self.__connect_lock.release()

        return self.__socket_conn

    def __write_new_system_id(self, new_id):
        """Write the new system uuid sends by the ossim-server.
        """
        try:
            with open(self.__system_id_file, 'w') as f:
                f.write(new_id)
            os.chmod(self.__system_id_file, 0644)
        except Exception, e:
            logger.error("Can't write sensor file..:%s" % str(e))

    def close(self):
        """Closes the connection.
        """
        self.__close_lock.acquire()
        try:

            if self.__socket_conn is not None:
                self.__socket_conn.shutdown(socket.SHUT_RDWR)
                self.__socket_conn.close()

        except Exception as exp:
            logger.error("Cannot close the connection cleanly {0}".format(exp))
        finally:
            self.__socket_conn = None
            self.__is_alive = False
            self.__close_lock.release()

    def get_ip(self):
        """Returns the connection IP
        """
        return self.__ip

    def get_port(self):
        """Returns the connection port
        """
        return self.__port

    def get_alive(self):
        """Returns true if the server is alive otherwise returns false
        """
        return self.__is_alive

    def get_addr(self):
        """Returns the connection address.
        """
        return self.__socket_conn.getsockname()

    def get_hash(self):
        return self.__ip + ":" + self.__port

    def recv_line_text(self):
        """receive a line from the connection
        """
        keep_reading = True
        data = ''
        while keep_reading and self.__socket_conn:
            try:
                char = self.__socket_conn.recv(1)
                data += char
                if char == '\n' or char == '' or char is None:
                    keep_reading = False
                if char == '' or char is None:
                    self.close()
            except socket.timeout:
                pass
            except socket.error, e:
                logger.error('Error receiving data from server: ' + str(e))
                keep_reading = False
                self.close()
            except AttributeError, e:
                logger.error('Error receiving data from server - Attribute Error: %s' % str(e))
                keep_reading = False
                self.close()
        return data

    def __recv_bytes(self, bytes_needed, buffer_data=None, read_size=1):
        """Receives bytes from the socket connection
        Args:
            bytes_needed: Number of bytes you want to read
            buffer_data (StringIO  or None)
            read_size
        Returns:
            StringIO with the data read or None when no data.
        Raises:
            All socket exceptions"""
        if buffer_data is None:
            buffer_data = StringIO()
        bytes_read = 0
        while bytes_read < bytes_needed:
            chunk = self.__socket_conn.recv(read_size)  # Must read 4 bytes
            chunk_len = len(chunk)
            if chunk_len < 1:
                return None
            bytes_read += chunk_len
            buffer_data.write(chunk)
        return buffer_data

    def recv_bson(self):
        """Reads a bson object
        """
        try:
            # We need to read
            buffer_data = self.__recv_bytes(bytes_needed=4)
            if buffer_data is None:
                return None

            message_length, = unpack("<L", buffer_data.getvalue())
            bytes_needed = message_length - 4
            buffer_data = self.__recv_bytes(bytes_needed=bytes_needed, buffer_data=buffer_data)
            bson_object = buffer_data.getvalue()
            buffer_data.close()
        except socket.error as sock_error:
            logger.error('Error receiving data from server - Attribute Error: %s' % str(sock_error))
            self.close()
            bson_object = None
        return bson_object

    def recv_line(self):
        if self.bson_protocol and self.__connection_type in [CONNECTION_TYPE_IDM, CONNECTION_TYPE_SERVER]:
            data = self.recv_bson()
        else:
            data = self.recv_line_text()
        return data

    def send(self, command):
        """Sends a message over the connection socket
        @param command Message to send
        """
        if self.__is_alive:
            self.__send_lock.acquire()
            try:
                if self.bson_protocol and self.__connection_type in [CONNECTION_TYPE_IDM, CONNECTION_TYPE_SERVER]:
                    msg = command.to_bson()
                    self.__socket_conn.send(msg)
                else:
                    self.__socket_conn.send(command.to_string())
            except socket.error as socket_error:
                logger.error("Connection crash: {0}".format(str(socket_error)))
                self.close()
            except AttributeError as attr_error:
                logger.error("Connection crash: {0}".format(str(attr_error)))
                self.close()
            except Exception as unknown_exception:
                logger.error("Unexpected exception %s " % str(unknown_exception))
            else:
                logger.debug(command.to_string().rstrip())
            finally:
                self.__send_lock.release()


class ServerConn(Connection):
    def __init__(self,
                 server_ip,
                 server_port,
                 priority,
                 plugins,
                 sensor_id,
                 sensor_id_file):

        Connection.__init__(self,
                            connection_id="",
                            connection_ip=server_ip,
                            connection_port=server_port,
                            sensor_id=sensor_id,
                            system_id_file=sensor_id_file,
                            connection_type=CONNECTION_TYPE_SERVER)
        self.__plugins = plugins
        self.__sequence = 0
        self.__server_ip = server_ip
        self.__server_port = server_port
        self.__monitor_scheduler = None
        self.__framework_hostname = ''
        self.__framework_ip = ''
        self.__framework_port = ''
        self.__priority = priority
        self.__thread_control_messages = None
        self.__control_thread_running = False
        self.__shutdown_event = threading.Event()
        self.__sensor_id = sensor_id

    def control_plugins(self, data):

        # get plugin_id of process to start/stop/enable/disable
        pattern = re.compile('(\S+) plugin_id="([^"]*)"')
        result = pattern.search(data)
        if result is not None:
            (command, plugin_id) = result.groups()
        else:
            logger.warning("Bad message from server: %s" % (data))
            return

        # get plugin from plugin list searching by the plugin_id given
        for plugin in self.__plugins:
            if int(plugin.get("config", "plugin_id")) == int(plugin_id):

                if command == Watchdog.PLUGIN_START_REQ:
                    Watchdog.start_process(plugin)

                elif command == Watchdog.PLUGIN_STOP_REQ:
                    Watchdog.stop_process(plugin)

                elif command == Watchdog.PLUGIN_ENABLE_REQ:
                    Watchdog.enable_process(plugin)

                elif command == Watchdog.PLUGIN_DISABLE_REQ:
                    Watchdog.disable_process(plugin)

                break

    def control_monitors(self, data):

        # build a watch rule, the server request.
        watch_rule = WatchRule()
        for attr in watch_rule.EVENT_ATTRS:
            pattern = ' %s="([^"]*)"' % attr
            result = re.findall(pattern, data)
            if result:
                value = result[0]
                if attr in watch_rule.EVENT_BASE64:
                    value = b64decode(value)
                watch_rule[attr] = value

        for plugin in self.__plugins:

            # look for the monitor to be called
            if plugin.get("config", "plugin_id") == watch_rule['plugin_id'] and \
                            plugin.get("config", "type").lower() == 'monitor':
                self.__monitor_scheduler.new_monitor(type=plugin.get("config", "source"),
                                                     plugin=plugin,
                                                     watch_rule=watch_rule)
                break

    def __process_plain_control_message(self, data):
        logger.info("Received message from server: " + data.rstrip())
        # 1) type of control messages: plugin management
        # (start, stop, enable and disable plugins)
        #
        if data.startswith(Watchdog.PLUGIN_START_REQ) or \
                data.startswith(Watchdog.PLUGIN_STOP_REQ) or \
                data.startswith(Watchdog.PLUGIN_ENABLE_REQ) or \
                data.startswith(Watchdog.PLUGIN_DISABLE_REQ):
            self.control_plugins(data)
        # 2) type of control messages: watch rules (monitors)
        #
        elif data.startswith('watch-rule'):
            self.control_monitors(data)
        # 3) type of control messages: ping
        elif data.startswith('ping'):
            self.send(AgentServerCommandPong())

    def __process_bson_control_message(self, data):
        data_json = BSON.decode(BSON(data), codec_options=CodecOptions(uuid_representation=STANDARD))
        if data_json.get('ping', None):
            # Send pong
            t = data_json.get('ping').get('timestamp')
            logger.info("Ping request with timestamp %d" % t)
            self.send(AgentServerCommandPong())
        elif data_json.get('watch-rule', None):
            self.control_monitors(data_json.get('watch-rule').get('str', ''))
        elif data_json.get('sensor-plugin-start', None):
            plugin_id = data_json.get['sensor-plugin-start'].get('plugin_id', None)
            if plugin_id is not None:
                Watchdog.start_process(int(plugin_id))
        elif data_json.get('sensor-plugin-stop', None):
            plugin_id = data_json.get['sensor-plugin-stop'].get('plugin_id', None)
            if plugin_id is not None:
                Watchdog.stop_process(int(plugin_id))
        elif data_json.get('sensor-plugin-enable'):
            plugin_id = data_json.get['sensor-plugin-enable'].get('plugin_id', None)
            if plugin_id is not None:
                Watchdog.enable_process(int(plugin_id))
        elif data_json.get('sensor-plugin-disable'):
            plugin_id = data_json.get['sensor-plugin-disable'].get('plugin_id', None)
            if plugin_id is not None:
                Watchdog.disable_process(int(plugin_id))
        else:
            logger.warning("Unknown BSON command: '%s'" % str(data_json))

    def __recv_control_messages(self):

        while not self.__shutdown_event.is_set():
            try:
                # receive message from server (line by line)
                data = self.recv_line()
                if data is not None:
                    if self.bson_protocol:
                        self.__process_bson_control_message(data)
                    else:
                        self.__process_plain_control_message(data)
                else:
                    # At this point the socket is in blocking mode so if no data arrives, that means
                    # the connection is down.
                    self.close()
            except socket.error:
                self.close()
            except Exception, e:
                if not self.__shutdown_event.is_set():
                    logger.error('Unexpected exception receiving from AlienVault server: ' + str(e))
            time.sleep(0.01)

    def append_plugins(self):
        """Sends append plugins messages to the server, to add all the plugins enabled.
        """
        logger.debug("Apending plugins..")
        for plugin in self.__plugins:
            state = 'start' if plugin.getboolean("config", "enable") else 'stop'
            message = AppendPlugin(plugin_id=int(plugin.get('config', 'plugin_id')),
                                   sequence_id=int(self.__sequence),
                                   state=state,
                                   enabled=plugin.getboolean("config", "enable"))
            self.send(message)

    def control_messages(self):
        """
        Launches new thread to manage control messages
        """
        if self.__control_thread_running:
            logger.info("Control message not started... already started")
            return

        if self.__monitor_scheduler is None:
            self.__monitor_scheduler = MonitorScheduler()
        self.__monitor_scheduler.start()
        self.__control_thread_running = True
        self.__thread_control_messages = threading.Thread(target=self.__recv_control_messages, args=())
        self.__thread_control_messages.start()

    def get_framework_data(self):
        return self.__framework_hostname, self.__framework_ip, self.__framework_port

    def get_priority(self):
        return self.__priority

    def set_framework_data(self, hostname, ip, port):
        """Sets the framework connection data"""
        self.__framework_hostname = hostname
        self.__framework_ip = ip
        self.__framework_port = port

    def close(self):
        try:
            self.__shutdown_event.set()
            self.__monitor_scheduler = None
            self.__control_thread_running = False
            self.__thread_control_messages = None
            super(ServerConn, self).close()
        except Exception:
            pass
        logger.info("AlienVault Server connection closed...")


class IDMConn(Connection):
    def __init__(self, idm_ip, idm_port, sensor_id, sensor_id_file):
        Connection.__init__(self, "", idm_ip, idm_port, sensor_id, sensor_id_file, CONNECTION_TYPE_IDM)
        self.__idm_ip = idm_ip
        self.__idm_port = idm_port
        self.__conn = None
        self.__control_thread_running = False
        self.__control_thread = None
        self.__shutdown_event = threading.Event()

    def start_control(self):
        self.__start_control_messages()

    @property
    def control_thread_running(self):
        return self.__control_thread_running

    @control_thread_running.setter
    def control_thread_running(self, value):
        self.__control_thread_running = value

    @property
    def ip(self):
        return self.__idm_ip

    @property
    def port(self):
        return self.__idm_port

    def control_loop(self, shutdown_event):
        while not shutdown_event.is_set():
            try:
                # receive message from server (line by line)
                data = self.recv_line()
                if data is not None:
                    if self.bson_protocol:
                        data_json = BSON.decode(BSON(data), codec_options=CodecOptions(uuid_representation=STANDARD))
                        if data_json.get('ping', None):
                            # Send pong
                            t = data_json.get('ping').get('timestamp')
                            self.send(AgentServerCommandPong(timestamp=long(t)))
                    else:
                        if data.startswith('ping'):
                            self.send(AgentServerCommandPong())
                else:
                    self.close()
            except Exception as e:
                if not shutdown_event.is_set():
                    logger.error('Unexpected exception receiving from IDM server: {0}' + str(e))
            time.sleep(1)
        logger.info("Ends IDM control message thread..")
        self.control_thread_running = False

    def __start_control_messages(self):
        self.__control_thread_running = True
        self.__control_thread = None
        self.__control_thread = threading.Thread(target=self.control_loop, args=(self.__shutdown_event,))
        self.__control_thread.start()

    def close(self):
        self.__control_thread = None
        self.__shutdown_event.set()
        logger.info("Closing AlienVault IDM connection ...")
        super(IDMConn, self).close()


class FrameworkConn(Connection):
    def __init__(self, conf, frmk_id, frmk_ip, frmk_port, sensor_id, sensor_id_file):
        Connection.__init__(self, frmk_id, frmk_ip, frmk_port, sensor_id, sensor_id_file, CONNECTION_TYPE_FRAMEWORK)
        self.__framework_ping = True
        self.__shutdown_event = threading.Event()
        self.__control_manager = None
        self.__pingThread = None
        self.__control_messages_listener = None
        self.__conf = conf

    def close(self):
        """Closes the connection and stop the control and ping threads"""
        self.__shutdown_event.set()
        logger.info("Closing control framework connection ...")
        if self.__control_manager is not None:
            self.__control_manager.stopProcess()
        super(FrameworkConn, self).close()

    def __recv_frmk_control_messages(self, shutdown_event):
        """Receives and processes control messages
        Args:
            threading.Event, e = The shutdown event
        """
        while not shutdown_event.is_set():
            # receive message from server (line by line)
            data = self.recv_line().rstrip('\n')
            if data == '':
                continue
            response = self.__control_manager.process(self.get_addr(), data)
            # send out all items in the response queue
            while len(response) > 0:
                self.send(AgentFrameworkCommand(response.pop(0)))
            time.sleep(1)
        logger.info("Closing thread - receive control framework control messages...!")

    def __ping(self, shutdown_event):
        """Ping thread entry point.
        Sends a ping message to the Framework every 30 seconds..
        Args:
            threading.Event, e = The shutdown event
        """
        while not shutdown_event.is_set():
            self.send(AgentFrameworkCommandPong())
            time.sleep(30)

    def framework_control_messages(self):
        """Launches the control thread and a ping thread to make sure the framework-daemon is alive"""
        self.__control_manager = ControlManager(self.__conf)
        self.__control_messages_listener = threading.Thread(target=self.__recv_frmk_control_messages,
                                                            args=(self.__shutdown_event,))
        self.__control_messages_listener.start()
        # enable keep-alive pinging if appropriate
        if self.__framework_ping:
            self.__pingThread = threading.Thread(target=self.__ping,
                                                 args=(self.__shutdown_event,))
            self.__pingThread.start()


if __name__ == "__main__":
    try:
        print "Test Server connection"
        # s = ServerConn(server_ip="192.168.212.13",
        #                server_port=40001,
        #                priority=0,
        #                plugins=[],
        #                sensor_id="564d3a49-e6da-730d-4c16-fa585e89e168",
        #                sensor_id_file="/tmp/somefile")
        # s.connect()
        # time.sleep(1)
        # s.close()
        print "Test IDM connection"
        s = IDMConn(idm_ip="192.168.212.13",
                    idm_port=40002,
                    sensor_id="564d3a49-e6da-730d-4c16-fa585e89e177",
                    sensor_id_file="/tmp/somefile")

        s.connect()
        time.sleep(1)
        s.close()
    except KeyboardInterrupt:
        print "Bye"
    import sys

    sys.exit(0)

# vim:ts=4 sts=4 tw=79 expandtab:

