import unittest

import os

import time

import requests
from mock import patch, MagicMock

from bounded_contexts.central_console.models.control_node import ControlNode
from bounded_contexts.central_console.models.deployment_info import DeploymentInfo
from bounded_contexts.central_console.models.license import License
from bounded_contexts.central_console.models.sensor import Sensor
from infrastructure.bounded_contexts.central_console.application_services.abstract_console_proxy\
    import usmcentral_http_proxy
from infrastructure.bounded_contexts.central_console.models.abstract_console_token.usmcentral_jwt_token\
    import USMCentralJwtToken


# This token may be reused, since it's not disconnected
USMCENTRAL_TOKEN_TEST_DEPLOYMENT_INFO = 'USMCENTRAL_TOKEN_TEST_DEPLOYMENT_INFO'
# This token could be used only once, since after disconnect test it won't be accepted
USMCENTRAL_TOKEN_TEST_DISCONNECT_ONESHOT = 'USMCENTRAL_TOKEN_TEST_DISCONNECT_ONESHOT'


class TestUSMCentralHttpProxy(unittest.TestCase):

    def setUp(self):
        self.proxy = usmcentral_http_proxy.USMCentralHttpProxy()

        # Use separate tokens in order not to depend on the order of test cases, since
        # after the disconnect test (which does actually connect + disconnect), it is not
        # possible to connect again using the same token
        connect_token_file_name = os.getenv(USMCENTRAL_TOKEN_TEST_DEPLOYMENT_INFO) or None
        disconnect_token_file_name = os.getenv(USMCENTRAL_TOKEN_TEST_DISCONNECT_ONESHOT) or None
        if not all((connect_token_file_name, disconnect_token_file_name)):
            self.skipTest(
                'Test environment parameter not set: {}'.format(USMCENTRAL_TOKEN_TEST_DEPLOYMENT_INFO)
            )
        with open(connect_token_file_name) as connect_token_file,\
                open(disconnect_token_file_name) as disconnect_token_file:
            self.connect_token = USMCentralJwtToken(connect_token_file.read())
            self.disconnect_token = USMCentralJwtToken(disconnect_token_file.read())

        control_node_ok = ControlNode(
            'control-node-id',
            'name',
            'some_description',
            'hyperV',  # allowed values only: ['aws', 'azure', 'hyperV', 'VMWare', 'hardware']
            'app_type',
            'software_version',
            'intelligence_version',
            'contact_email',
            'contact_name',
            'admin_ip_address',
            'vpn_ip_address'
        )
        control_node_not_ok = ControlNode(
            None,  # node id
            'name',
            'some_description',
            'hyperV',  # allowed values only: ['aws', 'azure', 'hyperV', 'VMWare', 'hardware']
            'app_type',
            'software_version',
            'intelligence_version',
            'contact_email',
            'contact_name',
            'admin_ip_address',
            'vpn_ip_address'
        )
        control_node_no_optionals = ControlNode(
            'node_id',
            'name',
            'description',
            'hyperV',  # allowed values only: ['aws', 'azure', 'hyperV', 'VMWare', 'hardware']
            'appliance_type',
            'software_version',
            'intelligence_version',
            None,  # contact email
            None,  # contact name
            'admin_ip_address',
            'vpn_ip_address'
        )
        license = License('False', 253402214400L, 10)
        license_no_optionals = License('False', None, None)
        sensor = Sensor(
            'sensor-id',
            'sensor-name',
            'sensor-description',
            'hyperV',
            '127.0.0.1',
            'software-version',
            'threat-intelligence-version',
            'Connected'
        )
        self.deployment_info_ok = DeploymentInfo(control_node_ok, license, [sensor])
        self.deployment_info_not_ok = DeploymentInfo(control_node_not_ok, license, [sensor])
        self.deployment_info_no_optionals = DeploymentInfo(control_node_no_optionals, license_no_optionals, [sensor])

    def test_connect_not_ok(self):
        rv = self.proxy.send_deployment_info(self.connect_token, self.deployment_info_not_ok)
        self.assertFalse(rv)

    def test_connect_ok(self):
        rv = self.proxy.send_deployment_info(self.connect_token, self.deployment_info_ok)
        self.assertTrue(rv)

    def test_connect_ok_deployment_info_only_required_fields(self):
        rv = self.proxy.send_deployment_info(self.connect_token, self.deployment_info_no_optionals)
        self.assertTrue(rv)

    def test_disconnect_ok(self):
        # First, connect in order to test disconnect
        self.proxy.send_deployment_info(self.disconnect_token, self.deployment_info_ok)

        # Should not raise HTTPError
        self.proxy.send_disconnect_notification(self.disconnect_token)

        # Should rise HTTPError, since the token is expected to be rejected with status_code=401
        self.assertRaises(requests.HTTPError, self.proxy.send_disconnect_notification, self.disconnect_token)

        # Should return false, since the token and the deployment info respectively are expected
        # to be rejected by USMCentral
        rv = self.proxy.send_deployment_info(self.disconnect_token, self.deployment_info_ok)
        self.assertFalse(rv)


if __name__ == '__main__':
    unittest.main()
