import unittest

from mock import MagicMock

from bounded_contexts.central_console.models import sensor
from bounded_contexts.central_console.domain_services import abstract_sensor_repository
from shared_kernel.config.domain_services.abstract_config_repository import AbstractConfigRepository


class ConcreteSensorRepository(abstract_sensor_repository.AbstractSensorRepository):

    def get_sensors(self):
        pass


class TestAbstractSensorRepository(unittest.TestCase):

    def test_abstract(self):
        self.assertRaises(
            TypeError,
            abstract_sensor_repository.AbstractSensorRepository,
            MagicMock(spec=sensor.Sensor),
            MagicMock(spec=AbstractConfigRepository)
        )

    def test_concrete(self):
        ConcreteSensorRepository(MagicMock(spec=sensor.Sensor), MagicMock(spec=AbstractConfigRepository))


if __name__ == '__main__':
    unittest.main()
