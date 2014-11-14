import sys
import os
import fileinput
#import MySQLdb
import re
import json
import sys

sids = []
lost = []

def parseLine(line):
    #Reject commented rules
    if line[0] == '#':
        #return None
    	line = line[1:len(line)-1]
    #Reject no alert actions
    if line[0:5] != 'alert':
        return None
    p = re.compile(r".*msg:\"\s*([^;]+)\";")
    m = p.match(line)
    if (m):
	    name = m.group(1)
	    #print name
	    p = re.compile(r".*sid:\s*([^;]+);")
	    m = p.match(line)
	    sid = m.group(1)
	    #print sid
	    p = re.compile(r".*[classtype|category]:\s*([^;]+);")
	    m = p.match(line)
	    classtype = m.group(1)
	    #print classtype
	    p = re.compile(r"^ET\s([^\s]+).*")
	    m = p.match(name)
	    if (m):
		    group = m.group(1)
	    else:
		    p = re.compile(r"^([^\s]+).*")
		    m = p.match(name)
		    if (m):
			    group = m.group(1)
			    #print group
	    data = [name,sid,classtype,group]
	    return data
	    #print data


snort_dir = sys.argv[1]
snort_files = []
rules = {}
#print "Looking for snort rule files:"
for root, dirs, files in os.walk(snort_dir, topdown=True):
	for name in files:
		p = re.compile(r".*rules")
		m = p.match(name)
		if (m):
			#print "\t" + m.group(0)	
			snort_files.append(m.group(0))
for fi in snort_files:
	#[name,sid,classtype,group]
	for line in fileinput.input(snort_dir + fi):
		data = parseLine(line)
		if data:
			print "INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1003, %s, NULL, NULL, '%s')" % (data[1], data[0])
