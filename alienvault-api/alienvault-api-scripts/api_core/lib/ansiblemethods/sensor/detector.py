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

from  ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.helper import parse_av_config_response
from apimethods.system.cache import flush_cache

ansible = Ansible()

def get_sensor_detectors (system_ip):
    """
    @param system_ip: The system IP where you want to get the [sensor]/detectors from ossim_setup.conf
    @return A tuple (sucess|error, data|msgerror)
    """
    response = ansible.run_module(host_list=[system_ip],
                                  module="av_config",
                                  args="sensor_detectors=True op=get",
                                  use_sudo=True)
    return parse_av_config_response(response, system_ip)


def set_sensor_detectors (system_ip, plugins):
    """
    @param system_ip: The system IP where you want to get the [sensor]/detectors from ossim_setup.conf
    @param Comma separate list of detector plugins to activate. Must exists in the machine
    @return A tuple (sucess|error, data|msgerror)
    """
    response = ansible.run_module(host_list=[system_ip],
                                  module="av_config",
                                  args="sensor_detectors=%s op=set" % plugins)
    return parse_av_config_response(response, system_ip)


def get_sensor_detectors_from_yaml(system_ip):
    rc = True

    try:
        response = ansible.run_module(host_list=[system_ip],
                                      module='av_sensor_yaml',
                                      args="op=get")
    except Exception as msg:
        rc = False
        response = str(msg)
    return (True,response)


def set_sensor_detectors_from_yaml(system_ip, plugins):
    rc = True
    flush_cache(namespace="sensor_plugins")
    try:
        response = ansible.run_module(host_list=[system_ip],
                                      module='av_sensor_yaml',
                                      args="op=set plugins=\"%s\"" % plugins)

        if response['dark'] != {}:
            return False, "Something wrong happened while running the set plugin module %s" % str(response)
        if "failed" in response['contacted'][system_ip]:
            print "FAiled"
            try:
                msg = response['contacted'][system_ip]['msg']
            except:
                msg = response
            return False,msg
        if "unreachable" in response:
            return False, "%s is unreachable" % system_ip

    except Exception as msg:
        response = str(msg)
        rc = False
    return rc,response

