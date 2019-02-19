import mock
import unittest
from passlib.hash import lmhash, nthash

from ansiblemethods.ansiblemanager import PLAYBOOKS
from ansiblemethods.sensor.ossec import (
    ossec_win_deploy,
    get_ossec_rule_filenames,
    ossec_extract_agent_key,
    ossec_add_new_agent,
    ossec_get_syscheck,
    is_valid_agent_id,
    make_err_message
)


class TestOssecHIDS(unittest.TestCase):
    """ Class to test OSSEC-HIDS related functions executed by Ansible.
    """

    def setUp(self):
        self.win_username = 'test'
        self.win_password = 'test_pass'
        self.system_ip = '10.11.12.15'
        self.sensor_ip = '10.11.12.14'
        self.win_ip = '10.11.12.13'
        self.win_domain = ''
        self.agent_name = 'Host-{}'.format(self.win_ip)
        self.agent_id = '001'

        self.extra_vars = {
            "target": "{}".format(self.sensor_ip),
            "agent_name": "{}".format(self.agent_name),
            "windows_ip": "{}".format(self.win_ip),
            "windows_domain": "{}".format(self.win_domain),
            "windows_username": "{}".format(self.win_username),
            "windows_password": "{}".format(self.win_password),
            "auth_str": "{}".format(self.win_username),
            "hashes": "%s:%s" % (lmhash.hash(self.win_password), nthash.hash(self.win_password))
        }

    @mock.patch('ansiblemethods.sensor.ossec.os')
    @mock.patch('ansiblemethods.sensor.ossec._ansible')
    @mock.patch('ansiblemethods.sensor.ossec.NamedTemporaryFile')
    def test_ossec_win_deploy_1(self, named_tuple_mock, ans_mock, os_mock):
        """ Tests `ossec_win_deploy` without domain.
        """
        self.extra_vars.update({
            'auth_file_samba': str(named_tuple_mock.return_value.name),
            'agent_config_file': str(named_tuple_mock.return_value.name),
            'agent_key_file': str(named_tuple_mock.return_value.name),
        })

        expected_result = {self.sensor_ip: {}, self.win_ip: {'ok': 0}}
        ans_mock.run_playbook.return_value = expected_result

        # Check results
        self.assertEqual(expected_result,
                         ossec_win_deploy(self.sensor_ip, self.agent_name, self.win_ip, self.win_username,
                                          self.win_domain, self.win_password))
        named_tuple_mock.assert_called()
        ans_mock.run_playbook.assert_called_once_with(playbook=PLAYBOOKS['OSSEC_WIN_DEPLOY'],
                                                      host_list=[self.sensor_ip],
                                                      extra_vars=self.extra_vars)
        os_mock.remove.assert_called()

    @mock.patch('ansiblemethods.sensor.ossec.os')
    @mock.patch('ansiblemethods.sensor.ossec._ansible')
    @mock.patch('ansiblemethods.sensor.ossec.NamedTemporaryFile')
    def test_ossec_win_deploy_2(self, named_tuple_mock, ans_mock, os_mock):
        """ Tests `ossec_win_deploy` with domain name.
        """
        domain = 'test_domain'
        self.extra_vars.update({
            'auth_file_samba': str(named_tuple_mock.return_value.name),
            'agent_config_file': str(named_tuple_mock.return_value.name),
            'agent_key_file': str(named_tuple_mock.return_value.name),
            'windows_domain': domain,
            'auth_str': "{}/{}".format(domain, self.win_username)
        })

        expected_result = {self.sensor_ip: {}, self.win_ip: {'ok': 0}}
        ans_mock.run_playbook.return_value = expected_result

        # Check results
        self.assertEqual(expected_result,
                         ossec_win_deploy(self.sensor_ip, self.agent_name, self.win_ip, self.win_username,
                                          domain, self.win_password))
        named_tuple_mock.assert_called()
        ans_mock.run_playbook.assert_called_once_with(playbook=PLAYBOOKS['OSSEC_WIN_DEPLOY'],
                                                      host_list=[self.sensor_ip],
                                                      extra_vars=self.extra_vars)
        os_mock.remove.assert_called()

    @mock.patch('ansiblemethods.sensor.ossec._ansible')
    def test_get_ossec_rule_filenames_1(self, ans_mock):
        """ Tests `get_ossec_rule_filenames`: should pass.
        """
        expected_list = ['rule1.xml', 'rule2.xml']
        ans_response = {
            'dark': '',
            'contacted': {
                self.sensor_ip: {
                    'stdout': '\n'.join(expected_list)}
                }
        }
        ans_mock.run_module.return_value = ans_response
        status, file_list = get_ossec_rule_filenames(self.sensor_ip)

        self.assertEqual((True, expected_list), (status, file_list))
        ans_mock.run_module.assert_called_once()

    @mock.patch('ansiblemethods.sensor.ossec._ansible')
    def test_get_ossec_rule_filenames_2(self, ans_mock):
        """ Tests `get_ossec_rule_filenames`: should fail if response == dark or unreachable.
        """
        ans_mock.run_module.return_value = {'dark': self.sensor_ip}
        self.assertFalse(get_ossec_rule_filenames(self.sensor_ip)[0])
        ans_mock.run_module.assert_called_once()

        ans_mock.run_module.return_value = {'unreachable': True}
        self.assertFalse(get_ossec_rule_filenames(self.sensor_ip)[0])  # status is false

    @mock.patch('ansiblemethods.sensor.ossec._ansible')
    def test_get_ossec_rule_filenames_3(self, ans_mock):
        """ Tests `get_ossec_rule_filenames`: exception raised.
        """
        err_msg = 'test err'
        ans_mock.run_module.side_effect = IOError(err_msg)
        status, result = get_ossec_rule_filenames(self.sensor_ip)

        self.assertEqual((False, err_msg), (status, result))

    @mock.patch('ansiblemethods.sensor.ossec._ansible')
    def test_ossec_extract_agent_key_1(self, ans_mock):
        """ Tests `ossec_extract_agent_key`: should pass.
        """
        ans_mock.run_module.return_value = {
            'dark': '',
            'contacted': {self.sensor_ip: {
                'stdout': 'first\nsecond\nthird_line_with_key'}}
        }
        status, result = ossec_extract_agent_key(self.sensor_ip, '001')

        self.assertEqual((True, 'third_line_with_key'), (status, result))

    @mock.patch('ansiblemethods.sensor.ossec._ansible')
    def test_ossec_extract_agent_key_2(self, ans_mock):
        """ Tests `ossec_extract_agent_key`: exception raised.
        """
        err_msg = 'test err'
        ans_mock.run_module.side_effect = IOError(err_msg)
        status, result = ossec_extract_agent_key(self.sensor_ip, '001')

        self.assertEqual((False, err_msg), (status, result))

    def test_ossec_extract_agent_key_3(self):
        """ Tests `ossec_extract_agent_key`: bad agent id.
        """
        status, result = ossec_extract_agent_key(self.sensor_ip, 'bad_id')
        self.assertEqual((False, 'Invalid agent ID. The agent ID has to be 1-4 digital characters'),
                         (status, result))

    @mock.patch('ansiblemethods.sensor.ossec.ansible_is_valid_response', return_value=(False, 'test_err'))
    @mock.patch('ansiblemethods.sensor.ossec._ansible')
    def test_ossec_get_syscheck_1(self, ans_mock, _):
        """ Tests `ossec_get_syscheck`: should fail if no result.
        """
        ans_mock.run_module.return_value = {}
        self.assertEqual((False, 'test_err'), ossec_get_syscheck(self.system_ip, self.agent_id))

    @mock.patch('ansiblemethods.sensor.ossec.ansible_is_valid_response', return_value=(True, ''))
    @mock.patch('ansiblemethods.sensor.ossec._ansible')
    def test_ossec_get_syscheck_2(self, ans_mock, _):
        """ Tests `ossec_get_syscheck`: should fail if return code != 0.
        """
        ans_response = {'contacted': {self.system_ip: {'rc': 1, 'stdout': 'test'}}}
        expected_err_msg = '[ossec_get_syscheck] Something wrong happened while running ansible command {}'.format(
            (ans_response['contacted'][self.system_ip]))

        ans_mock.run_module.return_value = ans_response
        self.assertEqual((False, expected_err_msg), ossec_get_syscheck(self.system_ip, self.agent_id))

    @mock.patch('ansiblemethods.sensor.ossec.ansible_is_valid_response', return_value=(True, ''))
    @mock.patch('ansiblemethods.sensor.ossec._ansible')
    def test_ossec_get_syscheck_3(self, ans_mock, _):
        """ Tests `ossec_get_syscheck`: positive case.
        """
        ans_response = {'contacted': {self.system_ip: {'rc': 0, 'stdout': 'test\nshould\n\npass\n\n'}}}
        ans_mock.run_module.return_value = ans_response

        self.assertEqual((True, {0: 'test', 1: 'should', 2: 'pass'}), ossec_get_syscheck(self.system_ip, self.agent_id))

    def test_is_valid_agent_id_1(self):
        """ Tests `is_valid_agent_id`: should fail if wrong agent_id.
        """
        self.assertEqual((False, 'Invalid agent ID. The agent ID has to be 1-4 digital characters'),
                         is_valid_agent_id('not_id'), 'not an int')
        self.assertEqual((False, 'Invalid agent ID. The agent ID has to be 1-4 digital characters'),
                         is_valid_agent_id('-1'), 'less than 0')
        self.assertEqual((False, 'Invalid agent ID. The agent ID has to be 1-4 digital characters'),
                         is_valid_agent_id('11111'), 'length is bigger than 4 char')

    def test_is_valid_agent_id_2(self):
        """ Tests `is_valid_agent_id`: positive cases.
        """
        for case in ('0', '1', '22', '012'):
            self.assertEqual((True, ''), is_valid_agent_id(case))

    def test_make_err_message(self):
        """ Test `make_err_message` function.
        """
        self.assertEqual('[test_func] test_err ', make_err_message('[test_func]', 'test_err', ''))
        self.assertEqual('[test_func]  ', make_err_message('[test_func]', '', ''))
        self.assertEqual('[test_func] test_err: raise_raise',
                         make_err_message('[test_func]', 'test_err', 'raise_raise'))
