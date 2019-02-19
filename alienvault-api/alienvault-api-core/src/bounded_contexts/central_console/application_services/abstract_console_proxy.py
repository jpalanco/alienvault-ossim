from abc import ABCMeta, abstractmethod


class AbstractConsoleProxy(object):
    __metaclass__ = ABCMeta

    @abstractmethod
    def send_deployment_info(self, token, deployment_info):
        pass

    @abstractmethod
    def send_disconnect_notification(self, token):
        pass
