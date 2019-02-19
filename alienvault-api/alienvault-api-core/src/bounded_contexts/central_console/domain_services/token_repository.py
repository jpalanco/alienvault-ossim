from shared_kernel.config.domain_services.abstract_config_repository import ConfigNotFoundError,\
    ConfigAlreadyExistsError

TOKEN_CONFIG_NAME = 'usm_central_token'


class TokenAlreadyExistsError(Exception):
    def __init__(self):
        message = 'Token already exists. Only one is supported.'
        super(TokenAlreadyExistsError, self).__init__(message)


class TokenNotFoundError(Exception):
    def __init__(self):
        message = 'Token was not found.'
        super(TokenNotFoundError, self).__init__(message)


class TokenRepository(object):

    def __init__(self, constructor, config_repository):
        self.__construct_token = constructor
        self.__config_repository = config_repository

    def get_token(self):
        alchemy_token = self.__config_repository.get_config(TOKEN_CONFIG_NAME)
        return alchemy_token and self.__construct_token(alchemy_token.value)

    def add_token(self, token):
        config_entity = self.__config_repository.config_constructor(TOKEN_CONFIG_NAME, token.raw_data)

        try:
            self.__config_repository.add_config(config_entity)
        except ConfigAlreadyExistsError:
            raise TokenAlreadyExistsError

    def delete_token(self):
        config_entity = self.__config_repository.get_config(TOKEN_CONFIG_NAME)
        try:
            self.__config_repository.delete_config(config_entity)
        except ConfigNotFoundError:
            raise TokenNotFoundError
