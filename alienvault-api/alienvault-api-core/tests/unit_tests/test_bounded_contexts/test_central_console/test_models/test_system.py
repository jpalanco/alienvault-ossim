import unittest

from mock import MagicMock

from bounded_contexts.central_console.models.system import System


class TestSystem(unittest.TestCase):

    def test_instance(self):
        expected_id = MagicMock(name='id')
        expected_name = MagicMock(name='name')
        expected_admin_ip = MagicMock(name='admin_ip')
        expected_vpn_ip = MagicMock(name='vpn_ip')
        expected_ha_ip = MagicMock(name='ha_ip')

        actual_system = System(expected_id, expected_name, expected_admin_ip, expected_vpn_ip, expected_ha_ip)

        self.assertEqual(actual_system.id, expected_id)
        self.assertEqual(actual_system.name, expected_name)
        self.assertEqual(actual_system.admin_ip, expected_admin_ip)
        self.assertEqual(actual_system.vpn_ip, expected_vpn_ip)
        self.assertEqual(actual_system.ha_ip, expected_ha_ip)


if __name__ == '__main__':
    unittest.main()
