'''
Script to update plugin sids from Tippingpoint Database
'''
import os, sys, re
import MySQLdb
import fileinput

#Credentials to Access Tippingpoint Database
db_ip = "localhost"
db_user = "root"
db_pass = "60d1baebf0"

config_file = "/etc/ossim/framework/ossim.conf"

sids = ()

def dbConn():
    db=MySQLdb.connect(host=db_ip, user=db_user , passwd=db_pass ,db='ExternalAccess')
    cursor=db.cursor()
    return cursor

def getData():
	cursor = dbConn()
	sql = "select NUM,SEVERITY,NAME from SIGNATURE;"
	cursor.execute(sql)
	sids= cursor.fetchall()
	return sids

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

def OssimdbConn():
    db=MySQLdb.connect(host='localhost',user=dbUser , passwd=dbPass ,db='ossim')
    cursor=db.cursor()
    return cursor

cred = readDbInfo()
dbUser = cred[0]
dbPass = cred[1]

def insertData(sids):
	#INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1612, 0291, NULL, NULL, 'Invalid TCP Traffic: Possible nmap Scan (FIN no ACK)', 2, 1);
	cursor = OssimdbConn()
	for s in sids:
		name = s[2]
		mre = re.compile("\d+:\s(.*)")
		m = mre.match(name)
		if m:
			name = m.group(1)
		rel = s[1]
		sid = s[0]
		sql = "REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1612, %d, NULL, NULL, '%s', %d, 2);" % (sid, name, rel)
		cursor.execute(sql)

print "Retrieving data from Tippingpoint Database.."
sids = getData()
print sids
insertData(sids)
