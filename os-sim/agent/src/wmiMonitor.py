#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

#
# GLOBAL IMPORTS
#
import os
import sys
import commands
import re

#
# GLOBAL VARIABLES
#
user = 'Administrador'
password = 'test'
machine = '192.168.1.224'

"""
This script requires wmi-client

Process
CLSID

Syntax:
option  value   user    password    machine
"""



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

    if existCLSID(sys.argv[2]):
        print "1"

#Process check
if sys.argv[1] == '-p':
    machine = sys.argv[3]

    if existProcess(sys.argv[2]):
        print "1"

#Service Check
if sys.argv[1] == '-s':
    machine = sys.argv[3]

    if existService(sys.argv[2]):
        print "1"

#User Check
if sys.argv[1] == '-u':
    machine = sys.argv[3]

    if existUser(sys.argv[2]):
        print "1"

