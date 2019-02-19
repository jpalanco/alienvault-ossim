import unittest

from mock import MagicMock

from bounded_contexts.central_console.application_services.central_console_status import CentralConsoleStatus
from bounded_contexts.central_console.models.abstract_console_token import AbstractConsoleToken


class TestCentralConsoleStatus(unittest.TestCase):

    def test_get_status(self):
        expected_status = 'status'
        token = MagicMock(spec=AbstractConsoleToken)

        console_status = CentralConsoleStatus(expected_status, token)

        self.assertEqual(expected_status, console_status.status)

    def test_get_token(self):
        expected_token = MagicMock(spec=AbstractConsoleToken)

        console_status = CentralConsoleStatus('status', expected_token)

        self.assertEqual(expected_token, console_status.token)


if __name__ == '__main__':
    unittest.main()
