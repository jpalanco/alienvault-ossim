import unittest

from mock import MagicMock

from shared_kernel.config.models.config import Config
from shared_kernel.config.domain_services.abstract_config_repository import AbstractConfigRepository


class ConcreteConfigRepository(AbstractConfigRepository):

    def get_config(self, conf_name):
        pass

    def add_config(self, conf):
        pass

    def delete_config(self, conf):
        pass


class TestAbstractConfigRepository(unittest.TestCase):

    def test_abstract(self):
        self.assertRaises(TypeError, AbstractConfigRepository)

    def test_concrete(self):
        expected_config_constructor = MagicMock(spec=Config)
        repo = ConcreteConfigRepository(expected_config_constructor)
        self.assertEqual(repo.config_constructor, expected_config_constructor)


if __name__ == '__main__':
    unittest.main()
