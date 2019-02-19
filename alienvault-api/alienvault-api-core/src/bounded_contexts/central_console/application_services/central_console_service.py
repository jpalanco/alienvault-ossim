from traceback import format_exc

import api_log
from bounded_contexts.central_console.application_services.central_console_status import CentralConsoleStatus

# Statuses for configured connection
CONSOLE_CONNECTION_OK = 'ok'
CONSOLE_CONNECTION_DENIED = 'connection_denied'
CONSOLE_CONNECTION_FAILED = 'connection_failed'

# Statuses for not configured connection
CONSOLE_CONNECTION_NOT_CONFIGURED = 'not_configured'
CONSOLE_TOKEN_ISSUER_NOT_REACHABLE = 'token_issuer_not_reachable'
CONSOLE_TOKEN_REJECTED = 'token_rejected'


class CentralConsoleService(object):

    def __init__(self, token_repository, deployment_repository, console_proxy):
        self.__token_repository = token_repository
        self.__deployment_repository = deployment_repository
        self.__console_proxy = console_proxy

    def get_console_status(self):
        token = self.__token_repository.get_token()
        deployment_info = self.__deployment_repository.get_deployment_info()

        if token is None:
            return CentralConsoleStatus(CONSOLE_CONNECTION_NOT_CONFIGURED, None)

        # If token exists, connection is established, so return corresponding statuses below.
        try:
            token_accepted = self.__console_proxy.send_deployment_info(token, deployment_info)
        except Exception as exc:
            api_log.error(format_exc(exc))
            return CentralConsoleStatus(CONSOLE_CONNECTION_FAILED, token)

        if token_accepted:
            return CentralConsoleStatus(CONSOLE_CONNECTION_OK, token)
        else:
            return CentralConsoleStatus(CONSOLE_CONNECTION_DENIED, token)

    def register_console(self, token):
        deployment_info = self.__deployment_repository.get_deployment_info()

        # Until token is added to the repository, the connection is considered as a not configured one.
        # So, return corresponding statuses below.
        try:
            token_accepted = self.__console_proxy.send_deployment_info(token, deployment_info)
        except Exception as exc:
            api_log.error(format_exc(exc))
            return CentralConsoleStatus(CONSOLE_TOKEN_ISSUER_NOT_REACHABLE, token)

        if not token_accepted:
            return CentralConsoleStatus(CONSOLE_TOKEN_REJECTED, token)

        self.__token_repository.add_token(token)

        return CentralConsoleStatus(CONSOLE_CONNECTION_OK, token)

    def unregister_console(self):
        token = self.__token_repository.get_token()
        if token is None:
            raise Exception('unregister_console failed: token was not found')

        try:
            self.__console_proxy.send_disconnect_notification(token)
        except Exception as exc:
            # Disconnect notification is not obligatory, so it should not block the unregistration.
            # This allows to unregister from the console which is not accessible without extending
            # business logic with something like 'force unregister'.
            api_log.warning('Disconnect notification failed, ignoring. Error details: {}'.format(format_exc(exc)))
        finally:
            self.__token_repository.delete_token()
            return CentralConsoleStatus(CONSOLE_CONNECTION_NOT_CONFIGURED, None)
