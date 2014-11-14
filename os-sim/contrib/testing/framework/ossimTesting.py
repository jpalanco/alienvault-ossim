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

from selenium import selenium
import unittest, time, re
import ConfigParser

config = ConfigParser.RawConfigParser()
config.read('config.cfg')
sHost = config.get('main', 'ip')
sPort = config.get('main', 'port')
browser = config.get('main', 'browser')
url = config.get('main', 'ossimUrl')
user = config.get('main', 'user')
password = config.get('main', 'password')

class GeneralTestCase(unittest.TestCase):
		#self.desc = ""
    	def setUp(self):
        	self.verificationErrors = []
        	self.selenium = selenium(sHost, int(sPort), browser, url)
        	self.selenium.start()
        	self.login()
 
    	def tearDown(self):
		self.selenium.stop()
		self.assertEqual([], self.verificationErrors)

    	def login(self):
    		self.selenium.open("/ossim/session/login.php")
        	self.selenium.type("user", user)
        	self.selenium.type("pass", password)
        	self.selenium.click("//input[@value='Login']")
        	self.selenium.wait_for_page_to_load("30000") 
