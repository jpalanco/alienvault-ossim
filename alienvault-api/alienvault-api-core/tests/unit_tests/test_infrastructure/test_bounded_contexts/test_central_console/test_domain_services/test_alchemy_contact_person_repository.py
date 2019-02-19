import unittest

from mock import patch, MagicMock
from sqlalchemy.orm import Query

from bounded_contexts.central_console.models.contact_person import ContactPerson
from infrastructure.bounded_contexts.central_console.domain_services.abstract_contact_person_repository\
    import alchemy_contact_person_repository
from unit_tests.alchemy_test_case import AlchemyTestCase


class TestAlchemyContactPersonRepository(AlchemyTestCase):

    def setUp(self):
        self.setup_require_db_decorator_mock(alchemy_contact_person_repository)

        self.contact_person_constructor = MagicMock(spec=ContactPerson)
        self.repo = alchemy_contact_person_repository.AlchemyContactPersonRepository(self.contact_person_constructor)

    def test_get_contact_person_decorated(self):
        self.assert_require_db_decorated(
            alchemy_contact_person_repository.AlchemyContactPersonRepository.get_contact_person.__func__
        )

    @patch.object(alchemy_contact_person_repository, 'Users', autospec=True)
    @patch('db.session', autospec=True)
    def test_get_contact_person(self, session_mock, alchemy_users_mock):
        session_mock.query.return_value = query_mock = MagicMock(spec=Query)
        query_mock.filter.return_value = filtered_query_mock = MagicMock(spec=Query)
        alchemy_users_mock.is_admin.__eq__.return_value = MagicMock()
        alchemy_users_mock.enabled.__eq__.return_value = MagicMock()
        filtered_query_mock.first.return_value = expected_user_info = [MagicMock(), MagicMock()]

        actual_user_info = self.repo.get_contact_person()

        session_mock.query.assert_called_once_with(alchemy_users_mock.email, alchemy_users_mock.name)
        query_mock.filter.assert_called_once_with(
            alchemy_users_mock.is_admin.__eq__.return_value,
            alchemy_users_mock.enabled.__eq__.return_value
        )
        alchemy_users_mock.is_admin.__eq__.assert_called_once_with(
            alchemy_contact_person_repository.USERS_IS_ADMIN_FILTER
        )
        alchemy_users_mock.enabled.__eq__.assert_called_once_with(
            alchemy_contact_person_repository.USERS_ENABLED_FILTER
        )
        filtered_query_mock.first.assert_called_once_with()
        self.contact_person_constructor.assert_called_once_with(expected_user_info[0], expected_user_info[1])
        self.assertEqual(actual_user_info, self.contact_person_constructor.return_value)

    @patch('db.session', autospec=True)
    def test_get_contact_person_none(self, session_mock):
        session_mock.query.return_value = query_mock = MagicMock(spec=Query)
        query_mock.filter.return_value = filtered_query_mock = MagicMock(spec=Query)
        filtered_query_mock.first.return_value = None

        actual_contact_person = self.repo.get_contact_person()

        self.assertIsNone(actual_contact_person)


if __name__ == '__main__':
    unittest.main()
