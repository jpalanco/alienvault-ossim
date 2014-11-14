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

class test7(GeneralTestCase):
  def test7(self):
        self.desc = "Test Incident Tickets. test report graphics, tag insertion, type insertion, email template visualization "
        sel = self.selenium
        sel.open("http://192.168.1.231/ossim/incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets")
        sel.click("link=Tags")
        sel.wait_for_page_to_load("30000")
        sel.click("link=Add new tag")
        sel.wait_for_page_to_load("30000")
        sel.type("name", "aaaaaaa")
        sel.type("descr", "aaa")
        sel.click("//input[@value='OK']")
        sel.wait_for_page_to_load("30000")
        try: self.failUnless(sel.is_text_present(""))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_text_present(""))
        except AssertionError, e: self.verificationErrors.append(str(e))
        sel.click("link=Types")
        sel.wait_for_page_to_load("30000")
        try: self.failUnless(sel.is_text_present(""))
        except AssertionError, e: self.verificationErrors.append(str(e))
        sel.click("link=Add new type")
        sel.wait_for_page_to_load("30000")
        sel.type("type_id", "test")
        sel.type("type_descr", "test")
        sel.click("//input[@value='OK']")
        sel.wait_for_page_to_load("30000")
        sel.open("http://192.168.1.231/ossim/incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets")
        sel.click("link=Report")
        sel.wait_for_page_to_load("30000")
        try: self.failUnless(sel.is_element_present("//img[@alt='incidents by status graph']"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_element_present("//img[@alt='incidents by type graph']"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_element_present("//img[@alt='incidents by user graph']"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_element_present("//img[@alt='Num incidents closed by month']"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_element_present("//img[@alt='incidents by resolution time']"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_text_present("Incident type"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_text_present("User in charge"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_text_present("Closed Incidents By Month"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_text_present("Incident Status"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_text_present("Incident Resolution Time"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        sel.click("link=Incidents Email Template")
        sel.wait_for_page_to_load("30000")
        sel.click("preview")
        sel.wait_for_page_to_load("30000")
        try: self.failUnless(sel.is_text_present("exact:Subject:"))
        except AssertionError, e: self.verificationErrors.append(str(e))
        try: self.failUnless(sel.is_text_present("exact:Body:"))
        except AssertionError, e: self.verificationErrors.append(str(e))

