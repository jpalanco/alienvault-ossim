from mock import MagicMock, patch, call

import unittest

from bounded_contexts.central_console.models.platform import Platform
from infrastructure.bounded_contexts.central_console.domain_services.abstract_platform_repository \
    import ansible_platform_repository
from shared_kernel.config.domain_services.abstract_config_repository import AbstractConfigRepository
from unit_tests.base_test_case import BaseTestCase


class TestAnsiblePlatformRepository(BaseTestCase):
    def setUp(self):
        self.platform_constructor = Platform
        self.config_repository = MagicMock(spec=AbstractConfigRepository)
        self.repo = ansible_platform_repository.AnsiblePlatformRepository(
            self.platform_constructor,
            self.config_repository
        )

    @patch.object(ansible_platform_repository, 'get_local_ami_public_ip', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_appliance_type', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_platform', autospec=True)
    @patch.object(ansible_platform_repository, 'get_threat_intelligence_version', autospec=True)
    def test_get_platform(self, get_version_mock, get_platform_mock, get_appliance_type_mock, get_public_ip_mock):
        query_ip = MagicMock(name='query_ip')
        expected_platform_name = MagicMock(name='platform')
        expected_version = MagicMock(name='threat_intelligence_version')
        expected_appliance_type = MagicMock(name='appliance_type')
        expected_public_ip = MagicMock(name='public_ip')
        get_platform_mock.return_value = (True, expected_platform_name)
        get_version_mock.return_value = (True, expected_version)
        get_appliance_type_mock.return_value = (True, expected_appliance_type)
        get_public_ip_mock.return_value = (True, expected_public_ip)

        actual_platform = self.repo.get_platform(query_ip)

        get_platform_mock.assert_called_once_with(query_ip)
        get_version_mock.assert_called_once_with(query_ip)
        get_appliance_type_mock.assert_called_once_with(query_ip)
        self.assertEqual(actual_platform.name, expected_platform_name)
        self.assertEqual(actual_platform.threat_intelligence_version, expected_version)
        self.assertEqual(actual_platform.appliance_type, expected_appliance_type)
        self.assertEqual(actual_platform.public_ip, expected_public_ip)

    @patch.object(ansible_platform_repository, 'get_local_ami_public_ip', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_appliance_type', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_platform', autospec=True)
    @patch.object(ansible_platform_repository, 'get_threat_intelligence_version', autospec=True)
    def test_get_platform_name_failed(
            self,
            get_version_mock,
            get_platform_mock,
            get_appliance_type_mock,
            get_public_ip_mock
    ):
        expected_version = MagicMock(name='version')
        expected_appliance_type = MagicMock(name='appliance_type')
        expected_public_ip = MagicMock(name='public_ip')
        get_version_mock.return_value = (True, expected_version)
        get_platform_mock.return_value = (False, 'error message')
        get_appliance_type_mock.return_value = (True, expected_appliance_type)
        get_public_ip_mock.return_value = (True, expected_public_ip)

        actual_platform = self.repo.get_platform(MagicMock(name='query_ip'))

        self.assertIsNone(actual_platform)

    @patch.object(ansible_platform_repository, 'get_local_ami_public_ip', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_appliance_type', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_platform', autospec=True)
    @patch.object(ansible_platform_repository, 'get_threat_intelligence_version', autospec=True)
    def test_get_intelligence_version_failed(
            self,
            get_version_mock,
            get_platform_mock,
            get_appliance_type_mock,
            get_public_ip_mock
    ):
        expected_public_ip = MagicMock(name='public_ip')
        expected_appliance_type = MagicMock(name='appliance_type')
        expected_platform_name = MagicMock(name='platform_name')
        get_version_mock.return_value = (False, 'error message')
        get_platform_mock.return_value = (True, expected_platform_name)
        get_appliance_type_mock.return_value = (True, expected_appliance_type)
        get_public_ip_mock.return_value = (True, expected_public_ip)

        actual_platform = self.repo.get_platform(MagicMock(name='query_ip'))

        self.assertIsNone(actual_platform)

    @patch.object(ansible_platform_repository, 'get_local_ami_public_ip', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_appliance_type', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_platform', autospec=True)
    @patch.object(ansible_platform_repository, 'get_threat_intelligence_version', autospec=True)
    def test_get_appliance_type_failed(
            self,
            get_version_mock,
            get_platform_mock,
            get_appliance_type_mock,
            get_public_ip_mock
    ):
        expected_intelligence_version = MagicMock(name='intelligence_version')
        expected_platform_name = MagicMock(name='platform_name')
        expected_public_ip = MagicMock(name='public_ip')
        get_version_mock.return_value = (True, expected_intelligence_version)
        get_platform_mock.return_value = (True, expected_platform_name)
        get_appliance_type_mock.return_value = (False, 'error message')
        get_public_ip_mock.return_value = (True, expected_public_ip)

        actual_platform = self.repo.get_platform(MagicMock(name='query_ip'))

        self.assertIsNone(actual_platform)

    @patch.object(ansible_platform_repository, 'get_local_ami_public_ip', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_appliance_type', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_platform', autospec=True)
    @patch.object(ansible_platform_repository, 'get_threat_intelligence_version', autospec=True)
    def test_get_public_ip_failed(
            self,
            get_version_mock,
            get_platform_mock,
            get_appliance_type_mock,
            get_public_ip_mock
    ):
        expected_intelligence_version = MagicMock(name='intelligence_version')
        expected_platform_name = MagicMock(name='platform_name')
        expected_appliance_type = MagicMock(name='appliance_type')
        get_version_mock.return_value = (True, expected_intelligence_version)
        get_platform_mock.return_value = (True, expected_platform_name)
        get_appliance_type_mock.return_value = (True, expected_appliance_type)
        get_public_ip_mock.return_value = (False, 'error message')

        actual_platform = self.repo.get_platform(MagicMock(name='query_ip'))

        self.assertEqual(actual_platform.name, expected_platform_name)
        self.assertEqual(actual_platform.threat_intelligence_version, expected_intelligence_version)
        self.assertEqual(actual_platform.appliance_type, expected_appliance_type)
        self.assertIsNone(actual_platform.public_ip)


if __name__ == '__main__':
    unittest.main()
