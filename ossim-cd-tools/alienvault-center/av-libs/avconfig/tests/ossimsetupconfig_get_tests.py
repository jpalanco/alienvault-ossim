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

import mock
from ossimsetupconfig import AVOssimSetupConfigHandler
from configparsererror import AVConfigParserErrors
import utils
TEST_FILES_PATH = os.path.abspath(os.path.join(__file__, os.pardir))+"/test_data/"


"""
Test files description:
ossim_setup1.conf: Well formatted file.
ossim_setup2.conf: Float number
ossim_setup3.conf: Invalid file
ossim_setup4.conf:

ossim_setup5.conf: Invalid values on load
{'firewall': {'active': (3, 'Invalid value. Please enter yes or no<invalidvalue>')}, 'sensor': {'monitors': (1018, 'Error. Invalid monitor plugin! Monitor plugin not found <unknown-plugin>')}}

"""
class TestAVOssimSetupConfigHandlerGets(unittest.TestCase):
    def setUp(self):
        pass

    def test_get_general_admin_dns(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_admin_dns(),"8.8.8.8")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_admin_dns(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_admin_dns(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_admin_dns(),None)
        del config


    def test_get_general_admin_gateway(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_admin_gateway(),"192.168.5.5")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_admin_gateway(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_admin_gateway(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_admin_gateway(),None)
        del config


    def test_get_general_admin_ip(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_admin_ip(),"192.168.2.22")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_admin_ip(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_admin_ip(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_admin_ip(),None)
        del config


    def test_get_general_admin_netmask(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_admin_netmask(),"255.255.255.0")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_admin_netmask(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_admin_netmask(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_admin_netmask(),None)
        del config


    def test_get_general_domain(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_domain(),"alienvault")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_domain(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_domain(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_domain(),None)
        del config


    def test_get_general_email_notify(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_email_notify(),"system@alienvault.com")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_email_notify(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_email_notify(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_email_notify(),None)
        del config


    def test_get_general_hostname(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_hostname(),"crgalienvault4free")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_hostname(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_hostname(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_hostname(),None)
        del config


    def test_get_general_interface(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_interface(),"eth0")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_interface(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_interface(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_interface(),None)
        del config


    def test_get_general_mailserver_relay(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_mailserver_relay(),"smtp.mail.yahoo.com")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_mailserver_relay(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_mailserver_relay(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_mailserver_relay(),None)
        del config


    def test_get_general_mailserver_relay_passwd(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_mailserver_relay_passwd(),"validpassword")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_mailserver_relay_passwd(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_mailserver_relay_passwd(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_mailserver_relay_passwd(),None)
        del config


    def test_mailserver_relay_port(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_mailserver_relay_port(),"587")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_mailserver_relay_port(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_mailserver_relay_port(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_mailserver_relay_port(),None)
        del config

    def test_get_general_mailserver_relay_user(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_mailserver_relay_user(),"prueba_mail2013@yahoo.es")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_mailserver_relay_user(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_mailserver_relay_user(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_mailserver_relay_user(),None)
        del config


    def test_get_general_ntp_server(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_general_ntp_server(),"no")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_ntp_server(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_ntp_server(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_ntp_server(),None)
        del config


    def test_get_general_profile(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        listprofiles = 'Server,Sensor,Framework,Database'
        self.assertEqual(config.get_general_profile(),listprofiles)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_profile(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_profile(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_profile(),None)
        del config


    def test_get_general_profile_list(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        listprofiles = ["Server","Sensor","Framework","Database"]
        self.assertEqual(config.get_general_profile_list(),listprofiles)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_general_profile_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_general_profile_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_general_profile_list(),None)
        del config


    def test_get_database_db_ip(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_database_db_ip(),"127.0.0.1")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_database_db_ip(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_database_db_ip(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_database_db_ip(),None)
        del config

    def test_get_database_pass(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_database_pass(),"IPTaoThDNw")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_database_pass(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_database_pass(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_database_pass(),None)
        del config


    def test_get_database_user(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_database_user(),"root")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_database_user(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_database_user(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_database_user(),None)
        del config


    def test_get_expert_profile(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_expert_profile(),"server")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_expert_profile(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_expert_profile(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_expert_profile(),None)
        del config


    def test_get_firewall_active(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_firewall_active(),"yes")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_firewall_active(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_firewall_active(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_firewall_active(),None)
        del config


    def test_get_framework_framework_https_cert(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_framework_framework_https_cert(),"default")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_framework_framework_https_cert(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_framework_framework_https_cert(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_framework_framework_https_cert(),None)
        del config


    def test_get_framework_framework_https_key(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_framework_framework_https_key(),"default")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_framework_framework_https_key(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_framework_framework_https_key(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_framework_framework_https_key(),None)
        del config


    def test_get_framework_framework_ip(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_framework_framework_ip(),"192.168.2.22")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_framework_framework_ip(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_framework_framework_ip(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_framework_framework_ip(),None)
        del config


    def test_get_sensor_detectors(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        detector_list = 'ossec,pam_unix,prads,snortunified,ssh,sudo'
        self.assertEqual(config.get_sensor_detectors(),detector_list)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_detectors(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_detectors(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_detectors(),None)
        del config


    def test_get_sensor_detectors_list(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        detector_list = ['ossec', 'pam_unix', 'prads', 'snortunified', 'ssh', 'sudo']
        self.assertEqual(config.get_sensor_detectors_list(),detector_list)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_detectors_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_detectors_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_detectors_list(),None)
        del config

    def test_get_sensor_ids_rules_flow_control(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_ids_rules_flow_control(),"yes")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_ids_rules_flow_control(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_ids_rules_flow_control(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_ids_rules_flow_control(),None)
        del config


    def test_get_sensor_interfaces(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_interfaces(),"eth0")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_interfaces(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_interfaces(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_interfaces(),None)
        del config

    def test_get_sensor_interfaces_list(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_interfaces_list()[0],"eth0")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_interfaces_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_interfaces_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_interfaces_list(),None)
        del config
    def test_get_sensor_ip(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_ip(),"192.168.2.22")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_ip(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_ip(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_ip(),None)
        del config


    def test_get_sensor_monitors(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        monitors_list = 'nmap-monitor, ntop-monitor, ossim-monitor, ping-monitor, whois-monitor, wmi-monitor'
        self.assertEqual(config.get_sensor_monitors(),monitors_list)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_monitors(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_monitors(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_monitors(),None)
        del config

    def test_get_sensor_monitors_list(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        monitors_list = ['nmap-monitor', 'ntop-monitor', 'ossim-monitor', 'ping-monitor', 'whois-monitor', 'wmi-monitor']
        self.assertEqual(config.get_sensor_monitors_list(),monitors_list)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_monitors_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_monitors_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_monitors_list(),None)
        del config
    def test_get_sensor_mservers(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_mservers(),"no")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_mservers(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_mservers(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_mservers(),None)
        del config


    def test_get_sensor_name(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_name(),"alienvault")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_name(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_name(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_name(),None)
        del config


    def test_get_sensor_netflow(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_netflow(),"yes")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_netflow(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_netflow(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_netflow(),None)
        del config


    def test_get_sensor_netflow_remote_collector_port(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_netflow_remote_collector_port(),"555")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_netflow_remote_collector_port(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_netflow_remote_collector_port(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_netflow_remote_collector_port(),None)
        del config


    def test_get_sensor_networks(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        net_list = '192.168.0.0/16,172.16.0.0/12,10.0.0.0/8'
        self.assertEqual(config.get_sensor_networks(),net_list)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_networks(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_networks(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_networks(),None)
        del config

    def test_get_sensor_networks_list(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        net_list = ['192.168.0.0/16', '172.16.0.0/12', '10.0.0.0/8']
        self.assertEqual(config.get_sensor_networks_list(),net_list)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_networks_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_networks_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_networks_list(),None)
        del config

    def test_get_sensor_pci_express(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_pci_express(),"no")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_pci_express(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_pci_express(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_pci_express(),None)
        del config


    def test_get_sensor_tzone(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_tzone(),"Europe/Madrid")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_tzone(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_tzone(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_tzone(),None)
        del config


    def test_get_sensor_asec(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_sensor_asec(),"yes")
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_sensor_asec(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_sensor_asec(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_sensor_asec(),None)
        del config


    def test_get_server_alienvault_ip_reputation(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_server_alienvault_ip_reputation(),"enabled")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_server_alienvault_ip_reputation(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_server_alienvault_ip_reputation(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_server_alienvault_ip_reputation(),None)
        del config


    def test_get_server_server_ip(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_server_server_ip(),"127.0.0.1")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_server_server_ip(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_server_server_ip(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_server_server_ip(),None)
        del config

    def test_get_server_server_plugins_list(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        server_plugins = ['osiris', 'pam_unix', 'ssh', 'snare', 'sudo']
        self.assertEqual(config.get_server_server_plugins_list(),server_plugins)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_server_server_plugins_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_server_server_plugins_list(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_server_server_plugins_list(),None)
        del config

    def test_get_server_server_plugins(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        server_plugins = 'osiris, pam_unix, ssh, snare, sudo'
        self.assertEqual(config.get_server_server_plugins(),server_plugins)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_server_server_plugins(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_server_server_plugins(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_server_server_plugins(),None)
        del config


    def test_get_server_server_pro(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_server_server_pro(),"no")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_server_server_pro(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_server_server_pro(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_server_server_pro(),None)
        del config


    def test_get_snmp_comunity(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_snmp_comunity(),"public")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_snmp_comunity(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_snmp_comunity(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_snmp_comunity(),None)
        del config


    def test_get_snmp_snmp_comunity(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_snmp_snmp_comunity(),"public")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_snmp_snmp_comunity(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_snmp_snmp_comunity(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_snmp_snmp_comunity(),None)
        del config


    def test_get_snmp_snmpd(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_snmp_snmpd(),"no")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_snmp_snmpd(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_snmp_snmpd(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_snmp_snmpd(),None)
        del config


    def test_get_snmp_snmptrap(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_snmp_snmptrap(),"no")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_snmp_snmptrap(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_snmp_snmptrap(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_snmp_snmptrap(),None)
        del config

    def test_get_update_update_proxy(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_update_update_proxy(),"disabled")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_update_update_proxy(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_update_update_proxy(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_update_update_proxy(),None)
        del config


    def test_get_update_update_proxy_dns(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_update_update_proxy_dns(),"my.proxy.com")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_update_update_proxy_dns(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_update_update_proxy_dns(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_update_update_proxy_dns(),None)
        del config

    def test_get_update_update_proxy_pass(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_update_update_proxy_pass(),"disabled")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_update_update_proxy_pass(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_update_update_proxy_pass(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_update_update_proxy_pass(),None)
        del config


    def test_get_update_update_proxy_port(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_update_update_proxy_port(),"disabled")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_update_update_proxy_port(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_update_update_proxy_port(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_update_update_proxy_port(),None)
        del config


    def test_get_update_update_proxy_user(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_update_update_proxy_user(),"disabled")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_update_update_proxy_user(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_update_update_proxy_user(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_update_update_proxy_user(),None)
        del config


    def test_get_vpn_vpn_infraestructure(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_vpn_vpn_infraestructure(),"yes")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_vpn_vpn_infraestructure(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_vpn_vpn_infraestructure(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_vpn_vpn_infraestructure(),None)
        del config


    def test_get_vpn_vpn_net(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_vpn_vpn_net(),"10.67.68")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_vpn_vpn_net(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_vpn_vpn_net(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_vpn_vpn_net(),None)
        del config


    def test_get_vpn_vpn_netmask(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_vpn_vpn_netmask(),"255.255.255.0")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_vpn_vpn_netmask(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_vpn_vpn_netmask(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_vpn_vpn_netmask(),None)
        del config


    def test_get_vpn_vpn_port(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertEqual(config.get_vpn_vpn_port(),"33800")
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertEqual(config.get_vpn_vpn_port(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertEqual(config.get_vpn_vpn_port(),None)
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertEqual(config.get_vpn_vpn_port(),None)
        del config


    def test_get_dirty(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        self.assertFalse(config.get_dirty())
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        self.assertFalse(config.get_dirty())
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        self.assertFalse(config.get_dirty())
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1111.conf")
        self.assertFalse(config.get_dirty())
        del config



    def test_get_modified_values(self):
        #ossim-setup6.conf ->> profile only server. Sensor values won't be written
        #ossim-setup7.conf ->> profile sensor (ip can't be setted)
        #ossim-setup8.conf ->> profile sensor,server
        #ossim_setup11111.cong ->> nonexistent file
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup6.conf")
        mod_string = config.get_modified_values_string()
        self.assertEqual(mod_string, "")
        self.assertEqual(config.set_sensor_ip("192.168.55.55"),AVConfigParserErrors.ALL_OK)
        mod_values = config.get_modified_values()
        self.assertFalse(mod_values.has_key('sensor'))
        self.assertEqual(len(mod_values),0)
        mod_string = config.get_modified_values_string()
        self.assertEqual(mod_string, "")
        del config

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup7.conf")

        self.assertEqual(config.set_sensor_ip("192.168.55.55")[0],AVConfigParserErrors.SENSOR_IP_CANT_BE_CHANGED_PROFILE_IS_SENSOR)
        mod_values = config.get_modified_values()
        self.assertFalse(mod_values.has_key('sensor'))
        self.assertEqual(len(mod_values),0)
        mod_string = config.get_modified_values_string()
        self.assertEqual(mod_string, "")

        del config
        

        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup11111.conf")
        
        self.assertNotEqual(config.set_sensor_ip("192.168.55.55"),AVConfigParserErrors.ALL_OK)
        mod_values = config.get_modified_values()
        self.assertFalse(mod_values.has_key('sensor'))
        self.assertEqual(len(mod_values),0)
        mod_string = config.get_modified_values_string()
        self.assertEqual(mod_string, "")

        del config


    def test_get_error_list(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        error_list =  config.get_error_list()
        self.assertEqual(len(error_list),0)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1222.conf")
        error_list =  config.get_error_list()
        self.assertEqual(len(error_list), 1)
        del config
        
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        error_list =  config.get_error_list()
        self.assertEqual(len(error_list), 1)
        del config
        #this file has two errors_
        #{'firewall': {'active': (3, 'Invalid boolean value, allowed values (1,yes,true,si,on,0,false,off,no)<InvalidVAlue>')}, 'sensor': {'monitors': (1018, 'Invalid monitor plugin! Monitor plugin not found <unknown-plugin>')}}
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup5.conf")
        error_list =  config.get_error_list()
        self.assertEqual(len(error_list), 2)
        del config
    def test_get_sensor_ctx(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup7.conf")
        self.assertEqual (config.get_sensor_ctx(),"")
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup8.conf")
        self.assertEqual (config.get_sensor_ctx(),"ca6a80cc-4abf-11e3-92b6-a820662271ea") 


        

    def test_get_allowed_values_for_update_update_proxy(self):
        #TODO: the mock objects doens't run properly inside the class..
        #mock pro
        utils.get_is_professional = mock.Mock(return_value=True)
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        #self.assertEqual(config.get_allowed_values_for_update_update_proxy(),config.PROXY_VALUES)
        del config
        #mock free
        utils.get_is_professional = mock.Mock(return_value=False)
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        #self.assertEqual(config.get_allowed_values_for_update_update_proxy(),config.PROXY_VALUES_NO_PRO)
        del config

    def tearDown(self):
        pass
