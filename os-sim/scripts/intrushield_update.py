#!/usr/bin/python
import sys
import getopt

try:
    import MySQLdb
except ImportError:
    print "You need mysql-python support (http://sourceforge.net/projects/mysql-python).\n Try \"apt-get install python-mysqldb\" in Debian like distribuition."    
    sys.exit(1)

global plugin_id, \
	plugin_reliability, \
	intrushield_host, \
	intrushield_user, \
	intrushield_password, \
	intrushield_db, \
	ossim_host, \
	ossim_user, \
	ossim_password, \
	ossim_db

# GLOBAL VARS
plugin_id=1551
plugin_reliability=3

# INTRUSHIELD DATABASE CONFIG
intrushield_host='localhost'
intrushield_user='root'
intrushield_password=''
intrushield_db='intrushield'

# OSSIM DATABASE CONFIG
ossim_host='localhost'
ossim_user='root'
ossim_password=''
ossim_db='ossim'

#
#
#

def hextoint(string):
    try:
        return int(string, 16)
    except ValueError:
        pass

def intrushield_sid(mcafee_sid,mcafee_name):
    mcafee_sid = hextoint(mcafee_sid)/256
    mcafee_name = mcafee_name.replace('-',':')

    mcafee_subsid=abs(mcafee_name.__hash__())

    mcafee_hash2 = 0
    for i in range(0,len(mcafee_name)):
        mcafee_hash2 = mcafee_hash2 + ord( mcafee_name[i] )

    ossim_sid = int(str(mcafee_hash2)[-1:]+str(int(str(mcafee_subsid)[-7:])+mcafee_sid))

    return ossim_sid

#
#

def db_connect(host,user,passwd,db):
    try:
        connector=MySQLdb.connect(host=host,user=user,passwd=passwd,db=db)
    except:
        print 'Error connecting to database',db,'with user',user,'\n\n * Remember to edit',sys.argv[0],'before run\n      it and configure database variables.\n'

	sys.exit(1)
    
    cursor=connector.cursor()

    return connector, cursor

def get_intrushield_ref(cursor):
    
    sql='SELECT attack_id_ref, name, confidence FROM iv_signature'
    cursor.execute(sql)
    result=cursor.fetchall()

    if result:
        return result
    else:
        return False

def get_ossim_ref(cursor):

    sql='SELECT sid FROM plugin_sid WHERE plugin_id="'+str(plugin_id)+'"'
    cursor.execute(sql)
    result=cursor.fetchall()
    
    if result:
        a=[]
	for i in result:
	    a.append(int(i[0]))
        return a
    else:
        return False

#
#

def gen_insert_rule(sid, name, priority):

    sql='INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (' + str(plugin_id) + ',' + str(sid) + ',NULL,NULL,\'intrushield: ' + name + '\',' + str(priority) + ',' + str(plugin_reliability) + ');'
    return sql

def db_insert_rule(cursor, sid, name, priority):

    cursor.execute(gen_insert_rule(sid,name,priority))


#
#
def Usage ():
    print sys.argv[0],"[-s|-d] [-v][-a]\n",\
          "    -h,--help			Help :)\n",\
          "    -s,--sql				Output sids list in SQL to stdout\n",\
          "    -d,--database	  		Insert sids in OSSIM database\n",\
          "    -v,--verbose			Verbose, info about duplicated sids\n",\
          "    -a,--all				Remove old sids before insert\n\n",\
          "  * Remember to edit",sys.argv[0],"before run\n      it and configure database variables.\n"
    sys.exit(0)


def main():
    try:
        optlist, args = getopt.getopt(sys.argv[1:], ":sdvah", ["sql","database","verbose","all"])
    except getopt.GetoptError:
        Usage()
        sys.exit(1)

    # Options vars
    opt_sql = opt_db = opt_ver = opt_all = False

    # Stats vars
    collisions = 0
    inserted = 0
    
    list = []
    for opt, args in optlist:
        if opt in ('-s','--sql'):
	    opt_sql = True 
	elif opt in ('-h','--help'):
	    Usage()
	    sys.exit(0)
	elif opt in ('-d','--database'):
	    opt_db = True
	elif opt in ('-v', '--verbose'):
	    opt_ver = True
	elif opt in ('-a', '--all'):
	    opt_all = True

    if not opt_sql and not opt_db:
        print '-- No output selected, default SQL'
	opt_sql=True

    #
    # Connection to databases
    #
    intrushield_connector, intrushield_cur=db_connect(intrushield_host, intrushield_user, intrushield_password, intrushield_db)

    if opt_all:
        ossim_connector = ossim_cur = ossim_sids = False
        sql='DELETE FROM plugin_sid WHERE plugin_id=' + str(plugin_id)
        if opt_sql:
	    print sql
	if opt_db:
            ossim_connector, ossim_cur=db_connect(ossim_host, ossim_user, ossim_password, ossim_db)
	    ossim_cur.execute(sql)
	    ossim_connector.commit()
	    ossim_sids=[]
    else:
        ossim_connector, ossim_cur=db_connect(ossim_host, ossim_user, ossim_password, ossim_db)
        ossim_sids=get_ossim_ref(ossim_cur)

    refs=get_intrushield_ref(intrushield_cur)

    if not refs:
        print 'McAfee Intrushield iv_signature table not found'
        sys.exit(1)

    if not ossim_sids or not opt_all and not opt_db:
        print '-- OSSIM sids for Mcafee Intrushield not found, generating all'
        ossim_sids=[]

    for i in refs:
        sid=intrushield_sid(i[0],i[1])
        if (opt_all or not ossim_sids.__contains__(sid)) and not list.__contains__(sid):
            list.append(sid)
            sql=gen_insert_rule(sid, i[1], i[2])
	    inserted += 1
	    if opt_sql:
                print sql
	    if opt_db:
	        ossim_cur.execute(sql)
        else:
	    if list.__contains__(sid):
	        collisions += 1
	        if opt_ver:
                    print '-- Duplicated intrushield sid', i[1], '->', i[0]
                    print '--', gen_insert_rule(sid, i[1], i[2])

#
# Disconnect from database(s)
#
    intrushield_cur.close()
    intrushield_connector.commit()
    intrushield_connector.close()

    if not opt_all or opt_db:
        ossim_cur.close()
        ossim_connector.commit()
        ossim_connector.close()

#
# Print stats
#
    if opt_ver:
        print '-- Stats:\n--   Total McAfee Intrushield sids:',refs.__len__(),'\n--   OSSIM DB sids:',ossim_sids.__len__(),'\n--   Collisions/Duplicated:',collisions,'\n--   Total sids inserted:',inserted,'\n'
#
# 
#
if __name__ == "__main__":
    main()

