import ntop
import json
import host
import interface
# Import modules for CGI handling
import cgi
import sys

def identify_interface_index(ifname):
	"""Identifies the interface index for the given interface name.
	This is used in order to extract hosts from teh correct interface.
	if ifname is nil it returns 0
	"""
	if ifname == '': return 0
	
	for i in range(interface.numInterfaces()):
		if (interface.name(i).lower() == ifname.lower()):
			return i
			
	return 0

__P2P__ = 2
__VOIP__ = 4
__PRINTER__ = 8
__DIRECTORY__ = 16
__WORKSTATION__ = 32
__HTTPHOST__ = 64
__FTPHOST__ = 128
__SERVER__ = 256
__MAILSERVER__ = 512
__DHCP__ = 1024

# form management
form = cgi.FieldStorage();

# Top hosts for which interface?
selectedif = form.getvalue('if', default="")

ifIndex = identify_interface_index(selectedif)

while ntop.getNextHost(ifIndex):
	if host.ipAddress()=="":
		 #drop host with no throughput or no ip
		continue 

	#host.hostResolvedName()
	#ntop.sendString("%s\n" % host.hostResolvedName())
	#ip,mac,name,fingerprint,isFTPhost,isWorkstation,isMasterBrowser,isPrinter,isSMTPhost,isPOPhost,isIMAPhost,isDirectoryHost,isHTTPhost,isVoIPClient,isVoIPGateway,isDHCPServer,isDHCPClient,
	data = "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s" % (host.ipAddress(), host.ethAddress(), host.hostResolvedName(), host.fingerprint(),
							 host.isFTPhost(),host.isWorkstation(),host.isMasterBrowser(),host.isPrinter(),
							 host.isSMTPhost(),host.isPOPhost(),host.isIMAPhost(),host.isDirectoryHost(),host.isHTTPhost(),
							 host.isVoIPClient(),host.isVoIPGateway(),host.isDHCPServer(),host.isDHCPClient())
	ntop.sendString("%s\n" % data)
'''
while ntop.getNextHost(interfaceId):
	#ip,mac,name,fingerprint,isFTPhost,isWorkstation,isMasterBrowser,isPrinter,isSMTPhost,isPOPhost,isIMAPhost,isDirectoryHost,isHTTPhost,isVoIPClient,isVoIPGateway,isDHCPServer,isDHCPClient,
	data = "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s" % (host.ipAddress(), host.ethAddress(), host.hostResolvedName(), host.fingerprint(),
							 host.isFTPhost(),host.isWorkstation(),host.isMasterBrowser(),host.isPrinter(),
							 host.isSMTPhost(),host.isPOPhost(),host.isIMAPhost(),host.isDirectoryHost(),host.isHTTPhost(),
							 host.isVoIPClient(),host.isVoIPGateway(),host.isDHCPServer(),host.isDHCPClient())
	ntop.sendString("%s\n" % data)	

'''
