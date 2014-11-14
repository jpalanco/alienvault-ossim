"""Test the ansiblemethods.system methods.
"""

from __future__ import print_function
from mock import patch
import unittest


from sqlalchemy.orm.exc import NoResultFound
import db
from db.models.alienvault import Config
from ansiblemethods.system.system import ansible_run_async_update,ansible_run_async_reconfig
from ansiblemethods.ansiblemanager import Ansible as AnsibleClass


from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler
from db.methods.system import get_system_id_from_local
CONFIG_FILE = "/etc/ossim/ossim_setup.conf"
ossim_setup = AVOssimSetupConfigHandler(CONFIG_FILE)

class TestAnsibleSystemFunctions(unittest.TestCase):

    def setUp(self):
        pass
    def tearDown(self):
        pass

    @patch.object(AnsibleClass,'run_playbook')
    def test_0001(self, mock_ansible_playbook):

        mock_ansible_playbook.return_value=[]
        success, response = ansible_run_async_update(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)

    @patch.object(AnsibleClass,'run_playbook')
    def test_0002(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value={'172.17.2.101': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        success, response = ansible_run_async_update(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)


    @patch.object(AnsibleClass,'run_playbook')
    def test_0003(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value={'999.999.999.999': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        success, response = ansible_run_async_update(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)


    @patch.object(AnsibleClass,'run_playbook')
    def test_0004(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value={'999.999.999.999': {'unreachable': 0, 'skipped': 0, 'ok': 1, 'changed': 0, 'failures': 0}}
        success, response = ansible_run_async_update(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)


    @patch.object(AnsibleClass,'run_playbook')
    def test_0005(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value={ossim_setup.get_general_admin_ip(): {'unreachable': 0, 'skipped': 0, 'ok': 1, 'changed': 0, 'failures': 0}}
        success, response = ansible_run_async_update(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, True)


    @patch.object(AnsibleClass,'run_playbook')
    def test_0005(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value={ossim_setup.get_general_admin_ip(): {'unreachable': 0, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 1}}
        success, response = ansible_run_async_update(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)


    @patch.object(AnsibleClass,'run_playbook')
    def test_0006(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value=None
        success, response = ansible_run_async_update(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)

    @patch.object(AnsibleClass,'run_playbook')
    def test_0007(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value=None
        success, response = ansible_run_async_update(ossim_setup.get_general_admin_ip(), only_feed=True)
        self.assertEqual(success, False)

    @patch.object(AnsibleClass,'run_playbook')
    def test_0008(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value=None
        success, response = ansible_run_async_update(ossim_setup.get_general_admin_ip(), only_feed="aAAA")
        self.assertEqual(success, False)

    @patch.object(AnsibleClass,'run_playbook')
    def test_0009(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value=None
        success, response = ansible_run_async_update(ossim_setup.get_general_admin_ip(), only_feed=False)
        self.assertEqual(success, False)

    @patch.object(AnsibleClass,'run_playbook')
    def test_0010(self, mock_ansible_playbook):

        mock_ansible_playbook.return_value=[]
        success, response = ansible_run_async_reconfig(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)

    @patch.object(AnsibleClass,'run_playbook')
    def test_0011(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value={'172.17.2.101': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        success, response = ansible_run_async_reconfig(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)


    @patch.object(AnsibleClass,'run_playbook')
    def test_0012(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value={'999.999.999.999': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        success, response = ansible_run_async_reconfig(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)


    @patch.object(AnsibleClass,'run_playbook')
    def test_0013(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value={'999.999.999.999': {'unreachable': 0, 'skipped': 0, 'ok': 1, 'changed': 0, 'failures': 0}}
        success, response = ansible_run_async_reconfig(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)


    @patch.object(AnsibleClass,'run_playbook')
    def test_0014(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value={ossim_setup.get_general_admin_ip(): {'unreachable': 0, 'skipped': 0, 'ok': 1, 'changed': 0, 'failures': 0}}
        success, response = ansible_run_async_reconfig(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, True)


    @patch.object(AnsibleClass,'run_playbook')
    def test_0015(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value={ossim_setup.get_general_admin_ip(): {'unreachable': 0, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 1}}
        success, response = ansible_run_async_reconfig(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)


    @patch.object(AnsibleClass,'run_playbook')
    def test_0016(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value=None
        success, response = ansible_run_async_reconfig(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)

    @patch.object(AnsibleClass,'run_playbook')
    def test_0017(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value=None
        success, response = ansible_run_async_reconfig(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)

    @patch.object(AnsibleClass,'run_playbook')
    def test_0018(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value=None
        success, response = ansible_run_async_reconfig(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)

    @patch.object(AnsibleClass,'run_playbook')
    def test_0019(self, mock_ansible_playbook):
        # {'172.17.2.101': {'unreachable': 0, 'skipped': 4, 'ok': 3, 'changed': 0, 'failures': 0}} 
        # {'172.17.2.102': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}
        mock_ansible_playbook.return_value=None
        success, response = ansible_run_async_reconfig(ossim_setup.get_general_admin_ip())
        self.assertEqual(success, False)
