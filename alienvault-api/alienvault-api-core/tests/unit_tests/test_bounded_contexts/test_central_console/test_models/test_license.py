import unittest

from mock import MagicMock

from bounded_contexts.central_console.models import license


class TestLicense(unittest.TestCase):

    def test_license(self):
        expected_is_trial = MagicMock()
        expected_expires_on = MagicMock()
        expected_devices = MagicMock()

        actual_license = license.License(expected_is_trial, expected_expires_on, expected_devices)

        self.assertEqual(actual_license.is_trial, expected_is_trial)
        self.assertEqual(actual_license.expires_on, expected_expires_on)
        self.assertEqual(actual_license.devices, expected_devices)

if __name__ == '__main__':
    unittest.main()
