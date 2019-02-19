import jwt

from bounded_contexts.central_console.models.abstract_console_token import AbstractConsoleToken


ISSUER_FIELD_NAME = 'msspId'


class USMCentralJwtToken(AbstractConsoleToken):

    def __init__(self, raw_data):
        super(USMCentralJwtToken, self).__init__(raw_data.strip())
        self.__issuer = jwt.decode(self.raw_data, verify=False).get(ISSUER_FIELD_NAME)

    @property
    def issuer(self):
        return self.__issuer
