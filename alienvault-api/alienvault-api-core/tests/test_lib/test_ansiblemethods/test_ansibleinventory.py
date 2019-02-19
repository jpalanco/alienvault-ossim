import os
import unittest
from shutil import copyfile
from tempfile import mktemp

from ansiblemethods.ansibleinventory import (AnsibleInventoryManager, AnsibleInventoryManagerFileNotFound)

TEST_FILES_PATH = os.path.abspath(os.path.join(__file__, os.pardir)) + "/data/"


# hosts1: Invalid file --
# hosts2: Ungrouped hosts
# hosts3: grouped hosts


class TestAnsibleInventory(unittest.TestCase):
    """
        Tests for AnsibleInventoryManager class
    """

    def test_load_nonexisting_file(self):
        """Test load of an invalid file"""
        self.assertRaises(AnsibleInventoryManagerFileNotFound,
                          AnsibleInventoryManager, inventory_file='/tmp/non_existent_file')

    def _get_inventory_from_test_file(self, file_name):
        return AnsibleInventoryManager(inventory_file=TEST_FILES_PATH + file_name)

    def test_load_invalid_file(self):
        """Test load of an invalid file"""
        ansible_file = self._get_inventory_from_test_file("hosts1")
        host_list = ansible_file.get_hosts()
        self.assertEqual(len(host_list), 1)
        self.assertEqual(host_list[0], "<novalidhostinventoryfile>")

    def test_get_hosts(self):
        """ Test to get ungrouped hosts """
        ansible_file = self._get_inventory_from_test_file("hosts2")
        expected_host_list = ["ungroupedhost1", "ungroupedhost2", "ungroupedhost3", "ungroupedhost4", "ungroupedhost5",
                              "ungroupedhost6"]
        host_list = ansible_file.get_hosts()
        self.assertEqual(len(host_list), len(expected_host_list))
        self.assertEqual(sorted(expected_host_list), sorted(host_list))

    def test_get_hosts_2(self):
        """ Test to get correct hosts from host3. """
        ansible_file = self._get_inventory_from_test_file("hosts3")
        expected_host_list = ["host1", "host2", "host3", "host4", "host5", "host6"]
        host_list = ansible_file.get_hosts()
        self.assertEqual(len(host_list), len(expected_host_list))
        self.assertEqual(sorted(expected_host_list), sorted(host_list))

    def test_get_groups(self):
        """ Test to successfully get groups from inventory file """
        ansible_file = self._get_inventory_from_test_file("hosts3")
        expected_group_list = ["group1", "group2", "ungrouped", "all"]
        group_list = [group.name for group in ansible_file.get_groups()]
        self.assertEqual(len(expected_group_list), len(group_list))
        self.assertEqual(sorted(expected_group_list), sorted(group_list))

    def test_get_groups_2(self):
        """ Test to successfully get groups from inventory file """
        ansible_file = self._get_inventory_from_test_file("hosts2")
        expected_group_list = ["ungrouped", "all"]
        group_list = [group.name for group in ansible_file.get_groups()]
        self.assertEqual(len(expected_group_list), len(group_list))
        self.assertEqual(sorted(expected_group_list), sorted(group_list))

    def test_get_groups_for_host(self):
        """ Test to successfully get group for a host from inventory file """
        ansible_file = self._get_inventory_from_test_file("hosts3")
        expected_grous_for_host2 = ["ungrouped", "all", "group1", "group2"]
        group_list = [group.name for group in ansible_file.get_groups_for_host("host2")]
        self.assertEqual(len(expected_grous_for_host2), len(group_list))
        self.assertEqual(sorted(expected_grous_for_host2), sorted(group_list))

        expected_grous_for_host2 = ["ungrouped", "all"]
        group_list = [group.name for group in ansible_file.get_groups_for_host("host3")]
        self.assertEqual(len(expected_grous_for_host2), len(group_list))
        self.assertEqual(sorted(expected_grous_for_host2), sorted(group_list))

    def test_delete_host(self):
        """ Test to successfully delete host from inventory file """
        ansible_file = self._get_inventory_from_test_file("hosts3")
        expected_list = [("host%s" % i) for i in xrange(1, 7)]
        given_list = ansible_file.get_hosts()
        self.assertEqual(len(expected_list), len(given_list))
        self.assertEqual(sorted(expected_list), sorted(given_list))

        ansible_file.delete_host("host1")
        expected_list = [("host%s" % i) for i in xrange(2, 7)]
        given_list = ansible_file.get_hosts()
        self.assertEqual(len(expected_list), len(given_list))
        self.assertEqual(sorted(expected_list), sorted(given_list))
        self.assertEqual(ansible_file.is_dirty(), True)

    def test_delete_host_from_group(self):
        """ Test to successfully delete host from group in inventory file """
        ansible_file = self._get_inventory_from_test_file("hosts3")
        expected_list = ["host1", "host2", "host4"]
        group = ansible_file.get_group("group1")
        given_list = [host.name for host in group.get_hosts()]

        self.assertEqual(len(expected_list), len(given_list))
        self.assertEqual(sorted(expected_list), sorted(given_list))

        ansible_file.delete_host("host1", group="group1")
        expected_list = ["host2", "host4"]

        group = ansible_file.get_group("group1")
        given_list = [host.name for host in group.get_hosts()]
        self.assertEqual(len(expected_list), len(given_list))
        self.assertEqual(sorted(expected_list), sorted(given_list))

    def test_get_group(self):
        """ Test to successfully get single group from inventory file """
        ansible_file = self._get_inventory_from_test_file("hosts3")
        group = ansible_file.get_group("group1")
        self.assertNotEqual(group, None)

        group = ansible_file.get_group("groupXXX")
        self.assertEqual(group, None)

    def test_add_host(self):
        """ Test to successfully add host to inventory file """
        ansible_file = self._get_inventory_from_test_file("hosts3")
        expected_list = [("host%s" % i) for i in xrange(1, 7)]
        given_list = ansible_file.get_hosts()
        self.assertEqual(len(expected_list), len(given_list))
        self.assertEqual(sorted(expected_list), sorted(given_list))

        ansible_file.add_host("host7")
        expected_list = [("host%s" % i) for i in xrange(1, 8)]
        given_list = ansible_file.get_hosts()
        self.assertEqual(len(expected_list), len(given_list))
        self.assertEqual(sorted(expected_list), sorted(given_list))
        self.assertEqual(ansible_file.is_dirty(), True)

        ansible_file.add_host(host_ip="host8", group_list=["group1"])
        expected_list = ["host1", "host2", "host4", "host8"]

        group = ansible_file.get_group("group1")
        given_list = [host.name for host in group.get_hosts()]

        self.assertEqual(len(expected_list), len(given_list))
        self.assertEqual(sorted(expected_list), sorted(given_list))
        self.assertEqual(ansible_file.is_dirty(), True)

    def test_save_inventory(self):
        """ Test save process """
        # Copy real data into temp_file
        temp_inventory_file = mktemp(prefix='test_inventory')
        temp_bkp_inventory_file = mktemp(prefix='test_bkp_inventory')
        try:
            copyfile(TEST_FILES_PATH + "hosts3", temp_inventory_file)
            ansible_file = AnsibleInventoryManager(inventory_file=temp_inventory_file)
            ansible_file.save_inventory(backup_file=temp_bkp_inventory_file)
            bk_ansible_file = AnsibleInventoryManager(inventory_file=temp_bkp_inventory_file)

            self.assertEqual(ansible_file.get_hosts(), bk_ansible_file.get_hosts())
            self.assertEqual([group.name for group in ansible_file.get_groups()],
                             [group.name for group in bk_ansible_file.get_groups()])
            ansible_file.add_host("host7")

            ansible_file.save_inventory(backup_file=temp_bkp_inventory_file)
            bk_ansible_file = AnsibleInventoryManager(inventory_file=temp_bkp_inventory_file)
            self.assertNotEqual(ansible_file.get_hosts(), bk_ansible_file.get_hosts())
            self.assertEqual([group.name for group in ansible_file.get_groups()],
                             [group.name for group in bk_ansible_file.get_groups()])
        finally:
            os.unlink(temp_inventory_file)
            os.unlink(temp_bkp_inventory_file)
