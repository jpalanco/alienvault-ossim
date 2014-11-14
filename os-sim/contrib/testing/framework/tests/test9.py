#!/usr/bin/env python
# encoding: utf-8
"""
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

class test9(GeneralTestCase):
	def test9(self):
		self.desc = "Test some words in anomalies"
		sel = self.selenium
		sel.open("http://192.168.1.231/ossim//control_panel/anomalies.php?hmenu=Anomalies&smenu=Anomalies")
		sel.click("link=Anomalies")
		sel.wait_for_page_to_load("30000")
		sel.click("link=[Get full list]")
		try: self.failUnless(sel.is_text_present("Host"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("What"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("Delete"))
		except AssertionError, e: self.verificationErrors.append(str(e))

