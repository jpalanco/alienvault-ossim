import os
import unittest

import mock

from apimethods.plugin.plugin import apimethod_remove_plugin
from apiexceptions.plugin import APICannotBeRemoved

TEMPORAL_FOLDER = "/var/lib/asec/plugins/"
PLUGINS_FOLDER = "/etc/ossim/agent/plugins/"
END_FOLDER = "/etc/alienvault/plugins/custom/"


class PluginDataType(object):
    ALIENVAULT_PLUGIN = 0
    ALIENVAULT_CUSTOM_PLUGIN = 1
    ALIENVAULT_USER_CUSTOM_PLUGIN = 2


def create_delete_plugin(file_name, create=True):
    file_name = END_FOLDER + file_name
    if create:
        with open(file_name, "w") as f:
            f.write("type=detector")
    else:
        if os.path.exists(file_name):
            os.remove(file_name)


class TestRemoveAPIMethod(unittest.TestCase):

    def setUp(self):
        self.fake_plugin = 'some_plugin_which_does_not_exist.cfg'
        self.real_plugin = 'some_plugin_which_exists.cfg'
        create_delete_plugin(self.real_plugin)

    def test_does_not_exists_plugin(self):
        """API method should raise APICannotBeRemoved exception
        """
        with self.assertRaises(APICannotBeRemoved):
            apimethod_remove_plugin(self.fake_plugin)

    @mock.patch("apimethods.plugin.plugin.get_plugin_data_for_plugin_id")
    def test_try_to_delete_alienvault_plugin(self, mock_plugin_data):
        """APICannotBeRemoved exception should be raised with appropriate message
        """
        class PluginTypeFake(object):
            plugin_type = 0

        mock_plugin_data.return_value = PluginTypeFake()
        with self.assertRaisesRegexp(APICannotBeRemoved, "This is an AlienVault Plugin. It cannot be removed"):
            apimethod_remove_plugin(self.real_plugin)

    def test_config_successfully_removed(self):
        """API method should remove file from file system
        """
        self.assertEquals(None, apimethod_remove_plugin(self.real_plugin))

    @mock.patch("apimethods.plugin.plugin.get_plugin_data_for_plugin_id")
    @mock.patch("apimethods.plugin.plugin.get_plugin_data_for_plugin_id")
    def test_try_to_delete_with_nassets_more_then_zero(self, mock_plugin_data, mock_nassets):
        class PluginTypeFake(object):
            plugin_type = 1
            nassets = 1

        mock_plugin_data.return_value = PluginTypeFake()
        mock_nassets.return_value = PluginTypeFake()
        with self.assertRaisesRegexp(APICannotBeRemoved, "This plugin is enabled for 1 assets. It cannot be removed"):
            apimethod_remove_plugin(self.real_plugin)

    def tearDown(self):
        create_delete_plugin(self.real_plugin, create=False)
