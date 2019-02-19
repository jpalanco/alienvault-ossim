import unittest

from infrastructure.shared_kernel.config.domain_services.abstract_config_repository import alchemy_config_repository
from integration_tests.alchemy_test_case import AlchemyTestCase
from shared_kernel.config.models.config import Config


class TestAlchemyConfigRepository(AlchemyTestCase):

    def setUp(self):
        self.assert_parameters()
        self.set_up_db()
        self.set_up_db_schema()
        self.set_up_alchemy(alchemy_config_repository)

        self.alchemy_repository = alchemy_config_repository.AlchemyConfigRepository(Config)

    def set_up_db_schema(self):
        self.cursor.execute("""CREATE TABLE `config` (
                       `conf` varchar(255) NOT NULL,
                       `value` text CHARACTER SET latin1 COLLATE latin1_general_ci,
                       PRIMARY KEY (`conf`)
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8"""
                            )

    def test_config_is_added(self):
        expected_config = Config('conf name', 'conf value')

        self.alchemy_repository.add_config(expected_config)
        actual_config = self.alchemy_repository.get_config(expected_config.conf)

        self.assertEqual(actual_config.conf, expected_config.conf)

    def test_config_is_deleted_after_adding(self):
        config_to_delete = Config('conf name', 'conf value')

        self.alchemy_repository.add_config(config_to_delete)
        self.assertEqual(config_to_delete.conf, self.alchemy_repository.get_config(config_to_delete.conf).conf)
        self.assertEqual(config_to_delete.value, self.alchemy_repository.get_config(config_to_delete.conf).value)
        self.alchemy_repository.delete_config(config_to_delete)
        actual_token = self.alchemy_repository.get_config(config_to_delete.conf)

        self.assertTrue(actual_token is None)


if __name__ == '__main__':
    unittest.main()
