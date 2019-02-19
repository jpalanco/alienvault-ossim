import unittest
import mock

from apimethods.plugin.plugin import apimethod_upload_plugin
from apiexceptions.plugin import APIInvalidPlugin, APICannotSavePlugin

TEMPORAL_FOLDER = "/var/lib/asec/plugins/"
PLUGINS_FOLDER = "/etc/ossim/agent/plugins/"
END_FOLDER = "/etc/alienvault/plugins/custom/"


class PluginDataType(object):
    ALIENVAULT_PLUGIN = 0
    ALIENVAULT_CUSTOM_PLUGIN = 1
    ALIENVAULT_USER_CUSTOM_PLUGIN = 2


class TestUploadAPIMethod(unittest.TestCase):

    @mock.patch("apimethods.plugin.plugin.PluginFile")
    def test_plugin_with_incorrect_syntax(self, MockPluginFile):

        mock_plugin = MockPluginFile.return_value
        mock_plugin.read.return_value = None
        mock_plugin.check.return_value = {
            "error_count": 1,
            "error_summary": "test_msg"
        }
        mock_plugin.get_latest_error_msg.return_value = "test_msg"
        with self.assertRaisesRegexp(APIInvalidPlugin, "test_msg"):
            apimethod_upload_plugin("fake_plugin", "", "", "", "", "")

    @mock.patch("apimethods.plugin.plugin.os")
    @mock.patch("apimethods.plugin.plugin.PluginFile")
    def test_plugin_need_to_override(self, MockPluginFile, mock_os):

        mock_plugin = MockPluginFile.return_value
        mock_plugin.read.return_value = None
        mock_plugin.check.return_value = {"error_count": 0, }

        mock_os.path.exists.return_value = True

        self.assertEquals({"error_count": 0, "need_overwrite": True},
                          apimethod_upload_plugin("fake_plugin", "", "", "", "", ""))

    @mock.patch("apimethods.plugin.plugin.save_plugin_from_raw_sql")
    @mock.patch("apimethods.plugin.plugin.get_plugin_data_for_plugin_id")
    @mock.patch("apimethods.plugin.plugin.os")
    @mock.patch("apimethods.plugin.plugin.PluginFile")
    def test_save_plugin_to_db(self, MockPluginFile, mock_os, mock_db_data, mock_save_sql):

        mock_plugin = MockPluginFile.return_value
        mock_plugin.read.return_value = None
        mock_plugin.check.return_value = {"error_count": 0, "need_overwrite": True}
        mock_os.path.exists.return_value = True
        mock_db_data.return_value = "not_empty_string"
        mock_save_sql.return_value = (False, "test_msg")
        with mock.patch('apimethods.plugin.plugin.open', mock.mock_open(read_data='')) as m:
            with self.assertRaisesRegexp(APICannotSavePlugin, "test_msg"):
                apimethod_upload_plugin('plugin_path', "", "", "", "", overwrite=True)
        mock_os.path.exists.assert_called_once()

    @mock.patch("apimethods.plugin.plugin.update_plugin_data")
    @mock.patch("apimethods.plugin.plugin.save_plugin_from_raw_sql")
    @mock.patch("apimethods.plugin.plugin.get_plugin_data_for_plugin_id")
    @mock.patch("apimethods.plugin.plugin.os")
    @mock.patch("apimethods.plugin.plugin.PluginFile")
    def test_update_plugin_db_data_fail(self, MockPluginFile, mock_os, mock_db_data, mock_save_sql,
                               mock_save_plg_data_func_update):

        mock_plugin = MockPluginFile.return_value
        mock_plugin.read.return_value = None
        mock_plugin.check.return_value = {"error_count": 0,
                                          "need_overwrite": True,
                                          "rules": []}
        mock_os.path.exists.return_value = True
        mock_db_data.return_value = "not_empty_string"
        mock_save_sql.return_value = (True, "test_msg")
        mock_save_plg_data_func_update.return_value = (False, "update_plugin")
        with mock.patch('apimethods.plugin.plugin.open', mock.mock_open(read_data='')) as m:
            with self.assertRaisesRegexp(APICannotSavePlugin, "update_plugin"):
                apimethod_upload_plugin('plugin_path', "", "", "", "", overwrite=True)
        mock_os.path.exists.assert_called_once()

    @mock.patch("apimethods.plugin.plugin.remove_plugin_data")
    @mock.patch("apimethods.plugin.plugin.update_plugin_data")
    @mock.patch("apimethods.plugin.plugin.save_plugin_from_raw_sql")
    @mock.patch("apimethods.plugin.plugin.get_plugin_data_for_plugin_id")
    @mock.patch("apimethods.plugin.plugin.os")
    @mock.patch("apimethods.plugin.plugin.PluginFile")
    def test_plugin_save_on_disk_fail(self, MockPluginFile, mock_os, mock_db_data, mock_save_sql,
                               mock_save_plg_data_func_update, mock_remove_plg):
        """
        Save plugin with the appropriate headers (vendor:model:version)
        """

        mock_plugin = MockPluginFile.return_value
        mock_plugin.read.return_value = None
        mock_plugin.check.return_value = {"error_count": 0,
                                          "need_overwrite": True,
                                          "rules": []}
        mock_os.path.exists.return_value = True
        mock_db_data.return_value = "not_empty_string"
        mock_save_sql.return_value = (True, "")
        mock_save_plg_data_func_update.return_value = (True, "")

        mock_plugin.save.return_value = False
        mock_remove_plg.return_value = None
        mock_plugin.get_latest_error_msg.return_value = "some_error_msg"
        with mock.patch('apimethods.plugin.plugin.open', mock.mock_open(read_data='')) as m:
            with self.assertRaisesRegexp(APICannotSavePlugin, "some_error_msg"):
                apimethod_upload_plugin('plugin_path', "", "", "", "", overwrite=True)
        mock_os.path.exists.assert_called_once()
