#!/usr/bin/env python
# encoding: utf-8

"""
Created by Jaime Blasco on 2009-09-15

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

import sys
import getopt
import fileinput
import commands
import re
from xmlobject import XMLFile
import libxml2
import libxslt

#Disks
#Memory
#CPU
#PCI devices
#tarjetas de red
#pfring
		#Packages and versions2
#Kernel parameters

class disk():
	def __init__(self, id, size):
		self.id = id
		self.size = size
		self.rVel = self.timeRead()
		
	def timeRead(self):
		print "Timing %s ..." % self.id
		data = commands.getstatusoutput('hdparm -t /dev/%s' % self.id)[1]
		data = data.split('\n')
		# Timing buffered disk reads:  376 MB in  3.00 seconds = 125.18 MB/sec
		p = re.compile(r".*= (?P<vel>[\d\.]+).*")
		for d in data:
			m = p.match(d)
			if (m):
				return float(m.group(1))
		return None
		
class diskInfo():
	def __init__(self):
		self.disks = []
		self.checkInfo()
		
	def checkInfo(self):
		data = commands.getstatusoutput('fdisk -l')[1]
		data = data.split('\n')
		#Disk /dev/sda: 73.4 GB, 73407488000 bytes
		p = re.compile(r"Disk /dev/(?P<label>\S+):.*, (?P<cap>\d+)\sbytes.*")
		for d in data:
			m = p.match(d)
			if (m):
				label = m.group(1)
				size =  m.group(2)
				di = disk(label, size)
				self.disks.append(di)
			
class memInfo():
	def __init__(self):
		self.checkInfo()
		self.memTotal = 0
		self.memFree = 0
		self.swapTotal = 0
		self.swapFree = 0
		
	def checkInfo(self):
		data = commands.getstatusoutput('cat /proc/meminfo')[1]
		data = data.split('\n')
		p1 = re.compile(r"MemTotal:\s+(?P<mem>\d+)\skB.*")
		p2 = re.compile(r"MemFree:\s+(?P<mem>\d+)\skB.*")
		p3 = re.compile(r"SwapTotal:\s+(?P<mem>\d+)\skB.*")
		p4 = re.compile(r"SwapFree:\s+(?P<mem>\d+)\skB.*")
		for d in data:
			m = p1.match(d)
			if (m):
				self.memTotal = m.group(1)
			m = p2.match(d)
			if (m):
				self.memFree = m.group(1)
			m = p3.match(d)
			if (m):
				self.swapTotal = m.group(1)
			m = p4.match(d)
			if (m):
				self.swapFree = m.group(1)

class cpu():
	def __init__(self, id, vendor_id, cpu_family, model_id, model_name):
		self.id = id
		self.vendor_id = vendor_id
		self.cpu_family = cpu_family
		self.model_id = model_id
		self.model_name = model_name
		
	def __repr__(self):
		return "%s,%s,%s,%s,%s" % (self.id, self.vendor_id, self.cpu_family, self.model_id, self.model_name)
	'''
	def xml(self, dom):
		cp = dom.createElement("cpu")
		cp.id = self.id
		cp.vendor_id = vendor_id
		cp.cpu_family = cpu_family
		cp.model_id = model_id
		cp.model_name = model_name
		return cp
	'''
class cpuInfo():
	def __init__(self):
		self.cpus = []
		self.checkInfo()
		
	def checkInfo(self):
		data = commands.getstatusoutput('cat /proc/cpuinfo')[1]
		data = data.split('\n\n')
		for d in data:
			self.parseCpuData(d)
		
	def parseCpuData(self, data):
		data = data.split('\n')
		p = re.compile(r"([\w\s+]+)\t: (?P<val>.*)")
		m = p.match(data[0])
		id = m.group(2)
		
		p = re.compile(r"([\w\s+]+)\t: (?P<val>.*)")
		m = p.match(data[1])
		vendor_id = m.group(2)
		
		p = re.compile(r"([\w\s+]+)\t: (?P<val>.*)")
		m = p.match(data[2])
		cpu_family = m.group(2)
		
		p = re.compile(r"([\w\s+]+)\t: (?P<val>.*)")
		m = p.match(data[3])
		model_id = m.group(2)
		
		p = re.compile(r"([\w\s+]+)\t: (?P<val>.*)")
		m = p.match(data[4])
		model_name = m.group(2)
		
		cp = cpu(id, vendor_id, cpu_family, model_id, model_name)
		#print cp
		self.cpus.append(cp)

class pciDev():
	def __init__(self, device, dClass, vendor, deviceName, sVendor, sDevice):
		self.device = device
		self.dClass = dClass
		self.vendor = vendor
		self.deviceName = deviceName
		self.sVendor = sVendor
		self.sDevice = sDevice
		
	def __repr__(self):
		return "%s,%s,%s,%s,%s,%s" % (self.device, self.dClass, self.vendor, self.deviceName, self.sVendor, self.sDevice)
		
class pciInfo():
	def __init__(self):
		self.devices = []
		self.checkInfo()
		
	def checkInfo(self):
		"""Parse lspci info"""
		data = commands.getstatusoutput('lspci -mm')[1]
		data = data.split('\n')
		#07:00.0 "Ethernet controller" "Intel Corporation" "80003ES2LAN Gigabit Ethernet Controller (Copper)" -r01 "ASUSTeK Computer Inc." "Device 8217"
		#07:00.1 "Ethernet controller" "Intel Corporation" "80003ES2LAN Gigabit Ethernet Controller (Copper)" -r01 "ASUSTeK Computer Inc." "Device 8217"
		for d in data:
			d = d.split(' "')
			device = d[0].replace('"', '')
			dClass = d[1].replace('"', '')
			vendor = d[2].replace('"', '')
			deviceName = d[3].replace('"', '')
			sVendor = d[4].replace('"', '')
			sDevice = d[5].replace('"', '')
			pDev = pciDev(device, dClass, vendor, deviceName, sVendor, sDevice)
			self.devices.append(pDev)
	
class networkCard():
	def __init__(self, interface, neg):
		self.interface = interface
		self.neg = neg
		self.getRingParams()

	def __repr__(self):
		return "%s,%s,%s,%s,%s,%s" % (self.interface, self.neg, self.maxRX, self.maxTX, self.RX, self.TX)
				
	def getRingParams(self):
		data = commands.getstatusoutput('ethtool -g %s' % self.interface)[1]
		data = data.split('\n')
		p = re.compile(r"([\w\s+]+):([^\d]+)(?P<val>\d+)")
		m = p.match(data[2])
		self.maxRX = m.group(3)
		
		m = p.match(data[5])
		self.maxTX = m.group(3)
		
		m = p.match(data[7])
		self.RX = m.group(3)
		
		m = p.match(data[10])
		self.TX = m.group(3)
		
				
class networkInfo():
	def __init__(self):
		self.cards = []
		self.checkInfo()
		
	def checkInfo(self):
		"""Parse Mii-tool and ethtool info"""
		data = commands.getstatusoutput('mii-tool')[1]
		data = data.split('\n')
		#eth0: negotiated 100baseTx-FD, link ok
		p = re.compile(r"(?P<in>[\d\w]+): negotiated (?P<vel>[\d\.]+).*")
		for d in data:
			m = p.match(d)
			if (m):
				interface = m.group(1)
				neg = m.group(2)
				card = networkCard(interface, neg)
				self.cards.append(card)

class pkt():
	def __init__(self, name, version, desc):
		self.name = name
		self.version = version
		self.desc = desc

	def __repr__(self):
		return "%s,%s,%s" % (self.name, self.version, self.desc)
					
class packageInfo():
	def __init__(self):
		self.packages = []
		self.checkInfo()
		
	def checkInfo(self):
		data = commands.getstatusoutput('dpkg -l')[1]
		data = data.split('\n')
		p = re.compile(r"..  (?P<name>\S+)\s+(?P<ver>\S+)\s+(?P<desc>.*)")
		for d in data:
			m = p.match(d)
			if m:
				name = m.group(1)
				version = m.group(2)
				desc = m.group(3)
				pk = pkt(name, version, desc)
				self.packages.append(pk)
		
class pfringInfo():
	def __init__(self):
		self.checkInfo()
		
	def checkInfo(self):
		data = commands.getstatusoutput('cat /proc/net/pf_ring/info')[1]
		data = data.split('\n')
		if len(data) == 8:
			p = re.compile(r"[^:]+: (?P<val>.*)")
			m = p.match(data[0])
			self.version = m.group(1)
				
			m = p.match(data[1])
			self.slots = m.group(1)
			
			m = p.match(data[2])
			self.sVersion = m.group(1)
			
			m = p.match(data[3])
			self.cTX = m.group(1)

			m = p.match(data[4])
			self.defrag = m.group(1)			

			m = p.match(data[5])
			self.transparentMode = m.group(1)
			
			m = p.match(data[6])
			self.rings = m.group(1)

			m = p.match(data[7])
			self.plugins = m.group(1)				
			
			
class kParam():
	def __init__(self, name, val):
		self.name = name
		self.val = val
	
class kernelInfo():
	def __init__(self):
		self.parametes = []
		self.version = ''
		self.checkInfo()
		
	def checkInfo(self):
		data = commands.getstatusoutput('sysctl -a')[1]
		data = data.split('\n')
		p = re.compile(r"(?P<name>[^\s]+)\s=\s(?P<val>.*)")
		for d in data:
			m = p.match(d)
			if m:
				name = m.group(1)
				val = m.group(2)
				kP = kParam(name, val)
				self.parametes.append(kP)
	
class systemInfo():
	def __init__(self):
		self.disk = diskInfo()
		self.mem = memInfo()
		self.cpu = cpuInfo()
		self.pci = pciInfo()
		self.network = networkInfo()
		self.packages = packageInfo()
		self.pfring = pfringInfo()
		self.kernel = kernelInfo()
		
	def xmlOut(self, fileName):
		xmlStr = '<?xml version="1.0" encoding="UTF-8"?>' \
		 '<system></system>'
		x = XMLFile(raw=xmlStr)
		system = x.root
		
		#CPU
		xcp = system._addNode("cpuInfo")
		for c in self.cpu.cpus:
			xc = xcp._addNode("cpu")
			for att in dir(c):
				if att[0] != '_':
					exec("xc.%s = c.%s" % (att, att))
			xcp._addNode(xc)
		
		#Memory
		xme = system._addNode("memInfo")
		for att in dir(self.mem):
			if att[0] != '_' and att != "checkInfo":
				exec("xme.%s = self.mem.%s" % (att, att))
			
		#Disk
		xdi = system._addNode("diskInfo")
		for d in self.disk.disks:
			xdis = xdi._addNode("disk")
			for att in dir(d):
				if att[0] != '_' and att != "timeRead":
					exec("xdis.%s = d.%s" % (att, att))
				xdi._addNode(xdis)
		
		#PCI
		xpci = system._addNode("pciInfo")
		for m in self.pci.devices:
			xp = xpci._addNode("pciModule")
			for att in dir(m):
				if att[0] != '_':
					exec("xp.%s = m.%s" % (att, att))
			xpci._addNode(xp)

		#Packages
		xpac = system._addNode("packages")
		for p in self.packages.packages:
			xpa = xpac._addNode("package")
			for att in dir(p):
				if att[0] != '_':
					exec("xpa.%s = p.%s" % (att,att))
			xpac._addNode(xpa)
			
		#Kernel
		xker = system._addNode("kernel")
		for k in self.kernel.parametes:
			xke = xker._addNode("parameter")
			for att in dir(k):
				if att[0] != '_':
					exec("xke.%s = k.%s" % (att, att))
			xker._addNode(xke)
		
		#PFRING
		xpfr = system._addNode("pfring")
		for att in dir(self.pfring):
			if att[0] != '_' and att != "checkInfo":
				exec("xpfr.%s = self.pfring.%s" % (att, att))
		
		#NETWORK
		xnet = system._addNode("network")
		for nc in self.network.cards:
			xn = xnet._addNode("card")
			for att in dir(nc):
				if att[0] != '_' and att != "getRingParams":
					exec("xn.%s = nc.%s" % (att,att))
			xnet._addNode(xn)

		#Write Results
		f = open(fileName, 'w')
		f.write(x.toxml())
		f.close()
	
	def applyXSLT(self, fileName):
		sourceXMLFile = fileName
		sourceDoc = libxml2.parseFile(sourceXMLFile)
		styleDoc = libxml2.parseFile("base.xsl")
		style = libxslt.parseStylesheetDoc(styleDoc)
		result = style.applyStylesheet(sourceDoc, None)
		print result


def main(argv=None):
	s = systemInfo()
	s.xmlOut("results.xml")
	s.applyXSLT("results.xml")
	
if __name__ == "__main__":
	sys.exit(main())
