import unittest

from mock import patch, MagicMock

from bounded_contexts.central_console.models.abstract_console_token import AbstractConsoleToken
from bounded_contexts.central_console.models import control_node
from bounded_contexts.central_console.models import deployment_info
from bounded_contexts.central_console.models import license
from bounded_contexts.central_console.models import sensor
from infrastructure.bounded_contexts.central_console.application_services.abstract_console_proxy\
    import usmcentral_http_proxy


class FakeToken(AbstractConsoleToken):

    def __init__(self, raw_data):
        super(FakeToken, self).__init__(raw_data)
        self.__issuer = 'www.com'

    @property
    def issuer(self):
        return self.__issuer


class TestUSMCentralHttpProxy(unittest.TestCase):

    def setUp(self):
        self.proxy = usmcentral_http_proxy.USMCentralHttpProxy()
        self.maxDiff = None

    @staticmethod
    def get_expected_payload_complete(expected_info):
        return {
            'controlNode': {
                'id': expected_info.control_node.node_id,
                'name': expected_info.control_node.name,
                'description': expected_info.control_node.description,
                'fqdn': usmcentral_http_proxy.USMCENTRAL_ATTR_NOT_AVAILABLE,
                'platform': usmcentral_http_proxy.PLATFORMS_MAP.get(expected_info.control_node.platform),
                'applianceType': expected_info.control_node.appliance_type,
                'softwareVersion': expected_info.control_node.software_version,
                'threatIntelligenceVersion': expected_info.control_node.intelligence_version,
                'contactEmail': expected_info.control_node.contact_email,
                'contactName': expected_info.control_node.contact_name,
                'adminIpAddress': expected_info.control_node.admin_ip_address,
                'vpnIpAddress': expected_info.control_node.vpn_ip_address
            },
            'license': {
                'isTrial': expected_info.license.is_trial,
                'expiresOn': expected_info.license.expires_on,
                'devices': expected_info.license.devices
            },
            'sensors': [{
                'id': expected_sensor.sensor_id,
                'name': expected_sensor.name,
                'description': expected_sensor.description,
                'platform': usmcentral_http_proxy.PLATFORMS_MAP.get(expected_sensor.platform),
                'ipAddress': expected_sensor.ip_address,
                'softwareVersion': expected_sensor.software_version,
                'threatIntelligenceVersion': expected_sensor.threat_intelligence_version,
                'connectionStatus': usmcentral_http_proxy.SENSOR_STATUS_CONNECTED if expected_sensor.is_connected
                else usmcentral_http_proxy.SENSOR_STATUS_CONNECTION_LOST
            } for expected_sensor in expected_info.sensors]
        }

    @staticmethod
    def get_expected_deployment_info():
        expected_info = MagicMock(spec=deployment_info.DeploymentInfo)
        expected_info.control_node = MagicMock(spec=control_node.ControlNode)
        expected_info.control_node.platform = 'alienvault-vmware'
        expected_info.license = MagicMock(spec=license.License)
        expected_sensor = MagicMock(spec=sensor.Sensor)
        expected_sensor.platform = 'alienvault-vmware'
        expected_info.sensors = [expected_sensor]
        return expected_info

    @patch.object(usmcentral_http_proxy, 'usmcentral_http_header', autospec=True)
    @patch.object(usmcentral_http_proxy, 'usmcentral_deployment_payload', autospec=True)
    @patch.object(usmcentral_http_proxy, 'requests', autospec=True)
    def test_send_deployment_info_ok(self, requests_mock, get_payload_mock, get_header_mock):
        token = FakeToken('tokenrawdata')
        info = None
        expected_url = '{}{}{}'.format(
            usmcentral_http_proxy.USMCENTRAL_HTTP_PROTOCOL,
            token.issuer,
            usmcentral_http_proxy.USMCENTRAL_HTTP_CONNECT_PATH
        )
        get_header_mock.return_value = header_mock = MagicMock()
        get_payload_mock.return_value = payload_mock = MagicMock()
        requests_mock.post.return_value = post_response = MagicMock()
        post_response.status_code = usmcentral_http_proxy.USMCENTRAL_HTTP_OK

        rv = self.proxy.send_deployment_info(token, info)

        requests_mock.post.assert_called_with(
            url=expected_url,
            json=payload_mock,
            headers=header_mock,
            timeout=usmcentral_http_proxy.USMCENTRAL_HTTP_TIMEOUT_SEC
        )
        self.assertTrue(rv)

    @patch.object(usmcentral_http_proxy, 'requests', autospec=True)
    def test_send_deployment_info_not_ok(self, requests_mock):
        token = FakeToken('tokenrawdata')
        info = MagicMock(spec=deployment_info.DeploymentInfo)
        requests_mock.get.return_value = get_response = MagicMock()
        get_response.status_code = 401

        self.assertFalse(self.proxy.send_deployment_info(token, info))

    def test_get_payload(self):
        expected_info = self.get_expected_deployment_info()
        expected_payload = self.get_expected_payload_complete(expected_info)

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)
        actual_payload['controlNode']['platform'] = usmcentral_http_proxy.PLATFORMS_MAP.get(
            expected_info.control_node.platform
        )

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_no_contact_name(self):
        expected_info = self.get_expected_deployment_info()
        expected_info.control_node.contact_name = None
        expected_payload = self.get_expected_payload_complete(expected_info)
        del expected_payload['controlNode']['contactName']

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_no_contact_email(self):
        expected_info = self.get_expected_deployment_info()
        expected_info.control_node.contact_email = None
        expected_payload = self.get_expected_payload_complete(expected_info)
        del expected_payload['controlNode']['contactEmail']

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_no_expiration(self):
        expected_info = self.get_expected_deployment_info()
        expected_info.license.expires_on = None
        expected_payload = self.get_expected_payload_complete(expected_info)
        del expected_payload['license']['expiresOn']

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_no_devices(self):
        expected_info = self.get_expected_deployment_info()
        expected_info.license.devices = None
        expected_payload = self.get_expected_payload_complete(expected_info)
        del expected_payload['license']['devices']

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_sensor_statuses(self):
        expected_info = self.get_expected_deployment_info()
        expected_sensor1 = MagicMock(spec=sensor.Sensor)
        expected_sensor1.is_connected = True
        expected_sensor1.platform = 'alienvault-vmware'
        expected_sensor2 = MagicMock(spec=sensor.Sensor)
        expected_sensor2.is_connected = False
        expected_sensor2.platform = None
        expected_info.sensors = [expected_sensor1, expected_sensor2]

        expected_payload = self.get_expected_payload_complete(expected_info)
        expected_payload['sensors'][0]['connectionStatus'] = usmcentral_http_proxy.SENSOR_STATUS_CONNECTED
        expected_payload['sensors'][0]['platform'] = usmcentral_http_proxy.PLATFORMS_MAP.get('alienvault-vmware')
        expected_payload['sensors'][1]['connectionStatus'] = usmcentral_http_proxy.SENSOR_STATUS_CONNECTION_LOST
        expected_payload['sensors'][1]['platform'] = usmcentral_http_proxy.SENSOR_FALLBACK_PLATFORM

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_sensor_no_platform(self):
        expected_info = self.get_expected_deployment_info()
        expected_info.sensors[0].platform = None

        expected_payload = self.get_expected_payload_complete(expected_info)
        expected_payload['sensors'][0]['platform'] = usmcentral_http_proxy.SENSOR_FALLBACK_PLATFORM

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_sensor_no_software_version(self):
        expected_info = self.get_expected_deployment_info()
        expected_info.sensors[0].software_version = None

        expected_payload = self.get_expected_payload_complete(expected_info)
        expected_payload['sensors'][0]['softwareVersion'] = usmcentral_http_proxy.SENSOR_FALLBACK_SOFTWARE_VERSION

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_sensor_no_intelligence_version(self):
        expected_info = self.get_expected_deployment_info()
        expected_info.sensors[0].threat_intelligence_version = None

        expected_payload = self.get_expected_payload_complete(expected_info)
        expected_payload['sensors'][0]['threatIntelligenceVersion'] = \
            usmcentral_http_proxy.SENSOR_FALLBACK_INTELLIGENCE_VERSION

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_control_node_no_platform(self):
        expected_info = self.get_expected_deployment_info()
        expected_info.control_node.platform = None

        expected_payload = self.get_expected_payload_complete(expected_info)
        expected_payload['controlNode']['platform'] = usmcentral_http_proxy.CONTROL_NODE_FALLBACK_PLATFORM

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_control_node_no_appliance_type(self):
        expected_info = self.get_expected_deployment_info()
        expected_info.control_node.appliance_type = None

        expected_payload = self.get_expected_payload_complete(expected_info)
        expected_payload['controlNode']['applianceType'] = usmcentral_http_proxy.CONTROL_NODE_FALLBACK_APPLIANCE_TYPE

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_payload_control_node_no_intelligence_version(self):
        expected_info = self.get_expected_deployment_info()
        expected_info.control_node.intelligence_version = None

        expected_payload = self.get_expected_payload_complete(expected_info)
        expected_payload['controlNode']['threatIntelligenceVersion'] = \
            usmcentral_http_proxy.CONTROL_NODE_FALLBACK_INTELLIGENCE_VERSION

        actual_payload = usmcentral_http_proxy.usmcentral_deployment_payload(expected_info)

        self.assertEqual(actual_payload, expected_payload)

    def test_get_header(self):
        token = FakeToken('tokenrawdata')
        expected_header = {
            'Content-type': 'application/json',
            'Authorization': 'Bearer ' + str(token.raw_data),
            'USMC-API-Version': '1.0'
        }
        actual_header = usmcentral_http_proxy.usmcentral_http_header(token)
        self.assertEqual(actual_header, expected_header)

    @patch.object(usmcentral_http_proxy, 'usmcentral_http_header', autospec=True)
    @patch.object(usmcentral_http_proxy, 'requests', autospec=True)
    def test_send_disconnect_request_ok(self, requests_mock, http_header_mock):
        token = FakeToken('tokenrawdata')
        expected_url = '{}{}{}'.format(
            usmcentral_http_proxy.USMCENTRAL_HTTP_PROTOCOL,
            token.issuer,
            usmcentral_http_proxy.USMCENTRAL_HTTP_DISCONNECT_PATH
        )
        http_header_mock.return_value = expected_headers = MagicMock()
        requests_mock.post.return_value = expected_response = MagicMock()

        self.proxy.send_disconnect_notification(token)

        http_header_mock.assert_called_once_with(token)
        requests_mock.post.assert_called_with(
            url=expected_url,
            headers=expected_headers,
            timeout=usmcentral_http_proxy.USMCENTRAL_HTTP_TIMEOUT_SEC
        )
        expected_response.raise_for_status.assert_called_once_with()

    @patch.object(usmcentral_http_proxy, 'requests', autospec=True)
    def test_send_disconnect_request_not_ok(self, requests_mock):
        token = FakeToken('tokenrawdata')
        requests_mock.post.return_value = expected_response = MagicMock()
        expected_response.raise_for_status.side_effect = expected_exception = Exception('error')
        self.assertRaises(type(expected_exception), self.proxy.send_disconnect_notification, token)

    def test_adapt_platform(self):
        for source_platform, expected_adapted_platform in usmcentral_http_proxy.PLATFORMS_MAP.iteritems():
            actual_adapted_platform = usmcentral_http_proxy.adapt_platform(source_platform)
            self.assertEqual(actual_adapted_platform, expected_adapted_platform)

    def test_adapt_platform_unknown(self):
        adapted_platform = usmcentral_http_proxy.adapt_platform('abcdefg')
        self.assertIsNone(adapted_platform)


if __name__ == '__main__':
    unittest.main()
