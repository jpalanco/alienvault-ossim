from __future__ import print_function
from nose import with_setup
from nose.tools import raises
import unittest
import sys
import os
import random
import string
import difflib
from shutil import copyfile

from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler
from ansiblemethods.system.network import set_interfaces_roles, get_iface_list

ossim_setup = AVOssimSetupConfigHandler("/etc/ossim/ossim_setup.conf")
admin_ip = ossim_setup.get_general_admin_ip()

NET_IPS = {'eth1': "172.17.2.50", 'eth2': "172.17.2.51", 'eth3': "172.17.2.52", 'eth4': "172.17.2.53",
           'eth5': "172.17.2.54"}


class TestNetworkSetInterfaces(unittest.TestCase):
    """Class to test the set_interface_roles function"""

    @classmethod
    def setUpClass(cls):
        print("TestNetworkSetInterfaces::setup_class() before any methods in this class")

        rc, net_current_status = get_iface_list(admin_ip)
        cls.net_current_status = net_current_status

    @classmethod
    def tearDownClass(cls):
        # print ("TestNetworkSetInterfaces::tearDownClass() after any methods in this class")
        request_dic = {}
        if not isinstance(cls.net_current_status, dict):
            print("Can't tear down the interfaces... net_current_status invalid")
            return
        for eth, eth_data in cls.net_current_status.iteritems():
            role = eth_data['role']
            ipv4 = None
            netmask = None
            if eth == 'lo':
                continue
            # The admin interface can't be set
            if role is 'admin':
                continue
            if 'ipv4' in eth_data:
                if 'address' in eth_data['ipv4']:
                    ipv4 = eth_data['ipv4']['address']
                if 'netmask' in eth_data['ipv4']:
                    netmask = eth_data['ipv4']['netmask']
            request_dic[eth] = {'role': role, 'ipaddress': ipv4, 'netmask': netmask}
        rc, data = set_interfaces_roles(admin_ip, request_dic)
        if not rc:
            print("Something wrong happen while restoring your net configuration %s" % data)

    def check_request_and_response(self, dic_request, dic_response):
        """Check whether a request is the same as a response
        Response:
        {
          u'lo': {
            'promisc': False,
            'role': 'disabled',
            'ipv4': {
              u'netmask': u'255.0.0.0',
              u'network': u'127.0.0.0',
              u'address': u'127.0.0.1'
            }
          },
          u'eth5': {
            'promisc': False,
            'role': 'disabled'
          },
          u'eth4': {
            'promisc': False,
            'role': 'disabled'
          },
          u'eth3': {
            'promisc': False,
            'role': 'disabled'
          },
          u'eth2': {
            'promisc': False,
            'role': 'disabled'
          },
          u'eth1': {
            'promisc': False,
            'role': 'disabled'
          },
          u'eth0': {
            'promisc': True,
            'role': 'admin',
            'ipv4': {
              u'netmask': u'255.255.255.0',
              u'network': u'172.17.2.0',
              u'address': u'172.17.2.6'
            }
          }
        }
        Request:

        roles = {'eth1':{'role':'log_management', 'ipaddress':NET_IPS['eth1'],'netmask':'255.255.255.0'},
                 'eth2':{'role':'log_management', 'ipaddress':NET_IPS['eth2'],'netmask':'255.255.255.0'},
                 'eth3':{'role':'monitoring', 'ipaddress':NET_IPS['eth3'],'netmask':'255.255.255.0'},
                 'eth4':{'role':'monitoring', 'ipaddress':NET_IPS['eth4'],'netmask':'255.255.255.0'},
                 'eth5':{'role':'monitoring', 'ipaddress':NET_IPS['eth5'],'netmask':'255.255.255.0'}}
        """
        print("=" * 100)
        for eth, eth_data in dic_request.iteritems():
            if eth not in dic_response:
                return False
            if eth_data['role'] != dic_response[eth]['role']:
                return False
            if eth_data['role'] == 'log_management':
                if not 'ipv4' in dic_response[eth]:
                    return False
                if not 'address' in dic_response[eth]['ipv4']:
                    return False
                if not 'netmask' in dic_response[eth]['ipv4']:
                    return False
                if eth_data['ipaddress'] != dic_response[eth]['ipv4']['address']:
                    return False
                if eth_data['netmask'] != dic_response[eth]['ipv4']['netmask']:
                    return False
        return True

    def test_0001(self):
        """Test 0001: empty params"""
        print('TestNetworkSetInterfaces:: test_empty_input_params()')
        # Empty args
        system_ip = ""
        roles = ""
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertFalse(rc)

    def test_0002(self):
        """Test 0002: empty role """
        print('TestNetworkSetInterfaces:: test_empty_roles_param()')
        # Roles empty string
        system_ip = admin_ip
        roles = ""
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertFalse(rc)

        # Empty roles
        roles = {}
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertFalse(rc)

    def test_0003(self):
        """Test 0003: Set Admin interface"""
        print('TestNetworkSetInterfaces:: test_set_admin_iface()')
        system_ip = admin_ip
        # Try to set the admin interface (It's not allowed)
        roles = {'eth0': {'role': 'admin', 'ipaddress': '172.17.2.6', 'netmask': '255.255.255.0'}}
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertFalse(rc)

    def test_0004(self):
        """Test 0004: set more than one admin iface"""
        print('TestNetworkSetInterfaces:: test_more_than_one_admin_iface()')
        system_ip = admin_ip
        # Try to set more than one admin interface
        roles = {'eth1': {'role': 'admin', 'ipaddress': '172.17.2.6', 'netmask': '255.255.255.0'}}
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertFalse(rc)

    def test_0005(self):
        """Test 0005: what happens when a  non existing interface is given to the method"""
        print('TestNetworkSetInterfaces:: test_non_existing_iface()')
        system_ip = admin_ip
        roles = {'ethxx1111': {'role': 'monitoring'}}
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertFalse(rc)

    def test_0006(self):
        """Test 0006: Invalid Role"""
        print('TestNetworkSetInterfaces:: test_invalid_role()')
        system_ip = admin_ip
        roles = {'eth1': {'role': 'invalid_role'}}
        rc, data = set_interfaces_roles(system_ip, roles)
        print(data)
        self.assertFalse(rc)

    def test_0007(self):
        """Test 0007: admin interface"""
        print('TestNetworkSetInterfaces:: test_disable_admin_interface()')
        system_ip = admin_ip
        roles = {'eth0': {'role': 'disabled', 'ipaddress': '192.168.2.2'}}
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertFalse(rc)

    def test_0008(self):
        """Test 0008: disable eth1"""
        print('TestNetworkSetInterfaces:: test_disable_interface()')
        system_ip = admin_ip
        roles = {'eth1': {'role': 'disabled', 'ipaddress': '192.168.2.2'}}
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertTrue(rc)

    def test_0009(self):
        """Test 0009: disable all nics except the management one"""
        print('TestNetworkSetInterfaces:: test_disable_all_nics()')
        system_ip = admin_ip
        roles = {
            'eth1': {'role': 'disabled'},
            'eth2': {'role': 'disabled'},
            'eth3': {'role': 'disabled'},
            'eth4': {'role': 'disabled'},
            'eth5': {'role': 'disabled'},
        }
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertTrue(rc)

    def test_0010(self):
        """Test 0010: set log management"""
        print('TestNetworkSetInterfaces:: test_set_log_management_without_ip_and_netmask()')
        system_ip = admin_ip
        roles = {'eth1': {'role': 'log_management', 'ipaddress': None}}
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertTrue(rc)
        self.assertTrue('eth1' in data)
        eth_rc, msg = data['eth1']
        self.assertFalse(eth_rc)

        roles = {'eth1': {'role': 'log_management', 'ipaddress': "172.17.2.9", "netmaks": None}}
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertTrue(rc)
        self.assertTrue('eth1' in data)
        eth_rc, msg = data['eth1']
        self.assertFalse(eth_rc)

    def test_0011(self):
        """Test 0011: more than one interface with the same """
        print('TestNetworkSetInterfaces:: test_more_than_one_interface_with_the_same_ip()')
        system_ip = admin_ip
        roles = {'eth1': {'role': 'log_management', 'ipaddress': admin_ip, 'netmask': '255.255.255.0'},
                 'eth2': {'role': 'log_management', 'ipaddress': admin_ip, 'netmask': '255.255.255.0'}}
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertFalse(rc)

    def test_0012(self):
        """Test 0012: valid configuration """
        print('TestNetworkSetInterfaces:: test_valid_config()')
        system_ip = admin_ip
        roles = {'eth1': {'role': 'log_management', 'ipaddress': NET_IPS['eth1'], 'netmask': '255.255.255.0'},
                 'eth2': {'role': 'log_management', 'ipaddress': NET_IPS['eth2'], 'netmask': '255.255.255.0'},
                 'eth3': {'role': 'monitoring', 'ipaddress': NET_IPS['eth3'], 'netmask': '255.255.255.0'},
                 'eth4': {'role': 'monitoring', 'ipaddress': NET_IPS['eth4'], 'netmask': '255.255.255.0'},
                 'eth5': {'role': 'monitoring', 'ipaddress': NET_IPS['eth5'], 'netmask': '255.255.255.0'}}
        rc, data = set_interfaces_roles(system_ip, roles)
        self.assertTrue(rc)
        rc, data = get_iface_list(admin_ip)
        self.assertTrue(rc)
        self.assertTrue(self.check_request_and_response(roles, data))
