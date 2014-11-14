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



class VAScannerManager:

    __vascanner_bin_path = ""
    __vascanner_report_path = ""
    __vascanner = None

    def __init__(self, conf):
        if self.__vascanner == None:
            logger.info("Initialising VAScanner Manager.")

            # grab the vascanner binary path
            self.__vascanner_bin_path = "/usr/bin/openvas-client"

            if os.path.exists(self.__vascanner_bin_path):
                logger.info('VAScanner binary path: %s' % self.__vascanner_bin_path)
            else:
                logger.error('VAScanner binary path "%s" does not exist or has restricted privileges!' % self.__vascanner_bin_path)

            # grab the vascanner report path
            self.__vascanner_report_path = "/tmp"

            if os.path.exists(self.__vascanner_report_path):
                logger.info("VAScanner report path: %s" % self.__vascanner_report_path)
            else:
                logger.error('VAScanner report path "%s" does not exist or has restricted privileges!' % self.__vascanner_bin_path)

            self.__vascanner = DoVAScanner(self.__vascanner_bin_path, self.__vascanner_report_path)
            self.__vascanner.start()

            logger.debug("VAScanner Manager initialised.")


    def process(self, data, base_response):
        logger.debug("VAScanner Manager: Processing: %s" % data)
        
        response = []
        action = Utils.get_var("action=\"([A-Za-z_]+)\"", data)
           
        if action == "va_scan":
            target = Utils.get_vars("target=\"([\s0-9a-fA-F\.:/]+)\"" , data)
           
            if len(target):
                if self.__vascanner.status() > 0:
                    logger.info("Scan already in progress: %i" % self.__vascanner.status())
                    response.append(base_response + ' status="%d" %s ackend\n' % (self.__vascanner.status(), ControlError.get(2001)))
                else:
                    # set the scan target and start the scan
                    self.__vascanner.set_scan_target(target)
                    self.__vascanner.scan_start()

                    response.append(base_response + ' status="%d" %s ackend\n' % (self.__vascanner.status(), ControlError.get(0)))

            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(2002))

        elif action == "va_status":
            if self.__vascanner.status() == -1:
                response.append(base_response + ' status="-1" error="%s" ackend\n' % (self.__vascanner.get_error()))

            else:
                response.append(base_response + ' status="%d" %s ackend\n' % (self.__vascanner.status(), ControlError.get(0)))

        elif action == "va_reset":
            self.__vascanner.reset_status()

            if self.__vascanner.status() == -1:
                logger.debug("Previous scan aborted raising errors, please check your logfile.")
                response.append(base_response + ' %s ackend\n' % ControlError.get(1, str(self.__vascanner.get_error())))
            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(0))

        elif action == "va_report_list":
            report_files = self.__get_report_file_list(self.__vascanner_report_path)
                   
            for p in report_files:
                base_response += ' report="%s"' % p

            response.append(base_response + ' count="%i" %s ackend\n' % (len(report_files), ControlError.get(0)))

        elif action == "va_report_get":
            path = Utils.get_var("path=\"([^\"]+)\"", data)

            # only valid paths should get through
            if path != "":
                # ensure we are not after the current working report
                if path != self.__vascanner.get_working_report_path():
                    report_response = self.__generate_report(path, base_response)
                    response.extend(report_response)
                    response.append(base_response + ' %s ackend\n' % ControlError.get(0))

                else:
                    response.append(base_response + '%s ackend\n' % ControlError.get(2005))

            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(2003))

        elif action == "va_report_raw_get":
            path = Utils.get_var("path=\"([^\"]+)\"", data)

            # only valid paths should get through
            if path != "":
                report_file = self.__get_report_file(path)
                report_response = ControlUtil.get_file(report_file, base_response)
                response.extend(report_response)
                response.append(base_response + ' %s ackend\n' % ControlError.get(0))

            else:
                response.append(base_response + ' %s ackend\n' % ControlError.get(2003))


        elif action == "va_report_delete":
            path = Utils.get_var("path=\"([^\"]+)\"", data)

            report_file = self.__get_report_file(path)

            if path == "*":
                logger.debug("Deleting all report(s)")
                report_files = self.__get_report_file_list(self.__vascanner_report_path)
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
        filter = re.compile("va\.(\d+)$")
        files = [f for f in os.listdir(dir) if filter.search(f)]
        files.sort()
       
        return files


    def __get_report_file(self, filename):

        report_file = self.__vascanner_report_path + "/" + filename
        report_files = self.__get_report_file_list(self.__vascanner_report_path)

        logger.debug("Checking sanity for report: %s" % report_file)

        # check we have some files to work with
        if len(report_files) > 0:
            if filename != "":
                if filename in report_files:
                    return report_file
                
        return ""


    def __generate_report(self, filename, base_response):
        report = []

        # support pings first
        report_file = self.__get_report_file(filename)
        if report_file != "":

            logger.debug("Generating report from: %s" % report_file)

            # read in the NBE report
            f = open(report_file, 'r')

            current_target = ""
            hosts = {}

            # loop through the lines
            for line in f:

                elements = line.rstrip("\n").split("|")

                try:
                    if elements[0] == "results" and len(elements) == 4:
                        if elements[2] != current_target:
                            current_target = elements[2]
                            hosts[current_target] = ""

                        if current_target != "":
                            hosts[current_target] += ' service="%s"' % elements[3]

                except:
                    logger.debug("Unexpected execption handled.")

            f.close()

            for k in hosts.keys():
                report.append(base_response + '%s ack\n' % hosts[k])

            # end the report transaction
            report.append(base_response + ' count="%d" ack\n' % len(hosts.keys()))

        return report

class DoVAScanner (threading.Thread):

    __vascanner_bin_path = ""
    __vascanner_report_path = ""
    __vascanner_target_path = "/tmp/va.targets"
    __scan_type = 0
    __target = []
    __vascanner_report_name = ""
    __vascanner_host = "127.0.0.1"
    __vascanner_port = "9390"
    __vascanner_user = "test"
    __vascanner_pass = "password"


    def __init__(self, vascanner_bin_path, vascanner_report_path):
        threading.Thread.__init__(self)

        self.__vascanner_bin_path = vascanner_bin_path
        self.__vascanner_report_path = vascanner_report_path
        self.__status = 0
        self.__last_error = None


    def get_working_report_path(self):
        return self.__vascanner_report_name


    def set_scan_target(self, target):
        logger.debug("VAScanner scan target: %s" % target)
        self.__target = target


    def status(self):
        return self.__status


    def scan_start(self):
        # set status to 1 to let the main thread get under way
        if not (self.__status > 0):
            self.__status = 1


    def reset_status(self):
        if self.__status != 0:
            cmd = "pkill -9 $(basename %s)" % self.__vascanner_bin_path
            logger.debug("Killing VAScanner via: %s" % cmd)

            os.system(cmd)


    def get_error(self):
        return self.__last_error


    def run(self):
        logger.debug("Executing VAScanner worker thread.")

        while True:
            # sleep on status
            while self.__status <= 0:
                time.sleep(5)

            self.__status = 5

            # set the output report path 
            timestamp = datetime.datetime.today().strftime("%Y%m%d%H%M00")
            self.__vascanner_report_name = "va.%s" % (timestamp)
            vascanner_report = "%s/%s" % (self.__vascanner_report_path, self.__vascanner_report_name)
            logger.info("VAScanner report path: %s" % vascanner_report)
  
            self.__status = 10

            # build the scan list
            target_file = open(self.__vascanner_target_path, "w")

            for ip in self.__target:
                target_file.write(ip + "\n");

            target_file.close()

            # configure the command
            cmd = "%s -q %s %s %s %s %s %s -x -T nbe" % (self.__vascanner_bin_path, self.__vascanner_host, self.__vascanner_port, self.__vascanner_user, self.__vascanner_pass, self.__vascanner_target_path, vascanner_report)
    
            self.__status = 50

            logger.debug("Executing VAScanner scan via: %s" % cmd)

            ret = os.system(cmd)

            if ret != 0 or not os.path.exists(vascanner_report):
                self.__status = -1;
                self.__last_error = "Scan failed (%s). Check output and try enabling debug." % str(ret)
                continue

            # start converting and calculating
            self.__status = 75

            logger.debug("VAScanner report created.")
   
            self.__status = 0
            self.__vascanner_report_name = ""

