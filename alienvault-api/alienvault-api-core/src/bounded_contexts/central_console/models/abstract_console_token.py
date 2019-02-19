from abc import ABCMeta, abstractproperty


class AbstractConsoleToken(object):
    __metaclass__ = ABCMeta

    def __init__(self, raw_data):
        self.__raw_data = raw_data

    @property
    def raw_data(self):
        return self.__raw_data

    @abstractproperty
    def issuer(self):
        pass
