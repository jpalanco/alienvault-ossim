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

from ossimTesting import *

class test6(GeneralTestCase):
	def test6(self):
		self.desc = "Test Incident check texts"
		sel = self.selenium
		sel.open("http://192.168.1.231/ossim//incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets")
		sel.click("link=Tickets")
		sel.wait_for_page_to_load("30000")
		try: self.failUnless(sel.is_text_present("Tickets"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("Class"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("Incidents Email Template"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("Report"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("Tags"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("Types"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		sel.select("type", "label=Policy Violation")
		sel.wait_for_page_to_load("30000")

