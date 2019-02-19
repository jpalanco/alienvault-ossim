#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
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

#
# GLOBAL IMPORTS
#
import ssl
import urllib
import urllib2
import base64
import time
import types
import xml.dom.minidom
from xml.dom.minidom import Node


def parse_open(action, data):
    doc = xml.dom.minidom.parseString(data)
    sess = doc.getElementsByTagName('env:Header')[0].getElementsByTagName('sd:oobInfo')[0].getElementsByTagName('sd:sessionId')[0]
    sessionid = sess.firstChild.wholeText
    subscript = doc.getElementsByTagName('env:Body')[0].getElementsByTagName('sd:subscriptionId')[0]
    subscriptionid = subscript.firstChild.wholeText

    return [sessionid, subscriptionid]


def nano(epoch):
    return int(epoch * 1e9)


def epoch(nano):
    return nano / 1e9


class SDEE:

    def __init__(self, **kwargs):

        try:
            self._callback = kwargs['callback']

        except:
            self._callback = ''

        try:
            self._format = kwargs['format']

        except:
            self._format = 'raw'

        try:
            self._timeout = kwargs['timeout']

        except:
            self._timeout = 1

        try:
            self._user = kwargs['user']

        except:
            self._user = ''

        try:
            self._password = kwargs['password']

        except:
            self._password = ''

        try:
            self._host = kwargs['host']

        except:
            self._host = 'localhost'

        try:
            self._method = kwargs['method']

        except:
            self._method = 'https'

        try:
            self._resource = kwargs['resource']

        except:
            self._resource = 'cgi-bin/sdee-server'

        self._uri = "%s://%s/%s" % (self._method, self._host, self._resource)

        try:
            self._sessionid = kwargs['sessionid']

        except:
            self._sessionid = ''


        try:
            self._subscriptionid = kwargs['subscriptionid']

        except:
            self._subscriptionid = ''

        try:
            self._starttime = kwargs['starttime']

        except:
            self._starttime = nano(time.time())

        self._b64pass = base64.encodestring("%s:%s" % (self._user, self._password))

        self._response = ''

        try:
            self._force = kwargs['force']

        except:
            self._force = 'no'

    def data(self):
        return self._response

    def Password(self, passwd):
        self._password = passwd
        self._b64pass = base64.encodestring("%s:%s" % (self._user, self._password))

    def User(self, username):
        self._user = username
        self._b64pass = base64.encodestring("%s:%s" % (self._user, self._password))

    def Host(self, host):
        self._host = host
        self._uri = "%s://%s/%s" % (self._method, self._host, self._resource)

    def Method(self, method):
        self._method = method
        self._uri = "%s://%s/%s" % (self._method, self._host, self._resource)

    def Resource(self, resource):
        self._resource = resource
        self._uri = "%s://%s/%s" % (self._method, self._host, self._resource)

    def _request(self, params, **kwargs):
        # Blank initial response
        self._response = ''
        if not self._uri or self._uri == "":
            print "ERROR: The uri has not been specified!"
            return None
        try: 
            context = ssl.SSLContext(ssl.PROTOCOL_TLSv1)
            req = urllib2.Request("%s?%s" % (self._uri, params))
            req.add_header('Authorization', "BASIC {0}".format(self._b64pass).rstrip())
            data = urllib2.urlopen(req, timeout=10, context=context)
            self._response = data.read()

            if self._action == 'open':
                self._sessionid, self._subscriptionid = parse_open(self._action, self._response)    
                print self._sessionid
                print self._subscriptionid
            elif self._action == 'close':
                print data.read()
            elif self._action == 'cancel':
                print data.read()
            elif self._action == 'get':
                if type(self._callback) is types.FunctionType:
                    self._callback(**kwargs)
            elif self._action == 'query':
                pass
        except Exception, e:
            print "Request error: %s" % str(e)

    def open(self, **kwargs):
        self._action = 'open'
        param_dict = {"events": "evIdsAlert", "action": "open", "force": self._force}

        if self._subscriptionid != '':
            param_dict['subscriptionId'] = self._subscriptionid

        params = urllib.urlencode(param_dict)
        self._request(params)

    def close(self, **kwargs):
        self._action = 'close'
        params = urllib.urlencode({"action": "close",
                                   "subscriptionId": self._subscriptionid})
        req = self._request(params)

    def cancel(self, **kwargs):
        self._action = 'cancel'
        params = urllib.urlencode({"action": "cancel",
                                   "subscriptionId": self._subscriptionid,
                                   "sessionId": self._sessionid})
        req = self._request(params)

    def get(self, **kwargs):
        self._action = 'get'
        params = urllib.urlencode({"confirm": "yes",
                                   "timeout": "1",
                                   "maxNbrofEvents": "20",
                                   "action": self._action,
                                   "subscriptionId": self._subscriptionid})
        req = self._request(params, **kwargs)

    def query(self, **kwargs):
        pass
