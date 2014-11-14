# -*- coding: utf-8 -*-
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
import string
import threading
import time
import uuid
import subprocess
import shutil
import random
#
# LOCAL IMPORTS
#
import ControlError
import ControlUtil
import Utils
from WinExec import WinExeC
from SambaClient import SambaClient
from Logger import Logger
#
# GLOBAL VARIABLES
#
logger = Logger.logger

OSSEC_AGENT_BINARY_FILE = "/usr/share/ossim/www/downloads/ossec-win32-agent_avmod_20130218085020.exe"
DOWNLOADS_FOLDER = "/usr/share/ossim/www/downloads"
OSSEC_AGENT_CONFIGTEMPLATE = "/etc/ossim/agent/ossecagentconfig.cfg"
SMB_WINEXEC_CONFIGURATION_FILE = "/etc/ossim/agent/smb.conf"
TEMPORAL_WORK_FOLDER = "/tmp"
OSSEC_SERVER_KEY_FILE = "/var/ossec/etc/client.keys"
NUMBER_MAX_REQUESTS = 200


class OssecDeployStatus:
    STOPPED_ERROR = -1
    FINISHED_OK  = 0
    INSTALLING_OSSEC_AGENT = 1
    CONFIGURING_OSSEC_AGENT = 2
    RESTARTING_SERVICES = 3
    WORKING = 4
    NOT_INITIALIZED = 5
    


class OssecDeployManager:


    def __init__(self):
        logger.info("Initializing Ossec Deploy Manager.")
        self.__deployWorks = {}


    def process(self, data, base_response):
        """Process all the ossec deployment requests
        control action="ossec-deploy" order="deploy" host="192.168.2.142" user="youruser" password="password" domain="domain" ossecserver="ossecserverip" 
        control action="ossec-deploy" order="status" workid="theworkid"
        control action="ossec-deploy" order="abort" workid="theworkid"
        control action="ossec-deploy" order="list" workid="theworkid"
        control action="ossec-deploy" order="purge" workid="theworkid"
        """
        logger.info("OssecDeployManager: Processing: %s" % data)
        response = []
        action = Utils.get_var("order=\"([A-Za-z_]+)\"", data)

        if action == "deploy":
            host = Utils.get_var("host=\"(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\"",data)
            if not host or host == "":
                response.append(base_response + ' %s ackend\n' % ControlError.get(5003))
                return response
            user = Utils.get_var("user=\"([^\"]+)\"",data)
            if not user or user == "":
                response.append(base_response + ' %s ackend\n' % ControlError.get(5004))
                return response
            passwd = Utils.get_var("password=\"([^\"]+)\"",data)
            if not passwd or passwd == "":
                response.append(base_response + ' %s ackend\n' % ControlError.get(5005))
                return response
            domain = Utils.get_var("domain=\"([^\"]+)\"",data)
            if not domain:
                domain= ""
            server = Utils.get_var("ossecserver=\"(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\"",data)
            if not server or server == "":
                response.append(base_response + ' %s ackend\n' % ControlError.get(5007))
                return response
            #we are ready to go
            # 1 - Generate a random uuid to use it as work id
            workid = str(uuid.uuid4())
            logger.info("Work ID: %s" % workid)
            work = OssecDeploy(workid,host, user, passwd, domain,server)
            work.start()
            self.__deployWorks[workid] = work
            response.append(base_response + ' workid="%s" %s ackend\n' % (workid,ControlError.get(0)))

        elif action == "status":
            workid = Utils.get_var("workid=\"([A-Za-z0-9_\-]+)\"",data)
            if not workid or workid is "":
                response.append(base_response + ' %s ackend\n' % ControlError.get(5001))
            elif not  self.__deployWorks.has_key(workid):
                response.append(base_response + ' %s ackend\n' % ControlError.get(5002))
            else:
                status = self.__deployWorks[workid].get_status_string()
                response.append(base_response + ' %s  %s ackend\n' % (status, ControlError.get(0)))
        elif action == "abort":
            workid = Utils.get_var("workid=\"([A-Za-z0-9_\-]+)\"",data)
            if not workid or workid is "":
                response.append(base_response + ' %s ackend\n' % ControlError.get(5001))
            elif not self.__deployWorks.has_key(workid):
                response.append(base_response + ' %s ackend\n' % ControlError.get(5002))
            else:
                self.__deployWorks[workid].join(1)
                del self.__deployWorks[workid]
                response.append(base_response + ' %s ackend\n' % (ControlError.get(0)))
        elif action == "list":
            worklist = ','.join(self.__deployWorks.keys())
            response.append(base_response + ' list="%s" %s ackend\n' % (worklist, ControlError.get(0)))
        elif action == "purge":
            logger.info("purge")
            for workid in self.__deployWorks.keys():
                st  = self.__deployWorks[workid].status() 
                if st == OssecDeployStatus.FINISHED_OK or st == OssecDeployStatus.STOPPED_ERROR:
                    del self.__deployWorks[workid]
            response.append(base_response + ' %s ackend\n' % (ControlError.get(0)))
        else:
            response.append(base_response + ' %s ackend\n' % ControlError.get(5000))
        
        # send back our response
        return response

    def shutdown(self):
        for workid, workthread in self.__deployWorks.iteritems():
            logger.info("Stopping work %s" % workid)
            try:
                workthread.join(1)
            except:
                logger.error("Error stopping the work:%s" % workid)


class OssecDeploy (threading.Thread):
    '''
       Ossec Deployment worker
    '''
    def __init__(self, workid, host, user, password, domain, ossec_server):
        '''
        Constructor:
        @param host: Host to connect to
        @param user: user to login in the remote host
        @param password: user password to login in the remote host
        @param domain: remote host domain
        @param ossec_server: Ossec server ip
        '''
        threading.Thread.__init__(self)
        self.__workerID = workid
        self.__stopEvent = threading.Event()
        self.__host = host
        self.__user = user
        self.__password = password
        self.__domain = domain
        self.__status = OssecDeployStatus.NOT_INITIALIZED
        self.__last_error = []
        self.__ossec_server = ossec_server
        self.__authfile = self.__build_authentication_file()
        print self.__authfile
    def __add_error(self, msg):
        """Add error message.
        """
        self.__last_error.append(msg)
        self.__status = OssecDeployStatus.STOPPED_ERROR

    def __get_ossec_agent_config(self):
        """Create a new ossec agent configuration file
        @returns The new ossec configuration file on sucess otherwise returns None
        """
        logger.info("Building a new ossec agent configuration file....")
        if not os.path.exists(OSSEC_AGENT_CONFIGTEMPLATE):
            self.__add_error("%s not exist! Can't create the ossec agent configuration" % OSSEC_AGENT_CONFIGTEMPLATE)
            return None
        #copy the file to a new one
        random_name = ''.join(random.choice(string.ascii_uppercase + string.digits) for x in range(10))
        newfilename = "%s/%s.cfg" % (TEMPORAL_WORK_FOLDER, random_name)
        template = open(OSSEC_AGENT_CONFIGTEMPLATE, 'r')
        newfile = open(newfilename, 'w')
        for line in template:
            newfile.write(line.replace('$$SERVER_IP$$', self.__ossec_server))
        newfile.close()
        os.chmod(newfilename, 0644)
        return newfilename


    def __build_authentication_file(self):
        """Returns the authentication filename"""
        authfilename = "/tmp/%s.keys" % (self.__host)
        try:
            f = open(authfilename, 'w')
            f.write('username=%s\n' % self.__user)
            f.write('password=%s\n' % self.__password)
            f.write('domain=%s\n' % self.__domain)
            f.close()
        except:
            logger.error('Error creating the authentication filename')
            return None
        return authfilename


    def stop(self):
        '''
            Set keep_working flag to false to break the main loop
        '''
        self.__stopEvent.set()

    def status(self):
        '''
            Returns the local status
        '''
        return self.__status

    def get_status_string(self):
        errormsg =','.join(self.__last_error)
        return "workid=\"%s\" status=\"%s\" errormsg=\"%s\"" % (self.__workerID,self.__status,errormsg)

    def get_error(self):
        '''
            Returns the las error
        '''
        return self.__last_error

    def install_agent(self):
        """Installs the ossec agent in the given remote system"""
        if not  self.__authfile:
            self.__add_error("Can't create the auth file")
            return False
        smb = SambaClient(self.__authfile, SMB_WINEXEC_CONFIGURATION_FILE, self.__host)
        wine = WinExeC(self.__authfile, SMB_WINEXEC_CONFIGURATION_FILE, self.__host)
        #systemunit = wine.get_working_unit()
        origdstfile = "%s" % (os.path.basename(OSSEC_AGENT_BINARY_FILE))
        logger.info("Saving the binary file")
        if not smb.put_file(DOWNLOADS_FOLDER, os.path.basename(OSSEC_AGENT_BINARY_FILE), "", origdstfile):
            self.__add_error("Error copying the file to the remote host")
            return False
        # installs
        logger.info("Running the ossec-agent setup....")
        systemunit = wine.get_working_unit()
        if not systemunit:
            self.__add_error("Can't retrieve the system unit from remote host. Maybe I can't connect to the remote host.")
            return False
        dstfile = "%s:/%s" % (systemunit.lower(), os.path.basename(OSSEC_AGENT_BINARY_FILE))
        if not wine.run_command('%s /S' % (dstfile)):
            logger.info("Running the ossec-agent setup.... FAIL")
            self.__add_error("Error installing the ossec agent..")
            return False
        time.sleep(2)
        if not smb.remove_file(origdstfile):
            logger.info("Error removing the ossec-agent from the remote host")
            self.__add_error("Error removing the ossec-agent from the remote host")
        return True


    def configure_agent(self):
        """Configure the remote agent
        """
        self.__status = OssecDeployStatus.CONFIGURING_OSSEC_AGENT
        wine = WinExeC(self.__authfile, SMB_WINEXEC_CONFIGURATION_FILE, self.__host)
        smb = SambaClient(self.__authfile, SMB_WINEXEC_CONFIGURATION_FILE, self.__host)
        configfile = self.__get_ossec_agent_config()
        if not configfile:
            self.__add_error("Ossec Agent invalid configuration file")
            return False
        program_files_folder = wine.get_environment_variable("ProgramFiles")
        program_files_folder = program_files_folder.split(':')
        
        dstdir = "%s\\ossec-agent" % program_files_folder[1]
        logger.info("Ossec-agent configuration... copying the configuration file...")
        
        if not smb.put_file(TEMPORAL_WORK_FOLDER, os.path.basename(configfile), dstdir, "ossec.conf"):
            self.__add_error("Error copying the configuration file to the remote host")
            return False
        if not os.path.isfile(OSSEC_SERVER_KEY_FILE):
            logger.info("ossec server key file not exists or its's accessible!")
            self.__add_error("ossec server key file not exists or its's accessible!")
            return False
        #get the keys
        keysfilename = "%s/ossecagent-%s.keys" % (TEMPORAL_WORK_FOLDER, self.__host)
        osseckeys = open(OSSEC_SERVER_KEY_FILE, 'r')
        newagentkeys = []
        for line in osseckeys:
            if self.__host in line:
                newagentkeys.append(line)
        osseckeys.close()
        if len(newagentkeys) <= 0:
            logger.error('No keys found for %s' % self.__host)
            self.__add_error('No keys found for %s' % self.__host)
            return False
        keyfile = open(keysfilename, 'w')
        for line in newagentkeys:
            keyfile.write(line)
        keyfile.close()
        
        dstkeyfile_folder = "%s\\ossec-agent" % (program_files_folder[1])
        logger.info("Ossec-agent configuration... copying the configuration file...")
        if not smb.put_file(TEMPORAL_WORK_FOLDER, os.path.basename(keysfilename), dstkeyfile_folder, "client.keys"):
            self.__add_error("Error copying the keys file to the remote host")
            return False
        os.remove(keysfilename)
        return True


    def restart_services(self):
        """Restart services
        """
        wine = WinExeC(self.__authfile, SMB_WINEXEC_CONFIGURATION_FILE, self.__host)
        program_files_folder = wine.get_environment_variable("ProgramFiles")
        program_files_folder = program_files_folder.split(':')
        dstdir = "%s\\ossec-agent" % program_files_folder[1]
        dstdir = dstdir.encode('string-escape')
        self.__status = OssecDeployStatus.RESTARTING_SERVICES
        
        if not  wine.run_command(command='cd  \\\"%s\\\"\ & service-stop' % dstdir, send_key=True, sleep_time=3):
            self.__add_error("Error stopping the ossec-agent")
            return False
        if not  wine.run_command(command='cd  \\\"%s\\\" & service-start' % dstdir, send_key=True, sleep_time=3):
            self.__add_error("Error starting the ossec-agent")
            return False
        return True


    def run(self):
        '''
            Wait until status change and then run the scan.
        '''
        print "starting.."
        try:
            self.__status = OssecDeployStatus.WORKING
            logger.info("[%s] Ossec-agent deploy started.." % self.__host)
            if not self.install_agent():
                logger.error("[%s] Ossec-agent deploy failed on installation step.." % self.__host)
                return
            logger.info("[%s] Ossec-agent deploy installation ok " % self.__host)
            time.sleep(2)
            if not self.configure_agent():
                print "configure...."
                logger.error("[%s] Ossec-agent deploy failed on configuration step.." % self.__host)
                return
            logger.info("[%s] Ossec-agent deploy configuration ok" % self.__host)
            time.sleep(2)
            if not self.restart_services():
                logger.error("[%s] Ossec-agent deploy failed on restarting step.." % self.__host)
                return
            logger.info("[%s] Ossec-agent deploy service restarted... ok" % self.__host)
            logger.info("[%s] Ossec-agent deploy finished ... ok" % self.__host)
            self.__status = OssecDeployStatus.FINISHED_OK
        except Exception, e:
            logger.error("Excpetion capturing data:%s" % str(e))
            self.__last_error = str(e)


'''
    The code below was written for testing purposes
'''

if __name__ == '__main__':
    dp = OssecDeploy("1", "192.168.2.108", "myuser", "mypass", "domain", "192.168.2.22")
    dp.start()
    
    while True:
        print dp.status()
        #print dp.get_error()
        time.sleep(1)
