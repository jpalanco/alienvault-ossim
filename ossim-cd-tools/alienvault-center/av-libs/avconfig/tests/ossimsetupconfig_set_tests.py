# -*- coding: utf-8 -*-
#
# © Alienvault Inc. 2012
# All rights reserved
#
# This code is protected by copyright and distributed under licenses
# restricting its use, copying, distribution, and decompilation. It may
# only be reproduced or used under license from Alienvault Inc. or its
# authorised licensees.
import unittest2 as unittest
import os
import sys
sys.path.insert(0,os.path.dirname(os.path.abspath(os.path.join(__file__, os.pardir))))

import time
from ossimsetupconfig import AVOssimSetupConfigHandler
from configparsererror import AVConfigParserErrors
from  utils import*
TEST_FILES_PATH = os.path.abspath(os.path.join(__file__, os.pardir))+"/test_data/"


"""
Test files description:
ossim_setup1.conf: Well formatted file.
ossim_setup2.conf: Float number
ossim_setup3.conf: Invalid file
ossim_setup4.conf:
ossim_setup5.conf: Invalid values on load
ossim_setup6.conf: Profile Server
ossim_setup7.conf: Profile Sensor
"""

class TestAVOssimSetupConfigHandlerSets(unittest.TestCase):
    def setUp(self):
        pass
    def test_set_general_admin_dns(self):
        """
        1 - Whether admin_dns == ""-> use the system nameserver at /etc/resolv.conf
        2 - It should be a valid ip
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_admin_dns("")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        self.assertEqual(config.set_general_admin_dns(""), AVConfigParserErrors.ALL_OK)
        current_nameserver = get_current_nameserver()
        self.assertEqual(current_nameserver, config.get_general_admin_dns())

        self.assertEqual(config.set_general_admin_dns(None)[0], AVConfigParserErrors.VALUE_NOT_VALID_IP)
        self.assertEqual(config.set_general_admin_dns("None")[0], AVConfigParserErrors.VALUE_NOT_VALID_IP)
        testip = "192.168.22.56"
        self.assertEqual(config.set_general_admin_dns(testip), AVConfigParserErrors.ALL_OK)
        self.assertEqual(testip, config.get_general_admin_dns())
        del config


    def test_set_general_admin_gateway(self):
        """
        1 - Whether admin_gateway==""-> get the current admin interface gateway
        2 - Whether the admin interface is not set or is not a valid interface,
        this function will return an error. 
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_admin_gateway("")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        allowed_ifaces = config.get_allowed_values_for_general_interface()
        # Setting default interface
        if len(allowed_ifaces) > 0:
            # 1 - Set a valid interface
            current_gateway = ""
            valid_net_interface = ""
            invalid_net_interface = ""
            for iface in allowed_ifaces:
                gtw = get_current_gateway(iface)
                if gtw != "":
                    current_gateway = gtw
                    valid_net_interface = iface
                else:
                    invalid_net_interface = iface
                
            if valid_net_interface != "":
                
                self.assertEqual(config.set_general_interface(valid_net_interface), AVConfigParserErrors.ALL_OK)
                self.assertEqual(config.get_general_interface(), valid_net_interface)
                # 2 - Check 
                self.assertEqual(config.set_general_admin_gateway(""), AVConfigParserErrors.ALL_OK)
                self.assertEqual(config.get_general_admin_gateway(), current_gateway)
            else:
                print "There's not valid interfaces to run the test."
        
            # Try to set with an invalid interface.
            if invalid_net_interface != "":
                
                self.assertEqual(config.set_general_interface(invalid_net_interface), AVConfigParserErrors.ALL_OK)
                self.assertEqual(config.get_general_interface(), invalid_net_interface)
                # 2 - Check 
                self.assertNotEqual(config.set_general_admin_gateway(""), AVConfigParserErrors.ALL_OK)
                #Reset the interface:
                
                self.assertEqual(config.set_general_interface(valid_net_interface), AVConfigParserErrors.ALL_OK)
        
        # Try invalid values
        # The value should be an IP Address
        self.assertNotEqual(config.set_general_admin_gateway("999.999.999.999"), AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.set_general_admin_gateway("ThisISNotAnIP"), AVConfigParserErrors.ALL_OK)

        # Test valid values
        self.assertEqual(config.set_general_admin_gateway("192.168.2.2"), AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.set_general_admin_gateway("10.5.5.2"), AVConfigParserErrors.ALL_OK)
        del config


    def test_set_general_admin_ip(self):
        """The admin_ip should be a valid ip
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_admin_ip("192.168.2.1")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        self.assertNotEqual(config.set_general_admin_ip("192.168.2.A"), AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.set_general_admin_ip("192.168.2.500"), AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.set_general_admin_ip(""), AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.set_general_admin_ip(None), AVConfigParserErrors.ALL_OK)
        ip1 = "192.168.2.2"
        self.assertEqual(config.set_general_admin_ip(ip1), AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.get_general_admin_ip(), ip1)


    def test_set_general_admin_netmask(self):
        """
        1 - Whether admin_netmask==""-> get the current admin interface netmask
        2 - Whether the admin interface is not set or is not a valid interface,
        this function will return an error. 
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_admin_netmask("")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        allowed_ifaces = config.get_allowed_values_for_general_interface()
        # Setting default interface
        if len(allowed_ifaces) > 0:
            # 1 - Set a valid interface
            current_netmask = ""
            valid_net_interface = ""
            invalid_net_interface = ""
            for iface in allowed_ifaces:
                gtw = get_network_mask_for_iface(iface)
                if gtw != "":
                    current_netmask = gtw
                    valid_net_interface = iface
                else:
                    invalid_net_interface = iface
                
            if valid_net_interface != "":
                self.assertEqual(config.set_general_interface(valid_net_interface), AVConfigParserErrors.ALL_OK)
                self.assertEqual(config.get_general_interface(), valid_net_interface)
                # 2 - Check 
                self.assertEqual(config.set_general_admin_netmask(""), AVConfigParserErrors.ALL_OK)
                self.assertEqual(config.get_general_admin_netmask(), current_netmask)
            else:
                print "There's not valid interfaces to run the test."
        
            # Try to set with an invalid interface.
            if invalid_net_interface != "":
                self.assertEqual(config.set_general_interface(invalid_net_interface), AVConfigParserErrors.ALL_OK)
                self.assertEqual(config.get_general_interface(), invalid_net_interface)
                # 2 - Check 
                self.assertNotEqual(config.set_general_admin_netmask(""), AVConfigParserErrors.ALL_OK)
                #Reset the interface:
                self.assertEqual(config.set_general_interface(valid_net_interface), AVConfigParserErrors.ALL_OK)
        
        # Try invalid values
        # The value should be an IP Address
        self.assertNotEqual(config.set_general_admin_netmask("999.999.999.999"), AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.set_general_admin_netmask("ThisISNotAnIP"), AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.set_general_admin_netmask("192.168.2.2"), AVConfigParserErrors.ALL_OK)
        # Test valid values
        
        self.assertEqual(config.set_general_admin_netmask("255.255.0.0"), AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.set_general_admin_netmask("255.255.255.0"), AVConfigParserErrors.ALL_OK)
        del config


    def test_set_general_domain(self):
        """Valid domain. ASCII [1,63] characters.
        It isn't allowed to use an IP Address despite of it's allowed
        by the RFC
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_domain("admin.domain")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        invalids = ["k"*64, ".."*64, "192.168.2.1"]
        valids = ["k"*63, "."*63, "admin.domnina"]
        for invaliddns in invalids:
            self.assertNotEqual(config.set_general_domain(invaliddns), AVConfigParserErrors.ALL_OK)
        for valid_dns in valids:
            self.assertEqual(config.set_general_domain(valid_dns), AVConfigParserErrors.ALL_OK)


    def test_set_general_email_notify(self):
        """It could be empty or a valid email
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_email_notify("valid@email.com")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        invalids = ["k"*64, ".."*64, "192.168.2.1", "invalid? @mail.com"]
        valids = ["", "valid@email.com", "admin.domnina@mail.com", "another-email@email.com", None]
        for email in invalids:
            self.assertNotEqual(config.set_general_email_notify(email), AVConfigParserErrors.ALL_OK)
        for email in valids:
            self.assertEqual(config.set_general_email_notify(email), AVConfigParserErrors.ALL_OK)
            if email:
                self.assertEqual(config.get_general_email_notify(), email)
            else:
                self.assertEqual(config.get_general_email_notify(), "")
        del config

    def test_set_general_hostname(self):
        """
        1 - Whether the hostname value == "" -> get the default value from /etc/hostname
        2 - It shouldn't be an IP. It should be a hostname (ASCII, size max 64)
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_hostname("valid.valid")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        valid_hostnames = ["unhostvalid-valid",  "k"*63]
        invalid_hostnames = ["k"*64, "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��", "@test" , "valid.invalid"]
        current_hostname = get_current_hostname()
        self.assertEqual(config.set_general_hostname(""), AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.get_general_hostname(), current_hostname)
        
        for valid_host in valid_hostnames:
            self.assertEqual(config.set_general_hostname(valid_host), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_general_hostname(), valid_host)
        for invalid_hostnames in invalid_hostnames:
            self.assertNotEqual(config.set_general_hostname(invalid_hostnames), AVConfigParserErrors.ALL_OK)
        del config

    def test_set_general_interface(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        interfaces = config.get_allowed_values_for_general_interface()
        invalid_interfaces = [99, None, "", "this is not a interface", "notinterface"]
        for interface in interfaces:
            self.assertEqual(config.set_general_interface(interface), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_general_interface(), interface)
        for interface in invalid_interfaces:
            self.assertNotEqual(config.set_general_interface(interface), AVConfigParserErrors.ALL_OK)
        del config


    def test_set_general_mailserver_relay(self):
        """no, ip address, hostname or domain
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_mailserver_relay("valid.valid")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        valid_servers = ["unhostvalid-valid", "valid.valid", "k"*63, "192.168.2.2"]
        invalid_servers = ["k"*64, "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��"]
        for server in valid_servers:
            self.assertEqual(config.set_general_mailserver_relay(server), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_general_mailserver_relay(), server)
        for server in invalid_servers:
            self.assertNotEqual(config.set_general_mailserver_relay(server), AVConfigParserErrors.ALL_OK)
        #default value
        self.assertEqual(config.set_general_mailserver_relay("no"), AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.get_general_mailserver_relay(), "no")
        self.assertEqual(config.get_general_mailserver_relay_passwd(), "unconfigured")
        self.assertEqual(config.get_general_mailserver_relay_port(), "25")
        self.assertEqual(config.get_general_mailserver_relay_user(), "unconfigured")
        del config


    def test_set_general_mailserver_relay_passwd(self):
        """ASCII {8,64}
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_mailserver_relay_passwd("valid.valid")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        valid_passwords = ["unhostvalid-valid", "valid.valid", "k"*63, "192.168.2.2"]
        invalid_passords = [ "k"*1000, "ݒ���P���'JO1�lߌ�vם˖"] 

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for valid_p in valid_passwords:
            self.assertEqual(config.set_general_mailserver_relay_passwd(valid_p), AVConfigParserErrors.ALL_OK)
        for invalid_p in invalid_passords:
            self.assertNotEqual(config.set_general_mailserver_relay_passwd(invalid_p), AVConfigParserErrors.ALL_OK)
        del config

    def test_set_general_mailserver_relay_port(self):
        """default: 25
           valid values: 0,65535
        """
        valid = ["",None, "0", "235", 25, 536]
        invalid = [ "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" , "1234567"] 

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for port in valid:
            self.assertEqual(config.set_general_mailserver_relay_port(port), AVConfigParserErrors.ALL_OK)
        for port in invalid:
            self.assertNotEqual(config.set_general_mailserver_relay_port(port), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_mailserver_relay_port(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_general_mailserver_relay_user(self):
        """ASCII {4,255}
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_mailserver_relay_user("valid.valid")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        valid_users = ["", "valid.valid", "k"*255, "k"*4, "192.168.2.2"]
        invalid_users = ["123", "k"*256, "â", "ݒ���p���'jo1�lߌ�vם˖$1�qu�uԞjqcn�ײޟ��"] 
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for user in valid_users:
            self.assertEqual(config.set_general_mailserver_relay_user(user), AVConfigParserErrors.ALL_OK)
            if user == "":
                user = "unconfigured"
            self.assertEqual(config.get_general_mailserver_relay_user(), user)
        for user in invalid_users:
            self.assertNotEqual(config.set_general_mailserver_relay_user(user), AVConfigParserErrors.ALL_OK)
        del config


    def test_set_general_ntp_server(self):
        """
        default value: no
        allowed = hostname or ip
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_general_ntp_server("hostnamevalid")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        valid = ["no", "hostnamevalid", "192.168.2.2"]
        invalid = ["a"*256, " test", "ݒ���p���'jo1�lߌ�vם˖$1�qu�uԞjqcn�ײޟ��"]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for value in valid:
            self.assertEqual(config.set_general_ntp_server(value), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_general_ntp_server(), value)
        for value in invalid:
            self.assertNotEqual(config.set_general_ntp_server(value), AVConfigParserErrors.ALL_OK)
        del config


    def test_set_general_profile(self):
        """read only"""
        pass

    def test_set_database_db_ip(self):
        """ipv4
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_database_db_ip("10.5.5.1")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        valid = ["10.5.5.1", "192.168.2.2"]
        invalid = ["192.168.565.1", "a"*256, " test", "ݒ���p���'jo1�lߌ�vם˖$1�qu�uԞjqcn�ײޟ��"]
        # profile = Server,Sensor,Database,Framework
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for value in valid:
            self.assertNotEqual(config.set_database_db_ip(value), AVConfigParserErrors.ALL_OK)
        for value in invalid:
            self.assertNotEqual(config.set_database_db_ip(value), AVConfigParserErrors.ALL_OK)
        del config

        # profile = Server
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup6.conf")
        for value in valid:
            self.assertEqual(config.set_database_db_ip(value), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_database_db_ip(), value)
        for value in invalid:
            self.assertNotEqual(config.set_database_db_ip(value), AVConfigParserErrors.ALL_OK)
        del config

    def test_set_database_pass(self):
        """ASCII 8 -16
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_database_pass("validpassword")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        valid_passwords = ["validpassword", "valid.valid", "k"*16, "k"*8, "192.168.2.2"]
        invalid_passords = ["", None, "1234567", "k"*7, "k"*17, "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" , "1234567"] 
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for valid_p in valid_passwords:
            self.assertEqual(config.set_database_pass(valid_p), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_database_pass(), valid_p)
        for invalid_p in invalid_passords:
            self.assertNotEqual(config.set_database_pass(invalid_p), AVConfigParserErrors.ALL_OK)
        del config


    def test_set_database_user(self):
        """ASCII 4 -16
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_database_user("validuser")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        valid = ["validpassword", "valid.valid", "k"*16, "k"*8, "192.168.2.2"]
        invalid = ["", None, "k"*3, "k"*17, "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" ] 
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for v in valid:
            self.assertEqual(config.set_database_user(v), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_database_user(), v)
        for v in invalid:
            self.assertNotEqual(config.set_database_user(v), AVConfigParserErrors.ALL_OK)
        del config


    def test_set_expert_profile(self):
        """Read only
        """
        pass

    def test_set_firewall_active(self):
        """YES_NO_CHOICES
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_firewall_active("yes")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        valid = [ "yes", "no", "",None]
        invalid = [0, 1,  "hhh", "�lߌ�vם˖$1"]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        default_value = config.get_default_value(config.FIREWALL_SECTION_NAME, config.SECTION_FIREWALL_ACTIVE)
        for v in valid:
            self.assertEqual(config.set_firewall_active(v), AVConfigParserErrors.ALL_OK)
            if v == "" or not v:
                v = default_value
            self.assertEqual(config.get_firewall_active(), v)
        for v in invalid:
            self.assertNotEqual(config.set_firewall_active(v), AVConfigParserErrors.ALL_OK)
        del config

    def test_set_framework_framework_https_cert(self):
        """default value: default
        or a existing file.
        """
        invalid = [ "not a file", "/invaliddirectory/invalidfile"]
        tmpfile = "/tmp/httpcert.cert.%s" % time.time()
        valid = [tmpfile,""]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        f = open(tmpfile,"w")
        f.write("TEST")
        f.close()
        for v in invalid:
            self.assertNotEqual(config.set_framework_framework_https_cert(v), AVConfigParserErrors.ALL_OK)
        for v in valid:
            self.assertEqual(config.set_framework_framework_https_cert(v), AVConfigParserErrors.ALL_OK)
            if v == "":
                v = config.get_default_value(config.FRAMEWORK_SECTION_NAME, config.SECTION_FRAMEWORK_HTTPS_CERT)
            self.assertEqual(config.get_framework_framework_https_cert(), v)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_framework_framework_https_cert(v)[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        os.remove(tmpfile)


    def test_set_framework_framework_https_key(self):
        """default value: default
        or a existing file.
        """
        invalid = ["not a file", "/invaliddirectory/invalidfile"]
        tmpfile = "/tmp/httpkey.cert.%s" % time.time()
        valid = [tmpfile,""]

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        f = open(tmpfile,"w")
        f.write("TEST")
        f.close()
        for v in invalid:
            self.assertNotEqual(config.set_framework_framework_https_key(v), AVConfigParserErrors.ALL_OK)
        for v in valid:
            self.assertEqual(config.set_framework_framework_https_key(v), AVConfigParserErrors.ALL_OK)
            if v == "":
                v = config.get_default_value(config.FRAMEWORK_SECTION_NAME, config.SECTION_FRAMEWORK_HTTPS_KEY)
            self.assertEqual(config.get_framework_framework_https_key(), v)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_framework_framework_https_key(v)[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        os.remove(tmpfile)


    def test_set_framework_framework_ip(self):
        valid = ["10.5.5.1", "192.168.2.2"]
        invalid = ["192.168.565.1", "a"*256, " test", "ݒ���p���'jo1�lߌ�vם˖$1�qu�uԞjqcn�ײޟ��"]

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_framework_framework_ip(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

        # profile = Server,Sensor,Database,Framework
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for value in valid:
            self.assertNotEqual(config.set_framework_framework_ip(value), AVConfigParserErrors.ALL_OK)
        for value in invalid:
            self.assertNotEqual(config.set_framework_framework_ip(value), AVConfigParserErrors.ALL_OK)
        del config

        # profile = Server
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup6.conf")
        for value in valid:
            self.assertEqual(config.set_framework_framework_ip(value), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_framework_framework_ip(), value)
        for value in invalid:
            self.assertNotEqual(config.set_framework_framework_ip(value), AVConfigParserErrors.ALL_OK)
        del config


    def test_set_sensor_detectors(self):
        """The set_sensor_detectors receives a string (comma separated) of detector plugins.
        """
        detector_list = get_current_detector_plugin_list()
        detector_list = detector_list[:-1]
        detector_list = ', '.join(detector_list)
        unknwon_lists = ["prads,pof,unknown","ssh, ossec,theinvalidplugin",None,888,""]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for v in unknwon_lists:
            self.assertNotEqual(config.set_sensor_detectors(v),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.set_sensor_detectors(detector_list),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.get_sensor_detectors(),detector_list)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_detectors(detector_list)[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_sensor_ids_rules_flow_control(self):
        """Default = yes
        YES_NO_CHOICES
        """
        valid = [ "yes", "no", "",None]
        invalid = [0, 1, "hhh", "�lߌ�vם˖$1"]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        default_value = config.get_default_value(config.SENSOR_SECTION_NAME, config.SECTION_SENSOR_IDS_RULES_FLOW_CONTROL)
        for v in valid:
            self.assertEqual(config.set_sensor_ids_rules_flow_control(v), AVConfigParserErrors.ALL_OK)
            if v == "" or v == None:
                v = default_value
            self.assertEqual(config.get_sensor_ids_rules_flow_control(), v)
        for v in invalid:
            self.assertNotEqual(config.set_sensor_ids_rules_flow_control(v), AVConfigParserErrors.ALL_OK)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_ids_rules_flow_control(v)[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_sensor_interfaces(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        invalid_values = ["AA"*1000,"","lo","wlank2"]
        allowed_values = config.get_allowed_values_for_general_interface()
        for v in invalid_values:
            self.assertNotEqual(config.set_sensor_interfaces(v),AVConfigParserErrors.ALL_OK)
        for v in allowed_values:
            self.assertEqual(config.set_sensor_interfaces(v),AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_sensor_interfaces(),v)
        valid_list = ", ".join(allowed_values)
        self.assertEqual(config.set_sensor_interfaces(valid_list),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.get_sensor_interfaces(),valid_list)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_interfaces(valid_list)[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_sensor_ip(self):
        valid = ["10.5.5.1", "192.168.2.2"]
        invalid = ["192.168.565.1", "a"*256, " test", "ݒ���p���'jo1�lߌ�vם˖$1�qu�uԞjqcn�ײޟ��"]
        # profile = Server,Sensor,Database,Framework
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for value in valid:
            self.assertNotEqual(config.set_sensor_ip(value), AVConfigParserErrors.ALL_OK)
        for value in invalid:
            self.assertNotEqual(config.set_sensor_ip(value), AVConfigParserErrors.ALL_OK)
        del config

        # profile = Server (nothing change)
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup6.conf")
        for value in valid:
            self.assertEqual(config.set_sensor_ip(value), AVConfigParserErrors.ALL_OK)
            self.assertNotEqual(config.get_sensor_ip(), value)
        for value in invalid:#all allow not sensor profile
            self.assertEqual(config.set_sensor_ip(value), AVConfigParserErrors.ALL_OK)
        del config


        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_ip(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config

    def test_set_sensor_monitors(self):
        """The set_sensor_monitors receives a string (comma separated) of detector plugins.
        if none or "" -> set to empty value ""
        """
        detector_list = get_current_monitor_plugin_list_clean()
        detector_list = detector_list[:-1]
        detector_list = ', '.join(detector_list)
        unknwon_lists = ["prads,pof,unknown","ssh, ossec,theinvalidplugin",888]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for v in unknwon_lists:
            self.assertNotEqual(config.set_sensor_detectors(v),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.set_sensor_monitors(detector_list),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.get_sensor_monitors(),detector_list)

        self.assertEqual(config.set_sensor_monitors(None),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.get_sensor_monitors(),"")

        self.assertEqual(config.set_sensor_monitors(""),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.get_sensor_monitors(),"")
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_monitors("")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_sensor_mservers(self):
        valid = ["192.168.1.2,4001,True,True,3,192.168.2.2,40003;192.168.1.3,40001,True,True,3,192.168.2.2,40003","192.168.1.2,4001,True,True,3,192.168.2.2,40003",None,""]
        invalid = ["192.168.1.2,AA,True,True,3,192.168.2.2,40003;192.168.1.3,40001,True,True,3,192.168.2.2,40003","192.168.2.2"]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        for v in valid:
            self.assertEqual(config.set_sensor_mservers(v),AVConfigParserErrors.ALL_OK)
            if v == "" or not v:
                v = config.get_default_value(config.SENSOR_SECTION_NAME,config.SECTION_SENSOR_MSERVER)
            self.assertEqual(config.get_sensor_mservers(),v)
        for v in invalid:
            self.assertNotEqual(config.set_sensor_mservers(v),AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_mservers(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_sensor_name(self):
        """ip or ascii 4,16"""
        valid = ["validsensor", "valid.valid", "k"*16, "k"*8]
        invalid = ["192.168.2.2","", None, "k"*3, "k"*17, "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" ] 
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for v in valid:
            self.assertEqual(config.set_sensor_name(v), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_sensor_name(), v)
        for v in invalid:
            self.assertNotEqual(config.set_sensor_name(v), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_name(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_sensor_netflow(self):
        """self.YES_NO_CHOICES
        """
        valid = [ "yes", "no", "",None]
        invalid = [0, 1,  "hhh", "�lߌ�vם˖$1"]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        default_value = config.get_default_value(config.FIREWALL_SECTION_NAME, config.SECTION_FIREWALL_ACTIVE)
        for v in valid:
            self.assertEqual(config.set_sensor_netflow(v), AVConfigParserErrors.ALL_OK)
            if v == "" or not v:
                v = default_value
            self.assertEqual(config.get_sensor_netflow(), v)
        for v in invalid:
            self.assertNotEqual(config.set_sensor_netflow(v), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_netflow(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_sensor_netflow_remote_collector_port(self):
        """There isn't default port
        """
        valid = [ "0", "235", 25, 536]
        invalid = ["",None, "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" , "1234567"] 

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for port in valid:
            self.assertEqual(config.set_sensor_netflow_remote_collector_port(port), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_sensor_netflow_remote_collector_port(), port)
        for port in invalid:
            self.assertNotEqual(config.set_sensor_netflow_remote_collector_port(port), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_netflow_remote_collector_port(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_sensor_networks(self):
        """Default = "192.168.0.0/16,172.16.0.0/12,10.0.0.0/8"
        List of valids cidr
        """
        valid = [ None,"",  "192.168.0.0/16,172.16.0.0/12,10.0.0.0/8",  "192.168.0.0/16,172.16.0.0/12", "192.168.0.0/16"]
        invalid = ["192.168.1,1/25", "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" , "1234567"] 

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for v in valid:
            self.assertEqual(config.set_sensor_networks(v), AVConfigParserErrors.ALL_OK)
            if v == "" or v == None:
                v = config.get_default_value(config.SENSOR_SECTION_NAME, config.SECTION_SENSOR_NETWORKS)
            self.assertEqual(config.get_sensor_networks(), v)
        for v in invalid:
            self.assertNotEqual(config.set_sensor_networks(v), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_networks(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_sensor_pci_express(self):
        """readonly"""
        pass


    def test_set_sensor_tzone(self):
        """readonly"""
        pass


    def test_set_sensor_asec(self):
        """self.YES_NO_CHOICES
        """
        valid = [ "yes", "no", "",None]
        invalid = [0, 1,  "hhh", "�lߌ�vם˖$1"]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        default_value = config.get_default_value(config.SENSOR_SECTION_NAME, config.SECTION_SENSOR_ASEC)
        for v in valid:
            self.assertEqual(config.set_sensor_asec(v), AVConfigParserErrors.ALL_OK)
            if v == "" or not v:
                v = default_value
            self.assertEqual(config.get_sensor_asec(), v)
        for v in invalid:
            self.assertNotEqual(config.set_sensor_asec(v), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_sensor_asec(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_server_alienvault_ip_reputation(self):
        """Default enabled.
        ENABLE_DISABLE_CHOICES (enabled,disabled)
        """
        valid = ["",None,"enabled","disabled"]
        invalid = [1,23, "kjagñlajl", " ", "�lߌ�vם˖$1�qU�"]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for v in valid:
            self.assertEqual(config.set_server_alienvault_ip_reputation(v),AVConfigParserErrors.ALL_OK)
            if v=="" or v==None:
                v = config.get_default_value(config.SERVER_SECTION_NAME, config.SECTION_SERVER_ALIENVAULT_IP_REPUTATION)
            self.assertEqual(config.get_server_alienvault_ip_reputation(),v)
        for v in invalid:
            self.assertNotEqual(config.set_server_alienvault_ip_reputation(v),AVConfigParserErrors.ALL_OK)

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_server_alienvault_ip_reputation(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_server_server_ip(self):
        valid = ["10.5.5.1", "192.168.2.2"]
        invalid = ["192.168.565.1", "a"*256, " test", "ݒ���p���'jo1�lߌ�vם˖$1�qu�uԞjqcn�ײޟ��"]
        # profile = Server,Sensor,Database,Framework
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for value in valid:
            self.assertNotEqual(config.set_server_server_ip(value), AVConfigParserErrors.ALL_OK)
        for value in invalid:
            self.assertNotEqual(config.set_server_server_ip(value), AVConfigParserErrors.ALL_OK)
        del config

        # profile = Sensor
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup7.conf")
        for value in valid:
            self.assertEqual(config.set_server_server_ip(value), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_server_server_ip(), value)
        for value in invalid:#all allow not sensor profile
            self.assertEqual(config.set_server_server_ip(value), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_server_server_ip(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_server_server_plugins(self):
        """Read only
        """
        pass


    def test_set_server_pro(self):
        """Read only
        """
        pass


    def test_set_snmp_community(self):
        """default: public
        ascii except @
        """
        valid = [None,"","public","validpassword", "valid.valid", "k"*16, "k"*8, "192.168.2.2"]
        invalid = ["invalid@p", "k"*3, "k"*17, "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" ] 
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for v in valid:
            self.assertEqual(config.set_snmp_comunity(v), AVConfigParserErrors.ALL_OK)
            if v == None or v=="":
                v = "public"
            self.assertEqual(config.get_snmp_comunity(), v)
        for v in invalid:
            self.assertNotEqual(config.set_snmp_comunity(v), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_snmp_comunity(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_snmp_snmp_comunity(self):
        """readonly
        """
        pass


    def test_set_snmp_snmpd(self):
        """default:yes
        YES_NO_CHOICES"""
        valid = [ "yes", "no", "",None]
        invalid = [0, 1,  "hhh", "�lߌ�vם˖$1"]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        default_value = config.get_default_value(config.SNMP_SECTION_NAME, config.SECTION_SNMP_SNMPD)
        for v in valid:
            self.assertEqual(config.set_snmp_snmpd(v), AVConfigParserErrors.ALL_OK)
            if v == "" or not v:
                v = default_value
            self.assertEqual(config.get_snmp_snmpd(), v)
        for v in invalid:
            self.assertNotEqual(config.set_snmp_snmpd(v), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_snmp_snmpd(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_snmp_snmptrap(self):
        """default:yes
        YES_NO_CHOICES"""
        valid = [ "yes", "no", "",None]
        invalid = [0, 1,  "hhh", "�lߌ�vם˖$1"]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        default_value = config.get_default_value(config.SNMP_SECTION_NAME, config.SECTION_SNMP_SNMPTRAP)
        for v in valid:
            self.assertEqual(config.set_snmp_snmptrap(v), AVConfigParserErrors.ALL_OK)
            if v == "" or not v:
                v = default_value
            self.assertEqual(config.get_snmp_snmptrap(), v)
        for v in invalid:
            self.assertNotEqual(config.set_snmp_snmptrap(v), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_snmp_snmptrap(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_update_update_proxy(self):
        """default:disabled
        PROXY_VALUES = manual, disabled o alienvault-center"""
        # check default values
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        self.assertEqual(config.set_update_update_proxy("disabled"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.get_update_update_proxy_dns(),config.get_default_value(config.UPDATE_SECTION_NAME, config.SECTION_UPDATE_PROXY_DNS))
        self.assertEqual(config.get_update_update_proxy_port(),config.get_default_value(config.UPDATE_SECTION_NAME, config.SECTION_UPDATE_PROXY_PORT))
        self.assertEqual(config.get_update_update_proxy_user(),config.get_default_value(config.UPDATE_SECTION_NAME, config.SECTION_UPDATE_PROXY_USER))
        self.assertEqual(config.get_update_update_proxy_pass(),config.get_default_value(config.UPDATE_SECTION_NAME, config.SECTION_UPDATE_PROXY_PASSWORD))

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_update_update_proxy("disabled")[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_update_update_proxy_dns(self):
        """default:my.proxy.com, hostname or ip"""
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        valid = ["my.proxy.com","unhostvalid-valid", "valid.valid", "k"*63, "192.168.2.2"]
        invalid = ["k"*64, "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��"]
        for v in valid:
            self.assertEqual(config.set_update_update_proxy_dns(v), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_update_update_proxy_dns(), v)
        for v in invalid:
            self.assertNotEqual(config.set_update_update_proxy_dns(v), AVConfigParserErrors.ALL_OK)

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_update_update_proxy_dns(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_update_update_proxy_pass(self):
        """[disabled, ascii characters {8,16}]"""
        valid_passwords = ["unhostvalid-vali", "valid.valid", "k"*16, "192.168.2.2"]
        invalid_passords = ["ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��"] 

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for valid_p in valid_passwords:
            self.assertEqual(config.set_update_update_proxy_pass(valid_p), AVConfigParserErrors.ALL_OK)
            self.assertEqual(config.get_update_update_proxy_pass(), valid_p)
        for invalid_p in invalid_passords:
            self.assertNotEqual(config.set_update_update_proxy_pass(invalid_p), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_update_update_proxy_pass(valid_passwords[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_update_update_proxy_port(self):
        """default:disabled"""
        valid = ["disabled","",None, "0", "235", 25, 536]
        invalid = [ "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" , "1234567"] 

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for port in valid:
            self.assertEqual(config.set_update_update_proxy_port(port), AVConfigParserErrors.ALL_OK)
            if port == None or port == "":
                port = config.get_default_value(config.UPDATE_SECTION_NAME, config.SECTION_UPDATE_PROXY_PORT)
            self.assertEqual(config.get_update_update_proxy_port(), port)
        for port in invalid:
            self.assertNotEqual(config.set_update_update_proxy_port(port), AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.set_update_update_proxy_port(25),AVConfigParserErrors.ALL_OK)
        self.assertFalse(config.has_errors())
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_update_update_proxy_port(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_update_update_proxy_user(self):
        """default:disabled
        ascii 4-16
        """
        valid_users = ["thisisatest","disabled","", "valid.valid", "k"*16, "k"*8, "192.168.2.2"]
        invalid_users = ["123", "k"*3, "k"*17, "â", "ݒ���p���'jo1�lߌ�vם˖$1�qu�uԞjqcn�ײޟ��"] 
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for user in valid_users:
            self.assertEqual(config.set_update_update_proxy_user(user), AVConfigParserErrors.ALL_OK)
            if user == "" or user==None:
                user = config.get_default_value(config.UPDATE_SECTION_NAME, config.SECTION_UPDATE_PROXY_USER)
            self.assertEqual(config.get_update_update_proxy_user(), user)
        for user in invalid_users:
            self.assertNotEqual(config.set_update_update_proxy_user(user), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_update_update_proxy_user(valid_users[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_vpn_vpn_infraestructure(self):
        valid = [ "yes", "no", "",None]
        invalid = [0, 1,  "hhh", "�lߌ�vם˖$1"]
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        default_value = config.get_default_value(config.VPN_SECTION_NAME, config.SECTION_VNP_INFRAESTRUCTURE)
        for v in valid:
            self.assertEqual(config.set_vpn_vpn_infraestructure(v), AVConfigParserErrors.ALL_OK)
            if v == "" or not v:
                v = default_value
            self.assertEqual(config.get_vpn_vpn_infraestructure(), v)
        for v in invalid:
            self.assertNotEqual(config.set_vpn_vpn_infraestructure(v), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_vpn_vpn_infraestructure(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_vpn_vpn_net(self):
        valid = [ None,"",  "192.168.0",]
        invalid = ["192.168.1,1/25", "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" , "1234567"] 

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for v in valid:
            self.assertEqual(config.set_vpn_vpn_net(v), AVConfigParserErrors.ALL_OK)
            if v == "" or v == None:
                v = config.get_default_value(config.VPN_SECTION_NAME, config.SECTION_VNP_NET)
            self.assertEqual(config.get_vpn_vpn_net(), v)
        for v in invalid:
            self.assertNotEqual(config.set_vpn_vpn_net(v), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_vpn_vpn_net(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_vpn_vpn_netmask(self):
        """Read only
        """
        valid = [ None,"",  "255.255.0.0"]
        invalid = ["192.168.1,1/25", "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" , "1234567"] 

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for v in valid:
            self.assertEqual(config.set_vpn_vpn_netmask(v), AVConfigParserErrors.ALL_OK)
            if v == "" or v == None:
                v = config.get_default_value(config.VPN_SECTION_NAME, config.SECTION_VNP_NETMASK)
            self.assertEqual(config.get_vpn_vpn_netmask(), v)
        for v in invalid:
            self.assertNotEqual(config.set_vpn_vpn_netmask(v), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_vpn_vpn_netmask(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def test_set_vpn_vpn_port(self):
        valid = ["",None, "0", "235", 25, 536]
        invalid = [ "Â", "ݒ���P���'JO1�lߌ�vם˖$1�qU�UԞJQcN�ײޟ��" , "1234567"] 

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "ossim_setup1.conf")
        for port in valid:
            self.assertEqual(config.set_vpn_vpn_port(port), AVConfigParserErrors.ALL_OK)
        for port in invalid:
            self.assertNotEqual(config.set_vpn_vpn_port(port), AVConfigParserErrors.ALL_OK)
        del config

        # Bad config file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH + "bad_file_name")
        self.assertEqual(config.set_vpn_vpn_port(valid[0])[0], AVConfigParserErrors.FILE_NOT_LOADED)
        del config


    def tearDown(self):
        pass
