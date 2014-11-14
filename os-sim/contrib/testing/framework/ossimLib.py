#!/usr/bin/env python
# encoding: utf-8
"""
Created by Jaime Blasco on 2009-09-14
Ossim Base testing module

License:

Copyright (c) 2009 AlienVault
All rights reserved.

This package is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 dated June, 1991.
You may not use, modify or distribute this program under any other version
of the GNU General Public License.

This package is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this package; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
MA  02110-1301  USA


On Debian GNU/Linux systems, the complete text of the GNU General
Public License can be found in `/usr/share/common-licenses/GPL-2'.

Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
"""

import os
import sys
import re
import socket
try:
	import MySQLdb
except:
	pass
import fileinput
import commands
import time
import datetime
import random
try:
	from statgrab import *
except:
	pass

class ossim():
	"""ossim lib base class"""
	def __init__(self):
		self.config_file = "/etc/ossim/framework/ossim.conf"
		self.server = "localhost"
		self.port = 40001
		self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
		self.user, self.password = self.readDbInfo()
	
	def readDbInfo(self):
		"""Reads Database Information from ossim configuration file"""
		user = ''
		password = ''
		for line in fileinput.input(self.config_file):
			p = re.compile(r"ossim_pass=(?P<pass>\S+)")
			m = p.match(line)
			if (m):
				password = m.group(1)
			p = re.compile(r"ossim_user=(?P<user>\S+)")
			m = p.match(line)
			if (m):
				user = m.group(1)
		return user, password

	def dbConn(self):
		"""Stablish a mysql session and return the cursos"""
		db=MySQLdb.connect(host=self.server,user=self.user , passwd=self.password ,db='ossim')
		cursor=db.cursor()
		return cursor
		
	def clearDataTables(self):
		"""Clear all the snort database tables"""
		cursor = self.dbConn()
		cursor.execute("TRUNCATE snort.acid_event");
		cursor.execute("TRUNCATE snort.data");
		cursor.execute("TRUNCATE snort.event");
		cursor.execute("TRUNCATE snort.icmphdr");
		cursor.execute("TRUNCATE snort.iphdr");
		cursor.execute("TRUNCATE snort.reference");
		cursor.execute("TRUNCATE snort.sensor");
		cursor.execute("TRUNCATE snort.sig_class");
		cursor.execute("TRUNCATE snort.sig_reference");
		cursor.execute("TRUNCATE snort.tcphdr");
		cursor.execute("TRUNCATE snort.udphdr");
		cursor.execute("TRUNCATE snort.ossim_event");
		cursor.execute("TRUNCATE snort.extra_data");
		cursor.execute("TRUNCATE snort.ac_sensor_sid");
		cursor.execute("TRUNCATE snort.ac_sensor_signature");
		cursor.execute("TRUNCATE snort.ac_sensor_ipsrc");
		cursor.execute("TRUNCATE snort.ac_sensor_ipdst");
		cursor.execute("TRUNCATE snort.ac_alerts_sid");
		cursor.execute("TRUNCATE snort.ac_alerts_signature");
		cursor.execute("TRUNCATE snort.ac_alerts_ipsrc");
		cursor.execute("TRUNCATE snort.ac_alerts_ipdst");
		cursor.execute("TRUNCATE snort.ac_alertsclas_classid");
		cursor.execute("TRUNCATE snort.ac_alertsclas_sid");
		cursor.execute("TRUNCATE snort.ac_alertsclas_signature");
		cursor.execute("TRUNCATE snort.ac_alertsclas_ipsrc");
		cursor.execute("TRUNCATE snort.ac_alertsclas_ipdst");
		cursor.execute("TRUNCATE snort.ac_srcaddr_ipdst");
		cursor.execute("TRUNCATE snort.ac_srcaddr_ipsrc");
		cursor.execute("TRUNCATE snort.ac_srcaddr_sid");
		cursor.execute("TRUNCATE snort.ac_srcaddr_signature");
		cursor.execute("TRUNCATE snort.ac_dstaddr_ipdst");
		cursor.execute("TRUNCATE snort.ac_dstaddr_ipsrc");
		cursor.execute("TRUNCATE snort.ac_dstaddr_sid");
		cursor.execute("TRUNCATE snort.ac_dstaddr_signature");
		cursor.execute("TRUNCATE snort.ac_layer4_sport");
		cursor.execute("TRUNCATE snort.ac_layer4_sport_sid");
		cursor.execute("TRUNCATE snort.ac_layer4_sport_signature");
		cursor.execute("TRUNCATE snort.ac_layer4_sport_ipsrc");
		cursor.execute("TRUNCATE snort.ac_layer4_sport_ipdst");
		cursor.execute("TRUNCATE snort.ac_layer4_dport");
		cursor.execute("TRUNCATE snort.ac_layer4_dport_sid");
		cursor.execute("TRUNCATE snort.ac_layer4_dport_signature");
		cursor.execute("TRUNCATE snort.ac_layer4_dport_ipsrc");
		cursor.execute("TRUNCATE snort.ac_layer4_dport_ipdst");
		cursor.close()
		
	def getServerVersion(self):
		"""Execute ossim-server -v and returns ossim server version"""
		return commands.getstatusoutput('ossim-server --v')[1]
		
	def getOs(self, host):
		"""Returns the Operating System from a host"""
		cursor = self.dbConn()
		sql = "select os from host_os where ip = inet_aton('%s');" % host
		cursor.execute(sql)
		os= cursor.fetchone()
		cursor.close()
		return os

	def getEvent(self, ip_src, ip_dst):
		"""Returns last event from ip_src to ip_dst"""
		cursor = self.dbConn()
		sql = "select ossim_priority,ossim_reliability,ossim_asset_src,ossim_asset_dst,ossim_risk_c,ossim_risk_a from snort.acid_event where  ip_src = inet_aton('%s') and ip_dst = inet_aton('%s') order by cid desc;" % (ip_src, ip_dst)
		cursor.execute(sql)
		val = cursor.fetchone()
		cursor.close()
		return val
		
	def checkAlarm(self, id, sid):
		"""Return Risk from last alarm with id, sid values"""
		cursor = self.dbConn()
		sql = "select risk from alarm where plugin_id = '%s' and plugin_sid = '%s';" % (id, sid)
		cursor.execute(sql)
		val = cursor.fetchone()
		cursor.close()
		if val:
			return val[0]
		else:
			return None
			
	def countAlarms(self, id, sid):
		"""Returns the number of alarms present on the system with values id, sid"""
		cursor = self.dbConn()
		sql = "select count(*) from alarm where plugin_id = '%s' and plugin_sid = '%s';" % (id, sid)
		cursor.execute(sql)
		val = cursor.fetchone()
		cursor.close()
		if val:
			return val[0]
		else:
			return None
			
	def countEvents(self):
		"""Return the number of events on database"""
		cursor = self.dbConn()
		sql = "select count(*) from snort.acid_event;"
		cursor.execute(sql)
		val = cursor.fetchone()
		cursor.close()
		if val:
			return val[0]
		else:
			return None
		
	def countBacklog(self, sid):
		"""Return the backlogs associated with alarm sid (last one)"""
		cursor = self.dbConn()
		sql = "SELECT count(*) FROM backlog_event where backlog_id = (select backlog_id from alarm where plugin_sid = %d limit 1);" % sid
		cursor.execute(sql)
		val = cursor.fetchone()
		cursor.close()
		if val:
			return int(val[0])
		else:
			return None
			
	def insertHost(self, ip, hostname, asset):
		"""Insert a host with values ip, hostname, asset"""
		cursor = self.dbConn()
		sql = "INSERT INTO host(ip, hostname, asset, threshold_c, threshold_a, alert, persistence) values ('%s', '%s', %d, 1000, 1000, 0, 0);" % (ip, hostname, asset)
		cursor.execute(sql)
		sql = "INSERT INTO host_sensor_reference values ('%s', 'ossim');" % ip
		cursor.execute(sql)
		cursor.close()
		
	def insertNetwork(self, name, ips, asset):
		"""Insert a network with values name, ips, asset"""
		cursor = self.dbConn()
		sql = "INSERT INTO net(name, ips, asset, threshold_c, threshold_a, alert, persistence) values('%s', '%s', %d, 30, 30, 0, 0);" % (name, ips, asset)
		cursor.execute(sql)
		sql = "INSERT INTO net_sensor_reference values ('%s', 'ossim');" % name
		cursor.execute(sql)
		cursor.close()
	
	def insertPluginSid(self, id, sid, prio, rel, name):
		"""Insert or replace a plugin sid"""
		cursor = self.dbConn()
		sql = "REPLACE INTO plugin_sid (plugin_id, sid, reliability, priority, name) values (%d, %d, %d, %d, '%s')" % (id, sid, prio, rel, name)
		cursor.execute(sql)
		cursor.close()

	def insertPolicyGroup(self, id, name, plugin_id, sids):
		"""Inserts a policy group"""
		cursor = self.dbConn()
		sql = "REPLACE INTO ossim.plugin_group(group_id,plugin_id,plugin_sid) values (%d, %d, '%s');" % (id, plugin_id, sids)
		cursor.execute(sql)
		sql = "REPLACE INTO ossim.plugin_group_descr (group_id,name,descr) values (%d, '%s', '%s');" % (id, name, name)
		cursor.execute(sql)
		cursor.close()

	def insertPortGroup(self, name, ports):
		"""Inserts a Port Group"""
		cursor = self.dbConn()
		sql = "REPLACE INTO port_group values ('%s', '%s');" % (name, name)
		cursor.execute(sql)
		for p in ports:
			sql = "REPLACE INTO port_group_reference values ('%s', %d, 'tcp');" % (name, p)
		cursor.execute(sql)
		cursor.close()
		
	def insertSimplePolicy(self, id, priority, group, order, desc):
		"""Inserts a simple policy with id,id , src,any, dst,any, priority,priority, plugin_group,group and !store """
		cursor = self.dbConn()
		sql = "REPLACE INTO policy (id,priority, active, policy.group, policy.order, descr) values (%d, %d, 1, 1, %d, '%s');" % (id, priority, order, desc)
		cursor.execute(sql)
		sql = "REPLACE INTO policy_host_reference(policy_id, host_ip, direction) values (%d, 'any', 'source');" % (id)
		cursor.execute(sql)
		sql = "REPLACE INTO policy_host_reference(policy_id, host_ip, direction) values (%d, 'any', 'dest');" % (id)
		cursor.execute(sql)
		sql = "REPLACE INTO policy_port_reference(policy_id, port_group_name) values (%d, 'ANY');" % (id)
		cursor.execute(sql)
		sql = "REPLACE INTO policy_plugin_group_reference(policy_id, group_id) values (%d, %d);" % (id, group)
		cursor.execute(sql)
		sql = "REPLACE INTO policy_sensor_reference(policy_id, sensor_name) values (%d, 'any');" % (id)
		cursor.execute(sql)
		sql = "REPLACE INTO policy_target_reference(policy_id, target_name) values (%d, 'any');" % (id)
		cursor.execute(sql)
		sql = "REPLACE INTO policy_role_reference(policy_id, correlate, cross_correlate, store, qualify, resend_alarm, resend_event, sign, sem, sim) values (%d, 1, 1, 0, 1, 0, 0, 0, 0, 1);" % (id)
		cursor.execute(sql)
		sql = "REPLACE INTO policy_time(policy_id, begin_hour, end_hour, begin_day, end_day) values (%d, 0, 23, 1, 7);" % (id)
		cursor.execute(sql)
		cursor.close()

	def countEventsBySid(self, id, sid):
		"""Counts the number of events with sig_name"""
		cursor = self.dbConn()
		sql = "SELECT count(*) from snort.acid_event where sig_name = '%d';" % (sid)
		cursor.execute(sql)
		cursor.close()
		val = cursor.fetchone()
		if val:
			return int(val[0])
		else:
			return None
		
	def getSensorInfo(self):
		"""Returns the ip of ossim sensor"""
		cursor = self.dbConn()
		sql = "select ip from sensor where name = 'ossim';"
		cursor.execute(sql)
		val = cursor.fetchone()
		cursor.close()
		return val[0]
		
	def checkOs(self, ip):
		"""Returns th eoperating system of a given host"""
		cursor = self.dbConn()
		sql = "select os from host_os where ip = inet_aton('%s');" % ip
		cursor.execute(sql)
		val = cursor.fetchone()
		cursor.close()
		if val:
			return val[0]
		else:
			return None

	def deleteHosts(self):
		"""Deletes all hosts from database"""
		cursor = self.dbConn()
		sql = "DELETE from host where hostname != 'ossim';"
		cursor.execute(sql)
		sql = "delete from host_sensor_reference where host_ip != (select ip from host where hostname = 'ossim');"
		cursor.execute(sql)
		cursor.close()

	def deleteNetworks(self):
		"""Deletes all networks from database"""
		cursor = self.dbConn()
		sql = "DELETE from ossim.net;"
		cursor.execute(sql)
		sql = "DELETE from ossim.net_sensor_reference;"
		cursor.execute(sql)
		cursor.close()

	def deletePolicy(self):
		"""Deletes all policies from database"""
		cursor = self.dbConn()
		sql = "DELETE from ossim.policy;"
		cursor.execute(sql)
		cursor.close()

	def deleteOs(self):
		"""Deletes all Operating system entries on database"""
		cursor = self.dbConn()
		sql = "DELETE from ossim.host_os;"
		cursor.execute(sql)
		cursor.close()

	def deleteAlarms(self):
		"""Deletes all alarms from database"""
		cursor = self.dbConn()
		sql = "DELETE from ossim.alarm;"
		cursor.execute(sql)
		sql = "DELETE from ossim.event;"
		cursor.execute(sql)
		cursor.close()

	def deleteDirect(self):
		"""Deletes all directive entries from database"""
		cursor = self.dbConn()
		sql = "DELETE from ossim.plugin_sid where plugin_id = 1505;"
		cursor.execute(sql)
		cursor.close()

	def closeAlarm(self, sid):
		"""Closes alarms with provided sid"""
		cursor = self.dbConn()
		sql = "UPDATE alarm SET status = 'closed' where plugin_sid = %s" % sid
		cursor.execute(sql)
		cursor.close()

	def connect(self):
		"""Opens connection to server with base socket"""
		try:
			self.s.connect((self.server, self.port))
		except:
			print "Connection to server failed"
			
			
	def sendEvent(self, src_ip, dst_ip, src_port, dst_port, sensor, id, sid, date):
		"""Sends event with provided values"""
		if date == "now":
			t = datetime.datetime.now()
			dat = str(int(time.mktime(t.timetuple())))
			fdate =  t.strftime("%Y-%m-%d %H:%M:%S")

		ev = 'event type="detector" date="%s" plugin_id="%s" plugin_sid="%s" sensor="%s" interface="eth0" protocol="TCP" src_ip="%s" src_port="%s" dst_ip="%s" dst_port="%s" fdate="%s" tzone="-1"\n' % (dat, id, sid, sensor, src_ip, src_port, dst_ip, dst_port,fdate)
		print ev
		self.s.send(ev)

	def stopProcess(self, process):
		"""Stops selected process"""
		os.system('/etc/init.d/%s stop' % process)
			
	def risk(self, asset, reliability, priority):
		"""Calculates risk"""
		print "Calculating risk %d * %d * %d / 25" % (asset, reliability, priority)
		return asset * reliability * priority / 25
	
	def randomHost(self):
		"""Returns random host"""
		a = random.randint(0, 253)
		b = random.randint(0, 253)
		c = random.randint(0, 253)
		d = random.randint(0, 253)
		
		return "%s.%s.%s.%s" % (str(a),str(b),str(c),str(d))

	def sendPixFWDenyEvent(self, src_ip, dst_ip, src_port, dst_port, sensor, date):
		"""Sends Pix Deny event with provided values"""
		if date == "now":
			t = datetime.datetime.now()
			dat = str(int(time.mktime(t.timetuple())))
			fdate =  t.strftime("%Y-%m-%d %H:%M:%S")
		#2009-04-16 15:54:44,175 Detector [INFO]: event type="detector" date="1239897389" sensor="16.0.13.1" interface="eth0,eth2,eth3,eth4,eth5" plugin_id="1514" plugin_sid="106011" src_ip="20.94.21.72" src_port="4049" dst_ip="16.0.45.46"
		#dst_port="445"
		#log="Apr 16 15:54:43 16.0.13.1 Apr 16 2009 16:56:29: %FWSM-3-106011: Deny inbound (No xlate) tcp src OUTSCORE:20.94.21.72/4049 dst OUTSCORE:16.0.45.46/445" fdate="2009-04-16 16:56:29" tzone="-1"
		ev = 'event type="detector" date="%s" plugin_id="1514" plugin_sid="106011" sensor="%s" interface="eth0" src_ip="%s" src_port="%s" dst_ip="%s" dst_port="%s" fdate="%s" log="%s: FWSM-3-106011: Deny inbound (No xlate) tcp src OUTSCORE:%s/%s dst OUTSCORE:%s/%s" tzone="-1"\n' % (dat, sensor, src_ip, src_port, dst_ip, dst_port, fdate, fdate, src_ip, src_port, dst_ip, dst_port)
		print ev
		self.s.send(ev)
		
	def sendSnareApp(self, src_ip, sensor, date, username, filename):
		"""Sends Snare application event with provided values"""
		if date == "now":
			t = datetime.datetime.now()
			dat = str(int(time.mktime(t.timetuple())))
			fdate =  t.strftime("%Y-%m-%d %H:%M:%S")
		ev = 'event type="detector" date="%s" plugin_id="1518" plugin_sid="592" sensor="%s" interface="eth0" src_ip="%s" dst_ip="%s" fdate="%s" ' \
		'tzone="-1" username="%s" filename="%s" log="%s MSWinEventLog;0110;011Security;0118;011%s;011592;011Security;011;011User;011Success Audit' \
		';011X;011Seguimiento detallado  ;011;011Se ha creado un proceso:     Id. de proceso: 6128     Nombre de archivo de imagen: %s     Id. de proceso creador: 1084     ' \
		'Nombre de usuario: %s     Dominio: X     Id. de inicio de sesion: (0x0,0x42ECF)    ;0111"\n' % (dat, sensor, src_ip, sensor, fdate, username, filename, fdate, username, filename, username)
		print ev
		self.s.send(ev)
		
	def sendOsEvent(self, host, sensor, date):
		"""Sends operating system event with provided values"""
		#host-os-event host="192.168.176.113" os="Windows" sensor="192.168.88.4" interface="any" date="1246509268" plugin_id="1511" plugin_sid="1" log="<Thu Jul  2 06:34:28 2009> 192.168.176.113:3659 - Windows 2000 SP4, XP SP1+" fdate="2009-07-02 06:34:28"
		if date == "now":
			t = datetime.datetime.now()
			dat = str(int(time.mktime(t.timetuple())))
			fdate =  t.strftime("%Y-%m-%d %H:%M:%S")
		ev = 'host-os-event host="%s" os="Windows" sensor="%s" interface="any" date="%s" plugin_id="1511" plugin_sid="1" log="<%s> %s:3659 - Windows 2000 SP4, XP SP1+" fdate="%s"\n' % (host, sensor, dat, fdate, host, fdate)
		print ev
		self.s.send(ev)

	def getPluginList(self):
		"""Returns a list with the plugins present in database"""
		cursor = self.dbConn()
		sql = "select id from plugin where Type = 1 and id != 1511 and id != 1516 and id != 1512 and id != 1505;"
		cursor.execute(sql)
		plugins= cursor.fetchall()
		plist = []
		for p in plugins:
			plist.append(p)
		cursor.close()
		return plist
		
	def getSidsByPlugin(self, id):
		"""Returns a list of sids of the provided plugin id"""
		cursor = self.dbConn()
		sql="select sid from plugin_sid where plugin_id = %s" % id
		cursor.execute(sql)
		sids=cursor.fetchall()
		slist = []
		for s in sids:
			slist.append(s[0])
		cursor.close()
		return slist
		
	def getProcSQL(self):
		"""Returns the number of database connections"""
		cursor = self.dbConn()
		sql = "SHOW PROCESSLIST;"
		cursor.execute(sql)
		conns= cursor.fetchall()
		cursor.close()
		return len(conns)

	def checkMem(self):
		"""Returns system's memory usage"""
		mem = sg_get_mem_stats()
		percent = (mem['used'] * 100) / mem['total']
		#print mem
		#print "%d percent %d " % (percent, mem['used'] / 1024 / 1024)
		return mem['used'] / 1024 / 1024

	def checkLoad(self):
		"""Returns system's load"""
		load = sg_get_load_stats()
		#print load
		return load
		
	def checkProcess(self, proc):
		"""Returns the data of a given process or None if the process doesn't exists"""
		process = sg_get_process_stats()
		for p in process:
			if p['process_name'] == 'ossim-server':
				return p
			else:
				return None
		return None

	def getDBAgents(self):
		"""Returns a list of valid ossim sensor's ips"""
		cursor = self.dbConn()
		sql="select ip from sensor"
		cursor.execute(sql)
		sids=cursor.fetchall()
		slist = []
		for s in sids:
			slist.append(s[0])
		cursor.close()
		return slist

	def clearData(self):
		"""Clear events and alarms from database"""
		self.clearDataTables()
		self.deleteAlarms()
		
		
if __name__ == '__main__':
	oLib = ossim()
	
	
