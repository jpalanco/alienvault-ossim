from abc import ABCMeta, abstractmethod


class AbstractSystemRepository(object):

    __metaclass__ = ABCMeta

    def __init__(self, system_constructor):
        self._system_constructor = system_constructor

    @abstractmethod
    def get_system(self):
        pass
