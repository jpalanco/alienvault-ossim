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
# Some maintenance tasks for the Nagios config related to HOST SERVICES
#

#
# GLOBAL IMPORTS
#
import os, re, subprocess, threading, time

#
# LOCAL IMPORTS
#
from DBConstantNames import *
from Logger import Logger
from NagiosMisc import nagios_host, nagios_host_service, nagios_host_group_service, nagios_host_group
from OssimDB import OssimDB
from OssimConf import OssimConf
import Util

#
# GLOBAL VARIABLES
#
logger = Logger.logger
looped = 0



class NagiosManager:

    def __init__(self, conf):
        logger.debug("Initialising Nagios Manager.")
        self.__nagios = None
        self.__conf = conf

    def process(self, message):
        logger.info("Nagios Manager: Processing: %s" % message)

        response = ""
        action = Util.get_var("action=\"([a-z]+)\"", message)

        if action in ["add","del","restart","reload"]:
            if self.__nagios == None:
                self.__nagios = DoNagios()

            self.__nagios.make_nagios_changes()
            self.__nagios.reload_nagios()
        else:
            logger.warning("Unknown action: %s" % action)
        # send back our response
        return response

class HostService(object):
    """Service active for a host.
    """
    def __init__(self,port,protocol,service):
        """Default constructor
        @param port The port service
        @param protocol The protocol service
        @param service  The service type
        """
        self.__port = port
        self.__protocol = protocol
        self.__service = service


    def getServicePort(self):
        """Returns service port"""
        return self.__port


    def getServiceProtocol(self):
        """Returns service protocol"""
        return self.__protocol


    def getServiceServiceType(self):
        """Returns service type"""
        return self.__service
    

class ActiveHost(object):
    """Abstraction for a host with nagios services checks
    """
    def __init__(self,host_id,hostname):
        self.__host_id = host_id
        self.__hostname = hostname
        self.__hostIPServices = {} #hash table ip=serivcelist
        self.__nips = 0

    def appendHostIP(self,ip):
        """Adds a new ip for the host
        @param ip the new hostip
        """
        if not self.__hostIPServices.has_key(ip):
            self.__hostIPServices[ip]=[] #empty services list
            self.__nips +=1


    def appendServiceToIP(self,ip,service):
        """Adds a new service associated with the ip passed as 
        parameter. If the host doesn't have the ip  associated, then 
        it adds the new ip to the host.
        @param ip the host ip to associate the service
        @param service the new service
        """
        if not self.__hostIPServices.has_key(ip):
            self.__hostIPServices[ip] = []
            self.__nips+=1
        self.__hostIPServices[ip].append(service)


    def getServicesByIP(self, ip):
        """Returns the services associated with the ip passed as param.
        @param ip The ip to look for services
        @returns the services list.
        """
        services = []
        if self.__hostIPServices.has_key(ip):
            return self.__hostIPServices[ip]
        return services


    def getIPList(self):
        """Returns the list of IP addresses associated with this host.
        @returns IP addreess list.
        """
        return self.__hostIPServices.keys()


    def getHostname(self):
        """Returns the hostname
        """
        return self.__hostname
    def hasMoreThanOneIP(self):
        if self.__nips > 1:
            return True
        return False

class DoNagios(threading.Thread):
    _interval = 600                 # intervals

    def __test_create_dir(self, path):
        """Checks if a directory exists and if it not exists, creates it
        """
        if not os.path.exists(path):
            os.makedirs(path)


    def __init__(self):
        """Default constructor.
        """
        self._tmp_conf = OssimConf ()
        threading.Thread.__init__(self)
        self.__active_hosts = {} #key = ip value=hostname
        self.__test_create_dir(self._tmp_conf[VAR_NAGIOS_CFG])
        self.__test_create_dir(os.path.join(self._tmp_conf[VAR_NAGIOS_CFG], "hosts"))
        self.__test_create_dir(os.path.join(self._tmp_conf[VAR_NAGIOS_CFG], "host-services"))
        self.__test_create_dir(os.path.join(self._tmp_conf[VAR_NAGIOS_CFG], "hostgroups"))
        self.__test_create_dir(os.path.join(self._tmp_conf[VAR_NAGIOS_CFG], "hostgroup-services"))
        self.__dbConnection = OssimDB(self._tmp_conf[VAR_DB_HOST],
                                      self._tmp_conf[VAR_DB_SCHEMA],
                                      self._tmp_conf[VAR_DB_USER],
                                      self._tmp_conf[VAR_DB_PASSWORD])
        self.__dbConnected = False

    def run(self):
        """Thread entry point.
        """
        global looped

        if looped == 0:
            self.loop()
            looped = 1

        else:
            logger.debug("Ignoring additional instance.")


    def loop(self):
        """Loop forever checking if there are changes to do...
        """
        while True:
            logger.debug("Looking for new services to add")
            self.make_nagios_changes()

            # sleep until the next round
            logger.debug("Sleeping until the next round in %ss" % self._interval)
            time.sleep(self._interval)

    def load_active_hosts(self):
        """ Loads those host that has nagios active and almost one service active
        """
        #query = "select hex(h.id) as id, inet6_ntop(hss.host_ip) as hostip, \
        #h.hostname from host h, host_scan hs,host_services hss where \
        #h.id = hss.host_id and h.id=hs.host_id and hss.nagios=1 \
        #and (hss.protocol =1 or hss.protocol=0 or hss.protocol=6 \
        #or hss.protocol=17) and hs.plugin_id=2007 group by hss.host_ip;"
        query="select hex(h.id) as id, inet6_ntop(hss.host_ip) as hostip,\
        h.hostname from host h LEFT JOIN host_services hss ON h.id = hss.host_id,\
        host_scan hs where  h.id=hs.host_id and hss.nagios=1 and \
        (hss.protocol =1 or hss.protocol=0 or hss.protocol=6 \
        or hss.protocol=17) and hs.plugin_id=2007 group by hss.host_ip \
        UNION select hex(h.id) as id, inet6_ntop(hip.ip) as hostip, \
        h.hostname from host h, host_scan hs, host_ip as hip where\
        h.id=hip.host_id and h.id=hs.host_id and hs.plugin_id=2007;"

        logger.info("Loading nagios active hosts")
        self.__active_hosts.clear()
        if not self.__dbConnected:
            self.__dbConnection.connect()
        data = self.__dbConnection.exec_query(query)
        print data
        self.__active_hosts.clear()
        for host in data:
            hostip = host['hostip']
            hostid = host['id']
            hostname = host['hostname']
            if self.__active_hosts.has_key(hostid):
                self.__active_hosts[hostid].appendHostIP(hostip)
            else:
                self.__active_hosts[hostid] = ActiveHost(hostid,hostname)
                self.__active_hosts[hostid].appendHostIP(hostip)
            #Loads the services associated with that ip
            query = 'select inet6_ntop(hss.host_ip) as ip, h.hostname as hostname,\
            h.id as id, hss.port as port, hss.protocol as protocol,\
            hss.service as service from host h, host_services hss \
            where hss.host_id=unhex("%s") and hss.host_ip=inet6_pton("%s")  \
            and (hss.protocol =1 or hss.protocol=0 or hss.protocol=6 or \
            hss.protocol=17) and nagios=1 and hss.host_id = h.id;' % (hostid,hostip)
            services = self.__dbConnection.exec_query(query)
            for service in services:
                s = HostService(service["port"],service["protocol"],service["service"])
                self.__active_hosts[hostid].appendServiceToIP(hostip,s)

#    def get_services_by_hosts(self, host_id,host_ip):
#        """Returns the service list for a specified host_id
#        @param host_id Host identifier in a standard uuid format.
#        """
#        query = 'select inet6_ntop(hss.host_ip) as ip, h.hostname as hostname,\
#        h.id as id, hss.port as port, hss.protocol as protocol,\
#        hss.service as service from host h, host_services hss \
#        where hss.host_id=unhex("%s") and hss.host_ip=inet6_pton("%s")  \
#        and (hss.protocol =1 or hss.protocol=0 or hss.protocol=6 or \
#        hss.protocol=17) and nagios=1 and hss.host_id = h.id;' % (host_id,host_ip)
#        if not self.__dbConnected:
#            self.__dbConnection.connect()
#        data = self.__dbConnection.exec_query(query)
#
#        return data
#

    def get_host_groups(self):
        """Returns the host groups list from the database
        returns a hostgroup list.
        """
        query = 'select hex(id) as id,name from host_group'
        if not self.__dbConnected:
            self.__dbConnection.connect()
        data = self.__dbConnection.exec_query(query)
        return data


    def get_hostlist_from_hg(self,hgid):
        """Returns the host list from a specified host group id
        @param hgid hostgroup id (in standard uuid mode)
        @returns host_list [{},{},...]
        """
        query="select hex(host_id) as host_id from host_group_reference where hex(host_group_id) = '%s'" % hgid
        if not self.__dbConnected:
            self.__dbConnection.connect()
        data = self.__dbConnection.exec_query(query)

        return data


    def make_nagios_changes(self):
        """Loads all the host/host_groups/net/net_groups and creates 
        all the nagios configuration.
        """
        logger.info("Making nagios changes..")

        pattern = re.compile("(?P<kk>^[\w\-]+)$")

        path = os.path.join(self._tmp_conf['nagios_cfgs'], "host-services")
        for fi in os.listdir(path):
            os.remove(os.path.join(path, fi))

        path = os.path.join(self._tmp_conf['nagios_cfgs'], "hostgroup-services")
        for fi in os.listdir(path):
            os.remove(os.path.join(path, fi))

        path = os.path.join(self._tmp_conf['nagios_cfgs'], "hostgroups")
        for fi in os.listdir(path):
            os.remove(os.path.join(path, fi))

        path = os.path.join(self._tmp_conf['nagios_cfgs'], "hosts")
        for fi in os.listdir(path):
            os.remove(os.path.join(path, fi))

        # 1 - Load the active hosts.
        self.load_active_hosts()
        services_by_host_dic = {}
        hostnames_dic = {}
        hostnames_dup_dic = {}

        # Looking for duplicate hostnames
        for host_id, host in self.__active_hosts.iteritems():
            hostname = host.getHostname()
            if (hostnames_dic.has_key(hostname)):
                hostnames_dup_dic[hostname] = True
            hostnames_dic[hostname] = True

        for host_id, host in self.__active_hosts.iteritems():
            hostname = host.getHostname()
            #host_ip = tupla[1]

            for ip in host.getIPList():
                realhostname = hostname

                if host.hasMoreThanOneIP() or hostnames_dup_dic.has_key(hostname):
                    realhostname = "%s_%s" % (hostname,ip)

                nh = nagios_host(ip,realhostname, "", self._tmp_conf)
                logger.info("nagios host: %s->%s" % (ip,realhostname))
                nh.write()
                for service in host.getServicesByIP(ip):
                    service_type = service.getServiceServiceType()
                    if not service_type:
                        continue
                    if not pattern.match(service_type):
                        logger.warning('Invalid service type: %s' % service_type)
                        continue
                    protocol = service.getServiceProtocol()
                    port = service.getServicePort()
                    if not services_by_host_dic.has_key(service_type):
                        services_by_host_dic[service_type] = []
                    if realhostname not in services_by_host_dic[service_type]:
                        services_by_host_dic[service_type].append(realhostname)
                    logger.info("nagios hostservice:  %s,%s,%s,%s" % (service_type,protocol,realhostname,port)) 
                    k = nagios_host_service(service_type,protocol,realhostname,port,"120", self._tmp_conf)
                    k.write()
        for key, value in services_by_host_dic.iteritems():
            if key=='unknown':
                continue
            members = ','.join(value)
            hg = nagios_host_group_service(key,key,members,self._tmp_conf)
            logger.info("nagios host group services %s,%s,%s"%(key,key,members))
            hg.write()
        data = self.get_host_groups()
        for hg in data:
            name = hg['name']   
            hostgroup_id = hg['id']
            data =self.get_hostlist_from_hg(hostgroup_id)
            host_list = []
            if len(data)>0:
                for host in data:
                    if self.__active_hosts.has_key(host['host_id']):
                        ac_host = self.__active_hosts[host['host_id']]
                        if ac_host.hasMoreThanOneIP():
                            for ip in ac_host.getIPList():
                                host_list.append(ac_host.getHostname()+"_%s" % ip)
                        else:
                            host_list.append(ac_host.getHostname())
                    else:
                        logger.info("host from hostgroup not in active hostsa :%s" %host['host_id'])
                logger.info("host from hg: %s" % host_list)
            if len(host_list)> 0:
                hosts_list_str = ','.join(host_list)
                logger.info("name:%s -  %s" % (name,hosts_list_str))
                nhg = nagios_host_group(name, name, hosts_list_str, self._tmp_conf)
                logger.info("nagios hostgroup :%s,%s,%s" % (name,name,hosts_list_str))
                nhg.write()
        logger.debug("Changes where applied! Reloading Nagios config.")
        # Change configuration file permissions.
        try:
            for root, dirs, files in os.walk(self._tmp_conf['nagios_cfgs']):
                for config in files:
                    config_file = root + "/" + config
                    os.chmod(config_file, 0644)
        except Exception as e:
            logger.error("An error occurred while changing the configuration files permissions: %s" % str(e))
        self.reload_nagios()



    def reload_nagios(self):
        """Reload the nagios process. 
        """
        # catch the process output for logging purposes
        process = subprocess.Popen(self._tmp_conf['nagios_reload_cmd'], stdout=subprocess.PIPE, stderr=subprocess.PIPE, shell=True)
        (pid, exit_status) = os.waitpid(process.pid, 0)
        output = process.stdout.read().strip() + process.stderr.read().strip()

        # show command output if return code indicates error
        if exit_status != 0:
            logger.error(output)


    def port_to_service(self, number):
        """Translates a port number into a port service.
        @param number The port number
        @returns The port service name if it exists, otherwise returns the port number.
        """
        f = open("/etc/services")
        #Actually we only look for tcp protocols here
        regexp_line = r'^(?P<serv_name>[^\s]+)\s+%d/tcp.*' % number
        try:
            service = re.compile(regexp_line)
            for line in f:
                serv = service.match(line)

                if serv != None:
                    return serv.groups()[0]

        finally:
            f.close()


    def serv_name(self, port):
        """Returns service name string
        @param port
        @returns the service string name.
        """
        return "%s_Servers" % (self.port_to_service(port)) 


    def serv_port(self, port):
        """Returns the port string name
        @param port port number
        @returns the port string 
        """
        return "port_%d_Servers" % port


if __name__ == "__main__":
    nagios = DoNagios()
    nagios.make_nagios_changes()
    nagios.reload_nagios()


