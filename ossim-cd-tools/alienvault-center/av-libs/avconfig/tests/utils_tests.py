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
import sys, os
sys.path.insert(0,"../")
from utils import *
import os

INVALID_IP_V6 = ["0000",
                 "192.168.2.22",
                 None,
                 ]
VALID_IP_V6 =  ["2001:0db8:85a3:08d3:1319:8a2e:0370:7334",
               "2001:0db8:85a3::1319:8a2e:0370:7344",
               "2001:0DB8:0000:0000:0000:0000:1428:57ab",
               "2001:0DB8:0000:0000:0000::1428:57ab",
               "2001:0DB8:0:0:0:0:1428:57ab",
               "2001:0DB8:0::0:1428:57ab",
               "2001:0DB8::1428:57ab", 
               "::ffff:192.168.89.9"]
VALID_IPV4 = ["0.0.0.1",
              "127.0.0.1",
              "255.255.254.1",
              "10.2.2.2"]
INVALID_IPV4 = ["nhoag",
                "1.2.3.4.5",
                "266.266.266.266"]
VALID_ADDRESS = VALID_IP_V6 + VALID_IPV4
INVALID_ADDRESS =  ["nhoag",
                    "1.2.3.4.5",
                    "266.266.266.266",
                    None]

VALID_CIDR = ["0.0.0.1/24",
              "192.168.2.0/16",
              "10.2.0.0/24"]
INVALID_CIDR = [None,
                "texttext",
                "192.168.2.2.2/22",
                "192.168.2.2/54"]
MD5_FILE = "/tmp/md5sumfile.txt"
class TestUtils(unittest.TestCase):
    def setUp(self):
        try:
            md5file = open(MD5_FILE,'w')
            md5file.write("test md5 sum")
            md5file.close()
        except:
            print "Error writign md5sum test file"

    def test_is_ipv6(self):
        for ip in INVALID_IP_V6:
            self.assertFalse(is_ipv6(ip))
        for valid_ip in VALID_IP_V6:
            self.assertTrue(is_ipv6(valid_ip))

    def test_is_ipv4(self):
        for ip in INVALID_IPV4:
            self.assertFalse(is_ipv4(ip))
        for valid_ip in VALID_IPV4:
            self.assertTrue(is_ipv4(valid_ip))


    def test_is_valid_ip_address(self):
        for ip in INVALID_ADDRESS:
            self.assertFalse(is_valid_ip_address(ip))
        for ip in VALID_ADDRESS:
            self.assertTrue(is_valid_ip_address(ip))


    def test_is_valid_CIDR(self):
        for cidr in INVALID_CIDR:
            self.assertFalse(is_valid_CIDR(cidr))
        for cidr in VALID_CIDR:
            self.assertTrue(is_valid_CIDR(cidr))

    def test_md5sum(self):
        self.assertIsNone(md5sum(None))
        self.assertIsNone(md5sum("/path_to/unknown/file.txt"))
        if os.path.isfile(MD5_FILE):
            cmd = "md5sum %s | cut -d\" \" -f1" % MD5_FILE
            status,output = commands.getstatusoutput(cmd)
            self.assertEqual(output,md5sum(MD5_FILE))

   
    def test_is_valid_domain(self):
        invalid = [None, "", " ", "ݒ���P���'JO1", "192.168.0.1"]
        valid = ["alienvault.com", "test.net"]
        for domain in invalid:
            self.assertFalse(is_valid_domain(domain))
        for domain in valid:
            self.assertTrue(is_valid_domain(domain))

    def test_is_valid_email(self):
        invalid = [None, "", " ", "ݒ���P���'JO1", "a@a.com", "aaaaaaaaa", "a"]
        valid = ["test@test.com", "aaaaaaa@aaaaa.net"]
        for email in invalid:
            self.assertFalse(is_valid_email(email))
        for email in valid:
            self.assertTrue(is_valid_email(email))

    def test_is_boolean(self):
        invalid = [None, "", " ", "a", "ture", "flase"]
        valid = ["1", "yes", "true", "si", "on", "0", "false", "off", "no", "enabled", "disabled"]
        for value in invalid:
            self.assertFalse(is_boolean(value))
        for value in valid:
            self.assertTrue(is_boolean(value))

    def test_get_current_domain(self):
        self.assertIsNotNone(get_current_domain())

    def tearDown(self):
        if os.path.isfile(MD5_FILE):
            os.remove(MD5_FILE)

    def test_current_plugins_by_type(self):
        for p in  get_current_detector_plugin_list():
            print p
        for p in  get_current_monitor_plugin_list():
            print p
