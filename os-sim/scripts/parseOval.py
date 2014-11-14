'''
This script parse Oval data to generate plugin sids
You could get the last xml definitions file from:
http://oval.mitre.org/rep-data/org.mitre.oval/v/oval.xml
'''
import re
import sys
import socket
import xml.dom.minidom
import sys

name = sys.argv[1]

doc = xml.dom.minidom.parse(name)
defs = doc.getElementsByTagName('definition')

for d in defs:
	id = d.getAttribute('id')
	id = id.split(':')[3]
	name = d.getElementsByTagName('metadata')[0].getElementsByTagName('title')[0].lastChild.nodeValue.replace('\n', ' ').replace('\t', ' ').replace("'", "")
	print "INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1585, %s, NULL, NULL, 'Oval: %s');" % (id, name)
