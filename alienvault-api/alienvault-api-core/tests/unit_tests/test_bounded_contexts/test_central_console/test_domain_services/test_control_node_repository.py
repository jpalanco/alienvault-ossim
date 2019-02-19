import unittest

from mock import MagicMock, call, patch

from bounded_contexts.central_console.domain_services import control_node_repository
from bounded_contexts.central_console.domain_services.abstract_contact_person_repository import \
    AbstractContactPersonRepository
from bounded_contexts.central_console.domain_services.abstract_platform_repository import AbstractPlatformRepository
from bounded_contexts.central_console.domain_services.abstract_server_repository import AbstractServerRepository
from bounded_contexts.central_console.domain_services.abstract_system_repository import AbstractSystemRepository
from bounded_contexts.central_console.domain_services.control_node_repository import CONFIG_SERVER_ID, CONFIG_VERSION
from bounded_contexts.central_console.models.contact_person import ContactPerson
from bounded_contexts.central_console.models.control_node import ControlNode
from bounded_contexts.central_console.models.platform import Platform
from bounded_contexts.central_console.models.server import Server
from bounded_contexts.central_console.models.system import System
from shared_kernel.config.domain_services.abstract_config_repository import AbstractConfigRepository
from shared_kernel.config.models.config import Config
from unit_tests.base_test_case import BaseTestCase


class TestControlNodeRepository(BaseTestCase):

    def setUp(self):
        self.conf_repo = MagicMock(spec=AbstractConfigRepository)
        self.system_repo = MagicMock(spec=AbstractSystemRepository)
        self.server_repo = MagicMock(spec=AbstractServerRepository)
        self.contact_repo = MagicMock(spec=AbstractContactPersonRepository)
        self.platform_repo = MagicMock(spec=AbstractPlatformRepository)

        self.repo = control_node_repository.ControlNodeRepository(
            ControlNode,
            self.conf_repo,
            self.system_repo,
            self.server_repo,
            self.contact_repo,
            self.platform_repo
        )

    @patch.object(control_node_repository, 'get_bytes_from_uuid', autospec=True)
    def test_get_control_node_with_public_ip(self, convert_id_mock):
        server_id_config = MagicMock(spec=Config)
        softversion_config = MagicMock(spec=Config)
        self.system_repo.get_system.return_value = MagicMock(spec=System)
        self.server_repo.get_server.return_value = MagicMock(spec=Server)
        self.conf_repo.get_config.side_effect = [server_id_config, softversion_config]
        self.contact_repo.get_contact_person.return_value = MagicMock(spec=ContactPerson)
        self.set_named_tuple_mock_bool_context_value(self.contact_repo.get_contact_person.return_value, True)
        self.platform_repo.get_platform.return_value = MagicMock(spec=Platform)
        self.set_named_tuple_mock_bool_context_value(self.platform_repo.get_platform.return_value, True)
        self.platform_repo.get_platform.return_value.public_ip = '10.0.0.0'

        actual_node = self.repo.get_control_node()

        self.conf_repo.get_config.assert_has_calls([
            call(CONFIG_SERVER_ID),
            call(CONFIG_VERSION)
        ])
        self.system_repo.get_system.assert_called_once_with()
        self.server_repo.get_server.assert_called_once_with(convert_id_mock.return_value)
        self.contact_repo.get_contact_person.assert_called_once_with()
        self.platform_repo.get_platform.assert_called_once_with(self.system_repo.get_system.return_value.admin_ip)

        self.assertEqual(actual_node.node_id, self.system_repo.get_system.return_value.id)
        self.assertEqual(actual_node.name, self.system_repo.get_system.return_value.name)
        self.assertEqual(actual_node.description, self.server_repo.get_server.return_value.descr)
        self.assertEqual(actual_node.platform, self.platform_repo.get_platform.return_value.name)
        self.assertEqual(actual_node.appliance_type, self.platform_repo.get_platform.return_value.appliance_type)
        self.assertEqual(actual_node.software_version, softversion_config.value)
        self.assertEqual(
            actual_node.intelligence_version,
            self.platform_repo.get_platform.return_value.threat_intelligence_version
        )
        self.assertEqual(actual_node.contact_email, self.contact_repo.get_contact_person.return_value.email)
        self.assertEqual(actual_node.contact_name, self.contact_repo.get_contact_person.return_value.name)
        self.assertEqual(actual_node.admin_ip_address, self.platform_repo.get_platform.return_value.public_ip)
        self.assertEqual(actual_node.vpn_ip_address, self.system_repo.get_system.return_value.vpn_ip)

    @patch.object(control_node_repository, 'get_bytes_from_uuid', autospec=True)
    def test_get_control_node_with_admin_ip(self, convert_id_mock):
        server_id_config = MagicMock(spec=Config)
        softversion_config = MagicMock(spec=Config)
        self.system_repo.get_system.return_value = MagicMock(spec=System)
        self.system_repo.get_system.return_value.admin_ip = '192.168.0.1'
        self.system_repo.get_system.return_value.ha_ip = ''
        self.server_repo.get_server.return_value = MagicMock(spec=Server)
        self.conf_repo.get_config.side_effect = [server_id_config, softversion_config]
        self.contact_repo.get_contact_person.return_value = MagicMock(spec=ContactPerson)
        self.contact_repo.get_contact_person.return_value.__len__ = MagicMock(return_value=1)
        self.platform_repo.get_platform.return_value = MagicMock(spec=Platform)
        self.set_named_tuple_mock_bool_context_value(self.platform_repo.get_platform.return_value, True)
        self.platform_repo.get_platform.return_value.public_ip = None

        actual_node = self.repo.get_control_node()

        self.conf_repo.get_config.assert_has_calls([
            call(CONFIG_SERVER_ID),
            call(CONFIG_VERSION)
        ])
        self.system_repo.get_system.assert_called_once_with()
        self.server_repo.get_server.assert_called_once_with(convert_id_mock.return_value)
        self.contact_repo.get_contact_person.assert_called_once_with()
        self.platform_repo.get_platform.assert_called_once_with(self.system_repo.get_system.return_value.admin_ip)

        self.assertEqual(actual_node.node_id, self.system_repo.get_system.return_value.id)
        self.assertEqual(actual_node.name, self.system_repo.get_system.return_value.name)
        self.assertEqual(actual_node.description, self.server_repo.get_server.return_value.descr)
        self.assertEqual(actual_node.platform, self.platform_repo.get_platform.return_value.name)
        self.assertEqual(actual_node.appliance_type, self.platform_repo.get_platform.return_value.appliance_type)
        self.assertEqual(actual_node.software_version, softversion_config.value)
        self.assertEqual(
            actual_node.intelligence_version,
            self.platform_repo.get_platform.return_value.threat_intelligence_version
        )
        self.assertEqual(actual_node.contact_email, self.contact_repo.get_contact_person.return_value.email)
        self.assertEqual(actual_node.contact_name, self.contact_repo.get_contact_person.return_value.name)
        self.assertEqual(actual_node.admin_ip_address, self.system_repo.get_system.return_value.admin_ip)
        self.assertEqual(actual_node.vpn_ip_address, self.system_repo.get_system.return_value.vpn_ip)

    @patch.object(control_node_repository, 'get_bytes_from_uuid', autospec=True)
    def test_get_control_node_with_ha_ip(self, convert_id_mock):
        server_id_config = MagicMock(spec=Config)
        softversion_config = MagicMock(spec=Config)
        self.system_repo.get_system.return_value = MagicMock(spec=System)
        self.system_repo.get_system.return_value.admin_ip = '192.168.0.1'
        self.system_repo.get_system.return_value.ha_ip = '192.168.0.10'
        self.server_repo.get_server.return_value = MagicMock(spec=Server)
        self.conf_repo.get_config.side_effect = [server_id_config, softversion_config]
        self.contact_repo.get_contact_person.return_value = MagicMock(spec=ContactPerson)
        self.set_named_tuple_mock_bool_context_value(self.contact_repo.get_contact_person.return_value, True)
        self.platform_repo.get_platform.return_value = MagicMock(spec=Platform)
        self.set_named_tuple_mock_bool_context_value(self.platform_repo.get_platform.return_value, True)
        self.platform_repo.get_platform.return_value.public_ip = None

        actual_node = self.repo.get_control_node()

        self.conf_repo.get_config.assert_has_calls([
            call(CONFIG_SERVER_ID),
            call(CONFIG_VERSION)
        ])
        self.system_repo.get_system.assert_called_once_with()
        self.server_repo.get_server.assert_called_once_with(convert_id_mock.return_value)
        self.contact_repo.get_contact_person.assert_called_once_with()
        self.platform_repo.get_platform.assert_called_once_with(self.system_repo.get_system.return_value.admin_ip)

        self.assertEqual(actual_node.node_id, self.system_repo.get_system.return_value.id)
        self.assertEqual(actual_node.name, self.system_repo.get_system.return_value.name)
        self.assertEqual(actual_node.description, self.server_repo.get_server.return_value.descr)
        self.assertEqual(actual_node.platform, self.platform_repo.get_platform.return_value.name)
        self.assertEqual(actual_node.appliance_type, self.platform_repo.get_platform.return_value.appliance_type)
        self.assertEqual(actual_node.software_version, softversion_config.value)
        self.assertEqual(
            actual_node.intelligence_version,
            self.platform_repo.get_platform.return_value.threat_intelligence_version
        )
        self.assertEqual(actual_node.contact_email, self.contact_repo.get_contact_person.return_value.email)
        self.assertEqual(actual_node.contact_name, self.contact_repo.get_contact_person.return_value.name)
        self.assertEqual(actual_node.admin_ip_address, self.system_repo.get_system.return_value.ha_ip)
        self.assertEqual(actual_node.vpn_ip_address, self.system_repo.get_system.return_value.vpn_ip)

    @patch.object(control_node_repository, 'get_bytes_from_uuid', autospec=True)
    def test_get_control_node_no_contact_person(self, convert_id_mock):
        server_id_config = MagicMock(spec=Config)
        softversion_config = MagicMock(spec=Config)
        self.system_repo.get_system.return_value = MagicMock(spec=System)
        self.server_repo.get_server.return_value = MagicMock(spec=Server)
        self.conf_repo.get_config.side_effect = [server_id_config, softversion_config]
        self.contact_repo.get_contact_person.return_value = None
        self.platform_repo.get_platform.return_value = MagicMock(spec=Platform)
        self.set_named_tuple_mock_bool_context_value(self.platform_repo.get_platform.return_value, True)
        self.platform_repo.get_platform.return_value.public_ip = '10.0.0.1'

        actual_node = self.repo.get_control_node()

        self.assertEqual(actual_node.node_id, self.system_repo.get_system.return_value.id)
        self.assertEqual(actual_node.name, self.system_repo.get_system.return_value.name)
        self.assertEqual(actual_node.description, self.server_repo.get_server.return_value.descr)
        self.assertEqual(actual_node.platform, self.platform_repo.get_platform.return_value.name)
        self.assertEqual(actual_node.appliance_type, self.platform_repo.get_platform.return_value.appliance_type)
        self.assertEqual(actual_node.software_version, softversion_config.value)
        self.assertEqual(
            actual_node.intelligence_version,
            self.platform_repo.get_platform.return_value.threat_intelligence_version
        )
        self.assertEqual(actual_node.contact_email, None)
        self.assertEqual(actual_node.contact_name, None)
        self.assertEqual(actual_node.admin_ip_address, self.platform_repo.get_platform.return_value.public_ip)
        self.assertEqual(actual_node.vpn_ip_address, self.system_repo.get_system.return_value.vpn_ip)

    @patch.object(control_node_repository, 'get_bytes_from_uuid', autospec=True)
    def test_get_control_node_no_platform_data(self, convert_id_mock):
        server_id_config = MagicMock(spec=Config)
        softversion_config = MagicMock(spec=Config)
        self.system_repo.get_system.return_value = MagicMock(spec=System)
        self.system_repo.get_system.return_value.ha_ip = ''
        self.server_repo.get_server.return_value = MagicMock(spec=Server)
        self.conf_repo.get_config.side_effect = [server_id_config, softversion_config]
        self.contact_repo.get_contact_person.return_value = MagicMock(spec=ContactPerson)
        self.set_named_tuple_mock_bool_context_value(self.contact_repo.get_contact_person.return_value, True)
        self.platform_repo.get_platform.return_value = None

        actual_node = self.repo.get_control_node()

        self.assertEqual(actual_node.node_id, self.system_repo.get_system.return_value.id)
        self.assertEqual(actual_node.name, self.system_repo.get_system.return_value.name)
        self.assertEqual(actual_node.description, self.server_repo.get_server.return_value.descr)
        self.assertEqual(actual_node.platform, None)
        self.assertEqual(actual_node.appliance_type, None)
        self.assertEqual(actual_node.software_version, softversion_config.value)
        self.assertEqual(actual_node.intelligence_version, None)
        self.assertEqual(actual_node.contact_email, self.contact_repo.get_contact_person.return_value.email)
        self.assertEqual(actual_node.contact_name, self.contact_repo.get_contact_person.return_value.name)
        self.assertEqual(actual_node.admin_ip_address, self.system_repo.get_system.return_value.admin_ip)
        self.assertEqual(actual_node.vpn_ip_address, self.system_repo.get_system.return_value.vpn_ip)


if __name__ == '__main__':
    unittest.main()
