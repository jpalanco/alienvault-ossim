#!/usr/bin/python

import MySQLdb
import sys,os
from ConfigParser import ConfigParser
from subprocess import Popen,PIPE
class OssimTestDB:
	"This class handle the db for ossim testing"
	schemaDir = os.path.join(os.pardir,os.pardir,"db")
	schemaFiles={"ossimdb_test": ["00-create_ossim_tbls_mysql.sql"],
		"snortdb_test":["00-create_snort_tbls_mysql.sql"],
		"ossim_acl_test":["00-create_ossim_acl_tbls_mysql.sql"],
	}
			

	def __init__(self):
		self.inifile ="db.ini"
		self.conn = None
		self.databases = ("ossimdb_test","snortdb_test","ossim_acl_test")
		try:
			fd = open(self.inifile,"r")
			conf = ConfigParser()
			conf.readfp(fd)
			self.dbhost = conf.get("db","host")
			self.dbport = int(conf.get("db","port"))
			self.dbuser = conf.get("db","user")
			self.dbpass = conf.get("db","pass")
		except IOError:
				print "No config file"
				sys.exit(-1)
	def checkConnect(self,db=""):
		r = False
		if self.conn<>None:
			self.conn.close()
			self.conn = None
		try:
			self.conn	 = MySQLdb.connect( host = self.dbhost,
				port = self.dbport,
				user = self.dbuser,
				passwd = self.dbpass,
				db = db)
			r = True
		except MySQLdb.MySQLError:
			print "Can't connect to database %s:%u with l/p %s:%s" % (self.dbhost,
				self.dbport,
				self.dbuser,
				self.dbpass)
			sys.exit(-1)
		return r
	def dropDatabases(self):
		pipe = Popen(["mysql",
			"-u%s" %  self.dbuser,
			"-p%s" % self.dbpass],stdin=PIPE,stdout=PIPE,stderr=PIPE)
		for database in self.databases:
			pipe = Popen(["mysql",
			"-u%s" %  self.dbuser,
			"-p%s" % self.dbpass],stdin=PIPE,stdout=PIPE,stderr=PIPE)
			(out,err) = pipe.communicate("DROP DATABASE IF EXISTS %s;\n" % database)
			if pipe.returncode<>0:
				print "Error dropping database %s:%s" % (database,err)
	def createDatabases(self):
		for database in self.databases:
			pipe = Popen(["mysql",
			"-u%s" %  self.dbuser,
			"-p%s" % self.dbpass],stdin=PIPE,stdout=PIPE,stderr=PIPE)
			sqlcmd = "CREATE DATABASE %s;use %s;\n" % (database,database)
			(out,err) = pipe.communicate(sqlcmd+"source "+OssimTestDB.schemaDir+os.sep+OssimTestDB.schemaFiles[database][0])
			print "OUT:%s\nERR:%s\n" % (out,err)
			
		

		
			
if __name__=="__main__":
	ossimdb = OssimTestDB()	
	ossimdb.dropDatabases()
	ossimdb.createDatabases()
		

