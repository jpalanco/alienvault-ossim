import socket
import unittest
import uuid

from mock import MagicMock, patch

from apimethods.utils import get_bytes_from_uuid
from bounded_contexts.central_console.models.platform import Platform
from bounded_contexts.central_console.models.sensor import Sensor
from infrastructure.bounded_contexts.central_console.domain_services.abstract_platform_repository import \
    ansible_platform_repository
from infrastructure.bounded_contexts.central_console.domain_services.abstract_sensor_repository import \
    alchemy_sensor_repository
from infrastructure.shared_kernel.config.domain_services.abstract_config_repository import alchemy_config_repository
from integration_tests.alchemy_test_case import AlchemyTestCase
from shared_kernel.config.models.config import Config


class MyTestCase(AlchemyTestCase):
    def setUp(self):
        self.expected_ip = '192.168.0.1'
        self.assert_parameters()
        self.set_up_db()
        self.set_up_db_schema()
        self.set_up_alchemy(alchemy_sensor_repository)
        self.set_up_db_deco_only(alchemy_config_repository)

        self.conf_repo = alchemy_config_repository.AlchemyConfigRepository(Config)
        self.platform_repo = ansible_platform_repository.AnsiblePlatformRepository(Platform, self.conf_repo)
        self.repo = alchemy_sensor_repository.AlchemySensorRepository(Sensor, self.platform_repo)

        alchemy_sensor_repository.get_sensor_ip_from_sensor_id = MagicMock(
            return_value=(True, self.expected_ip)
        )

    def set_up_db_schema(self):
        self.cursor.execute("""
           CREATE TABLE `sensor` (
          `id` binary(16) NOT NULL,
          `name` varchar(64) NOT NULL,
          `ip` varbinary(16) DEFAULT NULL,
          `priority` smallint(6) NOT NULL,
          `port` int(11) NOT NULL,
          `connect` smallint(6) NOT NULL,
          `descr` varchar(255) NOT NULL,
          `tzone` float NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        """)
        self.cursor.execute(
            """
           CREATE TABLE `sensor_properties` (
          `sensor_id` binary(16) NOT NULL,
          `version` varchar(64) NOT NULL,
          `has_nagios` tinyint(1) NOT NULL DEFAULT '1',
          `has_ntop` tinyint(1) NOT NULL DEFAULT '1',
          `has_vuln_scanner` tinyint(1) NOT NULL DEFAULT '1',
          `has_kismet` tinyint(1) NOT NULL DEFAULT '0',
          `ids` tinyint(1) NOT NULL DEFAULT '0',
          `passive_inventory` tinyint(1) NOT NULL DEFAULT '0',
          `netflows` tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (`sensor_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            """
        )
        self.cursor.execute(
            """
          CREATE TABLE `config` (
          `conf` varchar(255) NOT NULL,
          `value` text CHARACTER SET latin1 COLLATE latin1_general_ci,
          PRIMARY KEY (`conf`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            """
        )

        self.expected_sensor_id = str(uuid.uuid4())
        self.expected_name = 'sensor_name'
        self.expected_is_connected = True
        self.expected_descr = 'sensor description'
        self.cursor.execute("""
        INSERT INTO sensor (id, name, ip, priority, port, connect, descr)
        VALUES (%s, %s, %s, %s, %s, %s, %s)""", (
            get_bytes_from_uuid(self.expected_sensor_id),
            self.expected_name,
            socket.inet_aton(self.expected_ip),
            1,
            8080,
            0,
            self.expected_descr
        ))

        self.expected_software_version = '100500'
        self.cursor.execute("""
                INSERT INTO sensor_properties (sensor_id, version)
                VALUES (%s, %s)
                """, (
            get_bytes_from_uuid(self.expected_sensor_id),
            self.expected_software_version
        ))

        self.expected_platform_name = 'alienvault-hyperv'
        self.expected_platform_by_ansible = 'alienvault-hyperv'
        self.expected_appliance_type_by_ansible = 'alienvault-vmware-aio-6x1gb'
        self.expected_intelligence_version = '100501'

        self.db.commit()

    @patch.object(ansible_platform_repository, 'get_alienvault_appliance_type', autospec=True)
    @patch.object(ansible_platform_repository, 'get_alienvault_platform', autospec=True)
    @patch.object(ansible_platform_repository, 'get_threat_intelligence_version', autospec=True)
    def test_get_sensors(self, get_version_mock, get_platform_mock, get_appliance_type_mock):
        get_version_mock.return_value = True, self.expected_intelligence_version
        get_platform_mock.return_value = True, self.expected_platform_by_ansible
        get_appliance_type_mock.return_value = True, self.expected_appliance_type_by_ansible

        actual_sensors = self.repo.get_sensors()

        self.assertEqual(actual_sensors[0].sensor_id, self.expected_sensor_id)
        self.assertEqual(actual_sensors[0].name, self.expected_name)
        self.assertEqual(actual_sensors[0].description, self.expected_descr)
        self.assertEqual(actual_sensors[0].platform, self.expected_platform_name)
        self.assertEqual(actual_sensors[0].ip_address, self.expected_ip)
        self.assertEqual(actual_sensors[0].software_version, self.expected_software_version)
        self.assertEqual(actual_sensors[0].threat_intelligence_version, self.expected_intelligence_version)
        self.assertEqual(actual_sensors[0].is_connected, self.expected_is_connected)


if __name__ == '__main__':
    unittest.main()
