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

import traceback
import re
import time
import os.path
import api_log
from base64 import b64decode
from ansiblemethods.ansiblemanager import Ansible, PLAYBOOKS
from ansiblemethods.helper import parse_av_config_response, read_file, ansible_is_valid_response, ansible_is_valid_playbook_response
from ansiblemethods.ansibleinventory import AnsibleInventoryManager

ansible = Ansible()

def get_system_id(system_ip):
    """ Returns the system Id from a given ip
    @param system_ip: the host system ip
    """
    host_list = []
    host_list.append(system_ip)
    uuid_regex = re.compile('^[a-fA-F0-9]{8}\-[a-fA-F0-9]{4}\-[a-fA-F0-9]{4}\-[a-fA-F0-9]{4}\-[a-fA-F0-9]{12}$')
    
    # 1- Try alienvault-system-id
    response = ansible.run_module([system_ip], "command", "/usr/bin/alienvault-system-id")
    success, msg = ansible_is_valid_response(system_ip, response)
    if success:
        system_id = response['contacted'][system_ip]['stdout']
    
    # 2- When error, try the old way
    else:
        # 2.1- Read center file
        (success, system_id) = read_file(system_ip, "/etc/alienvault-center/alienvault-center-uuid")
        if not success:
            # 2.2- Call ansible method
            response = ansible.run_module(host_list, "av_setup", "filter=ansible_product_uuid")
            if system_ip in response['dark']:
                return (False, "[get_system_id]: " + response['dark'][system_ip]['msg'])
            else:
                if system_ip in response['contacted']:
                    system_id = response['contacted'][system_ip]['ansible_facts']['ansible_product_uuid'].lower()
                else:
                    return (False, "[get_system_id]: Error getting system ID")
    
    # Check the system_id is valid
    if not system_id or not uuid_regex.match(system_id):
        return (False, "[get_system_id]: Error getting system ID")
    
    return (True, system_id)
    

def get_system_load(system_ip):
    """
    @system_ip
    Get the uptime of the host with IP @sensor_ip. Return the ther parameter,
    (load average during the last 15 minutes)
    We use a regex to obtain the load from the output of uptime:
    " 11:32:20 up  1:43,  2 users,  load average: 0.18, 0.10, 0.09"
    r'.*?load\saverage:\s+(.*?),\s(.*?),\s(.*?)$'
    """
    reuptime = re.compile(r'.*?load\saverage:\s+(.*?),\s(.*?),\s(.*?)$')
    try:
        response = ansible.run_module([system_ip], "shell", "/usr/bin/uptime")
        if system_ip in response['dark']:
            return (False, "get_system_load " + response['dark'][system_ip]['msg'])
        else:
            uptimeout = response['contacted'][system_ip]['stdout']
            #Capture
            m = reuptime.match (uptimeout)
            if m<>None:
                loadcpu = float (m.group(3))
                return (True,loadcpu)
            else:
                return (False,"get_system_load " + "Can't match the uptime output '%s'" % uptimeout)

    except ValueError:
        return (False,"get_system_load " + "Can't get load output from")

    except Exception as e:
        return (False,"Ansible error: " + str (e) + "\n" + traceback.format_exc())

def get_profile (system_ip="127.0.0.1"):
    """Returns a list of profiles
    :system_ip System IP of which we want to know the available profiles
    :return A list of available profiles or a empty list
    """
    try:
        profile_list = []
        command = """executable=/bin/bash
PROFILES=""
dpkg -l alienvault-dummy-database | grep '^ii' > /dev/null 2>&1
RETVAL=$?
if [ $RETVAL -eq 0 ]
  then
    PROFILES="$PROFILES DATABASE,"
fi
dpkg -l alienvault-dummy-sensor| grep '^ii' > /dev/null 2>&1
RETVAL=$?
if [ $RETVAL -eq 0 ]
  then
    PROFILES="$PROFILES SENSOR,"
fi
dpkg -l alienvault-dummy-server| grep '^ii' > /dev/null 2>&1
RETVAL=$?
if [ $RETVAL -eq 0 ]
  then
    PROFILES="$PROFILES SERVER,"
fi

dpkg -l alienvault-dummy-framework| grep '^ii' > /dev/null 2>&1
RETVAL=$?
if [ $RETVAL -eq 0 ]
  then
    PROFILES="$PROFILES FRAMEWORK"
fi
echo $PROFILES
        """
        response = ansible.run_module(host_list=[system_ip], module="shell", args=command)
        response = response['contacted'][system_ip]['stdout'].replace(' ', '')
        profile_list = response.split(',')
    except Exception, e:
        api_log.error("get_plugin_list_by_location: Error Retrieving the plugin list by location. %s" % e)
        return (False, '')

    return (True, profile_list)

def get_system_setup_data (system_ip):
    """Returns the data from setup module from a given ip"""
    response = ansible.run_module([system_ip], "av_setup", "")
    if system_ip in response['dark']:
        return (False, "Error getting system data : %s" % response['dark'][system_ip])

    return(True, response['contacted'][system_ip]['ansible_facts'])

def get_root_disk_usage (system_ip):
    # Filesystem    Type    Size  Used Avail Use% Mounted on
    # /dev/sda1     ext3     31G  9.3G   20G  33% /
    rt = True
    percentage = 0.0
    try:
        data = ansible.run_module(host_list=[system_ip], module="shell", args="executable=/bin/bash df / | tail -1 |df / | tail -1 | awk '{print $5}'|sed 's/%//'")
        output = data['contacted'][system_ip]['stdout']
        percentage = float(output)
    except Exception,e:
        rt = False
    return (rt, percentage)

def get_service_status (system_ip, service):
    """
    Module check process: https://raw.github.com/ginsys/ansible-plugins/devel/library/check_process

    devel@develplus:~$ ansible 192.168.230.5  -u root -m check_process -a "name=snort mincount=1"
    192.168.230.5 | FAILED >> {
            "changed": false,
            "failed": true,
            "maxcount": null,
            "mincount": 1,
            "msg": "Number of running processes (0) is smaller than 1",
            "name": "snort",
            "pids": [],
            "running": 0
        }

    devel@develplus:~$ ansible 192.168.230.5  -u root -m check_process -a "name=suricata mincount=1"
    192.168.230.5 | success >> {
            "changed": false,
            "maxcount": null,
            "mincount": 1,
            "msg": "Number of running processes (1) is larger than 1",
            "name": "suricata",
            "pids": [
                "18933"
            ],
            "running": 1
        }
    """
    host_list = []
    host_list.append(system_ip)
    d = ansible.run_module(host_list,"check_process", "name=%s mincount=1" % service)
    running = False
    try:
        if d["contacted"][system_ip]["running"] == 1:
            running = True
    except:
        pass
    return running

def get_doctor_data (host_list=[], args={}):
    """
    Run AlienVault Doctor in the target machine(s) and return the results.
    """
    return ansible.run_module (host_list, 'av_doctor', args)

def install_debian_package(host_list=[], debian_package=None):
    """
    Install a Debian package in one or more remote systems.
    """
    response = ansible.run_module(host_list, 'command', '/usr/bin/dpkg -i --force-confnew %s' % debian_package)
    for host in host_list:
        if host in response['dark']:
            return (False, "install_debian_package : " + response['dark'][host]['msg'])
    return (True, '')

def reconfigure (system_ip):
    """
    Runs an alienvault-reconfigure
    :param system_ip: The system IP where you want to run the alienvault-reconfig
    :return A tuple (success, error_message).
    """
    rt = True
    error_str = ""
    try:
        command = """executable=/bin/bash alienvault-reconfig -c --center"""
        response = ansible.run_module(host_list=[system_ip], module="shell", args=command)
        if response['contacted'].has_key(system_ip):
            return_code = response['contacted'][system_ip]['rc']
            error_str = response['contacted'][system_ip]['stderr']
            if return_code != 0:
                rt = False
        else:
            rt = False
            error_str = response['dark'][system_ip]['msg']
    except Exception, e:
        trace = traceback.format_exc()
        api_log.error("Ansible Error: An error occurred while running alienvault-reconfig: %s \n trace: %s" % (str(e), trace))
        rt = False
    return rt, error_str

def get_av_config(system_ip, path_dict):
    """
    @param system_ip: The system IP
    @param path: the av_config file path dictionary (i.e '[sensor]detectors')
    @return A tuple (sucess|error, data|msgerror)
    """
    path_str = ' '.join(['%s=True' % (key) for (key, _value) in path_dict.items()])

    response = ansible.run_module(host_list=[system_ip],
                                  module="av_config",
                                  args="op=get %s" % path_str)
    return parse_av_config_response(response, system_ip)

def set_av_config(system_ip, path_dict):
    """
    @param system_ip: The system IP
    @param path: the av_config file path dictionary (i.e '[sensor]detectors')
    @return A tuple (sucess|error, data|msgerror)
    """
    path_str = ' '.join(['%s=%s' % (key, value) for (key, value) in path_dict.items()])
    #
    #
    #flush_cache(namespace="system")

    response = ansible.run_module(host_list=[system_ip],
                                  module="av_config",
                                  args="op=set %s" % path_str)
    return parse_av_config_response(response, system_ip)


def ansible_add_ip_to_inventory(system_ip):
    try:
        aim = AnsibleInventoryManager()
        aim.add_host(system_ip)
        aim.save_inventory()
    except Exception, msg:
        api_log.error(str(msg))
        return False, 'Error adding ip to ansible inventory'
    return True, ''


def ansible_add_system(local_system_id, remote_system_ip, password):
    """
    Add a new system.
    Create and set the crypto files and update the ansible inventory manager
    """
    result = False
    response = None

    # sanity check
    if not os.path.isfile('/var/ossim/ssl/local/ssh_capubkey.pem'):
        response = "Cannot access public key file"
        return (result, response)

    evars = {"remote_system_ip": "%s" % remote_system_ip,
             "local_system_id": "%s" % local_system_id}

    response = ansible.run_playbook(playbook=PLAYBOOKS['SET_CRYPTO_FILES'],
                                    host_list=[remote_system_ip],
                                    extra_vars=evars,
                                    ans_remote_user="root",
                                    ans_remote_pass=password)

    if response[remote_system_ip]['unreachable'] == 0 and response[remote_system_ip]['failures'] == 0:
        result = True
        response = "System with IP %s added correctly" % (remote_system_ip)
    else:
        result = False
        api_log.error(str(response))
        response = "Cannot add system with IP %s. Please verify that the system is reachable and the password is correct." % (remote_system_ip)

    # Add the system to the Ansible Inventory
    aim = AnsibleInventoryManager()
    aim.add_host(remote_system_ip)
    aim.save_inventory()

    return (result, response)


def ping_system(system_ip):
    try:
        response = ansible.run_module(host_list=[system_ip],
                                      module="ping", args="")
    except Exception as err:
        return False, "Something wrong happen while pingin the system:%s %s" % (system_ip, str(err))
    if 'dark' in response and system_ip in response['dark'] or 'unreachable' in response:
        return False, "System unreacheable"
    if 'contacted' in response and system_ip in response['contacted'] and 'pong' \
            in response['contacted'][system_ip]['ping']:
        return True, "OK"
    return False,""


def ansible_remove_certificates(system_ip, system_id_to_remove):
    """Removes all the ssh certificates data:
    :param system_ip: The system ip where you want to remove the keys
    :param system_id_to_remove: The system_id of the system you want to remove."""
    try:
        command = "rm -r /var/ossim/ssl/%s || true" % system_id_to_remove
        response = ansible.run_module(host_list=[system_ip], module="shell", args=command,use_sudo=True)
        success, msg = ansible_is_valid_response(system_ip,response)
        if not success:
            return False,"Something wrong happen while removing the ssl folder: %s" % str(msg)
        return_code = int(response['contacted'][system_ip]['rc'])
        output_error = response['contacted'][system_ip]['stderr']
        if return_code != 0:
            return False, "Something wrong happen while removing the ssl folder: %s" % str(output_error)
    except Exception as err:
        return False,"Something wrong happen while removing the ssl folder: %s" % str(err)
    return True,""


def ansible_get_hostname(system_ip):
    """ Returns the system hostname from a given ip
    @param system_ip: the host system ip
    """
    response =  ansible.run_module([system_ip], "av_setup", "filter=ansible_hostname")
    if not ansible_is_valid_response(system_ip, response):
        return (False, "Something wrong happend getting the system hostname")

    hostname = response['contacted'][system_ip]['ansible_facts']['ansible_hostname']
    return (True, hostname)


def ansible_get_system_info(system_ip):
    """ Returns: Info from a given ip:
    - the system id
    - the system hostname
    - the system alienvault profile
    - the server_id
    @param system_ip: the host system ip
    """
    response =  ansible.run_module([system_ip], "av_system_info", args="", use_sudo=True)
    success, msg = ansible_is_valid_response(system_ip, response)
    if not success:
        api_log.error(msg)
        return (False, "Something wrong happend getting the system data")

    return (True, response['contacted'][system_ip]['data'])


def restart_mysql(system_ip):
    """
    Restart the MySQL server
    :param system_ip: System IP
    """
    rc = True
    try:
        response = ansible.run_module(host_list=[system_ip], module="service", args="name=mysql state=restarted")
    except Exception, e:
        response = "Error restarting the alienvault agent: %s" % str(e)
        rc = False
    return (rc, str(response['contacted'][system_ip]['state']))


def generate_sync_sql(system_ip, restart=False):
    """
    Generate sync.sql file for parent server
    :param restart: pass param restart to asset_sync.sh script
    """
    if restart:
        command = '/usr/share/ossim/scripts/assets_sync.sh restart'
    else:
        command = '/usr/share/ossim/scripts/assets_sync.sh'

    try:
        response = ansible.run_module(host_list=[system_ip], module="command", use_sudo="True", args=command)
    except Exception, exc:
        api_log.error("Ansible Error: An error occurred while running generate_sync_sql: %s" % str(exc))
        return False, "Ansible Error: An error occurred while running generate_sync_sql: %s" % str(exc)

    (success, msg) = ansible_is_valid_response(system_ip, response)
    return success, msg

def ansible_run_async_reconfig(system_ip, log_file="/tmp/system_reconfigure.log"):
    """Runs an asynchronous reconfigure on the given system

    Args:
      system_ip(str): The system_ip of the system to configure.
      log_file(str): The path where the the alienvault-reconfig command should throw the logs. 

    Returns:
      (boolean, str): A tuple containing the result of the execution. On success msg will be the remote log file.

    Examples:

      >>> ansible_run_async_update("192.168.5.123","/tmp/update.log")
      (True,"/tmp/update.log")

      >>> ansible_run_async_update("192.168.5.999","/tmp/update.log")
      (False, "Something wrong happened while running ansible command {'192.168.1.198': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}")
    """

    log_file = "/tmp/system_reconfigure_%10.2f.log" % time.time()
    evars = {"target": "%s" % system_ip,
             "log_file": "%s" % log_file}


    response = ansible.run_playbook(playbook=PLAYBOOKS['ASYNC_RECONFIG'],
                                    host_list=[system_ip],
                                    extra_vars=evars,
                                    use_sudo=True)

    success, msg = ansible_is_valid_playbook_response(system_ip, response)
    if not success:
        return False, msg
    return success, log_file


def ansible_run_async_update(system_ip, log_file="/tmp/system_update.log", only_feed=False, update_key=""):
    """Runs an asynchronous update on the given system

    Args:
      system_ip(str): The system_ip of the system to update.
      log_file(str): The path where the the alienvault-update command should throw the logs. 
      only_feed(boolean): Update only the feed
      update_key(str): Upgrade key

    Returns:
      (boolean, str): A tuple containing the result of the execution. On success msg will be the remote log file.

    Examples:

      >>> ansible_run_async_update("192.168.5.123","/tmp/update.log")
      (True,"/tmp/update.log")

      >>> ansible_run_async_update("192.168.5.123","/tmp/update.log",only_feed=True)
      (True,"/tmp/update.log")

      >>> ansible_run_async_update("192.168.5.999","/tmp/update.log")
      (False, "Something wrong happened while running ansible command {'192.168.1.198': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}")

    """

    log_file = "/tmp/system_update_%10.2f.log" % time.time()
    if only_feed:
        log_file = "/tmp/system_update_feed_%10.2f.log" % time.time()
    if update_key!="":
        log_file = "/tmp/system_update_uc_%10.2f.log" % time.time()

    evars = {"target": "%s" % system_ip,
             "log_file": "%s" % log_file,
             "only_feed": only_feed,
             "update_key": update_key}


    response = ansible.run_playbook(playbook=PLAYBOOKS['ASYNC_UPDATE'],
                                    host_list=[system_ip],
                                    extra_vars=evars,
                                    use_sudo=True)

    success, msg = ansible_is_valid_playbook_response(system_ip, response)
    if not success:
        return False, msg
    return success, log_file


def ansible_check_if_process_is_running(system_ip, ps_filter):
    """Check whether a process is running or not
    Args:
      system_ip(str): The system IP where we would like to run the ps filter
      ps_filter(str): Filter to grep the ps aux command
    
    Returns:
      (boolean,int): A tuple containing whether the operation was well or not and the number the process running that meet the filter
    """
    try:
        rc = 0
        reconfig_running = False
        cmd = 'ps aux | grep "%s" | grep -v grep | grep -v tail | wc -l' % re.escape(ps_filter)
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo="True", args=cmd)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        rc = int(response['contacted'][system_ip]['stdout'])
    except Exception as exc:
        api_log.error("ansible_check_if_process_is_running: <%s>" % str(exc))
        return False, 0

    return success, rc


def ansible_get_process_pid(system_ip, ps_filter):
    """Check whether a process is running or not
    Args:
      system_ip(str): The system IP where we would like to run the ps filter
      ps_filter(str): Filter to grep the ps aux command

    Returns:
      (boolean,int): A tuple containing whether the operation was well or not and the PID of the process running that meet the filter (0 = not running)
    """
    try:
        cmd = 'ps aux | grep "%s" | grep -v grep | grep -v tail | tr -s " " | cut -d " " -f 2 | head -n 1' % re.escape(ps_filter)
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo="True", args=cmd)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if not success:
            api_log.error("[ansible_get_process_pid] Error: %s" % str(msg))
            return False, 0

        pid = response['contacted'][system_ip]['stdout']
        if pid:
            pid = int(pid)
        else:
            pid = 0
    except Exception as exc:
        api_log.error("[ansible_get_process_pid] Error: %s" % str(exc))
        return False, 0

    return success, pid


def ansible_check_asynchronous_command_return_code(system_ip, rc_file):
    """Check the return code of a previously asychronous command 
    Args:
      system_ip(str): The system IP where we would like to run
      rc_file(str): The return code file 
    
    Returns:
      (boolean,int): A tuple containing whether the operation was well 
    """
    reg = r"/tmp/system_(update|update_feed|update_uc|reconfigure)_\d{10}\.\d{2}\.log.rc"
    if re.match(reg, rc_file) is None:
        return False, "Invalid return code file"
    try:
        destination_path = "/tmp/ansible/"
        response = ansible.run_module(host_list=[system_ip], module="fetch", args="dest=%s src=%s flat=yes" % (destination_path, rc_file), use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)

        if not result or not 'dest' in  response['contacted'][system_ip]:
            return False, "Something wrong happen while fetching the return code file: %s" % msg

        # The content of the return code file should be a number.
        # The content of the return code file should be 0 for success. 
        rc_file_path= response['contacted'][system_ip]['dest']
        if not os.path.exists(rc_file_path):
            return False, "The local return code file doesn't exist"
        rc_file_fd = open(rc_file_path,'r')
        data = rc_file_fd.read()
        rc_file_fd.close()
        os.remove(rc_file_path)
        try:
            rc_code = int(data)
        except:
            return False, "The return code file doesn't contain a return code" 
        if rc_code != 0:
            return False, "Rerturn code is different from 0 <%s>" % str(rc_code)

    except Exception as err:
        return False, "An error occurred while retrieving the return code file <%s>" % str(err)
    return True, ""


def ansible_get_asynchronous_command_log_file(system_ip, log_file):
    """Retrieves the asynchronous command log file
    Args:
      system_ip(str): The system IP where we would like to run
      rc_file(str): The return code file

    Returns:
      (boolean,int): A tuple containing whether the operation was well
    """

    reg = r"/tmp/system_(update|update_feed|update_uc|reconfigure)_\d{10}\.\d{2}\.log"
    if re.match(reg, log_file) is None:
        return False, "Invalid return code file"
    try:
        destination_path = "/tmp/ansible/logs/"
        response = ansible.run_module(host_list=[system_ip], module="fetch", args="dest=%s src=%s flat=yes" % (destination_path, log_file), use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result or not 'dest' in  response['contacted'][system_ip]:
            return False, "Something wrong happen while fetching the return code file: %s" % msg
        # The content of the return code file should be a number.
        # The content of the return code file should be 0 for success.
        rc_file_path = response['contacted'][system_ip]['dest']
        if not os.path.exists(rc_file_path):
            return False, "The local return code file doesn't exist"

    except Exception as err:
        return False, "An error occurred while retrieving the return code file <%s>" % str(err)

    return True, rc_file_path


def delete_parent_server(system_ip, server_id):
    """
    Delete server entry from remote system's databases
    :param system_ip: ip address of the remote system
    :param server_id: server id to remove
    """
    command = """echo "CALL server_delete_parent('%s');" | ossim-db""" % server_id

    try:
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo="True", args=command)
    except Exception, exc:
        api_log.error("Ansible Error: An error occurred while running generate_sync_sql: %s" % str(exc))
        return False, "Ansible Error: An error occurred while running generate_sync_sql: %s" % str(exc)

    (success, msg) = ansible_is_valid_response(system_ip, response)
    return success, msg


def ansible_get_update_info(system_ip):
    """Retrieves information about the system packages.

    Args:
      system_ip(str): IP of the system of which we want info

    Returns:
      success(Boolean), msg(str): A tuple containing the result of the query and the data
    """
    try:
        response = ansible.run_module(host_list=[system_ip], module="av_update_info", use_sudo=True, args={})
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if success:
            msg = response['contacted'][system_ip]['data']

    except Exception as err:
        api_log.error("[get_packages_info] An error occurred while retrieving the system's package info <%s>" % str(err))
        return False, "[get_packages_info] An error occurred while retrieving the system's package info <%s>" % str(err)

    return success, msg


def ansible_download_release_info(system_ip):
    """Download release notes from alienvault.com

    Args:
        system_ip (str): ip of the host where we will download the release info file

    Returns:
        success (bool): True if successful, False otherwise
        msg (str): success/error message

    """
    try:
        args = "url=http://data.alienvault.com/RELEASES/release_info dest=/var/alienvault force=yes"
        response = ansible.run_module(host_list=[system_ip], module="get_url", use_sudo=True, args=args)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if success:
            msg = response['contacted'][system_ip]['msg']
    except Exception as err:
        api_log.error("[ansible_download_release_info] An error occurred while retrieving the release info <%s>" % str(err))
        return False, "[ansible_download_release_info] An error occurred while retrieving the release info <%s>" % str(err)
    return success, msg


def ansible_get_log_lines(system_ip, logfile, lines):
    """Get a certain number of log lines from a given log file
    
        Args:
            system_ip (str): String with system ip.
            log_file (str): String with the name of the log file.
            lines (integer): Integer with the number of lines to display.
    """
    
    command = "tail -%s %s | base64" % (str(lines), logfile)

    try:
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo="True", args=command)
    except Exception, exc:
        api_log.error("Ansible Error: An error occurred retrieving the log file: %s" % str(exc))
        return False, "Ansible Error: An error occurred retrieving the log file: %s" % str(exc)
        
    (success, msg) = ansible_is_valid_response(system_ip, response)
    if not success:
        return False,"Something wrong happen retrieving the log file: %s" % str(msg)
        
    return_code = int(response['contacted'][system_ip]['rc'])
    if return_code != 0:
        return False, "Something wrong happen retrieving the log file: %s" % str(response['contacted'][system_ip]['stderr'])

    output = unicode(b64decode(response['contacted'][system_ip]['stdout']), "utf-8", errors='replace')
    
    if output is not None:
        output = output.split("\n")
       
    return success, output
