import unittest

import mock
from ansiblemethods import ansiblemanager as ans_mng


class TestAnsible(unittest.TestCase):
    """
        Test Ansible Manager functions
    """

    def setUp(self):
        self.ansible = ans_mng.Ansible()

        # Common test data
        self.test_positive_response = {'status': 'ok', 'data': 'positive result'}
        self.test_module = 'test_module'
        self.test_playbook = 'test_playbook'
        self.test_args = '1 2 3'
        self.host_list = ['192.168.23.12', '192.168.67.99']
        self.timeout = 5
        self.remote_user = 'test_user'
        self.remote_pass = 'test_pass'
        self.with_sudo_false = False

        # Mocks
        patcher1 = mock.patch('ansible.runner.Runner')
        self.addCleanup(patcher1.stop)
        self.mock_runner = patcher1.start()
        self.mock_runner.return_value.run.return_value = self.test_positive_response

        patcher2 = mock.patch('ansible.playbook.PlayBook')
        self.addCleanup(patcher2.stop)
        self.mock_playbook = patcher2.start()
        self.mock_playbook.return_value.run.return_value = self.test_positive_response

    def test_run_module_1(self):
        """ Test run_module: executed with default parameters """
        result = self.ansible.run_module([], self.test_module, self.test_args)

        self.assertEqual(self.test_positive_response, result)
        self.mock_runner.assert_called_once_with(
            host_list=ans_mng.AnsibleConstants.DEFAULT_HOST_LIST,
            module_name=self.test_module,
            module_args=self.test_args,
            transport=ans_mng.AnsibleConstants.DEFAULT_TRANSPORT,
            remote_user=ans_mng.AnsibleConstants.DEFAULT_REMOTE_USER,
            remote_pass=ans_mng.AnsibleConstants.DEFAULT_REMOTE_PASS,
            sudo=True,
            timeout=ans_mng.AnsibleConstants.DEFAULT_TIMEOUT
        )
        self.mock_runner.return_value.run.assert_called_once_with()

    def test_run_module_2(self):
        """ Test run_module: run with local=True or host_list=[127.0.0.1] should be the same"""
        call_cases = ((['192.168.11.22', '192.168.11.33'], True), (['127.0.0.1'], False))

        for host_list_value, local_value in call_cases:
            result = self.ansible.run_module(host_list_value, self.test_module, self.test_args, local=local_value)

            self.assertEqual(self.test_positive_response, result)
            _, call_kwargs = self.mock_runner.call_args
            self.assertEqual(call_kwargs.get('host_list'), ['127.0.0.1'], msg='local host expected here')
            self.assertEqual(call_kwargs.get('transport'), 'local', msg='transport should be local here')
            self.mock_runner.return_value.run.assert_called_once_with()
            self.mock_runner.reset_mock()

    def test_run_module_3(self):
        """ Test run_module: executed with custom parameters """
        result = self.ansible.run_module(self.host_list, self.test_module, self.test_args,
                                         timeout=self.timeout,
                                         ans_remote_user=self.remote_user,
                                         ans_remote_pass=self.remote_pass,
                                         use_sudo=self.with_sudo_false)

        self.assertEqual(self.test_positive_response, result)
        self.mock_runner.assert_called_once_with(
            host_list=self.host_list,
            module_name=self.test_module,
            module_args=self.test_args,
            transport=ans_mng.AnsibleConstants.DEFAULT_TRANSPORT,
            remote_user=self.remote_user,
            remote_pass=self.remote_pass,
            sudo=self.with_sudo_false,
            timeout=self.timeout
        )
        self.mock_runner.return_value.run.assert_called_once_with()

    @mock.patch('ansible.callbacks.AggregateStats')
    def test_run_playbook_1(self, mock_aggregate_stats):
        """ Test run_playbook: executed with default parameters """
        result = self.ansible.run_playbook(self.test_playbook)

        self.assertEqual(self.test_positive_response, result)
        self.mock_playbook.assert_called_once_with(playbook=self.test_playbook,
                                                   host_list=None,
                                                   stats=mock_aggregate_stats.return_value,
                                                   callbacks=self.ansible.callbacks,
                                                   runner_callbacks=self.ansible.callbacks,
                                                   transport=ans_mng.AnsibleConstants.DEFAULT_TRANSPORT,
                                                   sudo=True,
                                                   extra_vars={},
                                                   remote_user=ans_mng.AnsibleConstants.DEFAULT_REMOTE_USER,
                                                   remote_pass=ans_mng.AnsibleConstants.DEFAULT_REMOTE_PASS,
                                                   only_tags=None,
                                                   skip_tags=None)
        self.mock_playbook.return_value.SETUP_CACHE.clear.assert_called_once_with()
        self.mock_playbook.return_value.run.assert_called_once_with()

    def test_run_playbook_2(self):
        """ Test run_module: run with local=True """
        result = self.ansible.run_playbook(self.test_playbook, local=True)

        self.assertEqual(self.test_positive_response, result)
        _, call_kwargs = self.mock_playbook.call_args
        self.assertEqual(call_kwargs.get('host_list'), ['127.0.0.1'], msg='local host expected here')
        self.assertEqual(call_kwargs.get('transport'), 'local', msg='transport should be local here')

    @mock.patch('ansible.callbacks.AggregateStats')
    def test_run_playbook_3(self, mock_aggregate_stats):
        """ Test run_playbook: executed with custom parameters """
        test_extra_vars = {'check': 'it'}
        include_task_list = ['task1', 'task2']
        skip_task_list = ['task_to_skip']
        result = self.ansible.run_playbook(self.test_playbook,
                                           host_list=self.host_list,
                                           use_sudo=self.with_sudo_false,
                                           extra_vars=test_extra_vars,
                                           ans_remote_user=self.remote_user,
                                           ans_remote_pass=self.remote_pass,
                                           only_tags=include_task_list,
                                           skip_tags=skip_task_list)

        self.assertEqual(self.test_positive_response, result)
        self.mock_playbook.assert_called_once_with(playbook=self.test_playbook,
                                                   host_list=self.host_list,
                                                   stats=mock_aggregate_stats.return_value,
                                                   callbacks=self.ansible.callbacks,
                                                   runner_callbacks=self.ansible.callbacks,
                                                   transport=ans_mng.AnsibleConstants.DEFAULT_TRANSPORT,
                                                   sudo=self.with_sudo_false,
                                                   extra_vars=test_extra_vars,
                                                   remote_user=self.remote_user,
                                                   remote_pass=self.remote_pass,
                                                   only_tags=include_task_list,
                                                   skip_tags=skip_task_list)
        self.mock_playbook.return_value.SETUP_CACHE.clear.assert_called_once_with()
        self.mock_playbook.return_value.run.assert_called_once_with()
