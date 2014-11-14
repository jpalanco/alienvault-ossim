#!/usr/bin/python
#
# License:
#
#    Copyright (c) 2012-2014 AlienVault
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

import struct
import os
import array
import base64
import time

#
# LOCAL IMPORTS
#
from AsecTlv import ASECTLV
from Logger import Logger
from OssimDB import OssimDB
from OssimConf import OssimConf
from DBConstantNames import *
from ASECDBModel import *
from uuid import UUID
import Util
logger = Logger.logger

class ASECHandler(object):
    """@brief Class to encapsulate all the ASEC functionality """
    def __init__(self, conf):
        """Constructor.
        @param conf: The configuration object
        """
        self.__myconf = conf
        self.__asecmodel = ASECModel()
        # TODO  use the configuration
        self.__asecmodel.connect(conf[VAR_DB_HOST], "alienvault_asec", conf[VAR_DB_USER], conf[VAR_DB_PASSWORD])
        self.__asecm_ip = "127.0.0.1"
        self.__asecm_port = 40005
        self.__asec_connected = False
        self.__asecm_Sock = None


    def __connect__(self):
        """@brief Connect to the asec system"""
        if self.__asecm_Sock is None:
            # creates the socket
            logger.info("Creating ASEC Sock")
            self.__asecm_Sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        try: 
            logger.info("Connecting to ASEC...")
            self.__asecm_Sock.connect((self.__asecm_ip, int(self.__asecm_port)))
            logger.info("Connected to Asec")
            self.__asec_connected = True
        except Exception, e:
            logger.error("Can't connect to ASEC: %s" % (str(e)))
            self.__close__()


    def __close__(self):
        """@brief Closes the connection"""
        try:
            if self.__asecm_Sock is not None:
                # From the python doc:
                # close() releases the resource associated with a connection 
                # but does not necessarily close the connection immediately. 
                # If you want to close the connection in a timely fashion, 
                # call shutdown() before close().
                self.__asecm_Sock.shutdown(socket.SHUT_WR)
                self.__asecm_Sock.close()
        except Exception,e:
            pass
        finally:
            self.__asec_connected = False
            self.__asecm_Sock = None


    def __send(self, data):
        """@brief Sends the data over the socket"""
        self.__close__()
        if not self.__asec_connected:
            self.__connect__()
        totalsent = 0
        len_data = len(data)
        while totalsent < len_data:
            try:
                sent = self.__asecm_Sock.send(data[totalsent:])
                if sent == 0:
                    logger.error("Error can't send messages")
                totalsent = totalsent + sent
            except Exception, e:
                logger.error("Can't send message: %s" % str(e))
                self.__close__()
                return False

        logger.info("Message sent successfully!")
        return True


    def sendPatternSuggestion(self, suggestion, patterns):
        """@brief Builds a composite TLV to send a the pattern 
        to the ASEC system.
        @param suggestion: the suggestion
        """
        logger.info("Sending results...")
        tlv_list = []
        suggestion_id = UUID(bytes=suggestion.suggestion_group_id)
        b64_suggestion_id = base64.b64encode(str(suggestion_id))
        
        tlv_suggestion_id = ASECTLV.tlv_simple(ASECTLV.TLV_TYPE_PATTERN_FIELD_ID, \
                        str(b64_suggestion_id), len(str(b64_suggestion_id)))

        suggestion_filename = ""
        if suggestion.location is None or suggestion.location == "":
            suggestion_filename = suggestion.filename
        else:
            suggestion_filename = suggestion.location
        suggestion_filename = base64.b64encode(suggestion_filename)
        tlv_suggestion_filename = ASECTLV.tlv_simple(ASECTLV.TLV_TYPE_PATTERN_FIELD_FILENAME, suggestion_filename, len(suggestion_filename))
        
        suggestion_json = '{"patterns":['
        pattern_list = ",".join(["%s" % (p.pattern_json) for p in patterns])
        suggestion_json = suggestion_json+pattern_list+"]}"
        suggestion_json = base64.b64encode(suggestion_json)
        tlv_suggestion_json = ASECTLV.tlv_simple(ASECTLV.TLV_TYPE_PATTERN_FIELD_JSON_STR, suggestion_json, len(suggestion_json))

        tlv_list.append(tlv_suggestion_id)
        tlv_list.append(tlv_suggestion_filename)
        tlv_list.append(tlv_suggestion_json)

        tlvcomposite = ASECTLV.tlv_composite(ASECTLV.TLV_TYPE_PATTERN, tlv_list)
        attempts = 3
        while attempts>0:
            if not self.__send(tlvcomposite):
                attempts = attempts - 1
                time.sleep(1)
            else:
                try:
                    self.__asecmodel.delete_suggestion(str(suggestion_id))
                except Exception,e:
                    logger.error("Can't remove the suggestionid: %s" % str(e))
                    return False
                return True
        return False




    def process_message_mlog4fwk(self, data, data_len):
        """Processes  the mlog4fwk message.
        """
        logger.info("processing message: mlog4fwk")
        total = data_len
        readed = 0
        pkg = data
        logstr = ""
        sensor = ""
        regex = ""

        while readed < total:
            s_type, s_len, s_value = ASECTLV.tlv_decode(pkg[readed:])
            readed += s_len + 8
            if s_type == ASECTLV.TLV_TYPE_MLOG4FWK_FIELD_LOG_LINE:
                logstr = s_value #base64.b64decode(s_value).rstrip('\n')
            elif s_type == ASECTLV.TLV_TYPE_MLOG4FWK_FIELD_REGEXP:
                regex = base64.b64decode(s_value)
            elif s_type == ASECTLV.TLV_TYPE_MLOG4FWK_FIELD_SENSOR_ID:
                sensor = base64.b64decode(s_value)
                logger.info("Campo Sensor :%s - %d" % (sensor, len(sensor)))
            else:
                logger.error("unknown type: %s" % s_type)
        
        obj = AsecDb_AlarmCoincidence(data=regex, sample_log=logstr, sensor_id=UUID(sensor).bytes)
        self.__asecmodel.set_alarm_coincidence(obj)


    def process_message_pattern(self, data, data_len):
        """Processes the pattern message.
        """
        logger.info("processing message: pattern")
        total = data_len
        readed = 0
        pkg = data
        logid = 0
        t_uuid = ""
        filename = ""
        json_str = ""
        while readed < total:
            s_type, s_len, s_value = ASECTLV.tlv_decode(pkg[readed:])
            readed += s_len + 8
            if s_type == ASECTLV.TLV_TYPE_PATTERN_FIELD_ID:
                logger.info("pattern_fieldid")
                t_uuid = base64.b64decode(s_value)
            elif s_type == ASECTLV.TLV_TYPE_PATTERN_FIELD_FILENAME:
                logger.info("pattern_field_filename")
                filename = base64.b64decode(s_value).rstrip('\n')
            elif s_type == ASECTLV.TLV_TYPE_PATTERN_FIELD_JSON_STR:
                logger.info("pattern_field_json")
                json_str = base64.b64decode(s_value).rstrip('\n')
            else:
                logger.error("unknown type: %s" % s_type)
        if self.__asecmodel.get_suggestion(t_uuid) is None:
            suggestion = AsecDb_Suggestion(suggestion_group_id=UUID(t_uuid).bytes, filename=filename,location="")
            self.__asecmodel.set_suggestion(suggestion)
        suggestion_pattern = AsecDb_Suggestion_pattern(suggestion_group_id=UUID(t_uuid).bytes, pattern_json=json_str)
        self.__asecmodel.set_suggestion_pattern(suggestion_pattern)


    def process_message_active_plugin(self, data, data_len):
        """Processes the active plugin  message.
        """
        logger.info("processing active plugin message")
        total = data_len
        readed = 0
        pkg = data
        plugin_id = ""
        plugin_name = ""
        sensor_id = ""
        log_file =""
        while readed < total:
            s_type, s_len, s_value = ASECTLV.tlv_decode(pkg[readed:])
            readed += s_len + 8
            if s_type == ASECTLV.TLV_TYPE_ACTIVE_PLUGIN_FIELD_PLUGIN_ID:
                plugin_id = base64.b64decode(s_value).rstrip('\n')
                logger.info("pattern_pluginid: %s" % plugin_id)
            elif s_type == ASECTLV.TLV_TYPE_ACTIVE_PLUGIN_FIELD_PLUGIN_NAME:
                plugin_name = base64.b64decode(s_value).rstrip('\n')
                logger.info("pattern_field_pluginname :%s" % plugin_name)
            elif s_type == ASECTLV.TLV_TYPE_ACTIVE_PLUGIN_FIELD_PLUGIN_SENSOR_ID:
                sensor_id = base64.b64decode(s_value)
                logger.info("pattern_field_sensorid :%s" % sensor_id)
            elif s_type == ASECTLV.TLV_TYPE_ACTIVE_PLUGIN_FIELD_PLUGIN_LOG_FILE:
                log_file = base64.b64decode(s_value)
            else:
                logger.error("unknown type: %s" % s_type)
        try:
            pid = int(plugin_id)
        except:
            logger.error("invalid plugin %s" % plugin_id)
            pid = 0
        notification = AsecDb_Notification(plugin_id=pid, sensor_id=UUID(sensor_id).bytes, rule_name=plugin_name,log_file = log_file)
        self.__asecmodel.set_notification(notification)


    def process(self, requestor, line):
        """Processes an ASEC requests
        requestor: Source Socket
        line: command to process
        """
        
        msg = Util.get_var("msg=\"([^\"]+)\"", line)
        line = base64.b64decode(msg)
        # TODO ACK tlv
        response = ""
        try:
            tlv_type, tlv_len, tlv_value = ASECTLV.tlv_decode(line)
            if tlv_type == ASECTLV.TLV_TYPE_PATTERN:
                self.process_message_pattern(tlv_value, tlv_len)
            elif tlv_type == ASECTLV.TLV_TYPE_MLOG4FWK:
                self.process_message_mlog4fwk(tlv_value, tlv_len);
            elif tlv_type == ASECTLV.TLV_TYPE_ACTIVE_PLUGIN:
                self.process_message_active_plugin(tlv_value, tlv_len)
            else:
                logger.error("unknown tlv")
        except Exception, e:
            import traceback
            logger.error(traceback.print_exc())
            logger.error("ERROR:  %s" % str(e))
        return response


    def process_web(self, requestor, line):
        """Processes ASEC web requests!
        """
        response = ""
        try:
            suggestion_id = Util.get_var("suggestion_id=\"([^\"]+)\"", line)
            if suggestion_id == "" or suggestion_id == None:
                response += ' errno="-1" error="Invalid Suggestion ID" ackend\n'
            else:
                suggestion = self.__asecmodel.get_suggestion(suggestion_id)
                patterns = self.__asecmodel.get_suggestions_patterns(suggestion_id)
                if suggestion is None:
                    response += ' errno="-2" error="Suggestion id not found" ackend \n'
                else:
                    if self.sendPatternSuggestion(suggestion,patterns):
                        response += ' ok errno="0" error="Success" ackend\n'
                    else:
                        response += ' errno="-2" error="Can\'t send the message" ackend\n' 
        except Exception, e:
            logger.error("Error processing request, %s" % str(e))
        return response

