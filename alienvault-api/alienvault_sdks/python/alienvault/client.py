#!/usr/bin/env python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2013 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

from conn import HttpConn
from paths import *
""" The **client** module provides an interface to the Alienvault REST API"""

class AlienvaultClient(object):
    """Alienvault REST APi client
    """
    def __init__(self,username, password, host="127.0.0.1", port=7000):
        """
        @param api_host: The Alienvault Rest server IP
        @param port: The Alienvault REst server listening port. Def
        """
        self.username = username
        self.password = password
        self.host = host
        self.port = port
        self.https_conn = HttpConn(self.username,self.password,self.host,self.port)
    
    def get_sensors(self):
        response =  self.https_conn.do_request(request_url="/av/api/1.0/config/sensors",request_params="", request_method ="GET")
        return response


if __name__ == "__main__":
    a = AlienvaultClient("admin", "alien4ever" ,"192.168.5.118",7000)
    response = a.get_sensors()
    print response.read()