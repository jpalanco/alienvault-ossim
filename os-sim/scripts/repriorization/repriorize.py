#!/usr/bin/python
# -*- coding: utf-8 -*-

import os
import sys
import re
import socket
import MySQLdb
import fileinput
import commands
import time
import datetime
import random
from priodata import prio
from sources import sources

category = {}
subcategory = {}
config_file = "/etc/ossim/framework/ossim.conf"
server = "localhost"

taxonomyIDS = {}

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


# GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'temporal';
def dbConn():
    db=MySQLdb.connect(host='%s' % server,user=dbUser , passwd=dbPass ,db='ossim')

    cursor=db.cursor()
    return cursor

def readCategory():
	cursor = dbConn()
	sql = "select id, name from category;"
	cursor.execute(sql)
	sids=cursor.fetchall()
	for s in sids:
		category[s[0]] = s[1]
	cursor.close()

def readSubCategory():
	cursor = dbConn()
	sql = "select id, name from subcategory;"
	cursor.execute(sql)
	sids=cursor.fetchall()
	for s in sids:
		subcategory[s[0]] = s[1]
	cursor.close()

def fill():
	cursor = dbConn()
	sql = "select cat.name, sub.name, sub.cat_id, sub.id from category as cat, subcategory as sub where sub.cat_id = cat.id order by cat.name;"
	cursor.execute(sql)
	sids=cursor.fetchall()
	for s in sids:
		taxonomyIDS["%s/%s" % (s[0], s[1])] = [s[2], s[3]]
	cursor.close()
	
def show():
	cursor = dbConn()
	sql = "select cat.name, sub.name from category as cat, subcategory as sub where sub.cat_id = cat.id order by cat.name;"
	cursor.execute(sql)
	cats = cursor.fetchall()
	cursor.close()
	for c in cats:
		print "prio['%s/%s'] = " % (c[0], c[1])

def update(id, sid, prio, rel=1):
	print "UPDATE IGNORE plugin_sid set priority = %d, reliability = %d where plugin_id = %d and sid = %d;" % (prio, rel, id, sid)
	
cred = readDbInfo()
dbUser = cred[0]
dbPass = cred[1]

readCategory()
readSubCategory()
#show()
fill()

cursor = dbConn()
sql = "select ps.plugin_id, ps.sid, ps.category_id, ps.subcategory_id, ps.name, p.source_type from plugin_sid as ps, plugin as p where ps.plugin_id = p.id"
cursor.execute(sql)
sids = cursor.fetchall()
cursor.close()
for s in sids:
	#print s[4]
	#print s[2], s[3]
	if s[2] and s[3]:
		#print "%s/%s" % (category[s[2]], subcategory[s[3]])
		rel = 1
		try:
		    rel = sources[s[5]]
		except:
		    pass
		#print s[2]
		#print s[3]
		update(s[0], s[1], prio["%s/%s" % (category[s[2]], subcategory[s[3]])], rel)
		




