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
import sys, os, struct, time, socket, string
import re
import stat
import zlib
import socket
from binascii import hexlify

#
# LOCAL IMPORTS
#
from PacketUtils import UDPPacket, IPPacket, RawPacket, TCPPacket, IPV6Packet, getprotobynumber
from Utils import dumphexdata
from Logger import Logger
logger = Logger.logger
ETHERNET_TYPE_IP = 0x0800
ETHERNET_TYPE_IPV6 = 0x86dd
ETHERNET_TYPE_8021Q = 0x8100
class SnortUnified:
    """
    All unified, version 1 and 2, constants.
    """

    U1_TYPE_ALERT = 1
    U1_TYPE_LOG = 2
    U1_TYPE_ALERT_IPV6 = 3

    U1_RECORD_TYPES = [U1_TYPE_ALERT, \
                       U1_TYPE_LOG, \
                       U1_TYPE_ALERT_IPV6]

    U2_TYPE_EVENT = 1
    U2_TYPE_PACKET = 2
    U2_TYPE_IDS_EVENT = 7
    U2_TYPE_IDS_EVENT_IPV6 = 72
    U2_TYPE_IDS_EVENT_MPLS = 99
    U2_TYPE_IDS_EVENT_IPV6_MPLS = 100
    U2_IDS_EVENT_VLAN = 104
    U2_IDS_EVENT_IPV6_VLAN = 105
#    U2_EXTRA_DATA = 110


    U2_RECORD_TYPES = [U2_TYPE_EVENT, \
                       U2_TYPE_PACKET, \
                       U2_TYPE_IDS_EVENT, \
                       U2_TYPE_IDS_EVENT_IPV6, \
                       U2_TYPE_IDS_EVENT_MPLS, \
                       U2_TYPE_IDS_EVENT_IPV6_MPLS, \
                       U2_IDS_EVENT_IPV6_VLAN,
                       U2_IDS_EVENT_VLAN]




#
# 
#
class EventSnort:
    """
    This class represents a Snort Event
    """

    def __init__(self, endian, unified_version=1, snortconf=None, linklayertype="ethernet"):
        self._endian = endian
        self.snortconf = snortconf
        self._pkt = None
        self._packet = None
        self._unified_version = unified_version

        self.sensor_id = 0
        self.sig_generator = 0
        self.sig_id = 0
        self.sig_rev = 0
        self.classification = 0
        self.priority = 0
        self.event_id = 0
        self.event_reference = 0
        self.event_second = 0
        self.ref_tv_sec = 0
        self.ref_tv_usec = 0
        self.tv_sec = 0
        self.tv_usec = 0
        self.sip = 0
        self.dip = 0
        self.sport = 0
        self.dport = 0
        self.protocol = 0
        self.flags = 0
        self.ipv6Event = False

        self.protocol = ""
        self.impact_flag = ""
        self.impact = ""
        self.blocked = ""
        if linklayertype == "ethernet":
            self.offsetip = 14
        elif linklayertype == "cookedlinux":
            self.offsetip = 16
        else:
            raise Exception, "Unknown link layer type"
    def addevent(self, t, data):
        if self._unified_version == 1:
            # extract event information
            (self.sig_generator, \
             self.sig_id, \
             self.sig_rev, \
             self.classification, \
             self.priority, \
             self.event_id, \
             self.event_reference, \
             self.ref_tv_sec, \
             self.ref_tv_usec) = struct.unpack(self._endian + "IIIIIIIII", data[:36])

            if t == SnortUnified.U1_TYPE_ALERT:
                # extract event time
                (self.tv_sec, \
                 self.tv_usec, \
                 self.sip, \
                 self.dip, \
                 self.sport, \
                 self.dport, \
                 self.protocol, \
                 self.flags) = struct.unpack(self._endian + "IIIIHHII", data[36:64])

            elif t == SnortUnified.U1_TYPE_LOG:
                pass

            else:
                raise Exception, "Unhandled unified1 record type."

        elif self._unified_version == 2:
            if t in [SnortUnified.U2_TYPE_EVENT, SnortUnified.U2_TYPE_IDS_EVENT]:
                # check if a packet exists and if so that

                # extract event information
                (self.sensor_id, \
                 self.event_id, \
                 self.tv_sec, \
                 self.tv_usec, \
                 self.sig_id, \
                 self.sig_generator, \
                 self.sig_rev, \
                 self.classification, \
                 self.priority, \
                 self.sip, \
                 self.dip, \
                 self.sport, \
                 self.dport, \
                 self.protocol) = struct.unpack("!IIIIIIIIIIIHHB", data[:49])
            elif t == SnortUnified.U2_IDS_EVENT_VLAN:
                self.sensor_id, \
                self.event_id, \
                self.tv_sec, \
                self.tv_usec, \
                self.sig_id, \
                self.sig_generator, \
                self.sig_rev, \
                self.classification, \
                self.priority, \
                self.sip, \
                self.dip, \
                self.sport, \
                self.dport, \
                self.protocol, \
                impact_flag, impact, blocked, mpls_label, \
                vlanid, pad = struct.unpack("!IIIIIIIIIIIHHBBBBIHH", data)
            elif t == SnortUnified.U2_IDS_EVENT_IPV6_VLAN:
                self.ipv6Event = True
            elif t == SnortUnified.U2_TYPE_IDS_EVENT_IPV6:
                self.ipv6Event = True
                self.sensor_id, \
                self.event_id, \
                self.tv_sec, \
                self.tv_usec, \
                self.sig_id, \
                self.sig_generator, \
                self.sig_rev, \
                self.classification, \
                self.priority = struct.unpack("!IIIIIIIII", data[0:36])
                self.sip = socket.inet_ntop(socket.AF_INET6, data[36:52])        
                self.dip = socket.inet_ntop(socket.AF_INET6, data[52:68])
                self.sport, \
                self.dport, \
                self.protocol, \
                self.impact_flag, \
                self.impact, \
                self.blocked = struct.unpack("!HHBBBB", data[68:])
            elif t in [SnortUnified.U2_TYPE_IDS_EVENT_MPLS, SnortUnified.U2_TYPE_IDS_EVENT_IPV6_MPLS]:
                pass
            elif t == SnortUnified.U2_TYPE_PACKET:
                pass


            else:
                raise Exception, "Unhandled unified2 record type."


    def addpacket(self, t, data):

        if self._unified_version == 1:
            if t == SnortUnified.U1_TYPE_ALERT:
                logger.debug("No packet information exists in this record.")

            elif t == SnortUnified.U1_TYPE_LOG:

                (self.logflags, \
                 self.tv_sec, \
                 self.tv_usec, \
                 self.__packet_caplen, \
                 self.__packet_realcaplen) = struct.unpack(self._endian + "IIIII", data[36:56])
                self._pkt = data[56:]

                # Snort pseudopacket (from sfportscan)
                if self._pkt[0:12] == "MACDADMACDAD":
                    self.offsetip = 14

                self._ethertype, = struct.unpack("!H", self._pkt[self.offsetip - 2:self.offsetip])
                #print "Ethertype: %04x" % self._ethertype
                if self._ethertype == ETHERNET_TYPE_IP:
                    self._packet = IPPacket(self._pkt[self.offsetip:])
                    if self._packet.version is 6:
                        logger.debug("Unsupported IP Version 6")
                        dumphexdata(self._pkt)
                        self.sip = self.dip = self.dport = self.sport = 0
                        self.protocol = self.flags = 0
                        self._packet = RawPacket(self._pkt)
                    else:
                        try:
                            self.sip = self._packet.sip
                        except:
                            self.sip = 0
                        try:
                            self.dip = self._packet.dip
                        except:
                            self.dip = 0
                        try:
                            self.dport = self._packet.dport
                        except:
                            self.dport = 0
                        try:
                            self.sport = self._packet.sport
                        except:
                            self.sport = 0
                        try:
                            self.protocol = self._packet.protocol
                        except:
                            self.protocol = 0

                    self.flags = 0

                elif self._ethertype == ETHERNET_TYPE_8021Q:  #VLAN!
                    self.offsetip += 4
                    self._packet = IPPacket(self._pkt[self.offsetip:])
                    if self._packet.version is 6:
                        logger.debug("Unsupported IP Version 6")
                        dumphexdata(self._pkt)
                        self.sip = self.dip = self.dport = self.sport = 0
                        self.protocol = self.flags = 0
                        self._packet = RawPacket(self._pkt)
                    else:
                        try:
                            self.sip = self._packet.sip
                        except:
                            self.sip = 0
                        try:
                            self.dip = self._packet.dip
                        except:
                            self.dip = 0
                        try:
                            self.dport = self._packet.dport
                        except:
                            self.dport = 0
                        try:
                            self.sport = self._packet.sport
                        except:
                            self.sport = 0
                        try:
                            self.protocol = self._packet.protocol
                        except:
                            self.protocol = 0

                    self.flags = 0

                #Further options should be added here
                else:
                    logger.info("Unsupported Ethertype: %04x" % self._ethertype)
                    dumphexdata(self._pkt)
                    self.sip = self.dip = self.dport = self.sport = 0
                    self.protocol = self.flags = 0
                    self._packet = RawPacket(self._pkt)

            else:
                raise Exception, "Unhandled unified1 record type."

        elif self._unified_version == 2:
            if t in [SnortUnified.U2_TYPE_EVENT, SnortUnified.U2_TYPE_IDS_EVENT]:
                logger.debug("No packet information exists in this record.")
            elif t == SnortUnified.U2_TYPE_PACKET:
                (self.sensor_id, \
                 self.event_id, \
                 self.event_sec, \
                 self.tv_sec, \
                 self.tv_usec, \
                 self._linktype, \
                 self.__caplen) = struct.unpack("!IIIIIII", data[0:28])
                self._pkt = data[28:]
                self._ethertype, = struct.unpack("!H", self._pkt[self.offsetip - 2:self.offsetip])
                if self._ethertype == ETHERNET_TYPE_IP:
                    self._packet = IPPacket(self._pkt[self.offsetip:])
                    self.flags = 0
                elif self._ethertype == ETHERNET_TYPE_8021Q:  #VLAN!
                    self.offsetip += 4
                    self._sethertype, = struct.unpack("!H", self._pkt[self.offsetip - 2:self.offsetip])
                    if self._sethertype == ETHERNET_TYPE_IP: #IP
                        self._packet = IPPacket(self._pkt[self.offsetip:])
                        self.flags = 0
                    elif self._sethertype == ETHERNET_TYPE_IPV6: #IP V6
                        self._packet = IPV6Packet(self._pkt[self.offsetip:])
                        #self.src_ipv6_ip =self._packet.src_ip
                        #self.dst_ipv6_ip =self._packet.dst_ip
                        self.ipv6Event = True
                        self.flags = 0
                    else:
                        logger.info("L2 Unsupported Ethertype: %04x" % self._sethertype)
                        logger.info("Dump packet %s " % hexlify (self._pkt))
                        self.sip = self.dip = self.dport = self.sport = 0
                        self.protocol = self.flags = 0
                        self._packet = RawPacket(self._pkt)
                elif self._ethertype == ETHERNET_TYPE_IPV6:                    
                    self._packet = IPV6Packet(self._pkt[self.offsetip:])
                    self.ipv6Event = True
                    #self.src_ipv6_ip =self._packet.src_ip
                    #self.dst_ipv6_ip =self._packet.dst_ip
                #Further options should be added here
                else:
                    logger.info("Unsupported Ethertype: %04x" % self._ethertype)
                    dumphexdata(self._pkt)
                    self.sip = self.dip = self.dport = self.sport = 0
                    self.protocol = self.flags = 0
                    self._packet = RawPacket(self._pkt)
            else:
                raise Exception, "Unhandled unified2 record type."


    def __str__(self):
        lt = time.localtime(self.tv_sec)
        st = ""
        if self.ipv6Event:
            st = """type="detector" date="%s" fdate="%s" sensor_id="%s" """ + \
                           """event_id="%s" event_second="%s" event_microsecond="%s" signature_id="%s" """ + \
                           """generator_id="%s" signature_revision="%s" classification_id="%s" priority_id="%s" """ + \
                           """source_ip="%s" destination_ip="%s" source_port_itype="%s" dest_port_itype="%s" """ + \
                           """protocol="%s" impact_flag="%s" impact="%s" blocked="%s" """
            st = st % (int(time.mktime(lt)), \
                       time.strftime("%Y-%m-%d %H:%M:%S", lt), \
                       self.sensor_id, \
                       self.event_id, \
                       self.tv_sec, \
                       self.tv_usec, \
                       self.sig_id, \
                       self.sig_generator, \
                       self.sig_rev, \
                       self.classification, \
                       self.priority, \
                       self.sip, \
                       self.dip, \
                       self.sport, \
                       self.dport, \
                       self.protocol, \
                       self.impact_flag, \
                       self.impact, \
                       self.blocked) 
        else:
            st = """type="detector" date="%s" fdate="%s" """ + \
             """snort_gid="%u" snort_sid="%u" snort_rev="%u" """ + \
             """snort_classification="%u" snort_priority="%u" """

            st = st % (int(time.mktime(lt)), time.strftime("%Y-%m-%d %H:%M:%S", lt), \
                self.sig_generator, self.sig_id, self.sig_rev, \
                self.classification, self.priority)

        if self._packet != None:
            if isinstance(self._packet, IPPacket):
                st += """ packet_type="ip" """
            if isinstance(self._packet, IPV6Packet):
                #logger.info("PAQUETE IPV6 :%s" % str(self._packet))
                #st += """ packet_type="ipv6" """
                st += """ packet_type="ipv6" """
            elif isinstance(self._packet, RawPacket):
                st += """ packet_type="raw" """
            st += str(self._packet)

        logger.debug("EventSnort: %s" % st)
        return st


    def strgzip(self):
        data = zlib.compress(self.__str__())
        return (len(self.__str__()), hexlify(data))


    def geteventid(self):
        return self.event_id


    def getcaplen(self):
        return self.__packet_caplen


    def getrealcaplen(self):
        return self.__packet_realcaplen


    caplen = property(getcaplen)
    packetlen = property(getrealcaplen)


    def dump(self):
        def _searchgen(gen):
            if self.GENERATOR.has_key(gen):
                return self.GENERATOR[gen]
            else:
                return "UNKNOWN GENERATOR"

        eventtime = time.strftime("%d-%m-%Y %H:%M:%S", time.localtime(self.tv_sec))

        if (self.snortconf != None):
            selfgen = "%s.%10u ID: %u G:%s SID:%s CLASS:%u PRI:%u" % (eventtime, self.tv_sec, \
                self.event_id, \
                _searchgen(self.sig_generator), \
                self.snortconf.searchsid(self.sig_id, self.sig_generator), \
                self.classification, \
                self.priority)
        else:
            selfgen = "%s.%10u ID: %u G:%s SID:%s CLASS:%u PRI:%u" % (eventtime, self.tv_sec, \
                self.event_id, \
                _searchgen(self.sig_generator), \
                self.sig_id, \
                self.classification, \
                self.priority)

        eventreftime = time.strftime("%d-%m-%Y %H:%M:%S", time.localtime(self.ref_tv_sec))
        eventref = " REF:%u REFTIME:%s.%10u" % (self.event_reference, eventreftime, self.ref_tv_usec)
        selfipinfo = " PROTO:%s SRC:%s:%d -> DST:%s:%d F:%08x" % (getprotobynumber(self.protocol), \
            socket.inet_ntoa(struct.pack("L", socket.htonl((self.sip)))), \
            self.sport, \
            socket.inet_ntoa(struct.pack("L", socket.htonl((self.dip)))), \
            self.dport, \
            self.flags)

        if (self.event_reference != self.event_id):
            selfrefevent = "\t%s\n" % (eventref)
        else:
            selfrefevent = ""

        if self.type == EventSnort.TYPEALARM:
            print "ALARM " + selfgen + selfipinfo + selfrefevent
        elif self.type == EventSnort.TYPELOG:
            print "ALARMLOG " + selfgen + selfipinfo + selfrefevent
        else:
            raise Exception, "Bad alarm type"

    def isIPV6(self):
        return self.ipv6Event
class ParserSnort(object):

    # unified (version 1) structures
    # 1 -> Alarm log
    # 2 -> alarm log packat
    U1_MAGICLITTEENDIAN = 0x2dac5ceb
    U1_MAGICBIGENDIAN = 0x5ceb2dac
    U1_SIZEHDR = 8
    U1_SIZEALARMLOG = 48

    # unified (version 2) structures
    UX_SIZERECORDHDR = 8


    def __init__(self, linklayer="ethernet", snort=None, unified_version=1):
        self.snortconf = snort
        self._fd = None
        self._currentname = ""
        self._prefix = ""
        self._dir = ""
        self._hdrread = False
        self._evskip = False
        self._unified_version = unified_version

        # for unified 2 files we need to cache event information to correlate
        # packet information with
        self._event_cache = {}
        self._event_cache_types = {}
        if linklayer == "ethernet":
            self.offsetip = 14
            self.linklayer = linklayer
        elif linklayer == "cookedlinux":
            self.offsetip = 16
            self.linklayer = linklayer
        else:
            raise Exception, "Unknown linklayer"

        # TODO: clean these variable names up _logdir is the files to be processed.
        self._logfiles = []
        self._logdir = []


    def _filterfile(self, file):
        """
        Filter files of the form: prefix.timestamp
       
        The prefix is the filename prefix specified in the snort configuration file and
        the timestamp is a number representing the UNIX ctime.

        Keyword arguments:
        file -- filename being processed   
        """
        r = re.compile(self._prefix + r'\.\d+$')

        return os.path.isfile(os.path.join(self._dir, file)) and \
               r.match(file) != None


    def _checklogname(self):
        dircontents = filter(self._filterfile, os.listdir(self._dir))
        dircontents.sort()

        if len(dircontents) > 0:
            return os.path.join(self._dir, dircontents[-1])
        else:
            return None


    def _readhdr(self, fd):
        """ 
        Read and verify the header of the unified file, as appropriate.
        
        For unified (version 1) files the endianess of the file is in HOST byte order.
        The endianess is determined by the constant magic number.

        For unified (version 2) files there is no dedicated file header. The endianess
        is always in NETWORK byte order.

        Keyword arguments:
        fd -- file descriptor of the open unified file being processed   
        """

        # stat the file and save retrieve the filesize
        st = os.fstat(fd.fileno())
        self._filesize = st[stat.ST_SIZE]

        # read the header (if we have enough bytes)
        if (self._unified_version == 1):
            if self._filesize >= self.U1_SIZEHDR:
                data = fd.read(self.U1_SIZEHDR)

                if len(data) != self.U1_SIZEHDR:
                    raise Exception, "I/O error on %s" % self._currentname

                # pull out the file magic
                (magic, flags) = struct.unpack("II", data)

                if magic == self.U1_MAGICLITTEENDIAN:
                    self._endian = '<'
                elif magic == self.U1_MAGICBIGENDIAN:
                    self._endian = '>'
                else:
                    raise Exception, "Bad magic number on %s" % self._currentfile

                return True

        elif (self._unified_version == 2):
            self._endian = '!'
            return True

        return False


    def _skipevents(self, fd):
        """ 
        Skip all existing events, fast forwarding to the end of the last complete 
        unified record.

        Keyword arguments:
        fd -- file descriptor of the open unified file being processed   
        """

        skipping_complete = False

        # quick check to see if have at least the header (version 1 only)
        if (self._unified_version == 1):
            pos = fd.tell()
            if (pos + self.U1_SIZEHDR) > self._filesize:
                return True

        while not skipping_complete:

            # save current position for rewinding if required
            pos = fd.tell()

            if (pos + self.UX_SIZERECORDHDR) <= self._filesize:
                data = fd.read(self.UX_SIZERECORDHDR)

                if len(data) != self.UX_SIZERECORDHDR:
                    raise Exception, "I/O error on file %s" % self._currentfile

                (type, size) = struct.unpack(self._endian + "II", data)

                # check record can be extracted from current file 
                if (pos + size) <= self._filesize:

                    # the record header holds the true length of the record for 
                    # all unified2 records and unified1 event records only
                    if (self._unified_version == 1 and type == 1) or \
                        self._unified_version == 2:
                        fd.seek(size, os.SEEK_CUR)

                    # for unified1 packet records the packet length needs to be
                    # determined to get the true record length
                    elif (self._unified_version == 1 and type == 2):
                        data = fd.read(size)

                        if len(data) != size:
                            raise Exception, "I/O error on file %s" % self._currentfile

                        # grab the capture length from the libpcap packet header
                        (caplen,) = struct.unpack(self._endian + "I", data[48:52])

                        if (pos + caplen) <= self._filesize:
                            fd.seek(caplen, os.SEEK_CUR)
                        else:
                            skipping_complete = True
                            fd.seek(pos, os.SEEK_SET)

                    else:
                        raise Exception, "Bad type of record %s on file %s" % (type, self._currentfile)

                else:
                    skipping_complete = True
                    fd.seek(pos, os.SEEK_SET)

            else:
                skipping_complete = True
                fd.seek(pos, os.SEEK_SET)

        logger.debug("Skipped all existing events...")


    def init_log_dir(self, directory, prefix):
        # close existing file descriptor if open
        if self._fd != None:
            self._fd.close()
            self._fd = None

        # update internal file prefix and direcotry for searching
        self._prefix = prefix
        self._dir = directory

        # reset timestamp
        self._timestamp = 0

        # grab directory contents and sort
        dircontents = filter(self._filterfile, os.listdir(self._dir))
        dircontents.sort()

        self._logdir = []

        # if available grab the the most recent unified file,
        # otherwise set none
        if len(dircontents) > 0:
            # get the timestamp
            # The snort timestamp is:
            # prefix.timestamp, where timestamp is the Unix ctime
            temp = dircontents[-1]
            self._timestamp = temp[temp.rindex('.') + 1:]
            self._logdir = [os.path.join(self._dir, dircontents[-1])]

        self._currentfile = ""
        # skip all events on init (the first time)
        self._skip = True
        #self._skip = False


    def _update_log_list(self):
        # grab directory contents and sort
        dircontents = filter(self._filterfile, os.listdir(self._dir))
        dircontents.sort()

        logger.debug("dircontents: %s" % dircontents)

        # check we have some log files to check
        if len(dircontents) > 0:
            timestamp = 0

            for f in dircontents:
                timestamp = f[f.rindex('.') + 1:]
                f = os.path.join(self._dir, f)

            if (timestamp > self._timestamp) and f not in self._logdir:
                self._logdir.append(f)


    def _try_rotate(self):
        logger.debug("Rotating log files...")
        time.sleep(10)
        self._update_log_list()

        if len(self._logdir) > 0:
            self._fd.close()
            self._fd = None


    def get_snort_event(self, filtertype=2):
        while 1:
            # if no file is being read
            if self._fd == None:
                # if files are available not available update and pause for 10s
                if len(self._logdir) == 0:
                    self._update_log_list()
                    time.sleep(10)
                    continue

                # otherwise select from the list
                else:
                    logger.debug("There are some files to read...")
                    self._currentfile = self._logdir[0]
                    del self._logdir[0]
                    self._timestamp = self._currentfile[self._currentfile.rindex('.') + 1:]

                    logger.debug("self._currentfile: %s" % self._currentfile)
                    logger.debug("self._timestamp: %s" % self._timestamp)

                    try:
                        self._fd = open(self._currentfile, "r")
                    except IOError:
                        logger.error("Error reading file %s: it no longer exists" % self._currentfile)

                    # we need to read the header of the new file
                    self._hdrread = False

            # read the header
            if not self._hdrread:
                if not self._readhdr(self._fd):
                    self._update_log_list()
                    if len(self._logdir) > 0:
                        #We have a newer file
                        self._fd.close()
                        self._fd = None
                    else:
                        time.sleep(10)
                    continue

                else:
                    self._hdrread = True

            # skip events and capture only new events from now
            if self._skip:
                self._skipevents(self._fd)
                self._skip = False

            # Now, procces the file
            pos = self._fd.tell()
            st = os.fstat(self._fd.fileno())
            self._filesize = st[stat.ST_SIZE]

            if (pos + self.UX_SIZERECORDHDR) <= self._filesize:
                data = self._fd.read(self.UX_SIZERECORDHDR)

                if len(data) != self.UX_SIZERECORDHDR:
                    raise Exception, "I/O error on %s" % self._currentfile

                (type, size) = struct.unpack(self._endian + "II", data)

                # check we can grab the entire record
                if pos + size <= self._filesize:
                    data = self._fd.read(size)

                    if len(data) != size:
                        raise Exception, "I/O error on %s" % self._currentfile

                    if self._unified_version == 1:
                        if type == SnortUnified.U1_TYPE_ALERT:
                            if filtertype == type:
                                ev = EventSnort(self._endian, 1, None, linklayertype=self.linklayer)
                                ev.addevent(type, data)
                                return ev

                        elif type == SnortUnified.U1_TYPE_LOG:
                            (caplen,) = struct.unpack(self._endian + "L", data[48:52])

                            if (pos + caplen) <= self._filesize:
                                pkt = self._fd.read(caplen)

                                if len(pkt) != caplen:
                                    raise Exception, "I/O error on %s" % self._currentfile

                                data += pkt

                                if filtertype == type:
                                    ev = EventSnort(self._endian, 1, None, linklayertype=self.linklayer)
                                    ev.addevent(type, data)
                                    ev.addpacket(type, data)
                                    return ev

                            # rewind if we couldn't get the complete record and packet
                            else:
                                self._fd.seek(pos, os.SEEK_SET)
                                self._try_rotate()

                        else:
                            raise Exception, "Bad/Unimplemented record type %u for unified1 file %s" % (type, self._currentfile)
                            self._fd.seek(size, os.SEEK_CUR)

                    elif self._unified_version == 2:
                        if type in [SnortUnified.U2_TYPE_EVENT, SnortUnified.U2_TYPE_IDS_EVENT, SnortUnified.U2_TYPE_IDS_EVENT_IPV6, SnortUnified.U2_IDS_EVENT_IPV6_VLAN, SnortUnified.U2_IDS_EVENT_VLAN]:
                            ev = EventSnort(self._endian, 2, None, linklayertype=self.linklayer)
                            
                            ev.addevent(type, data)
                            # cache this event for later correlation
                            self._event_cache[ev.geteventid()] = data
                            self._event_cache_types[ev.geteventid()] = type

                            # TODO: i don't think we can assume all events have associated packets
                            if filtertype == type:
                                return ev

                        elif type == SnortUnified.U2_TYPE_PACKET:
                            if filtertype == type:
                                ev = EventSnort(self._endian, 2, None, linklayertype=self.linklayer)
                                ev.addpacket(type, data)
                                
                                event_id = ev.geteventid()
                                # add correlated cached event as appropriate
                                if event_id in self._event_cache:
                                    ev.addevent(self._event_cache_types[event_id], self._event_cache[event_id])
                                    
                                    # TODO: we should not delete events that occur once for
                                    # multiple packets (eg. portscans and tagged rules)
                                    del self._event_cache[event_id]

                                    # only return packets with associated event information
                                    return ev

                        else:
                            raise Exception, "Bad/Unimplemented record type %u for unified2 file %s" % (type, self._currentfile)
                            self._fd.seek(size, os.SEEK_CUR)

                # rewind if we couldn't get the complete record
                else:
                    self._fd.seek(pos, os.SEEK_SET)
                    self._try_rotate()

            else:
                # If there isn't any more data (we can read the header of the alarm)
                self._try_rotate()


    def monitor(self, directory, prefix, filtertype=2):

        if (prefix != self._prefix) or (directory != self._dir):
            if self._fd != None:
                self._fd.close()

            self._currentname = ""
            self._prefix = prefix
            self._dir = directory
            self._hdrread = False
            self._evskip = False

        while 1:
            tempname = self._checklogname()

            if tempname == None:
                time.sleep(10)
                continue

            # Now, the rotate file logic
            if self._currentname <> tempname:
                if self._fd <> None:
                    self._fd.close()

                print "Tempname: %s" % tempname
                self._currentname = tempname
                self._fd = open(self._currentname, "r")
                print "File Descriptor: %u" % self._fd.fileno()
                self._hdrread = False

            if (self._unified_version == 1) and not self._hdrread:
                if not self._readhdr(self._fd):
                    time.sleep(10)
                    continue

                else:
                    self._skipevents(self._fd)

                self._hdrread = True

            # Wait for events
            pos = self._fd.tell()
            st = os.fstat(self._fd.fileno())

            if (pos + self.UX_SIZERECORDHDR) <= st[stat.ST_SIZE]:
                data = self._fd.read(self.UX_SIZERECORDHDR)
                if len(data) != self.UX_SIZERECORDHDR:
                    raise Exception, "I/O error on %s" % self._currentname

                (type, size) = struct.unpack(self._endian + "II", data)

                if type == 1 and pos + size <= st[stat.ST_SIZE]:
                    if filtertype == type:
                        data = self._fd.read(size)
                        if len(data) != size:
                            raise Exception, "I/O error on %s" % self._currentname
                        ev = EventSnort(EventSnort.TYPEALARM, data, self._endian, None, None, linklayertype=self.linklayer)
                        return ev
                    else:
                        # Skip record
                        self._fd.seek(size, 1)

                elif type == 2 and pos + size <= st[stat.ST_SIZE]:
                    data = self._fd.read(size)
                    if len(data) != size:
                        raise Exception, "I/O error on %s" % self._currentname
                    (caplen,) = struct.unpack(self._endian + "L", data[48:52])
                    if (pos + caplen) <= st[stat.ST_SIZE]:
                        pkt = self._fd.read(caplen)
                        if len(pkt) != caplen:
                            raise Exception, "I/O error on %s" % self._currentname
                        if filtertype == type:
                            ev = EventSnort(EventSnort.TYPELOG, data, self._endian, None, pkt, linklayertype=self.linklayer)
                            return  ev
                    else:
                        self._fd.seek(pos, 0)

                elif (type != 1 and type != 2):
                    raise Exception, "Bad type of record on file %s" % self._currentname
            else:
                time.sleep(10)
            # end while


    def dumpfile(self, f, filtertype=2):
        """ dump a unified alarm / log record"""
        if not os.path.isfile(f):
            raise Exception, "Error: %s is not a file" % file
        fd = open(f, "r")
        self._currentname = f
        if self._unified_version == 1:
            self.dumpfile_u1(fd, filtertype)
        elif self._unified_version == 2:
            self.dumpfile_u2(fd)
        else:
            print "Error: Unknown Snort Unified Version %u" % self._unified_version

    def dump_snort_unified2_record(self, type, data):
        if type == SnortUnified.U2_TYPE_PACKET:
            print "Unified2 Packet"
            (sensor_id, \
            event_id, \
            event_second, \
            packet_second, \
            packet_microsecond, \
            lynktype, \
            packet_length) = struct.unpack(">IIIIIII", data[:28])
            print """
            sensor_id: %u
            event_id: %u
            event_second: %u
            packet_second: %u
            packet_microsecond: %u
            lynktype: %u
            packet_length: %u""" % \
            (sensor_id, event_id, event_second, \
            packet_second, packet_microsecond, \
            lynktype, packet_length)
            ev = EventSnort(">", unified_version=2, linklayertype="ethernet")
            ev.addpacket(2, data)
            dumphexdata(data[28:])
        elif type == SnortUnified.U2_TYPE_IDS_EVENT:
            print "Unified2 IDS Event"
            (sensor_id, \
            event_id, \
            event_second, \
            event_microsecond, \
            signature_id, \
            generator_id, \
            signature_revision, \
            classification_id, \
            priority_id, \
            ip_source, \
            ip_destination, \
            sport_itype, \
            dport_icode, \
            protocol, \
            packet_action) = struct.unpack(">IIIIIIIII4s4sHHBB", data)
            print """
            sensor_id: %u
            event_id: %u
            event_second: %u
            event_microsecond: %u
            signature_id: %u
            generator_id: %u
            signature_revision: %u
            classification_id: %u
            priority_id: %u
            ip_source: %s
            ip_destination:%s 
            sport_itype: %u
            dport_icode: %u
            protocol: %u
            action: %u\n""" \
            % (sensor_id, event_id, event_second, \
            event_microsecond, signature_id, generator_id, \
            signature_revision, classification_id, priority_id, \
            socket.inet_ntoa(ip_source), \
            socket.inet_ntoa(ip_destination), \
            sport_itype, dport_icode, protocol, packet_action)
        else:
            print "Unified2 event no implemented %u" % type


    def dumpfile_u2(self, fd):
        if self._readhdr(fd):
            while 1:
                data = fd.read(self.UX_SIZERECORDHDR)
                if len(data) < self.UX_SIZERECORDHDR:
                    fd.close()
                    print "End of file"
                    return
                # Unified 2 always big endian
                (type, size) = struct.unpack(">II", data)
                if type in SnortUnified.U2_RECORD_TYPES:
                    recorddata = fd.read(size)
                    if type == SnortUnified.U2_TYPE_PACKET:
                        self.dump_snort_unified2_record(type, recorddata)

                else:
                    print "Unknown unified2 record type:%u" % type
                    fd.close()
                    return
                # Read the data
                #print "Datos %u" % len(data)
        fd.close()


    def dumpfile_u1(self, fd, filtertype):
        if self._readhdr(fd):
            while 1:
                data = fd.read(self.UX_SIZERECORDHDR)
                if data == "":
                    fd.close()
                    print "End of file"
                    return

                if len(data) == self.UX_SIZERECORDHDR:
                    (type, size) = struct.unpack(self._endian + "II", data)
                    if type == EventSnort.TYPEALARM:
                        data = fd.read(size)
                        if len(data) != size:
                            raise Exception, "I/O error on %s" % self._currentname
                        if (filtertype == type):
                            ev = EventSnort(EventSnort.TYPEALARM, data, self._endian, None, None)
                            print ev

                    elif type == EventSnort.TYPELOG:
                        data = fd.read(size)
                        if len(data) != size:
                            raise Exception, "I/O error on %s" % self._currentname
                        (caplen,) = struct.unpack(self._endian + "L", data[48:52])
                        pkt = fd.read(caplen)
                        if len(pkt) != caplen:
                            raise Exception, "I/O error on %s" % self._currentname
                        if filtertype == type:
                            ev = EventSnort(EventSnort.TYPELOG, data, self._endian, None, pkt)
                            print ev

                    else:
                        raise Exception, "Bad type of record on file %s" % self._currentname

        else:
            print "Can't read the header of %s" % file

# vim:ts=4 sts=4 tw=79 expandtab
