#!/usr/bin/env python
# encoding: utf-8
"""
Created by Jaime Blasco on 2009-09-14

License:

Copyright (c) 2009 AlienVault
All rights reserved.

This package is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 dated June, 1991.
You may not use, modify or distribute this program under any other version
of the GNU General Public License.

This package is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this package; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
MA  02110-1301  USA


On Debian GNU/Linux systems, the complete text of the GNU General
Public License can be found in `/usr/share/common-licenses/GPL-2'.

Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
"""

import MySQLdb
import fileinput
import re
import xml.dom.minidom
from xml.dom.minidom import parse, parseString
import sys, smtplib, string
import time

config_file = "/etc/ossim/framework/ossim.conf"
fromaddr = ''
toaddrs  = ''
cliente = ""
actevents = []

def readDbInfo():

    user = ''
    password = ''

    for line in fileinput.input(config_file):
        p = re.compile(r"ossim_pass=(?P<pass>\S+)")
        m = p.match(line)
        if (m):
            password = m.group(1)
        p = re.compile(r"ossim_user=(?P<user>\S+)")
        m = p.match(line)
        if (m):
            user = m.group(1)

    return user, password

cred = readDbInfo()
dbUser = cred[0]
dbPass = cred[1]

def dbConn():
    db=MySQLdb.connect(host='localhost',user=dbUser , passwd=dbPass ,db='snort')
    cursor=db.cursor()
    return cursor
        
        
def fillEvents():
    cursor = dbConn()
    sql = 'select distinct sig_name from acid_event;'
    cursor.execute(sql)
    events= cursor.fetchall()
    for e in events:
      actevents.append(e[0])
    cursor.close()
    
def checkEvents():
    print "Checking database"
    cursor = dbConn()
    sql = 'select distinct sig_name from acid_event;'
    cursor.execute(sql)
    events= cursor.fetchall()
    msg = ''
    for e in events:
      if e[0] not in actevents:
        print e[0]
        actevents.append(e[0])
        msg = msg + e[0] + "\n"
    cursor.close()
    if msg != '':
      sendmail(cliente + "\n" + msg)
    
def sendmail(msg):
    server = smtplib.SMTP('localhost')
    server.sendmail(fromaddr, toaddrs, msg)
    server.quit()
 
sendmail(cliente + "\n" + "Init Checks\n")
fillEvents()
while 1:
  checkEvents()
  time.sleep(600)
  
  
