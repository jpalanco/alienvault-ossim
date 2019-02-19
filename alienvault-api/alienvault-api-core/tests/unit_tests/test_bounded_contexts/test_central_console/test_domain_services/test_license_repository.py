import unittest

from datetime import datetime
from mock import MagicMock, patch

from bounded_contexts.central_console.domain_services import license_repository
from bounded_contexts.central_console.models.license import License
from shared_kernel.config.models.config import Config
from shared_kernel.config.domain_services.abstract_config_repository import AbstractConfigRepository


class TestAbstractLicenseRepository(unittest.TestCase):
    def setUp(self):
        self.config_repository = MagicMock(spec=AbstractConfigRepository)
        self.license_constructor = License
        self.repo = license_repository.LicenseRepository(self.license_constructor, self.config_repository)
        expires_on = '9999-12-31'
        self.expected_expires_on = int((
            datetime.strptime(expires_on, '%Y-%m-%d') - datetime.strptime('1970-01-01', '%Y-%m-%d')
        ).total_seconds())
        self.expected_devices = 10

        self.expected_license_info = """| [sign]
            sign=signvalue
            [appliance]
            key=keytext
            system_id=564d453a-4b3d-eff6-bdb3-70779f489a09
            expire={}
            devices={}
        """.format(expires_on, self.expected_devices)
        self.expected_trial_license_info = self.expected_license_info + '\nemail=example@mail.com'

        self.expected_license_info_no_expiration = """| [sign]
            sign=signvalue
            [appliance]
            key=keytext
            system_id=564d453a-4b3d-eff6-bdb3-70779f489a09
            devices={}
        """.format(self.expected_devices)

        self.expected_license_info_no_devices = """| [sign]
            sign=signvalue
            [appliance]
            key=keytext
            system_id=564d453a-4b3d-eff6-bdb3-70779f489a09
            expire={}
        """.format(expires_on)

    def test_get_license(self):
        self.config_repository.get_config.return_value = Config(
            license_repository.CONFIG_LICENSE_NAME,
            self.expected_license_info
        )

        self.repo.get_license()

        self.config_repository.get_config.assert_called_once_with(license_repository.CONFIG_LICENSE_NAME)

    def test_get_license_is_trial(self):
        expected_is_trial = license_repository.LICENSE_IS_TRIAL
        self.config_repository.get_config.return_value = Config(
            license_repository.CONFIG_LICENSE_NAME,
            self.expected_trial_license_info
        )

        actual_license = self.repo.get_license()

        self.assertEqual(actual_license.is_trial, expected_is_trial)

    def test_get_license_is_not_trial(self):
        expected_is_trial = license_repository.LICENSE_IS_NOT_TRIAL
        self.config_repository.get_config.return_value = Config(
            license_repository.CONFIG_LICENSE_NAME,
            self.expected_license_info
        )

        actual_license = self.repo.get_license()

        self.assertEqual(actual_license.is_trial, expected_is_trial)

    def test_get_license_expires_on(self):
        self.config_repository.get_config.return_value = Config(
            license_repository.CONFIG_LICENSE_NAME,
            self.expected_license_info
        )

        actual_license = self.repo.get_license()

        self.assertEqual(actual_license.expires_on, self.expected_expires_on)

    @patch.object(license_repository, 'api_log', autospec=True)
    def test_get_license_expires_on_exception(self, api_log_mock):
        self.config_repository.get_config.return_value = Config(
            license_repository.CONFIG_LICENSE_NAME,
            self.expected_license_info_no_expiration
        )

        actual_licesne = self.repo.get_license()

        api_log_mock.info.assert_called_once()
        self.assertEqual(actual_licesne.is_trial, license_repository.LICENSE_IS_NOT_TRIAL)
        self.assertIsNone(actual_licesne.expires_on)
        self.assertEqual(actual_licesne.devices, self.expected_devices)

    def test_get_license_devices(self):
        self.config_repository.get_config.return_value = Config(
            license_repository.CONFIG_LICENSE_NAME,
            self.expected_license_info
        )

        actual_license = self.repo.get_license()

        self.assertEqual(actual_license.devices, self.expected_devices)

    @patch.object(license_repository, 'api_log', autospec=True)
    def test_get_license_devices_exception(self, api_log_mock):
        self.config_repository.get_config.return_value = Config(
            license_repository.CONFIG_LICENSE_NAME,
            self.expected_license_info_no_devices
        )

        actual_license = self.repo.get_license()

        api_log_mock.info.assert_called_once()
        self.assertEqual(actual_license.is_trial, license_repository.LICENSE_IS_NOT_TRIAL)
        self.assertEqual(actual_license.expires_on, self.expected_expires_on)
        self.assertIsNone(actual_license.devices)


if __name__ == '__main__':
    unittest.main()
