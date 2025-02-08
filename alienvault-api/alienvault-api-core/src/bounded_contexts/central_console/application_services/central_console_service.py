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

    def __console_token_status(self, token):
        # If token exists, connection is established, so return corresponding statuses below.
        try:
            token_accepted = self.__console_proxy.get_token_status(token)
        except Exception as exc:
            api_log.error(format_exc(exc))
            return CentralConsoleStatus(CONSOLE_CONNECTION_FAILED, token)

        if token_accepted:
            return CentralConsoleStatus(CONSOLE_CONNECTION_OK, token)
        else:
            return CentralConsoleStatus(CONSOLE_CONNECTION_DENIED, token)

    def __console_send_deployment_info(self, token, status):
        # If token exists, connection is established, so return corresponding statuses below.
        try:
            token_accepted = self.__console_proxy.send_deployment_info(token, status)
        except Exception as exc:
            api_log.error(format_exc(exc))
            return CentralConsoleStatus(CONSOLE_CONNECTION_FAILED, token)

        if token_accepted:
            return CentralConsoleStatus(CONSOLE_CONNECTION_OK, token)
        else:
            return CentralConsoleStatus(CONSOLE_CONNECTION_DENIED, token)


    def get_console_status(self):
        #checking the system token before sending anything
        token = self.__token_repository.get_token()

        if token is None:
            return CentralConsoleStatus(CONSOLE_CONNECTION_NOT_CONFIGURED, None)

        #Since the new entry point is available we send empty payload to check the token status.
        return self.__console_token_status(token)

    def send_console_status(self):
        #checking the system token before sending anything
        token = self.__token_repository.get_token()

        if token is None:
            return CentralConsoleStatus(CONSOLE_CONNECTION_NOT_CONFIGURED, None)

        deployment_info = self.__deployment_repository.get_deployment_info()
        return self.__console_send_deployment_info(token, deployment_info)

    def register_console(self, token):
        #checking if the provided token is valid so the token is not in the system

        # Until token is added to the repository, the connection is considered as a not configured one.
        # So, return corresponding statuses below.
        console_status = self.__console_token_status(token)

        if console_status.status != CONSOLE_CONNECTION_OK:
            return console_status

        #adding the token to the repository
        self.__token_repository.add_token(token)

        return console_status

    def unregister_console(self):
        token = self.__token_repository.get_token()
        if token is None:
            raise Exception('unregister_console failed: token was not found')

        try:
            api_log.info('Trying to disconnect from USM Central...')
            self.__console_proxy.send_disconnect_notification(token)
        except Exception as exc:
            # Disconnect notification is not obligatory, so it should not block the unregistration.
            # This allows to unregister from the console which is not accessible without extending
            # business logic with something like 'force unregister'.
            api_log.warning('Unable to send disconnect notification to USM Central, ignoring. More details: {}'.format(format_exc(exc)))
        finally:
            self.__token_repository.delete_token()
            api_log.info('Disconnected from USM Central successfully.')
            return CentralConsoleStatus(CONSOLE_CONNECTION_NOT_CONFIGURED, None)
