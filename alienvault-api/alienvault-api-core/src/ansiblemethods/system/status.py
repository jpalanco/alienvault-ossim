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
"""
    Methos to use the av_center_facts functionality
"""
from collections import namedtuple
import api_log
from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.helper import parse_av_config_response, read_file, ansible_is_valid_response
from ansiblemethods.ansibleinventory import AnsibleInventoryManager


ansible = Ansible()
APIResult = namedtuple('APIResult', ['success', 'data'])


# XXX  All this code can be condesed in a only function with params
# and used simple stubs for the rest

def system_all_info(system_ip):
    """
     Get the alienvault_status system info

        Args:
           system_ip (str): String with a IP address.

        Returns:
            A tuple of the form (bool, data) where the first element is a true or false. The data parameter returns the error message on error or
            the all status data if the exececution is correct.
    """
    # First, contact with the system_ip
    response = ansible.run_module(host_list=[system_ip],
                                  module="av_center_facts", args="")
    # Check if we have contacted the system
    if system_ip in response['contacted']:
        # Check  the "failed" flag
        if not response['contacted'][system_ip].get('failed', False):
            result = APIResult(True, response['contacted'][system_ip]['data'])
        else:
            api_log.error("Error calling module 'av_center_facts' msg:%s" % response['contacted'][system_ip].get('msg', "Unknown error"))
            result = APIResult(False, "Error calling module 'av_center_facts' msg:%s" % response['contacted'][system_ip].get('msg', "Unknown error"))
    else:
        api_log.error("Can't connect to system with IP %s msg:%s " % (system_ip, str(response['dark'][system_ip])))
        result = APIResult(False, "Can't connect to system with IP %s msg:%s " % (system_ip, str(response['dark'][system_ip])))
    return result


def system_status(system_ip):
    """
        Get the alienvault_status system info

        Args:
           system_ip (str): String with a IP address.

        Returns:
            A tuple of the form (bool, data) where the first element is a true or false. The data parameter returns the error message on error or
            the system status data if the exececution is correct.

    """
    response = ansible.run_module(host_list=[system_ip],
                                  module="av_center_facts", args="sections=system_status")
    # Check if we have contacted the system
    if system_ip in response['contacted']:
        # Check  the "failed" flag
        if not response['contacted'][system_ip].get('failed', False):
            result = APIResult(True, response['contacted'][system_ip]['data'])
        else:
            api_log.error("Error calling module 'av_center_facts' msg:%s" % response['contacted'][system_ip].get('msg', "Unknown error"))
            result = APIResult(False, "Error calling module 'av_center_facts' msg:%s" % response['contacted'][system_ip].get('msg', "Unknown error"))
    else:
        api_log.error("Can't connect to system with IP %s msg:%s " % (system_ip, str(response['dark'][system_ip])))
        result = APIResult(False, "Can't connect to system with IP %s msg:%s " % (system_ip, str(response['dark'][system_ip])))
    return result


def network_status(system_ip):
    """
        Get the alienvault_status system info

        Args:
            system_ip (str): String with a IP address.

        Returns:
            A tuple of the form (bool, data) where the first element is a true or false. The data parameter returns the error message on error or
            the network status data if the exececution is correct.

    """
    response = ansible.run_module(host_list=[system_ip],
                                  module="av_center_facts", args="sections=network_status")
    # Check if we have contacted the system
    if system_ip in response['contacted']:
        # Check  the "failed" flag
        if not response['contacted'][system_ip].get('failed', False):
            result = APIResult(True, response['contacted'][system_ip]['data'])
        else:
            api_log.error("Error calling module 'av_center_facts' msg:%s" % response['contacted'][system_ip].get('msg', "Unknown error"))
            result = APIResult(False, "Error calling module 'av_center_facts' msg:%s" % response['contacted'][system_ip].get('msg', "Unknown error"))
    else:
        api_log.error("Can't connect to system with IP %s msg:%s " % (system_ip, str(response['dark'][system_ip])))
        result = APIResult(False, "Can't connect to system with IP %s msg:%s " % (system_ip, str(response['dark'][system_ip])))
    return result


def alienvault_status(system_ip):
    """
        Get the alienvault_status system info

        Args:
            system_ip (str): String with a IP address.

        Returns:
            A tuple of the form (bool, data) where the first element is a true or false. The data parameter returns the error message on error or
            the alienvault status data if the exececution is correct.
    """
    response = ansible.run_module(host_list=[system_ip],
                                  module="av_center_facts", args="sections=alienvault_status")

    success, msg = ansible_is_valid_response(system_ip, response)

    if not success:
        return APIResult(False, "Error retrieving AlienVault Status info: %s" % msg)

    return APIResult(True, response['contacted'][system_ip]['data']['alienvault_status'])


def cpu(system_ip):
    """
        Get the cpu system info

        Args:
            system_ip (str): String with a IP address.

        Returns:
            A tuple of the form (bool, data) where the first element is a true or false. The data parameter returns the error message on error or
            the  cpu status data if the exececution is correct.

    """
    response = ansible.run_module(host_list=[system_ip],
                                  module="av_center_facts", args="sections=cpu")
    # Check if we have contacted the system
    if system_ip in response['contacted']:
        # Check  the "failed" flag
        if not response['contacted'][system_ip].get('failed', False):
            result = APIResult(True, response['contacted'][system_ip]['data'])
        else:
            api_log.error("Error calling module 'av_center_facts' msg:%s" % response['contacted'][system_ip].get('msg', "Unknown error"))
            result = APIResult(False, "Error calling module 'av_center_facts' msg:%s" % response['contacted'][system_ip].get('msg', "Unknown error"))
    else:
        api_log.error("Can't connect to system with IP %s msg:%s " % (system_ip, str(response['dark'][system_ip])))
        result = APIResult(False, "Can't connect to system with IP %s msg:%s " % (system_ip, str(response['dark'][system_ip])))
    return result


def get_local_time(system_ip, date_fmt=None):
    """ Returns local time on remote system

    Args:
        system_ip: (str) Remote system IP.
        date_fmt: (str) Date format sting.

    Returns: A tuple of the form (bool, data) where the first element is a true or false and second one is local time
             or error message.
    """
    date_fmt = '%Y-%m-%d %H:%M:%S' if date_fmt is None else date_fmt
    response = ansible.run_module(host_list=[system_ip], module='shell', args='date +"%s"' % date_fmt)
    if system_ip in response['contacted']:
        if not response['contacted'][system_ip].get('failed', False):
            result = (True, response['contacted'][system_ip]['stdout'])
        else:
            result = (False, "Error getting local time: %s" % response['contacted'][system_ip].get('msg',
                                                                                                   'Unknown error'))
    else:
        result = (False, "Can't connect to system with IP %s msg: %s " % (system_ip, str(response['dark'][system_ip])))

    return result


def disk_usage(system_ip):
    """
        Get the disk usage info


        Args:
            system_ip (str): String with a IP address.

        Returns:
            A tuple of the form (bool, data) where the first element is a true or false. The data parameter returns the error message on error or
            the disk usage status data if the exececution is correct.
    """
    response = ansible.run_module(host_list=[system_ip],
                                  module="av_center_facts", args="sections=disk_usage")
    # Check if we have contacted the system
    if system_ip in response['contacted']:
        # Check  the "failed" flag
        if not response['contacted'][system_ip].get('failed', False):
            result = APIResult(True, response['contacted'][system_ip]['data'])
        else:
            api_log.error("Error calling module 'av_center_facts' msg:%s" % response['contacted'][system_ip].get('msg', "Unknown error"))
            result = APIResult(False, "Error calling module 'av_center_facts' msg:%s" % response['contacted'][system_ip].get('msg', "Unknown error"))
    else:
        api_log.error("Can't connect to system with IP %s msg:%s " % (system_ip, str(response['dark'][system_ip])))
        result = APIResult(False, "Can't connect to system with IP %s msg:%s " % (system_ip, str(response['dark'][system_ip])))
    return result
    
    
def package_list(system_ip):
    """
    Get a list with all the packages available

    Args:
        system_ip (str): String with a IP address.

    Returns:
        A tuple of the form (bool, data) where the first element is a true or false. The data parameter returns the error message on error or
        the dict with all the packages. Each package is a dic itself containing the version and the description of the package
    """
    command = "aptitude -F '%p@@@%v@@@%d' --disable-columns search ~i"
    
    try:
        response = ansible.run_module(host_list=[system_ip], module="command", use_sudo="True", args=command)
    except Exception, exc:
        api_log.error("Ansible Error: An error occurred while retrieving package list: %s" % str(exc))
        return False, "Ansible Error: An error occurred while retrieving package list: %s" % str(exc)

    (success, msg) = ansible_is_valid_response(system_ip, response)
    if not success:
        api_log.error(msg)
        return (False, "Something wrong happened getting the packages list: %s" % msg)

    packages = response['contacted'][system_ip]['stdout'].split("\n")
    result = {}
    
    for p in packages:
        p = p.split("@@@")
        
        pck = {}
        pck['version'] = p[1]
        pck['description'] = p[2]
        
        result[p[0]] = pck
    
    return success, result
    
    
# vim: set ts=4:expandtab
