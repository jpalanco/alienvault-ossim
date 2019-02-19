# python
# -*- coding: utf-8 -*-
"""
    Tests for apimethods.system
"""
from __future__ import print_function

import unittest
import uuid
from copy import copy

from mock import patch, call, mock_open, MagicMock

from apimethods.system.system import (
    get, get_all, get_all_systems_with_ping_info, add_child_server, add_ha_system,
    create_directory_for_ossec_remote, add_system_from_ip, add_system, apimethod_delete_system,
    sync_database_from_child, get_local_info, apimethod_get_pending_packges, asynchronous_reconfigure, system_is_trial,
    check_update_and_reconfig_status, get_jobs_running, asynchronous_update, check_if_process_is_running,
    system_is_professional, apimethod_check_asynchronous_command_return_code, sync_asec_plugins,
    apimethod_get_asynchronous_command_log_file, get_system_tags, apimethod_check_task_status, get_last_log_lines,
    get_license_devices, get_child_alarms, resend_alarms, NoOptionError, make_tunnel_with_vpn
)


class SafePatcher(object):
    """
        Creates a new mock for target and adds cleanup callback for safe exit from mock in case of any errors.
    """
    def __init__(self, test_case, for_module=None):
        self.base_target = for_module.rstrip('.')
        self.test_case_obj = test_case

    def __call__(self, target, **kwargs):
        """ Handles actual mock logic. Basically it's a wrapper over mock.patch.

        Args:
            obj_to_mock: mock target
            **kwargs: arguments suitable for mock.patch

        Returns: Mock object

        """
        # reload_patcher - it's special flag to skip self.base_target and use target provided on patcher call instead
        need_to_reload_patcher = kwargs.pop('reload_patcher', False)
        if self.base_target is not None and not need_to_reload_patcher:
            target = '%s.%s' % (self.base_target, target)

        # http://www.voidspace.org.uk/python/mock/patch.html#patch-methods-start-and-stop
        # https://docs.python.org/3.5/library/unittest.mock-examples.html#applying-the-same-patch-to-every-test-method
        patcher = patch(target, **kwargs)
        self.test_case_obj.addCleanup(patcher.stop)

        return patcher.start()


class SystemTestCase(unittest.TestCase):
    """
        Extended TestCase with handy safe_patcher method and few common mocks
    """
    def setUp(self):
        self.safe_patcher = SafePatcher(for_module='apimethods.system.system', test_case=self)
        self.mock_api_log = self.safe_patcher('api_log')
        self.system_id = str(uuid.uuid1())
        self.system_ip = '192.168.57.46'
        self.root_pass = 'secret_pass'


class TestSystemGetAll(SystemTestCase):
    """
        Unit test for apimethods.system.get_all()
    """
    def setUp(self):
        super(TestSystemGetAll, self).setUp()
        self.host_id_1 = str(uuid.uuid1())
        self.host_id_2 = str(uuid.uuid1())

        self.mock_get_systems_full = self.safe_patcher('get_systems_full')

    def test_0001(self):
        """ Test the fail of get_all """
        self.mock_get_systems_full.return_value = (False, "mock value")
        status, _ = get_all()
        self.assertFalse(status, 'get_all should return status False here')

    def test_0002(self):
        """ Test OK """
        get_system_full_result = [(self.host_id_1, {'admin_ip': '192.168.1.1',
                                                    'profile': 'Server,Sensor',
                                                    'hostname': 'ascodevida'}),
                                  (self.host_id_2, {'admin_ip': '192.168.1.2',
                                                    'profile': 'Server',
                                                    'hostname': 'menzoberrazan'})]
        self.mock_get_systems_full.return_value = (True, get_system_full_result)
        get_all_result = get_all()

        # Check the results
        self.assertTrue(get_all_result[0], 'status True is expected here')
        expected_result = dict(get_system_full_result)

        for key, data in get_all_result[1].items():
            self.assertTrue(key in expected_result.keys(), 'Host_ID is missing in results')
            self.assertTrue(expected_result[key] == data, 'Actual data differs from expected results')

    def test_0003(self):
        """ Missing data """
        self.mock_get_systems_full.return_value = (True, [(self.host_id_1,
                                                           {'admin_ip': '192.168.1.1', 'profile': 'Server,Sensor'})])
        # Should raise KeyError when data are missing.
        self.assertRaises(KeyError, get_all)


class TestGetLocalInfo(SystemTestCase):
    """
       Tests for `get_local_info` function
    """
    def setUp(self):
        super(TestGetLocalInfo, self).setUp()
        self.mock_get_sys_id_local = self.safe_patcher('get_system_id_from_local', return_value=(True, self.system_id))
        self.mock_get_all = self.safe_patcher('get_all', return_value=(True, ''))

    def test0001(self):
        """ get_local_info: Failed to get system ID """
        self.mock_get_sys_id_local.return_value = (False, '')

        self.assertEqual((False, 'Something wrong happened retrieving the local system id'), get_local_info())
        self.mock_get_sys_id_local.assert_called_once_with()
        self.mock_get_all.assert_not_called()

    def test0002(self):
        """ get_local_info: Failed due to get_all which return False """
        self.mock_get_all.return_value = (False, '')

        self.assertEqual((False, 'Something wrong happened retrieving the system info'), get_local_info())
        self.mock_get_sys_id_local.assert_called_once_with()
        self.mock_get_all.assert_called_once_with()

    def test0003(self):
        """ get_local_info: Failed because local ID not found in system data """
        non_local_id = str(uuid.uuid1())
        self.mock_get_all.return_value = (True, {non_local_id: 'non_local_data'})

        self.assertEqual((False, 'Something wrong happened retrieving the local system info'), get_local_info())
        self.mock_get_sys_id_local.assert_called_once_with()
        self.mock_get_all.assert_called_once_with()

    def test0004(self):
        """ get_local_info: Failed because local ID not found in system data """
        self.mock_get_all.return_value = (True, {self.system_id: 'local_data'})

        self.assertEqual((True, 'local_data'), get_local_info())
        self.mock_get_sys_id_local.assert_called_once_with()
        self.mock_get_all.assert_called_once_with()


class TestSystemAllSystemWithPingInfo(SystemTestCase):
    """
        Unit test for apimethods.system.get_all_systems_with_ping_info
    """
    def setUp(self):
        super(TestSystemAllSystemWithPingInfo, self).setUp()
        self.host_id = str(uuid.uuid1())
        self.host_data = {'admin_ip': '192.168.1.1',
                          'profile': 'Server,Sensor',
                          'hostname': 'ascodevida',
                          'vpn_ip': ''}
        self.mock_system_full = self.safe_patcher('get_systems_full')
        self.mock_system_full.return_value = (True, [(self.host_id, self.host_data)])
        self.mock_ping = self.safe_patcher('ping_system')
        self.mock_ping.return_value = True  # "PING OK"

    def test_0001(self):
        """ Reachable OK """
        status, systems_available = get_all_systems_with_ping_info()
        self.assertTrue(status, 'Status True is expected')
        self.assertTrue(systems_available[self.host_id]['reachable'], 'System should be reachable')

    def test_0002(self):
        """ Reachable False """
        self.mock_ping.return_value = False  # "Not reachable"
        status, systems_available = get_all_systems_with_ping_info()
        self.assertTrue(status, 'Status True is expected')
        self.assertFalse(systems_available[self.host_id]['reachable'], 'System should not be reachable')

    def test_0003(self):
        """ Reachable true with VPN """
        self.host_data['vpn_ip'] = '192.168.1.2'
        status, systems_available = get_all_systems_with_ping_info()
        self.assertTrue(status, 'Status True is expected')
        self.assertTrue(systems_available[self.host_id]['reachable'], 'System should be available')
        self.assertTrue(call(self.host_id) == self.mock_ping.call_args_list[0])

    def test_0004(self):
        """ Can't obtain system list """
        self.mock_system_full.return_value = (False, "PUF!")

        status, _ = get_all_systems_with_ping_info()
        self.assertFalse(status, "Should be False because can't get systems info")


class TestAddChildServer(SystemTestCase):
    """
        Tests for add_child_server
    """
    def setUp(self):
        super(TestAddChildServer, self).setUp()
        self.host_id_local = str(uuid.uuid1())
        self.host_id_global = str(uuid.uuid1())
        self.child_ip = '192.168.1.1'
        self.server_ip = '127.0.0.1'
        self.server_port = '40001'
        self.local_info = {'id': self.host_id_local,
                           'name': 'localserver',
                           'ip': self.server_ip,
                           'port': self.server_port,
                           'descr': 'Mock server local'}

        self.mock_get_server_ip_from_id = self.safe_patcher('get_server_ip_from_server_id',
                                                            return_value=(True, self.server_ip))
        self.mock_db_get_server = self.safe_patcher('db_get_server', return_value=(True, self.local_info))
        self.mock_ans_add_server = self.safe_patcher('ans_add_server', return_value=(True, "Mock ans_add_server"))
        self.mock_db_add_child_server = self.safe_patcher('db_add_child_server')
        self.mock_ans_add_server_hierarchy = self.safe_patcher('ans_add_server_hierarchy',
                                                               return_value=(True, "Mock ans_add_server_hierarchy"))

    def test0001(self):
        """ Test the add_child_server positive exit """
        status, _ = add_child_server(self.child_ip, self.host_id_global)
        self.mock_ans_add_server.assert_called_once_with(system_ip=self.child_ip,
                                                         server_id=self.host_id_local,
                                                         server_ip=self.server_ip,
                                                         server_port=self.server_port,
                                                         server_name=self.local_info['name'],
                                                         server_descr=self.local_info['descr'])
        self.mock_ans_add_server_hierarchy.assert_called_once_with(system_ip=self.child_ip,
                                                                   parent_id=self.host_id_local,
                                                                   child_id=self.host_id_global)
        self.assertTrue(status, 'Status should be True')

    def test0002(self):
        """ Test the add_child_server positive exit. Fail db_get_server """
        err_msg = "Failed to get server"
        self.mock_db_get_server.return_value = (False, err_msg)

        status, data = add_child_server(self.child_ip, self.host_id_global)
        self.assertFalse(status, 'Status should be False')
        self.assertTrue(data == err_msg)

    def test0003(self):
        """ Test the add_child_server positive exit. Fail ans_add_server """
        err_msg = "Mock ERROR ans_add_server"
        self.mock_ans_add_server.return_value = (False, err_msg)

        status, data = add_child_server(self.child_ip, self.host_id_global)
        self.assertFalse(status, 'Status should be False')
        self.assertTrue(data == err_msg, 'Got unexpected result while adding a child server')

    def test0004(self):
        """ Test the add_child_server positive exit. Fail ans_add_server_hierarchy """
        err_msg = "Mock ERROR ans_add_server_hierarchy"
        self.mock_ans_add_server_hierarchy.return_value = (False, err_msg)

        status, data = add_child_server(self.child_ip, self.host_id_global)
        self.assertFalse(status)
        self.assertTrue(data == err_msg)


class TestAddHASystem(SystemTestCase):
    """
        Tests for add_ha_system
    """
    def setUp(self):
        super(TestAddHASystem, self).setUp()
        self.remote_system_ip = '192.168.11.11'
        self.profile_str = 'server'
        self.system_info = {'system_id': self.system_id,
                            'hostname': 'UT system',
                            'admin_ip': '192.168.11.12',
                            'vpn_ip': None,
                            'profile': [self.profile_str],
                            'sensor_id': str(uuid.uuid1()),
                            'server_id': str(uuid.uuid1())}

        # Mocks
        self.mock_get_system_id_local = self.safe_patcher('get_system_id_from_local')
        self.mock_get_system_id_local.return_value = (True, self.system_id)
        self.mock_ans_add_system = self.safe_patcher('ansible_add_system', return_value=(True, 'IP added'))
        self.mock_ans_get_system_info = self.safe_patcher('ansible_get_system_info')
        self.mock_ans_get_system_info.return_value = (True, self.system_info)
        self.mock_db_add_system = self.safe_patcher('db_add_system', return_value=(True, ''))

    def test0001(self):
        """ add_ha_system: Failed to get local ID """
        self.mock_get_system_id_local.return_value = (False, '')

        self.assertEqual((False, '[add_ha_system] Something wrong happened retrieving the local system id'),
                         add_ha_system(self.remote_system_ip, self.root_pass))
        self.mock_ans_add_system.assert_not_called()

    def test0002(self):
        """ add_ha_system: Failed to add system via ansible """
        bad_response = 'Mock ERROR ansible_add_system'
        self.mock_ans_add_system.return_value = (False, bad_response)

        self.assertEqual((False, 'Something wrong happened adding the system'),
                         add_ha_system(self.remote_system_ip, self.root_pass))
        self.mock_ans_add_system.assert_called_once_with(local_system_id=self.system_id,
                                                         remote_system_ip=self.remote_system_ip,
                                                         password=self.root_pass)
        self.mock_api_log.error.assert_called_once_with(bad_response)
        self.mock_ans_get_system_info.assert_not_called()

    def test0003(self):
        """ add_ha_system: Failed to get system info """
        bad_response = 'Mock ERROR ansible_get_system_info'
        self.mock_ans_get_system_info.return_value = (False, bad_response)

        self.assertEqual((False, 'Something wrong happened getting the system info'),
                         add_ha_system(self.remote_system_ip, self.root_pass))
        self.mock_ans_add_system.assert_called_once_with(local_system_id=self.system_id,
                                                         remote_system_ip=self.remote_system_ip,
                                                         password=self.root_pass)
        self.mock_api_log.error.assert_called_once_with(bad_response)
        self.mock_ans_get_system_info.assert_called_once_with(self.remote_system_ip)
        self.mock_db_add_system.assert_not_called()

    def test0004(self):
        """ add_ha_system: Failed to add system into the DB """
        bad_response = 'Mock ERROR db_add_system'
        self.mock_db_add_system.return_value = (False, bad_response)

        self.assertEqual((False, 'Something wrong happened inserting the system into the database'),
                         add_ha_system(self.remote_system_ip, self.root_pass))
        self.mock_ans_add_system.assert_called_once_with(local_system_id=self.system_id,
                                                         remote_system_ip=self.remote_system_ip,
                                                         password=self.root_pass)
        self.mock_ans_get_system_info.assert_called_once_with(self.remote_system_ip)

        self.mock_db_add_system.assert_called_once_with(system_id=self.system_info['system_id'],
                                                        name=self.system_info['hostname'],
                                                        admin_ip=self.system_info['admin_ip'],
                                                        vpn_ip=self.system_info['vpn_ip'],
                                                        profile=self.profile_str,
                                                        server_id=self.system_info['server_id'],
                                                        sensor_id=self.system_info['sensor_id'])

        self.mock_api_log.error.assert_called_once_with(bad_response)

    def test0005(self):
        """ add_ha_system: Positive case with add_to_database """
        self.assertEqual((True, 'IP added'), add_ha_system(self.remote_system_ip, self.root_pass))
        self.mock_ans_get_system_info.assert_called_once_with(self.remote_system_ip)
        self.assertTrue(self.mock_db_add_system.called)
        self.mock_api_log.error.assert_not_called()

    def test0006(self):
        """ add_ha_system: Positive case without add_to_database """
        self.assertEqual((True, 'IP added'),
                         add_ha_system(self.remote_system_ip, self.root_pass, add_to_database=False))
        self.mock_ans_get_system_info.assert_called_once_with(self.remote_system_ip)
        self.mock_db_add_system.assert_not_called()
        self.mock_api_log.error.assert_not_called()


class TestAddSystemFromIp(SystemTestCase):
    """
        Tests for add_system_from_ip
    """
    def setUp(self):
        super(TestAddSystemFromIp, self).setUp()
        self.uuid_local = str(uuid.uuid1())
        self.uuid_server_id = str(uuid.uuid1())
        self.uuid_system_id = str(uuid.uuid1())
        self.uuid_sensor_id = str(uuid.uuid1())
        self.remote_host_ip = '192.168.0.1'
        self.vpn_ip = '10.20.30.40'
        self.hostname = 'ascodevida'
        self.system_info = {'profile': ['sensor'],
                            'server_id': self.uuid_server_id,
                            'sensor_id': self.uuid_sensor_id,
                            'system_id': self.uuid_system_id,
                            'hostname': self.hostname,
                            'vpn_ip': self.vpn_ip,
                            'admin_ip': self.remote_host_ip}

        self.mock_get_system_id_from_local = self.safe_patcher('get_system_id_from_local',
                                                               return_value=(True, self.uuid_local))
        self.mock_ansible_add_system = self.safe_patcher('ansible_add_system',
                                                         return_value=(True, "Mock OK ansible_add_system"))
        self.mock_ansible_get_system_info = self.safe_patcher('ansible_get_system_info',
                                                              return_value=(True, self.system_info))
        self.mock_get_sensor_id_from_sensor_ip = self.safe_patcher('get_sensor_id_from_sensor_ip',
                                                                   return_value=(True, self.uuid_sensor_id))

        self.mock_add_child_server = self.safe_patcher('add_child_server',
                                                       return_value=(True, "Mock OK add_child_server"))
        self.mock_db_add_system = self.safe_patcher('db_add_system', return_value=(True, "Mock OK db_add_system"))
        self.mock_create_directory_for_ossec_remote = self.safe_patcher(
            'create_directory_for_ossec_remote', return_value=(True, "Mock OK create_directory_for_ossec_remote"))

        self.mock_api_log = self.safe_patcher('api_log')
        self.mock_get_system_ip_from_system_id = self.safe_patcher(
            'get_system_ip_from_system_id', return_value=(True, 'Mock OK get_system_ip_from_system_id'))
        self.mock_fire_trigger = self.safe_patcher('fire_trigger', return_value=(True, "Firewall enabled"))

    def _rewrite_profile(self):
        """ Rewrites default profile for a remote system """
        # Ah, nice references :)
        self.system_info['profile'] = ['sensor', 'server']

    def test0001(self):
        """ test success call of add_system_from_ip with sensor and server profile"""
        self._rewrite_profile()  # profile=sensor,server
        self.assertEqual((True, self.system_info),
                         add_system_from_ip(self.remote_host_ip, self.root_pass, add_to_database=True))

        # Check mock calls and their params
        self.mock_get_system_id_from_local.assert_called_once_with()
        self.mock_ansible_add_system.assert_called_once_with(local_system_id=self.uuid_local,
                                                             remote_system_ip=self.remote_host_ip,
                                                             password=self.root_pass)

        self.mock_ansible_get_system_info.assert_called_once_with(self.remote_host_ip)
        self.mock_add_child_server.assert_called_once_with(self.remote_host_ip, self.uuid_server_id)
        self.mock_get_sensor_id_from_sensor_ip.assert_not_called()
        self.mock_db_add_system.assert_called_once_with(system_id=self.uuid_system_id,
                                                        name=self.hostname,
                                                        admin_ip=self.remote_host_ip,
                                                        vpn_ip=self.vpn_ip,
                                                        profile='sensor,server',
                                                        server_id=self.uuid_server_id,
                                                        sensor_id=self.uuid_sensor_id)

        self.mock_get_system_ip_from_system_id.assert_called_once_with(self.uuid_system_id)
        # Check that firewall is opened for inserted server.
        self.mock_fire_trigger.assert_called_once_with(system_ip="127.0.0.1",
                                                       trigger="alienvault-add-server")
        self.mock_create_directory_for_ossec_remote.assert_called_once_with(self.uuid_system_id)

    def test0002(self):
        """ test failed call get_system_id_from_local """
        self._rewrite_profile()
        self.mock_get_system_id_from_local.return_value = (False, "Mock ERROR get_system_id_from_local")

        self.assertEqual((False, "Something wrong happened retrieving the local system id"),
                         add_system_from_ip(self.remote_host_ip, self.root_pass, add_to_database=True))

    def test0003(self):
        """ Verify a remote sensor with VPN"""
        status, _ = add_system_from_ip(self.remote_host_ip, self.root_pass, add_to_database=True)
        self.assertTrue(status)
        self.mock_add_child_server.assert_not_called()
        # In sensor only profile when vpn is configured we should get sensor_id for vpn_ip
        self.mock_get_sensor_id_from_sensor_ip.assert_called_once_with(self.vpn_ip)

    def test004(self):
        """ add a remote sensor. Don't store in the database """
        status, _ = add_system_from_ip(self.remote_host_ip, self.root_pass, add_to_database=False)
        self.assertTrue(status)
        self.mock_db_add_system.assert_not_called()

    def test005(self):
        """ add remote sensor. Fails ansible_get_system_info """
        self.mock_ansible_get_system_info.return_value = (False, "Mock ERROR ansible_get_system_info")
        self.assertEqual((False, "Something wrong happened getting the system info"),
                         add_system_from_ip(self.remote_host_ip, self.root_pass, add_to_database=False))
        self.assertTrue(self.mock_ansible_get_system_info.called)

    def test0006(self):
        """ add remote sensor. Fails get_sensor_id_from_sensor_ip """
        self.mock_get_sensor_id_from_sensor_ip.return_value = (False, "Mock ERROR get_sensor_id_from_sensor_ip")

        status, _ = add_system_from_ip(self.remote_host_ip, self.root_pass, add_to_database=True)
        self.assertTrue(status)
        self.assertTrue(self.mock_get_sensor_id_from_sensor_ip.called)
        (_, kwargs) = self.mock_db_add_system.call_args
        self.assertTrue(kwargs['sensor_id'] is None)

    def test0007(self):
        """ add remote system. Fails add_child_server """
        self._rewrite_profile()
        self.mock_add_child_server.return_value = (False, "Mock ERROR add_child_server")

        self.assertEqual((False, 'Something wrong happened setting the child server'),
                         add_system_from_ip(self.remote_host_ip, self.root_pass, add_to_database=True))
        self.assertTrue(self.mock_add_child_server.called)

    def test0008(self):
        """ add remote system. Fails create_directory_for_ossec_remote """
        self._rewrite_profile()
        self.mock_create_directory_for_ossec_remote.return_value = (False, "Mock ERROR create_dir_for_ossec_remote")

        self.assertEqual(self.mock_create_directory_for_ossec_remote.return_value,
                         add_system_from_ip(self.remote_host_ip, self.root_pass, add_to_database=True))
        self.assertTrue(self.mock_create_directory_for_ossec_remote.called)


class TestAddSystem(SystemTestCase):
    """
        Tests for add_system
    """
    def setUp(self):
        super(TestAddSystem, self).setUp()
        self.root_password = 'secret'

        self.mock_get_system_ip_from_system_id = self.safe_patcher('get_system_ip_from_system_id')
        self.mock_get_system_ip_from_system_id.return_value = (True, self.system_ip)

        self.mock_add_system_from_ip = self.safe_patcher('add_system_from_ip')
        self.mock_add_system_from_ip.return_value = (True, "Mock OK add_system_from_ip")

    def test0001(self):
        """ Test add_system ok call """
        res = add_system(self.system_id, self.root_password)

        self.assertTrue(res[0])
        self.mock_get_system_ip_from_system_id.assert_called_once_with(self.system_id)
        self.mock_add_system_from_ip.assert_called_once_with(self.system_ip, self.root_password, add_to_database=False)

    def test0002(self):
        """ test add_system fails get_system_ip_from_system_id """
        self.mock_get_system_ip_from_system_id.return_value = (False, "Mock ERROR get_system_ip_from_system_id")
        res = add_system(self.system_id, self.root_password)

        self.assertFalse(res[0])
        # Check error msg
        self.assertIn("Error retrieving the system ip for the system id Mock ERROR", res[1])
        self.assertTrue(self.mock_get_system_ip_from_system_id.called)

    def test0003(self):
        """ test add_system fails add_system_from_ip """
        self.mock_add_system_from_ip.return_value = (False, "Mock ERROR add_system_from_ip")
        res = add_system(self.system_id, self.root_password)

        self.assertFalse(res[0])
        self.assertTrue(self.mock_add_system_from_ip.called)


class TestApimethodDeleteSystem(SystemTestCase):
    """
        Tests for apimethod_delete_system
    """
    def setUp(self):
        super(TestApimethodDeleteSystem, self).setUp()
        self.local_id = str(uuid.uuid1())
        self.system_ip_local = "192.168.0.1"

        # Mocks
        self.mock_get_system_ip_from_system_id = self.safe_patcher('get_system_ip_from_system_id',
                                                                   return_value=(True, self.system_ip))
        self.mock_db_remove_system = self.safe_patcher('db_remove_system',
                                                       return_value=(True, "Mock OK db_remove_system"))

        self.mock_get_system_ip_from_local = self.safe_patcher('get_system_ip_from_local',
                                                               return_value=(True, self.system_ip_local))
        self.mock_get_system_id_from_local = self.safe_patcher('get_system_id_from_local',
                                                               return_value=(True, self.local_id))
        self.mock_get_server_id_from_local = self.safe_patcher('get_server_id_from_local',
                                                               return_value=(True, self.local_id))
        self.mock_ansible_remove_certificates = self.safe_patcher(
            'ansible_remove_certificates', return_value=(True, "Mock OK ansible_remove_certificates"))
        self.mock_ansible_delete_parent_server = self.safe_patcher(
            'ansible_delete_parent_server', return_value=(True, "Mock OK ansible_delete_parent_server"))

        self.mock_ping_system = self.safe_patcher('ping_system', return_value=True)
        self.mock_ansible_inventory_manager_cls = self.safe_patcher('AnsibleInventoryManager')
        self.ansible_inventory_manager = self.mock_ansible_inventory_manager_cls.return_value

    def test0001(self):
        """ apimethod_delete_system: Positive case """
        self.assertEqual((True, ''), apimethod_delete_system(self.system_id))

        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.mock_get_system_ip_from_system_id.assert_called_once_with(self.system_id)
        self.mock_db_remove_system.assert_called_once_with(self.system_id)
        self.mock_ping_system.assert_called_once_with(self.system_id, no_cache=True)
        self.mock_db_remove_system.assert_called_once_with(self.system_id)
        self.mock_ansible_remove_certificates.assert_called_once_with(system_ip=self.system_ip_local,
                                                                      system_id_to_remove=self.system_id)
        self.ansible_inventory_manager.delete_host.assert_called_once_with(self.system_ip)
        self.ansible_inventory_manager.save_inventory.assert_called_once_with()

    def test0002(self):
        """ apimethod_delete_system: Fails get_system_ip_from_system_id """
        # Fails mock
        err_msg = "Mock ERROR get_system_ip_from_system_id"
        self.mock_get_system_ip_from_system_id.return_value = (False, err_msg)

        self.assertEqual((False, "Cannot retrieve the system ip for the given system-id %s" % err_msg),
                         apimethod_delete_system(self.system_id))
        self.mock_get_system_ip_from_system_id.assert_called_once_with(self.system_id)
        self.mock_db_remove_system.assert_not_called()

    def test0003(self):
        """ apimethod_delete_system: Fails db_remove_system """
        # Fails mock
        err_msg = "Mock ERROR db_remove_system"
        self.mock_db_remove_system.return_value = (False, err_msg)

        self.assertEqual((False, "Cannot remove the system from the database <%s>" % err_msg),
                         apimethod_delete_system(self.system_id))
        self.mock_db_remove_system.assert_called_once_with(self.system_id)
        self.mock_get_system_ip_from_local.assert_not_called()

    def test0004(self):
        """ apimethod_delete_system: Fails get_system_ip_from_local """
        # Fails mock
        err_msg = "Mock ERROR get_system_ip_from_local"
        self.mock_get_system_ip_from_local.return_value = (False, err_msg)

        self.assertEqual((False, "Cannot retrieve the local ip <%s>" % err_msg),
                         apimethod_delete_system(self.system_id))
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.mock_ansible_remove_certificates.assert_not_called()

    def test0005(self):
        """ apimethod_delete_system: Fails on AnsibleInventoryManager """
        # I need to raise a exception within the mock
        err_msg = "Ansible Inventory Manager Mock Exception"
        self.mock_ansible_inventory_manager_cls.side_effect = Exception(err_msg)

        self.assertEqual((False, "Cannot remove the system from the ansible inventory file <%s>" % err_msg),
                         apimethod_delete_system(self.system_id))
        self.assertTrue(self.mock_ansible_remove_certificates.called)

    def test0006(self):
        """ apimethod_delete_system: Fails AnsibleInventoryManager.delete_host """
        # I need to raise a exception within the mock
        err_msg = "Delete Host Mock Exception"
        self.ansible_inventory_manager.delete_host.side_effect = Exception(err_msg)

        self.assertEqual((False, "Cannot remove the system from the ansible inventory file <%s>" % err_msg),
                         apimethod_delete_system(self.system_id))
        self.assertTrue(self.mock_ansible_remove_certificates.called)

    def test0007(self):
        """ apimethod_delete_system: Fails AnsibleInventoryManager.save_inventory """
        # I need to raise a exception within the mock
        err_msg = "Save Inventory Mock Exception"
        self.ansible_inventory_manager.save_inventory.side_effect = Exception(err_msg)

        self.assertEqual((False, "Cannot remove the system from the ansible inventory file <%s>" % err_msg),
                         apimethod_delete_system(self.system_id))
        self.assertTrue(self.mock_ansible_remove_certificates.called)


class TestSyncDatabaseFromChild(SystemTestCase):
    """
        Tests for sync_database_from_child
    """
    def setUp(self):
        super(TestSyncDatabaseFromChild, self).setUp()
        self.remote_ip = '192.168.0.1'
        self.local_system_ip = '192.168.1.20'
        self.local_md5 = '1a2b3c'
        self.remote_id = str(uuid.uuid1())
        self.local_system_id = str(uuid.uuid1())
        self.system_info = {'id': self.local_system_id,
                            'name': 'test system',
                            'admin_ip': '192.168.1.11',
                            'vpn_ip': '10.20.30.40',
                            'profile': 'sensor',
                            'sensor_id': str(uuid.uuid1()),
                            'server_id': str(uuid.uuid1()),
                            'database_id': str(uuid.uuid1()),
                            'host_id': str(uuid.uuid1()),
                            'ha_ip': '192.168.0.10',
                            'ha_name': 'ha name',
                            'ha_role': 'ha role'}
        self.remote_info = copy(self.system_info)
        self.remote_info.update({'id': self.remote_id, 'admin_ip': self.remote_ip})

        # Mocks
        self.system_info_calls_chain = [(True, self.remote_info), (True, self.system_info)]
        self.mock_get_system_info = self.safe_patcher('get_system_info', side_effect=self.system_info_calls_chain)
        self.mock_get_system_id_from_local = self.safe_patcher('get_system_id_from_local',
                                                               return_value=(True, self.local_system_id))
        self.mock_get_system_ip_from_local = self.safe_patcher('get_system_ip_from_local',
                                                               return_value=(True, self.local_system_ip))
        self.mock_rsync_pull = self.safe_patcher('rsync_pull', return_value=(True, ''))
        self.mock_popen_cls = self.safe_patcher('Popen')
        self.mock_popen = self.mock_popen_cls.return_value
        # First call of communicate will return not local md5 value , but second call will be the same as local md5
        self.mock_popen.communicate.side_effect = (('md5 from remote', ''), (self.local_md5, ''))
        self.mock_restart_ossim_server = self.safe_patcher('restart_ossim_server')
        self.mock_has_forward_role = self.safe_patcher('has_forward_role', return_value=True)
        self.mock_generate_sync_sql = self.safe_patcher('generate_sync_sql')

        self.mock_open = self.safe_patcher('__builtin__.open', new=mock_open(), create=True, reload_patcher=True)
        self.mock_open.return_value.readline.return_value = self.local_md5
        self.mock_call = self.safe_patcher('call', return_value=0)

    def test0001(self):
        """ sync_database_from_child: Failed to get system_info"""
        err_msg = 'Failed to get_system_info for child'
        self.system_info_calls_chain[0] = (False, err_msg)

        status, msg = sync_database_from_child(self.remote_id)
        self.assertFalse(status)
        # Check that error msg contains appropriate error
        self.assertIn(err_msg, msg)
        self.mock_get_system_info.assert_called_once_with(self.remote_id)

    def test0002(self):
        """ sync_database_from_child: Failed to get local system ID"""
        err_msg = 'Failed to get system ID'
        self.mock_get_system_id_from_local.return_value = (False, err_msg)

        status, msg = sync_database_from_child(self.remote_id)
        self.assertFalse(status)
        self.assertIn(err_msg, msg)
        self.mock_get_system_info.assert_called_once_with(self.remote_id)
        self.assertTrue(self.mock_get_system_id_from_local.called)

    def test0003(self):
        """ sync_database_from_child: Failed to get system_info from local ID """
        err_msg = 'Failed to get system info for local ID'
        self.system_info_calls_chain[1] = (False, err_msg)

        status, msg = sync_database_from_child(self.remote_id)
        self.assertFalse(status)
        self.assertIn(err_msg, msg)
        self.assertTrue(self.mock_get_system_id_from_local.called)
        self.assertEqual(self.mock_get_system_info.call_count, 2)

    def test0004(self):
        """ sync_database_from_child: Failed to get system IP """
        # Simulate empty VPN IP field. Is it really possible?
        self.system_info_calls_chain[1] = (True, {})

        status, msg = sync_database_from_child(self.remote_id)
        self.assertFalse(status)
        self.assertIn("Error retrieving the system ip", msg)
        self.assertTrue(self.mock_get_system_id_from_local.called)
        self.assertEqual(self.mock_get_system_info.call_count, 2)
        self.mock_get_system_ip_from_local.assert_not_called()

    def test0005(self):
        """ sync_database_from_child: Failed to get system IP from local """
        err_msg = "Failed to get local IP"
        self.mock_get_system_ip_from_local.return_value = (False, err_msg)

        status, msg = sync_database_from_child(self.remote_id)
        self.assertFalse(status)
        self.assertIn(err_msg, msg)
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.mock_rsync_pull.assert_not_called()

    def test0006(self):
        """ sync_database_from_child: Failed to do rsync_pull (+vpn is set) """
        err_msg = "Failed to sync"
        self.mock_rsync_pull.return_value = (False, err_msg)
        remote_file_path = '/var/lib/alienvault-center/db/sync.md5'
        local_file_path = '/var/alienvault/{0}/sync_{0}.md5'.format(self.remote_id)

        status, msg = sync_database_from_child(self.remote_id)
        self.assertFalse(status)
        self.assertIn(err_msg, msg)
        self.mock_rsync_pull.assert_called_once_with(self.remote_info['vpn_ip'], remote_file_path,
                                                     self.local_system_ip, local_file_path)

    def test0007(self):
        """ sync_database_from_child: SQL is already synced """
        local_file_path = '/var/alienvault/{0}/sync_{0}.md5'.format(self.remote_id)
        self.mock_popen.communicate.side_effect = ((self.local_md5, ''),)
        self.mock_rsync_pull.return_value = (False, 'File(s) already in sync')

        self.assertEqual((True, "[Apimethod sync_database_from_child] SQL already synced"),
                         sync_database_from_child(self.remote_id))
        self.mock_open.assert_called_once_with(local_file_path)
        self.assertTrue(self.mock_rsync_pull.called)

    def test0008(self):
        """ sync_database_from_child: Databases are in sync """
        self.mock_rsync_pull.side_effect = ((True, 'Ok'), (False, 'File(s) already in sync'))

        self.assertEqual((True, "[Apimethod sync_database_from_child] Databases already in sync"),
                         sync_database_from_child(self.remote_id))
        self.assertEqual(self.mock_rsync_pull.call_count, 2)

    def test0009(self):
        """ sync_database_from_child: Second call of rsync_pull failed """
        self.mock_rsync_pull.side_effect = ((True, 'Ok'), (False, 'Error'))

        self.assertEqual((False, "[Apimethod sync_database_from_child] Error"),
                         sync_database_from_child(self.remote_id))
        self.assertEqual(self.mock_rsync_pull.call_count, 2)

    def test0010(self):
        """ sync_database_from_child: Corrupt SQL file (bad md5 sum) """
        # Simulate md5 check-sum difference.
        self.mock_popen.communicate.side_effect = (('123', ''), ('124', ''))

        self.assertEqual((False, "[Apimethod sync_database_from_child] Corrupt or incomplete SQL file (bad md5sum)"),
                         sync_database_from_child(self.remote_id))
        self.assertEqual(self.mock_rsync_pull.call_count, 2)
        self.assertEqual(self.mock_popen_cls.return_value.communicate.call_count, 2)

    def test0011(self):
        """ sync_database_from_child: Failed to apply SQL file """
        self.mock_call.return_value = 1

        self.assertEqual((False, "[Apimethod sync_database_from_child] Error applying SQL file to ossim-db"),
                         sync_database_from_child(self.remote_id))
        self.mock_call.assert_called_once_with(['/usr/bin/ossim-db'], stdin=self.mock_open.return_value)

    def test0012(self):
        """ sync_database_from_child: Failed on restart ossim-server """
        # Simulate that restart ossim-server msg appears in sync.sql
        err_msg = "failed restart of ossim-server"
        self.mock_open.return_value.readline.side_effect = (self.local_md5, 'RESTART OSSIM-SERVER')
        self.mock_restart_ossim_server.side_effect = IOError(err_msg)

        self.assertEqual((False, "An error occurred while restarting MySQL server: %s" % err_msg),
                         sync_database_from_child(self.remote_id))
        self.mock_call.assert_called_once_with(['/usr/bin/ossim-db'], stdin=self.mock_open.return_value)

    def test0013(self):
        """ sync_database_from_child: Failed on generating sync.sql """
        err_msg = "failed to generate sync file"
        self.mock_generate_sync_sql.side_effect = IOError(err_msg)

        self.assertEqual((False, "An error occurred while generating sync.sql file: %s" % err_msg),
                         sync_database_from_child(self.remote_id))
        self.mock_call.assert_called_once_with(['/usr/bin/ossim-db'], stdin=self.mock_open.return_value)
        self.assertEqual(self.mock_get_system_id_from_local.call_count, 2)
        self.mock_has_forward_role.assert_called_once_with(self.local_system_id)
        self.mock_generate_sync_sql.assert_called_once_with(self.local_system_ip, False)

    def test0015(self):
        """ sync_database_from_child: Positive case """
        self.assertEqual((True, "[Apimethod sync_database_from_child] SQL sync successful"),
                         sync_database_from_child(self.remote_id))
        self.mock_call.assert_called_once_with(['/usr/bin/ossim-db'], stdin=self.mock_open.return_value)
        self.mock_api_log.info.assert_called_once_with('[Apimethod sync_database_from_child] SQL applied successfully')
        self.assertEqual(self.mock_get_system_id_from_local.call_count, 2)
        self.assertTrue(self.mock_has_forward_role.called)


class TestMakeVPNTunnel(SystemTestCase):
    """
        Test for make_tunnel_with_vpn
    """
    def setUp(self):
        super(TestMakeVPNTunnel, self).setUp()
        self.client_end_point_ip = '10.20.20.10'
        self.mock_is_valid_ipv4 = self.safe_patcher('is_valid_ipv4', return_value=True)
        self.mock_get_server_id_from_local = self.safe_patcher('get_server_id_from_local')
        self.mock_get_server_id_from_local.return_value = (True, self.system_id)
        self.mock_get_system_ip_from_local = self.safe_patcher('get_system_ip_from_local')
        self.mock_get_system_ip_from_local.return_value = (True, self.system_ip)
        self.mock_ans_make_vpn_tunnel = self.safe_patcher('ansible_make_tunnel_with_vpn')
        self.mock_ans_make_vpn_tunnel.return_value = (True, {'client_end_point1': self.client_end_point_ip})
        self.mock_get_system_id_from_system_ip = self.safe_patcher('get_system_id_from_system_ip')
        self.mock_get_system_id_from_system_ip.return_value = (False, '')
        self.mock_set_system_vpn_ip = self.safe_patcher('set_system_vpn_ip')
        self.mock_flush_cache = self.safe_patcher('flush_cache')
        self.mock_ans_restart_frameworkd = self.safe_patcher('ansible_restart_frameworkd', return_value=(True, ''))

    def test0001(self):
        """ make_tunnel_with_vpn: Failed with invalid IP """
        self.mock_is_valid_ipv4.return_value = False

        self.assertEqual((False, 'Invalid system ip: not_ip'), make_tunnel_with_vpn('not_ip', self.root_pass))
        self.mock_get_server_id_from_local.assert_not_called()

    def test0002(self):
        """ make_tunnel_with_vpn: Failed to get local ID """
        mock_err = 'mock error ID'
        self.mock_get_server_id_from_local.return_value = (False, mock_err)

        self.assertEqual((False, 'Error while retrieving server_id from local: %s' % mock_err),
                         make_tunnel_with_vpn(self.system_ip, self.root_pass))
        self.mock_is_valid_ipv4.assert_called_once_with(self.system_ip)
        self.mock_get_server_id_from_local.assert_called_once_with()
        self.mock_get_system_ip_from_local.assert_not_called()

    def test0003(self):
        """ make_tunnel_with_vpn: Failed to get local IP """
        mock_err = 'mock error IP'
        self.mock_get_system_ip_from_local.return_value = (False, mock_err)

        self.assertEqual((False, 'Cannot retrieve the local ip <%s>' % mock_err),
                         make_tunnel_with_vpn(self.system_ip, self.root_pass))
        self.mock_is_valid_ipv4.assert_called_once_with(self.system_ip)
        self.mock_get_server_id_from_local.assert_called_once_with()
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.mock_ans_make_vpn_tunnel.assert_not_called()

    def test0004(self):
        """ make_tunnel_with_vpn: Failed to make VPN tunnel """
        mock_err = 'mock error VPN'
        self.mock_ans_make_vpn_tunnel.return_value = (False, mock_err)

        self.assertEqual((False, mock_err), make_tunnel_with_vpn(self.system_ip, self.root_pass))
        self.mock_is_valid_ipv4.assert_called_once_with(self.system_ip)
        self.mock_get_server_id_from_local.assert_called_once_with()
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.assertTrue(self.mock_ans_make_vpn_tunnel.called)
        self.mock_get_system_id_from_system_ip.assert_not_called()

    def test0005(self):
        """ make_tunnel_with_vpn: Failed to set VPN IP """
        vpn_data = 'some_vpn_related_data'
        self.mock_get_system_id_from_system_ip.return_value = (True, vpn_data)
        self.mock_set_system_vpn_ip.return_value = (False, '')

        self.assertEqual((False, 'Cannot set the new node vpn ip on the system table'),
                         make_tunnel_with_vpn(self.system_ip, self.root_pass))
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.assertTrue(self.mock_ans_make_vpn_tunnel.called)
        self.mock_get_system_id_from_system_ip.assert_called_once_with(self.system_ip)
        self.mock_set_system_vpn_ip.assert_called_once_with(vpn_data, self.client_end_point_ip)
        self.mock_flush_cache.assert_not_called()

    def test0006(self):
        """ make_tunnel_with_vpn: Positive case with setting VPN IP """
        vpn_data = 'some_vpn_related_data'
        self.mock_get_system_id_from_system_ip.return_value = (True, vpn_data)
        self.mock_set_system_vpn_ip.return_value = (True, '')

        self.assertEqual((True, 'VPN node successfully connected.'),
                         make_tunnel_with_vpn(self.system_ip, self.root_pass))
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.assertTrue(self.mock_ans_make_vpn_tunnel.called)
        self.mock_get_system_id_from_system_ip.assert_called_once_with(self.system_ip)
        self.mock_set_system_vpn_ip.assert_called_once_with(vpn_data, self.client_end_point_ip)
        self.mock_flush_cache.assert_called_once_with(namespace='support_tunnel')

        self.mock_ans_restart_frameworkd.assert_called_once_with(system_ip=self.system_ip)

    def test0007(self):
        """ make_tunnel_with_vpn: Positive case without setting VPN IP """
        self.assertEqual((True, 'VPN node successfully connected.'),
                         make_tunnel_with_vpn(self.system_ip, self.root_pass))
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.assertTrue(self.mock_ans_make_vpn_tunnel.called)
        self.mock_set_system_vpn_ip.assert_not_called()
        self.mock_flush_cache.assert_called_once_with(namespace='support_tunnel')
        self.mock_ans_restart_frameworkd.assert_called_once_with(system_ip=self.system_ip)


class TestSyncAsecPlugins(SystemTestCase):
    """
        Tests for sync_asec_plugins
    """
    def setUp(self):
        super(TestSyncAsecPlugins, self).setUp()

        self.sensor_ip = '192.168.22.33'
        self.sensors = ((self.system_id, self.sensor_ip),)
        self.sensor_data = {'sensor_detectors': ['old_plugin']}
        self.plugin_name = 'test_plugin'
        self.expected_list_of_detectors = 'old_plugin, %s' % self.plugin_name
        self.default_plugin_path = '/var/lib/asec/plugins/%s.cfg' % self.plugin_name
        self.default_plugin_sql_path = self.default_plugin_path + '.sql'
        self.expected_plugin_tmp_path = '/tmp/%s.cfg' % self.plugin_name
        self.expected_plugin_sql_path = '%s.%s' % (self.expected_plugin_tmp_path, 'sql')

        # Mocks
        self.mock_get_systems = self.safe_patcher('get_systems', return_value=(True, self.sensors))
        self.mock_get_system_ip_from_local = self.safe_patcher('get_system_ip_from_local',
                                                               return_value=(True, self.system_ip))
        self.mock_local_copy_file = self.safe_patcher('local_copy_file', return_value=(True, ''))
        self.mock_ansible_install_plugin = self.safe_patcher('ansible_install_plugin', return_value=(True, ''))
        self.mock_get_sensor_detectors = self.safe_patcher('get_sensor_detectors',
                                                           return_value=(True, self.sensor_data))
        self.mock_set_sensor_detectors = self.safe_patcher('set_sensor_detectors', return_value=(True, ''))
        self.mock_alienvault_reconfigure = self.safe_patcher('alienvault_reconfigure')
        self.mock_remove_file = self.safe_patcher('remove_file')

    def test0001(self):
        """ sync_asec_plugins: No plugins to sync """
        self.assertEqual((False, 'No plugin to sync'), sync_asec_plugins())
        self.mock_get_systems.assert_not_called()

    def test0002(self):
        """ sync_asec_plugins: Failed to get sensors list """
        mock_err = 'mock error -> failed to get sensors'
        self.mock_get_systems.return_value = (False, mock_err)

        self.assertEqual((False, 'Unable to get sensors list: %s' % mock_err),
                         sync_asec_plugins(plugin=self.plugin_name))
        self.mock_get_systems.assert_called_once_with(system_type='sensor')
        self.mock_get_system_ip_from_local.assert_not_called()

    def test0003(self):
        """ sync_asec_plugins: Failed to get local IP """
        mock_err = 'mock error -> failed to get IP'
        self.mock_get_system_ip_from_local.return_value = (False, mock_err)

        self.assertEqual((False, '[ansible_install_plugin] Failed to make get local IP: %s' % mock_err),
                         sync_asec_plugins(plugin=self.plugin_name))
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.mock_local_copy_file.assert_not_called()

    def test0004(self):
        """ sync_asec_plugins: Failed to copy plugin .cfg file """
        mock_err = 'mock error -> failed to copy cfg'
        self.mock_local_copy_file.return_value = (False, mock_err)

        self.assertEqual((False, '[ansible_install_plugin] Failed to make temp copy of plugin file: %s' % mock_err),
                         sync_asec_plugins(plugin=self.plugin_name))
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.mock_local_copy_file.assert_called_once_with(self.system_ip,
                                                          self.default_plugin_path,
                                                          self.expected_plugin_tmp_path)

    def test0005(self):
        """ sync_asec_plugins: Failed to copy plugin .sql file """
        mock_err = 'mock error -> failed to copy sql'
        self.mock_local_copy_file.side_effect = ((True, ''), (False, mock_err))
        expected_copy_calls = (call(self.system_ip, self.default_plugin_path, self.expected_plugin_tmp_path),
                               call(self.system_ip, self.default_plugin_sql_path, self.expected_plugin_sql_path))

        self.assertEqual((False, '[ansible_install_plugin] Failed to make temp copy of sql file: %s' % mock_err),
                         sync_asec_plugins(plugin=self.plugin_name))
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.mock_local_copy_file.assert_has_calls(expected_copy_calls)

    def test0006(self):
        """ sync_asec_plugins: Failed to install plugin """
        expected_calls_of_remove_files = [call([self.system_ip], self.expected_plugin_tmp_path),
                                          call([self.system_ip], self.expected_plugin_sql_path)]
        mock_err = 'mock error -> failed to install'
        self.mock_ansible_install_plugin.return_value = (False, mock_err)

        self.assertEqual((False, 'Plugin %s installation failed for some sensors' % self.plugin_name),
                         sync_asec_plugins(plugin=self.plugin_name))
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.mock_ansible_install_plugin.assert_called_once_with(self.sensor_ip,
                                                                 self.expected_plugin_tmp_path,
                                                                 self.expected_plugin_sql_path)
        self.mock_get_sensor_detectors.assert_not_called()
        self.mock_alienvault_reconfigure.assert_not_called()
        self.mock_remove_file.assert_has_calls(expected_calls_of_remove_files)

    def test0007(self):
        """ sync_asec_plugins: Failed to get sensor detectors """
        mock_err = 'mock error -> failed to detectors'
        self.mock_get_sensor_detectors.return_value = (False, mock_err)

        self.assertEqual((False, 'Plugin %s installation failed for some sensors' % self.plugin_name),
                         sync_asec_plugins(plugin=self.plugin_name))
        self.assertTrue(self.mock_get_sensor_detectors.called)
        self.mock_alienvault_reconfigure.assert_not_called()
        self.assertEqual(self.mock_remove_file.call_count, 2)

    def test0008(self):
        """ sync_asec_plugins: Failed due to Exception """
        mock_err = 'mock error -> error raised'
        self.mock_get_systems.side_effect = IOError(mock_err)

        self.assertEqual((False, '[sync_asec_plugins] Unknown error'), sync_asec_plugins(plugin=self.plugin_name))
        self.mock_get_sensor_detectors.assert_not_called()
        self.mock_api_log.error.any_call('[sync_asec_plugins] Exception catched: %s' % mock_err)

    def test0009(self):
        """ sync_asec_plugins: Positive case with enable """
        self.assertEqual((True, 'Plugin %s installed. Enabled = True' % self.plugin_name),
                         sync_asec_plugins(plugin=self.plugin_name))
        self.assertTrue(self.mock_set_sensor_detectors.called)
        self.mock_api_log.error.assert_not_called()

    def test0010(self):
        """ sync_asec_plugins: Positive case without enable """
        self.assertEqual((True, 'Plugin %s installed. Enabled = False' % self.plugin_name),
                         sync_asec_plugins(plugin=self.plugin_name, enable=False))
        self.mock_get_sensor_detectors.assert_not_called()
        self.mock_set_sensor_detectors.assert_not_called()
        self.mock_api_log.error.assert_not_called()


class TestGetPendinfPackges(SystemTestCase):
    """
        Tests for apimethod_get_pending_packges
    """
    def setUp(self):
        super(TestGetPendinfPackges, self).setUp()
        self.updates_available = 'some_updates_here'
        self.updates_data = {'available_updates': self.updates_available}

        # Mocks
        self.mock_apimethod_get_update_info = self.safe_patcher('apimethod_get_update_info',
                                                                return_value=(True, self.updates_data))
        self.mock_get_system_ip_from_local = self.safe_patcher('get_system_ip_from_local',
                                                               return_value=(True, self.system_ip))
        self.mock_get_is_professional = self.safe_patcher('get_is_professional', return_value=(True, True))
        self.mock_system_is_trial = self.safe_patcher('system_is_trial', return_value=(True, True))
        self.mock_ansible_download_release_info = self.safe_patcher('ansible_download_release_info',
                                                                    return_value=(True, True))

    def test0001(self):
        """ apimethod_get_pending_packges: Should fail on getting update info """
        mock_err = "can't get info"
        self.mock_apimethod_get_update_info.return_value = (False, mock_err)

        self.assertEqual(self.mock_apimethod_get_update_info.return_value,
                         apimethod_get_pending_packges(self.system_id))
        self.mock_get_system_ip_from_local.assert_not_called()

    def test0002(self):
        """ apimethod_get_pending_packges: Should fail on getting local IP """
        mock_err = "can't get IP"
        expected_err_msg = '[apimethod_get_pending_packges] Unable to get local IP: %s' % mock_err
        self.mock_get_system_ip_from_local.return_value = (False, mock_err)

        self.assertEqual((False, self.updates_available), apimethod_get_pending_packges(self.system_id))
        self.mock_api_log.error.assert_called_once_with(expected_err_msg)
        self.mock_get_system_ip_from_local.assert_called_once_with()
        self.mock_get_is_professional.assert_not_called()

    def test0003(self):
        """ apimethod_get_pending_packges: Positive for professional-trial without downloading release info"""
        expected_msg = '[apimethod_get_pending_packges] Trial version. Skipping download release info file'

        self.assertEqual((True, self.updates_available), apimethod_get_pending_packges(self.system_id))
        self.mock_get_is_professional.assert_called_once_with(self.system_ip)
        self.mock_system_is_trial.assert_called_once_with(system_id='local')
        self.mock_api_log.info.assert_called_once_with(expected_msg)
        self.mock_ansible_download_release_info.assert_not_called()

    def test0004(self):
        """ apimethod_get_pending_packges: Positive for not professional """
        self.mock_get_is_professional.return_value = (True, False)

        self.assertEqual((True, self.updates_available), apimethod_get_pending_packges(self.system_id))
        self.mock_get_is_professional.assert_called_once_with(self.system_ip)
        self.mock_system_is_trial.assert_not_called()
        self.mock_api_log.error.assert_not_called()
        self.mock_ansible_download_release_info.assert_called_once_with(self.system_ip)


class TestSmallFunctionsInSystems(SystemTestCase):
    """
        Common TestCase class for all small functions in this package
    """
    def setUp(self):
        super(TestSmallFunctionsInSystems, self).setUp()
        self.mock_get_system_ip_from_system_id = self.safe_patcher('get_system_ip_from_system_id')
        self.mock_get_system_ip_from_system_id.return_value = (True, self.system_ip)
        self.positive_ansible_response = (True, '')

    def _get_system_ip_from_id_failed_with_error(self, err_msg='Not found'):
        self.mock_get_system_ip_from_system_id.return_value = (False, err_msg)

    def _check_result_after_failed_system_ip(self, result, expected_data=None, cmp_method="assertIn"):
        if expected_data is None:
            expected_data = 'Error retrieving the system ip for the system id'

        status, data = result
        self.assertFalse(status)
        cmp_method = self.__getattribute__(cmp_method)
        cmp_method(expected_data, data)

    def _check_ansible_response(self, result, expected_status=True, expected_data=''):
        status, data = result
        self.assertEqual(expected_status, status)
        self.assertEqual(expected_data, data)

    def test001(self):
        """ asynchronous_reconfigure: Failed to get system IP from system ID """
        self._get_system_ip_from_id_failed_with_error()

        self._check_result_after_failed_system_ip(asynchronous_reconfigure(self.system_id))
        self.mock_get_system_ip_from_system_id.assert_called_once_with(self.system_id)

    @patch('apimethods.system.system.ansible_run_async_reconfig')
    def test0002(self, mock_ans_reconfig):
        """ asynchronous_reconfigure: ansible_run_async_reconfig executed """
        mock_ans_reconfig.return_value = (True, 'path_to_reconfig_log')

        actual_result = asynchronous_reconfigure(self.system_id)
        self.assertEqual(mock_ans_reconfig.return_value, actual_result)
        self.mock_get_system_ip_from_system_id.assert_called_once_with(self.system_id)
        mock_ans_reconfig.assert_called_once_with(self.system_ip)

    def test0003(self):
        """ asynchronous_update: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(asynchronous_update(self.system_id))
        self.assertTrue(self.mock_get_system_ip_from_system_id.called)

    @patch('apimethods.system.system.alienvault_asynchronous_update')
    def test0004(self, mock_av_async_update):
        """ asynchronous_update: Failed to update system via async job """
        mock_av_async_update.delay.return_value = None

        status, msg = asynchronous_update(self.system_id, only_feed=True, update_key='abc')
        self.assertFalse(status)
        self.assertIn("Please verify that the system is reachable", msg)
        mock_av_async_update.delay.assert_called_once_with(self.system_ip, True, "abc")

    @patch('apimethods.system.system.flush_cache')
    @patch('apimethods.system.system.alienvault_asynchronous_update')
    def test0005(self, mock_av_async_update, mock_flush_cache):
        """ asynchronous_update: Positive case """
        mock_job = MagicMock()
        mock_job.id = "1221"
        mock_av_async_update.delay.return_value = mock_job

        self._check_ansible_response(asynchronous_update(self.system_id), expected_data=mock_job.id)
        mock_av_async_update.delay.assert_called_once_with(self.system_ip, False, "")
        mock_flush_cache.assert_called_once_with(namespace="system_packages")

    @patch('apimethods.system.system.ansible_check_if_process_is_running')
    def test0006(self, mock_ans_process_running):
        """ check_if_process_is_running: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(check_if_process_is_running(self.system_id, 'test_process'))
        mock_ans_process_running.assert_not_called()

    @patch('apimethods.system.system.ansible_check_if_process_is_running')
    def test0007(self, mock_ans_process_running):
        """ check_if_process_is_running: Positive case """
        mock_ans_process_running.return_value = self.positive_ansible_response

        self._check_ansible_response(check_if_process_is_running(self.system_id, 'test_process'))
        mock_ans_process_running.assert_called_once_with(self.system_ip, "test_process")

    @patch('apimethods.system.system.ansible_check_asynchronous_command_return_code')
    def test0008(self, mock_ans_check_cmd_return_code):
        """ apimethod_check_asynchronous_command_return_code: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()

        self._check_result_after_failed_system_ip(apimethod_check_asynchronous_command_return_code(self.system_id,
                                                                                                   'path_to_rc_file'))
        mock_ans_check_cmd_return_code.assert_not_called()

    @patch('apimethods.system.system.ansible_check_asynchronous_command_return_code')
    def test0009(self, mock_ans_check_cmd_return_code):
        """ apimethod_check_asynchronous_command_return_code: Positive case """
        mock_ans_check_cmd_return_code.return_value = self.positive_ansible_response

        self._check_ansible_response(apimethod_check_asynchronous_command_return_code(self.system_id,
                                                                                      'path_to_rc_file'))
        mock_ans_check_cmd_return_code.assert_called_once_with(self.system_ip, 'path_to_rc_file')

    @patch('apimethods.system.system.ansible_get_asynchronous_command_log_file')
    def test0010(self, mock_ans_get_cmd_log_file):
        """ apimethod_get_asynchronous_command_log_file: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(apimethod_get_asynchronous_command_log_file(self.system_id,
                                                                                              'log_path'))
        mock_ans_get_cmd_log_file.assert_not_called()

    @patch('apimethods.system.system.ansible_get_asynchronous_command_log_file')
    def test0011(self, mock_ans_get_cmd_log_file):
        """ apimethod_get_asynchronous_command_log_file: Positive case """
        mock_ans_get_cmd_log_file.return_value = self.positive_ansible_response
        log_file = 'path_to_log_file_wanted_to_retrieve'

        self._check_ansible_response(apimethod_get_asynchronous_command_log_file(self.system_id, log_file))
        mock_ans_get_cmd_log_file.assert_called_once_with(self.system_ip, log_file)

    @patch('apimethods.system.system.get_task_status')
    def test0012(self, mock_get_task_status):
        """ apimethod_check_task_status: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(apimethod_check_task_status(self.system_id, ['test']),
                                                  expected_data={}, cmp_method="assertEqual")
        mock_get_task_status.assert_not_called()

    @patch('apimethods.system.system.get_task_status')
    def test0013(self, mock_get_task_status):
        """ apimethod_check_task_status: Failed to fetch task status """
        task_list = ['task1']
        err_msg = 'Error while fetching task status'
        mock_get_task_status.return_value = (False, err_msg)
        expected_err_msg = ('[apimethod_check_task_status]'
                            ' Unable to get the task status for system %s: %s' % (self.system_id, err_msg))

        result = apimethod_check_task_status(self.system_id, task_list)
        self.assertEqual((False, {}), result)
        self.mock_api_log.error.assert_called_once_with(expected_err_msg)
        mock_get_task_status.assert_called_once_with(self.system_id, self.system_ip, task_list)

    @patch('apimethods.system.system.get_task_status')
    def test0014(self, mock_get_task_status):
        """ apimethod_check_task_status: Positive case """
        task_list = ['task1', 'task2']
        task_data = {'some_status_here': 'ok'}
        mock_get_task_status.return_value = (True, task_data)

        self._check_ansible_response(apimethod_check_task_status(self.system_id, task_list),
                                     expected_data=task_data)
        mock_get_task_status.assert_called_once_with(self.system_id, self.system_ip, task_list)

    @patch('apimethods.system.system.apimethod_check_task_status')
    def test0015(self, mock_apimethod_check_task_status):
        """ check_update_and_reconfig_status: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(check_update_and_reconfig_status(self.system_id),
                                                  expected_data="", cmp_method="assertEqual")
        mock_apimethod_check_task_status.assert_not_called()

    @patch('apimethods.system.system.apimethod_check_task_status')
    def test0016(self, mock_apimethod_check_task_status):
        """ check_update_and_reconfig_status: Failed to get task status """
        err_msg = 'Error occurs while checking statuses'
        mock_apimethod_check_task_status.return_value = (False, err_msg)

        result = check_update_and_reconfig_status(self.system_id)
        self.assertEqual((False, err_msg), result)
        self.assertTrue(self.mock_api_log.error.called)
        self.assertTrue(mock_apimethod_check_task_status.called)

    @patch('apimethods.system.system.apimethod_check_task_status')
    def test0017(self, mock_apimethod_check_task_status):
        """ check_update_and_reconfig_status: Positive case """
        fake_result = {'alienvault-reconfig': 'running'}
        mock_apimethod_check_task_status.return_value = (True, fake_result)
        expected_task_list = {
            "alienvault-update": {'task': 'alienvault_asynchronous_update',
                                  'process': 'alienvault-update',
                                  'param_value': self.system_ip,
                                  'param_argnum': 0},
            "alienvault-reconfig": {'task': 'alienvault_asynchronous_reconfigure',
                                    'process': 'alienvault-reconfig',
                                    'param_value': self.system_ip,
                                    'param_argnum': 0},
            "ossim-reconfig": {'task': '',
                               'process': 'ossim-reconfig',
                               'param_value': self.system_ip,
                               'param_argnum': 0}
        }
        result = check_update_and_reconfig_status(self.system_id)
        self.assertEqual((True, fake_result), result)
        mock_apimethod_check_task_status.assert_called_once_with(self.system_id, expected_task_list)

    @patch('apimethods.system.system.ansible_get_log_lines')
    def test0018(self, mock_ansible_get_log_lines):
        """ get_last_log_lines: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(get_last_log_lines(self.system_id, 'auth', 50))
        mock_ansible_get_log_lines.assert_not_called()

    @patch('apimethods.system.system.ansible_get_log_lines')
    def test0019(self, mock_ansible_get_log_lines):
        """ get_last_log_lines: Not valid log file """
        invalid_log_file = 'not_a_log_file'
        result = get_last_log_lines(self.system_id, invalid_log_file, 100)
        self.assertEqual((False, '%s is not a valid key for a log file' % invalid_log_file), result)
        mock_ansible_get_log_lines.assert_not_called()

    @patch('apimethods.system.system.ansible_get_log_lines')
    def test0020(self, mock_ansible_get_log_lines):
        """ get_last_log_lines: Invalid number of lines """
        invalid_number_of_lines = 100500
        expected_err_msg = ("%s is not a valid number of lines. "
                            "The number of lines should be in [50, 100, 1000, 5000]" % invalid_number_of_lines)
        result = get_last_log_lines(self.system_id, 'syslog', invalid_number_of_lines)
        self.assertEqual((False, expected_err_msg), result)
        mock_ansible_get_log_lines.assert_not_called()

    @patch('apimethods.system.system.ansible_get_log_lines')
    def test0021(self, mock_ansible_get_log_lines):
        """ get_last_log_lines: Invalid number of lines """
        expected_err_msg = 'Failed to get log lines'
        mock_ansible_get_log_lines.return_value = (False, expected_err_msg)
        result = get_last_log_lines(self.system_id, 'syslog', 50)
        self.assertEqual(mock_ansible_get_log_lines.return_value, result)
        self.mock_api_log.error.assert_called_once_with(expected_err_msg)
        self.assertTrue(mock_ansible_get_log_lines.called)

    @patch('apimethods.system.system.ansible_get_log_lines')
    def test0022(self, mock_ansible_get_log_lines):
        """ get_last_log_lines: Positive case #1 to check all allowed files """
        mock_ansible_get_log_lines.return_value = self.positive_ansible_response

        allowed_files = {
            'kern': '/var/log/kern.log',
            'auth': '/var/log/auth.log',
            'daemon': '/var/log/daemon.log',
            'messages': '/var/log/messages',
            'syslog': '/var/log/syslog',
            'agent_stats': '/var/log/alienvault/agent/agent_stats.log',
            'agent': '/var/log/alienvault/agent/agent.log',
            'server': '/var/log/alienvault/server/server.log',
            'reputation': '/var/log/ossim/reputation.log',
            'apache_access': '/var/log/apache2/access.log',
            'apache_error': '/var/log/apache2/error.log',
            'frameworkd': '/var/log/ossim/frameworkd.log',
            'last_update': '/var/log/alienvault/update/last_system_update.rc'
        }

        for valid_file in allowed_files:
            self.assertEqual(self.positive_ansible_response, get_last_log_lines(self.system_id, valid_file, 50))
            mock_ansible_get_log_lines.assert_called_with(self.system_ip,
                                                          logfile=allowed_files[valid_file],
                                                          lines=50)

    @patch('apimethods.system.system.ansible_get_log_lines')
    def test0023(self, mock_ansible_get_log_lines):
        """ get_last_log_lines: Positive case #2 to check all allowed line numbers """
        mock_ansible_get_log_lines.return_value = self.positive_ansible_response

        for valid_line in [50, 100, 1000, 5000]:
            self.assertEqual(self.positive_ansible_response, get_last_log_lines(self.system_id, 'auth', valid_line))
            mock_ansible_get_log_lines.assert_called_with(self.system_ip,
                                                          logfile='/var/log/auth.log',
                                                          lines=valid_line)

    @patch('apimethods.system.system.get_license_info')
    def test0024(self, mock_get_license_info):
        """ system_is_professional: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(system_is_trial(system_id=self.system_id))
        mock_get_license_info.assert_not_called()

    @patch('apimethods.system.system.get_license_info')
    def test0025(self, mock_get_license_info):
        """ system_is_professional: Positive case #1 system is trial """
        mock_get_license_info.return_value = (True, {})

        self.assertEqual((True, False), system_is_trial())
        self.mock_get_system_ip_from_system_id.assert_called_once_with('local')
        mock_get_license_info.assert_called_with(self.system_ip)

    @patch('apimethods.system.system.get_license_info')
    def test0026(self, mock_get_license_info):
        """ system_is_professional: Positive case #2 system is not trial """
        mock_get_license_info.return_value = (True, {'email': 'test@test.me'})

        self.assertEqual((True, True), system_is_trial(system_id=self.system_id))
        self.mock_get_system_ip_from_system_id.assert_called_once_with(self.system_id)
        mock_get_license_info.assert_called_with(self.system_ip)

    @patch('apimethods.system.system.get_alienvault_version')
    def test0027(self, mock_get_alienvault_version):
        """ system_is_professional: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(system_is_professional(system_id=self.system_id))
        mock_get_alienvault_version.assert_not_called()

    @patch('apimethods.system.system.get_alienvault_version')
    def test0028(self, mock_get_alienvault_version):
        """ system_is_professional: Positive case #1 system is professional """
        mock_get_alienvault_version.return_value = (True, 'ALIENVAULT some version number')

        self.assertEqual((True, True), system_is_professional())
        self.mock_get_system_ip_from_system_id.assert_called_once_with('local')
        mock_get_alienvault_version.assert_called_with(self.system_ip)

    @patch('apimethods.system.system.get_alienvault_version')
    def test0029(self, mock_get_alienvault_version):
        """ system_is_professional: Positive case #2 system is not professional """
        mock_get_alienvault_version.return_value = (True, ('ossim',))

        self.assertEqual((True, False), system_is_professional(system_id=self.system_id))
        self.mock_get_system_ip_from_system_id.assert_called_once_with(self.system_id)
        mock_get_alienvault_version.assert_called_with(self.system_ip)

    @patch('apimethods.system.system.system_is_trial')
    @patch('apimethods.system.system.system_is_professional', return_value=(False, ''))
    def test0030(self, mock_is_professional, mock_is_trial):
        """ get_system_tags: system_is_professional failed """
        self.assertEqual((False, []), get_system_tags())
        mock_is_professional.assert_called_once_with(system_id='local')
        mock_is_trial.assert_not_called()

    @patch('apimethods.system.system.system_is_trial', return_value=(False, False))
    @patch('apimethods.system.system.system_is_professional', return_value=(True, False))
    def test0031(self, mock_is_professional, mock_is_trial):
        """ get_system_tags: system_is_trial failed """
        self.assertEqual((False, []), get_system_tags(system_id=self.system_id))
        mock_is_professional.assert_called_once_with(system_id=self.system_id)
        mock_is_trial.assert_called_once_with(system_id=self.system_id)

    @patch('apimethods.system.system.apimethod_is_otx_enabled', return_value=False)
    @patch('apimethods.system.system.system_is_trial', return_value=(True, False))
    @patch('apimethods.system.system.system_is_professional', return_value=(True, False))
    def test0032(self, mock_is_professional, mock_is_trial, mock_otx_enabled):
        """ get_system_tags: Positive case #1 not trail ossim without otx"""
        self.assertEqual(['OSSIM', 'NO-TRIAL', 'NO-OTX'], get_system_tags())
        mock_is_professional.assert_called_once_with(system_id='local')
        mock_is_trial.assert_called_once_with(system_id='local')
        mock_otx_enabled.assert_called_once_with()

    @patch('apimethods.system.system.apimethod_is_otx_enabled', return_value=True)
    @patch('apimethods.system.system.system_is_trial', return_value=(True, True))
    @patch('apimethods.system.system.system_is_professional', return_value=(True, True))
    def test0033(self, mock_is_professional, mock_is_trial, mock_otx_enabled):
        """ get_system_tags: Positive case #1 trial usm with otx"""
        self.assertEqual(['USM', 'TRIAL', 'OTX'], get_system_tags())
        mock_is_professional.assert_called_once_with(system_id='local')
        mock_is_trial.assert_called_once_with(system_id='local')
        mock_otx_enabled.assert_called_once_with()

    @patch('apimethods.system.system.get_running_tasks')
    def test0034(self, mock_get_running_tasks):
        """ get_jobs_running: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(get_jobs_running())
        mock_get_running_tasks.assert_not_called()

    @patch('apimethods.system.system.get_running_tasks', return_value=(True, {}))
    def test0035(self, mock_get_running_tasks):
        """ get_jobs_running: No running tasks """
        self.assertEqual((True, list()), get_jobs_running())
        mock_get_running_tasks.assert_called_once_with(self.system_ip)

    @patch('apimethods.system.system.get_running_tasks')
    def test0036(self, mock_get_running_tasks):
        """ get_jobs_running: Positive case which should return job list with allowed tasks"""
        time_start = 12345
        allowed_tasks = (
            ('update', 'celerymethods.jobs.system.alienvault_asynchronous_update'),
            ('reconfigure', 'celerymethods.jobs.system.alienvault_asynchronous_reconfigure'),
            ('reconfigure', 'celerymethods.jobs.reconfig.alienvault_reconfigure'),
            ('get_configuration_backup', 'celerymethods.tasks.backup_tasks.get_backup_file'),
            ('configuration_backup', 'celerymethods.tasks.backup_tasks.backup_configuration_for_system_id')
        )

        for task_type_name, task_name in allowed_tasks:
            running_tasks = {
                'TEST AIO NODE': [{'id': 'test_job_id',
                                   'name': task_name,
                                   'system_id': 'local',
                                   'time_start': str(time_start),
                                   'args': str(['local']),
                                   'kwargs': str({'system_id': self.system_id})
                                   }]
            }
            expected_job = {'job_id': 'test_job_id', 'name': task_type_name, 'time_start': time_start}
            mock_get_running_tasks.return_value = (True, running_tasks)

            self.assertEqual((True, [expected_job]), get_jobs_running())
            self.mock_get_system_ip_from_system_id.assert_called_with('local')
            mock_get_running_tasks.assert_called_with(self.system_ip)

    @patch('apimethods.system.system.system_is_professional', return_value=(False, False))
    def test0037(self, mock_is_pro):
        """ get_license_devices: Default number because failed to get pro version """
        self.assertEqual(1000000, get_license_devices())
        mock_is_pro.assert_called_once_with()

    @patch('os.path.isfile', return_value=False)
    @patch('apimethods.system.system.system_is_professional', return_value=(True, True))
    def test0038(self, _, mock_isfile):
        """ get_license_devices: Should be 0 devices because failed to find lic file """
        self.assertEqual(0, get_license_devices())
        mock_isfile.assert_called_once_with('/etc/ossim/ossim.lic')
        self.mock_api_log.debug.assert_called_once_with("License devices can't be determined: License file not found")

    @patch('apimethods.system.system.RawConfigParser')
    @patch('os.path.isfile', return_value=True)
    @patch('apimethods.system.system.system_is_professional', return_value=(True, True))
    def test0039(self, _, mock_isfile, mock_raw_parser):
        """ get_license_devices: Default number because failed to read lic file """
        lic_file = '/etc/ossim/ossim.lic'
        mock_raw_parser.return_value.getint.side_effect = NoOptionError(1, 'my msg')

        self.assertEqual(1000000, get_license_devices())
        mock_isfile.assert_called_once_with(lic_file)
        mock_raw_parser.return_value.read.assert_called_once_with(lic_file)
        mock_raw_parser.return_value.getint.assert_called_once_with('appliance', 'devices')

    @patch('apimethods.system.system.RawConfigParser')
    @patch('os.path.isfile', return_value=True)
    @patch('apimethods.system.system.system_is_professional', return_value=(True, True))
    def test0040(self, _, mock_isfile, mock_raw_parser):
        """ get_license_devices: Default number because failed to read lic file """
        mock_raw_parser.return_value.getint.return_value = 25

        self.assertEqual(25, get_license_devices())
        mock_isfile.assert_called_once_with('/etc/ossim/ossim.lic')
        self.assertTrue(mock_raw_parser.return_value.read.called)
        mock_raw_parser.return_value.getint.assert_called_once_with('appliance', 'devices')

    @patch('apimethods.system.system.ansible_get_child_alarms')
    def test0041(self, mock_ans_get_child_alarms):
        """ get_child_alarms: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(get_child_alarms(self.system_id))
        mock_ans_get_child_alarms.assert_not_called()

    @patch('apimethods.system.system.ansible_get_child_alarms')
    def test0042(self, mock_ans_get_child_alarms):
        """ get_child_alarms: Exception raises while getting alarms """
        err_msg = 'Test error: failed to connect'
        mock_ans_get_child_alarms.side_effect = IOError(err_msg)

        self.assertEqual(
            (False, '[apimethod_get_child_alarms] An error occurred while retrieving the child alarms <%s>' % err_msg),
            get_child_alarms(self.system_id))
        mock_ans_get_child_alarms.assert_called_once_with(self.system_ip, 1, 3)

    @patch('apimethods.system.system.ansible_get_child_alarms')
    def test0043(self, mock_ans_get_child_alarms):
        """ get_child_alarms: Positive case """
        mock_ans_get_child_alarms.return_value = (True, ['some_alarms_data_here'])

        self.assertEqual(mock_ans_get_child_alarms.return_value, get_child_alarms(self.system_id, delta=5))
        mock_ans_get_child_alarms.assert_called_once_with(self.system_ip, 1, 5)

    @patch('apimethods.system.system.ansible_resend_alarms')
    def test0044(self, mock_ans_resend_alarms):
        """ resend_alarms: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(resend_alarms(self.system_id, [1, 2, 3]))
        mock_ans_resend_alarms.assert_not_called()

    @patch('apimethods.system.system.ansible_resend_alarms')
    def test0045(self, mock_ans_resend_alarms):
        """ resend_alarms: System ID provided is not an uuid """
        self.assertEqual(
            (False, '[apimethod_resend_alarms] An error occurred while retrieving the child alarms '
                    '<badly formed hexadecimal UUID string>'),
            resend_alarms('i_am_not_uuid', []))
        mock_ans_resend_alarms.assert_not_called()

    @patch('apimethods.system.system.ansible_resend_alarms')
    def test0046(self, mock_ans_resend_alarms):
        """ resend_alarms: Positive case """
        fake_alarms_list = [1, 2, 3]
        mock_ans_resend_alarms.return_value = (True, '')
        self._check_ansible_response(resend_alarms(self.system_id, fake_alarms_list))
        self.mock_get_system_ip_from_system_id.assert_called_once_with(self.system_id)
        mock_ans_resend_alarms.assert_called_once_with(self.system_ip, fake_alarms_list)

    @patch('apimethods.system.system.get_base_path_from_system_id')
    @patch('apimethods.system.system.create_local_directory')
    def test0047(self, mock_create_local_dir, _):
        """ create_directory_for_ossec_remote: Failed to create local dir """
        err_msg = 'Test error: failed to create dir'
        mock_create_local_dir.return_value = (False, err_msg)

        self.assertEqual((False, err_msg), create_directory_for_ossec_remote(self.system_id))
        self.assertTrue(mock_create_local_dir.called)

    @patch('apimethods.system.system.get_base_path_from_system_id')
    @patch('apimethods.system.system.create_local_directory')
    def test0048(self, mock_create_local_dir, mock_get_base_path_from_id):
        """ create_directory_for_ossec_remote: Positive case """
        base_id_path = '/var/alienvault/%s' % self.system_id
        dir_to_create = base_id_path + '/ossec/'
        mock_get_base_path_from_id.return_value = base_id_path
        mock_create_local_dir.return_value = (True, 'test data')

        self.assertEqual((True, ""), create_directory_for_ossec_remote(self.system_id))
        mock_create_local_dir.assert_called_once_with(dir_to_create)

    @patch('apimethods.system.system.get_system_setup_data')
    def test0049(self, mock_get_sys_setup):
        """ get: Failed to get system IP """
        self._get_system_ip_from_id_failed_with_error()
        self._check_result_after_failed_system_ip(get(self.system_id, no_cache=True), expected_data='Not found')
        mock_get_sys_setup.assert_not_called()

    @patch('apimethods.system.system.get_system_setup_data')
    def test0050(self, mock_get_sys_setup):
        """ get: Positive case """
        mock_get_sys_setup.return_value = (True, 'system_data_here')

        self.assertEqual(mock_get_sys_setup.return_value, get(self.system_id, no_cache=True))
        mock_get_sys_setup.assert_called_once_with(self.system_ip)
