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
import os
import re
import socket
import SocketServer
import sys
import threading
import traceback
import struct
import json
from time import sleep
#
# LOCAL IMPORTS
#
import Action
#from AlarmGroup import AlarmGroup
#import Const
from DoControl import ControlManager
from DoASEC import ASECHandler
from DoWS import WSHandler
from DoNagios import NagiosManager
from ApacheNtopProxyManager import ApacheNtopProxyManager
from BackupManager import BackupRestoreManager
from Logger import Logger
from OssimConf import OssimConf
from DBConstantNames import *
import Util

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
                if len(line) > 0:
                    command = line.split()[0]

                    # set sane default response
                    response = ""

                    # check if we are a "control" request message
                    if command == "control":
                        # spawn our control timer
                        if controlmanager == None:
                            controlmanager = ControlManager(OssimConf())

                        response = controlmanager.process(self, command, line)

                    # otherwise we are some form of standard control message

                    elif command == "nagios":
                        if self.__nagiosmanager == None:
                            self.__nagiosmanager = NagiosManager(OssimConf())

                        response = self.__nagiosmanager.process(line)

                    elif command == "ping":
                        response = "pong\n"

                    elif command == "add_asset" or command == "remove_asset" or command == "refresh_asset_list":
                        linebk = ""
                        if controlmanager == None:
                            controlmanager = ControlManager(OssimConf())
                        linebk = "action=\"refresh_asset_list\"\n"
                        response = controlmanager.process(self, command, linebk)

#                    elif command == "refresh_inventory_task":
#                        if controlmanager == None:
#                            controlmanager = ControlManager(OssimConf())
#                        response = controlmanager.process(self, command, linebk)

                    elif command == "refresh_sensor_list":
                        logger.info("Check ntop proxy configuration ...")
                        ap = ApacheNtopProxyManager(OssimConf())
                        ap.refreshConfiguration()
                        ap.close()
                    elif command == "backup":
                        if bkmanager == None:
                            bkmanager=  BackupRestoreManager(OssimConf())
                        response =  bkmanager.process(line)
                    elif command == "asec":
                        if asechandler == None:
                            asechandler = ASECHandler(OssimConf())
                        response = asechandler.process_web(self, line)
                    elif command == "asec_m":#struct.unpack('!H',line[0:2])[0] == 0x1F1F:
                        #it's a tlv 
                        if asechandler == None:
                            asechandler = ASECHandler(OssimConf())
                        response = asechandler.process(self,line)
                    elif command == "ws":
                        [ws_data] = re.findall('ws_data=(.*)$', line)
                        try:
                            ws_json = json.loads(ws_data)
                            logger.info("Received new WS: %s" % str(ws_json))
                        except Exception, msg:
                            logger.warning ("WS json is invalid: '%s'" % line)
                        else:
                            if ws_json['ws_id'] != '':

                                for ws_id in ws_json['ws_id'].split(','):
                                    try:
                                        ws_handler = WSHandler(OssimConf(), ws_id)
                                    except Exception, msg:
                                        logger.warning (msg)
                                    else:
#                                        response = ws_handler.process_json(ws_type, ws_data)
                                        response = ws_handler.process_json('insert', ws_json)
                            else:
                                logger.warning ("WS command does not contain a ws_id field: '%s'" % line)
                    else:
                        a = Action.Action(line)
                        a.start()

                        # Group Alarms
                        #ag = AlarmGroup.AlarmGroup()
                        #ag.start()

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
        if controlmanager != None:
            controlmanager.finish(self)

        return SocketServer.StreamRequestHandler.finish(self)


    def set_id(self, id):
        self.__id = id

    def set_sensorID(self,uuid):
        self.__sensorID=uuid
    def get_sensorID(self):
        return self.__sensorID
    def get_id(self):
        return self.__id


class FrameworkBaseServer(SocketServer.ThreadingTCPServer):
    allow_reuse_address = True

    def __init__(self, server_address, handler_class=FrameworkBaseRequestHandler):
        SocketServer.ThreadingTCPServer.__init__(self, server_address, handler_class)
        return


    def serve_forever(self):
        while True:
            try:
                self.handle_request()
            except Exception,e:
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
            serverAddress = ("0.0.0.0", int(self.__conf[VAR_FRAMEWORK_PORT]))
            logger.info("Listen on: %s:%s"% serverAddress)
            sleep(3)
            self.__server = FrameworkBaseServer(serverAddress, FrameworkBaseRequestHandler)
            self.__server.serve_forever()
        except socket.error, e:
            logger.critical("Something wrong happend while binding the socket. %s" % str(e))
            sys.exit(-1)
        except Exception,e:
            logger.error("ERROR: %s" % str(e))
            sys.exit(-1)


if __name__ == "__main__":

    listener = Listener()
    listener.start()

# vim:ts=4 sts=4 tw=79 expandtab:
