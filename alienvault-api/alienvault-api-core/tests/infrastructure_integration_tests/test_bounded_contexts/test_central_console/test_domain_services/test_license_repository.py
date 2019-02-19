import unittest
from datetime import datetime

from bounded_contexts.central_console.domain_services import license_repository
from bounded_contexts.central_console.models.license import License
from infrastructure.shared_kernel.config.domain_services.abstract_config_repository import alchemy_config_repository
from integration_tests.alchemy_test_case import AlchemyTestCase
from shared_kernel.config.models.config import Config


class TestLicenseRepository(AlchemyTestCase):

    def setUp(self):
        self.assert_parameters()
        self.set_up_db()
        self.set_up_db_schema()
        self.set_up_alchemy(alchemy_config_repository)

        self.conf_repo = alchemy_config_repository.AlchemyConfigRepository(Config)
        self.repo = license_repository.LicenseRepository(License, self.conf_repo)

    def set_up_db_schema(self):
        self.cursor.execute(
            """
          CREATE TABLE `config` (
          `conf` varchar(255) NOT NULL,
          `value` text CHARACTER SET latin1 COLLATE latin1_general_ci,
          PRIMARY KEY (`conf`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            """
        )

        expires_on = '9999-12-31'
        self.expected_expires_on = int((
            datetime.strptime(expires_on, '%Y-%m-%d') - datetime.strptime('1970-01-01', '%Y-%m-%d')
        ).total_seconds())
        self.expected_is_trial = 'False'
        self.expected_devices = 10
        self.cursor.execute("""
                INSERT INTO config (conf, value)
                VALUES (%s, %s)
                """, (
            license_repository.CONFIG_LICENSE_NAME,
            """| [sign]
                sign=signvalue
                [appliance]
                key=keytext
                system_id=564d453a-4b3d-eff6-bdb3-70779f489a09
                expire={}
                devices={}
            """.format(expires_on, self.expected_devices)
        ))

        self.db.commit()

    def test_something(self):
        actual_license = self.repo.get_license()
        self.assertEqual(actual_license.is_trial, self.expected_is_trial)
        self.assertEqual(actual_license.expires_on, self.expected_expires_on)
        self.assertEqual(actual_license.devices, self.expected_devices)


if __name__ == '__main__':
    unittest.main()
