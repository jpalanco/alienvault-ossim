# -*- coding: utf-8 -*-
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
import re
import socket
import SocketServer
import sys
import threading
import json
from time import sleep
#
# LOCAL IMPORTS
#
import Action
from DoControl import ControlManager
from DoASEC import ASECHandler
from DoWS import WSHandler
from DoNagios import NagiosManager
from BackupManager import BackupRestoreManager, DoRestore
from Logger import Logger
from OssimConf import OssimConf
from OssimDB import OssimDB
from DBConstantNames import *

# Uncomment for SSL
# from OssimConf import OssimMiniConf

#
# GLOBAL VARIABLES
#
logger = Logger.logger
controlmanager = None
asechandler = None
bkmanager = None


class FrameworkBaseRequestHandler(SocketServer.StreamRequestHandler):
    __nagiosmanager = None
    __conf = None
    __sensorID = ""

    def getRequestorIP(self):
        return self.client_address[0]

    def getRequestorPort(self):
        return self.client_address[1]

    def getRequestorID(self):
        return self.__id

    def handle(self):
        global controlmanager
        global bkmanager
        global asechandler
        self.__id = None

        logger.debug("Request from: %s:%i" % (self.client_address))

        while 1:
            try:
                line = self.rfile.readline().rstrip('\n')
                if len(line) > 0 and not line.isspace():
                    command = line.split()[0]

                    # set sane default response
                    response = ""

                    # Commands available. Note that only 'ping' is opened to anyone.
                    if self.__check_sensor_ip(self.client_address[0]) or self.client_address[0] == '127.0.0.1':
                        if command == "ping":
                            response = "pong\n"

                        elif command == "control":
                            # spawn our control timer
                            if controlmanager is None:
                                controlmanager = ControlManager(OssimConf())

                            response = controlmanager.process(self, command, line)

                        elif self.client_address[0] == '127.0.0.1':
                            # Only control messages coming from localhost.

                            if command == "nagios":
                                if self.__nagiosmanager is None:
                                    self.__nagiosmanager = NagiosManager(OssimConf())

                                response = self.__nagiosmanager.process(line)

                            elif command == "add_asset" or command == "remove_asset" or command == "refresh_asset_list":
                                linebk = ""
                                if controlmanager is None:
                                    controlmanager = ControlManager(OssimConf())
                                linebk = "action=\"refresh_asset_list\"\n"
                                response = controlmanager.process(self, command, linebk)

                            elif command == "backup":
                                if bkmanager is None:
                                    ossim_conf = OssimConf()
                                    do_restore_cmd = DoRestore(ossim_conf)
                                    bkmanager = BackupRestoreManager(ossim_conf, do_restore_cmd)
                                response = bkmanager.process(line)

                            elif command == "asec":
                                if asechandler is None:
                                    asechandler = ASECHandler(OssimConf())
                                response = asechandler.process_web(self, line)

                            elif command == "asec_m":  # struct.unpack('!H',line[0:2])[0] == 0x1F1F:
                                # it's a tlv
                                if asechandler is None:
                                    asechandler = ASECHandler(OssimConf())
                                response = asechandler.process(self, line)

                            elif command == "ws":
                                try:
                                    [ws_data] = re.findall('ws_data=(.*)$', line)
                                    ws_json = json.loads(ws_data)
                                    logger.info("Received new WS: %s" % str(ws_json))
                                except Exception, msg:
                                    logger.warning("WS json is invalid: '%s'" % line)
                                else:
                                    if ws_json['ws_id'] != '':
                                        for ws_id in ws_json['ws_id'].split(','):
                                            try:
                                                ws_handler = WSHandler(OssimConf(), ws_id)
                                            except Exception, msg:
                                                logger.warning(msg)
                                            else:
                                                response = ws_handler.process_json('insert', ws_json)
                                    else:
                                        logger.warning("WS command does not contain a ws_id field: '%s'" % line)
                            elif command == 'event':
                                a = Action.Action(line)
                                a.start()

                            else:
                                logger.info(
                                    "Unrecognized command from source '%s': %s" % (self.client_address[0], command))
                                return

                        else:
                            logger.info("Unrecognized command from source '%s': %s" % (self.client_address[0], command))

                    else:
                        logger.info(
                            "Dropped data from a disallowed source '%s': %s" % (self.client_address[0], command))
                        return

                    # return the response as appropriate
                    if len(response) > 0:
                        self.wfile.write(response)

                    line = ""

                else:
                    return
            except socket.error, e:
                logger.warning("Client disconnected...%s" % e)

            except IndexError:
                logger.error("IndexError")

            except Exception, e:
                logger.error("Unexpected exception in listener: %s" % str(e))
                import sys, traceback
                traceback.print_exc(file=sys.stdout)
                return

    def finish(self):
        global controlmanager
        if controlmanager is not None:
            controlmanager.finish(self)

        return SocketServer.StreamRequestHandler.finish(self)

    def set_id(self, id):
        self.__id = id

    def set_sensorID(self, uuid):
        self.__sensorID = uuid

    def get_sensorID(self):
        return self.__sensorID

    def get_id(self):
        return self.__id

    @staticmethod
    def __check_sensor_ip(addr):
        """
        Checks if the request is coming from a sensor.
        Args:
            addr: tuple with ip address and port of the request
        Returns:
            True if address corresponds to a sensor, false otherwise.
        """
        try:
            conf = OssimConf()
            myDB = OssimDB(conf[VAR_DB_HOST],
                           conf[VAR_DB_SCHEMA],
                           conf[VAR_DB_USER],
                           conf[VAR_DB_PASSWORD])
            myDB_connected = myDB.connect()
        except Exception, msg:
            # Cannot connect to database, return false.
            logger.warning("Cannot find registered sensors: %s" % str(msg))
            return False

        query = 'select inet6_ntoa(sensor.ip) as ip from sensor, system where sensor.id=system.sensor_id;'
        result = myDB.exec_query(query)
        # Python doesn't support assignments in while statements...
        for r in result:
            if r['ip'] == addr:
                return True
        else:
            return False


class FrameworkBaseServer(SocketServer.ThreadingTCPServer):
    allow_reuse_address = True

    # Uncomment for Non SSL
    def __init__(self, server_address, handler_class=FrameworkBaseRequestHandler):
        SocketServer.ThreadingTCPServer.__init__(self, server_address, handler_class)
        return

    # Uncomment for SSL version
    #    def __init__(self, server_address, handler_class=FrameworkBaseRequestHandler, certfile=None, keyfile=None, ssl_version=ssl.PROTOCOL_TLSv1, conf=None):
    #        SocketServer.ThreadingTCPServer.__init__(self, server_address, handler_class)
    #        self.certfile = certfile
    #        self.keyfile = keyfile
    #        self.ssl_version = ssl_version
    #        self.using_SSL = False
    #        self.__myconf = conf
    #        self.__myDB = OssimDB(conf[VAR_DB_HOST],
    #                              conf[VAR_DB_SCHEMA],
    #                              conf[VAR_DB_USER],
    #                              conf[VAR_DB_PASSWORD])
    #        self.__myDB_connected = self.__myDB.connect ()
    #        self.__ossim_setup = OssimMiniConf(config_file='/etc/ossim/ossim_setup.conf')
    #        return
    #
    #    def get_request(self):
    #        """
    #        Calls to ThreadingTCPServer.get_request and checks for SSL header.
    #        If found, wraps the socket with SSL wrapper
    #        """
    #        self.using_SSL = False
    #        plain_sock = ssl_sock = None
    #        header = None
    #        # Check for SSL header
    #        try:
    #            plain_sock, fromaddr = SocketServer.ThreadingTCPServer.get_request(self)
    #            # Enable timeout to avoid locking on ASEC connections
    #            plain_sock.settimeout(0.5)
    #            header = plain_sock.recv(3, socket.MSG_PEEK)
    #        except socket.timeout, e:
    #            if e.args[0] != 'timed out':
    #                logger.error("Error getting request from socket: %s" % str(e))
    #
    #        # Disable socket timeout
    #        plain_sock.settimeout(None)
    #        if header == '\x16\x03\x01' and self.certfile and self.keyfile:
    #            try:
    #                ssl_sock = ssl.wrap_socket(plain_sock,
    #                                    server_side=True,
    #                                    certfile=self.certfile,
    #                                    keyfile=self.keyfile,
    #                                    ssl_version=self.ssl_version,
    #                                    do_handshake_on_connect=False)
    #                                    # Set ciphers for NIAP. Not valid in python 2.6
    #                                    #ciphers="AES128-SHA:AES256-SHA:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA")
    #
    #                # Probamos a hacer el handsake y si tira una excepcion seguimos con socket plano
    #                if ssl_sock:
    #                    ssl_sock.do_handshake()
    #                    self.using_SSL = True
    #            except Exception, e:
    #                logger.error("Error in SSL handshake: %s" % str(e))
    #        if self.using_SSL and ssl_sock:
    #            return ssl_sock, fromaddr
    #        return plain_sock, fromaddr
    #
    #
    #    def verify_request(self, request, client_address):
    #        """
    #        Filters request according to these rules:
    #        - Any   connection from localhost: ALLOW
    #        - Plain connection from remote sensor: ALLOW (DENY for NIAP version)
    #        - SSL   connection from remote sensor: ALLOW
    #        - Any   connection from non sensor: DENY
    #        """
    #        local_ip = self.__ossim_setup['admin_ip']
    #        if client_address[0] not in ['127.0.0.1',local_ip]:
    #            if not self.__check_sensor_ip(client_address):
    #                logger.info("Request from non-sensors not allowed. Origin: %s:%s" % (client_address))
    #                return False
    #             # Uncomment for NIAP version: restrict non-SSL connections to localhost
    ##            elif not self.using_SSL :
    ##                logger.info("Non-SSL remote request not allowed. Origin: %s:%s" % (client_address))
    ##                return False
    #        return SocketServer.ThreadingTCPServer.verify_request(self, request, client_address)
    #
    #
    #    def __check_sensor_ip(self, addr):
    #        """
    #        Checks if the request is coming from a sensor.
    #        Args:
    #            addr: tuple with ip address and port of the request
    #        Returns:
    #            True if address corresponds to a sensor, false otherwise.
    #        """
    #        query = 'select inet6_ntoa(sensor.ip) as ip from sensor, system where sensor.id=system.sensor_id;'
    #        result = self.__myDB.exec_query(query)
    #        # Python doesn't support assignments in while statements...
    #        for r in result:
    #            if r['ip'] == addr[0]:
    #                return True
    #        else:
    #            return False

    def serve_forever(self):
        while True:
            try:
                self.handle_request()
            except Exception, e:
                raise e
                logger.error("Error handling request: %s" % str(e))
                break
        return


class Listener(threading.Thread):
    def __init__(self):
        self.__conf = OssimConf()
        self.__server = None
        threading.Thread.__init__(self)

    def run(self):
        try:
            # Uncomment for SSL
            #            certfile='/var/ossim/ssl/local/cert_local.pem'
            #            keyfile='/var/ossim/ssl/local/key_local.pem'
            serverAddress = ("0.0.0.0", int(self.__conf[VAR_FRAMEWORK_PORT]))
            logger.info("Listen on: %s:%s" % serverAddress)
            sleep(3)

            # Uncomment for Non SSL
            self.__server = FrameworkBaseServer(serverAddress, FrameworkBaseRequestHandler)
            # Uncomment for SSL
            #            self.__server = FrameworkBaseServer(serverAddress, FrameworkBaseRequestHandler, certfile=certfile, keyfile=keyfile, conf=OssimConf())

            self.__server.serve_forever()
        except socket.error, e:
            logger.critical("Something wrong happend while binding the socket. %s" % str(e))
            sys.exit(-1)
        except Exception, e:
            logger.error("ERROR: %s" % str(e))
            sys.exit(-1)

# vim:ts=4 sts=4 tw=79 expandtab:
