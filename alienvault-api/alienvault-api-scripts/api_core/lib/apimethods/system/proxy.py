# -*- coding: utf-8 -*-
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

import urllib2
from ntlm import HTTPNtlmAuthHandler

from ansiblemethods.helper import read_file

class AVProxy:
    """Alienvault Proxy Class

    This Class handle the proxy connection using information
    of curlrc proxy settings, by default stored in '/etc/curlrc'.
    It Supports Basic, Digest and NTLM Authentication methods.
    """

    PROXY_FILE = '/etc/curlrc'

    def __init__(self, system_ip='127.0.0.1', proxy_file=None):
        """Constructor
        @param system_ip: the system ip of the appliance
        @param proxy_file: the file with the proxy configuration
        """
        self.__system_ip = system_ip
        self.__proxy_url = None
        self.__proxy_user = None
        self.__proxy_pass = None
        self.__proxy_handler = None
        self.__proxy_file = proxy_file
        if self.__proxy_file is None:
            self.__proxy_file = AVProxy.PROXY_FILE

        self.__buid_proxy_handler()


    def __str__(self):
        """Returns an string representing the object
        """
        return "AVProxy: system_ip:%s proxy_uri:%s proxy_user:%s proxy_pass:%s" \
            % (self.__system_ip, self.__proxy_url, self.__proxy_user, self.__proxy_pass)


    def __read_proxy_file(self):
        """ Read the proxy curl configuration file
        """
        (success, proxy_file_content) = read_file(self.__system_ip,
                                                  self.__proxy_file)
        if not success:
            return False

        try:
            splitted = proxy_file_content.split('\n')
            for line in splitted:
                (key, value) = line.replace(' ', '').split('=')
                if key == 'proxy':
                    self.__proxy_url = value.replace('http://', '')
                if key == 'proxy-user':
                    (self.__proxy_user, self.__proxy_pass) = value.split(':')
        except ValueError:
            return False

        return True


    def __buid_proxy_handler(self):
        """ Build the proxy handler
        """
        if self.__read_proxy_file():
            proxy_full_url = "http://"
            if self.need_authentication():
                proxy_full_url += "%s:%s@" % (self.__proxy_user, self.__proxy_pass)
            proxy_full_url += self.__proxy_url

            self.__proxy_handler = urllib2.ProxyHandler({'http' : proxy_full_url})
        else:
            self.__proxy_handler = urllib2.ProxyHandler({})


    def __build_opener(self, url):
        """ Build the proxy opener
        """
        opener = None
        if self.need_authentication():
            password_mgr = urllib2.HTTPPasswordMgrWithDefaultRealm()
            password_mgr.add_password(None,
                                      url,
                                      self.__proxy_user,
                                      self.__proxy_pass)
            proxy_basic_auth_handler = urllib2.ProxyBasicAuthHandler(password_mgr)
            proxy_digest_auth_handler = urllib2.ProxyDigestAuthHandler(password_mgr)
            proxy_ntlm_auth_handler = HTTPNtlmAuthHandler.HTTPNtlmAuthHandler(password_mgr)
            opener = urllib2.build_opener(self.__proxy_handler,
                                          proxy_basic_auth_handler,
                                          proxy_digest_auth_handler,
                                          proxy_ntlm_auth_handler)
        else:
            opener = urllib2.build_opener(self.__proxy_handler)

        return opener


    def get_proxy_url(self):
        """
        @return the proxy url
        """
        return self.__proxy_url


    def need_authentication(self):
        """ Proxy requires authentication
        @return True if the proxy requires authentication, False otherwise
        """
        return self.__proxy_user is not None and self.__proxy_pass is not None


    def open(self, request, timeout=None, retries=0):
        """ Open the request url.
        @param request: urllib2.Request object with the request or an url string
        @param timeout: timeout for opening the url
        @param retries: number of retries if the open statement fails
        @return a urllib2.Response
        """

        if not isinstance(request, urllib2.Request):
            request = urllib2.Request(request)

        opener = self.__build_opener(request.get_full_url())

        success = False
        attempt = 0
        response = None
        while not success:
            try:
                attempt += 1
                if timeout is None:
                    response = opener.open(request)
                else:
                    response = opener.open(request, timeout=timeout)
                success = True
            except:
                if attempt > retries:
                    raise

        return response


    def check_connection(self,
                         url="http://data.alienvault.com/avl/versions",
                         timeout=5,
                         retries=0):
        """Check the connection to the url
        Returns a tuple of (code, msg) where code=0 if success,
        otherwise it gives you an error code.

        Note: Minimun kernel socket timeout is 20 sec
        """
        response = None
        request = urllib2.Request(url)
        try:
            response = self.open(request, timeout=timeout, retries=retries)
        except Exception, e:
            return (False, "Connection error: %s" % str(e))

        if response is None or response.getcode() != 200:
            return (False, "Connection error")

        return (True, response)


# vim:ts=4 sts=4 tw=79 expandtab:
