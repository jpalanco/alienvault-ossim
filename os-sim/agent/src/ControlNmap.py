# -*- coding: utf-8 -*-
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
import datetime, os, re, threading, time
import subprocess, shlex
from xml.dom.minidom import parse

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



class NmapManager:

    __nmap_bin_path = ""
    __nmap_report_path = ""
    __nmap = None

    def __init__(self, conf):
        if self.__nmap == None:
            logger.info("Initialising Nmap Manager.")

            # grab the nmap binary path
            self.__nmap_bin_path = "/usr/bin/nmap"

            if os.path.exists(self.__nmap_bin_path):
                logger.info('Nmap binary path: %s' % self.__nmap_bin_path)

            else:
                logger.error('Nmap binary path "%s" does not exist or has restricted privileges!' % self.__nmap_bin_path)

            # grab the nmap report path
            self.__nmap_report_path = "/tmp"

            if os.path.exists(self.__nmap_report_path):
                logger.info("Nmap report path: %s" % self.__nmap_report_path)

            else:
                logger.error('Nmap report path "%s" does not exist or has restricted privileges!' % self.__nmap_bin_path)

            self.__nmap = DoNmap(self.__nmap_bin_path, self.__nmap_report_path)
            self.__nmap.start()

            logger.debug("Nmap Manager initialised.")


    def process(self, data, base_response):
        logger.debug("Nmap Manager: Processing: %s" % data)
        
        response = []
        action = Utils.get_var("action=\"([A-Za-z_]+)\"", data)
           
        if action == "nmap_scan":
            target = Utils.get_var("target=\"([\s0-9a-fA-F\.:/\-]+)\"" , data)
            scan_type = Utils.get_var("type=\"(ping|0|fast|1|normal|2|full|3|custom|4)\"" , data)
            scan_timming = Utils.get_var("timming=\"(T0|T1|T2|T3|T4|T5)\"" , data)
            autodect = Utils.get_var("autodetect=\"(enable|disable|enabled|disabled)\"" , data)
            scan_ports = Utils.get_var("scan_ports=\"([0-9\-\,]+)\"" , data)
            rdns = Utils.get_var("rdns=\"(enable|disable|enabled|disabled)\"" , data)
            report_prefix = Utils.get_var("report_prefix=\"([\s0-9a-fA-F\.:/\-]+)\"" , data)
            if autodect == "":
                autodect = "enable"
            if rdns == "":
                rdns = "disable"
            if scan_timming == "":
                scan_timming = "T3"
            # set the scan type as appropriate
            if scan_type == "":
                scan_type = "ping"
            if report_prefix == "":
                response.append(base_response + ' status="%d" %s ackend\n' % (self.__nmap.status(), ControlError.get(2007)))
                return response
            self.__nmap.set_report_prefix(report_prefix)
            self.__nmap.set_scan_type(scan_type)
            self.__nmap.set_scan_timming(scan_timming)
            self.__nmap.set_scan_autodetect(autodect)
            self.__nmap.set_scan_ports(scan_ports)#only if custom
            self.__nmap.set_scan_rdsn(rdns)
            if scan_type == "custom" and scan_ports=="":
                response.append(base_response + ' status="%d" %s ackend\n' % (self.__nmap.status(), ControlError.get(2006)))
                return response
                
            

            if len(target):
                if self.__nmap.status() > 0:
                    logger.info("Scan already in progress: %i" % self.__nmap.status())
                    response.append(base_response + ' status="%d" %s ackend\n' % (self.__nmap.status(), ControlError.get(2001)))

                else:
                    # set the scan target and start the scan
                    self.__nmap.set_scan_target(target)
                    self.__nmap.scan_start()

                    response.append(base_response + ' status="%d" %s ackend\n' % (self.__nmap.status(), ControlError.get(0)))

            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(2002))

        elif action == "nmap_status":
            if self.__nmap.status() == -1:
                response.append(base_response + ' status="-1" error="%s" ackend\n' % (self.__nmap.get_error()))

            else:
                response.append(base_response + ' status="%d" %s ackend\n' % (self.__nmap.status(), ControlError.get(0)))

        elif action == "nmap_reset":
            self.__nmap.reset_status()

            if self.__nmap.status() == -1:
                logger.debug("Previous scan aborted raising errors, please check your logfile.")
                response.append(base_response + ' %s ackend\n' % ControlError.get(1, str(self.__nmap.get_error())))

            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(0))

        elif action == "nmap_report_list":
            report_files = self.__get_report_file_list(self.__nmap_report_path)
                   
            for p in report_files:
                base_response += ' report="%s"' % p

            response.append(base_response + ' count="%i" %s ackend\n' % (len(report_files), ControlError.get(0)))

        elif action == "nmap_report_get":
            path = Utils.get_var("path=\"([^\"]+)\"", data)
            deletestr = Utils.get_var("delete=\"(yes|no|0|1|true\false)\"", data)
            deletestr = deletestr.lower()
            delete = False
            if deletestr in ['yes','1','true']:
                delete = True
            # only valid paths should get through
            if path != "":
                # ensure we are not after the current working report
                if path != self.__nmap.get_working_report_path():
                    report_response = self.__generate_report(path, base_response)
                    response.extend(report_response)
                    response.append(base_response + ' %s ackend\n' % ControlError.get(0))
                    if delete:
                        self.__deleteReport(path)
                else:
                    response.append(base_response + '%s ackend\n' % ControlError.get(2005))

            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(2003))

        elif action == "nmap_report_raw_get":
            path = Utils.get_var("path=\"([^\"]+)\"", data)
            deletestr = Utils.get_var("delete=\"(yes|no|0|1|true\false)\"", data)
            deletestr = deletestr.lower()
            delete = False
            if deletestr in ['yes','1','true']:
                delete = True
            # only valid paths should get through
            if path != "":
                report_file = self.__get_report_file(path)
                report_response = ControlUtil.get_file(report_file, base_response)
                response.extend(report_response)
                response.append(base_response + ' %s ackend\n' % ControlError.get(0))
                if delete:
                    self.__deleteReport(path)
            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(2003))


        elif action == "nmap_report_delete":
            path = Utils.get_var("path=\"([^\"]+)\"", data)

            report_file = self.__get_report_file(path)

            if path == "*":
                logger.debug("Deleting all report(s)")
                report_files = self.__get_report_file_list(self.__nmap_report_path)
                for f in report_files:
                    report_file = self.__get_report_file(f)
                    os.unlink(report_file)

                response.append(base_response + ' %s ackend\n' % ControlError.get(0))
            elif report_file != "":
                logger.debug("Deleting report at: %s" % report_file)
                os.unlink(report_file)
                response.append(base_response + ' %s ackend\n' % ControlError.get(0))
            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(2004))

        # send back our response
        return response


    def __get_report_file_list(self, dir):
        filter = re.compile("scan\.(\d+)$")
        files = [f for f in os.listdir(dir) if filter.search(f)]
        files.sort()
       
        return files


    def __get_report_file(self, filename):

        report_file = self.__nmap_report_path + "/" + filename
        report_files = self.__get_report_file_list(self.__nmap_report_path)

        logger.debug("Checking sanity for report: %s" % report_file)

        # check we have some files to work with
        if len(report_files) > 0:
            if filename != "":
                if filename in report_files:
                    return report_file
                
        return ""

    def __deleteReport(self,path):
        if os.path.exists(path):
            os.remove(path)

    def __generate_report(self, filename, base_response):
        report = []

        # support pings first
        report_file = self.__get_report_file(filename)
        if report_file != "":


            logger.debug("Generating report from: %s" % report_file)

            # read in the XML report
            xml = parse(report_file)

            # search through all available host nodes
            hosts = xml.getElementsByTagName("host")
            logger.debug("Hosts: %d" % len(hosts))

            # list of active hosts (ie up)
            active_hosts = 0

            for host in hosts:
                host_return = ""
                host_is_active = False

                # loop through any availale information
                for node in host.childNodes:
                    if node.nodeName == "status":
                        host_is_active = (node.attributes["state"].value == "up")

                    if node.nodeName == "address":
                        if node.attributes["addrtype"].value == "ipv4":
                            host_return += ' ip="%s"' % (node.attributes["addr"].value)

                        if node.attributes["addrtype"].value == "ipv6":
                            host_return += ' ip="%s"' % (node.attributes["addr"].value)

                        elif node.attributes["addrtype"].value == "mac":
                            host_return += ' mac="%s"' % (node.attributes["addr"].value)

                            if "vendor" in node.attributes.keys():
                                host_return += ' vendor="%s"' % (node.attributes["vendor"].value)
                            else:
                                host_return += ' vendor="unknown"'

                    elif node.nodeName == "ports":
                        for port in node.childNodes:
                            if port.nodeName == "port":
                                host_return += ' port="%s|%s"' % (port.attributes["portid"].value, port.attributes["protocol"].value)

                    elif node.nodeName == "os":
                        for n in node.childNodes:
                            if n.nodeName == "osmatch":
                                host_return += ' os="%s" os_accuracy="%s"' % (n.attributes["name"].value, n.attributes["accuracy"].value)
                            elif n.nodeName == "osclass":#New nmap version
                                osname = ""
                                if n.attributes.has_key('osfamily'):
                                    osname = "%s" % n.attributes['osfamily'].value
                                if n.attributes.has_key('osgen'):
                                    osname += " %s" % n.attributes['osgen'].value
                                host_return += ' os="%s" os_accuracy="%s"' % (osname, n.attributes["accuracy"].value)
                                

                # only interested in active hosts
                if host_is_active:
                    report.append(base_response + '%s ack\n' % host_return)
                    active_hosts += 1
                    logger.debug("Found active host")
                else:
                    logger.debug("Skipping inactive host")

            # end the report transaction
            report.append(base_response + ' count="%d" ack\n' % active_hosts)

            # clear the node(s)
            xml.unlink()

        return report

class DoNmap (threading.Thread):

    __nmap_bin_path = ""
    __nmap_report_path = ""
    __scan_type = 0
    __target = ""
    __nmap_report_name = ""


    def __init__(self, nmap_bin_path, nmap_report_path):
        threading.Thread.__init__(self)

        self.__nmap_bin_path = nmap_bin_path
        self.__nmap_report_path = nmap_report_path
        self.__status = 0
        self.__report_prefix=""
        self.__last_error = None


    def get_working_report_path(self):
        return self.__nmap_report_name


    def set_scan_timming(self,scan_timming):
        self.__scan_timming = scan_timming


    def set_scan_autodetect(self,autodect):
        if autodect == "enable" or autodect == "enabled":
            self.__autodect = "-A"
        else:
            self.__autodect = ""


    def set_scan_rdsn(self,rdns):
        if rdns == "enable":
            self.__rdns = " --system-dns"
        else:
            self.__rdns = " -n"


    def set_scan_ports(self,scan_ports):
        self.__scan_ports = scan_ports


    def set_report_prefix (self,rp):
        self.__report_prefix=rp


    def set_scan_type(self, scan_type):
        if scan_type == 0 or scan_type == "ping":
            logger.debug("Nmap scan type: ping")
            self.__scan_type = "ping"

        elif scan_type == 1 or scan_type == "fast":
            self.__scan_type = "fast"

        elif scan_type == 2 or scan_type == "normal":
            self.__scan_type = "normal"

        elif scan_type == 3 or scan_type == "full":
            self.__scan_type = "full"

        elif scan_type == 4 or scan_type == "custom":
            self.__scan_type = "custom"

        else:
            logger.debug("Unknown scan type. Setting default nmap scan type: ping")
            self.__scan_type = "ping"


    def set_scan_target(self, target):
        logger.debug("Nmap scan target: %s" % target)
        self.__target = target


    def status(self):
        return self.__status


    def scan_start(self):
        # set status to 1 to let the main thread get under way
        if not (self.__status > 0):
            self.__status = 1
            self.__last_error=""


    def reset_status(self):
        if self.__status != 0:
            cmd = "pkill -9 $(basename %s)" % self.__nmap_bin_path
            logger.debug("Killing Nmap via: %s" % cmd)

            os.system(cmd)


    def get_error(self):
        return self.__last_error


    def run(self):
        logger.debug("Executing Nmap worker thread.")

        while True:
            # sleep on status
            while self.__status <= 0:
                time.sleep(5)

            self.__status = 5

            # set the output report path 
            timestamp = datetime.datetime.today().strftime("%Y%m%d%H%M00")
            self.__nmap_report_name = "%s_nmap_%s_scan.%s" % (self.__report_prefix,self.__scan_type, timestamp)
            nmap_report = "%s/%s" % (self.__nmap_report_path, self.__nmap_report_name)
            logger.info("Nmap report path: %s" % nmap_report)
    
            # configure the command
            cmd = "%s %s %s -%s" % (self.__nmap_bin_path,self.__rdns, self.__autodect, self.__scan_timming)
            cmd_end = "%s -oX - --no-stylesheet " % (self.__target)
            if self.__scan_type == "ping":
                #Do not use -A option. 
                cmd = "%s %s -%s" % (self.__nmap_bin_path,self.__rdns,self.__scan_timming)
                #cmd = "%s -sP -n %s -oX %s --no-stylesheet" % (self.__nmap_bin_path, self.__target, nmap_report)
                cmd += " -sn -PE "
            elif self.__scan_type == "fast":
                #cmd = "%s -F -A -sS -n %s -oX %s --no-stylesheet" % (self.__nmap_bin_path, self.__target, nmap_report)
                cmd += " -sS -F "
            elif self.__scan_type == "full":
                #cmd = "%s -A -sS -n %s -oX %s --no-stylesheet" % (self.__nmap_bin_path, self.__target, nmap_report)
                cmd += " -sS -p1-65535  "#all ports 
            elif self.__scan_type == "normal":
                cmd += " -sS "
            elif self.__scan_type == "custom":
                cmd += " -sS -p %s " % self.__scan_ports
            else:
                self.__status = -1
                self.__last_error = "Invalid scan type. Check output and try enabling debug."
                continue
            cmd += cmd_end
            self.__status = 33
            logger.debug("Executing Nmap scan via: %s" % cmd)
            dest = os.open(nmap_report, os.O_WRONLY|os.O_CREAT, int("0600", 8))
            args = shlex.split(cmd)
            p = subprocess.Popen(args, stdout=dest)
            ret = p.wait()
            os.close(dest)
    
            # start converting and calculating
            self.__status = 66

            if ret != 0 or not os.path.exists(nmap_report):
                self.__status = -1
                self.__last_error = "Scan failed (%s). Check output and try enabling debug." % str(ret)
                continue

            logger.debug("Nmap report created.")
   
            self.__status = 0
            self.__nmap_report_name = ""
            self.__report_prefix = ""

