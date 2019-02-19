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
from ossimsetupconfig import AVOssimSetupConfigHandler
from avconfigparsererror import AVConfigParserErrors
from  utils import *
TEST_FILES_PATH = os.path.abspath(os.path.join(__file__, os.pardir))+"/test_data/"


"""
Test files description:
ossim_setup1.conf: Well formatted file.
ossim_setup2.conf: Float number
ossim_setup3.conf: Invalid file
ossim_setup4.conf:
ossim_setup5.conf: Invalid values on load
"""
class TestAVOssimSetupConfigHandlerChecks(unittest.TestCase):
    def setUp(self):
        pass

    def test_check_general_admin_dns(self):
        """admin dns has to be a valid IP v4 address.
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.check_general_admin_dns("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_dns("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_dns(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_dns(None),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_general_admin_gateway(self):
        """admin gateway has to be a valid IP v4 address.
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.check_general_admin_gateway("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_gateway("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_gateway(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_gateway(None),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_general_admin_ip(self):
        """admin ip has to be a valid IP v4 address.
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.check_general_admin_ip("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_ip("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_ip(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_ip(None),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_general_admin_netmask(self):
        """admin netmask has to be a valid IP v4 address.
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_general_admin_netmask("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_netmask("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_netmask(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_admin_netmask(None),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_admin_netmask("255.255.255.255"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_admin_netmask("255.255.255.0"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_admin_netmask("255.255.255.0"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_general_domain(self):
        """admin domain has to be a valid domain
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_general_domain("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_domain("255.255.255.0"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_domain(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_domain(None),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_domain("www.mydomain.com"),AVConfigParserErrors.ALL_OK)
        
        self.assertEqual(config.check_general_domain("value"),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_general_email_notify(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_general_email_notify("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_email_notify("255.255.255.0"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_email_notify(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_email_notify("Ç"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_email_notify("www.a@b.com"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_email_notify("myq@a.com"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_email_notify(""),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_general_hostname(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        invalidhostname = "ab"*100
        self.assertNotEqual(config.check_general_hostname(invalidhostname),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_hostname("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_hostname("255.255.255.0"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_hostname(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_hostname("www.a@b.com"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_hostname("myq@a.com"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_hostname(""),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_hostname("machinename-com"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_general_interface(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_general_interface("eth33443"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_interface("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_interface(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_interface(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_interface("lo"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_interface("eth0"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_general_mailserver_relay(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_general_mailserver_relay(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_mailserver_relay(None),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_mailserver_relay("no"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_mailserver_relay("mydomain.com"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_mailserver_relay("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_general_mailserver_relay_passwd(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_general_mailserver_relay_passwd(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_mailserver_relay_passwd(None),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_mailserver_relay_passwd("mydomain.com"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_mailserver_relay_passwd("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_general_mailserver_relay_port(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_general_mailserver_relay_port(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_mailserver_relay_port(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_mailserver_relay_port("no"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_mailserver_relay_port("mydomain.com"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_mailserver_relay_port("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_mailserver_relay_port("1132"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_general_mailserver_relay_user(self):
        invaliduser = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_general_mailserver_relay_user(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_mailserver_relay_user(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_mailserver_relay_user(invaliduser),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_mailserver_relay_user("invaliduser otro"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_mailserver_relay_user("1132"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_mailserver_relay_user("un-user"),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_general_ntp_server(self):
        invaliduser = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_general_ntp_server(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_ntp_server(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_ntp_server(invaliduser),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_general_ntp_server("invaliduser otro"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_ntp_server("1132"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_ntp_server("un-user"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_general_ntp_server("113.23.22.2"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_database_db_ip(self):
        invaliduser = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_database_db_ip(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_database_db_ip(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_database_db_ip(invaliduser),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_database_db_ip("invaliduser otro"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_database_db_ip("1132"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_database_db_ip("un-user"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_database_db_ip("113.23.22.2"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_database_db_ip("127.23.22.2"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_database_pass(self):
        invalidpass = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        
        self.assertNotEqual(config.check_database_pass(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_database_pass(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_database_pass(invalidpass),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_database_pass("mydomain.com"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_database_pass("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_database_user(self):
        invaliduser = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        
        self.assertNotEqual(config.check_database_user(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_database_user(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_database_user(invaliduser),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_database_user("mydomain.com"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_database_user("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_firewall_active(self):
        invalidvalue = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        
        self.assertNotEqual(config.check_firewall_active(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_firewall_active(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_firewall_active(invalidvalue),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_firewall_active(0),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_firewall_active("TRUE"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_firewall_active("YeS"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_firewall_active("no"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_firewall_active("1"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_firewall_active("0"),AVConfigParserErrors.ALL_OK)
        del config

    def test_framework_https_cert(self):
        invalidvalue = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        
        self.assertNotEqual(config.check_framework_https_cert(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_cert(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_cert(invalidvalue),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_cert(0),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_cert("TRUE"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_cert("YeS"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_cert("1"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_cert("0"),AVConfigParserErrors.ALL_OK)
        try:
            tmpfile ="/tmp/certificate.cert"
            f = open(tmpfile,"w")
            f.close()
            self.assertEqual(config.check_framework_https_cert(tmpfile),AVConfigParserErrors.ALL_OK)
            os.remove(tmpfile)
        except:
            print "Can't check a valid certificate"
        del config


    def test_framework_https_keyt(self):
        invalidvalue = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        
        self.assertNotEqual(config.check_framework_https_key(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_key(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_key(invalidvalue),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_key(0),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_key("TRUE"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_key("YeS"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_key("1"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_https_key("0"),AVConfigParserErrors.ALL_OK)
        try:
            tmpfile ="/tmp/certificate.cert"
            f = open(tmpfile,"w")
            f.close()
            self.assertEqual(config.check_framework_https_key(tmpfile),AVConfigParserErrors.ALL_OK)
            os.remove(tmpfile)
        except:
            print "Can't check a valid certificate"
        del config


    def test_check_framework_ip(self):
        """framework ip has to be a valid IP v4 address.
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.check_framework_ip("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_ip("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_ip(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_framework_ip(None),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_sensor_detectors(self):
        detector_list = get_current_detector_plugin_list()
        detector_list = ','.join(detector_list)
        unknwon_list = "prads,pof,unknown"
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_sensor_detectors("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_detectors("value"),AVConfigParserErrors.ALL_OK)
        
        self.assertNotEqual(config.check_sensor_detectors(unknwon_list),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_detectors(""),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_detectors(detector_list),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_detectors(None),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_sensor_ids_rules_flow_control(self):
        invalidvalue = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        
        self.assertNotEqual(config.check_sensor_ids_rules_flow_control(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_ids_rules_flow_control(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_ids_rules_flow_control(invalidvalue),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_ids_rules_flow_control(0),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_ids_rules_flow_control("TRUE"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_ids_rules_flow_control("yes"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_ids_rules_flow_control("no"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_ids_rules_flow_control("1"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_ids_rules_flow_control("0"),AVConfigParserErrors.ALL_OK)
        del config



    def test_check_sensor_interfaces(self):
        invalidvalue = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        
        self.assertNotEqual(config.check_sensor_interfaces(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_interfaces(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_interfaces(invalidvalue),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_interfaces(0),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_interfaces("TRUE"),AVConfigParserErrors.ALL_OK)

        invalid = "lo,wlank2"
        valid = "eth0"
        self.assertNotEqual(config.check_sensor_interfaces(invalid),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_interfaces(valid),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_sensor_ip(self):
        """sensor ip has to be a valid IP v4 address.
        """
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.check_sensor_ip("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_ip("value"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_ip(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_ip(None),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_sensor_monitors(self):
        monitor_list = get_current_monitor_plugin_list_clean()
        monitor_list = ','.join(monitor_list)
        unknwon_list = "prads-monitor,pof,unknown"
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_sensor_monitors("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_monitors("value"),AVConfigParserErrors.ALL_OK)
        
        self.assertNotEqual(config.check_sensor_monitors(unknwon_list),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_monitors(""),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_monitors(monitor_list),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_monitors(None),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_sensor_mserver(self):
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_sensor_mserver("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_mserver("value"),AVConfigParserErrors.ALL_OK)
        
        self.assertNotEqual(config.check_sensor_mserver(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_mserver(None),AVConfigParserErrors.ALL_OK)
        #SERVER_IPPORT,SEND_EVENTS(True/False),ALLOW_FRMK_DATA(True/False),PRIORITY (0-5),FRMK_IP,FRMK_PORT; another one
        valid1= "192.168.1.2,4001,True,True,3,192.168.2.2,40003;192.168.1.3,40001,True,True,3,192.168.2.2,40003"
        invalid1= "192.168.1.2,AA,True,True,3,192.168.2.2,40003;192.168.1.3,40001,True,True,3,192.168.2.2,40003"
        self.assertEqual(config.check_sensor_mserver(valid1),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_mserver(invalid1),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_sensor_name(self):
        #check_sensor_name
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_sensor_name("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_name("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_name(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_name(None),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_name("value-a"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_sensor_netflow(self):
        invalidvalue = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        
        self.assertNotEqual(config.check_sensor_netflow(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_netflow(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_netflow(invalidvalue),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_netflow(0),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_netflow("TRUE"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_netflow("yes"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_netflow("1"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_netflow("0"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_sensor_netflow_remote_collector_port(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_sensor_netflow_remote_collector_port(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_netflow_remote_collector_port(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_netflow_remote_collector_port("no"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_netflow_remote_collector_port("mydomain.com"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_netflow_remote_collector_port("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_netflow_remote_collector_port("1132"),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_sensor_networks(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.check_sensor_networks(""),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_networks(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_networks("nnn"),AVConfigParserErrors.ALL_OK)
        valid = "192.168.2.0/16,10.0.0.0/24"
        self.assertEqual(config.check_sensor_networks(valid),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_sensor_pci_express(self):
        """Read only"""
        pass
    def test_check_sensor_tzone(self):
        """Read only"""
        pass

    def test_check_sensor_asec(self):
        invalidvalue = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")

        self.assertNotEqual(config.check_sensor_asec(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_asec(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_asec(invalidvalue),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_asec(0),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_asec("TRUE"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_sensor_asec("yes"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_asec("1"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_sensor_asec("0"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_server_alienvault_ip_reputation(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_server_alienvault_ip_reputation(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_server_alienvault_ip_reputation(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_server_alienvault_ip_reputation("nnn"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_server_alienvault_ip_reputation("enabled"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_server_alienvault_ip_reputation("disabled"),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_server_ip(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.check_server_ip("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_server_ip("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_server_ip(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_server_ip(None),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_server_server_plugins(self):
        pass
    def test_check_server_pro(self):
        pass

    def test_check_snmp_community(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.check_snmp_community("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_snmp_community(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_snmp_community(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_snmp_community("Invalid@Community"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_snmp_community("validcommunity"),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_snmp_snmp_community(self):
        pass
    def test_check_snmp_snmpd(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_snmp_snmpd("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_snmp_snmpd(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_snmp_snmpd(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_snmp_snmpd("Invalid@Community"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_snmp_snmpd("yes"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_snmp_snmpd("no"),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_snmp_snmptrap(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_snmp_snmptrap("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_snmp_snmptrap(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_snmp_snmptrap(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_snmp_snmptrap("Invalid@Community"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_snmp_snmptrap("yes"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_snmp_snmptrap("no"),AVConfigParserErrors.ALL_OK)
        del config

    def test_check_update_update_proxy(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_update_update_proxy("value"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy("Invalid@Community"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy("disabled"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy("manual"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy("alienvault-proxy"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_update_update_proxy_dns(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_update_update_proxy_dns(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy_dns(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy_dns("Invalid@Community"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy_dns("192.168.2.1"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy_dns("systemaname"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy_dns("disabled"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_update_update_proxy_pass(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_update_update_proxy_pass(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy_pass(None),AVConfigParserErrors.ALL_OK)
        #7833 
        #self.assertNotEqual(config.check_update_update_proxy_pass("no"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy_pass("mydomain.com"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy_pass("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_update_update_proxy_port(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_update_update_proxy_port(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy_port(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy_port("no"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy_port("mydomain.com"),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy_port("192.168.2.2"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy_port("1132"),AVConfigParserErrors.ALL_OK)
        del config


    def test_check_update_update_proxy_user(self):
        invaliduser = "AAAA" * 100
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertNotEqual(config.check_update_update_proxy_user(""),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy_user(None),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy_user(invaliduser),AVConfigParserErrors.ALL_OK)
        self.assertNotEqual(config.check_update_update_proxy_user("invaliduser otro"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy_user("1132"),AVConfigParserErrors.ALL_OK)
        self.assertEqual(config.check_update_update_proxy_user("un-user"),AVConfigParserErrors.ALL_OK)
        del config
    def test_check_vpn_vpn_infraestructure(self):
        pass
    def test_check_vpn_vpn_net(self):
        pass
    def test_check_vpn_vpn_netmask(self):
        pass
    def test_check_vpn_vpn_port(self):
        pass
    
    def tearDown(self):
        pass
