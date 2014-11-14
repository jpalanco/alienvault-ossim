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
from binascii import hexlify
import socket, struct, sys
import time
#
# LOCAL IMPORTS
#
from Logger import Logger
from Utils import dumphexdata

#
# GLOBAL VARIABLES
#
logger = Logger.logger



class UDPPacket:

    def __init__(self, data):
        self._data = data
        (self.sport, self.dport, self.length, self.checksum) = struct.unpack(">HHHH", data[0:8])
        self._payload = data[8:]


    def getpayload(self):
        return self._payload

    payload = property(getpayload)


    def dump(self):
        print "UDP Header"
        print "SPORT:%u DPORT:%u LENGTH:%04x CHECKSUM:%04x" % (self.sport, self.dport, self.length, self.checksum)
        print "Payload"
        dumphexdata(self._payload)


    def __str__(self):
        st = """ udp_sport="%u" udp_dport="%u" udp_len="%u" udp_csum="%u" udp_payload="%s" """
        st = st % (self.sport, self.dport, self.length, self.checksum, hexlify(self._payload))

        return st



class TCPPacket:

    def __init__(self,data):
        """
         TCP Header Format
            
                0                   1                   2                   3   
                0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 
               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
               |          Source Port          |       Destination Port        |
               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
               |                        Sequence Number                        |
               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
               |                    Acknowledgment Number                      |
               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
               |  Data |           |U|A|P|R|S|F|                               |
               | Offset| Reserved  |R|C|S|S|Y|I|            Window             |
               |       |           |G|K|H|T|N|N|                               |
               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
               |           Checksum            |         Urgent Pointer        |
               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
               |                    Options                    |    Padding    |
               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
               |                             data                              |
               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+

        """
        self._data = data
        (self.sport,self.dport, \
        self.seq,self.ack,self.b1,self.b2, \
        self.window,self.checksum,
        self.urgent)=struct.unpack(">HHIIBBHHH",data[0:20])

        
        self.offset = (self.b1 & 0xf0)>>4
        self.res = (self.b1&0xf)|(self.b2&0xc0)<<4

        self.__flags = {1:"C", 2:"E", 4:"U", 8:"A", 16:"P", 32:"R", 64:"S", 128:"F"}
        self.__flags_keys = self.__flags.keys().sort()
        self.flags = (self.b2 & 0x3f)
       
        self.opt =[]
        strlog ="""
            tcp packet
            sport = %s
            dport = %s
            seq = %s
            ack = %s
            b1 = 0x%02x
            b2 = 0x%02x
            offset = %d
        """ % (self.sport,self.dport,self.seq,self.ack,self.b1,self.b2,self.offset)
        
        
        #logger.info(strlog)
        
        
        if self.offset <= 5:
            self.payload = data[20:]

        else:
            # XXX These calcs can be fake at the TCP Header
            # we must be sure that all fields are correct or no decode options
            #print "TCP:Opciones:  %04x" % self.offset

            optionsize = self.offset*4-20
            

            self.options = data[20:20+optionsize]
            self.payload = data[20+optionsize:]
            self._parseoptions()


    def __str__(self):
        st=""" tcp_sport="%u" tcp_dport="%u" """+\
        """ tcp_seq="%u" tcp_ack="%u" tcp_offset="%u" """+\
        """ tcp_flags="%u"  """+\
        """ tcp_window="%u" tcp_csum="%u" tcp_urgptr="%u" """

        st = st % \
        (self.sport,self.dport,
        self.seq,self.ack,self.b1,self.b2,
        self.window,self.checksum,self.urgent)

        if len(self.opt)>0:
            st_opt = """ tcp_optnum="%u" """ % len(self.opt)
            i=0

            for (c,l,v)  in self.opt:
                st_opt = st_opt + \
                """ tcp_optcode="%u" tcp_optlen="%u" tcp_optpayload="%s" """

                st_opt = st_opt % \
                (c,l,hexlify(v))

                i = i+1

        else:
            st_opt=""

        # The payload
        st = st+st_opt+""" tcp_payload="%s" """ % hexlify(self.payload)

        return st


    def _parseoptions(self):
        options = self.options[:]
       
        while len(options)>0:
            c, = struct.unpack(">B",options[0])

            if c==0:
                self.opt.append((0,0,""))
                return # not process more options

            elif c==1:
                self.opt.append((1,0,""))
                options=options[1:]

            else:
                try:
                    l, = struct.unpack(">B",options[1:2])
                    l = l - 2
                    if(l > (len(options)-2)):
                        logger.warning("Can't decode TCP option %02x: Payload:%s " % (c, hexlify(self.options)))
                        self.offset = 5
                        return
                    
                    if (l>0):
                        v = options[2:2+l]
                        self.opt.append((c,l,v))
                        options=options[2+l:]

                    elif l==0:
                        v=""
                        self.opt.append((c,l,v))
                        options=options[2+l:]

                    else:
                        raise Exception, "Error processing TCP Options"

                except Exception, e_msg:
                    options=options[2:]
                    logger.warning("Can't decode TCP option %02x: Payload:%s (exception: %s)" % (c, hexlify(self.options), str(e_msg)))


    def dump(self):
        print "TCP Header"
        print "SPORT:%u DPORT:%u" % (self.sport,self.dport)
        print "SEQ:%08x ACK:%08x" % (self.seq,self.ack)
        print "HDRLEN:%04x WINDOW:%04x FLAGS:%s" % (self.offset,self.window,self._getflags())
        print "CHEKSUM:%04x URG:%04x" % (self.checksum,self.urgent)

        if len(self.options)>0:
            print "Options"
            dumphexdata(self.options)

            print "Payload"
            dumphexdata(self.payload)


    def _getflags(self):
        st =""

        for k in self.__flags_keys():
            if self.flags & k:
                st += self.__flags[k]

            else:
                st += "."

        return st


def getprotobynumber(n):
    if n == socket.IPPROTO_ICMP:
        return "ICMP"

    elif n == socket.IPPROTO_UDP:
        return "UDP"

    elif n == socket.IPPROTO_TCP:
        return "TCP"

    else:
        return "UNKNOWN:%u"  % n



class RawPacket:
    """These class represent a raw packet capture from ethernet."""

    def __init__(self,data):
        self._data = data


    def dump(self):
        dumphexdata(self._data)


    def __str__(self):
        return "raw_payload=\""+hexlify(self._data)+"\""



class UnknownIPPacket:
    """These class represent a raw packet capture from ethernet."""

    def __init__(self,data):
        self._data = data


    def dump(self):
        dumphexdata(self._data)


    def __str__(self):
        return "ip_ippayload=\""+hexlify(self._data)+"\""



class ICMPPacket(object):
    """These class represent a ICMP packet"""

    def __init__(self,data):
        # Must check the len of the data because can be a fake packet
        self._data = data
        (self.type,self.code,self.checksum)= \
                struct.unpack(">BBH",data[0:4])
        # fuck snort. If type ==  0 or type == 8 , check for icmp_seq and icmp_id
        if self.type == 0 or self.type == 8:
            (self.icmp_id,self.icmp_seq) = struct.unpack(">HH",data[4:8])
            self.packetpayload = data[8:]

        else:
            self.icmp_id = self.icmp_seq = 0
            self.packetpayload = data[4:]


    def dump(self):
        print "ICMP Header"
        print "TYPE: %02x CODE:%02x CHECKSUM:%04x" % (self.type,self.code,self.checksum)


    def __str__(self):
        st=""" icmp_type="%u" icmp_code="%u" """+\
            """ icmp_csum="%u" icmp_id="%u" icmp_seq="%u" """ + \
            """ icmp_payload="%s" """

        st = st % (self.type,self.code,self.checksum,self.icmp_id,self.icmp_seq,hexlify(self.packetpayload))

        return st



class IPPacket:
    """These class represent a IP data packet"""

    def __init__(self,data):
        """Init the class with the data from a packet"""

        self.opt = []

        if len(data)>=20:
            self.packet = data
            (self.version, self.tos, self.length, \
            self.id,self.offset, \
            self.ttl,self.protocol, \
            self.checksum,self.sip, \
            self.dip) = struct.unpack(">BBHHHBBHII",data[0:20])

            self.hdrlen = (self.version & 0xf)
            self.version = (self.version & 0xf0)>>4

            #self.offset = (self.flags >> 3)
            #self.flags = (self.flags & 0x7)
            #
            #print "Marca: hdrlen %u" % self.hdrlen

            if self.hdrlen == 5:
                self.options=""
                self.payload=data[20:20+self.length]

            else:
                lenopt = self.hdrlen*4-20
                print "Opciones: %u " % lenopt
                self.options=data[20:20+lenopt]
                self._parseoptions()
                self.payload=data[20+lenopt:]

            # Decode the IP Packet
            if len(self.payload)>0:
                if self.protocol == socket.IPPROTO_UDP:
                    self.packetpayload = UDPPacket(self.payload)
                    self.dport = self.packetpayload.dport
                    self.sport = self.packetpayload.dport

                elif self.protocol == socket.IPPROTO_TCP:
                    self.packetpayload = TCPPacket(self.payload)
                    self.dport = self.packetpayload.dport
                    self.sport = self.packetpayload.sport

                elif self.protocol == socket.IPPROTO_ICMP:
                    self.packetpayload = ICMPPacket(self.payload)
                    self.dport = self.sport = 0

                else:
                    self.packetpayload = UnknownIPPacket(self.payload)
                    self.dport = self.sport = 0

            else:
                self.packetpayload = ""

        else:
            raise Exception,"Error: Incomplete ip packet"
    def __str__(self):
        st = """ip_ver="%u" ip_hdrlen="%u" ip_tos="%u" """ + \
             """ip_len="%u" ip_id="%u" """ + \
             """ip_offset="%u" """ + \
             """ip_ttl="%u" ip_proto="%u" """ + \
             """ip_csum="%u" """ + \
             """ip_src="%s" ip_dst="%s" """

        st = st % \
                (self.version,self.hdrlen,self.tos,
                self.length,self.id,
                self.offset,
                self.ttl,self.protocol,
                self.checksum,socket.inet_ntoa(struct.pack(">L",self.sip)),
                socket.inet_ntoa(struct.pack(">L",self.dip)))

        if len(self.opt)>0:
            st_opt=""" ip_optnum="%u" """ % len(self.opt)

            for (c,l,v) in self.opt:
                print c,l,v
                st_opt = st_opt + \
                    """ip_optcode="%u" ip_optlen="%u" ip_optpayload="%s" """ % \
                    (c,l,hexlify(v))

        else:
            st_opt=""

        # Now the protocol decoder
        # XXX
        #print "Protocolo: %u" % self.protocol

        if self.packetpayload<>"":
            #print type(self.packetpayload)
            st = st+st_opt+str(self.packetpayload)

        else:
            st = st+st_opt

        return st


    def _parseoptions(self):
        options = self.options[:]

        while len(options)>0:
            c, = struct.unpack(">B",options[0])

            if c==0:
                self.opt.append((0,0,""))
                return

            elif c==1:
                self.opt.append((1,0,""))
                options=options[1:]

            else:
                try:
                    l, = struct.unpack(">B",options[1:2])
                    l = l - 2

                    if l>0:
                        v = options[2:2+l]
                        self.opt.append((c,l,v))
                        options=options[2+l:]

                    elif l==0:
                        v = ""
                        self.opt.append((c,l,v))
                        options=options[2+l:]

                    else:
                        raise Exception, "Error processing IP  options"

                except:
                    logger.warning("Bad IP option %02x: Not len or data %s" % (c,hexlify(self.options)))


    def dump(self):
        """Dump ip packet"""

        print "IP Header"
        print "Version:%02x hdrlen:%02x tos:%02x packetlen:%04x" % (self.version,self.hdrlen,self.tos,self.length)
        print "ID:%04x Flags:%02x FragOffset:%04x" % (self.id,self.flags,self.offset)
        print "TTL:%02x Protocol:%s Checksum:%04x" % (self.ttl,getprotobynumber(self.protocol),self.checksum)
        print "SRC:%s DST:%s" % (socket.inet_ntoa(struct.pack("L",socket.htonl(self.sip))), \
            socket.inet_ntoa(struct.pack("L",socket.htonl(self.dip))))

        # Print options
        if len(self.options)>0:
            print "Dump IP options:"
            dumphexdata(self.options)

        # Dump UDP / TCP / ICMP
        if self.packetpayload!=None:
            self.packetpayload.dump()


class IPV6Packet:
    """These class represent a IP data packet"""

    def __init__(self,data):
        """Init the class with the data from a packet"""

        self.opt = []
        #Firs header
        """
        sf_snort_packet.h
        
        typedef struct _IPv6Hdr
        { 
            u_int32_t vcl;      /* version, class, and label */
            u_int16_t len;      /* length of the payload */
            u_int8_t  next;     /* next header
                                 * Uses the same flags as
                                 * the IPv4 protocol field */
            u_int8_t  hop_lmt;  /* hop limit */ 
            sfip_t ip_src;
            sfip_t ip_dst;
        } IP6Hdr; 
        typedef struct _ip {
            int family;
            int bits;
        
            /* see sfip_size(): these address bytes
             * must be the last field in this struct */
            union
            {
                u_int8_t  u6_addr8[16];
                u_int16_t u6_addr16[8];
                u_int32_t u6_addr32[4];
        //        u_int64_t    u6_addr64[2];
            } ip;
            #define ip8  ip.u6_addr8
            #define ip16 ip.u6_addr16
            #define ip32 ip.u6_addr32
        //    #define ip64 ip.u6_addr64
        } sfip_t;
        
        Header IPV6:(http://en.wikipedia.org/wiki/IPv6_packet)
        
     Oct     byte 1            byte2            byte 3            byte 4
        |________________|________________|________________|________________|
      0 |version| class(8)        |      Flow label(20b)                    |
      4 |payload len                      | next header    | hop limit      |
      8 |_______________________src addr (128bits)__________________________|
      12|___________________________________________________________________|
      16|___________________________________________________________________|
      20|___________________________________________________________________|
      24|_______________________dest addr (128 bits)________________________|
      28|___________________________________________________________________|
      32|___________________________________________________________________|
      36|___________________________________________________________________|         
        """
            #Next header. Specifies the type of next header. (http://en.wikipedia.org/wiki/List_of_IP_protocol_numbers)        
        
        if len(data)>=40:
            vlc,pack_length,next_header_type,hop_lmt = struct.unpack("!IHBB",data[0:8])    
            self.src_ip = socket.inet_ntop(socket.AF_INET6,data[8:24])
            self.dst_ip = socket.inet_ntop(socket.AF_INET6,data[24:40])            
            version = vlc >> 28
            traffic_class = (vlc & 0x0ff00000) >> 20
            label = (vlc & 0x000fffff)
            header_len = 40
            data_size = len(data)
            self.packet = data 
            self.version = version
            self.traffic_class = traffic_class
            self.flow_label = label
            self.payload_len = pack_length
            self.hop_limit = hop_lmt
            self.protocol = next_header_type
            self.payload = data[40:40+self.payload_len]
            if pack_length> 0:
                if self.protocol == socket.IPPROTO_ICMP:
                    self.packetpayload = ICMPPacket(self.payload)
                    self.dport = 0
#                    logger.info("ICMP:%s" % self.packetpayload)
                if self.protocol == socket.IPPROTO_TCP:
                    self.packetpayload = TCPPacket(self.payload)
                    self.dport = self.packetpayload.dport
                    self.sport = self.packetpayload.sport
#                    logger.info("TCP:%s" % self.packetpayload)
                if self.protocol == socket.IPPROTO_UDP:
                    self.packetpayload = UDPPacket(self.payload)
                    self.dport = self.packetpayload.dport
                    self.sport = self.packetpayload.dport
#                    logger.info("UDP:%s" % self.packetpayload)
            else:
                logger.info("Unknown packet type:%s" % self.protocol)
                self.packetpayload = ""

        else:
            raise Exception,"Error: Incomplete ip packet"

    def __str__(self):
        st = """ip_ver="%u" ip_traffic="%u" ip_flowlabel="%u" """ + \
             """ip_payload_len="%u" ip_proto="%u" """ + \
             """ip_hoplimit="%u" ip_src="%s" ip_dst="%s"  """
        st = st % \
                (self.version, \
                 self.traffic_class, \
                 self.flow_label, \
                 self.payload_len, \
                 self.protocol, \
                 self.hop_limit, \
                 self.src_ip, \
                 self.dst_ip
                 )
        
        
        # Now the protocol decoder
        # XXX
        #print "Protocolo: %u" % self.protocol

        if self.packetpayload <> "":
            #print type(self.packetpayload)
            st = st + str(self.packetpayload)
        return st


    def dump(self):
        """Dump ip packet"""

        print "IP Header"
        print "Version:0x%x traffic_class:0x%02x flow_label:0x%03x" % (self.version, self.traffic_class, self.flow_label)
        print "payload_len:0x%04x next_header:0x%02x hop_limit:0x%02x" % (self.packet_len, self.protocol, self.hop_limit)
        print "SRC:%s DST:%s" % (self.src_ip, self.dst_ip)

        # Print options
        if len(self.options) > 0:
            print "Dump IP options:"
            dumphexdata(self.options)

        # Dump UDP / TCP / ICMP
        if self.packetpayload != None:
            self.packetpayload.dump()

# vim:ts=4 sts=4 tw=79 expandtab:

