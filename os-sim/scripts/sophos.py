import pymssql
import sys
import string
import os
import time
import socket
import re
import signal

dbhost = "<sophos_console_ip>"
dbuser = "<sophos_database_user>"
dbpass = "<sophos_database_user_password>"
database = "<sophos_database>"

fetch_interval = 2

status_file = "/var/run/sophos_" + dbhost + ".status"
log_file = "/var/log/ossim/sophos_" + dbhost + ".log"

start_id = "0"

if len(sys.argv) == 2:
  if sys.argv[1] == "-d":
	pid = os.fork()
	if pid:
	  sys.exit()


con = pymssql.connect(host=dbhost,user=dbuser,password=dbpass,database=database)
cur = con.cursor()

def numToDottedQuad(n):
    
    d = 256 * 256 * 256
    q = []
    while d > 0:
        m,n = divmod(n,d)
        q.append(str(m))
        d = d/256
    a = q[0].split(".")[0] + "." + q[1].split(".")[0] + "." + q[2].split(".")[0] + "." + q[3].split(".")[0]
    return a
    

#if len(sys.argv) == 2:
#  query=sys.argv[1]
#else:

if not os.path.exists(status_file):
  fd = open(status_file, 'w')
  fd.close()
else:
  fd = open(status_file)
  for l in fd:
    start_id = l
    break


if not os.path.exists(log_file):
  fd = open(log_file, 'w')
  fd.close()


while True:
  query="select threatinstanceid, threatname, threattype, threatsubtype, priority, fullfilepath, firstdetectedat, name, ipaddress from threats, computersanddeletedcomputers where threats.computerid = computersanddeletedcomputers.id and threatinstanceid > " + start_id

  fd = open(log_file, "a")

  try:
    cur.execute(query)	
  except pymssql.DatabaseError, e:
    fd.write("Database Error, reconnecting.\n")
    time.sleep(5)
    try:
      con = pymssql.connect(host=dbhost,user=dbuser,password=dbpass,database=database)
      cur = con.cursor()
      cur.execute(query)	
    except:
      pass

  logline = ""
  for record in cur.fetchall():
    logline = "%d||%s||%d||%d||%d||%s||%s||%s||%s\n" % (record[0], record[1], record[2], record[3], record[4], record[5], record[6], record[7], numToDottedQuad(record[8]))
    if logline is not "":
      fd.write(logline)
  fd.close()

  try:
    start_id = str(record[0])
    fd_stats = open(status_file, "w")
    fd_stats.write(start_id)
    fd_stats.flush()
    fd_stats.close
  except NameError:
    pass

  time.sleep(fetch_interval)


con.commit()
con.close()
