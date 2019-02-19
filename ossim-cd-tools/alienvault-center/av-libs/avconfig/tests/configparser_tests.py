# -*- coding: utf-8 -*-
#
# © Alienvault Inc. 2012
# All rights reserved
#
# This code is protected by copyright and distributed under licenses
# restricting its use, copying, distribution, and decompilation. It may
# only be reproduced or used under license from Alienvault Inc. or its
# authorised licensees.
# Needed packages:- 
# python-unittest2
# python-xmlrunner

import unittest2 as unittest
import time
import os
import sys
sys.path.insert(0,os.path.dirname(os.path.abspath(os.path.join(__file__, os.pardir))))

from avconfigparser import AVConfigParser
from avconfigparsererror import AVConfigParserErrors
TEST_FILES_PATH = os.path.abspath(os.path.join(__file__, os.pardir))+"/test_data/"
DEFAULT_SECTION = "NO_SECTION"


"""
Test files description:
ossim_setup1.conf: Well formatted file.
ossim_setup2.conf: Float number
ossim_setup3.conf: Invalid file
ossim_setup4.conf: Duplicated section
"""
class TestAVConfigParser(unittest.TestCase):
    def setUp(self):
        pass

    def test_sections(self):
        config = AVConfigParser()
        self.assertEqual(config.sections(), [])
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)
        sections = ['expert', 'framework', 'DEFAULT', 'database', 'snmp', 'update', 'server', 'firewall', 'vpn', 'sensor']
        self.assertEqual(config.sections(),sections)
        del config
        
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertEqual(config.sections(), [])
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)
        sections = ['expert', 'database', 'firewall', 'snmp', 'update', 'server', 'framework', 'NO_SECTION', 'vpn', 'sensor']
        self.assertEqual(config.sections(),sections)
        del config


    def test_has_section(self):
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertFalse(config.has_section(DEFAULT_SECTION))
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)
        self.assertTrue(config.has_section(DEFAULT_SECTION))
        self.assertFalse(config.has_section("KKKKKKKK"))
        del config


    def test_options(self):
        """Test options method.
        [framework]
        framework_https_cert=default
        framework_https_key=default
        framework_ip=192.168.2.22
        """
        test_section = "framework"
        test_section_options = ['framework_ip', 'framework_https_key', 'framework_https_cert']

        config = AVConfigParser(DEFAULT_SECTION)
        self.assertEqual(config.options(DEFAULT_SECTION),[])
        self.assertEqual(config.options(test_section),[])

        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)
        self.assertTrue(config.has_section(test_section))
        self.assertEqual(config.options(test_section), test_section_options)
        del config


    def test_read(self):
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertFalse(config.has_section(DEFAULT_SECTION))
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup3.conf")[0],AVConfigParserErrors.EXCEPTION)
        del config
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertFalse(config.has_section(DEFAULT_SECTION))
        self.assertEqual(config.read(TEST_FILES_PATH + "NoExist.conf")[0],AVConfigParserErrors.FILE_NOT_EXIST)
        del config
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertFalse(config.has_section(DEFAULT_SECTION))
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)
        del config
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertFalse(config.has_section(DEFAULT_SECTION))
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup4.conf")[0],AVConfigParserErrors.EXCEPTION)
        del config

    def test_get_option(self):
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertEqual(config.get_option("expert","profile"),None)

        self.assertFalse(config.has_section(DEFAULT_SECTION))
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)

        self.assertEqual(config.get_option("expert","profile"),"server")
        self.assertEqual(config.get_option("unknownsection","profile"),None)
        self.assertEqual(config.get_option("","interface"),"eth0")
        self.assertEqual(config.get_option(DEFAULT_SECTION,"domain"),"alienvault")
        del config

    def test_get_items(self):
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertEqual(config.get_items("expert"),{})

        self.assertFalse(config.has_section(DEFAULT_SECTION))
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)
        
        self.assertEqual(config.get_items("expert"),{"profile":"server"})
        default_seciton_items = {'admin_netmask': '255.255.255.0', 'mailserver_relay_passwd': 'validpassword', 'domain': 'alienvault', 'admin_dns': '8.8.8.8', 'mailserver_relay_user': 'prueba_mail2013@yahoo.es', 'email_notify': 'system@alienvault.com', 'hostname': 'crgalienvault4free', 'mailserver_relay_port': '587', 'profile': 'Server,Sensor,Framework,Database', 'interface': 'eth0', 'mailserver_relay': 'smtp.mail.yahoo.com', 'admin_ip': '192.168.2.22', 'ntp_server': 'no', 'admin_gateway': '192.168.5.5'}
        self.assertEqual(config.get_items(""),default_seciton_items)
        self.assertEqual(config.get_items(DEFAULT_SECTION),default_seciton_items)
        del config
        
    def test_has_option(self):
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertFalse(config.has_option("expert","profile"))
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)
        self.assertTrue(config.has_option("expert","profile"))
        self.assertTrue(config.has_option("","admin_dns"))
        self.assertTrue(config.has_option(DEFAULT_SECTION,"admin_dns"))
        self.assertFalse(config.has_option("nosectionname", "nosectionoption"))
        self.assertFalse(config.has_option(DEFAULT_SECTION, "nosectionoption"))
        del config
    def test_get_int(self):
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertEqual(config.get_int("expert","profile"),None)
        self.assertEqual(config.get_int("","mailserver_relay_port"),None)
        
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)

        self.assertEqual(config.get_int("expert","profile"),None)
        self.assertEqual(config.get_int("","mailserver_relay_port"),587)
        del config

    def test_get_float(self):
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertEqual(config.get_float("expert","profile"),None)
        self.assertEqual(config.get_float("","float_number"),None)
        
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup2.conf"),AVConfigParserErrors.ALL_OK)

        self.assertEqual(config.get_float("expert","profile"),None)
        self.assertEqual(config.get_float("","float_number"),0.2)
        del config

    def test_get_boolean(self):

        config = AVConfigParser(DEFAULT_SECTION)
        self.assertEqual(config.get_boolean("expert","profile"),None)
        self.assertEqual(config.get_boolean("firewall","active"),None)
        
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)

        self.assertEqual(config.get_boolean("expert","profile"),None)
        self.assertTrue(config.get_boolean("firewall","active"))
        self.assertEqual(config.get_boolean("jjjjjj","profile"),None)
        self.assertEqual(config.get_boolean("firewall","novalue"),None)
        del config


    def test_write(self):
        tempfilename = "/tmp/testfile-%s.cfg" % time.time()
        
        config = AVConfigParser(DEFAULT_SECTION)
        config.write(tempfilename)
        self.assertFalse(config.has_section(DEFAULT_SECTION))
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)
        del config
        
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertFalse(config.has_section(DEFAULT_SECTION))
        self.assertEqual(config.read(tempfilename),AVConfigParserErrors.ALL_OK)
        del config
        
        os.remove(tempfilename)

        tempfilename = "/tmp/testfile2-%s.cfg" % time.time()
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)
        config.set("server","server_ip","192.168.7.99")
        config.set("sensor","interfaces","eth5")
        config.write(tempfilename)
        del config
        
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertEqual(config.read(tempfilename),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.get_option("sensor","interfaces"),"eth5")
        self.assertEqual(config.get_option("server","server_ip"),"192.168.7.99")
        del config
        os.remove(tempfilename)
        
        


    def test_set(self):
        config = AVConfigParser(DEFAULT_SECTION)
        self.assertEqual(config.read(TEST_FILES_PATH + "ossim_setup1.conf"),AVConfigParserErrors.ALL_OK)
        profile = config.get_option("expert","profile")
        self.assertTrue(config.set("expert","profile","Database"))
        self.assertEquals(config.get_option("expert","profile"),"Database")
        
        self.assertFalse(config.set("Nosection","novalue","value"))
        #Add a new option=value
        self.assertTrue(config.set(DEFAULT_SECTION,"novalue","value"))
        
    def tearDown(self):
        pass
