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

import ConfigParser

from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.system.system import get_system_id, get_profile
from ansiblemethods.helper import fetch_file

ansible = Ansible()

def get_alienvault_version (system_ip):
    """ Return the alienvault version
    @system_ip: The IP address of the system
    """
    command = """executable=/bin/bash
        if dpkg -l | grep alienvault-professional > /dev/null;
          then pro="ALIENVAULT";
          else pro="OSSIM";
        fi;
        version=$(dpkg -l | grep ossim-cd-tools | awk '{print $3}' | awk -F'-' '{ print $1 }');
        echo "$pro $version"
        """
    response = ansible.run_module([system_ip], "shell", command)
    if system_ip in response ['dark'] :
        return (False, "get_alienvault_version: " + response['dark'][system_ip]['msg'])
    version = response['contacted'][system_ip]['stdout']
    return (True, version)

def get_installation_date (system_ip='127.0.0.1'):
    """ Return the string with the system installation information
    @system_ip: The IP address of the system
    """
    command = """executable=/bin/bash
    date=$(ls -latr /etc/ossim/.ossim_installer_version | cut -d' ' -f5,6,7,8);
    cd=$(cat /etc/ossim/.ossim_installer_version);
    echo "$date ($cd)"
    """
    response = ansible.run_module([system_ip], "shell", command)
    if system_ip in response ['dark'] :
        return (False, "get_installation_date" + response['dark'][system_ip]['msg'])
    installation_date = response['contacted'][system_ip]['stdout']
    return (True, installation_date)

def get_license_info (system_ip='127.0.0.1'):
    """
    Return a dictionary with the license information on '[appliance]' section
    @system_ip: The IP address of the system
    """
    license_info = 'NA'
    (success, dst) = fetch_file(system_ip, '/etc/ossim/ossim.lic', '/tmp')
    if not success:
        return (False, dst)

    parsed = ConfigParser.ConfigParser()
    parsed.read(dst)
    if 'appliance' in parsed.sections():
        license_info = dict(parsed.items('appliance'))

    return (True, license_info)


def get_about_info (system_ip='127.0.0.1'):
    """ Return the about string of system with system_ip
    @system_ip: The IP address of the system
    """
    (success, version) = get_alienvault_version(system_ip)
    if not success:
        #ToDo: Log error
        version = 'NA'

    (success, installation_date) = get_installation_date(system_ip)
    if not success:
        #ToDo: Log error
        installation_date = 'NA'
    (success, system_id) = get_system_id(system_ip)
    if not success:
        #ToDo: Log error
        system_id = 'NA'

    (success, profiles) = get_profile(system_ip)
    profile_str = ""
    if len(profiles) == 4:
        profile_str+="All In One"
    else:
        profile_str += ','.join(x for x in profiles)

    (success, license_data) = get_license_info(system_ip)
    if not success:
        license_info = 'NA'
    else:
        license_info = "\n\tsystem_id: %s" % license_data.get('system_id', '')
        if 'email' in license_data:
            license_info += "\n\temail: %s" % license_data.get('email', '')
            license_info += "\n\texpire: %s" % license_data.get('expire', '')
        if 'key' in license_data:
            license_info += "\n\tkey: %s" % license_data.get('key', '')

    about = "\nAlienVault Version: %s\n" % version
    about += "Installation Date: %s\n" % installation_date
    about += "System ID: %s\n" % system_id
    about += "Profile: %s\n" % profile_str
    about += "License: %s\n" % license_info

    return (True, about)
