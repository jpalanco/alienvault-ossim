#!/usr/bin/env python
from base64 import b64decode
import struct
from binascii import unhexlify,hexlify
import socket
import zlib
from scapy.all import *
from struct import *
import time
from optparse import OptionParser
import sys
PCAP_MAGIC_NUMBER =  0xa1b2c3d4L
PCAP_VERSION_MAJOR = 2
PCAP_VERSION_MINOR = 4
PCAP_SIGFIGS =  0
PCAP_SNAPLEN = 65535

if __name__ == '__main__':
    binary_data=""
    parser = OptionParser()
    parser.add_option("-u", "--binarydata", dest='binarydata', help="Binary data field")
#    parser.add_option("-l", "--log", dest="log",help="log field")
    parser.add_option("-p", "--pcapfile", dest="pcap_file",help="PCAP File",metavar="FILE")

    pcap_file = "/tmp/pcap_%s.pcap" % time.time()
    (options, args) = parser.parse_args()
    if options.binarydata is not None:
        binary_data = options.binarydata
    else:
        print "Invalid data to build the pcap"
        sys.exit()
    if options.pcap_file is not None:
        pcap_file = options.pcap_file
    tmpdata = unhexlify(binary_data)

    bin_data = zlib.decompress(tmpdata)
    #Extract snort_packet data.

    sensor_id, event_id, event_second, packet_second, packet_ms, linktype,  packet_length = struct.unpack("!IIIIIII", bin_data[0:28])
    #Extract the real packet.
    packet_data = bin_data[28:]

    myfile = open(pcap_file,'w')
    # BUILD _ PCAP
    global_header = pack('IHHIIII', PCAP_MAGIC_NUMBER,PCAP_VERSION_MAJOR,PCAP_VERSION_MINOR,0,0,packet_length,linktype)
    myfile.write(global_header)
    packet_header = pack('IIII',packet_second,packet_ms,packet_length,packet_length)
    myfile.write(packet_header)
    myfile.write(packet_data)
    myfile.close()
    print "pcap writted! %s " % pcap_file
