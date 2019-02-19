import mock
import json
import uuid
import unittest

from api.lib.monitors.monitor import MonitorTypes
from api.lib.monitors.system import MonitorFeedAutoUpdates


# Test constants to use in mock.patch
SYSTEM_ID = str(uuid.uuid4())


class TestAutoUpdatesMonitor(unittest.TestCase):
    def setUp(self):
        self.upd_monitor = MonitorFeedAutoUpdates()
        self.system_id = SYSTEM_ID
        self.remote_id_1 = str(uuid.uuid4())
        self.remote_id_2 = str(uuid.uuid4())
        self.local_system_ip = '127.0.0.1'
        self.system_ip = '192.168.1.0'
        self.remote_ip_1 = '192.168.1.1'
        self.remote_ip_2 = '192.168.1.2'
        self.db_monitor_data = {
            'all_updated': False,
            'error_on_update': False,
            'number_of_hosts': 1,
            'update_results': {}
        }

    def get_raw_db_monitor_data(self):
        return [{'data': json.dumps(self.db_monitor_data)}]

    def test_init(self):
        self.assertEqual(self.upd_monitor.id, MonitorTypes.MONITOR_FEED_AUTO_UPDATES)
        self.assertEqual('Run automatic feed updates', self.upd_monitor.message)

    @mock.patch('api.lib.monitors.system.db_get_monitor_data', return_value=None)
    def test_get_monitor_data_use_defaults(self, db_mock):
        """ Checks that `get_monitor_data` should use defaults data
        """
        result = self.upd_monitor.get_monitor_data()
        self.assertEqual(result, self.upd_monitor.monitor_data)
        db_mock.assert_called_once_with(self.upd_monitor.id)

    @mock.patch('api.lib.monitors.system.db_get_monitor_data')
    def test_get_monitor_data_use_db_data(self, db_mock):
        """ Checks that `get_monitor_data` should fetch the data from the DB.
        """
        self.db_monitor_data['all_updated'] = False
        self.db_monitor_data['number_of_hosts'] = 2
        db_mock.return_value = self.get_raw_db_monitor_data()

        res = self.upd_monitor.get_monitor_data()
        db_mock.assert_called_once_with(self.upd_monitor.id)
        self.assertEqual(res, self.db_monitor_data)

    def test_check_and_reset_old_data_do_reset(self):
        self.upd_monitor.monitor_data['all_updated'] = True
        self.upd_monitor.monitor_data['error_on_update'] = False

        self.upd_monitor.check_and_reset_old_data()
        self.assertEqual(self.upd_monitor.monitor_data, self.upd_monitor.default_data)

    def test_check_and_reset_old_data_skip_reset(self):
        self.upd_monitor.monitor_data['all_updated'] = True
        self.upd_monitor.monitor_data['error_on_update'] = True

        self.upd_monitor.check_and_reset_old_data()
        self.assertNotEqual(self.upd_monitor.monitor_data, self.upd_monitor.default_data)

    @mock.patch('api.lib.monitors.system.get_local_time', return_value=(True, '14'))
    def test_system_could_be_updated_by_schedule_false(self, time_mock):
        test_time = '01'

        result = self.upd_monitor.system_could_be_updated_by_schedule(self.system_ip, test_time)
        self.assertEqual(False, result)
        time_mock.assert_called_once_with(self.system_ip, date_fmt='%H')

    @mock.patch('api.lib.monitors.system.get_local_time', return_value=(True, '14'))
    def test_system_could_be_updated_by_schedule_true(self, time_mock):
        test_time = '14'

        result = self.upd_monitor.system_could_be_updated_by_schedule(self.system_ip, test_time)
        self.assertEqual(True, result)
        time_mock.assert_called_once_with(self.system_ip, date_fmt='%H')

    @mock.patch('api.lib.monitors.system.apimethod_get_remote_software_update', return_value=(True, {}))
    def test_has_pending_updates_gets_nothing(self, get_updates_mock):
        """ Checks that `has_pending_feed_updates` returns None if contacted but failed to get the data.
        """
        result = self.upd_monitor.has_pending_feed_updates(self.system_id)
        get_updates_mock.assert_called_once_with(self.system_id, no_cache=True)
        self.assertEqual(None, result)

    @mock.patch('api.lib.monitors.system.apimethod_get_remote_software_update', return_value=(False, {}))
    def test_has_pending_updates_failed_to_contact(self, get_updates_mock):
        """ Checks that `has_pending_feed_updates` returns None if failed to contact the remote host.
        """
        result = self.upd_monitor.has_pending_feed_updates(self.system_id)
        get_updates_mock.assert_called_once_with(self.system_id, no_cache=True)
        self.assertEqual(False, result)

    @mock.patch('api.lib.monitors.system.apimethod_get_remote_software_update')
    def test_has_pending_updates_successful(self, get_updates_mock):
        """ Checks that `has_pending_feed_updates` returns True.
        """
        get_updates_mock.return_value = (True, {self.system_id: {'packages': {'pending_feed_updates': True}}})

        result = self.upd_monitor.has_pending_feed_updates(self.system_id)
        get_updates_mock.assert_called_once_with(self.system_id, no_cache=True)
        self.assertEqual(True, result)

    def test_update_monitors_data_one_update_failed(self):
        update_results = [{'system_ip': self.local_system_ip, 'result': None},
                          {'system_ip': self.remote_ip_1, 'result': 'test'}]
        systems = {self.system_id: self.local_system_ip, self.remote_id_1: self.remote_ip_1}
        expected_monitor_result = {
            self.local_system_ip: {'result': None},
            self.remote_ip_1: {'result': 'test'}
        }

        self.upd_monitor.update_monitors_data_with_results(update_results, systems)

        self.assertEqual(False, self.upd_monitor.monitor_data['all_updated'])
        self.assertEqual(True, self.upd_monitor.monitor_data['error_on_update'])
        self.assertEqual(1, self.upd_monitor.monitor_data['number_of_hosts'])
        self.assertDictContainsSubset(expected_monitor_result[self.local_system_ip],
                                      self.upd_monitor.monitor_data['update_results'][self.local_system_ip])
        self.assertDictContainsSubset(expected_monitor_result[self.remote_ip_1],
                                      self.upd_monitor.monitor_data['update_results'][self.remote_ip_1])

    def test_update_monitors_data_all_ok(self):
        update_results = [{'system_ip': self.local_system_ip, 'result': 'host1 ok'},
                          {'system_ip': self.remote_ip_1, 'result': 'host2 ok'}]
        systems = {self.system_id: self.local_system_ip, self.remote_id_1: self.remote_ip_1}
        expected_monitor_result = {
            self.local_system_ip: {'result': 'host1 ok'},
            self.remote_ip_1: {'result': 'host2 ok'}
        }

        self.upd_monitor.update_monitors_data_with_results(update_results, systems)

        self.assertEqual(True, self.upd_monitor.monitor_data['all_updated'])
        self.assertEqual(False, self.upd_monitor.monitor_data['error_on_update'])
        self.assertEqual(1, self.upd_monitor.monitor_data['number_of_hosts'])
        self.assertDictContainsSubset(expected_monitor_result[self.local_system_ip],
                                      self.upd_monitor.monitor_data['update_results'][self.local_system_ip])
        self.assertDictContainsSubset(expected_monitor_result[self.remote_ip_1],
                                      self.upd_monitor.monitor_data['update_results'][self.remote_ip_1])

    @mock.patch('api.lib.monitors.system.get_feed_auto_update', return_value=(False, None))
    def test_start_auto_updates_not_enabled(self, db_get_feed_updates_mock):
        """ Checks that `start` returns False when auto_updates are disabled.
        """
        result = self.upd_monitor.start()
        db_get_feed_updates_mock.assert_called_once()
        self.assertEqual(False, result)

    @mock.patch('api.lib.monitors.system.get_system_id_from_local', return_value=(False, 'err'))
    @mock.patch('api.lib.monitors.system.get_feed_auto_update', return_value=(True, 1))
    def test_start_auto_updates_failed_to_get_local_id(self, db_feed_mock, sys_id_mock):
        """ Checks that `start` returns False when failed to get local_id.
        """
        result = self.upd_monitor.start()
        db_feed_mock.assert_called_once()
        sys_id_mock.assert_called_once()
        self.assertEqual(False, result)

    @mock.patch('api.lib.monitors.system.group')
    @mock.patch('api.lib.monitors.system.alienvault_asynchronous_update')
    @mock.patch('api.lib.monitors.system.get_local_time', return_value=(True, '00'))
    @mock.patch('api.lib.monitors.system.get_system_id_from_local', return_value=(True, SYSTEM_ID))
    @mock.patch('api.lib.monitors.system.get_feed_auto_update', return_value=(True, 0))
    def test_start_single_machine_no_need_for_update(self, db_feed_mock, sys_id_mock, time_mock, upd_mock, group_mock):
        """ Checks that `start` one available machine - local server doesn't need to be updated.
        """
        # Mocks
        self.upd_monitor.save_data = mock.MagicMock()
        self.upd_monitor.remove_monitor_data = mock.MagicMock()
        self.upd_monitor.get_monitor_data = mock.MagicMock()
        self.upd_monitor.update_monitors_data_with_results = mock.MagicMock()
        self.upd_monitor.has_pending_feed_updates = mock.MagicMock(return_value=False)
        self.upd_monitor.get_connected_systems_to_update = mock.MagicMock(return_value={})

        # Run
        result = self.upd_monitor.start()

        # Verify
        self.assertEqual(True, result)
        db_feed_mock.assert_called_once()
        sys_id_mock.assert_called_once()
        time_mock.assert_not_called()
        upd_mock.s.assert_not_called()
        group_mock.return_value.join.assert_not_called()
        self.upd_monitor.get_connected_systems_to_update.assert_not_called()
        self.upd_monitor.update_monitors_data_with_results.assert_not_called()
        self.upd_monitor.save_data.assert_called()
        self.upd_monitor.remove_monitor_data.assert_called()

    @mock.patch('api.lib.monitors.system.group')
    @mock.patch('api.lib.monitors.system.alienvault_asynchronous_update')
    @mock.patch('api.lib.monitors.system.get_local_time', return_value=(True, '04'))
    @mock.patch('api.lib.monitors.system.get_systems', return_value=(True, {}))
    @mock.patch('api.lib.monitors.system.get_system_id_from_local', return_value=(True, SYSTEM_ID))
    @mock.patch('api.lib.monitors.system.get_feed_auto_update', return_value=(True, 3))
    def test_start_auto_updates_single_machine_not_now(self, db_feed_mock, sys_id_mock, systems_mock,
                                                       time_mock, upd_mock, group_mock):
        """ Checks that `start` tries to update one available machine - but actual time != scheduled time.
        """
        # Mocks

        self.upd_monitor.save_data = mock.MagicMock()
        self.upd_monitor.remove_monitor_data = mock.MagicMock()
        self.upd_monitor.update_monitors_data_with_results = mock.MagicMock()
        self.upd_monitor.has_pending_feed_updates = mock.MagicMock(return_value=True)

        # Run
        result = self.upd_monitor.start()

        # Verify
        self.assertEqual(True, result)
        db_feed_mock.assert_called_once()
        sys_id_mock.assert_called_once()
        systems_mock.assert_not_called()
        time_mock.assert_called_once()
        upd_mock.delay.return_value.wait.assert_not_called()
        upd_mock.s.assert_not_called()
        group_mock.return_value.join.assert_not_called()
        self.upd_monitor.remove_monitor_data.assert_called()
        self.upd_monitor.save_data.assert_called()

    @mock.patch('api.lib.monitors.system.group')
    @mock.patch('api.lib.monitors.system.alienvault_asynchronous_update')
    @mock.patch('api.lib.monitors.system.get_local_time', return_value=(True, '14'))
    @mock.patch('api.lib.monitors.system.get_systems')
    @mock.patch('api.lib.monitors.system.get_system_id_from_local', return_value=(True, SYSTEM_ID))
    @mock.patch('api.lib.monitors.system.get_feed_auto_update', return_value=(True, 14))
    def test_start_auto_updates_all_three_should_be_updated(self, db_feed_mock, sys_id_mock, systems_mock,
                                                            time_mock, upd_mock, group_mock):
        """ Checks that `start` tries to update three available machines with the same time.
        """
        # Mocks
        systems_mock.return_value = True, {self.remote_id_1: self.remote_ip_1,
                                           self.remote_id_2: self.remote_ip_2}
        upd_mock.delay.return_value.wait.return_value = {'system_ip': self.local_system_ip,
                                                         'system_id': self.system_id,
                                                         'result': True}

        self.upd_monitor.save_data = mock.MagicMock()
        self.upd_monitor.remove_monitor_data = mock.MagicMock()
        self.upd_monitor.has_pending_feed_updates = mock.MagicMock(return_value=True)

        # Run
        result = self.upd_monitor.start()

        # Verify
        self.assertEqual(True, result)
        db_feed_mock.assert_called_once()
        sys_id_mock.assert_called_once()
        systems_mock.assert_called_once()
        time_mock.assert_called()

        self.assertEqual(mock.call(self.local_system_ip, date_fmt='%H'), time_mock.mock_calls[0])  # server first
        time_mock.assert_has_calls(
            (mock.call(self.remote_ip_1, date_fmt='%H'),
             mock.call(self.remote_ip_2, date_fmt='%H')),
            any_order=True)
        self.upd_monitor.has_pending_feed_updates.assert_called()
        self.assertEqual(mock.call(self.system_id), self.upd_monitor.has_pending_feed_updates.mock_calls[0])
        self.upd_monitor.has_pending_feed_updates.assert_has_calls(
            (mock.call(self.remote_id_1),
             mock.call(self.remote_id_2)),
            any_order=True)

        upd_mock.delay.has_calls(mock.call(self.local_system_ip, only_feed=True))  # server first
        upd_mock.s.has_calls(
            (mock.call(self.remote_ip_1, only_feed=True),
             mock.call(self.remote_ip_2, only_feed=True)),
            any_order=True
        )
        upd_mock.s.return_value.set.has_calls((mock.call(countdown=2), mock.call(countdown=4)))
        group_mock.return_value.return_value.join.assert_called()
        self.upd_monitor.save_data.assert_called()

    @mock.patch('api.lib.monitors.system.group')
    @mock.patch('api.lib.monitors.system.alienvault_asynchronous_update')
    @mock.patch('api.lib.monitors.system.get_local_time')
    @mock.patch('api.lib.monitors.system.get_systems')
    @mock.patch('api.lib.monitors.system.get_system_id_from_local', return_value=(True, SYSTEM_ID))
    @mock.patch('api.lib.monitors.system.get_feed_auto_update', return_value=(True, 14))
    def test_start_auto_updates_two_from_three_updated(self, db_feed_mock, sys_id_mock, systems_mock,
                                                       time_mock, upd_mock, group_mock):
        """ Checks that `start` tries to update two machine from three.
        """

        data = {self.local_system_ip: '14', self.remote_ip_1: '12', self.remote_ip_2: '14'}

        def get_time_side_effect(*args, **kwargs):
            return True, data.get(args[0])

        # Mocks
        time_mock.side_effect = get_time_side_effect
        systems_mock.return_value = True, {self.remote_id_1: self.remote_ip_1,
                                           self.remote_id_2: self.remote_ip_2}
        upd_mock.delay.return_value.wait.return_value = {'system_ip': self.local_system_ip,
                                                         'system_id': self.system_id,
                                                         'result': True}

        upd_mock.s.return_value.join.return_value = [
            {'system_ip': self.remote_ip_2,
             'system_id': self.remote_id_2,
             'result': True}
        ]
        self.upd_monitor.save_data = mock.MagicMock()
        self.upd_monitor.remove_monitor_data = mock.MagicMock()
        self.upd_monitor.has_pending_feed_updates = mock.MagicMock(return_value=True)

        # Run
        result = self.upd_monitor.start()

        # Verify
        self.assertEqual(True, result)
        db_feed_mock.assert_called_once()
        sys_id_mock.assert_called_once()
        systems_mock.assert_called_once()
        time_mock.assert_has_calls((mock.call(self.local_system_ip, date_fmt='%H'),
                                    mock.call(self.remote_ip_1, date_fmt='%H'),
                                    mock.call(self.remote_ip_2, date_fmt='%H')),
                                   any_order=True)
        self.upd_monitor.has_pending_feed_updates.assert_has_calls(
            (mock.call(self.system_id),
             mock.call(self.remote_id_1),
             mock.call(self.remote_id_2)),
            any_order=True
        )
        upd_mock.delay.has_calls(mock.call(self.local_system_ip, only_feed=True))
        upd_mock.s.has_calls(mock.call(self.remote_ip_2, only_feed=True))
        group_mock.return_value.return_value.join.assert_called()
        self.upd_monitor.save_data.assert_called()
        self.assertEqual(True, self.upd_monitor.local_server_updated)
        self.assertEqual(False, self.upd_monitor.monitor_data.get('all_updated'))
        self.assertEqual(3, self.upd_monitor.monitor_data.get('number_of_hosts'))
