# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2014 AlienVault
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
from db.methods.system import (get_systems_full, get_system_ip_from_system_id,
                               get_system_id_from_local,get_system_id_from_system_ip)
from db.methods.system import db_add_system, db_remove_system
from db.methods.system import get_system_ip_from_local, has_forward_role
from db.methods.system import set_system_vpn_ip
from db.methods.sensor import get_sensor_id_from_sensor_ip
from db.methods.server import db_get_server, db_add_child_server, get_server_id_from_local

from ansiblemethods.system.system import (get_system_setup_data,
                                          ansible_add_system)
from ansiblemethods.system.system import (ansible_remove_certificates,
                                          ansible_get_system_info,
                                          restart_mysql, generate_sync_sql)
from ansiblemethods.system.system import ansible_run_async_reconfig
from ansiblemethods.system.system import ansible_check_asynchronous_command_return_code
from ansiblemethods.system.system import ansible_run_async_update
from ansiblemethods.system.system import ansible_check_if_process_is_running
from ansiblemethods.system.system import ansible_get_asynchronous_command_log_file
from ansiblemethods.system.system import delete_parent_server as ansible_delete_parent_server
from ansiblemethods.system.system import ansible_get_update_info
from ansiblemethods.system.system import ansible_get_log_lines
from ansiblemethods.server.server import ans_add_server, ans_add_server_hierarchy
from ansiblemethods.ansibleinventory import AnsibleInventoryManager
from ansiblemethods.helper import gunzip_file
from celerymethods.utils import exist_task_running, get_task_status
from apimethods.utils import create_local_directory, get_base_path_from_system_id, get_hex_string_from_uuid
from apimethods.system.cache import use_cache
from ansiblemethods.system.util import fetch_if_changed
from ansiblemethods.system.system import ping_system, ansible_get_process_pid
from apimethods.system.cache import flush_cache
from apimethods.utils import is_valid_ipv4
from subprocess import call, Popen, PIPE
from ansiblemethods.system.network import make_tunnel as ansible_make_tunnel_with_vpn
import re
def get_all():
    """
    Get all the information available about registered systems.
    """
    (success, system_data) = ret = get_systems_full()
    if not success:
        return ret

    return (success, dict([(x[0], {'admin_ip': x[1]['admin_ip'],
                                   'hostname': x[1]['hostname'],
                                   'profile': x[1]['profile']}) for x in system_data]))


def get_all_systems_with_ping_info():
    """
    get all the registered systems and ping information
    """

    success, system_list = ret = get_systems_full()
    if not success:
        return ret

    systems = dict(system_list)
    for system_id, system_data in systems.iteritems():
        system_ip = system_data['admin_ip']
        if system_data['vpn_ip'] != '':
            system_ip = system_data['vpn_ip']

        success, msg = ping_system(system_ip)
        systems[system_id]['reachable'] = success

    return True, systems


@use_cache(namespace="system")
def get(system_id, no_cache=False):
    """
    Get information about a single system
    """
    (success, ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret

    return get_system_setup_data(ip)


def create_directory_for_ossec_remote(system_id):

    path = get_base_path_from_system_id(system_id) + "/ossec/"
    success, msg = create_local_directory(path)
    if not success:
        return False, msg

    return True, ""


def add_child_server(system_ip, server_id):

    db_add_child_server(server_id)
    success, local_server = db_get_server('local')
    if not success:
        return False, local_server

    success, msg = ans_add_server(system_ip=system_ip,
                                  server_id=local_server['id'],
                                  server_name=local_server['name'],
                                  server_ip=local_server['ip'],
                                  server_port=local_server['port'],
                                  server_descr=local_server['descr'])
    if not success:
        return False, msg

    success, msg = ans_add_server_hierarchy(system_ip=system_ip,
                                            parent_id=local_server['id'],
                                            child_id=server_id)
    if not success:
        return False, msg

    return True, ''


def add_system_from_ip(system_ip, password, add_to_database=True):
    """
    Add a new system usign system ip.
    """
    (success, local_system_id) = get_system_id_from_local()
    if not success:
        return success, "Something wrong happened retrieving the local system id"

    (success, response) = ansible_add_system(local_system_id=local_system_id,
                                             remote_system_ip=system_ip,
                                             password=password)
    if not success:
        api_log.error(response)
        return success, response

    (success, system_info) = ansible_get_system_info(system_ip)
    if not success:
        api_log.error(system_info)
        return success, "Something wrong happened getting the system info"

    sensor_id = None
    if 'server' in system_info['profile']:
        # - Do not add the child server when I'm myself
        if system_info['server_id'] != local_system_id:
            success, msg = add_child_server(system_ip, system_info['server_id'])
            if not success:
                api_log.error(str(msg))
                return False, "Something wrong happened setting the child server"

    if 'sensor' in system_info['profile']:
        if 'server' in system_info['profile'] and system_info['sensor_id']:
            # sensor and sensor profiles come with its own sensor_id
            sensor_id = system_info['sensor_id']
        else:
            # get sensor_id from ip
            sensor_ip = system_ip
            if system_info['vpn_ip']:
                sensor_ip = system_info['vpn_ip']
            (success, sensor_id) = get_sensor_id_from_sensor_ip(sensor_ip)
            if not success:
                api_log.error(str(sensor_id))
                sensor_id = None

    system_info['sensor_id'] = sensor_id

    if not system_info['admin_ip']:
        system_info['admin_ip'] = system_ip
    if add_to_database:
        profile_str = ','.join(system_info['profile'])
        (success, msg) = db_add_system(system_id=system_info['system_id'],
                                       name=system_info['hostname'],
                                       admin_ip=system_info['admin_ip'],
                                       vpn_ip=system_info['vpn_ip'],
                                       profile=profile_str,
                                       server_id=system_info['server_id'],
                                       sensor_id=system_info['sensor_id'])
        if not success:
            api_log.error(msg)
            return (False, "Something wrong happened inserting the system into the database")

    (success, msg) = create_directory_for_ossec_remote(system_info['system_id'])
    if not success:
        api_log.error(msg)
        return (False, msg)

    return (True, system_info)


def add_system(system_id, password):
    """
    Add a system usign a system id. Already in database
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error(str(system_ip))
        return False, "Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))

    success, msg = add_system_from_ip(system_ip, password, add_to_database=False)
    if not success:
        api_log.error(str(msg))
        return False, msg

    return True, msg


def apimethod_delete_system(system_id):
    success, local_system_id = get_system_id_from_local()
    if not success:
        return success, "Error: Can not retrieve the local system id. %s" %str(local_system_id)
    if system_id == 'local' or get_hex_string_from_uuid(local_system_id) == get_hex_string_from_uuid(system_id):
        return False, "Error: You're trying to remove the local system, which it's not allowed"

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return success, "Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))
    # 1 - Remove it from the database
    success, msg = db_remove_system(system_id)
    if not success:
        return success, "Error while removing the system from the database: %s" % str(msg)
    # 2 - Remove the remote certificates
    # success, msg = ansible_remove_certificates(system_ip)
    # if not success:
    #     return success, "Error while removing the remote certificates: %s" % str(msg)
    # 3 - Remove the local certificates and keys
    success, local_ip = get_system_ip_from_local()
    if not success:
        return success, "Error while getting the local ip: %s" % str(local_ip)

    success, msg = ansible_remove_certificates(system_ip=local_ip, system_id_to_remove=system_id)
    if not success:
        return success, "Error while removing the local certificates: %s" % str(msg)

    # 4 - Remove it from the ansible inventory.
    try:
        aim = AnsibleInventoryManager()
        aim.delete_host(system_ip)
        aim.save_inventory()
        del aim
    except Exception as aim_error:
        return False, "An error occurred while removing the system from the ansible inventory file: %s" % str(aim_error)

    # 5 - Try to connect to the child and remove the parent using it's server_id
    success, own_server_id = get_server_id_from_local()
    if not success:
        return success, "Error while retrieving server_id from local: %s" % str(msg)

    success, msg = ansible_delete_parent_server(system_ip, own_server_id)
    if not success:
        return success, "Error while deleting parent server in child: %s" % str(msg)

    return True, ""


def sync_database_from_child(system_id):
    """
    Check SQL sync file in system_id and if it differs from the local one, get it and add to local database
    Then, check if we have to propagate changes upwards and generate sync.sql if so
    """
    # Get remote and local IPs
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return success, "[Apimethod sync_database_from_child] Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))

    success, local_ip = get_system_ip_from_local()
    if not success:
        return success, "[Apimethod sync_database_from_child] Error while getting the local ip: %s" % str(local_ip)

    # Get remote sync file if changed
    remote_file_path = "/var/lib/alienvault-center/db/sync.sql.gz"
    local_gzfile_path = "%s/sync_%s.sql.gz" % (get_base_path_from_system_id(system_id), system_id)
    local_file_path = "%s/sync_%s.sql" % (get_base_path_from_system_id(system_id), system_id)
    (retrieved, msg) = fetch_if_changed(system_ip, remote_file_path, local_ip, local_gzfile_path)
    if not retrieved:
        if 'already in sync' in msg:
            return True, "[Apimethod sync_database_from_child] Databases already in sync"
        else:
            return False, "[Apimethod sync_database_from_child] %s" % msg

    # SQL file changed. Get it, check md5 and apply
    # Get MD5SUM file for the SQL file
    remote_md5file_path = "/var/lib/alienvault-center/db/sync.md5"
    local_md5file_path = "%s/sync_%s.md5" % (get_base_path_from_system_id(system_id), system_id)
    (retrieved, msg) = fetch_if_changed(system_ip, remote_md5file_path, local_ip, local_md5file_path)
    if not retrieved and 'already in sync' not in msg:
        return False, "[Apimethod sync_database_from_child] %s" % msg

    # Gunzip SQL file before processing it
    success, msg = gunzip_file(local_ip, local_gzfile_path, local_file_path)
    if not success:
        return False, "[Apimethod sync_database_from_child] %s" % msg

    # Check SQL file MD5
    with open(local_md5file_path) as m:
        md5_read = m.readline()
    p = Popen(['/usr/bin/md5sum', local_file_path], stdout=PIPE)
    md5_calc, err = p.communicate()
    if err:
        return False, "[Apimethod sync_database_from_child] %s" % err
    if not str(md5_read.rstrip('\n')) in str(md5_calc):
        return False, "[Apimethod sync_database_from_child] Corrupt or incomplete SQL file (bad md5sum)"

    # SQL file OK. Apply
    with open(local_file_path) as f:
        if call(['/usr/bin/ossim-db'], stdin=f):
            return False, "[Apimethod sync_database_from_child] Error applying SQL file to ossim-db"
        else:
            api_log.info("[Apimethod sync_database_from_child] SQL applied successfully")
            # Check first line of sync.sql file for mySQL restart option
            f.seek(0, 0)
            restart_db = "RESTART OSSIM-SERVER" in f.readline()

    # Restart SQL server if needed
    if restart_db:
        try:
            restart_mysql(local_ip)
        except Exception, err:
            return False, "An error occurred while restarting MySQL server: %s" % str(err)

    # Check server_forward_role and generate sync.sql
    (success, local_id) = get_system_id_from_local()
    if success and has_forward_role(local_id):
        try:
            generate_sync_sql(local_ip, restart_db)
        except Exception, err:
            return False, "An error occurred while generating sync.sql file: %s" % str(err)

    return True, "[Apimethod sync_database_from_child] SQL sync successful"


@use_cache(namespace="system_packages", expire=84600)
def apimethod_get_update_info(system_id, no_cache=False):
    """Retrieves the system update information
    Args:
      system_id(str): The system id of which we want to know if it has available updates
    Returns:
      (success,data): success=True when the operation when ok, otherwise success=False. On success data will contain a json object with the updates information.
    """
    try:
        (success, system_ip) = get_system_ip_from_system_id(system_id)
        if not success:
            return success, "[apimethod_get_packages_info] Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))
        success, data = ansible_get_update_info(system_ip)
    except Exception as err:
        return False, "[apimethod_get_packages_info] An error occurred while retrieving the update info <%s>" % str(err)
    return success, data


def apimethod_get_pending_packges(system_id, no_cache=False):
    """Retrieves the available updates for the given system_id
    Args:
      system_id(str): The system id of which we want to know if it has available updates
    Returns:
      (success,data): success=True when the operation when ok, otherwise success=False. On success data will contain a json object with the updates information.
    """
    success, data = apimethod_get_update_info(system_id, no_cache=no_cache)
    if not success:
        return success, data
    available_updates = data['available_updates']
    return success, available_updates


def apimethod_get_remote_software_update(system_id, no_cache=False):
    """Retrieves the available updates for the given system_id
    Args:
      system_id(str): The system id of which we want to know if it has available updates
    Returns:
      (success,data): success=True when the operation when ok, otherwise success=False. On success data will contain a json object with the updates information.
    """
    success, data = apimethod_get_update_info(system_id, no_cache=no_cache)
    if not success:
        return success, data
    info = {'current_version':data['current_version'],
            'last_update':data['last_update'],
            'packages':{
                        'total':data['total_packages'],
                        'pending_updates':data['pending_updates'],
                        'pending_feed_updates':data['pending_feed_updates']
                        }}
    return success, info


def asynchronous_reconfigure(system_id):
    """Launches an asynchronous reconfigure on the given system_ip

    Args:
      system_id (str): The system_id of the system to configure.

    Returns:
      (boolean, str): A tuple containing the result of the execution

    Examples:
      >>> asynchronous_reconfigure("88888888-8888-8888-888888888888")
      (True,"/tmp/system_reconfigure.log"
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return success, "[asynchronous_reconfigure] Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))

    return ansible_run_async_reconfig(system_ip)


def asynchronous_update(system_id, only_feed=False,update_key=""):
    """Launches an asynchronous update on the given system_ip

    Args:
      system_id (str): The system_id of the system to update.
      only_feed (boolean): A boolean to indicate that we need update only the feed.
    Returns:
      (boolean, str): A tuple containing the result of the execution

    Examples:
      >>> asynchronous_update("11111111-1111-1111-111111111111")
      (True,"/tmp/system_update.log")
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return success, "[asynchronous_update] Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))
    return ansible_run_async_update(system_ip,only_feed=only_feed,update_key=update_key)


def check_if_process_is_running(system_id, ps_filter):
    """Check if there is any process running that meet the ps_filter.

    Args:
      system_ip (str): The system_id where you want to know if the process is running
      ps_filter(str): Filter to be passed to grep the results.

    Returns:
      (boolean, str): A tuple containing the result of the execution

    Examples:
      >>> check_if_process_is_running("192.168.63.199", "/tmp/system_reconfigure.log")
      (True,0)
    """

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return success, "[check_if_process_is_running] Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))

    return ansible_check_if_process_is_running(system_ip, ps_filter)


def apimethod_check_asynchronous_command_return_code(system_id, rc_file):
    """Check the return code of a previously asynchronous request

    Args:
      system_ip (str): The system_id where you want to know if the process is running
      rc_file(str): The return code file

    Returns:
      (boolean, str): A tuple containing the result of the execution

    Examples:
      >>> apimethod_ansible_check_asynchronous_command_return_code("11111111-1111-1111-1111-1111222244445555", "/tmp/system_reconfigure.log.rc")
      (True,0)
    """

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return success, "[apimethod_ansible_check_asynchronous_command_return_code] Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))

    return ansible_check_asynchronous_command_return_code(system_ip, rc_file)


def apimethod_get_asynchronous_command_log_file(system_id, log_file):
    """Get the asynchronous command log file

    Args:
        system_id(str): The system_id where you want to know get the log file
        log_file(str): The log file you want to retrieve.

    Returns:
        (boolean,str): A tuple containing the result of the execution. On success the str will contain the local copy.

    Examples:
      >>> apimethod_get_asynchronous_command_log_file("11111111-1111-1111-1111-1111222244445555", "/tmp/system_reconfigure.log")
    """

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return success, "[apimethod_get_asynchronous_command_log_file] Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))

    return ansible_get_asynchronous_command_log_file(system_ip, log_file)


def apimethod_check_task_status(system_id, tasks):
    """
    Check the status of a given list of tasks. IE: alienvault-update, alienvault-reconfig

    Args:
        system_id (str) : The system_id where you want to check if it's running
        tasks (dict)    : The list of tasks to test.

    Returns:
        success (bool)     : True if successful, False elsewhere
        task_status (dict) : A dictionary containing job_id, job_status for each task

    """
    task_status = {}

    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error("[apimethod_check_task_status] Unable to get system ip for system id %s: %s" % (system_id, system_ip))
        return  False, {}


    success, task_status = get_task_status(system_id, system_ip, tasks)

    if not success:
        api_log.error("[apimethod_check_task_status] Unable to get the task status for system %s: %s" % (system_id, str(task_status)))
        return  False, {}


    return success, task_status


def get_last_log_lines(system_id, log_file, lines):
    """Get a certain number of log lines from a given log file

        Args:
            system_id (str): String with system id (uuid) or local.
            log_file (str): String with the name of the log file.
            lines (integer): Integer with the number of lines to display.
    """

    # Get system_ip from system id
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error(str(system_ip))
        return False, "Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))

    # White list check
    allowed_files = {
        'kern'         : '/var/log/kern.log',
        'auth'         : '/var/log/auth.log',
        'daemon'       : '/var/log/daemon.log',
        'messages'     : '/var/log/messages',
        'syslog'       : '/var/log/syslog',
        'agent_stats'  : '/var/log/ossim/agent_stats.log',
        'agent'        : '/var/log/alienvault/agent/agent.log',
        'server'       : '/var/log/alienvault/server/server.log',
        'reputation'   : '/var/log/ossim/reputation.log',
        'apache_access': '/var/log/apache2/access.log',
        'apache_error' : '/var/log/apache2/error.log',
        'frameworkd'   : '/var/log/ossim/frameworkd.log'
    }

    if not allowed_files.has_key(log_file):
        return False, "%s is not a valid key for a log file" % log_file

    if lines not in [50, 100, 1000, 5000]:
        return False, "%s is not a valid number of lines. The number of lines be in [50, 100, 1000, 5000]" % str(lines)

    (success, msg) = ansible_get_log_lines(system_ip, logfile=allowed_files[log_file], lines=lines)

    if not success:
        api_log.error(str(msg))
        return False, msg

    return True, msg


def make_tunnel_with_vpn(system_ip,password):
    """Build the VPN tunnel with the given node"""
    if not is_valid_ipv4(system_ip):
        return False, "Invalid system ip: %s" % str(system_ip)
    success, own_server_id = get_server_id_from_local()
    if not success:
        return success, "Error while retrieving server_id from local: %s" % str(own_server_id)

    success, data = ansible_make_tunnel_with_vpn(system_ip=system_ip, local_server_id= get_hex_string_from_uuid(own_server_id), password=password)
    if not success:
        return success, data
    
    print "Set VPN IP on the system table"
    new_node_vpn_ip = data['client_end_point1']
    if new_node_vpn_ip is None:
        return False, "Cannot retrieve the new node VPN IP"
    print "New Node VPN IP %s" % new_node_vpn_ip
    success, data =  get_system_id_from_system_ip(system_ip)
    if success:# If the system is not on the system table is doesn't matter
        success, data = set_system_vpn_ip(data, new_node_vpn_ip)
        if not success:
            return False, "Cannot set the new node vpn ip on the system table"
    flush_cache(namespace="system")
    return True, "VPN node successfully connected."
