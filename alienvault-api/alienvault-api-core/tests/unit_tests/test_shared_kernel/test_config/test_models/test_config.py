import unittest

from shared_kernel.config.models import config


class TestConfig(unittest.TestCase):

    def test_config(self):
        expected_conf = 'conf'
        expected_value = 'value'

        actual_config = config.Config(expected_conf, expected_value)

        self.assertEqual(actual_config.conf, expected_conf)
        self.assertEqual(actual_config.value, expected_value)


if __name__ == '__main__':
    unittest.main()
