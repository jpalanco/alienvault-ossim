import unittest

from bounded_contexts.central_console.models.abstract_console_token import AbstractConsoleToken


class ConcreteConsoleToken(AbstractConsoleToken):

    def __init__(self, raw_data):
        super(ConcreteConsoleToken, self).__init__(raw_data)

    @property
    def issuer(self):
        return 'something'


class TestAbstractConsoleToken(unittest.TestCase):

    def test_abstract(self):
        self.assertRaises(TypeError, AbstractConsoleToken)

    def test_concrete(self):
        raw_data = 'dummytoken'
        ConcreteConsoleToken(raw_data)

    def test_concrete_raw_accessible(self):
        expected_raw = 'dummytoken'

        token = ConcreteConsoleToken(expected_raw)
        actual_raw = token.raw_data

        self.assertEqual(actual_raw, expected_raw)


if __name__ == '__main__':
    unittest.main()
