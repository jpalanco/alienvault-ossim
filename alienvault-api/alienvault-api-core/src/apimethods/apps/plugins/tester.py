# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2015 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

from apimethods.apps.plugins.error import ErrorCodes
from apimethods.apps.plugins.pfile import PluginFile
from apiexceptions.plugin import APICannotCheckPlugin

import api_log


class PluginTester(object):
    """
    Plugin Tester class.
    """

    def __init__(self, plugin_file):
        """
        Constructor
        Args:
            plugin_file:The plugin configuration file
        """
        self.__plugin_file_name = plugin_file
        self.__plugin_file = None
        self.__plugin_loaded = False

    def __load_plugin(self):
        self.__plugin_file = PluginFile()
        self.__plugin_file.read(plugin_file=self.__plugin_file_name, encoding='latin1')
        self.__plugin_loaded = True

    def process(self):
        """Processes the plugin checks"""

        if not self.__plugin_loaded:
            self.__load_plugin()
        try:
            data = self.__plugin_file.check()
        except Exception as e:
            api_log.warning("[PluginTester] Cannot check the plugin %s" % str(e))
            raise APICannotCheckPlugin(self.__plugin_file_name)
        return data