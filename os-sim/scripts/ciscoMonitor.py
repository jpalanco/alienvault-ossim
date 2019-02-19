'''
This script requires snmpwalk
You need a list of valid devices/communities
'''

import os
import sys
import commands
import re

device = '192.168.1.252'
comm = 'public'


#Returns number of entries on arp table
def getArpTableCount():
	data = commands.getstatusoutput('snmpwalk -Os -c %s -v 1 %s .1.3.6.1.2.1.4.22.1.4' % (comm, device))
	data = data[1].split('\n')
	return len(data)

#Returns cpu usage
def getCpuUtil():
	data = commands.getstatusoutput('snmpwalk -Os -c %s -v 1 %s .1.3.6.1.4.1.9.2.1.57' % (comm, device))
	p = re.compile(r'.*INTEGER:\s(?P<cpu>\d+)$')
    	m = p.match(data[1])
    	if (m):
    		return m.group(1)
	else:
		return None
		
#Returns memory usage
def percentMemoryUsed():
	used = commands.getstatusoutput('snmpwalk -Os -c %s -v 1 %s 1.3.6.1.4.1.9.9.48.1.1.1.5.1' % (comm, device))
	used = used[1]
	p = re.compile(r'.*Gauge32:\s(?P<cpu>\d+)$')
    	m = p.match(used)
    	if (m):
    		used = m.group(1)
	free = commands.getstatusoutput('snmpwalk -Os -c %s -v 1 %s 1.3.6.1.4.1.9.9.48.1.1.1.6.1' % (comm, device))
	free = free[1]
	p = re.compile(r'.*Gauge32:\s(?P<cpu>\d+)$')
    	m = p.match(free)
    	if (m):
    		free = m.group(1)
	total = int(free) + int(used)
	return (int(used) * 100)/total

#Return number of active TTY's	
def getActiveTTY():
	ttys = commands.getstatusoutput('snmpwalk -Os -c %s -v 1 %s 1.3.6.1.4.1.9.2.9.2.1.1' % (comm, device))
	num = 0
	for tty in ttys[1].split('\n'):
		if tty[len(tty)-1] == "1":
			num = num +1
	return num

if len(sys.argv) > 1:
	#Number of ARP entries
	if sys.argv[1] == '-arp':
		device = sys.argv[2]
		getArpTableCount()
		
	#CPU Utilization
	if sys.argv[1] == '-cpu':
		device = sys.argv[2]
		getCpuUtil()
	
	#Percent Memory Used
	if sys.argv[1] == '-memory':
		device = sys.argv[2]
		percentMemoryUsed()
	
	#Number of Active ttys
	if sys.argv[1] == '-tty':
		device = sys.argv[2]
		getActiveTTY()
else:
	print "Usage:\n\t-arp device\n\t-cpu device\n\t-memory device\n\t-tty device\n"
