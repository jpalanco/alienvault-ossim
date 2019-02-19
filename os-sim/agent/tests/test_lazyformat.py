import unittest

from src.Logger import Lazyformat


class TestLazyformat(unittest.TestCase):

    def setUp(self):
        self.expected = "{}".format("lazy")
        self.lazyformat = Lazyformat("{}", "lazy")


    def test_str(self):
        actual = str(self.lazyformat)

        self.assertEqual(self.expected, actual)


    def test_str_no_attrs(self):
        template = "Hello, World!"
        actual_str = str(self.lazyformat)

        expected_str = template.format()
        lazyformat = Lazyformat(template)

        actual_str = str(lazyformat)

        self.assertEqual(expected_str, actual_str)


    def test_str_many_attrs(self):
        template = "Hello{}{}{}"
        attrs = [", ", "World", "!"]
        expected_str = template.format(*attrs)
        lazyformat = Lazyformat(template, *attrs)

        actual_str = str(lazyformat)

        self.assertEqual(expected_str, actual_str)


    def test_str_not_enough_attrs(self):
        lazyformat = Lazyformat("{}{}", "a")

        self.assertRaises(IndexError, str, lazyformat)


    def test_str_too_many_attrs(self):
        template = "{}"
        attrs = ["Hello", ", ", "World", "!"]
        expected_str = template.format(*attrs)
        lazyformat = Lazyformat(template, *attrs)

        actual_str = str(lazyformat)

        self.assertEqual(expected_str, actual_str)


    def test_str_nested(self):
        template = "[{}]"
        attr = "Hello, World!"
        expected_str = template.format(template.format(attr))
        lazyformat = Lazyformat(template, Lazyformat(template, attr))

        actual_str = str(lazyformat)

        self.assertEqual(expected_str, actual_str)


    def test_format(self):
        template = "{}"
        lazyformat = Lazyformat("Hello, World!")
        expected = template.format(str(lazyformat))

        actual = template.format(lazyformat)

        self.assertEqual(expected, actual)


    def test_add(self):
        suffix = "text"
        expected = self.expected + suffix

        actual = self.lazyformat + suffix

        self.assertEqual(expected, actual)


    def test_radd(self):
        prefix = "text"
        expected = prefix + self.expected

        actual = prefix + self.lazyformat

        self.assertEqual(expected, actual)


    def test_add_self(self):
        expected = self.expected + self.expected

        actual = self.lazyformat + self.lazyformat

        self.assertEqual(expected, actual)


    def test_mul(self):
        mul = 3
        expected = self.expected * mul

        actual = self.lazyformat * mul

        self.assertEqual(expected, actual)


    def test_rmul(self):
        mul = 3
        expected = mul * self.expected

        actual = mul * self.lazyformat

        self.assertEqual(expected, actual)
