'''
Ossim Action Responses Framework
This script requires wmi-client and wmiexec
You have to configure user and password with a valid Admin account for your windows network/domain
rundll32 user32.dll MessageBox "My text"

'''

import os
import sys
import commands
import re

user = 'Administrador'
password = ''
machine = ''
	
def nullRoute(ip):
	os.system('winexe -U %s%%%s //%s "route add %s mask 255.255.255.255 %s"' % (user, password, machine, ip, machine))
	pass
	
def blockComputer():
	os.system('winexe -U %s%%%s //%s "Rundll32.exe user32.dll,LockWorkStation"' % (user, password, machine))
	pass
	
def haltComputer():
	os.system('winexe -U %s%%%s //%s "Shutdown.exe -s -t 00"' % (user, password, machine))
	pass
	
def restartComputer():
	os.system('winexe -U %s%%%s //%s "Shutdown.exe -r -t 00"' % (user, password, machine))
	pass
	
def sleepComputer():
	os.system('winexe -U %s%%%s //%s "Rundll32.exe powrprof.dll,SetSuspendState Sleep"' % (user, password, machine))
	pass

def hibernateComputer():
	os.system('winexe -U %s%%%s //%s "Rundll32.exe powrprof.dll,SetSuspendState"' % (user, password, machine))
	pass

def killProcess(process):
	os.system('winexe -U %s%%%s //%s "taskkill /IM %s"' % (user, password, machine, process))
	pass
	
def startService(service):
	os.system('winexe -U %s%%%s //%s "net start %s"' % (user, password, machine, service))
	pass

def stopService(service):
	os.system('winexe -U %s%%%s //%s "net stop %s"' % (user, password, machine, service))
	pass
	
#Requires Msessenger service to be started
def sendMessage(msg):
	os.system('winexe -U %s%%%s //%s "net send %s %s"' % (user, password, machine, machine, msg))
	pass

def flushDNS():
	os.system('winexe -U %s%%%s //%s "ipconfig /flushdns"' % (user, password, machine))

if len(sys.argv) > 1:
	#nullroute
	if sys.argv[1] == '-nullRoute':
	    machine = sys.argv[3]
	    ip = sys.argv[2]
	    nullRoute(ip)
	
	#block computer
	if sys.argv[1] == '-block':
	    machine = sys.argv[2]
	    blockComputer()
	    
	#halt computer
	if sys.argv[1] == '-halt':
	    machine = sys.argv[2]
	    haltComputer()
	    
	#restart computer
	if sys.argv[1] == '-restart':
	    machine = sys.argv[2]
	    restartComputer()
	    
	#sleep computer
	if sys.argv[1] == '-sleep':
	    machine = sys.argv[2]
	    sleepComputer()
	    
	#hibernate computer
	if sys.argv[1] == '-hibernate':
	    machine = sys.argv[2]
	    hibernateComputer()
	    
	#kill a process
	if sys.argv[1] == '-kill':
	    machine = sys.argv[3]
	    process = sys.argv[2]
	    killProcess(process)
	
	#Start Service
	if sys.argv[1] == '-startService':
	    machine = sys.argv[3]
	    service = sys.argv[2]
	    startService(service)
	
	#Stop Service
	if sys.argv[1] == '-stopService':
	    machine = sys.argv[3]
	    service = sys.argv[2]
	    stopService(service)
	
	#Send Message
	if sys.argv[1] == '-sendMsg':
	    machine = sys.argv[3]
	    msg = sys.argv[2]
	    sendMessage(msg)

	#FlushDNS
	if sys.argv[1] == '-flushDNS':
	   machine = sys.argv[2]
	   flushDNS()
	        
else:
	print "Usage:\n\t-nullRoute ip machine\n\t-block machine\n\t-halt machine\n\t-restart machine\n\t-sleep machine\n\t-hibernate machine\n\t-kill process machine" \
		  "-startService service machine\n\t-stopService service machine\n\t-sendMsg msg machine\n\t-flushDNS machine\n"
