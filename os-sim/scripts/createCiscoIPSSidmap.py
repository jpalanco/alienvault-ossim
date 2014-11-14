#!/usr/bin/env python
# encoding: utf-8

"""
createCiscoIPSSidmap.py

Create sid values for Cisco IPS Plugin 

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
	print "Usage: ./createCiscoIPSSidmap.py IPSFile.xml\n"
	sys.exit(0)

doc = xml.dom.minidom.parse(name)

rules = {}

#Risk Conversion Table
riskTable = {'high': 3, 'informational': 1, 'low': 2}

#Reliability Conversion Function
def getReliability(val):
	if val < 51:
		return 2
	if val < 81:
		return 3
	return 4

#Parse definitions
defs = doc.getElementsByTagName('mapEntry')

for d in defs:
	sId = None
	sName = None
	sSeverity = None
	sReliablity = None
	sid = d.getElementsByTagName('key')[0].getElementsByTagName('var')[0]
	sId = sid.firstChild.data   

	stru = None
	try:
		stru = d.getElementsByTagName('struct')[0]
	except:
		pass
	if stru:
		vars = stru.getElementsByTagName('var')
		try:
			for v in vars:
				for k in v.attributes.keys():
					if v.attributes[k].value == "sig-name":
						sName =  v.firstChild.data.replace("'", "")
					elif v.attributes[k].value == "alert-severity":
						sSeverity = v.firstChild.data
						sSeverity = riskTable[sSeverity]
					elif v.attributes[k].value == "sig-fidelity-rating":
						sReliablity = v.firstChild.data
						sReliablity = getReliability(sReliablity)
		except:
			pass
	if not sId or not sName or not sSeverity or not sReliablity:
		pass
	else:
		try:
			rules[sId]
		except:
			rules[sId] = (sId, sName, sSeverity, sReliablity)

print 'DELETE FROM plugin WHERE id = "1597";\n'
print 'DELETE FROM plugin_sid where plugin_id = "1597";\n'
print "INSERT INTO plugin (id, type, name, description) VALUES (1597, 1, 'Cisco-IPS', 'Cisco Intrusion Prevention System');\n"

for r in rules.keys():
	print "INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1597, %s, NULL, NULL, 'Cisco-IPS: %s', %d, %d);" % (r, rules[r][1], rules[r][2], rules[r][3])
	
