# -*- coding: utf-8 -*-
#
# License:
#
# Copyright (c) 2015 AlienVault
# All rights reserved.
#
# This package is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; version 2 dated June, 1991.
# You may not use, modify or distribute this program under any other version
# of the GNU General Public License.
#
# This package is distributed in the hope that it will be useful,
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

import api_log


class APIException(Exception):
    def __init__(self, http_code=500, log=None, log_level='ERROR', message=''):
        super(APIException, self).__init__(message)
        self._http_code = http_code
        if log is not None:
            api_log.log(message=log, level=log_level)

    @property
    def http_code(self):
        """ Get the current http code """
        return self._http_code


#
# APIException Template example
#
class APIExceptionTemplate(APIException):
    def __init__(self, log=None):
        super(APIExceptionTemplate, self).__init__(http_code=450,  # Error code to return from a blueprint
                                                   log=log,  # Text with the error details to log
                                                   log_level='ERROR',  # Log level
                                                   message='This the Message text to the user')
