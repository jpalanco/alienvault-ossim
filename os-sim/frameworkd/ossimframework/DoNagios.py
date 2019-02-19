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
# Some maintenance tasks for the Nagios config related to HOST SERVICES
#

#
# GLOBAL IMPORTS
#
import os
import re
import subprocess
import threading
import time

try:
    import redis
except ImportError:
    # In some profiles redis is not available
    redis = None

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

        if action in ["add", "del", "restart", "reload"]:
            if self.__nagios is None:
                self.__nagios = DoNagios()

            self.__nagios.make_nagios_changes()
        else:
            logger.warning("Unknown action: %s" % action)

        # send back our response
        return response


class HostService(object):
    """Service active for a host.
    """
    def __init__(self, port, protocol, service):
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
    def __init__(self, host_id, hostname):
        self.__host_id = host_id
        self.__hostname = hostname
        self.__hostIPServices = {}  # hash table ip=services_list
        self.__nips = 0

    def appendHostIP(self, ip):
        """Adds a new ip for the host
        @param ip the new hostip
        """
        if ip not in self.__hostIPServices:
            self.__hostIPServices[ip] = []  # empty services_list
            self.__nips += 1

    def appendServiceToIP(self, ip, service):
        """Adds a new service associated with the ip passed as 
        parameter. If the host doesn't have the ip  associated, then 
        it adds the new ip to the host.
        @param ip the host ip to associate the service
        @param service the new service
        """
        self.appendHostIP(ip)
        self.__hostIPServices[ip].append(service)

    def getServicesByIP(self, ip):
        """Returns the services associated with the ip passed as param.
        @param ip The ip to look for services
        @returns the services list.
        """
        return self.__hostIPServices.get(ip, [])

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
        return self.__nips > 1


class DoNagios(threading.Thread):
    _interval = 600  # intervals
    _cache_key = 'NAGIOS-activity_cache'

    @staticmethod
    def __test_create_dir(path):
        """Checks if a directory exists and if it not exists, creates it
        """
        if not os.path.exists(path):
            os.makedirs(path)

    def __init__(self):
        """Default constructor.
        """
        self._tmp_conf = OssimConf()
        threading.Thread.__init__(self)
        self.__active_hosts = {}  # key = ip, value = hostname
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

        # Set default value for Nagios activity diff and check it in cache.
        self.__prevhash = 0
        self.__get_nagios_activity_diff()

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

    #
    # Diff cache methods.
    #
    @staticmethod
    def get_redis_connection():
        redis_cache = None

        # redis might be not available here.
        if redis is not None:
            try:
                # connect to default RedisDB on localhost
                redis_cache = redis.Redis()
            except Exception as err_detail:
                logger.error('Failed to get redis connection: %s' % err_detail)

        return redis_cache

    @staticmethod
    def is_cache_available(redis_cache_obj):
        return redis_cache_obj and redis_cache_obj.ping()

    def __get_nagios_activity_diff(self):
        redis_cache = self.get_redis_connection()

        try:
            if self.is_cache_available(redis_cache):
                self.__prevhash = int(redis_cache.get(self._cache_key) or 0)
        except Exception as err_detail:
            logger.error('Failed to get Nagios activity diff from cache: %s' % err_detail)

        return self.__prevhash

    def __save_nagios_activity_diff(self, activity_diff):
        redis_cache = self.get_redis_connection()

        # Update local cache with a new diff
        self.__prevhash = activity_diff
        try:
            if self.is_cache_available(redis_cache):
                # Store new diff in redis
                redis_cache.set(self._cache_key, activity_diff)
        except Exception as err_detail:
            logger.error('Failed to cache Nagios activity diff: %s' % err_detail)
    #
    # End of redis cache methods.
    #

    def load_active_hosts(self):
        """ Loads those host that has agios active and almost one service active
        """
        query = ('SELECT hex(h.id) AS id, inet6_ntoa(hss.host_ip) AS hostip, h.hostname FROM host h '
                 'LEFT JOIN host_services hss ON h.id = hss.host_id, host_scan hs '
                 'WHERE h.id=hs.host_id AND hss.nagios=1 AND '
                 '(hss.protocol=1 OR hss.protocol=0 OR hss.protocol=6 OR hss.protocol=17) '
                 'AND hs.plugin_id=2007 GROUP BY hss.host_ip '
                 'UNION SELECT hex(h.id) AS id, inet6_ntoa(hip.ip) AS hostip, h.hostname '
                 'FROM host h, host_scan hs, host_ip AS hip '
                 'WHERE h.id=hip.host_id AND h.id=hs.host_id AND hs.plugin_id=2007;')

        logger.info("Loading nagios active hosts")
        self.__active_hosts.clear()
        if not self.__dbConnected:
            self.__dbConnection.connect()
        data = self.__dbConnection.exec_query(query)

        for host in data:
            hostip = host['hostip']
            hostid = host['id']
            hostname = host['hostname']

            if hostid in self.__active_hosts:
                self.__active_hosts[hostid].appendHostIP(hostip)
            else:
                self.__active_hosts[hostid] = ActiveHost(hostid, hostname)
                self.__active_hosts[hostid].appendHostIP(hostip)

            # Loads the services associated with that ip
            query = ('SELECT inet6_ntoa(hss.host_ip) AS ip, h.hostname AS hostname, h.id AS id, '
                     'hss.port AS port, hss.protocol AS protocol, hss.service AS service '
                     'FROM host h, host_services hss '
                     'WHERE hss.host_id=unhex(%s) AND hss.host_ip=inet6_aton(%s) '
                     'AND (hss.protocol=1 OR hss.protocol=0 OR hss.protocol=6 OR hss.protocol=17) '
                     'AND nagios=1 AND hss.host_id = h.id;')

            services = self.__dbConnection.exec_query(query, (hostid, hostip))
            for service in services:
                s = HostService(service['port'], service['protocol'], service['service'])
                self.__active_hosts[hostid].appendServiceToIP(hostip, s)

    def get_host_groups(self):
        """Returns the host groups list from the database
        returns a host_group list.
        """
        query = 'SELECT hex(id) AS id, name FROM host_group'
        if not self.__dbConnected:
            self.__dbConnection.connect()
        data = self.__dbConnection.exec_query(query)
        return data

    def get_hostlist_from_hg(self, hgid):
        """Returns the host list from a specified host group id
        @param hgid hostgroup id (in standard uuid mode)
        @returns host_list [{},{},...]
        """
        query = "SELECT hex(host_id) AS host_id FROM host_group_reference WHERE hex(host_group_id) = %s"
        if not self.__dbConnected:
            self.__dbConnection.connect()
        data = self.__dbConnection.exec_query(query, (hgid,))
        return data

    def make_nagios_changes(self):
        """Loads all the host/host_groups/net/net_groups and creates 
        all the nagios configuration.
        """
        logger.info("Making nagios changes..")

        # 1 - Load the active hosts.
        self.load_active_hosts()

        # Check if the return is the same as last time.
        # This will prevent unnecessary reloads and disk writes of configs
        # This *could* still run after the first change, but not subsequent ones without change
        current_hash = hash(str(sorted(self.__active_hosts)))

        if current_hash == self.__prevhash:
            logger.info("No Nagios Changes")
        else:
            # Store new activity diff.
            self.__save_nagios_activity_diff(current_hash)

            services_by_host_dic = {}
            hostnames_dic = {}
            hostnames_dup_dic = {}
            pattern = re.compile("(?P<kk>^\w[\w\-\s]+)$")

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

            # Looking for duplicate hostname
            for host_id, host in self.__active_hosts.iteritems():
                hostname = host.getHostname()
                if hostname in hostnames_dic:
                    hostnames_dup_dic[hostname] = True
                hostnames_dic[hostname] = True

            for host_id, host in self.__active_hosts.iteritems():
                hostname = host.getHostname()

                for ip in host.getIPList():
                    realhostname = hostname

                    if host.hasMoreThanOneIP() or hostname in hostnames_dup_dic:
                        realhostname = "%s_%s" % (hostname, ip)

                    nh = nagios_host(ip, realhostname, "", self._tmp_conf)
                    logger.info("nagios host: %s->%s" % (ip, realhostname))
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

                        if service_type not in services_by_host_dic:
                            services_by_host_dic[service_type] = []
                        if realhostname not in services_by_host_dic[service_type]:
                            services_by_host_dic[service_type].append(realhostname)
                        logger.info("nagios hostservice:  %s,%s,%s,%s" % (service_type, protocol, realhostname, port))
                        k = nagios_host_service(service_type, protocol, realhostname, port, "120", self._tmp_conf)
                        k.write()

            for key, value in services_by_host_dic.iteritems():
                if key == 'unknown':
                    continue
                members = ','.join(value)
                hg = nagios_host_group_service(key, key, members, self._tmp_conf)
                logger.info("nagios host group services %s,%s,%s" % (key, key, members))
                hg.write()

            data = self.get_host_groups()
            for hg in data:
                name = hg['name']
                hostgroup_id = hg['id']
                data = self.get_hostlist_from_hg(hostgroup_id)
                host_list = []

                if len(data) > 0:
                    for host in data:
                        host_id = host['host_id']
                        if host_id in self.__active_hosts:
                            ac_host = self.__active_hosts[host_id]
                            if ac_host.hasMoreThanOneIP():
                                for ip in ac_host.getIPList():
                                    host_list.append(ac_host.getHostname()+"_%s" % ip)
                            else:
                                host_list.append(ac_host.getHostname())
                        else:
                            logger.info("host from hostgroup not in active hosts :%s" % host_id)
                    logger.info("host from hg: %s" % host_list)

                if len(host_list) > 0:
                    hosts_list_str = ','.join(host_list)
                    logger.info("name:%s -  %s" % (name, hosts_list_str))
                    nhg = nagios_host_group(name, name, hosts_list_str, self._tmp_conf)
                    logger.info("nagios hostgroup :%s,%s,%s" % (name, name, hosts_list_str))
                    nhg.write()

            logger.debug("Changes were applied! Reloading Nagios config.")
            # Change configuration file permissions.
            try:
                for root, dirs, files in os.walk(self._tmp_conf['nagios_cfgs']):
                    for config in files:
                        config_file = root + "/" + config
                        os.chmod(config_file, 0644)
                    for cdir in dirs:
                        config_dir = root + "/" + cdir
                        os.chmod(config_dir, 0755)
                os.chmod(self._tmp_conf['nagios_cfgs'], 0755)
            except Exception as e:
                logger.error("An error occurred while changing the configuration files permissions: %s" % str(e))

            self.reload_nagios()

    def reload_nagios(self):
        """Reload the nagios process. 
        """
        # Add logging that we are really reloading nagios
        logger.info("Reloading Nagios Config.")

        # catch the process output for logging purposes
        process = subprocess.Popen(self._tmp_conf['nagios_reload_cmd'],
                                   stdout=subprocess.PIPE,
                                   stderr=subprocess.PIPE,
                                   shell=True)
        (pid, exit_status) = os.waitpid(process.pid, 0)
        output = process.stdout.read().strip() + process.stderr.read().strip()

        # show command output if return code indicates error
        if exit_status != 0:
            logger.error(output)

    @staticmethod
    def port_to_service(number):
        """Translates a port number into a port service.
        @param number The port number
        @returns The port service name if it exists, otherwise returns the port number.
        """
        f = open("/etc/services")
        # Actually we only look for tcp protocols here
        regexp_line = r'^(?P<serv_name>[^\s]+)\s+%d/tcp.*' % number
        try:
            service = re.compile(regexp_line)
            for line in f:
                serv = service.match(line)

                if serv is not None:
                    return serv.groups()[0]

        finally:
            f.close()

    def serv_name(self, port):
        """Returns service name string
        @param port
        @returns the service string name.
        """
        return "%s_Servers" % (self.port_to_service(port))

    @staticmethod
    def serv_port(port):
        """Returns the port string name
        @param port port number
        @returns the port string 
        """
        return "port_%d_Servers" % port

