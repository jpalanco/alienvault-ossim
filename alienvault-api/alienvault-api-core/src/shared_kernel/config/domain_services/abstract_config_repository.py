from abc import ABCMeta, abstractmethod


class ConfigAlreadyExistsError(Exception):
    def __init__(self):
        message = 'Config value already exists. Only one is supported.'
        super(ConfigAlreadyExistsError, self).__init__(message)


class ConfigNotFoundError(Exception):
    def __init__(self):
        message = 'Config value was not found.'
        super(ConfigNotFoundError, self).__init__(message)


class AbstractConfigRepository(object):

    __metaclass__ = ABCMeta

    def __init__(self, constructor):
        self._construct_config = constructor

    @property
    def config_constructor(self):
        return self._construct_config

    @abstractmethod
    def get_config(self, conf_name):
        pass

    @abstractmethod
    def add_config(self, conf):
        pass

    @abstractmethod
    def delete_config(self, conf):
        pass
