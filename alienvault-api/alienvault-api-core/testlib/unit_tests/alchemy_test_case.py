import unittest

import imp
from mock import MagicMock, patch, call

from unit_tests.base_test_case import BaseTestCase


class AlchemyTestCase(BaseTestCase):

    def setup_require_db_decorator_mock(self, repository_module):
        def kill_patches():
            patch.stopall()
            imp.reload(repository_module)
        self.addCleanup(kill_patches)

        self.require_db_deco_mock = MagicMock(side_effect=lambda x: x)
        patch('apimethods.decorators.require_db', self.require_db_deco_mock).start()
        imp.reload(repository_module)

    def assert_require_db_decorated(self, func):
        self.assertTrue(
            call(func) in self.require_db_deco_mock.call_args_list,
            '{} should be decorated with @require_db'.format(func)
        )


if __name__ == '__main__':
    unittest.main()
