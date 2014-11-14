import MySQLdb
import fileinput
import re

config_file = "/etc/ossim/framework/ossim.conf"

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
    db=MySQLdb.connect(host='localhost',user=dbUser , passwd=dbPass ,db='ossim')
    cursor=db.cursor()
    return cursor

def getAlarms():
	cursor = dbConn()
	sql="select risk, INET_NTOA(src_ip), INET_NTOA(dst_ip), src_port, dst_port, protocol, backlog_id, timestamp from alarm"
	cursor.execute(sql)
	alarms= cursor.fetchall()
	if alarms:
		for a in alarms:
			print "*****************************ALARMS************************************"
			print a[7]
			print "Risk: ",a[0]
			print a[1],":",a[3],"----------->",a[2],":",a[4],"	",a[5],"\n"
			sql = "select  e.plugin_id, p.name, INET_NTOA(e.src_ip), INET_NTOA(e.dst_ip), e.src_port, e.dst_port, e.timestamp  from backlog_event as b, alarm as a, event as e, plugin_sid as p where a.backlog_id = b.backlog_id and e.id=b.event_id and p.sid =e.plugin_sid and p.plugin_id = e.plugin_id and a.backlog_id = %s  order by timestamp desc;" % (a[6])
			cursor.execute(sql)
			evs=cursor.fetchall()
			for e in evs:
				print "\t",e[1],"\t",e[6],"\n"
				print "\t\t",e[2],":",e[4],"------------->",e[3],":",e[5],"\n"
	else:
		print "No alarms to display"
	
getAlarms()
	

	
