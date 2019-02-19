import unittest

from mock import MagicMock

from bounded_contexts.central_console.domain_services.abstract_platform_repository import AbstractPlatformRepository
from bounded_contexts.central_console.models.platform import Platform
from shared_kernel.config.domain_services.abstract_config_repository import AbstractConfigRepository


class ConcretePlatformRepository(AbstractPlatformRepository):

    def get_platform(self, ip):
        pass


class TestAbstractPlatformRepository(unittest.TestCase):

    def test_abstract(self):
        self.assertRaises(
            TypeError,
            AbstractPlatformRepository,
            MagicMock(spec=Platform),
            MagicMock(spec=AbstractConfigRepository)
        )

    def test_concrete(self):
        ConcretePlatformRepository(MagicMock(spec=Platform), MagicMock(spec=AbstractConfigRepository))


if __name__ == '__main__':
    unittest.main()
