'''
This script requires wmi-client
You have to configure user and password with a valid Admin account for your windows network/domain
'''

import os
import sys
import commands
import re

'''
Process
CLSID

Sintax:
option  value   user    password    machine
'''

user = ''
password = ''
machine = ''

def getCLSIDS():
    data = commands.getstatusoutput('wmic -U %s%%%s //%s "SELECT * FROM Win32_COMClass"' % (user, password, machine))
    data = data[1].split("\n")
    clsids = []
    for l in data:
        try:
            l = l.split('|')
            clsids.append(l[1])        
        except:
            pass
    return clsids

def existCLSID(clsid):
    if clsid in getCLSIDS():
        return True
    
def getProcesses():
    data = commands.getstatusoutput('wmic -U %s%%%s //%s "SELECT Name FROM Win32_Process"' % (user, password, machine))
    data = data[1].split("\n")
    process = []
    for l in data:
        try:
            l = l.split('|')
            process.append(l[1])        
	    #print l[1]
        except:
            pass
    return process

def getServices():
    data = commands.getstatusoutput('wmic -U %s%%%s //%s "SELECT Name FROM Win32_Service"' % (user, password, machine))
    data = data[1].split("\n")
    services = []
    for s in data:
            services.append(s)        
    return services
    
def getLoggedUsers():
    data = commands.getstatusoutput('wmic -U %s%%%s //%s "SELECT * FROM Win32_LoggedOnUSer"' % (user, password, machine))
    data = data[1].split("\n")
    users = []
    for l in data:
        p = re.compile(r'.*Name="(?P<user>\S+)\"\|.*')
        m = p.match(l)
        if (m):
            users.append(m.group(1))
    return users
    
def existService(proc):
    if proc in getServices():
        return True
        
def existProcess(proc):
    if proc in getProcesses():
        return True

def existUser(user):
    if user in getLoggedUsers():
        return True
    
#clsid check
if sys.argv[1] == '-c':
    machine = sys.argv[3]
    clsid = "{%s}" % (sys.argv[2])
    if existCLSID(clsid):
        print "1"
    else:
	print "0"

#Process check
if sys.argv[1] == '-p':
    machine = sys.argv[3]
    if existProcess(sys.argv[2]):
        print "1"
    else:
	print "0"
        
#Service Check
if sys.argv[1] == '-s':
    machine = sys.argv[3]
    if existService(sys.argv[2]):
        print "1"
    else:
	print "0"

#User Check
if sys.argv[1] == '-u':
    machine = sys.argv[3]
    if existUser(sys.argv[2]):
        print "1"
    else:
	print "0"
    
