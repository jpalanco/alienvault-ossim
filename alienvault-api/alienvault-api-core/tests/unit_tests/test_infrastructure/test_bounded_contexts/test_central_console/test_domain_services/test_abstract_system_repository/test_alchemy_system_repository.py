import unittest

from mock import patch, MagicMock
from sqlalchemy.orm import Query

from bounded_contexts.central_console.models.system import System
from infrastructure.bounded_contexts.central_console.domain_services.abstract_system_repository import \
    alchemy_system_repository
from unit_tests.alchemy_test_case import AlchemyTestCase


class TestAlchemySystemRepository(AlchemyTestCase):

    def setUp(self):
        self.setup_require_db_decorator_mock(alchemy_system_repository)

        self.system_contructor = System
        self.repo = alchemy_system_repository.AlchemySystemRepository(self.system_contructor)

    def test_get_system_decorated(self):
        self.assert_require_db_decorated(
            alchemy_system_repository.AlchemySystemRepository.get_system.__func__
        )

    @patch.object(alchemy_system_repository, 'get_system_id_from_local', autospec=True)
    @patch.object(alchemy_system_repository, 'get_bytes_from_uuid', autospec=True)
    @patch.object(alchemy_system_repository, 'get_ip_str_from_bytes', autospec=True)
    @patch.object(alchemy_system_repository, 'System', autospec=True)
    @patch('db.session', autospec=True)
    def test_get_system(self, session_mock, alchemy_system_mock, convert_ip_mock, convert_id_mock, get_system_id_mock):
        system_id = MagicMock(name='system_id')
        get_system_id_mock.return_value = True, system_id
        convert_id_mock.return_value = system_id_bin = MagicMock(name='system_id_bin')
        session_mock.query.return_value = query_mock = MagicMock(spec=Query)
        query_mock.filter.return_value = filtered_query_mock = MagicMock(spec=Query)
        alchemy_system_mock.id.__eq__ = MagicMock()
        alchemy_name = MagicMock(name='name')
        alchemy_admin_ip = MagicMock(name='admin_ip')
        alchemy_vpn_ip = MagicMock(name='vpn_ip')
        alchemy_ha_ip = MagicMock(name='ha_ip')
        filtered_query_mock.one.return_value = (
            alchemy_name,
            alchemy_admin_ip,
            alchemy_vpn_ip,
            alchemy_ha_ip
        )
        expected_admin_ip = MagicMock(name='system_admin_ip_str')
        expected_vpn_ip = MagicMock(name='vpn_ip_str')
        expected_ha_ip = MagicMock(name='ha_ip_str')
        convert_ip_mock.side_effect = [expected_admin_ip, expected_vpn_ip, expected_ha_ip]

        actual_system = self.repo.get_system()

        get_system_id_mock.assert_called_once_with()
        session_mock.query.assert_called_once_with(
            alchemy_system_mock.name,
            alchemy_system_mock.admin_ip,
            alchemy_system_mock.vpn_ip,
            alchemy_system_mock.ha_ip
        )
        query_mock.filter.assert_called_once_with(alchemy_system_mock.id.__eq__.return_value)
        alchemy_system_mock.id.__eq__.assert_called_once_with(system_id_bin)
        filtered_query_mock.one.assert_called_once_with()
        self.assertEqual(actual_system.id, system_id)
        self.assertEqual(actual_system.name, alchemy_name)
        self.assertEqual(actual_system.admin_ip, expected_admin_ip)
        self.assertEqual(actual_system.vpn_ip, expected_vpn_ip)
        self.assertEqual(actual_system.ha_ip, expected_ha_ip)


if __name__ == '__main__':
    unittest.main()
