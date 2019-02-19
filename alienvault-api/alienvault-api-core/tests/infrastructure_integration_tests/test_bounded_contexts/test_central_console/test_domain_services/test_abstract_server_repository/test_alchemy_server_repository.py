import socket
import unittest
import uuid

from mock import MagicMock

from bounded_contexts.central_console.models.server import Server
from infrastructure.bounded_contexts.central_console.domain_services.abstract_server_repository import \
    alchemy_server_repository
from integration_tests.alchemy_test_case import AlchemyTestCase


class TestServerRepository(AlchemyTestCase):

    def setUp(self):
        self.assert_parameters()
        self.set_up_db()
        self.set_up_db_schema()
        self.set_up_alchemy(alchemy_server_repository)

        def get_ip_str_from_bytes_win(ip_bin):
            return socket.inet_ntoa(ip_bin)

        self.alchemy_repository = alchemy_server_repository.AlchemyServerRepository(Server)
        alchemy_server_repository.get_ip_str_from_bytes = MagicMock(side_effect=get_ip_str_from_bytes_win)

    def set_up_db_schema(self):
        self.expected_server_id = uuid.uuid4().bytes
        self.expected_descr = 'server description'
        self.expected_ip = '192.168.0.1'
        self.cursor.execute("""
        CREATE TABLE `server` (
          `id` binary(16) NOT NULL,
          `name` varchar(64) NOT NULL,
          `ip` varbinary(16) DEFAULT NULL,
          `port` int(11) NOT NULL,
          `descr` varchar(255) NOT NULL,
          `remoteadmin` varchar(64) NOT NULL,
          `remotepass` varchar(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
          `remoteurl` varchar(128) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        """)
        self.cursor.execute("""
        INSERT INTO server (id, name, ip, port, descr, remoteadmin, remotepass, remoteurl) 
        VALUES (%s, 'name', %s, 8080, %s, 'admin', 'admin', 'http://url')
        """, (self.expected_server_id, socket.inet_aton(self.expected_ip), self.expected_descr))
        self.db.commit()

    def test_get_user(self):
        actual_server = self.alchemy_repository.get_server(self.expected_server_id)
        self.assertEqual(actual_server.descr, self.expected_descr)
        self.assertEqual(actual_server.ip, self.expected_ip)


if __name__ == '__main__':
    unittest.main()
