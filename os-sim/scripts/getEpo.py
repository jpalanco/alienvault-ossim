#!/usr/bin/env python
# encoding: utf-8
'''
Python script to extract information from Mcaffee EPO
Cesar Fernandez

'''
import sys
try:
	import pymssql
except:
	print "You need pymssql to run this script"
	sys.exit(0)

import socket
import struct
import time
import os
import commands

#Extract extra data from each of the events
def getEventData(sID):
    #sql1 = "select * from SensorDataAVP1 where SensorDataID = %s" % sID
    sql1 = "select CONVERT(nvarchar(200), AttributeName), CONVERT(nvarchar(200), AttributeValue) from SensorDataAVP1 where SensorDataID = %s" % sID
    cursor.execute(sql1)
    ret = cursor.fetchall()
    data = ""
    for i in ret:
    	data = data + "%s:%s, " % (i[0], i[1])
    return data


#Check if there us another instance running
def checkIfRunning():
	pid = os.getpid()
	print "print pid of new proc:%s" % pid
	data = commands.getstatusoutput('ps auxwwwwwwwwww|grep getEpo|grep -v grep')
	data2 = data[1].split('\n')
	if data[1].find("/usr/share/ossim/scripts/getEpo.py") != -1 and len(data2) > 1:
        	print "Exist, exiting"
        	sys.exit(0)
	else:
        	print "getEpo.py process didn't existed before this one, continue"

#Connect to Database
def connectToDB():
        db = pymssql.connect(host="10.10.10.10:10", user="test", password="test", database="test")
	cursor=db.cursor()
	return cursor

#Initial query
def getFirstID():
	cursor = connectToDB()
	sql = "select top 1 AutoID from EPOEvents order by AutoID desc"
	cursor.execute(sql)
	row= cursor.fetchone()
	cVal = int(row[0])
	cursor.close()
	#print "ultimo valor insertado %s" % cVal
	return cVal


def extractData(sID):
    #sql1 = "select CONVERT(nvarchar(200), AttributeName), CONVERT(nvarchar(200), AttributeValue) from SensorDataAVP1 where SensorDataID = %s" % sID
    sql1 = "select * from [EPOEvents] where AutoID = %s" % sID
    #print sql1
    cursor.execute(sql1)
    ret = cursor.fetchall()
    return ret

def countEvents(sID):
   ret = extractData(sID)
   for i in ret:
        #print i[0]
        if i[0] == ":repeat-count":
                return int(i[1])
   return 1



checkIfRunning()

cVal = getFirstID()
while 1:
	conn = False
	#TODO cada vez hay que reconectar ? 
	while not conn:
		try:
			cursor = connectToDB()
			conn = True
			#print "conneced!"
		except:
			print "Error connecting to MSSQL Server......"
			time.sleep(10)
		
        #KB66342 from kc.mcafee.com
        #SELECT [AutoID],[AnalyzerIPV4] ,CAST([AnalyzerIPV4] + 2147483648 AS VARCHAR) ,[TargetIPV4] + 2147483648 AS [TargetIPV4] FROM [dbo].[EPOEvents]"
        sql="select AutoID, DetectedUTC, Analyzer, TargetHostName,      \
             CAST(TargetIPV4 + 2147483648 AS VARCHAR),                  \
             ThreatCategory, ThreatEventID, ThreatSeverity, ThreatName, \
             CAST(SourceIPV4 + 2147483648 AS VARCHAR)                   \
	     from EPOEvents where AutoID > %s" % cVal
        cursor.execute(sql)
        ret = cursor.fetchall()
        if ret and len(ret) > 0:
                #print "viejo cVal %s, nuevo %s" % (cVal,int(ret[len(ret) - 1][0]))
        	cVal = int(ret[len(ret) - 1][0])
	#print "Vamos a imprimir resultados, %s" % len (ret)
        for e in ret:
                f=open('/var/log/ossim/epo.log', 'a')
                #print " \n %s" % str(e)
		try:
			AutoID          = str(e[0])
			DetectedUTC     = e[1]
			Analyzer        = str(e[2])
			TargetHostName  = str(e[3])
			try:
				#print "TargetIPV4 %s" % e[4]
				TargetIPV4 = socket.inet_ntoa(struct.pack('!L',(int(e[4]))))
			except:
                        	if len(str(e[4])) > 8:
					TargetIPV4 = str(e[4])
				else:
					TargetIPV4 = "0.0.0.0"
			ThreatCategory  = str(e[5])
			ThreatEventID   = int(e[6])
			ThreatSeverity  = int(e[7])
			ThreatName      = str(e[8].replace("\n", "")) 
			
			try:
				SourceIPV4 = socket.inet_ntoa(struct.pack('!L',int(e[9])))
			except:
				if len(str(e[9])) > 8:
                                        SourceIPV4 = str(e[9])
                                else:
                                        SourceIPV4 = "0.0.0.0"

			#f.write("%s,%s,%s,%s,%s,%s,%s,%s,%s\n" % (evId, evName, evDate, evSensor, evSrc, evDst, evSrcP, evDstP, data))
			f.write("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n" % (AutoID,
								     DetectedUTC,
								     Analyzer,
								     TargetHostName,
								     TargetIPV4,
								     ThreatCategory,
								     ThreatEventID,
								     ThreatSeverity,
								     ThreatName,
								     SourceIPV4))
		except Exception,e:
			print "Error %s" % e
			pass
		
		f.close()
	cursor.close()
	time.sleep(5)


