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

import api_log
import ConfigParser

from time import ctime
from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.system.system import get_system_id, get_profile
from ansiblemethods.helper import fetch_file, remove_dir

ansible = Ansible()


def get_alienvault_version(system_ip='127.0.0.1'):
    """ Return the alienvault version
    @system_ip: The IP address of the system
    """
    command = """executable=/bin/bash
        if dpkg -l | grep alienvault-professional > /dev/null;
          then pro="ALIENVAULT";
          else pro="OSSIM";
        fi;
        version=$(dpkg -l | grep ossim-cd-tools | awk '{print $3}' | awk -F'-' '{ print $1 }');
        extravers=$(dpkg -l | grep "alienvault-vmware\|alienvault-hw\|alienvault-ami\|ossim-ami\|alienvault-hyperv" |  awk '{print $2}')
        if dpkg -l | grep "alienvault-vmware\|alienvault-hw\|alienvault-ami\|ossim-ami\|alienvault-hyperv" > /dev/null;
            then echo "$pro $version ($extravers)";
            else echo "$pro $version";
        fi;
        """
    response = ansible.run_module(
        host_list=[system_ip],
        module="shell",
        args=command
    )
    if system_ip in response['dark']:
        return False, "get_alienvault_version: " + response['dark'][system_ip]['msg']
    version = response['contacted'][system_ip]['stdout']
    return True, version


def get_alienvault_platform(system_ip='127.0.0.1'):
    command = """
    executable=/bin/bash dpkg -l | grep -oh "alienvault-vmware\|alienvault-hw\|alienvault-ami\|alienvault-hyperv" |  awk '{print $1}'
    """
    try:
        response = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args=command
        )
        platform = response['contacted'][system_ip]['stdout']
    except Exception as exc:
        return False, "[get_alienvault_platform] Failed: {}".format(exc)

    return True, platform


def get_alienvault_appliance_type(system_ip='127.0.0.1'):
    command = """
        executable=/bin/bash dpkg -l | grep "alienvault-vmware\|alienvault-hw\|alienvault-ami\|alienvault-hyperv" | awk '{print $2}'
        """
    try:
        response = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args=command
        )
        appliance_type = response['contacted'][system_ip]['stdout']
    except Exception as exc:
        return False, "[get_alienvault_appliance_type] Failed: {}".format(exc)

    return True, appliance_type


def get_local_ami_public_ip(system_ip='127.0.0.1'):
    _, current_platform = get_alienvault_platform(system_ip)
    if current_platform != 'alienvault-ami':
        return False, "[get_local_ami_public_ip] Failed: The current platform is not AMI ({})".format(current_platform)

    try:
        command = """
        executable=/bin/bash curl checkip.amazonaws.com
        """
        response = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args=command
        )
        public_ip = response['contacted'][system_ip]['stdout']
    except Exception as exc:
        return False, "[get_local_ami_public_ip] Failed: {}".format(exc)

    return True, public_ip


def get_threat_intelligence_version(system_ip='127.0.0.1'):
    command = """
        executable=/bin/bash
        ti_version=`dpkg-query --showformat='${Version}' --show alienvault-directives-pro 2>1 /dev/null`;
        [ -z $ti_version ] && echo '-' || echo $ti_version;
        """
    try:
        response = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args=command
        )
        version = response['contacted'][system_ip]['stdout']
    except Exception as exc:
        return False, "[get_threat_intelligence_version] Failed: {}".format(exc)

    return True, version


def get_platform_info(ip_str):
    command = """
        executable=/bin/bash 
        platform=`dpkg -l | grep -oh "alienvault-vmware\|alienvault-hw\|alienvault-ami\|alienvault-hyperv" |  awk '{print $1}'`;
        echo $platform;
        ti_version=`dpkg-query --showformat='${Version}' --show alienvault-directives-pro 2>1 /dev/null`;
        [ -z $ti_version ] && echo '-' || echo $ti_version;
        dpkg -l | grep "alienvault-vmware\|alienvault-hw\|alienvault-ami\|alienvault-hyperv" | awk '{print $2}';
        [ $platform == 'alienvault-ami' ] && curl checkip.amazonaws.com || echo 'no ami'
        """
    try:
        response = ansible.run_module(
            host_list=[ip_str],
            module="shell",
            args=command
        )
        res = response['contacted'][ip_str]['stdout'].split('\n')
    except Exception as exc:
        api_log.error('[get_platform_info] Failed: {}'.format(exc))
        return False, "[get_platform_info] Failed: {}".format(exc)

    return True, res


def get_is_professional(system_ip='127.0.0.1'):
    """ Return if the system is professional
    @system_ip: The IP address of the system
    """
    command = """
    executable=/bin/bash dpkg -l alienvault-professional > /dev/null
    """
    try:
        response = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args=command
        )
        if system_ip in response['dark']:
            return False, response['dark'][system_ip]['msg']
        if 'failed' in response['contacted'][system_ip]:
            return False, response['contacted'][system_ip]['msg']

        is_pro = response['contacted'][system_ip]['rc'] == 0
    except Exception, e:
        return False, "[get_is_professional] %s" % str(e)

    return True, is_pro


def get_installation_date(system_ip='127.0.0.1'):
    """ Return the string with the system installation information
    @system_ip: The IP address of the system
    """
    installer_file = "/etc/ossim/.ossim_installer_version"

    response = ansible.run_module(
        host_list=[system_ip],
        module="stat",
        args="path=%s" % installer_file
    )
    if system_ip in response['dark']:
        return False, "get_installation_date" + response['dark'][system_ip]['msg']

    if not response['contacted'][system_ip]['stat']['exists']:
        return False, "get_installation_date: File /etc/ossim/.ossim_installer_version doesn't exists"

    installation_date = response['contacted'][system_ip]['stat']['mtime']

    response = ansible.run_module(
        host_list=[system_ip],
        module="command",
        args="cat %s" % installer_file
    )
    if system_ip in response['dark']:
        return False, "get_installation_date" + response['dark'][system_ip]['msg']

    installation_version = response['contacted'][system_ip]['stdout']

    return True, "%s (%s)" % (ctime(installation_date), installation_version)


def get_license_info(system_ip='127.0.0.1'):
    """
    Return a dictionary with the license information on '[appliance]' section
    @system_ip: The IP address of the system
    """
    license_info = 'NA'
    (success, dst) = fetch_file(system_ip, '/etc/ossim/ossim.lic', '/tmp')
    if not success:
        return False, dst

    parsed = ConfigParser.ConfigParser()
    parsed.read(dst)
    if 'appliance' in parsed.sections():
        license_info = dict(parsed.items('appliance'))

    (success, msg) = remove_dir([system_ip], '/tmp/%s' % system_ip)
    if not success:
        return False, msg
    return True, license_info


def get_about_info(system_ip='127.0.0.1'):
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
        profile_str += "All In One"
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

    return True, about
