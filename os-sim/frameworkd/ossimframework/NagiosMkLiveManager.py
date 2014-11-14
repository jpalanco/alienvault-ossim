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

import socket
import threading
import time
import os
import Util
from OssimDB import OssimDB
from OssimConf import OssimConf
from DBConstantNames import *
from Logger import Logger
logger = Logger.logger
_CONF = OssimConf()
_DB = OssimDB(_CONF[VAR_DB_HOST],
            _CONF[VAR_DB_SCHEMA],
            _CONF[VAR_DB_USER],
            _CONF[VAR_DB_PASSWORD])
_DB.connect()
'''

Plugin Return Code    Service State    Host State
                0        OK                UP
                1        WARNING           UP or DOWN/UNREACHABLE*
                2        CRITICAL          DOWN/UNREACHABLE
                3        UNKNOWN           DOWN/UNREACHABLE

'''

class HostInfo(object):
    """Host information retrieved from nagios mklive status.
    """
    def __init__(self):
        self.__hostid=""
        self.__hostip= []
        self.__hostName = ""
        self.__hostState = NagiosMkLiveManager.HOST_STATE_UNKNOWN
        self.__nservices = 0
        self.__nservicesWARNING = 0
        self.__nservicesCRITICAL = 0
        self.__hostServicesState = {}

    def addHostState(self,value):
        if value == NagiosMkLiveManager.HOST_STATE_UP:
            self.__hostState = value

    def setHostServicesState(self, value):
        self.__hostServicesState = value

    def getHostServicesState(self):
        return self.__hostServicesState
    
    def setHostId(self,value):
        """Returns the host id
        """
        self.__hostid = value

    def getHostId(self):
        """Returns the host id
        """
        return self.__hostid


    def getNServices(self):
        """Returns the number of services
        """
        return self.__nservices


    def setNServices(self,value):
        """Sets the number of services
        """
        self.__nservices = value


    def getNServicesWarning(self):
        """Returns the number of services in warning state.
        """
        return self.__nservicesWARNING


    def setNServicesWarning(self,value):
        """Sets the number of servicies in waring state
        """
        self.__nservicesWARNING = value


    def getNServicesCritical(self):
        """Returns the number of services in critical state
        """
        return self.__nservicesCRITICAL


    def setNServicesCritical(self,value):
        """Sets the number of services in critical state.

        """
        self.__nservicesCRITICAL = value

    def addServicesCritical(self,value):
        """Adds value services to the critical counter.
        @param value: Number of critical services to add.
        """
        self.__nservicesCRITICAL+=value


    def addServicesWarning(self,value):
        """Adds value services to the warning counter.
        @param value: Number of warning services to add.
        """
        self.__nservicesWARNING+=value


    def addNServices(self,value):
        """Adds value services to the total counter
        @param value: Number of services to add to the total counter.
        """
        self.__nservices+=value


    def addHostIp(self,value):
        """Adds an IP address to the host.
        """
        if value not in self.__hostip:
            self.__hostip.append(value)


    def getSeverity(self):
        """Calculates the host severity and returns it.
        @return the host severity
        """
        severity = 0
        if self.__hostState == NagiosMkLiveManager.HOST_STATE_UP and self.__nservices > 0:
            severity = round((((self.__nservicesWARNING*0.5)+self.__nservicesCRITICAL)/self.__nservices)*10)
        elif self.__hostState == NagiosMkLiveManager.HOST_STATE_UP and self.__nservices == 0:
            severity = 0
        else:
            severity = 10
        return int(severity)
    def has_ip(self,value):
        """Cheks if the host contains the ip
        @param value The IP Address.

        """
        if value not in self.__hostip:
            return False
        return True

class NagiosMkLiveManager(threading.Thread):
    '''
    Nagios mk_livestatus wrapper
    #hostgroup column member_with_state: A list of all host names that are members of the hostgroup together with state and has_been_checked
    '''

    HOST_STATES = {0: "UP",1:"DOWN",2:"DOWN",3:"DOWN"}
    HOST_STATES_SEVERITY = {0: 0,1:10,2:10,3:10}
    #SERVICE_STATES = {0: "UP",1:"WARNING",2:"CRITICAL",3:"UNKNOWN"}
    HOST_STATE_UP = 0
    HOST_STATE_WARNING = 1
    HOST_STATE_CRITICAL = 2
    HOST_STATE_UNKNOWN = 3
    HOST_QUERY_COLUMNS_COUNT = 7
    INDX_HOST_QUERY_HOSTIP = 0
    INDX_HOST_QUERY_HOSTNAME = 1
    INDX_HOST_QUERY_HOSTSTATE = 2
    INDX_HOST_QUERY_HOST_NUMSERVICES = 3
    INDX_HOST_QUERY_HOST_NUMSERVICESHARDWARN = 4
    INDX_HOST_QUERY_HOST_NUMSERVICESHARDCRIT = 5
    INDX_HOST_QUERY_HOST_SERVICESSTATE = 6
    '''
    TODO:
    (18:48:51) Pablo Catalina: (((W*0.5)+C)/float(SRV))*10
    (18:49:05) Pablo Catalina: Columns: name address state num_services_hard_ok num_services_hard_warn num_services_hard_crit num_services_hard_unknown
    '''
    def __init__(self):
        '''
        Constructor
        '''
        self.nagios_querys = {"hosts" : "GET hosts\nColumns: address name state num_services num_services_hard_warn num_services_hard_crit services_with_state\nOutputFormat: python\n",
#                              "services": "GET services\nColumns:check_command host_name description state\nOutputFormat: python\n",
#                              "hostgroups": "GET hostgroups\nColumns: name num_hosts num_hosts_down num_hosts_pending num_hosts_unreach num_hosts_up num_services num_services_hard_crit num_services_hard_ok num_services_hard_unknown num_services_hard_warn\nOutputFormat: python\n"
                              "hostgroups": "GET hostgroups\nColumns: name members_with_state \nOutputFormat: python\n",
                              "hostservices": "GET hosts\nColumns: address services_with_state\nOutputFormat: python\n"
#                              "hostgroups": "GET hostgroups\n",
#                              "servicegroups":"GET servicegroups\n",
#                              "contactgroups": "GET contactgroups\n",
#                              "servicesbygroup": "GET servicesbygroup\n",
#                              "servicesbyhostgroup": "GET servicesbyhostgroup\n",
#                              "hostsbygroup": "GET hostsbygroup\n",
#                              "contacts": "GET contacts\n",
#                              "commands":"GET commands\n",
#                              "timeperiods" : "GET timeperiods\n",
#                              "downtimes": "GET downtimes\n",
                              #"comments": "GET comments\n",
#                              "status": "GET status\nColumns: program_version\n",
                              #"columns": "GET columns\n"
                         }
        self.connection = None #Unix socket connection ->nagios socket.
        self.host_list = [] # BP asset host member list
        self.host_list_groups=[] # BP asset host_group members list
        self.interval = 30.0 # check interval.
        try:
            self.__interval = int(_CONF[VAR_NAGIOS_MKL_PERIOD])
        except ValueError:
            logger.error("Invalid value for: %s" % _CONF[VAR_NAGIOS_MKL_PERIOD])
        
        threading.Thread.__init__(self)


    def getData(self,cmd):
        """Runs the cmd query against the nagios socket.
        @param cmd Nagios query
        @returns the data with the query results or [] if empty.
        examble queries:
            GET hosts\nColumns: address name state num_services num_services_hard_warn num_services_hard_crit\nOutputFormat: python\n
            GET commands\n
         """
        data = []
        #Do not use isfile
        if os.path.exists(_CONF[VAR_NAGIOS_SOCK_PATH]):
            try:
                self.connection = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
                self.connection.connect(_CONF[VAR_NAGIOS_SOCK_PATH])
                self.connection.send(cmd)
                self.connection.shutdown(socket.SHUT_WR)
                data = self.connection.recv(100000000)
                self.connection.close()
            except socket.error,e:
                logger.error("Can't connect with nagios mklivestatus socket: %s"% str(e))
            except Exception,e:
                logger.error("An error occurred while connecting with mklivestatus socket: %s" % str(e))
        else:
            logger.warning( "%s doesn't exists. MkLiveStatus doesn't work" % _CONF[VAR_NAGIOS_SOCK_PATH])
        return data


    def loadDBData(self):
        """Loads the BP asset memebers from the database and stores it 
        in the local atributte self.host_list and self.host_list_groups.
        @returns Nothing
        """
        query_host = "SELECT distinct(hex(member))as member FROM bp_asset_member where type='host';"
        members = _DB.exec_query(query_host)
        del self.host_list[:]
        for host_member in members:
            #logger.info("host member :%s"%  host_member['member'])
            self.host_list.append(host_member['member'])
        query_host_group = "select hex(id) as id from host_group;"
        members = _DB.exec_query(query_host_group)
        del self.host_list_groups[:]
        for host_group in members:
            #logger.info("hot group :%s" % host_group['id'])
            self.host_list_groups.append(host_group['id'])

    def get_HostInfo_from_hostData(self,host_data):
        host_ip = host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOSTIP]
        host_name = host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOSTNAME]
        try:
            host_state = float(host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOSTSTATE])
        except ValueError:
            logger.info("Invalid host state value: %s --> %s" % (host_ip,host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOSTSTATE]))
            #return 0
        try:
            host_nservices = float(host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICES])
        except ValueError:
            logger.info("Invalid host_nservices value: %s --> %s" % (host_ip,host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICES]))
            #return 0
        try:
            host_nservices_warn = float(host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICESHARDWARN])
        except ValueError:
            logger.info("Invalid host_nservices_warn value: %s --> %s" % (host_ip,host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICESHARDWARN]))
            #return 0
        try:
            host_nservices_crit = float(host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICESHARDCRIT])
        except ValueError:
            logger.info("Invalid host_nservices_crit value: %s --> %s" % (host_ip,host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_NUMSERVICESHARDCRIT]))

        services_state_list = host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOST_SERVICESSTATE]
        host_services_state = {}
        for service in services_state_list:
            host_services_state[service[0]] = service[1]

            #return 0
#        severity = 0
#        if host_state == NagiosMkLiveManager.HOST_STATE_UP and host_nservices > 0:
#            severity = round((((host_nservices_warn*0.5)+host_nservices_crit)/host_nservices)*10)
#        elif host_state == NagiosMkLiveManager.HOST_STATE_UP and host_nservices == 0:
#            severity = 0
#        else:
#            severity = 10
#        return int(severity)
        return (host_state,host_nservices,host_nservices_warn,host_nservices_crit,host_services_state)

    def getHostGroupIDFromGroupName(self,name):
        """Retrieves from the database the host_group_id from the host name.
        @param name host group name
        @returns the host group uuid if it exists, otherwise empty
        """
        query = "select hex(id) as id from host_group where name='%s'" % name
        data = _DB.exec_query(query)
        if data:
            return data[0]['id']
        return ""


    def getHostIDFromHostIP(self,host_ip):
        """Retrieves from the database the host_id from the host ip.
        @param host_ip The host IP address
        @returns the host_id uuid if it exists, otherwise empty
        """
        query = """select hex(host.id) as id  from host,host_ip where host_ip.ip =inet6_pton('%s') and host.id = host_ip.host_id"""  % (host_ip)
        data = _DB.exec_query(query)
        if data:
            return data[0]['id']

        return ""


    def updateHostAvailability(self):
        """Updates the host availabiilty using the nagios date to do it.
        """
        data  = self.getData(self.nagios_querys['hosts'])
        if data!= []:
            #logger.info(data)
            
            try:
                data= eval(data)
            except Exception,e:
                logger.info("Invalid nagios data: %s" % str(e) )
                return 
            # Now, one host could have more than one IP address.
            # then we need to retreive all the services status for
            # each host_ip address.
            hostsData = {} # hostsData[host_id]= HostInfo()
            for host_data in data:
                if len(host_data) == NagiosMkLiveManager.HOST_QUERY_COLUMNS_COUNT:
                    host_ip = host_data[NagiosMkLiveManager.INDX_HOST_QUERY_HOSTIP]
                    host_id = self.getHostIDFromHostIP(host_ip)
                    if host_id  == "":
                        continue
                    (host_state,host_nservices,host_nservices_warn,host_nservices_crit,host_services_state) = self.get_HostInfo_from_hostData(host_data)
                    if not hostsData.has_key(host_id):
                        newhost = HostInfo()
                        newhost.setHostId(host_id)
                        hostsData[host_id]= newhost
                    hostsData[host_id].addHostIp(host_ip)
                    hostsData[host_id].addNServices(host_nservices)
                    hostsData[host_id].addServicesCritical(host_nservices_crit)
                    hostsData[host_id].addServicesWarning(host_nservices_warn)
                    hostsData[host_id].addHostState(host_state)
                    hostsData[host_id].setHostServicesState(host_services_state)
            for hostid,hostinfo in hostsData.iteritems():
                logger.info("Host ID: %s " % hostid)
                severity = hostinfo.getSeverity()
                query = "DELETE FROM bp_member_status WHERE member_id = unhex('%s') and measure_type = '%s';" % (hostid,'host_availability') 
                _DB.exec_query(query)
                query = "INSERT INTO bp_member_status (member_id, status_date, measure_type, severity) VALUES(0x%s, now(), '%s', %d);" % (hostid,'host_availability',severity)
                _DB.exec_query(query)
                logger.info("Updating Host Availability bp_member_status -> member:%s type: %s severity:%s" % (hostid,'host_availability',severity))

                services_state = hostinfo.getHostServicesState()
                for service, state in services_state.iteritems():
                    if service.startswith("GENERIC"):
                        service_split = service.split('_')
                        if service_split[1] == "UDP":
                            protocol = "17"
                        elif service_split[1] == "ICMP":
                            protocol = "1"
                        else:
                            protocol = "6"

                        query = "UPDATE host_services SET nagios_status = %s WHERE host_id = unhex('%s') AND port = %s AND protocol = %s" % (state, hostid, service_split[2], protocol)
                    else:
                        query = "UPDATE host_services SET nagios_status = %s WHERE host_id = unhex('%s') AND service LIKE '%s'" % (state, hostid, service)
                    _DB.exec_query(query)
                    logger.info("Updating Host Service Status host_services -> host_id: %s service: %s state: %s" % (hostid, service, state))

    def updataHostGroups(self):
        """Updates the host groups availability using the retrieved nagios data
        """
        data  = self.getData(self.nagios_querys['hostgroups'])
        if data != []:
            data= eval(data) 
            for hostgroup_data in data:
                if len (hostgroup_data) == 2:
                    host_group_name = hostgroup_data[0]
                    host_group_id = self.getHostGroupIDFromGroupName(host_group_name)
                    if host_group_id in self.host_list_groups:#host group name
                        for host in hostgroup_data[1]:
                            #From mk-livestatus-1.2.0b3/src/TableServicegroups.cc:62
                            #host[0] = hostname
                            #host[1] = host state
                            #host[2] =  has been checked
                            if len(host) == 3:
                                if host[1] != 0 and host[2] == 1:#host state
                                    query = "DELETE FROM bp_member_status WHERE hex(member_id) = '%s' and measure_type = '%s';" % (host_group_id,'host_group_availability') 
                                    _DB.exec_query(query)
                                    query = "INSERT INTO bp_member_status (member_id, status_date, measure_type, severity) VALUES(0x%s, now(), '%s', %d);" % (host_group_id,'host_group_availability', NagiosMkLiveManager.HOST_STATES_SEVERITY [host[1]])
                                    _DB.exec_query(query)
                                    logger.info("Updating Host Grop bp_member_status -> member:%s type: %s severity:%s" % (host_group_name,'host_group_availability',NagiosMkLiveManager.HOST_STATES_SEVERITY [host[1]]))

    def run(self):
        """Nagios Mklive wrapper entry point.
        """
        while True:
            # 1 - Loads the database bp members.
            self.loadDBData()
            # 2 - Updates the host availability 
            self.updateHostAvailability()
            # 3 - Updates the host group availability
            self.updataHostGroups()

            time.sleep(self.interval)
if __name__ == "__main__":
    ng = NagiosMkLiveManager()
    ng.start()
