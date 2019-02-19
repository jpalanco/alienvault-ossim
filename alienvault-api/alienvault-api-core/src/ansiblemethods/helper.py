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
from ansiblemethods.ansiblemanager import Ansible
from apiexceptions.ansible import APIAnsibleError, APIAnsibleBadResponse
import re
import os.path

ansible = Ansible()


def parse_av_config_response(response, system_ip):
    """
    Utility method to parse av_config library responses.
    """
    if system_ip in response['dark']:
        return False, response['dark'][system_ip]['msg']

    if 'failed' in response['contacted'][system_ip]:
        return False, response['contacted'][system_ip]['msg']

    return True, response['contacted'][system_ip]['data']


def read_file(host, file_name=None):
    response = ansible.run_module([host], 'command', "cat %s" % file_name)
    if host in response['dark']:
        return False, "read_file : " + response['dark'][host]['msg']

    contacted = response['contacted'][host]
    if contacted['rc'] != 0:
        return False, "read_file : " + contacted['stderr']

    return True, contacted['stdout']


def fetch_file(system_ip, src_file_path, dst_file_path, fail_on_missing=True, flat=False):
    """
    Copy a file from one or more remote systems.
    """
    args = {'src': src_file_path,
            'dest': dst_file_path,
            'fail_on_missing': fail_on_missing,
            'flat': flat}

    response = ansible.run_module([system_ip], 'fetch', args, use_sudo=True)
    if system_ip in response['dark']:
        return False, "system.fetch_file: " + str(response['dark'])
    if 'failed' in response['contacted'][system_ip]:
        return False, "system.fetch_file: " + str(response['contacted'])

    return True, response['contacted'][system_ip]['dest']


def file_exist(system_ip, file_name):
    """
    Check if a file exists or not.
    """
    try:
        response = ansible.run_module(host_list=[system_ip], module='stat', args="path=" + file_name)
    except Exception, exc:
        raise APIAnsibleError(str(exc))

    success, msg = ansible_is_valid_response(system_ip, response)
    if success:
        if response['contacted'][system_ip]['stat']['exists']:
            return True
        else:
            return False
    else:
        raise APIAnsibleBadResponse(str(msg))



def copy_file(host_list=[], args={}):
    """
    Copy a file to one or more remote systems.
    """
    response = ansible.run_module(host_list, 'copy', args, use_sudo=True)
    for host in host_list:
        if host in response['dark']:
            return False, "system.copy_file : " + response['dark'][host]['msg']
    return True, ''


def remove_file(host_list, file_name=None):
    """
    Remove a file from one or more remote systems.
    """
    if host_list is None:
        host_list = []
    response = ansible.run_module(host_list, 'command', 'rm -f {}'.format(file_name))
    for host in host_list:
        if host in response['dark']:
            return False, "remove_file : " + response['dark'][host]['msg']
    return True, ''


def remove_dir(host_list=[], dir_name=None):
    """
    Remove a directory from one or more remote systems.
    """
    response = ansible.run_module(host_list, 'command', 'rm -rf %s' % dir_name)
    for host in host_list:
        if host in response['dark']:
            return False, "remove_dir : " + response['dark'][host]['msg']
    return True, ''


def gunzip_file(system_ip, gzip_file=None, gunzipped_file=None):
    """
    Gunzip a file

    Args:
        system_ip (str): host IP
        gzip_file (str): path to gzip file
        gunzipped_file (str): path to gunzipped file

    Returns:
        success (bool): True if successful, False otherwise
        msg (str): error string message
    """
    response = ansible.run_module([system_ip], 'shell', 'gunzip -c %s > %s' % (gzip_file, gunzipped_file))
    success, msg = ansible_is_valid_response(system_ip, response)
    if not success:
        return False, "gunzip_file : %s" % msg
    return True, ''


def ansible_is_valid_response(system_ip, response):
    """Check whether a ansible response is ok or not"""
    try:
        if system_ip in response['dark'] or 'unreachable' in response:
            return False, "Something wrong happened while running ansible command %s" % str(response)
        if 'failed' in response['contacted'][system_ip]:
            return False, "Something wrong happened while running ansible command %s" % str(response)
    except Exception as err:
        api_log.error(str(err))
        return False, "Something wrong happened while running ansible command %s" % str(response)
    return True, ""


def ansible_is_valid_playbook_response(system_ip, response):
    """Check whether a ansible response is ok or not"""
    try:
        # {'192.168.5.122': {'unreachable': 0, 'skipped': 0, 'ok': 6, 'changed': 5, 'failures': 1}}
        if system_ip not in response:
            return False, "Something wrong happened while running ansible command %s" % str(response)
        if response[system_ip]['unreachable'] > 0 or response[system_ip]['failures'] > 0:
            return False, "Something wrong happened while running ansible command %s" % str(response)
    except Exception as err:
        api_log.error(str(err))
        return False, "Something wrong happened while running ansible command %s" % str(response)
    return True, ""


def local_copy_file(system_ip, src_file, dst_file):
    """
    Make a local copy of a file using the command module.
    Workaround for ansible "copy" module bug with permissions other=0.
    Change permissions to 777 after copying so we can use "copy" module
    with the resulting copies.

    Args:
        system_ip (str): system ip
        src_file (str): path to source file
        dst_file (str): path to destination file

    """
    cp_command = "cp -f %s %s" % (src_file, dst_file)
    chmod_command = "chmod %s %s" % ("777", dst_file)
    response = ansible.run_module([system_ip], module='command', args=cp_command, use_sudo=True)
    if system_ip in response['dark']:
        return False, "system.local_copy_file : " + response['dark'][system_ip]['msg']
    response = ansible.run_module([system_ip], module='command', args=chmod_command, use_sudo=True)
    if system_ip in response['dark']:
        return False, "system.local_copy_file : " + response['dark'][system_ip]['msg']
    return True, ''


def get_files_in_path(system_ip, path):
    """
    Get the list of files in $path of $system_ip
    """
    response = ansible.run_module([system_ip], module='shell', args='ls --time-style=long-iso -l %s|grep -v "^total"' % path)
    success, msg = ansible_is_valid_response(system_ip, response)
    if not success:
        return False, msg
    lines = response['contacted'][system_ip]['stdout'].splitlines()
    result = {}
    pattern = r'(?P<mode>.*?)\s+(?P<hl>\d+)\s+(?P<user>.*?)\s+(?P<group>.*?)\s+(?P<size>\d+)\s+(?P<date>(.*?\s+.*?))\s+(?P<filename>.*)'
    for line in lines:
        m = re.match(pattern, line)
        result[os.path.join(path, m.group('filename'))] = m.groupdict()
    return True, result


def fire_trigger(system_ip, trigger, execute_trigger=True):
    """ Fires a trigger described by trigger_name

    Args:
        trigger: Name of string to be fired
    Return:
        True on success, False on failure
    """
    command_fire = "dpkg-trigger --no-await %s" % trigger
    response = ansible.run_module([system_ip], module='command', args=command_fire, use_sudo=True)
    if system_ip in response['dark']:
        return False, "system.fire_trigger : " + response['dark'][system_ip]['msg']

    if execute_trigger is True:
        command_configure = "dpkg --pending --configure"
        response = ansible.run_module([system_ip], module='command', args=command_configure, use_sudo=True)
        if system_ip in response['dark']:
            return False, "system.configure_trigger : " + response['dark'][system_ip]['msg']

    return True, ""
