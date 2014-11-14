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
import sys 
import struct
import os
import threading
import stat
import socket
import zlib
import re
from binascii import hexlify,unhexlify
from optparse import OptionParser
from time import time, localtime, mktime, strptime, strftime, sleep
from base64 import b64encode
import glob
from Logger import Logger
from Detector import Detector
from Event import Event

logger = Logger.logger
#Unified2_common.h
SNORT_FILE_HEADER_SIZE = 8

UNIFIED2_EVENT = 1
UNIFIED2_PACKET = 2 # -
UNIFIED2_IDS_EVENT = 7 # -
UNIFIED2_IDS_EVENT_IPV6 = 72 # -
UNIFIED2_IDS_EVENT_MPLS = 99
UNIFIED2_IDS_EVENT_IPV6_MPLS = 100
UNIFIED2_IDS_EVENT_VLAN = 104 # -
UNIFIED2_IDS_EVENT_IPV6_VLAN = 105 # -
UNIFIED2_EXTRA_DATA = 110 # -
UNIFIED2_IDS_EVENT_NG = 207 # -
UNIFIED2_IDS_EVENT_IPV6_NG = 208 # -
EVENT_TYPE_EXTRA_DATA = 4

TOTAL = 1111

ETHERNET_TYPE_IP = 0x0800
ETHERNET_TYPE_IPV6 = 0x86dd
ETHERNET_TYPE_8021Q = 0x8100
ETHERNET_TYPE_PPPOES = 0x8864 # not used by snort.
#Snort Sources: http://fossies.org/dox/snort-2.9.2.1/group__Unified2.html
#http://fossies.org/dox/snort-2.9.2.1/Unified2__common_8h_source.html

class SnortIDSEvent(object):
    IDS_EVENT_ATTRS = [
        "sensor_id",
        "event_id",
        "event_second",
        "event_microsecond",
        "signature_id",
        "generator_id",
        "signature_revision",
        "classification_id",
        "priority_id",
        "ip_source",
        "ip_destination",
        "sport_itype",
        "dport_icode",
        "protocol",
        "impact_flag",
        "impact",
        "blocked",
        "raw_data",
        "timestamp",
    ]
    
    IDS_EVENT_BASE64 = ["raw_data"]

    def __init__(self):
        self.ids_event = {}

    def __setitem__(self, key, value):
        if key in self.IDS_EVENT_ATTRS:
#            if key in self.IDS_EVENT_BASE64:
#                self.ids_event[key] = b64encode (value)
#            else:
            self.ids_event[key] = value

    def __getitem__(self, key):
        return self.ids_event.get(key, None)


    def __repr__(self):
        """Event representation."""
        str = ""
        for attr in self.IDS_EVENT_ATTRS:
            if self[attr]:
                str += ' %s="%s"' % (attr, self[attr])
        return str + "\n"


class SnortUnpack():
    ids_events_lock = threading.RLock()
    ids_events = {}#event_id, event_data
    purge_thread = None
    purge_thread_started = False
    keep_purgin = False
    def __init__(self):
        print "Snort Unpacker..."

    @staticmethod
    def get_UNIFIED2_EVENT(data):
        '''
        No docs available - UNIFIED2_EVENT 1
        '''
        logger.info("No information available for UNIFIED2_EVENT")
    @staticmethod
    def get_UNIFIED2_IDS_EVENT_MPLS(data):
        '''
        No docs available - UNIFIED2_IDS_EVENT_MPLS 99
        '''
        logger.info("No information available for UNIFIED2_IDS_EVENT_MPLS")
    @staticmethod
    def get_UNIFIED2_IDS_EVENT_IPV6_MPLS(data):
        '''
        No docs available - UNIFIED2_IDS_EVENT_IPV6_MPLS
        '''
        logger.info("No information available for get_UNIFIED2_IDS_EVENT_IPV6_MPLS")
    @staticmethod
    def get_EVENT_TYPE_EXTRA_DATA(data):
        '''
        No docs available - EVENT_TYPE_EXTRA_DATA 4
        '''
        logger.info("No information available for EVENT_TYPE_EXTRA_DATA")
    @staticmethod
    def get_Unified2IDSEventNG(data):
        '''
            //UNIFIED2_IDS_EVENT_NG = type 207
            typedef struct _Unified2IDSEventNG
            {
                uint32_t sensor_id;
                uint32_t event_id;
                uint32_t event_second;
                uint32_t event_microsecond;
                uint32_t signature_id;
                uint32_t generator_id;
                uint32_t signature_revision;
                uint32_t classification_id;
                uint32_t priority_id;
                uint32_t ip_source;
                uint32_t ip_destination;
                uint16_t sport_itype;
                uint16_t dport_icode;
                uint8_t  protocol;
                uint8_t  impact_flag;//overloads packet_action
                uint8_t  impact;
                uint8_t  blocked;
                uint32_t mpls_label;
                uint16_t vlanId;
                uint16_t pad;
                /* Structure should match Unified2IDSEvent up to this point */
                uint8_t  policy_uuid[16];
                uint32_t user_id;
                uint32_t web_application_id;
                uint32_t client_application_id;
                uint32_t application_protocol_id;
                uint32_t policyengine_rule_id;
                uint8_t  policyengine_policy_uuid[16];
                uint8_t  interface_ingress_uuid[16];
                uint8_t  interface_egress_uuid[16];
                uint8_t  security_zone_ingress_uuid[16];
                uint8_t  security_zone_egress_uuid[16];
            } Unified2IDSEventNG;
        '''
        logger.info("Not information yet")
    @staticmethod
    def get_Unified2IDSEventIPv6_NG(data):
        '''
            //UNIFIED2_IDS_EVENT_IPV6_NG = type 208
            typedef struct _Unified2IDSEventIPv6_NG
            {
                uint32_t sensor_id;
                uint32_t event_id;
                uint32_t event_second;
                uint32_t event_microsecond;
                uint32_t signature_id;
                uint32_t generator_id;
                uint32_t signature_revision;
                uint32_t classification_id;
                uint32_t priority_id;
                struct in6_addr ip_source;
                struct in6_addr ip_destination;
                uint16_t sport_itype;
                uint16_t dport_icode;
                uint8_t  protocol;
                uint8_t  impact_flag;//overloads packet_action
                uint8_t  impact;
                uint8_t  blocked;
                uint32_t mpls_label;
                uint16_t vlanId;
                uint16_t pad;
                /* Structure should match Unified2IDSEventIPv6 up to this point */
                uint8_t  policy_uuid[16];
                uint32_t user_id;
                uint32_t web_application_id;
                uint32_t client_application_id;
                uint32_t application_protocol_id;
                uint32_t policyengine_rule_id;
                uint8_t  policyengine_policy_uuid[16];
                uint8_t  interface_ingress_uuid[16];
                uint8_t  interface_egress_uuid[16];
                uint8_t  security_zone_ingress_uuid[16];
                uint8_t  security_zone_egress_uuid[16];
            } Unified2IDSEventIPv6_NG;
        '''
        logger.info("Not information yet")
    @staticmethod
    def get_Unified2IDSEvent(data):
        '''
            //UNIFIED2_IDS_EVENT_VLAN = type 104
            //comes from SFDC to EStreamer archive in serialized form with the extended header
            typedef struct _Unified2IDSEvent
            {
                uint32_t sensor_id;
                uint32_t event_id;
                uint32_t event_second;
                uint32_t event_microsecond;
                uint32_t signature_id;
                uint32_t generator_id;
                uint32_t signature_revision;
                uint32_t classification_id;
                uint32_t priority_id;
                uint32_t ip_source;
                uint32_t ip_destination;
                uint16_t sport_itype;
                uint16_t dport_icode;
                uint8_t  protocol;
                uint8_t  impact_flag;//overloads packet_action
                uint8_t  impact;
                uint8_t  blocked;
                uint32_t mpls_label;
                uint16_t vlanId;
                uint16_t pad2;//Policy ID
            } Unified2IDSEvent;
        '''
        sensor_id, event_id, event_second, event_microsecond, \
        signature_id, generator_id, signature_revision, classification_id, \
        priority_id, source_ip, destination_ip, sport_itpye, dport_icode, \
        protocol, impact_flag, impact, blocked, mpls_label, \
        vlanid, pad = struct.unpack("!IIIIIIIIIIIHHBBBBIHH", data)
        sip_int = int(source_ip)
        dip_int = int(destination_ip)
        source_ip_str = socket.inet_ntoa(struct.pack("I", socket.htonl((sip_int))))
        dest_ip_str = socket.inet_ntoa(struct.pack("I", socket.htonl((dip_int))))
        ev = SnortIDSEvent()
        ev["raw_data"] = hexlify(data)
        ev["sensor_id"] = sensor_id
        ev["event_id"] = event_id
        ev["event_second"] = event_second
        ev["event_microsecond"] = event_microsecond
        ev["signature_id"] = signature_id
        ev["generator_id"] = generator_id
        ev["signature_revision"] = signature_revision
        ev["classification_id"] = classification_id
        ev["priority_id"] = priority_id
        ev["ip_source"] = source_ip_str
        ev["ip_destination"] = dest_ip_str
        ev["sport_itype"] = sport_itpye
        ev["dport_icode"] = dport_icode
        ev["protocol"] = protocol
        ev["impact_flag"] = impact_flag
        ev["impact"] = impact
        ev["blocked"] = blocked
        ev["timestamp"] = time()#to detect lapsed events
        SnortUnpack.ids_events_lock.acquire()
        SnortUnpack.ids_events[event_id] = ev
        SnortUnpack.ids_events_lock.release()
    @staticmethod
    def get_Unified2IDSEventIPv6(data):
        '''
            //UNIFIED2_IDS_EVENT_IPV6_VLAN = type 105
            typedef struct _Unified2IDSEventIPv6
            {
                uint32_t sensor_id;
                uint32_t event_id;
                uint32_t event_second;
                uint32_t event_microsecond;
                uint32_t signature_id;
                uint32_t generator_id;
                uint32_t signature_revision;
                uint32_t classification_id;
                uint32_t priority_id;
                struct in6_addr ip_source;
                struct in6_addr ip_destination;
                uint16_t sport_itype;
                uint16_t dport_icode;
                uint8_t  protocol;
                uint8_t  impact_flag;
                uint8_t  impact;
                uint8_t  blocked;
                uint32_t mpls_label;
                uint16_t vlanId;
                uint16_t pad2;/*could be IPS Policy local id to support local sensor alerts*/
            } Unified2IDSEventIPv6;        
        '''
        sensor_id, event_id, event_second, \
        event_microsecond, signature_id, \
        generator_id, signature_revision, \
        classification_id, priority_id = struct.unpack("!IIIIIIIII", data[0:36])
        str_ipv6_source = socket.inet_ntop(socket.AF_INET6, data[36:52])        
        str_ipv6_dest = socket.inet_ntop(socket.AF_INET6, data[52:68])   
        source_port_itype, dest_port_itype, protocol, impact_flag, impact, blocked, \
        mpls_label, vlan_id, pad2 = struct.unpack("!HHBBBBIHH", data[68:])
        ev = SnortIDSEvent()
        ev["raw_data"] = hexlify(data)
        ev["sensor_id"] = sensor_id
        ev["event_id"] = event_id
        ev["event_second"] = event_second
        ev["event_microsecond"] = event_microsecond
        ev["signature_id"] = signature_id
        ev["generator_id"] = generator_id
        ev["signature_revision"] = signature_revision
        ev["classification_id"] = classification_id
        ev["priority_id"] = priority_id
        ev["ip_source"] = str_ipv6_source
        ev["ip_destination"] = str_ipv6_dest
        ev["sport_itype"] = source_port_itype
        ev["dport_icode"] = dest_port_itype
        ev["protocol"] = protocol
        ev["impact_flag"] = impact_flag
        ev["impact"] = impact
        ev["blocked"] = blocked
        ev["timestamp"] = time()#to detect lapsed events
        SnortUnpack.ids_events_lock.acquire()
        SnortUnpack.ids_events[event_id] = ev
        SnortUnpack.ids_events_lock.release()
    
    @staticmethod
    def decodeIPPacket(packet):
        '''
             Internet Protocol Datagram
             From RFC 791
             0              1               2               3              4 
             0 1 2 3 4 5 6 7 0 1 2 3 4 5 6 7 0 1 2 3 4 5 6 7 0 1 2 3 4 5 6 7 
            +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
            |Version|  IHL  |Type of Service|          Total Length         |
            +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
            |         Identification        |Flags|      Fragment Offset    |
            +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
            |  Time to Live |    Protocol   |         Header Checksum       |
            +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
            |                       Source Address                          |
            +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
            |                    Destination Address                        |
            +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
            |                    Options                    |    Padding    |
            +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+

                Example Internet Datagram Header
        '''
        version_ihl, type_of_service, total_length, identification, flags_offset, \
        ttl, protocol, header_checksum, source_ip, destination_ip = struct.unpack(">BBHHHBBHII", packet[0:20])
        header_len = (version_ihl & 0xf)
        # Header length:
        # Internet Header Length is the length of the internet header in 32
        # bit words, and thus points to the beginning of the data.  Note that
        # the minimum value for a correct header is 5.
        # Protocol: See RFC760 - Assigned Internet Protocol Numbers
        # -- Next layer-> it depends of the protocol number.
        offset_to_payload = (header_len * 32) / 8
        payload = packet[offset_to_payload:]
        end_payload = ""
        
        if protocol == socket.IPPROTO_ICMP:
            #See: http://www.rfc-es.org/rfc/rfc0792-es.txt
            icmp_type, icmp_code, icmp_chekcsum = struct.unpack(">BBH", payload[:4])
            end_payload = payload[8:]
        elif protocol == socket.IPPROTO_TCP:
            #see http://www.faqs.org/rfcs/rfc793.html
            if len(payload) >= 20:
                source_port, destination_port, seq_number, ack_number, tcp_offset_flags, \
                tcp_checksum, tcp_urgenpointer = struct.unpack(">HHIIIHH", payload[:20])
                # H = 2 bytes
                # I = 4 bytes
                # B = 1 byte
                #Number of words of 32 bits in the tcp header -> 32 bits = 4 bytes
                tcp_header_size = ((tcp_offset_flags & 0xf0000000) >> 28) * 4

               # self.debug(""" TCP Packet: 
               #     src_port: 0x%04X
               #     dst_port: 0x%04X
               #     seq_number: 0x%08X
               #     ack_nuber: 0x%08X
               #     WORD con offset: 0x%08X
               #         TCP HEADER: %s bytes
               #     checksum: 0x%04X
               #     urgenpointer: 0x%04X

               # """ % (source_port, destination_port, seq_number, ack_number,tcp_offset_flags, tcp_header_size,tcp_checksum, tcp_urgenpointer))
                #data_offset are 4 bits.
                data_offset = tcp_header_size
                if len(payload) > data_offset:
                    end_payload = payload[data_offset:]
                    #self.debug("Packet Payload:%s" % end_payload)
                else:
                    end_payload = ""
            else:
                end_payload = ""
            
        elif protocol == socket.IPPROTO_UDP:
            #http://www.rfc-es.org/rfc/rfc0768-es.txt
            end_payload = payload[8:]
        else:#not impolemented packet type.
            end_payload = ""
        return end_payload
    @staticmethod
    def decodeIPV6Packet(packet):
        #See http://www.faqs.org/rfcs/rfc2460.html for ipv6 header format.
        #ipv6 first word: version,traffic_class and flow label.
        ipv6_first_word, ipv6_payload_legth, ipv6_next_header, ipv6_hoplimit = struct.unpack(">IHBB", packet[0:8])
        #ipv6_src_ip = socket.inet_ntop(socket.AF_INET6,packet[8:24])
        #ipv6_dst_ip = socket.inet_ntop(socket.AF_INET6,packet[24:40])
        payload = packet[40:]
        '''Next Header: 8-bit selector.  Identifies the type of header
                    immediately following the IPv6 header.  Uses the
                    same values as the IPv4 Protocol field [RFC-1700
                    et seq.].
            @see the below web to see update protocol number list.
            Protocol Numbers: http://www.iana.org/assignments/protocol-numbers/protocol-numbers.xml 
        '''
        
        if ipv6_next_header == socket.IPPROTO_ICMP:
            #See: http://www.rfc-es.org/rfc/rfc0792-es.txt
            icmp_type, icmp_code, icmp_chekcsum = struct.unpack(">BBH", payload[:4])
            end_payload = payload[8:]
        elif ipv6_next_header == socket.IPPROTO_TCP:
            #see http://www.faqs.org/rfcs/rfc793.html
            if len(payload) >= 20:
                source_port, destination_port, seq_number, ack_number, tcp_data_offset, tcp_window, tcp_flags, \
                tcp_checksum, tcp_urgenpointer = struct.unpack(">HHIIBBHHH", payload[:20])
                #data_offset are 4 bits.
                data_offset = (tcp_data_offset & 0xf0) >> 4
                data_offset = (data_offset * 32) / 8
                if len(payload) > data_offset:
                    end_payload = payload[data_offset:]
                else:
                    end_payload = ""
            else:
                end_payload = payload[:]
        elif ipv6_next_header == socket.IPPROTO_UDP:
            #http://www.rfc-es.org/rfc/rfc0768-es.txt
            end_payload = payload[8:]
        elif ipv6_next_header == socket.IPPROTO_ICMPV6:
            # see http://tools.ietf.org/html/rfc4443
            ipv6_icmp_type, ipv6_icmp_code, ipv6_icmp_chekcsum = struct.unpack(">BBH", payload[:4])
            end_payload = "" #payload[4:]
            socket.IPPROTO_HOPOPTS
        elif ipv6_next_header == socket.IPPROTO_HOPOPTS: 
            end_payload = ""
        else:#not impolemented packet type.
#            self.warning("IPV6 - Next header not implemented: %s" % ipv6_next_header)
            end_payload = ""
        return end_payload
    @staticmethod
    def get_Serial_Unified2Packet(data, type, length):
        '''
             //UNIFIED2_PACKET = type 2
             typedef struct _Serial_Unified2Packet
             {
                 uint32_t sensor_id;
                 uint32_t event_id;
                 uint32_t event_second;
                 uint32_t packet_second;
                 uint32_t packet_microsecond;
                 uint32_t linktype;
                 uint32_t packet_length;
                 uint8_t packet_data[4];
             } Serial_Unified2Packet;
        '''
        sensor_id, event_id, event_second, \
        packet_second, packet_ms, linktype, \
        packet_length = struct.unpack("!IIIIIII", data[0:28])
        if not SnortUnpack.ids_events.has_key(event_id):
            logger.info("Pay attention! Snort packet without associated event! %s " % event_id)
            return
        
        snort_packet_data = data[28:]
        
        """
            #define ETHER_HDR_LEN  14
            #define ETHERNET_TYPE_IP    0x0800
            #define ETHERNET_TYPE_IPV6  0x86dd
            #define ETHERNET_TYPE_8021Q 0x8100
            
            typedef struct _EtherHeader
            {
                u_int8_t ether_destination[6];
                u_int8_t ether_source[6];
                u_int16_t ethernet_type;
            
            } EtherHeader;
        """
        eth_typ, = struct.unpack("!H", snort_packet_data[12:14])
        packet = snort_packet_data[14:]
        payload = ""
        if eth_typ == ETHERNET_TYPE_IP:
            end_payload = SnortUnpack.decodeIPPacket(packet)
        elif eth_typ == ETHERNET_TYPE_IPV6:
            end_payload = SnortUnpack.decodeIPV6Packet(packet)
        elif eth_typ == ETHERNET_TYPE_8021Q:#VLAN
            vlan_header,ethstype = struct.unpack(">HH", packet[:4])#vlan header four bytes, and ethernet type
            payload = packet[4:]
            if ethstype == ETHERNET_TYPE_IP:
                end_payload = SnortUnpack.decodeIPPacket(payload)
            elif ethstype == ETHERNET_TYPE_IPV6:
                end_payload = SnortUnpack.decodeIPV6Packet(packet)
            else:
                end_payload = ""

        elif eth_typ == ETHERNET_TYPE_PPPOES:
            logger.warning("ETHERNET_TYPE_PPPOES - Not implemented")
            end_payload = ""
        else:
            logger.warning("ethernet_type: %s" % eth_typ)
            return
            #pass #raw packet
            
        ev = Event()
        idsev = SnortUnpack.ids_events[event_id]
        ev['plugin_id']=str(1000+idsev['generator_id'])
        ev['plugin_sid'] = idsev["signature_id"]
        ev['src_ip'] = idsev['ip_source']
        ev['dst_ip'] = idsev['ip_destination']
        ev['src_port'] = idsev['sport_itype']
        ev['dst_port'] = idsev['dport_icode']
        ev['protocol'] = idsev['protocol']
        # we store the pay in the 'log' field and the snort_packet without the payload in the 'userdata1' field.a
        len_payload = len(end_payload)
        textpayload = "" 
        try:
            textpayload = "%s" % end_payload
        except Exception,e:
            logger.error("Error convertion bintoascii snort payload")
            textpayload = ""
        #ev['log'] = hexlify(end_payload)
        ev['log'] = textpayload
        len_data = len(data) - len(end_payload)
        capture=data[:]
        compress_data = zlib.compress(capture)
        #logger.info("PKC COMPRESS: %s" % compress_data)
        ev['binary_data'] = hexlify(compress_data)
        return ev
    @staticmethod
    def get_SerialUnified2ExtraData(data):
        '''
             //UNIFIED2_EXTRA_DATA - type 110
             typedef struct _SerialUnified2ExtraData{
                 uint32_t sensor_id;
                 uint32_t event_id;
                 uint32_t event_second;
                 uint32_t type;              /* EventInfo */
                 uint32_t data_type;         /*EventDataType */
                 uint32_t blob_length;       /* Length of the data + sizeof(blob_length) + sizeof(data_type)*/
             } SerialUnified2ExtraData;
        '''
    @staticmethod
    def get_Serial_Unified2IDSEvent_legacy(data, type, length):
        '''
            //---------------LEGACY, type '7'
            //These structures are not used anymore in the product
            typedef struct _Serial_Unified2IDSEvent_legacy
            {
                uint32_t sensor_id;
                uint32_t event_id;
                uint32_t event_second;
                uint32_t event_microsecond;
                uint32_t signature_id;
                uint32_t generator_id;
                uint32_t signature_revision;
                uint32_t classification_id;
                uint32_t priority_id;
                uint32_t ip_source;
                uint32_t ip_destination;
                uint16_t sport_itype;
                uint16_t dport_icode;
                uint8_t  protocol;
                uint8_t  impact_flag;//sets packet_action
                uint8_t  impact;
                uint8_t  blocked;
            } Serial_Unified2IDSEvent_legacy;
        '''
        sensor_id, event_id, event_second, \
        event_microsecond, signature_id, \
        generator_id, signature_revision, \
        classification_id, priority_id, \
        ip_source, ipdestination, \
        sport_itype, dport_icode, \
        protocol, impact_flag, impact, blocked = struct.unpack("!IIIIIIIIIIIHHBBBB", data)
        sip_int = int(ip_source)
        dip_int = int(ipdestination)
        source_ip_str = socket.inet_ntoa(struct.pack("I", socket.htonl((sip_int))))
        dest_ip_str = socket.inet_ntoa(struct.pack("I", socket.htonl((dip_int))))
        ev = SnortIDSEvent()
        ev["raw_data"] = hexlify(data)
        ev["sensor_id"] = sensor_id
        ev["event_id"] = event_id
        ev["event_second"] = event_second
        ev["event_microsecond"] = event_microsecond
        ev["signature_id"] = signature_id
        ev["generator_id"] = generator_id
        ev["signature_revision"] = signature_revision
        ev["classification_id"] = classification_id
        ev["priority_id"] = priority_id
        ev["ip_source"] = source_ip_str
        ev["ip_destination"] = dest_ip_str
        ev["sport_itype"] = sport_itype
        ev["dport_icode"] = dport_icode
        ev["protocol"] = protocol
        ev["impact_flag"] = impact_flag
        ev["impact"] = impact
        ev["blocked"] = blocked
        ev["timestamp"] = time()#to detect lapsed events
        SnortUnpack.ids_events_lock.acquire()
        SnortUnpack.ids_events[event_id] = ev
        SnortUnpack.ids_events_lock.release()

    @staticmethod
    def get_Serial_Unified2IDSEventIPv6_legacy(data):
        '''
            //----------LEGACY, type '72'
            typedef struct _Serial_Unified2IDSEventIPv6_legacy
            {
                uint32_t sensor_id;
                uint32_t event_id;
                uint32_t event_second;
                uint32_t event_microsecond;
                uint32_t signature_id;
                uint32_t generator_id;
                uint32_t signature_revision;
                uint32_t classification_id;
                uint32_t priority_id;
                struct in6_addr ip_source;
                struct in6_addr ip_destination;
                uint16_t sport_itype;
                uint16_t dport_icode;
                uint8_t  protocol;
                uint8_t  impact_flag;
                uint8_t  impact;
                uint8_t  blocked;
            } Serial_Unified2IDSEventIPv6_legacy;        
        '''
        sensor_id, event_id, event_second, \
        event_microsecond, signature_id, \
        generator_id, signature_revision, \
        classification_id, priority_id = struct.unpack("!IIIIIIIII", data[0:36])
        str_ipv6_source = socket.inet_ntop(socket.AF_INET6, data[36:52])        
        str_ipv6_dest = socket.inet_ntop(socket.AF_INET6, data[52:68])   
        source_port_itype, dest_port_itype, protocol, impact_flag, impact, blocked = struct.unpack("!HHBBBB", data[68:])
        ev = SnortIDSEvent()
        ev["raw_data"] = hexlify(data)
        ev["sensor_id"] = sensor_id
        ev["event_id"] = event_id
        ev["event_second"] = event_second
        ev["event_microsecond"] = event_microsecond
        ev["signature_id"] = signature_id
        ev["generator_id"] = generator_id
        ev["signature_revision"] = signature_revision
        ev["classification_id"] = classification_id
        ev["priority_id"] = priority_id
        ev["ip_source"] = str_ipv6_source
        ev["ip_destination"] = str_ipv6_dest
        ev["sport_itype"] = source_port_itype
        ev["dport_icode"] = dest_port_itype
        ev["protocol"] = protocol
        ev["impact_flag"] = impact_flag
        ev["impact"] = impact
        ev["blocked"] = blocked
        ev["timestamp"] = time()#to detect lapsed events
        SnortUnpack.ids_events_lock.acquire()
        SnortUnpack.ids_events[event_id] = ev
        SnortUnpack.ids_events_lock.release()
    @staticmethod
    def purgeEvents():
        while SnortUnpack.keep_purgin:
            events_to_del = []
            SnortUnpack.ids_events_lock.acquire()
            for event_id, event in SnortUnpack.ids_events.iteritems():
                if time() - float(event["timestamp"]) > 5:
                    events_to_del.append(event_id)
            for event_id in events_to_del:
                del SnortUnpack.ids_events[event_id]
            SnortUnpack.ids_events_lock.release()
            del events_to_del[:]
            sleep(1)
        logger.info("ending... snort unpacker thread..")
    @staticmethod
    def startPurgeEventsThread():
        logger.info("Starting snort  unpacker purge thread")
        if SnortUnpack.purge_thread is None:
            SnortUnpack.purge_thread_started = True
            SnortUnpack.keep_purgin = True
            SnortUnpack.purge_thread = threading.Thread(target=SnortUnpack.purgeEvents, args=())
            SnortUnpack.purge_thread.start()
    @staticmethod
    def stopPurgeEventsThread():
        SnortUnpack.keep_purgin = False


class SnortEventsParser(Detector):
    '''
        This class is a tool to parse snort events from the snort 
        binary files.
        
    '''
    snort_events_by_type = {}
    snort_events_by_type[1] = "UNIFIED2_EVENT"
    snort_events_by_type[2] = "UNIFIED2_PACKET"
    snort_events_by_type[4] = "EVENT_TYPE_EXTRA_DATA"
    snort_events_by_type[7] = "UNIFIED2_IDS_EVENT" 
    snort_events_by_type[72] = "UNIFIED2_IDS_EVENT_IPV6"
    snort_events_by_type[99] = "UNIFIED2_IDS_EVENT_MPLS"
    snort_events_by_type[100] = "UNIFIED2_IDS_EVENT_IPV6_MPLS"
    snort_events_by_type[104] = "UNIFIED2_IDS_EVENT_VLAN"
    snort_events_by_type[105] = "UNIFIED2_IDS_EVENT_IPV6_VLAN"
    snort_events_by_type[110] = "UNIFIED2_EXTRA_DATA"
    snort_events_by_type[207] = "UNIFIED2_IDS_EVENT_NG"
    snort_events_by_type[208] = "UNIFIED2_IDS_EVENT_IPV6_NG"
    def __init__(self, config, plugin_config):
        '''
        config: Agent configuration object
        plugin_config: Plugin configuration
        '''
        Detector.__init__(self, config, plugin_config, None)
        
        self.__keepWorking = False
        self.__pluginConfig = plugin_config
        self.__keep_working_v_lock = threading.RLock()
        self.__logDirectory = ""
        self.__filePrefix = ""
        self.__currentOpenedLogFile_fd = None
        self.__currentOpenedLogFile_name = ""
        self.__currentOpenedLogFile_size = 0
        self.__timestamp = 0
        self.__logfiles = []
        self.__skipOldEvents = True


    def __getKeepWorkingValue(self):
        '''
            Returns the current keep_working value.
        '''
        tmpValue = False
        self.__keep_working_v_lock.acquire()
        tmpValue = self.__keepWorking
        self.__keep_working_v_lock.release()
        return tmpValue


    def __setKeepWorkingValue(self, value):
        self.__keep_working_v_lock.acquire()
        self.__keepWorking = value
        self.__keep_working_v_lock.release()


    def stop(self):
        '''
            Stop parsing.
        '''
        self.__setKeepWorkingValue(False)
        self.__logfiles = []
        #Indicates if we've to read old events or only the new ones.
        self.__skipOldEvents = False

    def __lookForFiles(self, updatemode=False):
#        if updatemode:
#            if self.__currentOpenedLogFile_fd is not None:
#                try:
#                    self.__currentOpenedLogFile_fd.close()
#                except Exception:
#                    pass

        filter_str = "%s%s*" % (self.__logDirectory, self.__filePrefix)
        tmpfiles = glob.glob(filter_str)
        snortfiles = []
        pattern = re.compile("(.*\d{10})")
        for f in tmpfiles:
            if pattern.match(f):
                snortfiles.append(f)
        #Order by timestamp. Asc 
        snortfiles.sort(reverse=True)
        if len(snortfiles) > 0:
            if not updatemode:
                self.__timestamp = 0
            last_one = snortfiles[0]
            #lookup for the timestamp. Example log file name :snort_eth0.1328203225
            last_timestamp = last_one [last_one.rindex('.') + 1:]
            if not updatemode:
                self.__timestamp = last_timestamp
                self.__logfiles.append(last_one)
            else:
                if (last_timestamp > self.__timestamp) and last_one not in self.__logfiles:
                    self.__logfiles.append(last_one)

    def __tryRotate(self):
        self.__lookForFiles(True)
        if len(self.__logfiles) > 0:
            self.__currentOpenedLogFile_fd.close()
            self.__currentOpenedLogFile_name = ""
            self.__currentOpenedLogFile_fd = None
        
    def __do_skipOldEvents(self):
        """ 
        Skip all existing events, fast forwarding to the end of the last complete 
        unified record.
        Help:
        os.SEEK_SET: Absolute position in the file
        os.SEEK_CURSOR: From the current position
        os.SEEK_END: From the end of the file
        """
        skipping_complete = False


        while not skipping_complete:

            # save current position for rewinding if required
            pos = self.__currentOpenedLogFile_fd.tell()
            if (pos + SNORT_FILE_HEADER_SIZE) <= self.__currentOpenedLogFile_size:
                data = self.__currentOpenedLogFile_fd.read(SNORT_FILE_HEADER_SIZE)
                if len(data) != SNORT_FILE_HEADER_SIZE:
                    raise Exception, "I/O error on file %s" % self.__currentOpenedLogFile_name

                (type, size) = struct.unpack("!II", data)

                # check record can be extracted from current file 
                if (pos + size) <= self.__currentOpenedLogFile_size:
                    # the record header holds the true length of the record for 
                    # all unified2 records
                    self.__currentOpenedLogFile_fd.seek(size, os.SEEK_CUR)
                else:
                    skipping_complete = True
                    self.__currentOpenedLogFile_fd.seek(pos, os.SEEK_SET)

            else:
                skipping_complete = True
                self.__currentOpenedLogFile_fd.seek(pos, os.SEEK_SET)

        logger.info("Skipped all existing events...")
    def process(self):
        '''
        Process the snort file.
        '''
        self.__setKeepWorkingValue(True)
        keepWorking = self.__getKeepWorkingValue()
        if self.__pluginConfig.get("config", "linklayer") != "ethernet":
            logger.error("This kind of snort parser only works for 'ethernet' linklayer.Please use the old one")
            return
        if int(self.__pluginConfig.get("config", "unified_version")) != 2:
            logger.error("This kind of snort parser only works for 'UNIFIED 2' V,ersion.Please use the old one")
            return
        self.__filePrefix = self.__pluginConfig.get("config", "prefix")
        if self.__filePrefix == "":
            logger.error("Invalid prefix used.")
            return
        self.__logDirectory = self.__pluginConfig.get("config", "directory")
        self.__lookForFiles()
        #return
        SnortUnpack.startPurgeEventsThread()
        last_valid_position = 0
        last_valid_packet_size = 0
        #testconter = 0
        while keepWorking:
            sleep(0.02)#to avoid excessive cpu usage when no snort events.
            if self.__currentOpenedLogFile_fd == None:
                if len(self.__logfiles) == 0:
                    #There's no files ....waiting for it
                    self.__lookForFiles(True)
                    #and wait for some time...
                    sleep(10)
                    continue
                else:
                    #read the file!
                    self.__currentOpenedLogFile_name = self.__logfiles[0]
                    del self.__logfiles[0]
                    self.__timestamp = self.__currentOpenedLogFile_name[self.__currentOpenedLogFile_name.rindex('.') + 1:]
                    try:
                        self.__currentOpenedLogFile_fd = open(self.__currentOpenedLogFile_name, 'r')
                    except IOError:
                        logger.error("Error reading file %s: it no longer exists" % self.__currentOpenedLogFile_name)
                    # For unified (version 2) files there is no dedicated file header. The endianess
                    # is always in NETWORK byte order.
            else:
                #there's an opened file..
                logger.debug("Processing file : %s" % self.__currentOpenedLogFile_name)
                filestat = os.fstat(self.__currentOpenedLogFile_fd.fileno())
                self.__currentOpenedLogFile_size = filestat[stat.ST_SIZE]
                position = self.__currentOpenedLogFile_fd.tell()
                if self.__skipOldEvents:
                    logger.debug("Skip evetns enabled!!")
                    self.__do_skipOldEvents()
                    self.__skipOldEvents = False
                position = self.__currentOpenedLogFile_fd.tell()
                if (position + SNORT_FILE_HEADER_SIZE) <= self.__currentOpenedLogFile_size:
                    data = self.__currentOpenedLogFile_fd.read(SNORT_FILE_HEADER_SIZE)
                    type, size = struct.unpack("!II", data)
                else:
                    self.__tryRotate()
                    continue
                position = self.__currentOpenedLogFile_fd.tell()
                #wait until the packet bytes are written by snort
                max_tries = 10 # Max tries until the data should be there
                while ((position + size) > self.__currentOpenedLogFile_size ) and max_tries > 0:
                    logger.info("waiting until Snort writes the packet data")
                    filestat = os.fstat(self.__currentOpenedLogFile_fd.fileno())
                    self.__currentOpenedLogFile_size = filestat[stat.ST_SIZE]
                    max_tries = max_tries -1
                    sleep(0.1)
                
                if (position + size) <= self.__currentOpenedLogFile_size:
                    data = self.__currentOpenedLogFile_fd.read(size)
                    position = self.__currentOpenedLogFile_fd.tell()
                    if self.snort_events_by_type.has_key(type):
                        last_valid_position = position - size - SNORT_FILE_HEADER_SIZE
                        last_valid_packet_size = size
                        if type == UNIFIED2_EVENT:#1
                            SnortUnpack.get_UNIFIED2_EVENT(data) # --Not information
                        elif type == UNIFIED2_PACKET: #2
                            ev = SnortUnpack.get_Serial_Unified2Packet(data, type, size) #           -- done
                            if ev:
                                #logger.info(str(ev))
                                self.send_message(ev)
                        elif type == EVENT_TYPE_EXTRA_DATA:
                            SnortUnpack.get_EVENT_TYPE_EXTRA_DATA(data)#4                       -not information
                        elif type == UNIFIED2_IDS_EVENT:#7                                        -- done
                            SnortUnpack.get_Serial_Unified2IDSEvent_legacy(data, type, size) 
                        elif type == UNIFIED2_IDS_EVENT_IPV6:#72                                        -- done
                            SnortUnpack.get_Serial_Unified2IDSEventIPv6_legacy(data) 
                        elif type == UNIFIED2_IDS_EVENT_MPLS:#99                        -not information
                            SnortUnpack.get_UNIFIED2_IDS_EVENT_MPLS(data)
                        elif type == UNIFIED2_IDS_EVENT_IPV6_MPLS:#100                        -not information
                            SnortUnpack.get_UNIFIED2_IDS_EVENT_IPV6_MPLS(data)
                        elif type == UNIFIED2_IDS_EVENT_VLAN:#104                                -- done
                            SnortUnpack.get_Unified2IDSEvent(data) 
                        elif type == UNIFIED2_IDS_EVENT_IPV6_VLAN:#105                            --done
                            SnortUnpack.get_Unified2IDSEventIPv6(data)
                        elif type == UNIFIED2_EXTRA_DATA:#110
                            SnortUnpack.get_SerialUnified2ExtraData(data)
                        elif type == UNIFIED2_IDS_EVENT_NG:#207                                  NOT YET INFORMATION
                            SnortUnpack.get_Unified2IDSEventNG(data)
                        elif type == UNIFIED2_IDS_EVENT_IPV6_NG:#208 -                           NOT YET INFORMATION
                            SnortUnpack.get_Unified2IDSEventIPv6_NG(data) 
                    else:
                        logger.error("Unknown record type: %s, last valid cursor: %s, last valid packet size: %s, current_cursor: %s, theoric packet size: %s " % (type, last_valid_position,last_valid_packet_size,position,size))
                        self.__currentOpenedLogFile_fd.seek(position, os.SEEK_CUR)
                else:
                    logger.info("Snort Log file size is less than packet size... we have been waiting for a second, try rotate..")
                    #Set the current position of file descriptor fd to position pos, modified by how: SEEK_SET or 0 to set the position relative to the beginning of the file; SEEK_CUR or 1 to set it relative to the current position; os.SEEK_END or 2 to set it relative to the end of the file. 
                    self.__currentOpenedLogFile_fd.seek(position, os.SEEK_SET)
                    self.__tryRotate()
                #testconter+=1
            keepWorking = self.__getKeepWorkingValue()
        SnortUnpack.stopPurgeEventsThread()
