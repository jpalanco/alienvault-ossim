import unittest

from mock import MagicMock

from bounded_contexts.central_console.models.platform import Platform


class TestPlatform(unittest.TestCase):

    def test_instance(self):
        expected_name = MagicMock(name='name')
        expected_threat_intelligence_version = MagicMock(name='threat_intelligence_version')
        expected_appliance_type = MagicMock(name='appliance_type')
        expected_public_ip = MagicMock(name='public_ip')

        actual_platform = Platform(
            expected_name,
            expected_threat_intelligence_version,
            expected_appliance_type,
            expected_public_ip
        )

        self.assertEqual(actual_platform.name, expected_name)
        self.assertEqual(actual_platform.threat_intelligence_version, expected_threat_intelligence_version)
        self.assertEqual(actual_platform.appliance_type, expected_appliance_type)
        self.assertEqual(actual_platform.public_ip, expected_public_ip)


if __name__ == '__main__':
    unittest.main()
