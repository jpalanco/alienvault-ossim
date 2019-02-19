import unittest

from mock import MagicMock

from bounded_contexts.central_console.models.contact_person import ContactPerson


class TestContactPerson(unittest.TestCase):

    def test_instance(self):
        expected_email = MagicMock()
        expected_name = MagicMock()

        actual_user = ContactPerson(expected_email, expected_name)

        self.assertEqual(actual_user.email, expected_email)
        self.assertEqual(actual_user.name, expected_name)


if __name__ == '__main__':
    unittest.main()
