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
import datetime
import os
import re
import threading
import time
import commands
import pcap
from base64 import b64encode
import zlib
from binascii import hexlify
#
# LOCAL IMPORTS
#
import ControlError
import ControlUtil
from Logger import Logger
import Utils

#
# GLOBAL VARIABLES
#
logger = Logger.logger
KYLOBYTE = 1024
MEGABYTE = 1024 * KYLOBYTE
GIGABYTE = 1024 * MEGABYTE
MAX_CAPTURE_LEN = 1 * GIGABYTE
MIN_FREE_SPACE = 5 * GIGABYTE
class SnifferStatus:
    STOPPED_ERROR = -1
    STOPPED_OK = 0
    WORKING = 1
    RUNNING_SCAN = 2
    CONVERTING_PCAP_TO_PDML = 3
class SnifferManager:

    __sniffer = None

    def __init__(self):
        if self.__sniffer == None:
            logger.info("Initializing Sniffer Manager.")
            self.__scan_in_progress = False
            self.__timeout_scan_in_progress = 0
            self.__start_time_scan_in_progress = 0
            self.__availableInterfaces = self.get_interface_list()
            # grab the tcpdump captures path
            self.__sniffer_captures_path = "/var/ossim/traffic"
            if not os.path.exists(self.__sniffer_captures_path):
                os.makedirs(self.__sniffer_captures_path)

            if os.path.exists(self.__sniffer_captures_path):
                logger.info("Sniffer capture path: %s" % self.__sniffer_captures_path)
            else:
                logger.error('Sniffer capture path "%s" does not exist or has restricted privileges!' % self.__sniffer_captures_path)

            self.__sniffer = SniffWork()
            self.__sniffer.start()
            logger.debug("Sniffer Manager initialized.")

    def get_interface_list(self):
        '''
        Returns the interface list.
        '''
        regex_str = "^\s*(?P<iface_name>\S+):.*"
        regex = re.compile(regex_str)
        interfaces = []
        if_file = open('/proc/net/dev', 'r')
        for line in if_file.readlines():
            groups = regex.match(line)
            if groups:
                interfaces.append(groups.groupdict()['iface_name'])
        if_file.close()
        return interfaces

    def getDiskUsage(self, path):
        '''
        Returns available free space (MB) in a partition.
        '''
        disk_state = os.statvfs(path)
        free_space = (disk_state.f_bsize * disk_state.f_bavail) / MEGABYTE
        return free_space

    def process(self, data, base_response):
        logger.info("Sniffer Manager: Processing: %s" % data)
        '''
            control action="net_scan" sensor="id_sensor" scan_name="file.pcap" eth="eth0,eth1,..ethx" hosts="192.168.1.2,192.168.1.3,..." nets="10.0.0.0/8,10.2.0.0/8,.."
            #capsize packets
            control action="net_scan" sensor="id_sensor" scan_name="file.pcap" eth="eth0" cap_size="20" raw_filter="src port 40001 and dsp port 3338"
            control action="net_scan_capture_list" sensor="id_sensor"
            control action="net_scan_capture_get" id="ID_CAPTURE"
            control action="net_scan_capture_delete" id="ID_CAPTURE"
            control action="net_scan_status"
            control action="net_scan_stop"
        '''
        response = []
        action = Utils.get_var("action=\"([A-Za-z_]+)\"", data)

        if action == "net_scan":
            free_space = self.getDiskUsage('/var/ossim')
            check = (MIN_FREE_SPACE / MEGABYTE)
            if free_space < (MIN_FREE_SPACE / MEGABYTE):
                response.append(base_response + ' status="-1" %s ackend\n' % (ControlError.get(3006)))
                return response
            device = Utils.get_var("eth=\"(\S+)\"" , data)
            if device not in self.__availableInterfaces:
                return response.append(base_response + ' %s ackend\n' % ControlError.get(3002))
                
            tmp_capture_size = Utils.get_var("cap_size=\"(\d+)\"" , data)
            capture_size = 0
            if tmp_capture_size:
              try:
                capture_size = int(tmp_capture_size)
              except TypeError:
                capture_size = 0
                logger.warning("Invalid Caputure size: %s" % tmp_capture_size)

            capture_name = Utils.get_var("scan_name=\"([0-9A-Za-z_\.]+)\"", data)
            try:
                tmp = Utils.get_var("timeout=\"(\d+)\"", data)
                if tmp != "":
                    timeout = int(tmp)
                else:
                    timeout = 60
            except TypeError:
                timeout = 60


            if device:
                if self.__sniffer.status() == SnifferStatus.RUNNING_SCAN or self.__sniffer.status() == SnifferStatus.WORKING:
                    logger.info("Scan already in progress: %i" % self.__sniffer.status())
                    response.append(base_response + ' status="%d" %s ackend\n' % (self.__sniffer.status(), ControlError.get(3001)))

                else:
                    #timestamp = datetime.datetime.today().strftime("%Y%m%d%H%M00")
                    #cap_file_name = "net_scan_%s_%s.pcap" % (device, timestamp)
                    cap_file_path = "%s/%s" % (self.__sniffer_captures_path, capture_name)
                    self.__sniffer.set_data_to_build_filter(data)
                    self.__sniffer.set_capture_file(cap_file_path)
                    self.__sniffer.set_device(device)
                    self.__sniffer.set_timeout(timeout)
                    self.__sniffer.set_capture_size(capture_size)
                    self.__sniffer.run_scan()
                    self.__scan_in_progress = True
                    self.__timeout_scan_in_progress = timeout
                    self.__start_time_scan_in_progress = time.time()
                    response.append(base_response + ' status="%d" %s ackend\n' % (self.__sniffer.status(), ControlError.get(0)))

            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(3002))

        elif action == "net_scan_stop":
            if self.__sniffer.status() > 0:
                self.__sniffer.stopScan()
                response.append(base_response + ' %s ackend\n' % (ControlError.get(0)))
            else:
                response.append(base_response + ' status="-1" %s ackend\n' % ControlError.get(3007))
        elif action == "net_scan_status":
            if self.__sniffer.status() == -1:
                response.append(base_response + ' status="-1" error="%s" ackend\n' % (self.__sniffer.get_error()))

            else:
                response.append(base_response + ' %s %s ackend\n' % (self.__sniffer.getStatusString(), ControlError.get(0)))

        elif action == "net_scan_capture_list":
            capture_files = self.__get_capture_file_list(self.__sniffer_captures_path)

            for p in capture_files:
                base_response += ' capture="%s"' % p

            response.append(base_response + ' count="%i" %s ackend\n' % (len(capture_files), ControlError.get(0)))

        elif action == "net_scan_capture_get":
            path = Utils.get_var("path=\"([0-9A-Za-z_\.]+)\"", data)

            # only valid paths should get through
            if path != "":
                # ensure we are not after the current working report
                if path != self.__sniffer.get_working_capture_path():
                    filename = "%s/%s" % (self.__sniffer_captures_path, path)
                    if not os.path.isfile(filename):
                        response.append(base_response + '%s ackend\n' % ControlError.get(3004))
                    else:
                        capture_response, capture_len = self.__get_capture_file_data (filename)
                        response.append(base_response + ' data="%s" datalen="%s" %s ackend\n' % (capture_response, capture_len, ControlError.get(0)))
                else:
                    response.append(base_response + '%s ackend\n' % ControlError.get(3005))

            else:
                print "path?"
                response.append(base_response + ' %s ackend\n' % ControlError.get(3003))

        elif action == "net_scan_capture_delete":
            path = Utils.get_var("path=\"([^\"]+)\"", data)

            capture_file = self.__get_capture_file(path)

            if path == "*":
                logger.debug("Deleting all captures(s)")
                capture_files = self.__get_capture_file_list(self.__sniffer_captures_path)
                for f in capture_files:
                    pcap_file = self.__get_capture_file(f)
                    os.unlink(pcap_file)

                response.append(base_response + ' %s ackend\n' % ControlError.get(0))
            elif capture_file != "":
                logger.debug("Deleting report at: %s" % capture_file)
                os.unlink(capture_file)
                response.append(base_response + ' %s ackend\n' % ControlError.get(0))
            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(3004))

        # send back our response
        return response

    def __get_capture_file(self, filename):

        capture_file = self.__sniffer_captures_path + "/" + filename
        capture_files = self.__get_capture_file_list(self.__sniffer_captures_path)

        logger.debug("Checking sanity for report: %s" % capture_file)

        # check we have some files to work with
        if len(capture_files) > 0:
            if filename != "":
                if filename in capture_files:
                    return capture_file

        return ""
    def __get_capture_file_list(self, dir):
        logger.info("Looking for capture files... %s" % dir)
        filter = re.compile("netscan.*.pcap$")
        files = [f for f in os.listdir(dir) if filter.search(f)]
        files.sort()

        return files
    def __get_capture_file_data(self, filename):
        data = ''
        if os.path.isfile(filename):
            f = open(filename, "rb")
            data = f.read()
            data = zlib.compress(data)
        return hexlify(data), len(data)
    def shutdown(self):
        if self.__sniffer:
            self.__sniffer.stopWorking()
            time.sleep(6)
            self.__sniffer.join()

class SniffWork (threading.Thread):
    '''
        Sniff Thread to get a pcap file
    '''
    def __init__(self):
        '''
            device: interface to do the scan
            host_list: host_list to build the filter to scan
            net_list: net_list to build the filter to scan
            timeout: timeout to read (s)
            capture_file: capture filename
            promisc_mode: set the interface in promiscuous mode
        '''
        threading.Thread.__init__(self)

        self.__device = ''
        self.__host_list = []
        self.__net_list = []
        self.__status = SnifferStatus.STOPPED_OK
        self.__last_error = ''
        self.__pcapobj = pcap.pcapObject()
        self.__timeout = 0
        self.__setpromisc_mode = False
        self.__capture_file = ''
        self.__convertToPDML = False
        self.__data = ''
        self.__keep_working = True
        self.__capture_size = 0
        #For current status:
        self.__elapsedTime = 0
        self.__currentPackets = 0;
        self.__mustStop = False
        '''
            Set the capture file size.
        '''
    def set_capture_size(self, value):
        self.__capture_size = value


    def set_data_to_build_filter(self, data):
        '''
            Set the data used to build the scan filter.
        '''
        self.__data = data
    def set_device(self, value):
        '''
            Set the device to scan
        '''
        self.__device = value
    def set_convert_to_pdml(self, value):
        '''
            Set if we've to convert the pcap file to pdml file
        '''
        self.__convertToPDML = value
    def set_capture_file(self, value):
        '''
            Set capture filename
        '''
        self.__capture_file = value
    def set_promisc_mode(self, value):
        '''
            Set if we've to put the device on promiscous mode
            [NOT USED]
        '''
        self.__setpromisc_mode = value
    def set_timeout(self, value):
        '''
            Set capture timeout
        '''
        self.__timeout = value
    def stopWorking(self):
        '''
            Set keep_working flag to false to break the main loop
        '''
        self.__keep_working = False

    def stopScan(self):
        self.__mustStop = True
    def getdevices(self):
        return pcap.findalldevs()

    def __buildfilter(self):
        '''
            Build the filter for scan
        '''
        filter = ''
        raw_filter = Utils.get_var("raw_filter=\"([^\"]+[^\"])\"" , self.__data)
        if raw_filter:
          filter = raw_filter
          return filter
        without_and_operator = False
        reg_exp = "src_hosts=\"(?P<src_hosts>(\d+\.*\,*)+)\""
        tmppattern = re.compile(reg_exp)
        match_obj = tmppattern.search(self.__data)
        if match_obj:
            string_list = match_obj.group('src_hosts')
            tmplist = string_list.split(',')
            sizelist = len(tmplist)
            index = 0
            for host in tmplist:
                if index == sizelist - 1:
                    without_and_operator = False
                    filter += "src host %s " % host
                else:
                    filter += "src host %s or " % host
                    index += 1
        else:
            tmp = Utils.get_var("src_hosts=\"(ANY|)\"", self.__data)
            if tmp == "" or tmp == "ANY":
                without_and_operator = True


        reg_exp = "dst_hosts=\"(?P<dst_hosts>(\d+\.*\,*)+)\""
        tmppattern = re.compile(reg_exp)
        match_obj = tmppattern.search(self.__data)
        if match_obj:
            string_list = match_obj.group('dst_hosts')
            tmplist = string_list.split(',')
            sizelist = len(tmplist)
            index = 0
            if not without_and_operator:
                filter += ' and '
            for host in tmplist:
                if index == sizelist - 1:
                    without_and_operator = False
                    filter += "dst host %s " % host
                else:
                    filter += "dst host %s or " % host
                index += 1
        else:
            tmp = Utils.get_var("dst_hosts=\"(ANY|)\"", self.__data)
            if tmp == "" or tmp == "ANY":
                print "No DST Host"
                without_and_operator = True


        reg_exp = "src_nets=\"(?P<src_net_list>(\d+\.*\,*\/*)+)\""
        tmppattern = re.compile(reg_exp)
        match_obj = tmppattern.search(self.__data)
        if match_obj:
            string_list = match_obj.group('src_net_list')
            tmplist = string_list.split(',')
            sizelist = len(tmplist)
            index = 0
            if not without_and_operator:
                filter += ' and '
            for host in tmplist:
                if index == sizelist - 1:
                    without_and_operator = False
                    filter += "src net %s " % host
                else:
                    filter += "src net %s or " % host
                index += 1
        else:
            tmp = Utils.get_var("src_nets=\"(ANY|)\"", self.__data)
            if tmp == "" or tmp == "ANY":
                without_and_operator = True

        reg_exp = "dst_nets=\"(?P<dst_net_list>(\d+\.*\,*\/*)+)\""
        tmppattern = re.compile(reg_exp)
        match_obj = tmppattern.search(self.__data)
        if match_obj:
            string_list = match_obj.group('dst_net_list')
            tmplist = string_list.split(',')
            sizelist = len(tmplist)
            index = 0
            if not without_and_operator:
                filter += ' and '
            for host in tmplist:
                without_and_operator = False
                if index == sizelist - 1:
                    filter += "dst net %s " % host
                else:
                    filter += "dst net %s or " % host
                index += 1
        else:
            tmp = Utils.get_var("dst_nets=\"(ANY|)\"", self.__data)
            if tmp == "" or tmp == "ANY":
                without_and_operator = True

        return filter


    def getStatusString(self):
        packet_percentage = 0.0
        time_percentage = 0.0
        if self.__timeout > 0:
            time_percentage = ((float(self.__elapsedTime) * 100) / float(self.__timeout))
        if self.__capture_size > 0:
            packet_percentage = ((float(self.__currentPackets) * 100) / float(self.__capture_size))
        
        status = 'status="%d" packets="%d" total_packets="%d" packet_percentage="%.2f" elapsed_time="%.2f" total_time="%s" time_percentage="%.2f"' % \
        (self.__status, self.__currentPackets, self.__capture_size, packet_percentage, self.__elapsedTime, self.__timeout, time_percentage)
        return status
    def status(self):
        '''
            Returns the local status
        '''
        return self.__status

    def get_working_capture_path(self):
        '''
            Returns the name of the current scan file
        '''
        return self.__capture_file

    def get_error(self):
        '''
            Returns the las error
        '''
        return self.__last_error


    def __convertPCAP_To_PDML(self):
        '''
            Convert pcap file to pdml. This could be dangerous, because the size of output file 
            is around 15 times greather than input file.
        '''
        capture_file_pdml = self.__capture_file + '.pdml'
        logger.info("converting pcap file %s to pdml file %s" % (self.__capture_file, capture_file_pdml))
        cmd = '/usr/bin/tshark -r %s > %s -T pdml' % (self.__capture_file, capture_file_pdml)
        status, output = commands.getstatusoutput(cmd)
        logger.info("Conversion results: status:%s output:%s" % (status, output))

    def run_scan(self):
        '''
            Change local status to begin the job
        '''
        self.__status = SnifferStatus.WORKING
        self.__mustStop = False

    def run(self):
        '''
            Wait until status change and then run the scan.
        '''
        while self.__keep_working:
            while self.__status <= 0:
                time.sleep(5)
            logger.info("Executing Sniffer worker thread, saving pcap file on %s" % self.__capture_file)
            start_time = time.time()
            try:
                filter = self.__buildfilter()
                self.__status = 1
                arguments = """
                    Arguments: 
                        Device: %s
                        MAX_CAPTURE_LEN:%d
                        PROMISC_MODE: %s
                        TIMEOUT:%d s
                        CAPTURE_SIZE : %d packets
                        FILTER: %s
                """ % (self.__device, MAX_CAPTURE_LEN, self.__setpromisc_mode, self.__timeout, self.__capture_size, filter)

                logger.info(arguments)
                self.__pcapobj.open_live(self.__device, 10000, self.__setpromisc_mode, self.__timeout * 1000)
                self.__pcapobj.setfilter(filter, 0, 0)
                self.__pcapobj.setnonblock(1)
                self.__pcapobj.dump_open(self.__capture_file)

                self.__currentPackets = 0
                start_time = time.time()
                self.__elapsedTime = 0
                logger.info("Running scan...")
                while (self.__elapsedTime < self.__timeout) and self.__keep_working and not self.__mustStop:
                    self.__status = SnifferStatus.RUNNING_SCAN
                    self.__currentPackets += self.__pcapobj.dispatch(0, None)
                    self.__elapsedTime = time.time() - start_time
                    if self.__capture_size > 0 and self.__currentPackets >= self.__capture_size:
                        self.__elapsedTime = self.__elapsedTime + self.__timeout
                logger.info("We've readed :%d packets, capture file:%s in %d seconds" % (self.__currentPackets, self.__capture_file, self.__elapsedTime))
                if self.__convertToPDML:
                    self.__status = SnifferStatus.CONVERTING_PCAP_TO_PDML
                    self.__convertPCAP_To_PDML()
                self.__status = SnifferStatus.STOPPED_OK
                self.__mustStop = False
                self.__capture_size = 0
                self.__currentPackets = 0
                self.__elapsedTime = 0
                self.__timeout = 0
                self.__capture_file = ''
                self.__data = ''
            except Exception, e:
                print "Exception: %s" % Exception
                logger.error("Excpetion capturing data:%s" % str(e))
                self.__last_error = str(e)
                self.__status = SnifferStatus.STOPPED_ERROR


'''
    The code below was written for testing purposes
'''

if __name__ == '__main__':
#    host_list = []
#    host_list.append('192.168.2.19')
#    host_list.append('192.168.2.130')
#    net_list = []
#    net_list.append('192.168.2.0/24')
#    mysniffer = SniffWork('eth0', host_list, net_list, 60, '/mnt/devel_unstable/tmp/capture22.pcap', False, True)
#    mysniffer.start()
    manager = SnifferManager()
    try:

        #TEST SCAN COMMAND
    #    command = 'control action="net_scan" scan_name="net_scan_user_pepito.pcap" eth="eth0" src_hosts="192.168.2.19,192.168.2.130" dst_hosts="192.168.2.19,192.168.2.130" src_nets="192.168.2.0/24" dst_nets="192.168.2.0/24" id="pepito" timeout="60" transaction="1234"'
    #    command = 'control action="net_scan" scan_name="net_scan_user_pepito.pcap" eth="eth0" src_hosts="ANY" dst_hosts="192.168.3.19,192.168.3.130" src_nets="192.168.3.0/24" dst_nets="192.168.3.0/24" id="pepito" timeout="60" transaction="1234"'
    #    command = 'control action="net_scan" scan_name="net_scan_user_pepito.pcap" eth="eth0" src_hosts="192.168.2.19,192.168.2.130" dst_hosts="ANY" src_nets="192.168.2.0/24" dst_nets="ANY" id="pepito" timeout="60" transaction="1234"'
    #    command = 'control action="net_scan" scan_name="net_scan_user_pepito.pcap" eth="eth0" src_hosts="192.168.2.19,192.168.2.130" dst_hosts="192.168.2.19,192.168.2.130" src_nets="ANY" dst_nets="192.168.2.0/24" id="pepito" timeout="60" transaction="1234"'
    #    command = 'control action="net_scan" scan_name="net_scan_user_pepito.pcap" eth="eth0" src_hosts="ANY" dst_hosts="ANY" src_nets="ANY" dst_nets="ANY" id="pepito" timeout="60" transaction="1234"'
        command = 'control action="net_scan" scan_name="net_scan_user_pepito2.pcap" eth="lo" cap_size="20" raw_filter="src port 40001" timeout="60" transaction="1234"'
        base_response = 'control net_scan transaction="1234" id="test_frmk"'
        res = manager.process(command, base_response)
        print "Manager response: %s" % res
    #
    #    #TEST STATUS COMMAND
        time.sleep(15)
        command = 'action="net_scan_status" transaction="1234"'
        base_response = 'control net_scan_status transaction="1234" id="test_frmk"'
        res = manager.process(command, base_response)
        print "Manager response: %s" % res
        time.sleep(60)
    #
    #    #TEST GET CAPTURE LIST COMMAND
    #    command = 'action="net_scan_capture_list" transaction="1234"'
    #    base_response = 'control net_scan_capture_list transaction="1234" id="test_frmk"'
    #    res = manager.process(command, base_response)
    #    print "Manager response: %s" % res
    #
    #    #TEST GET COMMAND
    #    command = 'action="net_scan_capture_get" path="net_scan_eth0_20110428182700.pcap" transaction="1234"'
    #    base_response = 'control net_scan_capture_get transaction="1234" id="test_frmk"'
    #    res = manager.process(command, base_response)
    #    print "Manager response: %s" % res
    #
    #    #TEST DELETE COMMAND
    #    command = 'action="net_scan_capture_delete" path="net_scan_eth0_20110428182700.pcap" transaction="1234"'
    #    base_response = 'control net_scan_capture_delete transaction="1234" id="test_frmk"'
    #    res = manager.process(command, base_response)
    #    print "Manager response: %s" % res
    except KeyboardInterrupt:
        manager.shutdown()
    os.sys.exit(0)



