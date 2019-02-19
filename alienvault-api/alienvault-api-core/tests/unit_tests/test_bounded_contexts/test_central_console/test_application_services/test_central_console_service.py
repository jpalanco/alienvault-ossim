import unittest

from mock import MagicMock, patch

from bounded_contexts.central_console.application_services import central_console_service
from bounded_contexts.central_console.application_services.abstract_console_proxy import AbstractConsoleProxy
from bounded_contexts.central_console.models.abstract_console_token import AbstractConsoleToken
from bounded_contexts.central_console.domain_services.token_repository import TokenRepository
from bounded_contexts.central_console.domain_services.deployment_info_repository import DeploymentInfoRepository


class MockConsoleToken(AbstractConsoleToken):

    def __init__(self, raw_data):
        self.__raw_data = raw_data
        self.__issuer = 'issuer'

    @property
    def issuer(self):
        return self.__issuer


class TestCentralConsoleService(unittest.TestCase):

    def setUp(self):
        self.token = MockConsoleToken('rawtokendata')
        self.token_repository = MagicMock(spec=TokenRepository)
        self.deployment_repository = MagicMock(spec=DeploymentInfoRepository)
        self.console_proxy = MagicMock(spec=AbstractConsoleProxy)

        self.console_service = central_console_service.CentralConsoleService(
            self.token_repository,
            self.deployment_repository,
            self.console_proxy
        )

    def test_console_get_status_token_not_configured(self):
        expected_status = central_console_service.CONSOLE_CONNECTION_NOT_CONFIGURED
        self.token_repository.get_token.return_value = expected_token = None

        actual_status = self.console_service.get_console_status()

        self.token_repository.get_token.assert_called_once_with()
        self.console_proxy.send_deployment_info.assert_not_called()
        self.assertEqual(actual_status.status, expected_status)
        self.assertEqual(actual_status.token, expected_token)

    def test_console_get_status_token_ok(self):
        expected_status = central_console_service.CONSOLE_CONNECTION_OK
        self.token_repository.get_token.return_value = expected_token = self.token
        self.console_proxy.send_deployment_info.return_value = True

        actual_status = self.console_service.get_console_status()

        self.token_repository.get_token.assert_called_once_with()
        self.deployment_repository.get_deployment_info.assert_called_once_with()
        self.assertEqual(actual_status.status, expected_status)
        self.assertEqual(actual_status.token, expected_token)

    def test_console_get_status_connection_denied(self):
        expected_status = central_console_service.CONSOLE_CONNECTION_DENIED
        self.token_repository.get_token.return_value = expected_token = self.token
        self.console_proxy.send_deployment_info.return_value = False

        actual_status = self.console_service.get_console_status()

        self.token_repository.get_token.assert_called_once_with()
        self.assertEqual(actual_status.status, expected_status)
        self.assertEqual(actual_status.token, expected_token)

    def test_console_get_status_token_repository_exception(self):
        self.token_repository.get_token.side_effect = expected_exception = Exception('error')
        self.assertRaises(type(expected_exception), self.console_service.get_console_status)

    def test_console_get_status_deployment_repository_exception(self):
        self.deployment_repository.get_deployment_info.side_effect = expected_exception = Exception('error')
        self.assertRaises(type(expected_exception), self.console_service.get_console_status)

    @patch.object(central_console_service, 'format_exc', autoscpe=True)
    @patch.object(central_console_service, 'api_log', autospec=True)
    def test_console_get_status_proxy_send_exception(self, api_log_mock, format_exc_mock):
        expected_status = central_console_service.CONSOLE_CONNECTION_FAILED
        self.token_repository.get_token.return_value = expected_token = self.token
        self.console_proxy.send_deployment_info.side_effect = expected_exc = Exception('error')

        actual_status = self.console_service.get_console_status()

        self.token_repository.get_token.assert_called_once_with()
        self.assertEqual(actual_status.status, expected_status)
        self.assertEqual(actual_status.token, expected_token)
        format_exc_mock.assert_called_once_with(expected_exc)
        api_log_mock.error.assert_called_once_with(format_exc_mock.return_value)

    def test_register_console_success(self):
        expected_deployment_info = self.deployment_repository.get_deployment_info.return_value
        self.console_proxy.send_deployment_info.return_value = True
        self.token_repository.add_token.return_value = None
        expected_status = central_console_service.CONSOLE_CONNECTION_OK

        console_status = self.console_service.register_console(self.token)

        self.deployment_repository.get_deployment_info.assert_called_once_with()
        self.console_proxy.send_deployment_info.assert_called_once_with(self.token, expected_deployment_info)
        self.token_repository.add_token.assert_called_once_with(self.token)
        self.assertEqual(console_status.status, expected_status)
        self.assertEqual(console_status.token, self.token)

    def test_register_console_token_rejected(self):
        expected_deployment_info = self.deployment_repository.get_deployment_info.return_value
        self.console_proxy.send_deployment_info.return_value = False
        expected_status = central_console_service.CONSOLE_TOKEN_REJECTED

        console_status = self.console_service.register_console(self.token)

        self.console_proxy.send_deployment_info.assert_called_once_with(self.token, expected_deployment_info)
        self.token_repository.add_token.assert_not_called()
        self.assertEqual(console_status.status, expected_status)
        self.assertEqual(console_status.token, self.token)

    def test_register_console_deployment_repository_exception(self):
        self.deployment_repository.get_deployment_info.side_effect = expected_exception = Exception('error')

        self.assertRaises(type(expected_exception), self.console_service.register_console, self.token)

        self.token_repository.add_token.assert_not_called()

    @patch.object(central_console_service, 'format_exc', autoscpe=True)
    @patch.object(central_console_service, 'api_log', autospec=True)
    def test_register_console_proxy_error(self, api_log_mock, format_exc_mock):
        expected_status = central_console_service.CONSOLE_TOKEN_ISSUER_NOT_REACHABLE
        self.console_proxy.send_deployment_info.side_effect = expected_exception = Exception('error')

        actual_data = self.console_service.register_console(self.token)

        format_exc_mock.assert_called_once_with(expected_exception)
        api_log_mock.error.assert_called_once_with(format_exc_mock.return_value)
        self.assertEqual(actual_data.status, expected_status)
        self.assertEqual(actual_data.token, self.token)
        self.token_repository.add_token.assert_not_called()

    def test_register_console_token_repository_error(self):
        self.console_proxy.send_deployment_info.return_value = True
        self.token_repository.add_token.side_effect = expected_exception = Exception('error')
        self.assertRaises(type(expected_exception), self.console_service.register_console, self.token)

    @patch.object(central_console_service, 'api_log', autospec=True)
    def test_unregister_console(self, api_log_mock):
        expected_status = central_console_service.CONSOLE_CONNECTION_NOT_CONFIGURED
        expected_token = None
        self.token_repository.get_token.return_value = self.token

        actual_data = self.console_service.unregister_console()

        self.token_repository.get_token.assert_called_once_with()
        self.console_proxy.send_disconnect_notification.assert_called_once_with(self.token)
        self.token_repository.delete_token.assert_called_once_with()
        self.assertEqual(actual_data.status, expected_status)
        self.assertEqual(actual_data.token, expected_token)

    @patch.object(central_console_service, 'api_log', autospec=True)
    def test_unregister_console_no_token(self, api_log_mock):
        self.token_repository.get_token.return_value = None

        self.assertRaises(Exception, self.console_service.unregister_console)

        self.console_proxy.send_disconnect_notification.assert_not_called()
        self.token_repository.delete_token.assert_not_called()

    def test_unregister_console_get_token_exception(self):
        self.token_repository.get_token.side_effect = expected_exception = Exception('error')
        self.assertRaises(type(expected_exception), self.console_service.unregister_console)

    @patch.object(central_console_service, 'api_log', autospec=True)
    def test_unregister_console_proxy_exception(self, api_log_mock):
        expected_status = central_console_service.CONSOLE_CONNECTION_NOT_CONFIGURED
        expected_token = None
        self.console_proxy.send_disconnect_notification.side_effect = Exception('error')

        actual_data = self.console_service.unregister_console()

        api_log_mock.warning.assert_called_once()
        self.token_repository.delete_token.assert_called_once_with()
        self.assertEqual(actual_data.status, expected_status)
        self.assertEqual(actual_data.token, expected_token)

    def test_unregister_console_delete_token_exception(self):
        self.token_repository.delete_token.side_effect = expected_exception = Exception('error')
        self.assertRaises(type(expected_exception), self.console_service.unregister_console)


if __name__ == '__main__':
    unittest.main()
