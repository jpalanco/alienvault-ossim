'''
Ossim Action Responses Framework (Cisco)
This script requires snmpset
You need a list of valid devices/communities
'''

import os
import sys
import commands
import re

device = '192.168.1.252'
comm = 'private'

#Close a specific tty
def closeTTY(tty):
	os.system('snmpset -c %s -v 1 %s 1.3.6.1.4.1.9.2.9.10.0 integer %s' % (comm, device, tty))	

#Clear ARP entry
def clearARP(ip):
	os.system('snmpset -c %s -v 1 %s .1.3.6.1.2.1.4.22.1.4.2.%s integer 2' % (comm, device, ip))	
	
#Shutdown device
#Requires snmp-server system-shutdown 
def shutdown():
	os.system('snmpset -c %s -v 1 %s .1.3.6.1.4.1.9.2.9.9.0 i 2' % (comm, device))
		
if len(sys.argv) > 1:
	#Close tty
	if sys.argv[1] == '-closeTTY':
	    device = sys.argv[3]
	    tty = sys.argv[2]
	    closeTTY(tty)
	    
	#Clear ARP entry
	if sys.argv[1] == '-clearARP':
		device = sys.argv[3]
		ip = sys.argv[2]
		clearARP(ip)
		
	#Shuwtdown device
	if sys.argv[1] == '-shutdown':
		device = sys.argv[2]
		shutdown()

else:
	print "Usage:\n\t-closeTTY tty device\n\t-clearARP ip device\n\t-shutdown device"	

	
