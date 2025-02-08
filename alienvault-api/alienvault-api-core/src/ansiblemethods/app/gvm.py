# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2013 AlienVault
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

from collections import namedtuple
from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.helper import ansible_is_valid_response, copy_file, remove_file
from xml.sax.handler import ContentHandler
from xml.sax import make_parser
import os
import re

ansible = Ansible()
APIResult = namedtuple('APIResult', ['success', 'data'])

def parsefile(file):
    """
    Try to parse a xml file
    :param file: File to parse
    """
    parser = make_parser()
    parser.setContentHandler(ContentHandler())
    parser.parse(file)


def ansible_execute_gvm_command(sensor_ip, gvm_file):
    """
    Execute gvm command remotely
    :param sensor_ip: The sensor IP that will execute the command
    :param gvm_file: file with GVM command to be executed
    :return: A tuple (status, message).

    This is a workaround until we can adapt user avapi/www-data to run gvm commands
    """

    file = "/tmp/" + gvm_file
    cert_file = '/var/ossim/ssl/local/private/cakey_avapi.pem'
    localhost = "127.0.0.1"

    if not os.path.exists(file):
        return APIResult(False, "%s Doesn't exists!" % file)

    try:
        parsefile(file)
    except Exception, e:
        return APIResult(False, "%s is NOT well-formed! %s" % (file, e))

    response = ansible.run_module(
        host_list=[localhost],
        module="shell",
        args="sudo runuser -u _gvm -- /usr/bin/gvm-cli tls --hostname " + sensor_ip + " --certfile " + cert_file + " " + file
    )

    success, msg = ansible_is_valid_response(localhost, response, True)

    if not success:
        if re.search("socket.timeout", msg):
            msg = "The sensor is not available. Connection timed out"
        elif re.search("Connection refused", msg):
            msg = "The sensor is not available. Connection refused"
        elif re.search("No route to host", msg):
            msg = "Sensor is not available"
        elif re.search("Traceback", msg):
            msg = "Something went wrong"
        return APIResult(False, "[ansible_execute_gvm_command] - " + msg)

    return APIResult(True, response['contacted'][localhost]['stdout'])
