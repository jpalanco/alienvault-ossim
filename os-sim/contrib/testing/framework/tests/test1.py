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

class test1(GeneralTestCase):
	desc = "Comprueba la pestana Aggregated Risk que aparezcan los titulos de compromiso y ataque asi como las redes definidas"
	def test1(self):
		sel = self.selenium
		sel.open("/ossim/")
		sel.select_frame("topmenu")
		sel.click("//div[@id='test-content']/div/table/tbody/tr[2]/td/div/table/tbody/tr/td[2]/span")
		sel.wait_for_page_to_load("30000")
		sel.select_frame("relative=up")
		sel.select_frame("main")
		for i in range(60):
		        try:
		                if "Control Panel" == sel.get_title(): break
		        except: pass
		        time.sleep(1)
		else: self.fail("time out")
		try: self.failUnless(sel.is_text_present("C O M P R O M I S E"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("A T T A C K"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("Pvt_10"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("Pvt_172"))
		except AssertionError, e: self.verificationErrors.append(str(e))
		try: self.failUnless(sel.is_text_present("Pvt_192"))
		except AssertionError, e: self.verificationErrors.append(str(e))

