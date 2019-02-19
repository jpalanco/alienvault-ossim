import unittest

from bounded_contexts.central_console.application_services.abstract_console_proxy import AbstractConsoleProxy


class TestAbstractConsoleProxy(unittest.TestCase):

    def test_abstract(self):
        self.assertRaises(TypeError, AbstractConsoleProxy)


if __name__ == '__main__':
    unittest.main()
