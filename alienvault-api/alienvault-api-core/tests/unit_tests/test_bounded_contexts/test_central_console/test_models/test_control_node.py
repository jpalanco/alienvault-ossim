import unittest

from bounded_contexts.central_console.models.control_node import ControlNode


class TestControlNode(unittest.TestCase):

    def test_control_node_create(self):
        expected_node_id = 'node id'
        expected_name = 'name'
        expected_description = 'description'
        expected_platform = 'platform'
        expected_appliance_type = 'appliance type'
        expected_software_version = 'software version'
        expected_intelligence_version = 'intelligence version'
        expected_contact_email = 'contact email'
        expected_contact_name = 'contact name'
        expected_admin_ip_address = 'admin ip address'
        expected_vpn_ip_address = 'vpn ip address'

        actual_node = ControlNode(
            expected_node_id,
            expected_name,
            expected_description,
            expected_platform,
            expected_appliance_type,
            expected_software_version,
            expected_intelligence_version,
            expected_contact_email,
            expected_contact_name,
            expected_admin_ip_address,
            expected_vpn_ip_address
        )

        self.assertEqual(actual_node.node_id, expected_node_id)
        self.assertEqual(actual_node.name, expected_name)
        self.assertEqual(actual_node.description, expected_description)
        self.assertEqual(actual_node.platform, expected_platform)
        self.assertEqual(actual_node.appliance_type, expected_appliance_type)
        self.assertEqual(actual_node.software_version, expected_software_version)
        self.assertEqual(actual_node.intelligence_version, expected_intelligence_version)
        self.assertEqual(actual_node.contact_email, expected_contact_email)
        self.assertEqual(actual_node.contact_name, expected_contact_name)
        self.assertEqual(actual_node.admin_ip_address, expected_admin_ip_address)
        self.assertEqual(actual_node.vpn_ip_address, expected_vpn_ip_address)


if __name__ == '__main__':
    unittest.main()
