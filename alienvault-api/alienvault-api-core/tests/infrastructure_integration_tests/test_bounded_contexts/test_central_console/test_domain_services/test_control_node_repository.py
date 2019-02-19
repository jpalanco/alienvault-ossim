import socket
import unittest
import uuid

from mock import MagicMock, patch

from bounded_contexts.central_console.domain_services import control_node_repository
from bounded_contexts.central_console.models.contact_person import ContactPerson
from bounded_contexts.central_console.models.control_node import ControlNode
from bounded_contexts.central_console.models.platform import Platform
from bounded_contexts.central_console.models.server import Server
from bounded_contexts.central_console.models.system import System
from infrastructure.bounded_contexts.central_console.domain_services.abstract_contact_person_repository import \
    alchemy_contact_person_repository
from infrastructure.bounded_contexts.central_console.domain_services.abstract_platform_repository import \
    ansible_platform_repository
from infrastructure.bounded_contexts.central_console.domain_services.abstract_server_repository import \
    alchemy_server_repository
from infrastructure.bounded_contexts.central_console.domain_services.abstract_system_repository import \
    alchemy_system_repository
from infrastructure.shared_kernel.config.domain_services.abstract_config_repository import alchemy_config_repository
from integration_tests.alchemy_test_case import AlchemyTestCase
from shared_kernel.config.models.config import Config


class TestControlNodeRepository(AlchemyTestCase):

    def setUp(self):
        self.system_id = uuid.uuid4()

        self.assert_parameters()
        self.set_up_db()
        self.set_up_db_schema()
        self.set_up_alchemy(alchemy_config_repository)
        self.set_up_db_deco_only(alchemy_system_repository)
        self.set_up_db_deco_only(alchemy_server_repository)
        self.set_up_db_deco_only(alchemy_contact_person_repository)

        self.config_repo = alchemy_config_repository.AlchemyConfigRepository(Config)
        self.system_repo = alchemy_system_repository.AlchemySystemRepository(System)
        self.server_repo = alchemy_server_repository.AlchemyServerRepository(Server)
        self.contact_repo = alchemy_contact_person_repository.AlchemyContactPersonRepository(ContactPerson)
        self.platform_repo = ansible_platform_repository.AnsiblePlatformRepository(Platform, self.config_repo)
        self.repo = control_node_repository.ControlNodeRepository(
            ControlNode,
            self.config_repo,
            self.system_repo,
            self.server_repo,
            self.contact_repo,
            self.platform_repo
        )

        def get_ip_str_from_bytes_win(ip_bytes):
            return ip_bytes and socket.inet_ntoa(ip_bytes)

        alchemy_system_repository.get_ip_str_from_bytes = MagicMock(
            side_effect=get_ip_str_from_bytes_win
        )
        alchemy_system_repository.get_system_id_from_local = MagicMock(
            return_value=(True, str(self.system_id))
        )
        alchemy_server_repository.get_ip_str_from_bytes = MagicMock(
            side_effect=get_ip_str_from_bytes_win
        )

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

        alchemy_system_id = str(self.system_id)
        alchemy_server_id = uuid.uuid4()
        system_admin_ip = '192.168.0.1'
        self.expected_system_id = alchemy_system_id
        self.expected_server_id = str(alchemy_server_id)
        self.expected_system_name = 'system name'
        self.expected_system_vpn_ip = '172.16.0.1'
        self.expected_ha_ip = ''
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
        INSERT INTO system (id, name, admin_ip, vpn_ip, profile, server_id) 
        VALUES (%s, %s, %s, %s, 'Server', %s)
        """, (
            self.system_id.bytes,
            self.expected_system_name,
            socket.inet_aton(system_admin_ip),
            socket.inet_aton(self.expected_system_vpn_ip),
            alchemy_server_id.bytes
        ))

        self.expected_server_descr = 'server description'
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
        """, (alchemy_server_id.bytes, socket.inet_aton(system_admin_ip), self.expected_server_descr))

        self.expected_user_email = 'mail@example.com'
        self.expected_user_name = 'User User'
        self.cursor.execute("""CREATE TABLE `users` (
          `login` varchar(64) NOT NULL,
          `ctx` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
          `name` varchar(128) NOT NULL,
          `pass` varchar(128) NOT NULL,
          `email` varchar(255) DEFAULT NULL,
          `company` varchar(128) DEFAULT NULL,
          `department` varchar(128) DEFAULT NULL,
          `language` varchar(12) NOT NULL DEFAULT 'en_GB',
          `enabled` tinyint(1) NOT NULL DEFAULT '1',
          `first_login` tinyint(1) NOT NULL DEFAULT '1',
          `timezone` varchar(64) NOT NULL DEFAULT 'GMT',
          `last_pass_change` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `last_logon_try` datetime NOT NULL,
          `is_admin` tinyint(1) NOT NULL DEFAULT '0',
          `template_id` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
          `uuid` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
          `expires` datetime NOT NULL DEFAULT '2200-01-01 00:00:00',
          `login_method` varchar(4) NOT NULL,
          `salt` text NOT NULL,
          PRIMARY KEY (`login`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"""
                )
        self.cursor.execute("""
        INSERT INTO users (login, name, pass, email, last_logon_try, is_admin, login_method, salt) 
        VALUES ('login', %s, 'pass', %s, CURRENT_TIMESTAMP, 1, 'abc', 'saltsaltsalt')
        """, (self.expected_user_name, self.expected_user_email))

        self.expected_platform_by_ansible = 'alienvault-hyperv'
        self.expected_platform_name = 'alienvault-hyperv'
        self.expected_intelligence_version = '100501'
        self.expected_appliance_type = 'alienvault-vmware-aio-6x1gb'
        self.expected_public_ip = '10.0.0.1'

        self.cursor.execute("""
                INSERT INTO config (conf, value)
                VALUES (%s, %s)
                """, (
            control_node_repository.CONFIG_SERVER_ID,
            self.expected_server_id
        ))

        self.expected_software_version = '100500'
        self.cursor.execute("""
                        INSERT INTO config (conf, value)
                        VALUES (%s, %s)
                        """, (
            control_node_repository.CONFIG_VERSION,
            self.expected_software_version
        ))

        self.db.commit()

    @patch.object(ansible_platform_repository, 'get_local_ami_public_ip', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_appliance_type', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_platform', autospec=True)
    @patch.object(ansible_platform_repository, 'get_threat_intelligence_version', autospec=True)
    def test_get_control_node(self, get_version_mock, get_platform_mock, get_appliance_type_mock, get_public_ip_mock):
        get_version_mock.return_value = True, self.expected_intelligence_version
        get_platform_mock.return_value = True, self.expected_platform_by_ansible
        get_appliance_type_mock.return_value = True, self.expected_appliance_type
        get_public_ip_mock.return_value = True, self.expected_public_ip

        actual_control_node = self.repo.get_control_node()

        self.assertEqual(actual_control_node.node_id, self.expected_system_id)
        self.assertEqual(actual_control_node.name, self.expected_system_name)
        self.assertEqual(actual_control_node.description, self.expected_server_descr)
        self.assertEqual(actual_control_node.platform, self.expected_platform_name)
        self.assertEqual(actual_control_node.appliance_type, self.expected_appliance_type)
        self.assertEqual(actual_control_node.software_version, self.expected_software_version)
        self.assertEqual(actual_control_node.intelligence_version, self.expected_intelligence_version)
        self.assertEqual(actual_control_node.contact_email, self.expected_user_email)
        self.assertEqual(actual_control_node.contact_name, self.expected_user_name)
        self.assertEqual(actual_control_node.admin_ip_address, self.expected_public_ip)
        self.assertEqual(actual_control_node.vpn_ip_address, self.expected_system_vpn_ip)


if __name__ == '__main__':
    unittest.main()
