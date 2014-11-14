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
import threading, time, sys
import re
#
# LOCAL IMPORTS
#
import Util
from Logger import Logger
from OssimDB import OssimDB
from OssimConf import OssimConf
from DBConstantNames import *
#
# GLOBAL VARIABLES
#
logger = Logger.logger
_CONF = OssimConf()
_DB = OssimDB(_CONF[VAR_DB_HOST],
              _CONF[VAR_DB_SCHEMA],
              _CONF[VAR_DB_USER],
              _CONF[VAR_DB_PASSWORD])
_DB.connect()

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
#                                                                       #
#             BPMember            Measure --------<> MeasureList        #
#                ^                   ^                                  #
#                |                   |               MemberTypes        #
#      +----+----+----+----+         |                                  #
#      |    |    |    |    |     MeasureDB         BusinessProcesses    #
#     Host  |   Net   |   File                                          #
#      HostGroup  NetGroup                                              #
#                                                                       #
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

class BPMember:

    def __init__(self, member,ip = ''):
        self.member = member #hex(id) 
        self.ip = ip 
        # Child classes must declare the following variables:
        # - self.member_type: the type of the child member 
        #   For example: 'host', 'file'
        #   These values must be present in bp_asset_member_type db table
        # - self.measures: a list of measure objects.
        #   Every kind of member has its own measures


    def update_status(self):
        for m in self.measures:
            if m.measure_type == "net_availability":
                severity = self.get_net_availability(self.member)
            elif m.measure_type == "net_group_availability":
                severity = self.get_netgroup_availability(self.member)
            else:
                severity = m.get_severity()
            #print severity
            if severity >= Measure.MIN_SEVERITY and \
               severity <= Measure.MAX_SEVERITY:

                # clean the old member entry
                query = """
                    DELETE FROM bp_member_status 
                        WHERE member_id = unhex('%s') and measure_type = '%s';
                """ % (self.member, m.measure_type)
                _DB.exec_query(query)

                # insert the new one
                query = """
                    INSERT INTO bp_member_status
                        (member_id, status_date, measure_type, severity)
                        VALUES(0x%s, now(), '%s', %d);
                """ % (self.member, m.measure_type, severity)
                logger.info("Updating measure [%s] of member [%s:%s] with severity [%s]" % (m.measure_type, self.member_type, self.member, str(severity)))
                _DB.execute_non_query(query=query,autocommit=False)


    def get_net_availability(self, net_id):
        #query = "select hex(id) from host;"
        query = "select hex(host.id) as id , inet6_ntop(host_ip.ip) as ip from host, host_ip where host.id = host_ip.host_id;"
        host_list = _DB.exec_query(query)
        tmp_availability = 0
        for host in host_list:
            #look for the host inside the network.
            query = "select c.cidr from net_cidrs c,net n where n.ips=c.cidr and c.begin<=inet6_pton('%s') and c.end>=inet6_pton('%s') and hex(n.id)='%s' order by hex(c.end)-hex(c.begin) asc;" % (host['ip'], host['ip'], net_id)
            result = _DB.exec_query(query)
            if result != []:
                query = "select severity as ha from bp_member_status where member_id=unhex('%s') and measure_type='host_availability';" % host['id']
                host_availability = _DB.exec_query(query)
                if host_availability != []:
                    if host_availability[0].has_key('ha'):
                        ha_value = host_availability[0]['ha']
                        if host_availability[0]['ha'] is not None:
                            if tmp_availability < ha_value:
                                tmp_availability = ha_value
        return tmp_availability

    def get_netgroup_availability(self, netgroup):
        query = """SELECT hex(net_id) as net_id FROM net_group_reference where hex(net_group_id) = '%s';""" % netgroup
        result = _DB.exec_query(query)
        tmp_availibility = 0
        for row in result:
            n_name = row['net_id']
            net_av = self.get_net_availability(n_name)
            if tmp_availibility < net_av:
                tmp_availibility = net_av
        return tmp_availibility

class BPMemberHost(BPMember):

    def __init__(self, member,ip):

        BPMember.__init__(self, member,ip)

        # A host member has the following measures:
        # risk, vulnerability, incident and metric
        self.member_type = 'host'
        measure_list = MeasureList(self.member,self.ip)
        self.measures = [
            measure_list['host_alarm'],
            measure_list['host_metric'], # 1
            measure_list['host_vulnerability'], # 2
            measure_list['host_incident'],
            measure_list['host_incident_alarm'],
            measure_list['host_incident_event'],
            measure_list['host_incident_metric'],
            measure_list['host_incident_anomaly'],
            measure_list['host_incident_vulns'],
#            measure_list['host_availability'], #Use nagios mklive
        ]

        # add net->metric and net->vulnerability alternative measures
        # to each network the host belongs to
#        for net in self.get_member_nets():
#            net_measure_list = MeasureList(net)
#            self.measures[1].add_alternative_measure(
#                net_measure_list['net_metric'])
#            self.measures[2].add_alternative_measure(
#                net_measure_list['net_vulnerability'])

        # add global->metric and global->vulnerability alternative measures
        self.measures[1].add_alternative_measure(
            measure_list['global_metric'])
        self.measures[2].add_alternative_measure(
            measure_list['global_vulnerability'])

#    def get_member_nets(self):
#        nets = []
#        result = _DB.exec_query("""SELECT ips FROM net;""")
#        if result != []:
#            for r in result:
#                net = r['ips']
#                if Util.isIpInNet(host=self.member, net_list=[net]):
#                    nets.append(net)
#        return nets

class BPMemberNet(BPMember):

    def __init__(self, member):
        BPMember.__init__(self, member)
        self.member_type = 'net'
        measure_list = MeasureList(self.member)
        self.measures = [
            measure_list['net_metric'], # 0
            measure_list['net_vulnerability'], # 1
            measure_list['net_availability'], # 1
        ]

        # metric measure: get global->metric value if net->metric is empty
        self.measures[0].add_alternative_measure(
            measure_list['global_metric'])
        self.measures[1].add_alternative_measure(
            measure_list['global_vulnerability'])

# TODO: new members
class BPMemberHostGroup(BPMember):

    def __init__(self, member):
        BPMember.__init__(self, member)
        self.member_type = 'host_group'
        measure_list = MeasureList(self.member)
        self.measures = [
            measure_list['host_group_availability'],
            measure_list['host_group_vulnerability'],
            measure_list['host_group_alarm'],
            measure_list['host_group_metric'],
        ]


class BPMemberNetworkGroup(BPMember):
    def __init__(self, member):
        BPMember.__init__(self, member)
        self.member_type = 'network_group'
        measure_list = MeasureList(self.member)
        self.measures = [
            measure_list['net_group_availability'],
            measure_list['net_group_vulnerability'],
            measure_list['net_group_alarm'],
            measure_list['net_group_metric'],
        ]

#class BPMemberNetGroup(BPMember):
#
#    def __init__(self, member):
#        BPMember.__init__(self, member)
#        self.member_type = 'host_group'
#        self.measures = []

class BPMemberFile(BPMember):

    def __init__(self, member):
        BPMember.__init__(self, member)
        self.member_type = 'file'
        self.measures = []


class MeasureList:

    def __init__(self, member,ip = ""):
        self.member = member
        self.ip = ip
        self.measures = {
            ### host measures ###
            'host_alarm': \
                MeasureDB (
                    measure_type='host_alarm',
                    request="""
                    SELECT MAX(risk) AS host_alarm FROM alarm, host_ip WHERE status='open' AND  alarm.src_ip=host_ip.ip AND host_ip.host_id = unhex('%s')
                    UNION
                    SELECT MAX(risk) AS host_alarm FROM alarm, host_ip WHERE status='open' AND  alarm.dst_ip=host_ip.ip AND host_ip.host_id = unhex('%s');
                    """ % (self.member,self.member),
#                    request="""
#                SELECT MAX(risk) AS host_alarm FROM alarm
#                    WHERE (dst_ip in (select host_ip.ip from host_ip where host_ip.host_id = unhex('%s'))  OR
#                           src_ip in (select host_ip.ip from host_ip where host_ip.host_id = unhex('%s'))) 
#                           AND status='open';
#                    """ % (self.member, self.member),
                    severity_max=7
                ),
            'host_metric': \
                MeasureDB (
                    measure_type='host_metric',
                    request="""
                SELECT compromise + attack AS host_metric
                    FROM host_qualification
                    WHERE host_id = unhex('%s');
                    """ % (self.member),
                    severity_max=int(_CONF["threshold"]) * 2
                ),
            'host_vulnerability': \
                MeasureDB (
                    measure_type='host_vulnerability',
                    request="""
                SELECT vulnerability AS host_vulnerability
                    FROM host_vulnerability WHERE host_id = unhex('%s') ORDER BY scan_date DESC;
                    """ % (self.member),
                    severity_max=10
                ),
            'host_incident': \
                MeasureDB (
                    measure_type='host_incident',
                    request="""
                SELECT priority AS host_incident FROM incident
                    WHERE title LIKE '%%%s%%' AND status = 'Open';
                    """ % (self.member),
                    severity_max=7
                ),

            ## TODO: fix search pattern ##
            'host_incident_alarm': \
                MeasureDB (
                    measure_type='host_incident_alarm',
                    request="""
                SELECT incident.priority AS host_incident_alarm, incident.id 
                    FROM incident, incident_alarm
                    WHERE incident.id = incident_alarm.incident_id AND
                        (incident_alarm.src_ips LIKE "%%%s" OR 
                         incident_alarm.dst_ips LIKE "%%%s") AND
                        incident.status = 'Open';
                    """ % (self.member, self.member),
                    severity_max=7
                ),
            'host_incident_event': \
                MeasureDB (
                    measure_type='host_incident_event',
                    request="""
                SELECT incident.priority AS host_incident_event, incident.id 
                    FROM incident, incident_event
                    WHERE incident.id = incident_event.incident_id AND
                        (incident_event.src_ips LIKE "%%%s" OR 
                         incident_event.dst_ips LIKE "%%%s") AND
                        incident.status = 'Open';
                    """ % (self.member, self.member),
                    severity_max=7
                ),
            'host_incident_metric': \
                MeasureDB (
                    measure_type='host_incident_metric',
                    request="""
                SELECT incident.priority AS host_incident_metric, incident.id
                    FROM incident, incident_metric
                    WHERE incident.id = incident_metric.incident_id AND
                        incident_metric.target = "%s" AND
                        incident.status = 'Open';
                    """ % (self.ip),
                    severity_max=7
                ),
            'host_incident_anomaly': \
                MeasureDB (
                    measure_type='host_incident_anomaly',
                    request="""
                SELECT incident.priority AS host_incident_anomaly, incident.id
                    FROM incident, incident_anomaly
                    WHERE incident.id = incident_anomaly.incident_id AND
                        incident_anomaly.ip = "%s" AND
                        incident.status = 'Open';
                    """ % (self.ip),
                    severity_max=7
                ),
            'host_incident_vulns': \
                MeasureDB (
                    measure_type='host_incident_vulns',
                    request="""
                SELECT incident.priority AS host_incident_vulns, incident.id 
                    FROM incident, incident_vulns
                    WHERE incident.id = incident_vulns.incident_id AND 
                        incident_vulns.ip = "%s" AND
                        incident.status = 'Open';
                    """ % (self.ip),
                    severity_max=7
                ),
            'host_availability': \
                MeasureDB (
                    measure_type='host_availability',
                    # TODO: Don't hardcode DB ino, query right DB
                    # nagios plugin_id: 1525
                    # nagios sids for host availability: 1-6
                    #
                    # select userdata1 as host_availability FROM snort.event, snort.ossim_event, snort.extra_data, snort.iphdr WHERE snort.event.sid = snort.ossim_event.sid and snort.event.cid = snort.ossim_event.cid and snort.event.sid = snort.extra_data.sid and snort.event.cid = snort.extra_data.cid and snort.event.sid = snort.iphdr.sid and snort.event.cid = snort.iphdr.cid  and snort.iphdr.ip_src = inet_aton("%s") and snort.ossim_event.plugin_id = 1525 order by snort.event.timestamp desc limit 1;
                    #
                    request="""
                    select e.userdata1 as host_availability 
                        FROM alienvault_siem.acid_event a, alienvault_siem.extra_data e 
                        WHERE a.id = e.event_id and a.ip_src = inet6_pton("%s") and 
                        a.plugin_id = 1525 order by a.timestamp desc limit 1;
                    """ % (self.ip),
                    severity_max=70,
                    translation={
                        'host_availability: DOWN': 100,
                        'host_availability: UP': 0,
                        'service_availability: CRITICAL': 100,
                        'service_availability: UNREACHABLE': 60,
                        'service_availability: WARNING': 60,
                        'service_availability: UNKNOWN': 20,
                        'service_availability: OK': 0,
                    }
                ),
            ### net measures ###
            'net_metric': \
                MeasureDB (
                    measure_type='net_metric',
                    request="""
                        SELECT compromise + attack AS net_metric
                            FROM net_qualification
                            WHERE net_id = unhex('%s');
                    """ % (self.member),
                    severity_max=int(_CONF["threshold"]) * 2
                ),
            'net_vulnerability': \
                MeasureDB (
                    measure_type='net_vulnerability',
                    request="""
                        SELECT vulnerability AS net_vulnerability
                            FROM net_vulnerability WHERE net_id = unhex('%s') ORDER BY scan_date DESC;
                        """ % (self.member),
                    severity_max=10,
                ),
            'net_availability': \
                MeasureDB (
                    measure_type='net_availability',
                    request="",
                    severity_max=10,
                ),
            ### global measures ###
            'global_metric': \
                MeasureDB (
                    measure_type='global_metric',
                    request="""
                        SELECT (SUM(compromise)+SUM(attack))/count(*) 
                            AS global_metric FROM host_qualification;
                    """,
                    severity_max=int(_CONF["threshold"] * 2)
                ),
            'global_vulnerability': \
                MeasureDB (
                    measure_type='global_vulnerability',
                    request="""
                        SELECT SUM(vulnerability)/count(*)
                            AS global_vulnerability FROM host_vulnerability;
                    """,
                    severity_max=10
                ),
        ###Host_Group measures - JBlasco###
            'host_group_availability': \
                MeasureDB (
                    measure_type='host_group_availability',
                    request="""
                        select severity as host_group_availability from host_group_reference as refer, 
                        bp_member_status as stat where host_group_id = unhex('%s') and refer.host_id = stat.member_id and 
                        stat.measure_type = 'host_availability' order by stat.severity desc limit 1;
                    """ % (self.member),
                    severity_max=10
                ),
            'host_group_vulnerability': \
                MeasureDB (
                    measure_type='host_group_vulnerability',
                    request="""
                        select severity as host_group_vulnerability from host_group_reference as refer,
                            bp_member_status as stat where host_group_id = unhex('%s') and
                            refer.host_id = stat.member_id and
                            stat.measure_type = 'host_vulnerability' order by stat.severity desc limit 1;
                    """ % (self.member),
                    severity_max=10
                ),
            'host_group_alarm': \
                MeasureDB (
                    measure_type='host_group_alarm',
                    request="""
                        select severity as host_group_alarm from host_group_reference as refer,
                            bp_member_status as stat where host_group_id = unhex('%s') and
                            refer.host_id = stat.member_id and
                            stat.measure_type = 'host_alarm' order by stat.severity desc limit 1;
                    """ % (self.member),
                    severity_max=10
                ),
            'host_group_metric': \
                MeasureDB (
                    measure_type='host_group_metric',
                    request="""
                        select severity as host_group_metric from host_group_reference as refer,
                            bp_member_status as stat where host_group_id = unhex('%s' and
                            refer.host_id = stat.member_id and
                            stat.measure_type = 'host_metric' order by stat.severity desc limit 1
                    """ % (self.member),
                    severity_max=10
                ),
            'host_group_incident': \
                MeasureDB (
                    measure_type='host_group_incident',
                    request="""
                        select severity as host_group_incident from host_group_reference as refer,
                            bp_member_status as stat where host_group_id = unhex('%s') and
                            refer.host_id = stat.member_id and
                            stat.measure_type = 'host_incident' order by stat.severity desc limit 1
                    """ % (self.member),
                    severity_max=10
               ),
        ###NET_Group measures ###
            'net_group_availability': \
                MeasureDB (
                    measure_type='net_group_availability',
                    request="",
                    severity_max=10
                ),
            'net_group_vulnerability': \
                MeasureDB (
                    measure_type='net_group_vulnerability',
                    request="""
                        select severity as net_group_vulnerability from net_group_reference as refer,
                            bp_member_status as stat where net_group_id = unhex('%s') and
                            refer.net_group_id = stat.member_id and
                            stat.measure_type = 'net_vulnerability' order by stat.severity desc limit 1;
                    """ % (self.member),
                    severity_max=10
                ),
            'net_group_alarm': \
                MeasureDB (
                    measure_type='net_group_alarm',
                    request="""
                        select severity as net_group_alarm from net_group_reference as refer,
                            bp_member_status as stat where net_group_id = unhex('%s') and
                            refer.net_group_id = stat.member_id and
                            stat.measure_type = 'net_alarm' order by stat.severity desc limit 1;
                    """ % (self.member),
                    severity_max=10
                ),
            'net_group_metric': \
                MeasureDB (
                    measure_type='net_group_metric',
                    request="""
                        select severity as net_group_metric from net_group_reference as refer,
                            bp_member_status as stat where net_group_id = unhex('%s') and
                            refer.net_group_id = stat.member_id and
                            stat.measure_type = 'net_metric' order by stat.severity desc limit 1
                    """ % (self.member),
                    severity_max=10
                ),
            'net_group_incident': \
                MeasureDB (
                    measure_type='net_group_incident',
                    request="""
                        select severity as net_group_incident from net_group_reference as refer,
                            ossim.bp_member_status as stat where net_group_id = unhex('%s') and
                            refer.net_group_id = stat.member_id and
                            stat.measure_type = 'net_incident' order by stat.severity desc limit 1
                    """ % (self.member),
                    severity_max=10
               ),
        }

    def __getitem__(self, item):
        return self.measures[item]

    def __setitem__(self, item, value):
        self.measures[item] = value

class Measure:

    MAX_SEVERITY = 10
    MIN_SEVERITY = 0

    def __init__(self, measure_type, request, severity_max, translation={}):
        self.measure_type = measure_type
        self.request = request
        self.severity_max = severity_max
        self.translation = translation
        self.alternative_measures = []

    # you must redefine this method in child classes
    def get_measure(self):
        logger.info("%s" % str(self.request))
# TODO: Remove when confirmed
#        print __name__, self.request
        return None

    def get_severity(self):
        
        def _get_severity(measure):
            measure.measure_value = measure.get_measure()
            #print measure.measure_value
            if measure.measure_value is not None:
                severity = measure.measure_value * Measure.MAX_SEVERITY \
                    / measure.severity_max
                if severity > Measure.MAX_SEVERITY:
                    severity = Measure.MAX_SEVERITY
                return severity
            return None

        # array with measures 
        # [ original (self) plus alternatives (self.alternative_measures) ]
        measures = self.alternative_measures
        measures.insert(0, self)

        for measure in measures:            
            severity = _get_severity(measure)
            if severity is not None:
                return severity

        return Measure.MIN_SEVERITY

    # if a measure returns a 'None' severity,
    # try getting the value using alternative measures
    def add_alternative_measure(self, alt_measure):
        self.alternative_measures.append(alt_measure)


class MeasureDB(Measure):

    def get_measure(self):
        
        query_array = self.request.split('\n')
        query = ""
        for text in query_array:
            query += text.lstrip()
        result = _DB.exec_query(self.request)
    #print self.request
        if result != []:
            #
            # IMPORTANT: the result is indexed by measure_type,
            # so be careful building your queries in bp_meber_* classes.
            #
            # for example, given a measure of type 'metric', 
            # you need to build your query this way: 
            # """SELECT foobar AS metric"""
            #                     ^^^^^^
            
            if result[0].has_key(self.measure_type):
                if result[0][self.measure_type] is not None:
                    s = self.translation.get(result[0][self.measure_type],
                                             result[0][self.measure_type])
                    #logger.info("CRG - MeasureDB  - get_measure Query: %s  REsult: %s S:%s" % (self.request[0],result,s))
                    if type(s) is int or \
                       type(s) is long:     # severity must be integer
                        return s
        return None


class MemberTypes:

    def __init__(self):
        self.types = self.get_types()

    def get_types(self):
        types = []
        #query = """SELECT distinct(type_name) FROM bp_asset_member_type;"""
        #AV4 Change.
        query = """select column_type from INFORMATION_SCHEMA.COLUMNS where TABLE_SCHEMA ='alienvault' and TABLE_NAME='bp_asset_member' and COLUMN_NAME='type';"""
        
        result = _DB.exec_query(query)
        if len(result) > 0:
            d = result[0]
            tmpstr = d['column_type']
            rr = re.compile("enum\((?P<groups>.*)\)")
            data = rr.match(tmpstr)
            if data:
                tmp_list = data.groupdict()['groups'].split('\'')
                for type in tmp_list:
                    if type != '' and type != ',':
                        types.append(type)
        return types        

    # this method is defined to allow the use
    # of the operators 'in' and 'not in'
    def __contains__(self, measure_type):
        return measure_type in self.types 


class BusinessProcesses(threading.Thread):

    def __init__(self, seconds_between_iterations=_CONF[VAR_BUSINESSPROCESSES_PERIOD]):
        #self.member_types = MemberTypes()
        #AV4 Change. Now,
        self.__interval = 300.0
        try:
            self.__interval = int(_CONF[VAR_BUSINESSPROCESSES_PERIOD])
        except ValueError:
            logger.error("Invalid value for: %s" % _CONF[VAR_BUSINESSPROCESSES_PERIOD])
        self.member_types = MemberTypes()
        self.sleep = float(seconds_between_iterations)
        threading.Thread.__init__(self)

    def get_members(self):
        query = \
            """SELECT distinct(hex(member))as member, type as member_type FROM bp_asset_member;"""
        members = _DB.exec_query(query)
        return members

    def run(self):
        while 1:
            self.members = self.get_members()
            
            for m in self.members:
                member = None

                # check bp_asset_member_type table for supported member types
                if m['member_type'] not in self.member_types:
                    logger.info("Unsupported member type (%s)" % (m['member_type']))
                    continue

                if m['member_type'] == 'host':
                    query = "select hex(host.id) as id, inet6_ntop(host_ip.ip) as ip from host,host_ip where id =unhex('%s') and host.id = host_ip.host_id union select hex(id) as id, inet6_ntop(ip) from sensor where hex(id)='%s';" % (m['member'], m['member'])
                    result = _DB.exec_query(query)
                    for row in result:#Must by only one row.
                        member = BPMemberHost(m['member'],row['ip'])


                elif m['member_type'] == 'net':
                    member = BPMemberNet(m['member'])

                elif m['member_type'] == 'host_group':
                    query = """SELECT hex(host_group_reference.host_id) as host_id, inet6_ntop(host_ip.ip) as ip FROM host_ip,host_group_reference where host_group_id = unhex('%s' and host_group_id=host_ip.host_id;""" % (m['member'])
                    #host_group_reference
                    result = _DB.exec_query(query)
                    for row in result:
                        member = BPMemberHost(row['host_id'],row['ip'])
        
                        if member:
                            member.update_status()
                    #group_measures = ["host_alarm", "host_metric", "host_vulnerability", "host_incident", "host_incident_alarm", "host_incident_event", "host_incident_metric", "host_incident_anomaly", "host_incident_vulns", "host_availability"]
                    member = BPMemberHostGroup(m['member'])
                elif m['member_type'] == 'net_group':
                    ng_name = m['member']
                    query = """SELECT hex(net_id) as net_id FROM net_group_reference where net_group_id = unhex('%s');""" % (m['member'])
                    result = _DB.exec_query(query)
                    for row in result:
                        n_name = row['net_id']
                        member = BPMemberNet(row['net_id'])
                        if member:
                            member.update_status()
                    member = BPMemberNetworkGroup(row['net_id'])
                    
                if member:
                    member.update_status()
            time.sleep(self.__interval)


if __name__ == '__main__':

    bp = BusinessProcesses(seconds_between_iterations=10)
    bp.start()

    while 1:
        try:
            time.sleep(1)
        except KeyboardInterrupt:
            import os, signal
            pid = os.getpid()
            os.kill(pid, signal.SIGTERM)


# vim:ts=4 sts=4 tw=79 expandtab:
