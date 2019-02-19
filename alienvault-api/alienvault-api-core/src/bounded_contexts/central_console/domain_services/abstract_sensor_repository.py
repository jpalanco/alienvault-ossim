from abc import ABCMeta, abstractmethod


class AbstractSensorRepository(object):

    __metaclass__ = ABCMeta

    def __init__(self, sensor_constructor, platform_repository):
        self._sensor_constructor = sensor_constructor
        self._platform_repository = platform_repository

    @abstractmethod
    def get_sensors(self):
        pass
