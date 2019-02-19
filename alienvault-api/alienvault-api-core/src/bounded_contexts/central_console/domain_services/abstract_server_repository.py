from abc import ABCMeta, abstractmethod


class AbstractServerRepository(object):
    __metaclass__ = ABCMeta

    def __init__(self, server_constructor):
        self._server_constructor = server_constructor

    @abstractmethod
    def get_server(self, server_id):
        pass
