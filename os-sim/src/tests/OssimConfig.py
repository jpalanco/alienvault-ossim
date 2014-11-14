#!/usr/bin/python

class OssimConfig:
	"""Generate a config.xml file"""
	def __init__(self):
		self.logfile = "/var/log/ossim/server.log"
		self.ossimdb = "HOST=127.0.0.1;PORT=3306;USER=root;PASSWORD=;DATABASE=ossim_test"
		self.snortdb = "HOST=127.0.0.1;PORT=3306;USER=root;PASSWORD=;DATABASE=snort_test"
		self.osvdb = "HOST=127.0.0.1;PORT=3306;USER=root;PASSWOR=;DATABASE=osvdb_test"
		self.directivefile = "/etc/ossim/server/directive.xml"
		self.address = "0.0.0.0"
		self.port = 41000
		self.name = "opensourcesim"
		self.frameworkname = "opensourcesim"
		self.frameworkaddr = "127.0.0.1"
		self.frameworkport = 43000
		self.interval = 15

		
	def setLogFile(self,filename):
		self.logfile = filename
	def setOssimDb(self,dsn):
		self.ossimdb = dsn
	def setSnortDb(self,dsn):
		self.snortdb = dsn
	def setOsvDb(self,dsn):
		self.osvdb = dsn
	def setDirectiveFile(self,filename):
		self.directivefile = filename
	def setServerAddr(self,addr):
		self.address = addr
	def setServerPort(self,port):
		self.port = port
	def setServername(self,name):
		self.name = name
	def setFrameworkName(self,name):
		self.frameworkname = name
	def setFrameworkAddr(self,addr):
		self.frameworkaddr = addr
	def setFrameworkPort(self,port):
		self.framrworkport = port
	def setInterval(self,interval):
		self.interval=interval
	def getConfig(self):
		return \
		"""
		<config>
			<log filename="%s"/>
			<framework name="%s" ip="%s" port="%u"/>
			<datasources>
				<datasource name="ossimDS" provider="MySQL" dsn="%s"/>
				<datasource name="snortDS" provider="MySQL" dsn="%s"/>
				<datasource name="osvdbDS" provider="MySQL" dsn="%s"/>
			</datasources>
			<directive filaname="%s"/>
			<server name="%s" ip="%s" port="%u"/>
			<scheduler interval="%u"/>
			
		</config>
		""" % \
		(self.logfile,
		self.frameworkname,
		self.frameworkaddr,
		self.frameworkport,
		self.ossimdb,
		self.snortdb,
		self.osvdb,
		self.directivefile,
		self.name,
		self.address,
		self.port,
		self.interval)

if __name__== "__main__":
	conf = OssimConfig()
	print conf.getConfig()
		
