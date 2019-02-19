import unittest

from mock import MagicMock

from bounded_contexts.central_console.domain_services.abstract_server_repository import AbstractServerRepository
from bounded_contexts.central_console.models.server import Server


class ConcreteServerRepository(AbstractServerRepository):

    def get_server(self):
        pass


class TestAbstractServerRepository(unittest.TestCase):

    def test_abstract(self):
        self.assertRaises(TypeError, AbstractServerRepository, MagicMock(spec=Server))

    def test_concrete(self):
        ConcreteServerRepository(MagicMock(spec=Server))


if __name__ == '__main__':
    unittest.main()
