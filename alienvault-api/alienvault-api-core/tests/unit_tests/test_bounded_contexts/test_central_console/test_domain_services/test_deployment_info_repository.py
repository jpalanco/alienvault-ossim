import unittest

from mock import MagicMock

from bounded_contexts.central_console.domain_services import control_node_repository
from bounded_contexts.central_console.domain_services import deployment_info_repository
from bounded_contexts.central_console.domain_services import license_repository
from bounded_contexts.central_console.domain_services import abstract_sensor_repository
from bounded_contexts.central_console.models import deployment_info


class TestDeploymentInfoRepository(unittest.TestCase):

    def setUp(self):
        self.deployment_info_constructor = MagicMock(spec=deployment_info.DeploymentInfo)
        self.control_node_repo = MagicMock(spec=control_node_repository.ControlNodeRepository)
        self.license_repo = MagicMock(spec=license_repository.LicenseRepository)
        self.sensor_repo = MagicMock(spec=abstract_sensor_repository.AbstractSensorRepository)

        self.deployment_info_repo = deployment_info_repository.DeploymentInfoRepository(
            self.deployment_info_constructor,
            self.control_node_repo,
            self.license_repo,
            self.sensor_repo
        )

    def test_get_deployment_info(self):
        self.deployment_info_constructor.return_value = expected_info = MagicMock()
        self.control_node_repo.get_control_node.return_value = expected_control_node = MagicMock()
        self.license_repo.get_license.return_value = expected_license = MagicMock()
        self.sensor_repo.get_sensors.return_value = expected_sensors = [MagicMock(), MagicMock()]

        actual_info = self.deployment_info_repo.get_deployment_info()

        self.deployment_info_constructor.assert_called_once_with(
            expected_control_node,
            expected_license,
            expected_sensors
        )
        self.assertEqual(actual_info, expected_info)


if __name__ == '__main__':
    unittest.main()
