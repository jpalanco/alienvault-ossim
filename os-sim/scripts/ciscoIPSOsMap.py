#!/usr/bin/env python
# encoding: utf-8

"""
createCiscoIPSSidmap.py

Create inventory correlation sid map for Cisco IPS 

Created by Jaime Blasco on 2009-10-22.
Copyright (c) 2009 Alienvault. All rights reserved.
"""

import re
import sys
import socket
import xml.dom.minidom

try:
	name = sys.argv[1]
except:
	print "Usage: ./ciscoIPSOsMap.py IPSFile.xml\n"
	sys.exit(0)


def getOsSid(data):
	if data.find("win") != -1:
		return 1
	elif data.find("linux") != -1:
		return 2
	elif data.find("unix") != -1:
		return 14
	elif data.find("mac-os") != -1:
		return 10
	elif data.find("bsd") != -1:
		return 4
	elif data.find("solaris") != -1:
		return 9
	elif data.find("ios") != -1:
		return 3
	elif data.find("aix") != -1:
		return 13
	elif data.find("hp-ux") != -1:
		return 8

doc = xml.dom.minidom.parse(name)

osMap = {}
#Parse definitions
defs = doc.getElementsByTagName('mapEntry')

for d in defs:
	xsid = None
	xos = None
	sid = d.getElementsByTagName('key')[0].getElementsByTagName('var')[0]
	xsid = sid.firstChild.data
	sids = d.getElementsByTagName('var')
	for v in sids:
		for k in v.attributes.keys():
			if v.attributes[k].value == "vulnerable-os":
				try:
					xos = v.firstChild.data
				except:
					pass
					
	if xsid and xos:
		oses = xos.split('|')
		for o in oses:
			osVal = getOsSid(o)
			if osVal:
				print "replace into plugin_reference values (1597, %s, 3001, %d);" % (xsid, osVal)

		