import unittest
import mock


class BaseTestCase(unittest.TestCase):

    # The following attrs are not supported as they are either in use by mock,
    # can't be set dynamically, or can cause problems
    unsupported_attrs = [
        '__getattr__',
        '__setattr__',
        '__init__',
        '__new__',
        '__prepare__',
        '__instancecheck__',
        '__subclasscheck_',
        '__del__'
    ]

    def mock_attr_strict(self, obj, **kwargs):
        self.assertTrue(
            kwargs.get('name') not in self.unsupported_attrs,
            'The "{}" attr is the one of not supported ones {}.'.format(kwargs.get('name'), self.unsupported_attrs)
        )
        self.assertTrue(
            hasattr(obj, kwargs.get('name')),
            'The object has no "{}" attr declared'.format(kwargs.get('name'))
        )
        mock_attr = mock.MagicMock(**kwargs)
        setattr(obj, kwargs.get('name'), mock_attr)

        return mock_attr

    def mock_attr(self, obj, **kwargs):
        self.assertTrue(
            kwargs.get('name') not in self.unsupported_attrs,
            'The "{}" attr is the one of not supported ones: {}'.format(kwargs.get('name'), self.unsupported_attrs)
        )
        mock_attr = mock.MagicMock(**kwargs)
        setattr(obj, kwargs.get('name'), mock_attr)

        return mock_attr

    def set_property_strict(self, obj, pname, pvalue):
        self.assertTrue(
            pname not in self.unsupported_attrs,
            'The "{}" property is the one of not supported ones: {}'.format(pname, self.unsupported_attrs)
        )
        self.assertTrue(
            hasattr(obj, pname),
            'The object has no "{}" property declared'.format(pname)
        )
        setattr(obj, pname, pvalue)

    def set_property(self, obj, pname, pvalue):
        self.assertTrue(
            pname not in self.unsupported_attrs,
            'The "{}" property is the one of not supported ones: {}'.format(pname, self.unsupported_attrs)
        )
        setattr(obj, pname, pvalue)
