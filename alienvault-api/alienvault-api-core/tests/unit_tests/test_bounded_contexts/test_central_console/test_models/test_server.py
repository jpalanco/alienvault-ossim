import unittest

from mock import MagicMock

from bounded_contexts.central_console.models import server


class TestServer(unittest.TestCase):

    def test_instance(self):
        expected_descr = MagicMock()
        expected_ip = MagicMock()

        actual_server = server.Server(expected_descr, expected_ip)

        self.assertEqual(actual_server.descr, expected_descr)
        self.assertEqual(actual_server.ip, expected_ip)


if __name__ == '__main__':
    unittest.main()
