#!/usr/bin/env python

# Reputation feedback
#
# vrt@alienvault.com
# aortega@alienvault.com

import MySQLdb
import os
import sys
import time
import datetime
import urllib
import pycurl
import StringIO
import base64
import ConfigParser
from IPy import IP
import re

global config_file
global db_encryption_file
global repu_server
global repu_url
global repu_url_all
global repu_config
global log_file
global protocol
global curlrc
global lockfile

protocol = "https" # http or https
repu_server = "reputation.alienvault.com"
repu_url = "/cb502fcbda6339ac65e76e31bcf6f8f2_feedback/post_info.php"
repu_url_all = "/cb502fcbda6339ac65e76e31bcf6f8f2_feedback/post_info_all.php"
repu_config = "/cb502fcbda6339ac65e76e31bcf6f8f2_feedback/feedback_config"
config_file = "/etc/ossim/ossim_setup.conf"
db_encryption_file = "/etc/ossim/framework/db_encryption_key"
log_file = "/var/log/ossim/reputation.log"
curlrc = "/etc/curlrc"
lockfile = "/tmp/.reputation_feedback.lock"

preview = False
if (len(sys.argv) == 2):
	if (sys.argv[1] == "--preview"):
		preview = True

def lock():
	f = open(lockfile, "w")
	f.close()

def unlock():
	if os.path.exists(lockfile):
		os.unlink(lockfile)

def exit_proc(unlock_file = True):
	if unlock_file == True:
		unlock()
	sys.exit(0)

def write_repu_log(msg):
	try:
		f = open(log_file, "a")
		f.write("%s %s\n" % (time.strftime("%Y-%m-%d %H:%M:%S"), msg))
		f.close()
	except:
		pass

def read_curl_config():
	proxy = None
	if os.path.exists(curlrc) == True:
		r = re.compile("^proxy.*=(.*)$")
		f = open(curlrc, "r")
		data = f.read()
		f.close()
		for ln in data.split("\n"):
			m = r.match(ln)
			if m:
				proxy = m.group(1).replace(" ", "")
				break
	return proxy

def get_url(url):
	proxy = read_curl_config()
	buffer = StringIO.StringIO()
	c = pycurl.Curl()
	c.setopt(pycurl.URL, url)
	c.setopt(pycurl.SSL_VERIFYPEER, 0)
	c.setopt(pycurl.WRITEFUNCTION, buffer.write)
	if proxy:
		c.setopt(pycurl.PROXY, proxy)
	c.perform()
	data = buffer.getvalue()
	c.close()
	return data

def readDbInfo():

	# DB access information
	c = ConfigParser.ConfigParser()

	fix = False
	try:
		c.read(config_file)
	except:
		# Fix
		dummy = "[ossim]\n"
		f = open(config_file, "r")
		data = f.read()
		f.close()
		f = open("/tmp/.crafted_conf", "w")
		f.write("%s%s" % (dummy, data))
		f.close()
		c.read("/tmp/.crafted_conf")
		fix = True

	user = c.get('database','user')
	password = c.get('database','pass')
	host = c.get('database','db_ip')

	if fix == True:
		os.remove("/tmp/.crafted_conf")

	# DB encryption information
	c = ConfigParser.ConfigParser()
	c.read(db_encryption_file)
	db_encryption_key = c.get('key-value','key')

	return user, password, host, db_encryption_key

def dbConn(dbUser, dbPass, dbHost):
	global db
	db=MySQLdb.connect(host=dbHost, user=dbUser, passwd=dbPass)
	cursor=db.cursor()
	return cursor

def getHomeNet(cursor):
	nets_ret = []
	sql = "select ips from alienvault.net;"
	cursor.execute(sql)
	nets = cursor.fetchall()
	if nets:
		for n in nets:
			try:
				IP(n[0])
				nets_ret.append(n[0])
			except:
				pass
	return nets_ret

def cleanHomeNet(data, homenets):
	data_ret = data
	for h in homenets:
		net = IP(h)
		for i in data.keys():
			if i in net or IP(i).iptype() == "PRIVATE":
				data_ret.pop(i)
	return data_ret

def getOTXopt(cursor):
	sql = "select value from alienvault.config where conf = 'open_threat_exchange';"
	try:
		cursor.execute(sql)
		opt = cursor.fetchall()
		option = False
		if opt:
			for i in opt:
				option = i[0]
			if option == "yes":
				option = True
			else:
				option = False
		return option
	except:
		return False

def getClientID(cursor, enc_key):
	sql = "select value from alienvault.config where conf = 'open_threat_exchange_key';"
	try:
		cursor.execute(sql)
		data = cursor.fetchall()
		clid = False
		for i in data:
			clid = i[0]
		if re.search("\w{8}-\w{4}-\w{4}-\w{4}-\w{12}", clid):
			return clid
		else:
			sql = "select AES_DECRYPT(value, '"+enc_key+"') from alienvault.config where conf = 'open_threat_exchange_key';"
			cursor.execute(sql)
			data = cursor.fetchall()
			for i in data:
				clid = i[0]
			if re.search("\w{8}-\w{4}-\w{4}-\w{4}-\w{12}", clid):
				return clid
			elif re.search("[0-9a-fA-F]{64}", clid): # New otx key format: sha256 - 64 hexadecimal characters
				return clid
		return False
	except:
		return False

def get_feed_timestamp(cursor):
	sql = "select UNIX_TIMESTAMP(value) from alienvault.config where conf = 'open_threat_exchange_last';"
	try:
		cursor.execute(sql)
		time = cursor.fetchall()
		timestamp = None
		if time:
			for i in time:
				timestamp = int(i[0])
		return timestamp
	except:
		return None

def dump_feed_timestamp(cursor):
	sql = "REPLACE INTO alienvault.config (conf, value) VALUES ('open_threat_exchange_last', UTC_TIMESTAMP());"
	cursor.execute(sql)
	db.commit()

def getDataSrc(since, cursor):
	data = {}
	sql = "select count(a.ip_src) as total, a.plugin_id, a.plugin_sid, inet6_ntop(a.ip_src) as ip_src from alienvault_siem.acid_event a, alienvault_siem.reputation_data r, alienvault.plugin_sid s where a.timestamp >= FROM_UNIXTIME("+str(since)+") and a.timestamp < UTC_TIMESTAMP() and a.id = r.event_id and r.rep_act_src != '' and s.plugin_id = a.plugin_id and s.sid = a.plugin_sid and s.name not like '%ET RBN%' and s.name not like '%ET DROP%' and s.name not like '%ET TOR%' group by plugin_id,plugin_sid,a.ip_src order by total desc;"
	cursor.execute(sql)
	events =  cursor.fetchall()
	if events:
		for e in events:
			ip = e[3]
			id = e[1]
			sid = e[2]
			cnt = int(e[0])
			if ip not in data.keys():
				data[ip] = {"%s,%s"%(id,sid):cnt}
			else:
				data[ip]["%s,%s"%(id,sid)] = cnt

	return data

def getDataSrc_total(since, cursor, condition):
	data = {}
	sql = "select count(a.ip_src) as total, a.plugin_id, a.plugin_sid, inet6_ntop(a.ip_src) as ip_src from alienvault_siem.acid_event a, alienvault.plugin_sid s where a.timestamp >= FROM_UNIXTIME("+str(since)+") and a.timestamp < UTC_TIMESTAMP() and s.plugin_id = a.plugin_id and s.sid = a.plugin_sid and ("+condition+") group by plugin_id,plugin_sid,a.ip_src order by total desc;"
	cursor.execute(sql)
	events =  cursor.fetchall()
	if events:
		for e in events:
			ip = e[3]
			id = e[1]
			sid = e[2]
			cnt = int(e[0])
			if ip not in data.keys():
				data[ip] = {"%s,%s"%(id,sid):cnt}
			else:
				data[ip]["%s,%s"%(id,sid)] = cnt

	return data

def getDataDst(since, cursor):
	data = {}
	sql = "select count(a.ip_dst) as total, a.plugin_id, a.plugin_sid, inet6_ntop(a.ip_dst) as ip_dst from alienvault_siem.acid_event a, alienvault_siem.reputation_data r, alienvault.plugin_sid s where a.timestamp >= FROM_UNIXTIME("+str(since)+") and a.timestamp < UTC_TIMESTAMP() and a.id = r.event_id and r.rep_act_dst != '' and s.plugin_id = a.plugin_id and s.sid = a.plugin_sid and s.name not like '%ET RBN%' and s.name not like '%ET DROP%' and s.name not like '%ET TOR%' group by plugin_id,plugin_sid,a.ip_dst order by total desc;"
	cursor.execute(sql)
	events =  cursor.fetchall()
	if events:
		for e in events:
			ip = e[3]
			id = e[1]
			sid = e[2]
			cnt = int(e[0])
			if ip not in data.keys():
				data[ip] = {"%s,%s"%(id,sid):cnt}
			else:
				data[ip]["%s,%s"%(id,sid)] = cnt

	return data

def getDataDst_total(since, cursor, condition):
	data = {}
	sql = "select count(a.ip_dst) as total, a.plugin_id, a.plugin_sid, inet6_ntop(a.ip_dst) as ip_dst from alienvault_siem.acid_event a, alienvault.plugin_sid s where a.timestamp >= FROM_UNIXTIME("+str(since)+") and a.timestamp < UTC_TIMESTAMP() and s.plugin_id = a.plugin_id and s.sid = a.plugin_sid and ("+condition+") group by plugin_id,plugin_sid,a.ip_dst order by total desc;"
	cursor.execute(sql)
	events =  cursor.fetchall()
	if events:
		for e in events:
			ip = e[3]
			id = e[1]
			sid = e[2]
			cnt = int(e[0])
			if ip not in data.keys():
				data[ip] = {"%s,%s"%(id,sid):cnt}
			else:
				data[ip]["%s,%s"%(id,sid)] = cnt

	return data

def fromConfigToSql(config):
	sql_append = ""
	first_p = True
	for ln in config.split("\n"):
		to_drop = []
		to_add = []
		ln = ln.rstrip()
		if ln == "end":
			break
		plugin = ln.split(":")[0]
		conds = ln.split(":")[1]
		if first_p == True:
			sql_append = sql_append + "((s.plugin_id = %s)" % plugin
			first_p = False
		else:
			sql_append = sql_append + " or ((s.plugin_id = %s)" % plugin
		for c in conds.split(","):
			if c == "all":
				break
			# Negations and all stuff
			if c[0] == "!":
				if (c.find("-") != -1): # Negative ranges
					to_drop.append(c.replace("!", ""))
				else:
					to_drop.append(c.split("-")[0].replace("!", ""))
			else:
				if (c.find("-") != -1): # Positive ranges
					to_add.append(c)
				else:
					to_add.append(c)

		sql_append = sql_append + " and ("
		first = True
		if len(to_add) == 0:
			sql_append = sql_append + "1 = 1"
		for i in to_add:
			if i.find("-") != -1:
				if first == True:
					sql_append = sql_append + "(s.sid between %s and %s)" % (i.split("-")[0], i.split("-")[1])
					first = False
				else:
					sql_append = sql_append + " or (s.sid between %s and %s)" % (i.split("-")[0], i.split("-")[1])
			else:
				if first == True:
					sql_append = sql_append + "(s.sid = %s)" % (i)
					first = False
				else:
					sql_append = sql_append + " or (s.sid = %s)" % (i)
		sql_append = sql_append + ") and ("
		first = True
		if len(to_drop) == 0:
			sql_append = sql_append + "1 = 1"
		for i in to_drop:
			if i.find("-") != -1:
				if first == True:
					sql_append = sql_append + "(s.sid not between %s and %s)" % (i.split("-")[0], i.split("-")[1])
					first = False
				else:
					sql_append = sql_append + " and (s.sid not between %s and %s)" % (i.split("-")[0], i.split("-")[1])
			else:
				if first == True:
					sql_append = sql_append + "(s.sid <> %s)" % (i)
					first = False
				else:
					sql_append = sql_append + " and (s.sid <> %s)" % (i)
		sql_append = sql_append + "))"

	return sql_append

# Run
if os.path.exists(lockfile):
	# Process running, die now
	exit_proc(False)

lock()

# Get DB access data
try:
	dbUser, dbPass, dbHost, db_encryption_key = readDbInfo()
except:
	write_repu_log("Error-feedback: Unable to read database configuration")
	exit_proc()

# Connect to DB
try:
	dbcursor = dbConn(dbUser, dbPass, dbHost)
except:
	write_repu_log("Error-feedback: Unable to connect to database")
	exit_proc()

# Check if reputation feedback is enabled
if (getOTXopt(dbcursor) == False):
	exit_proc()

write_repu_log("Message-feedback: Running reputation feedback")
write_repu_log("Message-feedback: Getting data from database")

# Get client ID
client_id = getClientID(dbcursor, db_encryption_key)
if (client_id == False):
	write_repu_log("Error-feedback: Unable to get OTX client ID")
	exit_proc()

# Get last timestamp
last_time = get_feed_timestamp(dbcursor)
if not last_time or last_time == 0:
	last_time = time.time() - 10800

# Get HOME_NET
try:
	home_nets = getHomeNet(dbcursor)
except:
	write_repu_log("Error-feedback: Unable to read HOME_NET from database")
	exit_proc()

# Get reputation feedback config
try:
	config = get_url("%s://%s%s" % (protocol, repu_server, repu_config))
except:
	write_repu_log("Error-feedback: Unable to get feedback configuration from remote server")
	exit_proc()

# Parse feedback configuration
try:
	feed_condition = fromConfigToSql(config)
except:
	write_repu_log("Error-feedback: Error parsing feedback configuration")
	exit_proc()

# Get reputation feedback data
try:
	data_dst = getDataDst(last_time, dbcursor)
	data_dst = cleanHomeNet(data_dst, home_nets)
	data_src = getDataSrc(last_time, dbcursor)
	data_src = cleanHomeNet(data_src, home_nets)
except:
	write_repu_log("Error-feedback: Something went wrong while getting data from database (reputation step)")
	exit_proc()

# Get all feedback data
try:
	data_dst_all = getDataDst_total(last_time, dbcursor, feed_condition)
	data_dst_all = cleanHomeNet(data_dst_all, home_nets)
	data_src_all = getDataSrc_total(last_time, dbcursor, feed_condition)
	data_src_all = cleanHomeNet(data_src_all, home_nets)
except:
	write_repu_log("Error-feedback: Something went wrong while getting data from database (all feedback step)")
	exit_proc()

if (preview == True):
	# Print information
	print "{\"reputation_destin\":" + str(data_dst).replace("\'", "\"") + "}"
	print "{\"reputation_source\":" + str(data_src).replace("\'", "\"") + "}"
	print "{\"reputation_destin_all\":" + str(data_dst_all).replace("\'", "\"") + "}"
	print "{\"reputation_source_all\":" + str(data_src_all).replace("\'", "\"") + "}"
	write_repu_log("Message-feedback: Running in --preview mode, information wont be sent")
	exit_proc()

try:
	dump_feed_timestamp(dbcursor)
except:
	write_repu_log("Error-feedback: Unable to dump timestamp")
	exit_proc()

# Send to our server
try:
	write_repu_log("Message-feedback: Sending information (reputation step)")
	data_to_send = base64.urlsafe_b64encode("%s#%s" % (str(data_dst), str(data_src)))
	http_params = urllib.urlencode({'client_id': client_id, 'info': data_to_send})
	url = "%s://%s%s" % (protocol, repu_server, repu_url)
	proxy = read_curl_config()
	c = pycurl.Curl()
	c.setopt(pycurl.URL, url)
	c.setopt(pycurl.SSL_VERIFYPEER, 0)
	c.setopt(pycurl.POSTFIELDS, http_params)
	c.setopt(pycurl.HTTPHEADER, ["Host: %s" % repu_server])
	if proxy:
		c.setopt(pycurl.PROXY, proxy)
	c.perform()
	c.close()
	write_repu_log("Message-feedback: Information sent (reputation step)")
except:
	write_repu_log("Error-feedback: Unable to send information (reputation step)")
	exit_proc()

try:
	write_repu_log("Message-feedback: Sending information (all feedback step)")
	data_to_send = base64.urlsafe_b64encode("%s#%s" % (str(data_dst_all), str(data_src_all)))
	http_params = urllib.urlencode({'client_id': client_id, 'info': data_to_send})
	url = "%s://%s%s" % (protocol, repu_server, repu_url_all)
	proxy = read_curl_config()
	c = pycurl.Curl()
	c.setopt(pycurl.URL, url)
	c.setopt(pycurl.SSL_VERIFYPEER, 0)
	c.setopt(pycurl.POSTFIELDS, http_params)
	c.setopt(pycurl.HTTPHEADER, ["Host: %s" % repu_server])
	if proxy:
		c.setopt(pycurl.PROXY, proxy)
	c.perform()
	c.close()
	write_repu_log("Message-feedback: Information sent (all feedback step)")
except:
	write_repu_log("Error-feedback: Unable to send information (all feedback step)")
	exit_proc()

exit_proc()
