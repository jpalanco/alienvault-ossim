class CentralConsoleStatus(object):

    def __init__(self, status, token):
        self.__status = status
        self.__token = token

    @property
    def status(self):
        return self.__status

    @property
    def token(self):
        return self.__token
