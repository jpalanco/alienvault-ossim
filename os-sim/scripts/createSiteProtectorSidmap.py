#!/usr/bin/env python
# encoding: utf-8

"""
createSiteProtectorSidmap.py

Create inventory correlation sid map for SiteProtector

Created by Jaime Blasco on 2010-02-24.
Copyright (c) 2009 Alienvault. All rights reserved.

You need ISS signature information, download it from http://www.iss.net/security_center/reference/vuln/XForceHelpFiles.zip

Then execute python createSiteProtectorSidmap.py XForceHelpFiles/

Warning: You need SiteProtector plugin sids on your database

"""

import fileinput
import os
import sys
import MySQLdb
import re

config_file = "/etc/ossim/framework/ossim.conf"
server = "localhost"

def readDbInfo():
    user = ''
    password = ''

    for line in fileinput.input(config_file):
        p = re.compile(r"ossim_pass=(?P<pass>\S+)")
        m = p.match(line)
        if (m):
            password = m.group(1)
        p = re.compile(r"ossim_user=(?P<user>\S+)")
        m = p.match(line)
        if (m):
            user = m.group(1)

    return user, password

def dbConn():
    db=MySQLdb.connect(host='%s' % server,user=dbUser , passwd=dbPass ,db='ossim')
    cursor=db.cursor()
    return cursor

def getSidsByPlugin(id):
    cursor = dbConn()
    sql="select name,sid from plugin_sid where plugin_id = %s" % id
    cursor.execute(sql)
    data = {}
    sids=cursor.fetchall()
    for s in sids:
    	data[s[0]] = s[1]
    return data

cred = readDbInfo()
dbUser = cred[0]
dbPass = cred[1]

plugins = getSidsByPlugin(1611)

def getOsSid(data):
	sids = []
	if data.find("Microsoft Windows") != -1:
		sids.append(1)
	if data.find("Linux") != -1:
		sids.append(2)
	if data.find("Unix") != -1:
		sids.append(14)
	if data.find("Mac OS") != -1:
		sids.append(10)
	if data.find("BSD") != -1:
		sids.append(4)
	if data.find("Solaris") != -1:
		sids.append(9)
	if data.find("Cisco IOS") != -1:
		sids.append(3)
	if data.find("IBM AIX") != -1:
		sids.append(13)
	if data.find("HP-UX") != -1:
		sids.append(8)
	return sids

def process(pSid, data):
	osList = getOsSid(data)
	if osList != []:
		for o in osList:
			print "replace into plugin_reference values (1611, %s, 3001, %d);" % (pSid, o)
	
d = sys.argv[1]
ids = os.listdir(d)
for i in ids:
	name = i.split(".")[0]
	name = "Siteprotector: %s" % name
	lines = []
	for line in fileinput.input('%s/%s' % (d, i)):
		lines.append(line)
	id = 0
	while id < len(lines):
		if lines[id].find("Systems affected") != -1:
			try:
			 	pSid = plugins[name]
				#print pSid
				process(pSid, lines[id+1])
			except:
				pass
		id = id +1

