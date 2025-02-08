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
import uuid
from base64 import b64decode
from os.path import basename
from db.methods.system import get_system_ip_from_system_id, ossim_setup
from apiexceptions.ansible import APIAnsibleError, APIAnsibleBadResponse
from ansiblemethods.ansiblemanager import Ansible, PLAYBOOKS
from ansiblemethods.helper import (
    parse_av_config_response,
    read_file,
    ansible_is_valid_response,
    ansible_is_valid_playbook_response,
    copy_file,
    remove_file,
    package_list_generic
)
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound

ansible = Ansible()


def get_system_id(system_ip):
    """ Returns the system Id from a given ip
    @param system_ip: the host system ip
    """
    host_list = [system_ip, ]
    uuid_regex = re.compile('^[a-fA-F0-9]{8}\-[a-fA-F0-9]{4}\-[a-fA-F0-9]{4}\-[a-fA-F0-9]{4}\-[a-fA-F0-9]{12}$')

    # 1- Try alienvault-system-id
    response = ansible.run_module(
        host_list=[system_ip],
        module="command",
        args="/usr/bin/alienvault-system-id"
    )
    success, msg = ansible_is_valid_response(system_ip, response)
    if success:
        system_id = response['contacted'][system_ip]['stdout']

    # 2- When error, try the old way
    else:
        # 2.1- Read center file
        center_file = "/etc/alienvault-center/alienvault-center-uuid"
        (success, system_id) = read_file(system_ip,
                                         center_file)
        if not success:
            # 2.2- Call ansible method
            response = ansible.run_module(
                host_list=host_list,
                module="av_setup",
                args="filter=ansible_product_uuid"
            )
            if system_ip in response['dark']:
                error_msg = "[get_system_id]: "
                error_msg = error_msg + response['dark'][system_ip]['msg']
                return False, error_msg
            else:
                if system_ip in response['contacted']:
                    system_id = response['contacted'][system_ip]['ansible_facts']['ansible_product_uuid'].lower()
                else:
                    return False, "[get_system_id]: Error getting system ID"

    # Check the system_id is valid
    if not system_id or not uuid_regex.match(system_id):
        return False, "[get_system_id]: Error getting system ID"

    return True, system_id


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
        response = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args="/usr/bin/uptime"
        )
        if system_ip in response['dark']:
            error_msg = "get_system_load "
            error_msg = error_msg + response['dark'][system_ip]['msg']
            return False, error_msg
        else:
            uptimeout = response['contacted'][system_ip]['stdout']
            # Capture
            m = reuptime.match(uptimeout)
            if m is not None:
                loadcpu = float(m.group(3))
                return True, loadcpu
            else:
                error_msg = " ".join(["get_system_load", "Can't match the uptime output '%s'" % uptimeout])
                return False, error_msg

    except ValueError:
        return False, "get_system_load " + "Can't get load output from"

    except Exception as e:
        error_msg = "Ansible error: " + str(e) + "\n"
        error_msg = error_msg + traceback.format_exc()
        return False, error_msg


def get_profile(system_ip="127.0.0.1"):
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
dpkg -l alienvault-dummy-sensor-ids| grep '^ii' > /dev/null 2>&1
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
        response = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args=command
        )
        response = response['contacted'][system_ip]['stdout'].replace(' ', '')
        profile_list = response.split(',')
    except Exception, e:
        error_msg = "get_plugin_list_by_location: Error Retrieving the plugin list by location. %s" % e
        api_log.error(error_msg)
        return False, ''
    return True, profile_list


def get_system_setup_data(system_ip):
    """Returns the data from setup module from a given ip"""
    response = ansible.run_module(
        host_list=[system_ip],
        module="av_setup",
        args=""
    )
    if system_ip in response['dark']:
        error_msg = "Error getting system data : %s" % response['dark'][system_ip]
        return False, error_msg
    return True, response['contacted'][system_ip]['ansible_facts']


def get_root_disk_usage(system_ip):
    # Filesystem    Type    Size  Used Avail Use% Mounted on
    # /dev/sda1     ext3     31G  9.3G   20G  33% /
    rt = True
    percentage = 0.0
    try:
        args = "executable=/bin/bash df / | tail -1 |df / | tail -1 | awk '{print $5}'|sed 's/%//'"
        data = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args=args
        )
        output = data['contacted'][system_ip]['stdout']
        percentage = float(output)
    except Exception:
        rt = False
    return rt, percentage


def get_doctor_data(host_list=[], args={}):
    """Run AlienVault Doctor in the target machine(s) and return the results.
    """
    return ansible.run_module(
        host_list=host_list,
        module='av_doctor',
        args=args
    )


def install_debian_package(host_list=None, debian_package=None):
    """Install a Debian package in one or more remote systems.
    """
    if host_list is None:
        host_list = []
    dpkg_command = '/usr/bin/dpkg -i --force-confnew %s' % debian_package
    response = ansible.run_module(
        host_list=host_list,
        module='command',
        args=dpkg_command
    )
    for host in host_list:
        if host in response['dark']:
            error_msg = "install_debian_package : " + response['dark'][host]['msg']
            return False, error_msg
    return True, ''


def reconfigure(system_ip):
    """
    Runs an alienvault-reconfigure
    :param system_ip: The system IP where you want to run
                      the alienvault-reconfig
    :return A tuple (success, error_message).
    """
    rt = True
    error_str = ""
    try:
        command = """executable=/bin/bash alienvault-reconfig -c --center"""
        response = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args=command
        )
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
        error_msg = "Ansible Error: An error occurred while running " + \
                    "alienvault-reconfig: %s \n trace: " % str(e) + \
                    "%s" % trace
        api_log.error(error_msg)
        rt = False
    return rt, error_str


def get_av_config(system_ip, path_dict):
    """
    @param system_ip: The system IP
    @param path_dict: the av_config file path dictionary (i.e '[sensor]detectors')
    @return A tuple (success|error, data|msgerror)
    """
    path_str = ' '.join(['%s=True' % (key) for (key, _value) in path_dict.items()])

    response = ansible.run_module(
        host_list=[system_ip],
        module="av_config",
        args="op=get %s" % path_str
    )
    return parse_av_config_response(response, system_ip)


def set_av_config(system_ip, path_dict):
    """
    @param system_ip: The system IP
    @param path_dict: the av_config file path dictionary (i.e '[sensor]detectors')
    @return A tuple (success|error, data|msgerror)
    """
    path_str = ' '.join(['%s="%s"' % (key, value.replace("\"","\\\"")) for (key, value) in path_dict.items()])
    #
    #
    # flush_cache(namespace="system")

    response = ansible.run_module(
        host_list=[system_ip],
        module="av_config",
        args="op=set %s" % path_str
    )
    return parse_av_config_response(response, system_ip)


def ansible_add_ip_to_inventory(system_ip):
    try:
        from ansiblemethods.ansibleinventory import AnsibleInventoryManager
        aim = AnsibleInventoryManager()
        aim.add_host(system_ip)
        aim.save_inventory()
    except Exception, msg:
        api_log.error(str(msg))
        return False, 'Error adding ip to ansible inventory'
    return True, ''


def ansible_add_system(local_system_id, remote_system_ip, password):
    """Add a new system.
    Create and set the crypto files and update the ansible inventory manager
    """
    from ansiblemethods.ansibleinventory import AnsibleInventoryManager
    result = False
    response = None

    # sanity check
    if not os.path.isfile('/var/ossim/ssl/local/ssh_capubkey.pem'):
        response = "Cannot access public key file"
        return result, response

    success, message = ansible_remove_key_from_known_host_file("127.0.0.1", remote_system_ip)

    if not success:
        return success, message
    evars = {"remote_system_ip": "%s" % remote_system_ip, "local_system_id": "%s" % local_system_id}

    response = ansible.run_playbook(
        playbook=PLAYBOOKS['SET_CRYPTO_FILES'],
        host_list=[remote_system_ip],
        extra_vars=evars,
        ans_remote_user="root",
        ans_remote_pass=password,
        use_sudo=True
    )

    if response[remote_system_ip]['unreachable'] == 0 and response[remote_system_ip]['failures'] == 0:
        result = True
        response = "System with IP %s added correctly" % remote_system_ip
    else:
        result = False
        api_log.error(str(response))
        response = "Cannot add system with IP %s. " % remote_system_ip + \
                   "Please verify that the system is reachable " + \
                   "and the password is correct."

    # Add the system to the Ansible Inventory
    aim = AnsibleInventoryManager()
    aim.add_host(remote_system_ip)
    aim.save_inventory()

    # Squid access configuration
    # ----------------------------------------------------------------------
    (result, local_system_ip) = get_system_ip_from_system_id(local_system_id)
    if result is False:
        api_log.error(
            "Cannot retrieve the system ip from the given sensor id <%s>" % local_system_id
        )

    acl = "acl {0} src {0}".format(remote_system_ip)
    http_access = "http_access allow {}".format(remote_system_ip)
    conf = "/etc/squid/squid.conf"

    # Whole command:
    # if [ `grep -q "192.168.1.1" /etc/squid3/squid.conf;echo $?` -gt 0 ]
    # then /bin/sed -i -e "/acl.*CONNECT/a acl 192.168.1.1 src 192.168.1.1"
    # -e "/^http_access.*nent$/a http_access allow 192.168.1.1" /etc/squid3/squid.conf; fi
    if_exists = """if [ `grep -q "{0}" {1};echo $?` -gt 0 ]; then""".format(remote_system_ip, conf)
    command = """{0} /bin/sed -i -e "/acl.*CONNECT/a {1}" -e "/^http_access.*nent$/a {2}" {3}; fi""".format(
        if_exists, acl, http_access, conf
    )
    api_log.info("Adding squid acl for {}".format(remote_system_ip))
    try:
        response = ansible.run_module(
            host_list=[local_system_ip],
            module="shell",
            args=command
        )
    except Exception, err:
        trace = traceback.format_exc()
        error_msg = "Ansible Error: An error occurred while configuring squid: {0}, trace: {1}".format(
            err, trace
        )
        api_log.error(error_msg)
    api_log.info("The following ACLs have been added : {}, {}".format(acl, http_access))

    api_log.info("Executing reset_squid_firewall after adding a system into deployment...")
    command = """/usr/share/alienvault-center/lib/reset_squid_firewall.pl &>/dev/null &"""
    try:
        response = ansible.run_module(
            host_list=[local_system_ip],
            module="shell",
            args=command,
            timeout=60
        )
    except Exception, err:
        trace = traceback.format_exc()
        error_msg = "Ansible Error: An error occurred while running external command: {0}, trace: {1}".format(
            err, trace
        )
        api_log.error(error_msg)
    api_log.info("Successfully executed reset_squid_firewall on system <%s>" % local_system_ip)

    return result, response


def ansible_clean_squid_config(remote_system_ip):
    """If a server is removed from USM deployment and the corresponding ACL is not removed from squid.conf,
    then any arbitrary machine in the environment (adjacent broadcast domain)
    can use this IP to bypass firewall restrictions and access the Internet via USM proxy;
    this in essence constitutes a security policy violation.
    """

    response = None

    try:
        local_system_ip = ossim_setup.get_general_admin_ip(refresh=True)
    except NoResultFound, msg:
        return False, "No admin_ip found for local system: %s" % str(msg)
    except MultipleResultsFound, msg:
        return False, "More than one admin_ip found for local system: %s" % str(msg)
    except Exception, msg:
        return False, "Error captured while querying for local system admin_ip: %s" % str(msg)

    conf = "/etc/squid/squid.conf"
    correct_regexp_ip = remote_system_ip.replace(".", "\.")
    # Whole command:
    # if [ `grep -q "allow 192.168.1.1" /etc/squid3/squid.conf;echo $?` -eq 0 ]
    # then sed -e "/http_acc.*192\.168\.1\.1/d" -e "/acl.*192\.168\.1\.1$/d" /etc/squid3/squid.conf; fi
    if_exists = """if [ `grep -q "allow {0}" {1};echo $?` -eq 0 ]; then""".format(remote_system_ip, conf)
    command = """{0} /bin/sed -i -e "/http_acc.*{1}/d" -e "/acl.*{1}$/d" {2}; fi""".format(
        if_exists, correct_regexp_ip, conf
    )
    api_log.info("Trying to remove acl for {}".format(remote_system_ip))
    try:
        response = ansible.run_module(
            host_list=['127.0.0.1'],
            module="shell",
            args=command
        )
    except Exception, err:
        trace = traceback.format_exc()
        error_msg = "Ansible Error: An error occurred while configuring squid: {0}, trace: {1}".format(
            err, trace
        )
        api_log.error(error_msg)
        return False, response

    api_log.info("Executing reset_squid_firewall after removing a system from deployment...")
    command = """/usr/share/alienvault-center/lib/reset_squid_firewall.pl &>/dev/null"""
    try:
        response = ansible.run_module(
            host_list=[local_system_ip],
            module="shell",
            args=command,
            timeout=60
        )
    except Exception, err:
        trace = traceback.format_exc()
        error_msg = "Ansible Error: An error occurred while running external command: {0}, trace: {1}".format(
            err, trace
        )
        api_log.error(error_msg)
    api_log.info("Successfully executed reset_squid_firewall on system <%s>" % local_system_ip)

    return True, response


def ansible_ping_system(system_ip):
    try:
        # hardcoded timeout to fix ENG-102699
        response = ansible.run_module(
            host_list=[system_ip],
            timeout=5,
            module="ping",
            args=""
        )
    except Exception as err:
        error_msg = "Something wrong happened while pinging the system: %s %s" % (system_ip, str(err))
        return False, error_msg

    # TODO: simplify these conditions when unit-tests will be fixed and updated.
    if 'dark' in response and system_ip in response['dark'] or 'unreachable' in response:
        return False, "System unreachable"

    if 'contacted' in response and system_ip in response['contacted'] and \
                    'pong' in response['contacted'][system_ip].get('ping', {}):
        return True, "OK"
    return False, ""


def ansible_remove_certificates(system_ip, system_id_to_remove):
    """Removes all the ssh certificates data:
    :param system_ip: The system ip where you want to remove the keys
    :param system_id_to_remove: The system_id of the system you want
                                to remove."""
    try:
        command = "rm -r /var/ossim/ssl/%s || true" % system_id_to_remove
        response = ansible.run_module(
            host_list=[system_ip],
            module="shell",
            args=command,
            use_sudo=True
        )
        success, msg = ansible_is_valid_response(system_ip, response)
        if not success:
            return False, "Something wrong happened while removing the ssl folder: {}".format(msg)
        return_code = int(response['contacted'][system_ip]['rc'])
        output_error = response['contacted'][system_ip]['stderr']
        if return_code != 0:
            return False, "Something wrong happened while removing the ssl folder: %s" % str(output_error)
    except Exception as err:
        return False, "Something wrong happened while removing the ssl folder: %s" % str(err)
    return True, ""


def ansible_get_hostname(system_ip):
    """ Returns the system hostname from a given ip
    @param system_ip: the host system ip
    """
    response = ansible.run_module([system_ip], "av_setup", "filter=ansible_hostname")
    success, msg = ansible_is_valid_response(system_ip, response)
    if not success:
        return False, "Something wrong happend getting the system hostname"

    hostname = response['contacted'][system_ip]['ansible_facts']['ansible_hostname']
    return True, hostname


def ansible_get_system_info(system_ip):
    """ Returns: Info from a given ip:
    - the system id
    - the system hostname
    - the system alienvault profile
    - the server_id
    @param system_ip: the host system ip
    """
    response = ansible.run_module([system_ip], "av_system_info", args="", use_sudo=True)
    success, msg = ansible_is_valid_response(system_ip, response)
    if not success:
        api_log.error(msg)
        return False, "Something wrong happend getting the system data"

    return True, response['contacted'][system_ip]['data']


def restart_mysql(system_ip):
    """
    Restart the MySQL server
    :param system_ip: System IP
    """
    try:
        response = ansible.run_module(
            host_list=[system_ip], module="service", args="name=mysql state=restarted", use_sudo=True
        )
    except Exception, e:
        return False, "Error restarting the MySQL database: %s" % str(e)
    return True, str(response['contacted'][system_ip]['state'])


def restart_ossim_server(system_ip):
    """
    Restart Ossim server
    :param system_ip: System IP
    """
    try:
        response = ansible.run_module(
            host_list=[system_ip],
            module="service",
            args="name=ossim-server state=restarted",
            use_sudo=True
        )
    except Exception, e:
        return False, "Error restarting ossim server: %s" % str(e)
    return True, str(response['contacted'][system_ip]['state'])


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
        error_msg = "Ansible Error: An error occurred while running generate_sync_sql: %s" % str(exc)
        api_log.error(error_msg)
        return False, error_msg

    (success, msg) = ansible_is_valid_response(system_ip, response)
    return success, msg


def ansible_run_async_reconfig(system_ip):
    """Runs an asynchronous reconfigure on the given system

    Args:
      system_ip(str): The system_ip of the system to configure.

    Returns:
      (boolean, str): A tuple containing the result of the execution. On success msg will be the remote log file.

    Examples:

      >>> ansible_run_async_update("192.168.5.123")
      (True,"/var/log/alienvault/update/update.log")

      >>> ansible_run_async_update("192.168.5.999")
      (False, "Something wrong happened while running ansible command {'192.168.1.198': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}")
    """

    log_file = "/var/log/alienvault/update/system_reconfigure_%10.2f.log" % time.time()
    evars = {"target": "%s" % system_ip, "log_file": "%s" % log_file}

    ansible_purge_logs(system_ip, 'reconfigure')
    response = ansible.run_playbook(
        playbook=PLAYBOOKS['ASYNC_RECONFIG'],
        host_list=[system_ip],
        extra_vars=evars,
        use_sudo=True
    )

    success, msg = ansible_is_valid_playbook_response(system_ip, response)
    if not success:
        return False, msg
    return success, log_file


def ansible_run_async_update(system_ip,
                             only_feed=False,
                             update_key=""):
    """Runs an asynchronous update on the given system

    Args:
      system_ip(str): The system_ip of the system to update.
      only_feed(boolean): Update only the feed
      update_key(str): Upgrade key

    Returns:
      (boolean, str): A tuple containing the result of the execution.
                      On success msg will be the remote log file.

    Examples:

      >>> ansible_run_async_update("192.168.5.123")
      (True,"/var/log/alienvault/update/update.log")

      >>> ansible_run_async_update("192.168.5.123",only_feed=True)
      (True,"/var/log/alienvault/update/update.log")

      >>> ansible_run_async_update("192.168.5.999")
      (False, "Something wrong happened while running ansible command {'192.168.1.198': {'unreachable': 1, 'skipped': 0, 'ok': 0, 'changed': 0, 'failures': 0}}")

    """

    log_file = "/var/log/alienvault/update/system_update_%10.2f.log" % time.time()
    if only_feed:
        log_file = "/var/log/alienvault/update/system_update_feed_%10.2f.log" % time.time()
    if update_key != "":
        log_file = "/var/log/alienvault/update/system_update_uc_%10.2f.log" % time.time()

    evars = {"target": "%s" % system_ip, "log_file": "%s" % log_file, "only_feed": only_feed, "update_key": update_key}

    ansible_purge_logs(system_ip, 'update')
    response = ansible.run_playbook(
        playbook=PLAYBOOKS['ASYNC_UPDATE'],
        host_list=[system_ip],
        extra_vars=evars,
        use_sudo=True
    )
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
      (boolean,int): A tuple containing whether the operation was well or not
                     and the number the process running that meet the filter
    """
    try:
        rc = 0
        cmd = 'ps aux | grep "%s" | grep -v grep | ' \
              'grep -v tail | wc -l' % re.escape(ps_filter)
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo="True", args=cmd)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if not success:
            return False, msg
        rc = int(response['contacted'][system_ip]['stdout'])
    except Exception as exc:
        api_log.error("ansible_check_if_process_is_running: <%s>" % str(exc))
        return False, 0

    return success, rc


def ansible_pgrep(system_ip, pgrep_filter="''"):
    """
        Launch a pgrep in system :system_ip: with filter
        :pgrep_filter: matched against all the command line (-f).
        Return a tuple list with (pid,command line for each filter)
    """
    result = []
    try:
        cmd = "/usr/bin/pgrep -a -f '%s'" % pgrep_filter
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo=True, args=cmd)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if not success:
            api_log.error("[ansible_pgrep] Error: %s" % str(msg))
            return False, str(msg)
        if response['contacted'][system_ip]['stdout'] != '':
            data = response['contacted'][system_ip]['stdout'].split("\n")
        else:
            data = []
        result = [tuple(x.split(" ", 1)) for x in data]
    except Exception as exc:
        api_log.error("[ansible_pgrep] Error: %s" % str(exc))
        return False, str(exc)
    return True, result


def ansible_pkill(system_ip, pkill_filter):
    """
        Kill all processes that matches :pgrep_filter: in
        :system_ip:
    """
    try:
        cmd = "/usr/bin/pkill -f '%s'" % pkill_filter
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo=True, args=cmd)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if not success:
            api_log.error("[ansible_pkill] Error: %s" % str(msg))
            return False, str(msg)
    except Exception as exc:
        api_log.error("[ansible_pkill] Error: %s" % str(exc))
        return False, str(exc)
    return True, ''


def ansible_get_process_pid(system_ip, ps_filter):
    """Check whether a process is running or not
    Args:
      system_ip(str): The system IP where we would like to run the ps filter
      ps_filter(str): Filter to grep the ps aux command

    Returns:
      (boolean,int): A tuple containing whether the operation was well or not
                     and the PID of the process running that meet the filter
                     (0 = not running)
    """
    try:
        cmd = ('ps aux | grep \"%s\" | grep -v grep | '
               'grep -v tail | tr -s \" \" | cut -d \" \" -f 2 | '
               'head -n 1' % str(re.escape(ps_filter)))
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
    reg = r"/var/log/alienvault/update/system_(update|update_feed|update_uc|reconfigure)_\d{10}\.\d{2}\.log.rc"
    if re.match(reg, rc_file) is None:
        return False, "Invalid return code file"
    try:
        destination_path = "/var/log/alienvault/ansible/logs/"
        args = "dest=%s src=%s flat=yes" % (destination_path, rc_file)
        response = ansible.run_module(host_list=[system_ip], module="fetch", args=args, use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)

        if not result or 'dest' not in response['contacted'][system_ip]:
            error_msg = "Something wrong happened while fetching the return code file: %s" % msg
            return False, error_msg

        # The content of the return code file should be a number.
        # The content of the return code file should be 0 for success.
        rc_file_path = response['contacted'][system_ip]['dest']
        if not os.path.exists(rc_file_path):
            return False, "The local return code file doesn't exist"
        rc_file_fd = open(rc_file_path, 'r')
        data = rc_file_fd.read()
        rc_file_fd.close()
        os.remove(rc_file_path)
        try:
            rc_code = int(data)
        except Exception:
            return False, "The return code file doesn't contain a return code"
        if rc_code != 0:
            return False, "Return code is different from 0 <%s>" % str(rc_code)

    except Exception as err:
        error_msg = "An error occurred while retrieving the return code file <%s>" % str(err)
        return False, error_msg
    return True, ""


def ansible_get_asynchronous_command_log_file(system_ip, log_file):
    """Retrieves the asynchronous command log file
    Args:
      system_ip(str): The system IP where we would like to run
      rc_file(str): The return code file

    Returns:
      (boolean,int): A tuple containing whether the operation was well
    """

    reg = r"/var/log/alienvault/update/system_(update|update_feed|update_uc|reconfigure)_\d{10}\.\d{2}\.log"
    if re.match(reg, log_file) is None:
        return False, "Invalid async command log file"
    try:
        destination_path = "/var/log/alienvault/ansible/logs/"
        args = "dest=%s src=%s flat=yes" % (destination_path, log_file)
        response = ansible.run_module(host_list=[system_ip], module="fetch", args=args, use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result or 'dest' not in response['contacted'][system_ip]:
            error_msg = "Something wrong happened while fetching the async command log file: %s" % msg
            return False, error_msg
        # The content of the return code file should be a number.
        # The content of the return code file should be 0 for success.
        rc_file_path = response['contacted'][system_ip]['dest']
        if not os.path.exists(rc_file_path):
            return False, "The local async command log file doesn't exist"
        os.chmod(rc_file_path, 0644)

    except Exception as err:
        error_msg = "An error occurred while retrieving the async command log file <%s>" % str(err)
        return False, error_msg

    return True, rc_file_path


def delete_parent_server(system_ip, server_id):
    """
    Delete server entry from remote system's databases
    :param system_ip: ip address of the remote system
    :param server_id: server id to remove
    """
    command = """echo "CALL server_delete_parent('%s');" | ossim-db
              """ % server_id

    try:
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo="True", args=command)
    except Exception, exc:
        error_msg = "Ansible Error: An error occurred while running generate_sync_sql: %s" % str(exc)
        api_log.error(error_msg)
        return False, error_msg

    (success, msg) = ansible_is_valid_response(system_ip, response)
    return success, msg


def ansible_get_update_info(system_ip):
    """Retrieves information about the system packages.

    Args:
      system_ip(str): IP of the system of which we want info

    Returns:
      success(Boolean), msg(str): A tuple containing the result of the query
                                  and the data
    """
    try:
        response = ansible.run_module(host_list=[system_ip], module="av_update_info", use_sudo=True, args={})
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if success:
            msg = response['contacted'][system_ip]['data']

    except Exception as err:
        error_msg = "[get_packages_info] An error occurred while retrieving the system's package info <%s>" % str(err)
        api_log.error(error_msg)
        return False, error_msg

    return success, msg


def ansible_download_release_info(system_ip):
    """Download release notes from alienvault.com

    Args:
        system_ip (str): ip of the host where we will download
                         the release info file

    Returns:
        success (bool): True if successful, False otherwise
        msg (str): success/error message

    """
    try:
        args = "url=http://data.alienvault.com/alienvault58/RELEASES/release_info dest=/var/alienvault force=yes"
        response = ansible.run_module(host_list=[system_ip], module="get_url", use_sudo=True, args=args)
        (success, msg) = ansible_is_valid_response(system_ip, response)
        if success:
            msg = response['contacted'][system_ip]['msg']
    except Exception as err:
        error_msg = "[ansible_download_release_info] An error occurred while retrieving the release info <%s>" % str(err)
        api_log.error(error_msg)
        return False, error_msg
    return success, msg


def ansible_get_log_lines(system_ip, logfile, lines):
    """Get a certain number of log lines from a given log file

        Args:
            system_ip (str): String with system ip.
            logfile (str): String with the name of the log file.
            lines (integer): Integer with the number of lines to display.
    """

    command = "tail -%s %s | base64" % (str(lines), logfile)

    try:
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo="True", args=command)
    except Exception, exc:
        error_msg = "Ansible Error: An error occurred retrieving the log file: %s" % str(exc)
        api_log.error(error_msg)
        return False, error_msg

    (success, msg) = ansible_is_valid_response(system_ip, response)
    if not success:
        error_msg = "Something wrong happened retrieving the log file: %s" % str(msg)
        return False, error_msg

    return_code = int(response['contacted'][system_ip]['rc'])
    if return_code != 0:
        error_msg = "Something wrong happened retrieving the log file: %s" % str(response['contacted'][system_ip]['stderr'])
        return False, error_msg

    output = unicode(b64decode(response['contacted'][system_ip]['stdout']), "utf-8", errors='replace')

    if output is not None:
        output = output.split("\n")

    return success, output


def ansible_remove_key_from_known_host_file(system_ip, system_ip_to_remove):
    """Remove the given system ip ssh key from the knownhost file

        Args:
            system_ip (str): String with system ip.
            system_ip_to_remove (str): The system ip key to remove
    """

    command = "ssh-keygen -R %s" % str(system_ip_to_remove)

    try:
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo=False, args=command)
    except Exception, exc:
        error_msg = "Ansible Error: An error occurred while removing the ssh key: %s" % str(exc)
        api_log.error(error_msg)
        return False, error_msg
    (success, msg) = ansible_is_valid_response(system_ip, response)
    if not success:
        error_msg = "An error occurred while removing the ssh key: %s" % str(msg)
        return False, error_msg
    return_code = int(response['contacted'][system_ip]['rc'])
    if return_code != 0:
        error_msg = "An error occurred while removing the ssh key: %s" % str(response['contacted'][system_ip]['stderr'])
        return False, error_msg
    return success, ""


def ansible_install_plugin(system_ip, plugin_path, sql_path):
    if not (system_ip or plugin_path or sql_path):
        return False, "[ansible_install_plugin]: Missing arguments"

    # Copy plugin file to plugins dir
    remote_plugin_path = "/etc/ossim/agent/plugins/" + basename(plugin_path)
    cmd_args = "src={} dest={} force=yes owner=root group=alienvault mode=644".format(
        plugin_path, remote_plugin_path
    )
    (success, msg) = copy_file([system_ip], cmd_args)
    if not success:
        error_msg = "[ansible_install_plugin] Failed to copy plugin file: %s" % msg
        return False, error_msg

    # Copy SQL file to tmp dir
    remote_sql_path = "/tmp/tmp_" + basename(sql_path)
    cmd_args = "src=%s dest=%s force=yes " % (sql_path, remote_sql_path) + "owner=root group=alienvault mode=644"
    (success, msg) = copy_file([system_ip], cmd_args)
    if not success:
        error_msg = "[ansible_install_plugin] Failed to copy sql file: %s" % msg
        return False, error_msg

    # Apply SQL file
    cmd_args = "/usr/bin/ossim-db < %s" % remote_sql_path
    response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo=True, args=cmd_args)
    (success, msg) = ansible_is_valid_response(system_ip, response)
    if not success:
        error_msg = "[ansible_install_plugin] Failed to apply sql file: %s" % msg
        return False, error_msg

    # Delete SQL file
    (success, msg) = remove_file([system_ip], remote_sql_path)
    if not success:
        error_msg = "[ansible_install_plugin] Failed to delete sql file: %s" % msg
        return False, error_msg

    return True, "[ansible_install_plugin] Plugin installed OK"


def ansible_purge_logs(system_ip, log_type):
    """
    Delete update/reconfigure log files older than a year

    Args:
        system_ip(str): System IP
        log_type (str): reconfigure or update

    Returns:
        success (bool): OK/ERROR
        msg (str): info message
    """

    if not (system_ip or log_type):
        return False, "[ansible_purge_logs]: Missing arguments"

    response = ansible.run_module(
        host_list=[system_ip], module="av_purge_logs", use_sudo=True, args="log_type=%s" % log_type
    )
    success, msg = ansible_is_valid_response(system_ip, response)
    if success:
        if response['contacted'][system_ip]['changed']:
            api_log.info(response['contacted'][system_ip]['msg'])
        return True, "[ansible_purge_logs] Purge logs OK"
    return False, "[ansible_purge_logs] Purge logs error: %s" % str(msg)


def ansible_restart_frameworkd(system_ip):
    """
    Restart frameworkd daemon
    :param system_ip: System IP

    :return:
    """
    rc = True
    try:
        args = "name=ossim-framework state=restarted"
        response = ansible.run_module(host_list=[system_ip], module="service", args=args)
    except Exception, e:
        response = "Error restarting frameworkd: %s" % str(e)
        rc = False
    return rc, response


def ansible_is_gvm_installed(system_ip, remote_user=None, remote_password=None):
    """
    Check if GVM is installed for a given IP address
    :param system_ip: System IP
    :param remote_user: User to establish SSH connection
    :param remote_password: User password

    :return:
    """

    rc = True
    try:
        success, response = package_list_generic(system_ip, "'~i alienvault-gvm'", remote_user, remote_password)
        if not success:
            response = "Error checking if GVM is installed: {}".format(response)
            rc = False

    except Exception, e:
        response = "Error checking if GVM is installed: {}".format(e)
        rc = False
    return rc, response


def ansible_restart_gvm(system_ip, remote_user=None, remote_password=None):
    """
    Restart GVM for a given IP address
    :param system_ip: System IP
    :param remote_user: User to establish SSH connection
    :param remote_password: User password

    :return:
    """
    rc = True
    try:
        if remote_user is not None and remote_password is not None:
            response = ansible.run_module(
                host_list=[system_ip],
                module="shell",
                args="gvm-stop && gvm-start",
                ans_remote_pass=remote_password,
                ans_remote_user=remote_user,
            )
        else:
            response = ansible.run_module(
                host_list=[system_ip],
                module="shell",
                args="gvm-stop && gvm-start"
            )

        success, msg = ansible_is_valid_response(system_ip, response)
        if not success:
            response = "Error restarting gvm: {}".format(msg)
            rc = False
    except Exception, e:
        response = "Error restarting gvm: {}".format(e)
        rc = False
    return rc, response

def ansible_get_decrypted_field_from_config(system_ip, conf_name):
    """
    Get the config_name from config of a given system.

    Args:
        system_ip (str): ip of the host where we will get the config.
        conf_name (str): name of field in config which value we want to retrieve.
    Returns:
        key (str): AES decrypted config value or empty string
    """
    query = """SELECT AES_DECRYPT(value, (SELECT value FROM config WHERE conf='encryption_key')) AS "token"
               FROM config
               WHERE conf = '%s';""" % conf_name

    command = """echo "%s" | ossim-db
              """ % query
    try:
        response = ansible.run_module(host_list=[system_ip], module="shell", use_sudo="True", args=command)
    except Exception, exc:
        raise APIAnsibleBadResponse(str(exc))

    success, msg = ansible_is_valid_response(system_ip, response)
    if success:
        return response['contacted'][system_ip]['stdout'].replace('token\n', '')
    else:
        raise APIAnsibleBadResponse(str(msg))


def ansible_get_otx_key(system_ip):
    """
    Get the OTX Key of a given system.

    Args:
        system_ip (str): ip of the host where we will get the OTX key

    Returns:
        key (str): OTX key or empty string
    """
    return ansible_get_decrypted_field_from_config(system_ip, 'open_threat_exchange_key')


def ansible_get_backup_config_pass(system_ip):
    """
    Get the backup configuration pass of a given system.

    Args:
        system_ip (str): ip of the host where we will get the backup pass

    Returns:
        key (str): pass or empty string
    """
    return ansible_get_decrypted_field_from_config(system_ip, 'backup_conf_pass')


def ansible_set_system_certificate(local_ip, cert, priv, ca):
    """ Set content of a given file name
    :returns True if the file is properly created, False elsewhere
    """
    # Copy content to file
    try:
        command_args = "content=\"{0}\" dest={1} owner=root group=alienvault mode=\"u+rw,g-wx,o-rwx\"".format(
            cert, '/etc/ssl/private/custom_ui_certificate.crt'
        )
        ansible.run_module(host_list=[local_ip], module='copy', args=command_args, use_sudo=True)
    except Exception, exc:
        return False, "Ansible Error: An error occurred while generating certificate file(s): %s" % str(exc)

    try:
        command_args = "content=\"{0}\" dest={1} owner=root group=alienvault mode=\"u+rw,g-wx,o-rwx\"".format(
            priv, '/etc/ssl/private/custom_ui_private.key'
        )
        ansible.run_module(host_list=[local_ip], module='copy', args=command_args, use_sudo=True)
    except Exception, exc:
        return False, "Ansible Error: An error occurred while generating private key file(s): %s" % str(exc)

    try:
        if ca:
            command_args = "content=\"{0}\" dest={1} owner=root group=alienvault mode=\"u+rw,g-wx,o-rwx\"".format(
                ca, '/etc/ssl/private/custom_ui_ca_certificate.crt'
            )
            ansible.run_module(host_list=[local_ip], module='copy', args=command_args, use_sudo=True)
    except Exception, exc:
        return False, "Ansible Error: An error occurred while generating CA certificate file(s): %s" % str(exc)

    return True, ""


def ansible_remove_system_certificate(local_ip):
    """ Set content of a given file name
    :returns True if the file is properly created, False elsewhere
    """
    cmd_removing_certificate_file = "rm -f /etc/ssl/private/custom_ui_certificate.crt"
    try:
        ansible.run_module(host_list=[local_ip], module='shell', args=cmd_removing_certificate_file, use_sudo=True)
    except Exception, exc:
        return False, "Ansible Error: An error occurred while removing certificate file(s): %s" % str(exc)
    cmd_removing_private_key_file = "rm -f /etc/ssl/private/custom_ui_private.key"
    try:
        ansible.run_module(host_list=[local_ip], module='shell', args=cmd_removing_private_key_file, use_sudo=True)
    except Exception, exc:
        return False, "Ansible Error: An error occurred while removing private key file(s): %s" % str(exc)
    cmd_removing_ca_certificate_file = "rm -f /etc/ssl/private/custom_ui_ca_certificate.crt"
    try:
        ansible.run_module(host_list=[local_ip], module='shell', args=cmd_removing_ca_certificate_file, use_sudo=True)
    except Exception, exc:
        return False, "Ansible Error: An error occurred while removing CA certificate file(s): %s" % str(exc)

    return True, ""


def ansible_get_child_alarms(system_ip, delay=1, delta=3):
    """Get the alarms from remote system
    """
    cmd = "echo \"select hex(event_id), timestamp, hex(backlog_id) FROM alarm WHERE status='closed' AND timestamp between DATE_SUB(utc_timestamp(), " \
          "interval %u hour) AND DATE_SUB(utc_timestamp(), interval %u hour) UNION select hex(event_id), timestamp, hex(backlog_id) " \
          "FROM alarm WHERE status='open' AND " \
          "timestamp between DATE_SUB(utc_timestamp(), interval %u hour) AND DATE_SUB(utc_timestamp(), interval %u hour) ORDER BY timestamp DESC;\" | ossim-db " % (
              delta + delay, delay, delta + delay, delay)

    api_log.debug("Query: %s" % cmd)
    response = ansible.run_module(host_list=[system_ip], module="shell", args=cmd)
    success, msg = ansible_is_valid_response(system_ip, response)
    if not success:
        return False, "[ansible_get_child_alarms] Can't retrieve remote alarms (%s) : %s" % (system_ip, msg)

    data = []
    try:
        output = str(response['contacted'][system_ip]['stdout'])
        split = output.splitlines()  # Discard first line
        if split:
            for line in split[1:]:  # Omit header
                (event_id, timestamp, backlog_id) = line.split('\t')
                data.append(event_id)
    except KeyError:
        api_log.error("[ansible_get_child_alarms] Bad response from child server: %s" % str(response))
        return False, "[ansible_get_child_alarms] Bad response from child server"
    return True, data


def ansible_resend_alarms(system_ip, alarms):
    """ Resend alarms to AV server.

    Args:
        system_ip: destination server IP
        alarms: alarms list, e.g. ['1b534bb0-dbc3-11e5-a68d-000c293716eb', '1b534bb0-dbc3-11e5-a68d-001c293716ea', ...]

    Returns:
        (boolean status, string msg)

    Raises:
        ValueError: badly formed hexadecimal UUID string, when wrong event ID was provided in the alarms list
    """
    if alarms:
        chunk_size = 10  # alarm_chunks are 10 alarms
        for alarm_chunk in [alarms[x:x + chunk_size] for x in xrange(0, len(alarms), chunk_size)]:
            # event_id = str(uuid.UUID(alarm))
            events = "\n".join(map(lambda x: str(uuid.UUID(x)), alarm_chunk))
            api_log.info("[ansible_resend_alarms] Resending event '%s' to server '%s'" % (str(events), system_ip))
            cmd = "echo -e \"%s\" | nc 127.0.0.1 40004 -w1" % events
            # api_log.debug("Remote command: %s " % cmd)
            response = ansible.run_module(host_list=[system_ip], module="shell", args=cmd)
            success, msg = ansible_is_valid_response(system_ip, response)
            if not success:
                err_msg = "Can't resend to '%s' event_ids: %s. Bailing out" % (system_ip, alarm_chunk)
                api_log.error("[ansible_resend_alarms] %s" % err_msg)
                return False, err_msg

    return True, ''
