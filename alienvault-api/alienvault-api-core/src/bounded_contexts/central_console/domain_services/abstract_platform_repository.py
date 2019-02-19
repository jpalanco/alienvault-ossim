from abc import ABCMeta, abstractmethod


class AbstractPlatformRepository(object):

    __metaclass__ = ABCMeta

    def __init__(self, platform_constructor, config_repository):
        self._platform_constructor = platform_constructor
        self._config_repository = config_repository

    @abstractmethod
    def get_platform(self, ip_str):
        pass
