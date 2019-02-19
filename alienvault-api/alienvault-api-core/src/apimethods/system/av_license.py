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

from apimethods.system.proxy import AVProxy

import urllib2
import urllib
import base64
import os
import json

from ansiblemethods.system.system import get_system_id, install_debian_package
from ansiblemethods.helper import copy_file, remove_file
from ansiblemethods.ansiblemanager import Ansible

from db.methods.system import get_system_ip_from_system_id

ansible = Ansible()

class AVLicense:

    def __init__(self, license_type, key, system_ip):
        """Constructor
        @param license_type: The license type
        @param key: the license key server user
        @param system_ip: the system ip of the appliance to register
        """
        self.__license_type = license_type
        self.__key = key
        self.__system_ip = system_ip
        self.__system_id = None
        self.__deb_pkg_file = '/tmp/avl.deb'
        self.__deb_pkg_file_sig = '/tmp/avl.deb.sig'
        self.__proxy = None

    def __str__(self):
        """Returns an string representing the object
        """
        return "AVLicense: type:%s key:%s system_id:%s system_id:%s" % (self.__license_type, self.__key, self.__sytem_id, self.__sytem_id)

    def __get_license(self):
        """ Obtain the license package from license server
        """

        encoded_key = urllib.quote(self.__key)
        url = "http://data.alienvault.com/avl/%s/?license=%s" % (self.__license_type, encoded_key)
        response = None
        request = urllib2.Request(url)
        request.add_header('User-agent', self.__system_id)
        try:
            response = self.__proxy.open(request, timeout=20, retries=2)
        except Exception, e:
            return (False, "ERROR_CONNECTION")

        av_error = response.info().getheader('X-AV-ERROR')
        if av_error is not None:
            return (False, av_error)

        av_signature = response.info().getheader('X-AV-Signature')
        if av_signature is None:
            return (False, "ERROR_SIGNING_PACKAGE")

        if os.path.exists(self.__deb_pkg_file_sig):
            os.remove(self.__deb_pkg_file_sig)
        deb_file_sig = open(self.__deb_pkg_file_sig, 'wr')
        deb_file_sig.write(base64.b64decode(av_signature))
        deb_file_sig.close()

        if os.path.exists(self.__deb_pkg_file):
            os.remove(self.__deb_pkg_file)
        deb_file = open(self.__deb_pkg_file, 'wr')
        deb_file.write(response.read())
        deb_file.close()

        response = ansible.run_module(
                host_list=["127.0.0.1"],
                module="command",
                args="/usr/bin/gpg --verify --keyring /etc/apt/trusted.gpg %s" % (self.__deb_pkg_file_sig))

        if os.path.exists(self.__deb_pkg_file_sig):
            os.remove(self.__deb_pkg_file_sig)

        if response['contacted']['127.0.0.1']['rc'] == 0:
            return (True, 'SUCCESS')
        else:
            return (False, 'ERROR_SIGNING_PACKAGE')

    def __install_license(self):
        """ Install license:
        1. Copy the debian package in the target appliance
        2. Install debian package
        3. Remove remote and local debian package file
        """
        (success, msg) = copy_file(host_list=[self.__system_ip],
                                   args="src=%s dest=%s" % (self.__deb_pkg_file, self.__deb_pkg_file))
        if not success:
            return (False, 'ERROR_SERVER')

        (success, msg) = install_debian_package(host_list=[self.__system_ip],
                                                debian_package=self.__deb_pkg_file)
        if not success:
            return (False, 'ERROR_INSTALLING_LICENSE')

        (success, msg) = remove_file(host_list=[self.__system_ip],
                                     file_name=self.__deb_pkg_file)
        if not success:
            return (False, 'ERROR_SERVER')

        (success, msg) = remove_file(host_list=['127.0.0.1'],
                                     file_name=self.__deb_pkg_file)
        if not success:
            return (False, 'ERROR_SERVER')

        return (True, 'SUCCESS')

    def register_appliance(self):
        """ Register the appliance:
        1. Get the system_id
        2. Get proxy settings
        3. Check the internet connection,
        4. Tries to get the license
        5. Install license
        """
        (success, msg) = get_system_id(self.__system_ip)
        if not success:
            return (False, 'ERROR_SERVER')
        self.__system_id = msg

        self.__proxy = AVProxy(self.__system_ip)

        (success, msg) = self.__get_license()
        if not success:
            return (False, msg)

        return self.__install_license()


def translate_msg(msg):

    translation = {
        'SUCCESS': 'AlienVault USM activated successfully',
        'ERROR_SERVER': 'Internal error found. Please try again later',
        'ERROR_CONNECTION': 'Impossible to connect to server, please check your network configuration',
        'ERROR_INTERNET': 'An internet connection is needed in order to activate your version.',
        'ERROR_DNS': 'DNS problem, please check your DNS configuration.',
        'WARNING_VERSION': 'There is a new version available. A updated trial version is highly recommended.',
        'ERROR_KEY_ASSOCIATED': "AlienVault USM Professional key has already been used",
        'ERROR_BAD_KEY': "AlienVault USM Professional key not valid",
        'ERROR_EMAIL_ASSOCIATED': "ERROR: Email address used to activate more than 5 systems",
        'ERROR_BAD_EMAIL': "Submitted email address was not found in the license system.\nPlease check that you are entering the email address that you used to when registering for the free trial",
        'ERROR_INSTALLING_LICENSE': "There is a problem installing the Alienvault USM license, the package system could be blocked.\nPlease try again later."
    }

    if msg in translation:
        return translation[msg]
    else:
        return msg


def register_appliance_trial(email='',
                             system_id='local',
                             translate=True):
    """
    Register the Appliance. Trial version
    """
    (success, data) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret

    av_license = AVLicense(license_type='trial',
                           key=email,
                           system_ip=data)

    (success, msg) = av_license.register_appliance()

    if translate:
        msg = translate_msg(msg)

    return (success, msg)


def register_appliance_pro(key='',
                           system_id='local',
                           translate=True):
    """
    Get the system_id and Register the Appliance. Pro version
    """
    (success, data) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret

    av_license = AVLicense(license_type='pro',
                           key=key,
                           system_ip=data)

    (success, msg) = av_license.register_appliance()

    if translate:
        msg = translate_msg(msg)

    return (success, msg)


def get_current_version(system_id='local'):
    """
    Get the current version
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return (False, 'Error getting system id')

    proxy = AVProxy(system_ip)
    (success, response) = proxy.check_connection()
    if not success:
        return (False, "Error getting current version")

    try:
        response = json.load(response)
    except Exception, e:
        return (False, "Error json load: %s" % str(e))

    return (True, response)


# vim:ts=4 sts=4 tw=79 expandtab:
