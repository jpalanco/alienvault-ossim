"""
    Test for backup_task
"""
from mock import patch
import unittest
import time
import datetime
import os.path
import random

from celerymethods.tasks.backup_tasks import rotate_backups


class TestBackupTask(unittest.TestCase):
    """
        Unit test for backup_tasks
    """
    def setUp(self):
        """
            Setup Function
        """
        pass

    def tearDown(self):
        """
            tearDown function
        """
        pass

    def _genBackupList(self, number=100):
        """
            Generate a backup list from now till :number: days.
        """
        backup_list = []
        tnow = time.time()
        for x in range(0, number):
            d = datetime.datetime.fromtimestamp(tnow)
            bname = d.strftime("configuration_backup_%Y_%m_%d-%H-%M-%S.tar.gz")
            backup_list.append({'file': os.path.join("/var/alienvault/backup/", bname),
                                'name': bname,
                                'timestamp': int(tnow)})
            # Random days between 0 to 7
            # tnow = tnow - random.randint(0, 24 * 60 * 60 * 7)
            tnow = tnow - 24 * 60 * 60
        random.shuffle(backup_list)
        return backup_list

    @patch('celerymethods.tasks.backup_tasks.remove_file')
    @patch('celerymethods.tasks.backup_tasks.get_system_ip_from_system_id')
    @patch('celerymethods.tasks.backup_tasks.get_backup_list')
    def test_0001(self, mock_get_backup_list, mock_get_system_ip, mock_remove_file):
        """
            Test with patter 100 backups
        """
        data = self._genBackupList(100)
        named = [x['file'] for x in sorted(data, key=lambda x: x['timestamp'])]
        mock_get_backup_list.return_value = (True, data)
        mock_get_system_ip.return_value = (True, "192.168.1.1")
        mock_remove_file.return_value = (True, '')
        success, result = rotate_backups('local', 10)
        self.assertEqual(success, True)
        self.assertEqual(result, "Remove 90 backups")
        self.assertEqual(mock_remove_file.called, True)
        # Per algoritm backups mas be present backups
        # [18, 30, 44, 59, 70, 84, 92, 96, 98, 99].
        self.assertEqual(len(mock_remove_file.call_args_list), 1)
        firstcall = mock_remove_file.call_args_list[0]
        self.assertEqual(firstcall[0][0], ['192.168.1.1'])
        deletedone = firstcall[0][1].split(" ")
        self.assertEqual(len(deletedone), 90)
        test_vector = [18, 30, 44, 59, 70, 84, 92, 96, 98, 99]
        for fname in deletedone:
            index = named.index(fname)
            self.assertTrue(index not in test_vector)

    @patch('celerymethods.tasks.backup_tasks.remove_file')
    @patch('celerymethods.tasks.backup_tasks.get_system_ip_from_system_id')
    @patch('celerymethods.tasks.backup_tasks.get_backup_list')
    def test_0002(self, mock_get_backup_list, mock_get_system_ip, mock_remove_file):
        """
            10 backups must no fired part of the code
        """
        data = self._genBackupList(10)
        # ordered = sorted(data, key=lambda x: x['timestamp'])
        mock_get_backup_list.return_value = (True, data)
        mock_get_system_ip.return_value = (True, "192.168.1.1")
        mock_remove_file.return_value = (True, '')
        success, _ = rotate_backups('local', 10)
        self.assertEqual(success, True)
        self.assertEqual(mock_remove_file.called, False)
