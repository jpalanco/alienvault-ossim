import unittest

from mock import MagicMock, patch
from sqlalchemy.orm import Query

from bounded_contexts.central_console.models.server import Server
from infrastructure.bounded_contexts.central_console.domain_services.\
    abstract_server_repository import alchemy_server_repository
from unit_tests.alchemy_test_case import AlchemyTestCase


class TestAlchemyServerRepository(AlchemyTestCase):

    def setUp(self):
        self.setup_require_db_decorator_mock(alchemy_server_repository)

        self.server_constructor = Server
        self.repo = alchemy_server_repository.AlchemyServerRepository(self.server_constructor)

    def test_get_server_decorated(self):
        self.assert_require_db_decorated(
            alchemy_server_repository.AlchemyServerRepository.get_server.__func__
        )

    @patch.object(alchemy_server_repository, 'get_ip_str_from_bytes', autospec=True)
    @patch.object(alchemy_server_repository, 'Server', autospec=True)
    @patch('db.session', autospec=True)
    def test_get_server(self, session_mock, alchemy_server_mock, convert_ip_mock):
        expected_server_id = MagicMock()
        session_mock.query.return_value = query_mock = MagicMock(spec=Query)
        query_mock.filter.return_value = filtered_query_mock = MagicMock(spec=Query)
        alchemy_descr = MagicMock(name='descr')
        alchemy_ip = MagicMock(name='IP binary')
        filtered_query_mock.one.return_value = (alchemy_descr, alchemy_ip)
        convert_ip_mock.return_value = expected_ip = MagicMock(name='IP string')

        actual_server = self.repo.get_server(expected_server_id)

        session_mock.query.assert_called_once_with(alchemy_server_mock.descr, alchemy_server_mock.ip)
        query_mock.filter.assert_called_once()
        alchemy_server_mock.id.__eq__.assert_called_once_with(expected_server_id)
        filtered_query_mock.one.assert_called_once_with()
        convert_ip_mock.assert_called_once_with(alchemy_ip)
        self.assertEqual(actual_server.descr, alchemy_descr)
        self.assertEqual(actual_server.ip, expected_ip)


if __name__ == '__main__':
    unittest.main()
