import os
import unittest

from apimethods.plugin.plugin import apimethod_download_plugin
from apiexceptions.plugin import APIPluginFileNotFound

TEMPORAL_FOLDER = "/var/alienvault/tmp/"
PLUGINS_FOLDER = "/etc/ossim/agent/plugins/"
END_FOLDER = "/etc/alienvault/plugins/custom/"


def create_delete_plugin(file_name, create=True):
    file_name = END_FOLDER + file_name
    if create:
        with open(file_name, "w") as f:
            f.write("type=detector")
    else:
        if os.path.exists(file_name):
            os.remove(file_name)


class TestDownloadAPIMethod(unittest.TestCase):
    def setUp(self):
        self.fake_plugin = 'plugin_which_does_not_exist.cfg'
        self.real_plugin = 'plugin_which_exists.cfg'
        create_delete_plugin(self.real_plugin)

    def test_does_not_exists_plugin(self):
        """API method should raise APIPluginFileNotFound exception
        """
        with self.assertRaises(APIPluginFileNotFound):
            apimethod_download_plugin(self.fake_plugin)

    def test_try_to_download_wrong_plugin(self):
        """APIPluginFileNotFound exception should be raised with appropriate message
        """
        with self.assertRaisesRegexp(APIPluginFileNotFound,
                                     "Plugin File not found: plugin_which_does_not_exist.cfg"):
            apimethod_download_plugin(self.fake_plugin)

    def test_config_successfully_downloaded(self):
        """API method should return the contents of the file
        """
        self.assertEquals("type=detector", apimethod_download_plugin(self.real_plugin))

    def tearDown(self):
        create_delete_plugin(self.real_plugin, create=False)
