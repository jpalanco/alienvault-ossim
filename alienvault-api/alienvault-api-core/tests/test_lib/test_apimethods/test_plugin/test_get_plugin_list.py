import unittest
import datetime

import mock

from apimethods.plugin.plugin import apimethod_get_plugin_list
from db.models.alienvault import PluginData


class TestGetPluginListAPIMethod(unittest.TestCase):
    @mock.patch("apimethods.plugin.plugin.get_plugin_list_from_plugin_data")
    def test_empty_list(self, mock_plugin_list):
        """API method should return an empty list
        """
        # with self.assertRaises(APIPluginFileNotFound):
        #     apimethod_download_plugin(self.fake_plugin)
        mock_plugin_list.return_value = []
        self.assertEquals([], apimethod_get_plugin_list())

    @mock.patch("apimethods.plugin.plugin.get_plugin_list_from_plugin_data")
    def test_exception_handling(self, mock_plugin_list):
        """API method should return Fasle and appropriate error message
        """
        # this should raise an AttributeError in list comprehension
        mock_plugin_list.return_value = [1]
        self.assertEquals((False, "Cannot load the list of the plugins"),
                          apimethod_get_plugin_list())

    @mock.patch("apimethods.plugin.plugin.get_plugin_list_from_plugin_data")
    def test_list_with_one_element(self, mock_plugin_list):
        """API method should raise APIPluginFileNotFound exception
        """
        pl_mock = mock.Mock()
        data = {'ctx': '71776572-7479-0000-0000-000000000000',
                'last_update': datetime.datetime(2017, 3, 20, 11, 2, 36),
                'model': None,
                'nassets': 0L,
                'nsids': 0L,
                'plugin_id': 666L,
                'plugin_name': 'Test_plugin',
                'plugin_type': 2,
                'product_type': 3L,
                'vendor': 'apalii',
                'version': None}
        pl_mock.serialize = data
        mock_plugin_list.return_value = [pl_mock]
        self.assertEquals([data], apimethod_get_plugin_list())

    @mock.patch('db.models.alienvault.PluginData.serialize', new_callable=mock.PropertyMock)
    @mock.patch("apimethods.plugin.plugin.get_plugin_list_from_plugin_data")
    def test_list_with_couple_of_elements2(self, mock_plugin_list, serialize_mock):
        """API method should return list of plugins data #2 (cleaner way)
        """
        data = {'ctx': '71776572-7479-0000-0000-000000000000',
                'last_update': datetime.datetime(2017, 3, 20, 11, 2, 36),
                'model': None,
                'nassets': 0L,
                'nsids': 0L,
                'plugin_id': 666L,
                'plugin_name': 'Test_plugin',
                'plugin_type': 2,
                'product_type': 3L,
                'vendor': 'apalii',
                'version': None}
        data2 = {'ctx': '71776572-7479-0000-0000-000000000001',
                 'last_update': datetime.datetime(2017, 3, 20, 11, 2, 36),
                 'model': None,
                 'nassets': 0L,
                 'nsids': 0L,
                 'plugin_id': 667L,
                 'plugin_name': 'Test_plugin2',
                 'plugin_type': 2,
                 'product_type': 3L,
                 'vendor': 'apalii',
                 'version': None}

        serialize_mock.side_effect = [data, data2]
        mock_plugin_list.return_value = [PluginData(), PluginData()]
        self.assertEquals([data, data2], apimethod_get_plugin_list())
