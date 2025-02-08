# -*- coding: utf-8 -*-
#
# License:
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

import traceback
import api_log
from ansiblemethods.ansiblemanager import Ansible, PLAYBOOKS
from ansiblemethods.helper import ansible_is_valid_response, fetch_file

ansible = Ansible()


def ansible_run_nmap_scan(sensor_ip, target, scan_type, rdns, privileged_mode, scan_timing, autodetect, scan_ports, job_id):
    """Runs a nmap scan on the given sensor and with the given parameters.
    Args:
        sensor_ip: The system IP where you want to get the [sensor]/interfaces from ossim_setup.conf
        target: IP address of the component where the NMAP will be executed
        scan_type: Sets the NMAP scan type
        rdns: Tells Nmap to do reverse DNS resolution on the active IP addresses it finds
        privileged_mode: Use --privileged if enabled or --unprivileged if disabled
        scan_timing: Set the timing template
        autodetect: Aggressive scan options (enable OS detection)
        scan_ports: Scan only specified ports

    Returns:
        A tuple (success|error, data | msgerror)
    """
    args = "target=%s" % target
    if scan_type is not None:
        args += " scan_type=%s" % scan_type
    if rdns is not None:
        args += " rdns=%s" % str(rdns).lower()
    if privileged_mode is not None:
        args += " privileged_mode=%s" % str(privileged_mode).lower()
    if scan_timing is not None:
        args += " scan_timming=%s" % scan_timing
    if autodetect is not None:
        args += " autodetect=%s" % str(autodetect).lower()
    if scan_ports is not None:
        args += " scan_ports=%s" % scan_ports
    args += " job_id={0}".format(job_id)
    try:
        response = ansible.run_module([sensor_ip], 'av_nmap', args)
        (success, msg) = ansible_is_valid_response(sensor_ip, response)
        if not success:
            api_log.error("[ansible_run_nmap_scan] Error: %s" % str(msg))
            return False, str(msg)
        data = ""
        if response['contacted'][sensor_ip]['data'] != '':
            data = response['contacted'][sensor_ip]['data']
    except Exception as exc:
        api_log.error("[ansible_run_nmap_scan] Error: %s" % str(exc))
        return False, str(exc)
    return True, data


def ansible_nmap_get_scan_progress(sensor_ip, task_id):
    """Retrieves the scan progress
    Args:
        sensor_ip: the sensor ip where the scan is running
        task_id: The task id to identify the scan progress.
    Returns:
        success (boolean): True or False
        data(dict) {"scanned_hosts":-1, "target_number":-1}
    """
    data = {"scanned_hosts": -1, "target_number": -1}
    try:
        scan_file = "/tmp/{0}.scan".format(task_id)
        targets_file = "/tmp/{0}.targets".format(task_id)
        command = "wc -l {0} {1} | head -2 | awk '{2}' | xargs".format(scan_file, targets_file, '{print $1}')
        response = ansible.run_module([sensor_ip], "shell", command)
        (success, msg) = ansible_is_valid_response(sensor_ip, response)
        if not success:
            raise Exception("Invalid response {0}".format(msg))
        if response['contacted'][sensor_ip]['stdout'] != '':
            (shosts, nhosts) = response['contacted'][sensor_ip]['stdout'].split(' ', 1)
            data['scanned_hosts'] = int(shosts)
            data['target_number'] = int(nhosts)
    except Exception:
        raise
    return data


def ansible_nmap_stop(sensor_ip, task_id):
    """Stops the given scan"""
    try:
        pid_file = "/tmp/{0}.scan.pid".format(task_id)
        command = "kill -9 $(cat {0})".format(pid_file)
        response = ansible.run_module([sensor_ip], "shell", command)
        (success, msg) = ansible_is_valid_response(sensor_ip, response)
        if not success:
            api_log.error("[ansible_nmap_stop] Error: %s" % str(msg))
            return False, str(msg)
    except Exception as exc:
        api_log.error("[ansible_nmap_stop] Error: %s" % str(exc))
        return False, str(exc)
    return True, ""


def ansible_nmap_purge_scan_files(sensor_ip, task_id):
    """Removes the files used during the scan"""
    try:
        command = "rm -rf /tmp/{0}*".format(task_id)
        response = ansible.run_module([sensor_ip], "shell", command)
        (success, msg) = ansible_is_valid_response(sensor_ip, response)
        if not success:
            api_log.error("[ansible_nmap_purge_scan_files] Error: %s" % str(msg))
            return False, str(msg)
    except Exception as exc:
        api_log.error("[ansible_nmap_purge_scan_files] Error: %s" % str(exc))
        return False, str(exc)
    return True, ""


def ansible_get_partial_results(sensor_ip, task_id):
    """Get partial nmap results if exists"""
    try:
        scan_file = "/tmp/{0}.scan".format(task_id)
        (success, dst) = fetch_file(sensor_ip, scan_file, '/var/tmp')
        if not success:
            return False, dst
    except Exception as exc:
        api_log.error("[ansible_get_partial_results] Error: %s" % str(exc))
        return False, str(exc)
    return True, dst
