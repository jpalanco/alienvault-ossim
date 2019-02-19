# python
# -*- coding: utf-8 -*-
"""
    Tests for apimethods.system
"""
from __future__ import print_function
import unittest
from mock import patch, call, DEFAULT, mock_open
import uuid
from apimethods.system.status import system_all_info
# from apimethods.system.status import network_status
from apimethods.system.status import alienvault_status


class TestSystemAllInfo(unittest.TestCase):
    """Test the apimethods.system.status.system_all_info
    """
    def setUp(self):
        self.system_ip = "192.168.1.1"
        self.system_id = str(uuid.uuid1())

    @patch('apimethods.system.status.ans_system_all_info')
    @patch('apimethods.system.status.get_system_ip_from_system_id')
    def test0001(self, mock_get_sysid, mock_ans_all_info):
        """Test a correct execution of system_all_info
        """
        mock_get_sysid.return_value = (True, self.system_ip)
        mock_ans_all_info.return_value = (True, "{'called':'ok'}")
        res = system_all_info(self.system_id, no_cache=False)  # We need to bypass the cache.
        self.assertTrue(res[0] is True)
        mock_ans_all_info.assert_called_once_with(self.system_ip)
        mock_get_sysid.assert_called_once_with(self.system_id)

    @patch('apimethods.system.status.ans_system_all_info') 
    @patch('apimethods.system.status.get_system_ip_from_system_id')
    def test0002(self, mock_get_sysid, mock_ans_all_info):
        """Fails at get_system_ip_from_id
        """
        mock_get_sysid.return_value = (False, "ERROR get_system_ip_from_system_id")
        mock_ans_all_info.return_value = (True, "{'called':'ok'}")
        res = system_all_info(self.system_id, no_cache=False)  # We need to bypass the cache.
        self.assertTrue(res[0] is False)
        mock_get_sysid.assert_called_once_with(self.system_id)
        mock_ans_all_info.assert_not_called()
       
    @patch('apimethods.system.status.ans_system_all_info') 
    @patch('apimethods.system.status.get_system_ip_from_system_id')
    def test0003(self, mock_get_sysid, mock_ans_all_info):
        """Fails at ans_system_all_info
        """
        mock_get_sysid.return_value = (True, self.system_ip)
        mock_ans_all_info.return_value = (False, "ERROR ans_system_all_info")
        res = system_all_info(self.system_id, no_cache=False)  # We need to bypass the cache.
        self.assertTrue(res[0] is False)
        mock_get_sysid.assert_called_once_with(self.system_id)
        self.assertTrue(mock_ans_all_info.called)


class TestNetworkStatus(unittest.TestCase):
    """Test the apimethods.system.status.network_status
    """
    
    def setUp(self):
        pass
    
    def tearDown(self):
        pass


class TestAlienvaultStatus(unittest.TestCase):
    """Test the apimethods.system.status.alienvault_status
    """
    
    def setUp(self):
        self.system_ip = "192.168.1.1"
        self.system_id = str(uuid.uuid1())

    @patch('apimethods.system.status.ans_alienvault_status')
    @patch('apimethods.system.status.get_system_ip_from_system_id')
    def test0001(self, mock_get_sysid, mock_alienvault_status):
        """Test a correct execution of system_all_info
        """
        mock_get_sysid.return_value = (True, self.system_ip)
        mock_alienvault_status.return_value = (True, "{'called':'ok'}")
        res = alienvault_status(self.system_id, no_cache=False)  # We need to bypass the cache.
        self.assertTrue(res[0] is True)
        mock_alienvault_status.assert_called_once_with(self.system_ip)
        mock_get_sysid.assert_called_once_with(self.system_id)

    @patch('apimethods.system.status.ans_alienvault_status') 
    @patch('apimethods.system.status.get_system_ip_from_system_id')
    def test0002(self, mock_get_sysid, mock_alienvault_status):
        """Fails at get_system_ip_from_id
        """
        mock_get_sysid.return_value = (False, "ERROR get_system_ip_from_system_id")
        mock_alienvault_status.return_value = (True, "{'called':'ok'}")
        res = alienvault_status(self.system_id, no_cache=False)  # We need to bypass the cache.
        self.assertTrue(res[0] is False)
        mock_get_sysid.assert_called_once_with(self.system_id)
        mock_alienvault_status.assert_not_called()
       
    @patch('apimethods.system.status.ans_alienvault_status') 
    @patch('apimethods.system.status.get_system_ip_from_system_id')
    def test0003(self, mock_get_sysid, mock_alienvault_status):
        """Fails at ans_system_all_info
        """
        mock_get_sysid.return_value = (True, self.system_ip)
        mock_alienvault_status.return_value = (False, "ERROR ans_system_all_info")
        res = alienvault_status(self.system_id, no_cache=False)  # We need to bypass the cache.
        self.assertTrue(res[0] is False)
        mock_get_sysid.assert_called_once_with(self.system_id)
        self.assertTrue(mock_alienvault_status.called)

