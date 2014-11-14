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
import threading, socket, re, sys, time
from xmlrpclib import ServerProxy, Transport
from sets import Set

#
# LOCAL IMPORTS
#

import Const
from OssimDB import OssimDB
from OssimConf import OssimConf

from Logger import Logger
logger = Logger.logger

_CONF  = OssimConf(Const.CONFIG_FILE)
_DB    = OssimDB()
_DB.connect(_CONF['ossim_host'],
            _CONF['ossim_base'],
            _CONF['ossim_user'],
            _CONF['ossim_pass'])
_SNORT_DB    = OssimDB()
_SNORT_DB.connect(_CONF['snort_host'],
                  _CONF['snort_base'],
                  _CONF['snort_user'],
                  _CONF['snort_pass'])


class ProxiedTransport(Transport):

    # Put here an identification string for your application
    user_agent = 'frameworkd'

    def __init__(self, proxy):
        Transport.__init__(self, use_datetime=0)
        self.proxy = proxy
        # xmlrpclib.Transport.__init__(self, proxy)

    def set_proxy(self, proxy):
        self.proxy = proxy

    def make_connection(self, host):
        self.realhost = host
        import httplib
        return httplib.HTTP(self.proxy)

    def send_request(self, connection, handler, request_body):
        connection.putrequest("POST", 'http://%s%s' % (self.realhost, handler))

    def send_host(self, connection, host):
        connection.putheader('Host', self.realhost)


# Table A - Status
#
class Status:

    def __init__(self):

        self.info = {
            'service_level':        100,
            'attack_level':         0,
            'compromise_level':     0,
            'threshold':            500,
            'last_incident':        "",
            'num_events':           0,
            'incident_num_open':    0,
        }

    def _get_service_level(self):

        query = """
            SELECT c_sec_level AS c, a_sec_level AS a 
                FROM control_panel 
                WHERE rrd_type = 'global' AND 
                      id = 'global_admin' AND
                      time_range = 'day'
            """
        levels = _DB.exec_query(query)
        if levels != []:
            self.info['service_level'] = \
                int((levels[0]['c'] + levels[0]['a']) / 2)

    def _get_current_levels(self):

        query = """
            SELECT SUM(compromise) AS c, SUM(attack) AS a 
                FROM host_qualification
            """
        levels = _DB.exec_query(query)
        if levels != []:
            if levels[0]['c'] is None:
                self.info['compromise_level'] = 0
            else:
                self.info['compromise_level'] = int(levels[0]['c'])
            if levels[0]['a'] is None:
                self.info['attack_level'] = 0
            else:
                self.info['attack_level'] = int(levels[0]['a'])

    def _get_threshold(self):
        query = ''
        usekey = False
        if not _CONF['encryptionKey']:
            query = "SELECT value FROM config WHERE conf = 'threshold'"
        else:
            logger.debug("THRESHOLD---- using key: %s" % _CONF['encryptionKey'])
            query = "SELECT *,AES_DECRYPT(value,'%s') as dvalue FROM config WHERE conf = 'threshold'" % _CONF['encryptionKey']
            usekey = True
            
        result = _DB.exec_query(query)
        if result != []:
            self.info['threshold'] = int(result[0]['value'])
        elif usekey:
            #test for no decrypt.
            query = "SELECT value FROM config WHERE conf = 'threshold'"
            result = _DB.exec_query(query)
            if result != []:
                self.info['threshold'] = int(result[0]['value'])


    def _get_last_incident(self):

        query = "SELECT title FROM incident ORDER BY date DESC LIMIT 1"
        result = _DB.exec_query(query)
        if result != []:
            self.info['last_incident'] = str(result[0]['title'])

    def _get_open_incidents(self):

        query = """
            SELECT count(*) AS incidents FROM incident 
                WHERE status = 'Open'
            """
        result = _DB.exec_query(query)
        if result != []:
            self.info['incident_num_open'] = int(result[0]['incidents'])

    def _get_num_events(self):

        query = """
            SELECT count(*) AS num_events
                FROM event WHERE timestamp + 3600 > now()
            """
        result = _SNORT_DB.exec_query(query)
        if result != []:
            self.info['num_events'] = int(int(result[0]['num_events']) / 60)


    def get_info(self):
        self._get_service_level()
        self._get_current_levels()
        self._get_threshold()
        self._get_last_incident()
        self._get_open_incidents()
        self._get_num_events()


# Table B - Incident
#
class Incidents:
    
    def __init__(self):
        self.info = {}

    def get_info(self):

        query = """
            SELECT * FROM incident 
                WHERE status = 'Open' AND priority > 7
            """
        result = _DB.exec_query(query)
        if result != []:
            for incident in result:
                self.info[incident['title']] = {
                    'in_charge':    str(incident['in_charge']),
                    'created':      str(incident['date']),
                    'type_name':    str(incident['ref']),
                    'last_update':  str(incident['last_update']),
                    'priority':     int(incident['priority']),
                }

# Table C - Service
#
class Services:
  
    MSG_CONNECT =                   'connect id="%s" type="sensor"\n'
  
    GET_SENSORS_REQUEST =           'server-get-sensors id="1"\n'
    GET_SENSORS_REPLY =             'sensor host="([^"]*)" state="([^"]*)"'

    GET_SENSOR_PLUGINS_REQUEST =    'server-get-sensor-plugins id="2"\n'
    GET_SENSOR_PLUGINS_REPLY =      'sensor="([^"]*)" plugin_id="([^"]*)" ' +\
                                    'state="([^"]*)" enabled="([^"]*)"\n'
  
    def __init__(self):
        self.server_ip = _CONF['server_address']
        self.server_port = _CONF['server_port']

        self.conn = None
        self._connect()

        self.info = {
            'agents':       { 'total': 0, 'up': 0 },
            'servers':      { 'total': 1, 'up': 0 },
            'frameworks':   { 'total': 1, 'up': 1 },
            'snorts':       { 'total': 0, 'up': 0 },
            'ntops':        { 'total': 0, 'up': 0 },
        }

    def _connect(self):

        try:
            self.conn = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            self.conn.connect((self.server_ip, int(self.server_port)))
        except socket.error, e:
            print __name__, ": Can not connect to server(%s, %s)!" % \
                ((self.server_ip, int(self.server_port)))
            self.conn = None
            print e

    def _get_agents(self):

        sensor_set = Set()

        # get total agents from sensor table
        query = "SELECT count(*) AS agents FROM sensor"
        result = _DB.exec_query(query)
        if result != []:
            self.info['agents']['total'] = int(result[0]['agents'])

        # get running agents from server
        if self.conn is not None:

            self.conn.send(self.GET_SENSORS_REQUEST)
            while 1:
                try:
                    data = self.recv_line(self.conn)
                    if data.startswith("ok"):
                        break
                    result = re.findall(self.GET_SENSORS_REPLY, data)
                    try:
                        (sensor_temp, sensor_temp_status) = result[0]
                        sensor_set.add(sensor_temp)
                    except IndexError,e:
                        logger.debug("Error ..%s" % str(e))
                except socket.error, e:
                    print e
            self.info['agents']['up'] = len(sensor_set)

        else:
            self.info['agents']['up'] = 0

    def _get_servers(self):

        if self.conn is not None:
            self.info['servers']['total'] = self.info['servers']['up'] = 1

    def _get_frameworks(self):
        pass


    def _get_sensor_plugins(self):
        snort_set = Set()
        snort_total_set = Set()
        ntop_set = Set()
        ntop_total_set = Set()
        
        if self.conn is None:
            return
        try:
            self.conn.send(self.GET_SENSOR_PLUGINS_REQUEST)
            while 1:
                data = self.recv_line(self.conn)
                if data.startswith('ok'):
                    break

                result = re.findall(self.GET_SENSOR_PLUGINS_REPLY, data)
                if result != []:
                    (sensor_ip, plugin_id, state, enabled) = result[0]


                    if int(plugin_id) < 1500: 
                        snort_total_set.add(sensor_ip)
                        if state == "start":
                            snort_set.add(sensor_ip)
                    if int(plugin_id) == 2005: 
                        ntop_total_set.add(sensor_ip)
                        if state == "start":
                            ntop_set.add(sensor_ip)

        except socket.error, e:
            print e
        self.info['ntops']['up'] = len(ntop_set)
        self.info['snorts']['up'] = len(snort_set)
        self.info['snorts']['total'] = self.info['agents']['total']
        self.info['ntops']['total'] = self.info['agents']['total']

    def recv_line(self, conn):
        char = data = ''
        while 1:
            try:
                char = conn.recv(1)
                data += char
                if char == '\n':
                    break
            except socket.error:
                print 'Error receiving from server'
            except AttributeError:
                print 'Error receiving from server'
        return data


    def get_info(self):

        self._get_agents()
        self._get_servers()
        self._get_frameworks()
        self._get_sensor_plugins()

        if self.conn is not None:
            self.conn.close()


class SOC(threading.Thread):

    def __init__(self):

        self.status = self.incident = self.services = None

        self.client_name = _CONF['client_name']
        self.soc_url = _CONF['soc_url']
        self.soc_proxy = _CONF['soc_proxy']

        if self.client_name is None:
            print "client_name is not set"
            sys.exit()

        if self.soc_url is None:
            print "soc_url is not set"
            sys.exit()

        if self.soc_proxy is None:
            self.server = ServerProxy(self.soc_url)
        else:
            transport = ProxiedTransport(self.soc_proxy)
            self.server = ServerProxy(self.soc_url, transport)

        threading.Thread.__init__(self)

    def send_status(self):
        self.status = Status()
        self.status.get_info()
        try:
            print "Status ->", self.status.info, "\n"
            self.server.security_status(self.client_name, 
                                        self.status.info)
        except Exception, e:
            print e
        self.status = None

    def send_incidents(self):
        self.incidents = Incidents()
        self.incidents.get_info()
        try:
            print "Incidents ->", self.incidents.info, "\n"
            self.server.incidents(self.client_name,
                                  self.incidents.info)
        except Exception, e:
            print e
        self.incidents = None

    def send_services(self):
        self.services = Services()
        self.services.get_info()
        try:
            print "Services ->", self.services.info, "\n"
            self.server.services_status(self.client_name,
                                        self.services.info)
        except Exception, e:
            print e
        self.services = None


    def send_all_info(self):

        print __name__, ": Sending status info to SOC.."
        self.send_status()

        print __name__, ": Sending incidents info to SOC.."
        self.send_incidents()

        print __name__, ": Sending services info to SOC.."
        self.send_services()


    def run(self):

        self.send_all_info()
        print __name__, ": Sleeping 180 seconds in order to let other threads run before this one.."
        time.sleep(float(180))

        while 1:
            self.send_all_info()
            time.sleep(float(Const.SLEEP))


if __name__ == '__main__':
    soc = SOC()
    soc.start()


# vim:ts=4 sts=4 tw=79 expandtab:
