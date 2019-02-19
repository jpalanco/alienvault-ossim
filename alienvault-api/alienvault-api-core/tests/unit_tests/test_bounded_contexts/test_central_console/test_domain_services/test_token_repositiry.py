import unittest

from mock import MagicMock

from bounded_contexts.central_console.models.abstract_console_token import AbstractConsoleToken
from bounded_contexts.central_console.domain_services import token_repository
from shared_kernel.config.models.config import Config
from shared_kernel.config.domain_services.abstract_config_repository import AbstractConfigRepository,\
    ConfigNotFoundError, ConfigAlreadyExistsError


class TestTokenRepository(unittest.TestCase):

    def setUp(self):
        self.config_repository = MagicMock(spec=AbstractConfigRepository)
        self.token_constructor = MagicMock(spec=AbstractConsoleToken)
        self.repository = token_repository.TokenRepository(self.token_constructor, self.config_repository)

    def test_get_token(self):
        expected_raw_data = 'token'
        self.token_constructor.return_value = expected_token = MagicMock(spec=AbstractConsoleToken)
        self.config_repository.get_config.return_value = Config(token_repository.TOKEN_CONFIG_NAME, expected_raw_data)

        actual_token = self.repository.get_token()

        self.config_repository.get_config.assert_called_once_with(token_repository.TOKEN_CONFIG_NAME)
        self.token_constructor.assert_called_once_with(expected_raw_data)
        self.assertEqual(actual_token, expected_token)

    def test_get_token_none(self):
        self.config_repository.get_config.return_value = None
        actual_token = self.repository.get_token()
        self.assertTrue(actual_token is None)

    def test_add_token(self):
        expected_token = MagicMock(spec=AbstractConsoleToken)
        self.config_repository.config_constructor = MagicMock(spec=Config)

        self.repository.add_token(expected_token)

        self.config_repository.config_constructor.assert_called_once_with(
            token_repository.TOKEN_CONFIG_NAME,
            expected_token.raw_data
        )
        self.config_repository.add_config.assert_called_once_with(
            self.config_repository.config_constructor.return_value
        )

    def test_add_token_already_exists_error(self):
        self.config_repository.add_config.side_effect = ConfigAlreadyExistsError()
        self.assertRaises(
            token_repository.TokenAlreadyExistsError,
            self.repository.add_token,
            MagicMock(spec=AbstractConsoleToken)
        )

    def test_delete_token(self):
        token_to_delete = MagicMock(spec=AbstractConsoleToken)

        self.repository.delete_token()

        self.config_repository.get_config.assert_called_once_with(
            token_repository.TOKEN_CONFIG_NAME
        )
        self.config_repository.delete_config.assert_called_once_with(
            self.config_repository.get_config.return_value
        )

    def test_delete_token_token_not_found_exception(self):
        self.config_repository.delete_config.side_effect = ConfigNotFoundError()
        self.assertRaises(
            token_repository.TokenNotFoundError,
            self.repository.delete_token
        )


if __name__ == '__main__':
    unittest.main()
