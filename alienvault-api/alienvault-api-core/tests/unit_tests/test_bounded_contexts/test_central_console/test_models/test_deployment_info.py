import unittest

from mock import MagicMock

from bounded_contexts.central_console.models import deployment_info


class TestDeploymentInfo(unittest.TestCase):

    def test_deployment_info(self):
        expected_control_node = MagicMock()
        expected_license = MagicMock()
        expected_sensors = [MagicMock(), MagicMock()]

        actual_info = deployment_info.DeploymentInfo(expected_control_node, expected_license, expected_sensors)

        self.assertEqual(actual_info.control_node, expected_control_node)
        self.assertEqual(actual_info.license, expected_license)
        self.assertEqual(actual_info.sensors, expected_sensors)


if __name__ == '__main__':
    unittest.main()
