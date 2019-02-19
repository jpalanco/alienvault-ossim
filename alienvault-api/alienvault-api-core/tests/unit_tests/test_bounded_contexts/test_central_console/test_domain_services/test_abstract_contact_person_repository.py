import unittest

from mock import MagicMock

from bounded_contexts.central_console.domain_services.abstract_contact_person_repository\
    import AbstractContactPersonRepository
from bounded_contexts.central_console.models.contact_person import ContactPerson


class ConcreteContactPersonRepository(AbstractContactPersonRepository):

    def get_contact_person(self):
        pass


class TestAbstractContactPersonRepository(unittest.TestCase):

    def test_abstract(self):
        self.assertRaises(TypeError, AbstractContactPersonRepository)

    def test_concrete(self):
        ConcreteContactPersonRepository(MagicMock(spec=ContactPerson))


if __name__ == '__main__':
    unittest.main()
