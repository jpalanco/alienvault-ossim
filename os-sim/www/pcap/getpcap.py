#!/usr/bin/env python

#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2013 AlienVault
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


import getopt
import sys
import re
import socket
import zlib
from binascii import hexlify, unhexlify
import time
import logging

logger = logging.getLogger('getpcap')
hdlr = logging.FileHandler('/var/tmp/getpcap.log')
formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
hdlr.setFormatter(formatter)
logger.addHandler(hdlr)
logger.setLevel(logging.INFO)

#
#   Sends get_pcap command to the framework
#   to get the pcap file.
#
E_GETOPT = 2
E_INVALID_ARGS = 3
E_SOCKET_TIMEOUT = 4
E_SOCKET_ERROR = 5
E_BAD_RESPONSE = 6


def usage():
    print ("""
    Usage getpcap:
        -v set verbose mode
        -h show this help
        -p pcap file to download
        -t path to store dwonloaded file.
        -f framework ip
        -l framework listen port
        -a agent id

    Example:
    getpcap.py -p pcapfile -t path_to_save_file
    getpcap.py --pcap=pcapfile --tempath=path_to_save_file
    """)


def main():
    pcap = ""
    path_pcap = ""
    agent = ""
    vebose = False
    framework_ip = "127.0.0.1"
    framework_port = 40003
    try:
        opts, args = getopt.getopt(sys.argv[1:], "hp:t:a:l:f:v",
                                   ["help", "pcap=", "tempath=", "agent=", "frameworkip=", "frameworkport="])
    except getopt.GetoptError, err:
        print str(err)
        usage()
        sys.exit(E_GETOPT)
    if len(opts) < 2:
        logger.error("Invalid arguments")
        usage()
        sys.exit(E_INVALID_ARGS)

    for option, value in opts:
        if option == "-v":
            verbose = True
        elif option in ("-h", "--help"):
            usage()
            sys.exit(0)
        elif option in ("-p", "--pcap"):
            pcap = value
        elif option in ("-t", "--tempath"):
            path_pcap = value
        elif option in ("-a", "--agent"):
            agent = value
        elif option in ("-f", "--frameworkip"):
            framework_ip = value
        elif option in ("-l", "--frameworkport"):
            try:
                framework_port = int(value)
            except ValueError:
                logger.error("Invalid port %s, using defualt 40003" % value)
                framework_port = 40003
        else:
            assert False, "unhandled option"

    logger.info("Requested pcap to download: %s - path:%s" % (pcap, path_pcap))
    fmk_conn = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    msg = "control action=\"net_scan_capture_get\" id=\"%s\" path=\"%s\"\n" % (agent, pcap)

    expeted_response_reg = "control\s+net_scan_capture_get\s+transaction=\"\d{1,6}\" id=\"\w+\"\s+data=\"(?P<data>.*)\"\s+datalen=\"(?P<datalen>\d+)\"\s+errno=\"\d\"\s+error=\"Success.\"\s+ackend"
    regexp = re.compile(expeted_response_reg)

    try:
        fmk_conn.connect((framework_ip, framework_port))
        logger.info("Sendig request to the framework: %s" % msg)
        fmk_conn.send(msg)
        time.sleep(1)
        data = ""
        while True:
            tmpdata = fmk_conn.recv(2048)
            if tmpdata == "ping\n":
                continue
            data = data + tmpdata
            if tmpdata.endswith("\n"):
                break

    except socket.timeout, e:
        logger.error("Socket time out.")
        sys.exit(E_SOCKET_TIMEOUT)
    except socket.error as err:
        logger.error("Socket error. :%s" % str(err))
        sys.exit(E_SOCKET_ERROR)
    # print data

    result = regexp.match(data)
    if result is not None:
        datalen = result.group("datalen")
        pcap_data = result.group("data")

        logger.info("ok datalen:%s saved file on %s" % (datalen, path_pcap))
        unhexdata = unhexlify(pcap_data)
        realdata = zlib.decompress(unhexdata)
        file_d = open(path_pcap, 'w')
        file_d.write(realdata)
        file_d.close()
        fmk_conn.close()
    else:
        # try get file ...
        tmp_array = data.split()

        if len(tmp_array) == 9 and tmp_array[-1] == "ackend":
            pcapdata = tmp_array[4]
            if pcapdata.startswith("data="):
                realdata = pcapdata[6:len(pcapdata) - 1]
                unhexdata = unhexlify(realdata)
                realdata = zlib.decompress(unhexdata)
                datalen = len(realdata)
                file_d = open(path_pcap, 'w')
                file_d.write(realdata)
                file_d.close()
                fmk_conn.close()
                logger.info("ok datalen:%s saved file on %s" % (datalen, path_pcap))
        else:
            file_err = open("./error_get_pcap.log", "w")
            file_err.write("Error:%s" % data)
            file_err.close()

            logger.error("Error: Bad response. See error file: ./error_get_pcap.log")
            sys.exit(E_BAD_RESPONSE)
    sys.exit(0)


if __name__ == "__main__":
    # ./getpcap.py -v -p netscan_admin_1318348659_180_10.67.68.12.pcap -t /tmp/pp.pcap -a 10.67.68.12
    main()
