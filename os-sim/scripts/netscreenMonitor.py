'''
Netscreen Monitor
This script requires snmpwalk
You need a list of valid devices/communities
'''

import os
import sys
import commands
import re

device = '192.168.1.252'
comm = 'public'

#Returns cpu usage
def getCpuUtil():
	data = commands.getstatusoutput('snmpwalk -Os -c %s -v 1 %s .1.3.6.1.4.1.3224.16.1.2.0' % (comm, device))
	p = re.compile(r'.*INTEGER:\s(?P<cpu>\d+)$')
    	m = p.match(data[1])
    	if (m):
    		return m.group(1)
	else:
		return None
		
#Returns memory usage
def percentMemoryUsed():
	used = commands.getstatusoutput('snmpwalk -Os -c %s -v 1 %s .1.3.6.1.4.1.3224.16.2.1.0' % (comm, device))
	used = used[1]
	p = re.compile(r'.*Gauge32:\s(?P<cpu>\d+)$')
    	m = p.match(used)
    	if (m):
    		used = m.group(1)
	free = commands.getstatusoutput('snmpwalk -Os -c %s -v 1 %s .1.3.6.1.4.1.3224.16.2.2.0' % (comm, device))
	free = free[1]
	p = re.compile(r'.*Gauge32:\s(?P<cpu>\d+)$')
    	m = p.match(free)
    	if (m):
    		free = m.group(1)
	total = int(free) + int(used)
	return (int(used) * 100)/total

#Returns percentage of sessions
def percentSessions():
	used = commands.getstatusoutput('snmpwalk -Os -c %s -v 1 %s 1.3.6.1.4.1.3224.16.3.2.0' % (comm, device))
	used = used[1]
	p = re.compile(r'.*Gauge32:\s(?P<cpu>\d+)$')
    	m = p.match(used)
    	if (m):
    		used = m.group(1)
	total = commands.getstatusoutput('snmpwalk -Os -c %s -v 1 %s 1.3.6.1.4.1.3224.16.3.3.0' % (comm, device))
	total = total[1]
	p = re.compile(r'.*Gauge32:\s(?P<cpu>\d+)$')
    	m = p.match(total)
    	if (m):
    		total = m.group(1)
	return (int(used) * 100)/int(total)

if len(sys.argv) > 1:
	#CPU Utilization
	if sys.argv[1] == '-cpu':
		device = sys.argv[2]
		getCpuUtil()
	
	#Percent Memory Used
	if sys.argv[1] == '-memory':
		device = sys.argv[2]
		percentMemoryUsed()
	
	#Percent of active sessions
	if sys.argv[1] == '-sessions':
		device = sys.argv[2]
		percentSessions()
		
else:
	print "Usage:\n\t-cpu device\n\t-memory device\n\t-sessions device\n"
	

