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

import unittest, time, re
import HTMLTestRunner
import smtplib
from email.mime.text import MIMEText
import ConfigParser
import os
import sys

config = ConfigParser.RawConfigParser()
config.read('config.cfg')
htmlReport = config.get('main', 'htmlReport')

'''
def sendMail():
	fp = open(htmlReport, 'rb')
	msg = MIMEText(fp.read())
	fp.close()
	msg['Subject'] = 'Report'
	msg['From'] = 'testing@alienvault.com'
	msg['To'] = 'jaime.blasco@alienvault.com'
	s = smtplib.SMTP('192.168.1.133')
	s.sendmail(msg['From'], msg['To'], msg.as_string())
	s.quit()
'''
	
if __name__ == "__main__":
	#tests = ['test1', 'test2']
	testList = []
	descs = {}
	if len(sys.argv) != 2:
		print "Usage:   main.py [all|testX]\n" 
		exit(0)
	if sys.argv[1] == 'all':
		for f in os.listdir(os.path.abspath('./tests')):
			t, ext = os.path.splitext(f)
			if ext == '.py' and t.startswith('test'):
				exec('from tests.%s import *' % t)
				try:
					exec("descs['%s'] = %s.desc" % (t,t))
				except:
					print "Required description for %s" % t
				#suite = unittest.TestLoader().loadTestsFromTestCase(Untitled)
				exec('%s = unittest.TestLoader().loadTestsFromTestCase(%s)' % (t,t))
				exec('testList.append(%s)' % t)
	elif sys.argv[1].startswith('test'):
		t = sys.argv[1]
		try:
			exec('from tests.%s import *' % t)
		except:
			print "%s not found" % t
			exit(0)
		try:
			exec("descs['%s'] = %s.desc" % (t,t))
		except:
			print "Required description for %s" % t
		exec('%s = unittest.TestLoader().loadTestsFromTestCase(%s)' % (t,t))
		exec('testList.append(%s)' % t)
		
	description = ""
	for t,d in descs.iteritems():
		description = description + t + ": " + d + "\n" 
	alltests = unittest.TestSuite(testList)
	#unittest.TextTestRunner(verbosity=2).run(alltests)
	fp = file(htmlReport, 'wb')
	runner = HTMLTestRunner.HTMLTestRunner(stream=fp, title='Ossim testing', description=description)
	runner.run(alltests)
	#sendMail()
    

	
