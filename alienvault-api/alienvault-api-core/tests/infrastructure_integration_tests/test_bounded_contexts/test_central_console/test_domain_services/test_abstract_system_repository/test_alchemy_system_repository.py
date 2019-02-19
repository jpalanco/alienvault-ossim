import socket
import unittest
import uuid

from mock import MagicMock, patch

from bounded_contexts.central_console.models.system import System
from infrastructure.bounded_contexts.central_console.domain_services.abstract_system_repository import \
    alchemy_system_repository
from integration_tests.alchemy_test_case import AlchemyTestCase


class TestSystemRepository(AlchemyTestCase):

    def setUp(self):
        self.assert_parameters()
        self.set_up_db()
        self.set_up_db_schema()
        self.set_up_alchemy(alchemy_system_repository)

        def get_ip_str_from_bytes_win(ip_bytes):
            return socket.inet_ntoa(ip_bytes)

        self.alchemy_repository = alchemy_system_repository.AlchemySystemRepository(System)
        alchemy_system_repository.get_ip_str_from_bytes = MagicMock(side_effect=get_ip_str_from_bytes_win)

    def set_up_db_schema(self):
        self.system_id = uuid.uuid4()
        self.expected_id = str(self.system_id)
        self.expected_server_id = uuid.uuid4().bytes
        self.expected_name = 'system_name'
        self.expected_admin_ip = '192.168.0.1'
        self.expected_vpn_ip = '172.16.0.1'
        self.expected_ha_ip = '192.168.0.10'
        self.expected_profile = 'Server'
        self.cursor.execute("""
        CREATE TABLE `system` (
          `id` binary(16) NOT NULL,
          `name` varchar(64) NOT NULL,
          `admin_ip` varbinary(16) NOT NULL,
          `vpn_ip` varbinary(16) DEFAULT NULL,
          `profile` varchar(255) NOT NULL,
          `sensor_id` binary(16) DEFAULT NULL,
          `server_id` binary(16) DEFAULT NULL,
          `database_id` binary(16) DEFAULT NULL,
          `host_id` binary(16) DEFAULT NULL,
          `ha_ip` varbinary(16) DEFAULT NULL,
          `ha_name` varchar(64) DEFAULT '',
          `ha_role` varchar(32) DEFAULT '',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        """)
        self.cursor.execute("""
        INSERT INTO system (id, name, admin_ip, vpn_ip, profile, server_id, ha_ip) 
        VALUES (%s, %s, %s, %s, 'Server', %s, %s)
        """, (
            self.system_id.bytes,
            self.expected_name,
            socket.inet_aton(self.expected_admin_ip),
            socket.inet_aton(self.expected_vpn_ip),
            self.expected_server_id,
            socket.inet_aton(self.expected_ha_ip)
        ))
        self.db.commit()

    @patch.object(alchemy_system_repository, 'get_system_id_from_local', autospec=True)
    def test_get_user(self, get_system_id_mock):
        get_system_id_mock.return_value = True, self.expected_id

        actual_system = self.alchemy_repository.get_system()

        self.assertEqual(actual_system.id, self.expected_id)
        self.assertEqual(actual_system.name, self.expected_name)
        self.assertEqual(actual_system.admin_ip, self.expected_admin_ip)
        self.assertEqual(actual_system.vpn_ip, self.expected_vpn_ip)
        self.assertEqual(actual_system.ha_ip, self.expected_ha_ip)


if __name__ == '__main__':
    unittest.main()
