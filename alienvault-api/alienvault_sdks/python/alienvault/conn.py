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
import httplib
import base64
class HttpConn(object):
    
    def __init__(self, username, password,host,port):
        self.username  = username
        self.password = password
        self.port = port
        self.host = host
        self.user =username
        self.conn = None
    
    def __get_headers(self):
        headers={}
        base64string = base64.encodestring('%s:%s' % (self.username, self.password)).replace('\n','')
        authheader =  "Basic %s" % base64string
        headers["Authorization"] =  authheader
        headers["Content-type"] = "application/x-www-form-urlencoded"
        headers["Accept"] ="application/json"
        headers["User-Agent"] ="AlienvaultClient"
        return headers
    
    def connect(self,username, password):
        #TODO: Check certificates?
        try:
            print "conect"
            self.username = username
            self.password = password
            self.conn = httplib.HTTPSConnection(host = self.host,port=self.port)
        except Exception,e:
            print "Error creating the https connection.%s" % (str(e))
            self.conn = None
    
    def do_request(self, request_url,request_params ,request_method = "GET"):
        if self.conn is None:
            self.connect(self.username, self.password)
        self.conn.request(request_method,request_url,headers=self.__get_headers())
        response = self.conn.getresponse()
        return response