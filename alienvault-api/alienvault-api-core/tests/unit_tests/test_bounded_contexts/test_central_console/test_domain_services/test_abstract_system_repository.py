import unittest

from bounded_contexts.central_console.domain_services.abstract_system_repository import AbstractSystemRepository
from mock import MagicMock

from bounded_contexts.central_console.models.system import System


class ConcreteSystemRepository(AbstractSystemRepository):

    def get_system(self):
        pass


class TestAbstractSystemRepository(unittest.TestCase):

    def test_abstract(self):
        self.assertRaises(TypeError, AbstractSystemRepository, MagicMock(spec=System))

    def test_concrete(self):
        ConcreteSystemRepository(MagicMock(spec=System))


if __name__ == '__main__':
    unittest.main()
