import unittest

from bounded_contexts.central_console.models.contact_person import ContactPerson
from infrastructure.bounded_contexts.central_console.domain_services.abstract_contact_person_repository import \
    alchemy_contact_person_repository
from integration_tests.alchemy_test_case import AlchemyTestCase


class TestContactPersonRepository(AlchemyTestCase):

    def setUp(self):
        self.assert_parameters()
        self.set_up_db()
        self.set_up_db_schema()
        self.set_up_alchemy(alchemy_contact_person_repository)

        self.alchemy_repository = alchemy_contact_person_repository.AlchemyContactPersonRepository(ContactPerson)

    def set_up_db_schema(self):
        self.expected_user_name = 'name'
        self.expected_user_email = 'name@example.com'
        self.cursor.execute("""CREATE TABLE `users` (
              `login` varchar(64) NOT NULL,
              `ctx` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
              `name` varchar(128) NOT NULL,
              `pass` varchar(128) NOT NULL,
              `email` varchar(255) DEFAULT NULL,
              `company` varchar(128) DEFAULT NULL,
              `department` varchar(128) DEFAULT NULL,
              `language` varchar(12) NOT NULL DEFAULT 'en_GB',
              `enabled` tinyint(1) NOT NULL DEFAULT '1',
              `first_login` tinyint(1) NOT NULL DEFAULT '1',
              `timezone` varchar(64) NOT NULL DEFAULT 'GMT',
              `last_pass_change` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `last_logon_try` datetime NOT NULL,
              `is_admin` tinyint(1) NOT NULL DEFAULT '0',
              `template_id` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
              `uuid` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
              `expires` datetime NOT NULL DEFAULT '2200-01-01 00:00:00',
              `login_method` varchar(4) NOT NULL,
              `salt` text NOT NULL,
              PRIMARY KEY (`login`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"""
                            )
        self.cursor.execute("""
        INSERT INTO users (login, name, pass, email, last_logon_try, is_admin, login_method, salt) 
        VALUES ('login', %s, 'pass', %s, CURRENT_TIMESTAMP, 1, 'abc', 'saltsaltsalt')
        """, (self.expected_user_name, self.expected_user_email))
        self.db.commit()

    def test_get_user(self):
        actual_contact_person = self.alchemy_repository.get_contact_person()

        self.assertEqual(actual_contact_person.email, self.expected_user_email)
        self.assertEqual(actual_contact_person.name, self.expected_user_name)


if __name__ == '__main__':
    unittest.main()
