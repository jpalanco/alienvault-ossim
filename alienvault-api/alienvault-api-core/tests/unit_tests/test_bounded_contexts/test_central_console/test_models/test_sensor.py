import unittest

from mock import MagicMock

from bounded_contexts.central_console.models import sensor


class TestSensor(unittest.TestCase):

    def test_something(self):
        expected_sensor_id = MagicMock()
        expected_name = MagicMock()
        expected_description = MagicMock()
        expected_platform = MagicMock()
        expected_ip_address = MagicMock()
        expected_software_version = MagicMock()
        expected_intelligence_version = MagicMock()
        expected_is_connected = MagicMock()

        actual_sensor = sensor.Sensor(
            expected_sensor_id,
            expected_name,
            expected_description,
            expected_platform,
            expected_ip_address,
            expected_software_version,
            expected_intelligence_version,
            expected_is_connected
        )

        self.assertEqual(actual_sensor.sensor_id, expected_sensor_id)
        self.assertEqual(actual_sensor.name, expected_name)
        self.assertEqual(actual_sensor.description, expected_description)
        self.assertEqual(actual_sensor.platform, expected_platform)
        self.assertEqual(actual_sensor.ip_address, expected_ip_address)
        self.assertEqual(actual_sensor.software_version, expected_software_version)
        self.assertEqual(actual_sensor.threat_intelligence_version, expected_intelligence_version)
        self.assertEqual(actual_sensor.is_connected, expected_is_connected)


if __name__ == '__main__':
    unittest.main()
