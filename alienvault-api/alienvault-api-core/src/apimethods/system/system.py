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

# pylint: disable=F0401
# Generics
import api_log
import os
from subprocess import call, Popen, PIPE
from ast import literal_eval
from ConfigParser import RawConfigParser, NoOptionError
import OpenSSL.crypto
import uuid

# DB methods
from db.methods.system import (
    get_systems,
    get_systems_full,
    get_system_info,
    get_system_ip_from_system_id,
    get_system_id_from_local,
    get_system_id_from_system_ip,
    get_sensor_id_from_system_id,
    db_add_system,
    db_remove_system,
    get_system_ip_from_local,
    has_forward_role,
    set_system_vpn_ip,
    db_set_config)

from db.methods.sensor import get_sensor_id_from_sensor_ip

from db.methods.server import (
    db_get_server,
    db_add_child_server,
    get_server_id_from_local,
    get_server_ip_from_server_id)

# Ansible methods
from ansiblemethods.helper import fire_trigger

from ansiblemethods.system.system import (
    get_system_setup_data,
    ansible_add_system,
    ansible_remove_certificates,
    ansible_get_system_info,
    restart_ossim_server,
    generate_sync_sql,
    ansible_run_async_reconfig,
    ansible_check_asynchronous_command_return_code,
    ansible_check_if_process_is_running,
    ansible_get_asynchronous_command_log_file,
    delete_parent_server as ansible_delete_parent_server,
    ansible_get_update_info,
    ansible_download_release_info,
    ansible_get_log_lines,
    ansible_install_plugin,
    ansible_set_system_certificate,
    ansible_remove_system_certificate,
    ansible_restart_frameworkd,
    ansible_get_child_alarms,
    ansible_resend_alarms,
    ansible_clean_squid_config,
)

from ansiblemethods.system.about import (
    get_license_info,
    get_alienvault_version,
    get_is_professional)

from ansiblemethods.system.network import make_tunnel as ansible_make_tunnel_with_vpn

from ansiblemethods.server.server import (
    ans_add_server,
    ans_add_server_hierarchy)

from ansiblemethods.sensor.detector import (
    set_sensor_detectors,
    get_sensor_detectors)

from ansiblemethods.ansibleinventory import AnsibleInventoryManager
from ansiblemethods.helper import (
    local_copy_file,
    remove_file)

from ansiblemethods.system.util import rsync_pull

# Celery methods
from celerymethods.utils import get_task_status, get_running_tasks
from celerymethods.jobs.system import alienvault_asynchronous_update
from celerymethods.jobs.reconfig import alienvault_reconfigure, job_alienvault_reconfigure
from celerymethods.tasks.tasks import Scheduler

# API methods
from apimethods.otx.otx import apimethod_is_otx_enabled
from apimethods.utils import (
    create_local_directory,
    get_base_path_from_system_id,
    get_hex_string_from_uuid,
    is_valid_ipv4)
from apimethods.system.status import ping_system
from apimethods.system.cache import use_cache, flush_cache

# API Exceptions
from apiexceptions import APIException
from apiexceptions.plugin import APICannotSavePlugin


def get_all():
    """
    Get all the information available about registered systems.
    """
    (success, system_data) = ret = get_systems_full()
    if not success:
        return ret

    return (success,
            dict([(x[0], {'admin_ip': x[1]['admin_ip'],
                          'hostname': x[1]['hostname'],
                          'profile': x[1]['profile']}) for x in system_data]))


def get_local_info():
    """
    Get all the information available about the local system.
    """
    success, local_system_id = get_system_id_from_local()
    if not success:
        error_msg = "Something wrong happened retrieving " + \
                    "the local system id"
        return success, error_msg

    success, system_data = get_all()
    if not success:
        error_msg = "Something wrong happened retrieving " + \
                    "the system info"
        return success, error_msg

    if local_system_id in system_data:
        return True, system_data[local_system_id]
    else:
        error_msg = "Something wrong happened retrieving " + \
                    "the local system info"
        return False, error_msg


def get_all_systems_with_ping_info(system_type=None):
    """
    get all the registered systems and ping information
    """

    if system_type is None:
        success, system_list = ret = get_systems_full()
    else:
        success, system_list = ret = get_systems_full(system_type=system_type)
    if not success:
        return ret

    systems = dict(system_list)
    for system_id in systems:
        try:
            reachable = ping_system(system_id)
        except APIException:
            reachable = False
        systems[system_id]['reachable'] = reachable

    return True, systems


@use_cache(namespace="system")
def get(system_id, no_cache=False):
    """
    Get information about a single system
    """
    (success, ip_addr) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret

    return get_system_setup_data(ip_addr)


def create_directory_for_ossec_remote(system_id):
    """
    Creates a directory for ossec remote deployment
    """

    path = get_base_path_from_system_id(system_id) + "/ossec/"
    success, msg = create_local_directory(path)
    if not success:
        return False, msg

    return True, ""


def add_child_server(system_ip, server_id):
    """
    Adds a child server
    """

    db_add_child_server(server_id)
    success, local_server = db_get_server('local')
    if not success:
        return False, local_server

    success, server_connect_ip = get_server_ip_from_server_id(local_server['id'])
    if not success:
        return False, server_connect_ip

    success, msg = ans_add_server(system_ip=system_ip,
                                  server_id=local_server['id'],
                                  server_name=local_server['name'],
                                  server_ip=server_connect_ip,
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


def add_ha_system(system_ip, password, add_to_database=True):
    """
    Add an HA system using system ip.

    Args:
        system_ip (str): IP address of the system to add to HA
        password (str): root password of the system to add

    Returns:
        success (bool): True if OK, False elsewhere
        response (str): Result message
    """
    # Get local IP
    (success, local_system_id) = get_system_id_from_local()
    if not success:
        error_msg = "[add_ha_system] Something wrong happened retrieving " + \
                    "the local system id"
        return success, error_msg

    # Exchange certificates
    (success, response) = ansible_add_system(local_system_id=local_system_id,
                                             remote_system_ip=system_ip,
                                             password=password)
    if not success:
        api_log.error(response)
        return success, "Something wrong happened adding the system"

    # Get remote system info
    (success, system_info) = ansible_get_system_info(system_ip)
    if not success:
        api_log.error(system_info)
        return success, "Something wrong happened getting the system info"

    # Insert system into the database
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
            error_msg = "Something wrong happened inserting " + \
                        "the system into the database"
            return (False, error_msg)

    return success, response


def add_system_from_ip(system_ip, password, add_to_database=True):
    """
    Add a new system using system ip.
    """
    (success, local_system_id) = get_system_id_from_local()
    if not success:
        error_msg = "Something wrong happened retrieving " + \
                    "the local system id"
        return success, error_msg

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
            success, msg = add_child_server(system_ip,
                                            system_info['server_id'])
            if not success:
                api_log.error(str(msg))
                error_msg = "Something wrong happened setting the child server"
                return False, error_msg

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
    if system_info['admin_ip'] != system_ip:
        # We're natted
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
            error_msg = "Something wrong happened inserting " + \
                        "the system into the database"
            return False, error_msg
        else:
            result, _ = get_system_ip_from_system_id(system_info['system_id'])
            if not result:
                error_msg = "System was not inserted, cannot continue"
                return False, error_msg

    # Now that the system is in the database, check if it is a server and
    # open the firewall, if it is required.
    if 'server' in system_info['profile']:
        trigger_success, msg = fire_trigger(system_ip="127.0.0.1",
                                            trigger="alienvault-add-server")
        if not trigger_success:
            api_log.error(msg)

    (success, msg) = create_directory_for_ossec_remote(system_info['system_id'])
    if not success:
        api_log.error(msg)
        return False, msg

    return True, system_info


def add_system(system_id, password):
    """
    Add a system using a system id. Already in database
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error(str(system_ip))
        error_msg = "Error retrieving the system ip " + \
                    "for the system id %s -> %s" % (system_ip, str(system_ip))
        return False, error_msg

    success, msg = add_system_from_ip(system_ip,
                                      password,
                                      add_to_database=False)
    if not success:
        api_log.error(str(msg))
        return False, msg

    return True, msg


def apimethod_delete_system(system_id):
    success, local_system_id = get_system_id_from_local()

    if not success:
        error_msg = "Cannot retrieve the " + \
                    "local system id. %s" % str(local_system_id)
        return success, error_msg
    if system_id == 'local' or get_hex_string_from_uuid(local_system_id) == get_hex_string_from_uuid(system_id):
        error_msg = "You're trying to remove the local system, " + \
                    "which it's not allowed"
        return False, error_msg

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        error_msg = "Cannot retrieve the system ip " + \
                    "for the given system-id %s" % (str(system_ip))
        return success, error_msg

    # Check whether the remote system is reachable or not:
    try:
        remote_system_is_reachable = ping_system(system_id, no_cache=True)
    except APIException:
        remote_system_is_reachable = False

    # We need to take the sensor_id from the database before removing it from the db
    (success_f, sensor_id) = get_sensor_id_from_system_id(system_id)

    # 1 - Remove it from the database
    success, msg = db_remove_system(system_id)
    if not success:
        error_msg = "Cannot remove the system " + \
                    "from the database <%s>" % str(msg)
        return success, error_msg

    # 2 - Remove the firewall rules.
    if success_f:
        trigger_success, msg = fire_trigger(system_ip="127.0.0.1",
                                            trigger="alienvault-del-sensor")
        if not trigger_success:
            api_log.error(msg)
    else:
        trigger_success, msg = fire_trigger(system_ip="127.0.0.1",
                                            trigger="alienvault-del-server")
        if not trigger_success:
            api_log.error(msg)

    # 3 - Remove the remote certificates
    # success, msg = ansible_remove_certificates(system_ip)
    # if not success:
    #     return (success,
    #            "Error while removing the remote certificates: %s" % str(msg))
    # 4 - Remove the local certificates and keys
    success, local_ip = get_system_ip_from_local()
    if not success:
        error_msg = "Cannot retrieve the local ip " + \
                    "<%s>" % str(local_ip)
        return success, error_msg

    # Remove remote system certificates on the local system
    success, msg = ansible_remove_certificates(system_ip=local_ip,
                                               system_id_to_remove=system_id)
    if not success:
        return success, "Cannot remove the local certificates <%s>" % str(msg)

    # 5 - Remove it from the ansible inventory.
    try:
        aim = AnsibleInventoryManager()
        aim.delete_host(system_ip)
        aim.save_inventory()
        del aim
    except Exception as aim_error:
        error_msg = "Cannot remove the system from the " + \
                    "ansible inventory file " + \
                    "<%s>" % str(aim_error)
        return False, error_msg

    # 6 - Clean Squid config
    success, result_msg = ansible_clean_squid_config(system_ip)
    if not success:
        error_msg = "Can't clean the squid config: {}".format(result_msg)
        return success, error_msg

    # 7 - Try to connect to the child and remove the parent
    # using it's server_id
    success, own_server_id = get_server_id_from_local()
    if not success:
        error_msg = "Cannot retrieve the server-id " + \
                    "from local <%s>" % str(msg)
        return success, error_msg

    if remote_system_is_reachable:
        success, msg = ansible_delete_parent_server(system_ip, own_server_id)
        if not success:
            error_msg = "Cannot delete parent server in child <%s>" % str(msg)
            return success, error_msg
        return True, ""

    msg = "The remote system is not reachable. " + \
          "We had not been able to remove the parent configuration"
    return True, msg


def sync_database_from_child(system_id):
    """
    Check SQL sync file in system_id and if it differs from the local one,
    get it and add to local database
    Then, check if we have to propagate changes upwards
    and generate sync.sql if so
    """
    # Get remote and local IPs
    (success, remote_system) = get_system_info(system_id)
    if not success:
        error_msg = "[Apimethod sync_database_from_child] " + \
                    "Error retrieving the system info for the system id " + \
                    "%s -> %s" % (system_id, str(remote_system))
        return success, error_msg

    success, local_system_id = get_system_id_from_local()
    if not success:
        error_msg = "[Apimethod sync_database_from_child] " + \
                    "Error retrieving the local system id " + \
                    "-> %s" % (str(local_system_id))
        return success, error_msg

    (success, local_system) = get_system_info(local_system_id)
    if not success:
        error_msg = "[Apimethod sync_database_from_child] " + \
                    "Error retrieving the system info for the system id " + \
                    "%s -> %s" % (local_system_id, str(local_system))
        return success, error_msg

    try:
        system_ip = remote_system['admin_ip']
        # Use VPN IP only if both servers have VPN configured
        if remote_system['vpn_ip'] is not None and local_system['vpn_ip'] is not None:
            system_ip = remote_system['vpn_ip']
    except KeyError as e:
        error_msg = "[Apimethod sync_database_from_child] " + \
                    "Error retrieving the system ip" + \
                    "%s" % (str(e))
        return False, error_msg

    success, local_ip = get_system_ip_from_local()
    if not success:
        error_msg = "[Apimethod sync_database_from_child] " + \
                    "Error while getting the local ip: %s" % str(local_ip)
        return success, error_msg

    # SQL file changed. Get it, check md5 and apply
    # Get MD5SUM file for the SQL file
    remote_md5file_path = "/var/lib/alienvault-center/db/sync.md5"
    local_md5file_path = "%s" % get_base_path_from_system_id(system_id) + \
                         "/sync_%s.md5" % system_id
    (retrieved, msg) = rsync_pull(system_ip,
                                  remote_md5file_path,
                                  local_ip,
                                  local_md5file_path)
    if not retrieved and 'already in sync' not in msg:
        return False, "[Apimethod sync_database_from_child] %s" % msg

    # Check SQL file MD5
    local_file_path = "%s" % get_base_path_from_system_id(system_id) + \
                      "/sync_%s.sql" % system_id
    with open(local_md5file_path) as m:
        md5_read = m.readline()
    p = Popen(['/usr/bin/md5sum', local_file_path], stdout=PIPE)
    md5_calc, err = p.communicate()
    if err:
        return False, "[Apimethod sync_database_from_child] %s" % err
    if str(md5_read.rstrip('\n')) in str(md5_calc):
        return True, "[Apimethod sync_database_from_child] SQL already synced"

    # Get remote sync file if changed
    remote_file_path = "/var/lib/alienvault-center/db/sync.sql"
    (retrieved, msg) = rsync_pull(system_ip,
                                  remote_file_path,
                                  local_ip,
                                  local_file_path)
    if not retrieved:
        if 'already in sync' in msg:
            true_msg = "[Apimethod sync_database_from_child] " + \
                       "Databases already in sync"
            return True, true_msg
        else:
            false_msg = "[Apimethod sync_database_from_child] " + \
                        "%s" % msg
            return False, false_msg

    # Check SQL file MD5
    p = Popen(['/usr/bin/md5sum', local_file_path], stdout=PIPE)
    md5_calc, err = p.communicate()
    if err:
        return False, "[Apimethod sync_database_from_child] %s" % err
    if str(md5_read.rstrip('\n')) not in str(md5_calc):
        error_msg = "[Apimethod sync_database_from_child] " + \
                    "Corrupt or incomplete SQL file (bad md5sum)"
        return False, error_msg

    # SQL file OK. Apply
    with open(local_file_path) as f:
        if call(['/usr/bin/ossim-db'], stdin=f):
            error_msg = "[Apimethod sync_database_from_child] " + \
                        "Error applying SQL file to ossim-db"
            return False, error_msg
        else:
            info_msg = "[Apimethod sync_database_from_child] " + \
                       "SQL applied successfully"
            api_log.info(info_msg)
            # Check first line of sync.sql file for mySQL restart option
            f.seek(0, 0)
            restart_db = "RESTART OSSIM-SERVER" in f.readline()

    # Restart SQL server if needed
    if restart_db:
        try:
            restart_ossim_server(local_ip)
        except Exception, err:
            error_msg = "An error occurred while restarting " + \
                        "MySQL server: %s" % str(err)
            return False, error_msg

    # Check server_forward_role and generate sync.sql
    (success, local_id) = get_system_id_from_local()
    if success and has_forward_role(local_id):
        try:
            generate_sync_sql(local_ip, restart_db)
        except Exception, err:
            error_msg = "An error occurred while generating " + \
                        "sync.sql file: %s" % str(err)
            return False, error_msg

    return True, "[Apimethod sync_database_from_child] SQL sync successful"


@use_cache(namespace="system_packages", expire=84600)
def apimethod_get_update_info(system_id, no_cache=False):
    """Retrieves the system update information
    Args:
      system_id(str): The system id of which we want to know
                      if it has available updates
    Returns:
      (success,data): success=True when the operation when ok,
                      otherwise success=False.
                      On success data will contain a json object
                      with the updates information.
    """
    try:
        (success, system_ip) = get_system_ip_from_system_id(system_id)
        if not success:
            error_msg = "[apimethod_get_packages_info] Error retrieving " + \
                        "the system ip for the system id " + \
                        "%s -> %s" % (system_ip, str(system_ip))
            return success, error_msg
        success, data = ansible_get_update_info(system_ip)
    except Exception as err:
        error_msg = "[apimethod_get_packages_info] " + \
                    "An error occurred while retrieving " + \
                    "the update info <%s>" % str(err)
        return False, error_msg
    return success, data


def apimethod_get_pending_packges(system_id, no_cache=False):
    """Retrieves the available updates for the given system_id
       and the release_info file
    Args:
      system_id(str): The system id of which we want to know
                      if it has available updates
    Returns:
      (success,data): success=True when the operation when ok,
                      otherwise success=False.
                      On success data will contain a json object
                      with the updates information.
    """
    success, data = apimethod_get_update_info(system_id, no_cache=no_cache)
    if not success:
        return success, data

    available_updates = data['available_updates']

    if available_updates:

        # Check for release info file
        success, local_ip = get_system_ip_from_local()
        if not success:
            error_msg = "[apimethod_get_pending_packges] " + \
                        "Unable to get local IP: %s" % local_ip
            api_log.error(error_msg)
            return False, available_updates

        success, is_pro = get_is_professional(local_ip)
        if success and is_pro:
            success, is_trial = system_is_trial(system_id='local')
            if success and is_trial:
                info_msg = "[apimethod_get_pending_packges] " + \
                           "Trial version. Skipping download release info file"
                api_log.info(info_msg)
                return True, available_updates

        success, msg = ansible_download_release_info(local_ip)
        if not success:
            error_msg = "[apimethod_get_pending_packges] " + \
                        "Unable to retrieve release info file: %s" % msg
            api_log.error(error_msg)

    return True, available_updates


def apimethod_get_remote_software_update(system_id, no_cache=False):
    """Retrieves the available updates for the given system_id
    Args:
      system_id(str): The system id of which we want to know
                      if it has available updatesz
    Returns:
      (success,data): success=True when the operation when ok,
                      otherwise success=False.
                      On success data will contain a json object
                      with the updates information.
    """
    systems = []  # Systems that we are going to check the updates
    updates = {}  # Dict with the updates available for each system

    if system_id == 'all':  # If all, we load all the systems
        result, all_systems = get_systems(directly_connected=False)
        if not result:
            api_log.error("Can't retrieve the system info: %s" % str(systems))
            return False, "Can't retrieve the system info: %s" % str(systems)

        for (system_id, system_ip) in all_systems:
            systems.append(system_id)

    else:  # Otherwise we only load in the list the system given.
        systems.append(system_id)

    # For each system, getting the update info
    for sys_id in systems:
        success, data = apimethod_get_update_info(sys_id, no_cache=no_cache)
        if not success:
            error_msg = "Can't retrieve the system updates " + \
                        "for system %s: %s" % (str(sys_id), str(data))
            api_log.error(error_msg)
            updates[sys_id] = {}
            continue

        info = {'current_version': data['current_version'],
                'last_update': data['last_update'],
                'last_update_status': data['last_update_status'],
                'last_feed_update_status': data['last_feed_update_status'],
                'packages': {'total': data['total_packages'],
                             'pending_updates': data['pending_updates'],
                             'pending_feed_updates': data['pending_feed_updates']}}
        updates[sys_id] = info

    return True, updates


def asynchronous_reconfigure(system_id):
    """Launches an asynchronous reconfigure on the given system_ip

    Args:
      system_id (str): The system_id of the system to configure.

    Returns:
      (boolean, str): A tuple containing the result of the execution

    Examples:
      >>> asynchronous_reconfigure("88888888-8888-8888-888888888888")
      (True,"/var/log/alienvault/update/system_reconfigure.log"
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        error_msg = "[asynchronous_reconfigure] " + \
                    "Error retrieving the system ip " + \
                    "for the system id %s -> %s" % (system_ip, str(system_ip))
        return success, error_msg

    return ansible_run_async_reconfig(system_ip)


def asynchronous_update(system_id, only_feed=False, update_key=""):
    """Launches an asynchronous update on the given system_ip

    Args:
      system_id (str): The system_id of the system to update.
      only_feed (boolean): A boolean to indicate that we need
                           to update only the feed.
    Returns:
      (boolean, job_id): A tuple containing the result of the execution

    Examples:
      >>> asynchronous_update("11111111-1111-1111-111111111111")
      (True,"/var/log/alienvault/update/system_update.log")
    """
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        error_msg = "[asynchronous_update] Error retrieving " + \
                    "the system ip for the system id " + \
                    "%s -> %s" % (system_ip, str(system_ip))
        return False, error_msg

    job = alienvault_asynchronous_update.delay(system_ip,
                                               only_feed,
                                               update_key)
    if job is None:
        error_msg = "Cannot update system %s. " % system_id + \
                    "Please verify that the system is reachable."
        api_log.error(error_msg)
        return False, error_msg

    flush_cache(namespace="system_packages")

    return True, job.id


def set_feed_auto_update(enabled=False):
    """ Enables/disables the automatic feed updates.

    Args:
        enabled (bool): flag to enable or disable automatic feed updates. False by default.
    """
    scheduler = Scheduler()
    task = scheduler.get_task('feed_auto_updates')
    task.enabled = enabled
    scheduler.update_task(task)


def check_if_process_is_running(system_id, ps_filter):
    """Check if there is any process running that meet the ps_filter.

    Args:
      system_ip (str): The system_id where you want to know
                       if the process is running
      ps_filter(str): Filter to be passed to grep the results.

    Returns:
      (boolean, str): A tuple containing the result of the execution

    Examples:
      >>> check_if_process_is_running("192.168.63.199", "/var/log/alienvault/update/system_reconfigure.log")
      (True,0)
    """

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        error_msg = "[check_if_process_is_running] " + \
                    "Error retrieving the system ip " + \
                    "for the system id %s -> %s" % (system_ip, str(system_ip))
        return success, error_msg

    return ansible_check_if_process_is_running(system_ip, ps_filter)


def apimethod_check_asynchronous_command_return_code(system_id, rc_file):
    """Check the return code of a previously asynchronous request

    Args:
      system_ip (str): The system_id where you want to know
                       if the process is running
      rc_file(str): The return code file

    Returns:
      (boolean, str): A tuple containing the result of the execution

    Examples:
      apimethod_ansible_check_asynchronous_command_return_code("11111111-1111-1111-1111-1111222244445555", "/var/log/alienvault/update/system_reconfigure.log.rc")
      (True,0)
    """

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        error_msg = "[apimethod_ansible_check_" + \
                    "asynchronous_command_return_code] " + \
                    "Error retrieving the system ip " + \
                    "for the system id %s -> %s" % (system_ip, str(system_ip))
        return success, error_msg

    return ansible_check_asynchronous_command_return_code(system_ip, rc_file)


def apimethod_get_asynchronous_command_log_file(system_id, log_file):
    """Get the asynchronous command log file

    Args:
        system_id(str): The system_id where you want to know get the log file
        log_file(str): The log file you want to retrieve.

    Returns:
        (boolean,str): A tuple containing the result of the execution.
                       On success the str will contain the local copy.

    Examples:
      >>> apimethod_get_asynchronous_command_log_file("11111111-1111-1111-1111-1111222244445555", "/var/log/alienvault/update/system_reconfigure.log")
    """

    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        error_msg = "[apimethod_get_asynchronous_command_log_file] " + \
                    "Error retrieving the system ip " + \
                    "for the system id %s -> %s" % (system_ip, str(system_ip))
        return success, error_msg

    return ansible_get_asynchronous_command_log_file(system_ip, log_file)


def apimethod_check_task_status(system_id, tasks):
    """
    Check the status of a given list of tasks.
    IE: alienvault-update, alienvault-reconfig

    Args:
        system_id (str) : The system_id where you want to check if it's running
        tasks (dict)    : The list of tasks to test.

    Returns:
        success (bool)     : True if successful, False elsewhere
        task_status (dict) : A dictionary containing job_id,
                             job_status for each task

    """
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        error_msg = "[apimethod_check_task_status] " + \
                    "Unable to get system ip " + \
                    "for system id %s: %s" % (system_id, system_ip)
        api_log.error(error_msg)
        return False, {}

    success, task_status = get_task_status(system_id, system_ip, tasks)

    if not success:
        error_msg = "[apimethod_check_task_status] " + \
                    "Unable to get the task status " + \
                    "for system %s: %s" % (system_id, str(task_status))
        api_log.error(error_msg)
        return False, {}

    return success, task_status


def check_update_and_reconfig_status(system_id):
    """
    Check the status of alienvault-update and alienvault-reconfig tasks

    Args:
        system_id (str) : The system_id where you want to check if it's running
    Returns:
        success (bool)     : True if successful, False elsewhere
        task_status (dict) : A dictionary containing job_id,
                            job_status for each task
    """
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        error_msg = "[check_update_and_reconfig_status] " + \
                    "Unable to get system ip " + \
                    "for system id %s: %s" % (system_id, system_ip)
        api_log.error(error_msg)
        return False, ""

    """"
    This is the list of task to check. the format is the following:
    {
        <Name of the task>: {'task': <name of the celery task>,
                             'process': <name of the process>,
                             'param_value': <task condition>,
                             'param_argnum': <position of the condition>}
    }

    In this particular case, we check the alienvault-update and
    alienvault-reconfig. The condition is that the task has to belong
    to the given system_ip
    """
    t_list = {"alienvault-update": {'task': 'alienvault_asynchronous_update',
                                    'process': 'alienvault-update',
                                    'param_value': system_ip,
                                    'param_argnum': 0},
              "alienvault-reconfig": {'task': 'alienvault_asynchronous_reconfigure',
                                      'process': 'alienvault-reconfig',
                                      'param_value': system_ip,
                                      'param_argnum': 0},
              "ossim-reconfig": {'task': '',
                                 'process': 'ossim-reconfig',
                                 'param_value': system_ip,
                                 'param_argnum': 0}
              }
    (success, tasks_status) = apimethod_check_task_status(system_id, t_list)
    if not success:
        error_msg = "[check_update_and_reconfig_status] " + \
                    "Unable to get system ip " + \
                    "for system id %s: %s" % (system_id, system_ip)
        api_log.error(error_msg)

    return success, tasks_status


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
        error_msg = "Error retrieving the system ip " + \
                    "for the system id %s -> %s" % (system_ip, str(system_ip))
        return False, error_msg

    # White list check
    allowed_files = {
        'kern': '/var/log/kern.log',
        'auth': '/var/log/auth.log',
        'daemon': '/var/log/daemon.log',
        'messages': '/var/log/messages',
        'syslog': '/var/log/syslog',
        'agent_stats': '/var/log/alienvault/agent/agent_stats.log',
        'agent': '/var/log/alienvault/agent/agent.log',
        'server': '/var/log/alienvault/server/server.log',
        'reputation': '/var/log/ossim/reputation.log',
        'apache_access': '/var/log/apache2/access.log',
        'apache_error': '/var/log/apache2/error.log',
        'frameworkd': '/var/log/ossim/frameworkd.log',
        'last_update': '/var/log/alienvault/update/last_system_update.rc'
    }

    if log_file not in allowed_files:
        return False, "%s is not a valid key for a log file" % log_file

    if lines not in [50, 100, 1000, 5000]:
        error_msg = "%s is not a valid number of lines. " % str(lines) + \
                    "The number of lines should be in [50, 100, 1000, 5000]"
        return False, error_msg

    (success, msg) = ansible_get_log_lines(system_ip,
                                           logfile=allowed_files[log_file],
                                           lines=lines)

    if not success:
        api_log.error(str(msg))
        return False, msg

    return True, msg


def make_tunnel_with_vpn(system_ip, password):
    """Build the VPN tunnel with the given node"""
    if not is_valid_ipv4(system_ip):
        return False, "Invalid system ip: %s" % str(system_ip)
    success, own_server_id = get_server_id_from_local()
    if not success:
        error_msg = "Error while retrieving " + \
                    "server_id from local: %s" % str(own_server_id)
        return success, error_msg

    success, local_ip = get_system_ip_from_local()
    if not success:
        return success, "Cannot retrieve the local ip <%s>" % str(local_ip)

    success, data = ansible_make_tunnel_with_vpn(system_ip=system_ip,
                                                 local_server_id=get_hex_string_from_uuid(own_server_id),
                                                 password=password)
    if not success:
        return success, data

    print "Set VPN IP on the system table"
    new_node_vpn_ip = data['client_end_point1']
    if new_node_vpn_ip is None:
        return False, "Cannot retrieve the new node VPN IP"
    print "New Node VPN IP %s" % new_node_vpn_ip
    success, data = get_system_id_from_system_ip(system_ip)
    if success:  # If the system is not on the system table is doesn't matter
        success, data = set_system_vpn_ip(data, new_node_vpn_ip)
        if not success:
            return False, "Cannot set the new node vpn ip on the system table"
    flush_cache(namespace="support_tunnel")
    # Restart frameworkd
    print "Restarting ossim-framework"
    success, data = ansible_restart_frameworkd(system_ip=local_ip)
    if not success:
        print "Restarting %s ossim-framework failed (%s)" % (local_ip, data)
    return True, "VPN node successfully connected."


def sync_asec_plugins(plugin=None, enable=True):
    """
    Send the ASEC generated plugins to the system sensors and enable them

    Args:
        plugin: plugin name
        enable: wether we should enable the plugin or not. Default = True

    Returns:
        success (bool):
        msg (str): Success message/Error info

    """
    if not plugin:
        return False, "No plugin to sync"

    try:
        plugin_path = "/var/lib/asec/plugins/%s.cfg" % plugin
        sql_path = plugin_path + ".sql"

        (success, sensors) = get_systems(system_type='sensor')
        if not success:
            return False, "Unable to get sensors list: %s" % sensors

        # Bug in ansible copy module prevents us from copying the files from
        # /var/lib/asec/plugins as it has permissions 0 for "other"
        # Workaround: make a local copy using ansible command module
        plugin_tmp_path = "/tmp/%s.cfg" % plugin
        sql_tmp_path = plugin_tmp_path + ".sql"
        success, local_ip = get_system_ip_from_local()
        if not success:
            error_msg = "[ansible_install_plugin] Failed to make get local IP: %s" % local_ip
            return False, error_msg

        (success, msg) = local_copy_file(local_ip, plugin_path, plugin_tmp_path)
        if not success:
            error_msg = "[ansible_install_plugin] Failed to make temp copy of plugin file: %s" % msg
            return False, error_msg
        (success, msg) = local_copy_file(local_ip, sql_path, sql_tmp_path)
        if not success:
            error_msg = "[ansible_install_plugin] Failed to make temp copy of sql file: %s" % msg
            return False, error_msg

        all_ok = True
        for (sensor_id, sensor_ip) in sensors:
            (success, msg) = ansible_install_plugin(sensor_ip, plugin_tmp_path, sql_tmp_path)

            if not success:
                api_log.error("[sync_asec_plugins] Error installing plugin %s "
                              "in sensor %s: %s" % (plugin, sensor_ip, msg))
                all_ok = False

            # if success and enable=True -> activate plugin
            elif enable:
                # Get list of active plugins and add the new one.
                # Then send the list back to the sensor?
                (success, data) = get_sensor_detectors(sensor_ip)
                if success:
                    data['sensor_detectors'].append(plugin)
                    sensor_det = ','.join(data['sensor_detectors'])
                    (success, data) = set_sensor_detectors(sensor_ip, sensor_det)
                if not success:
                    api_log.error("[sync_asec_plugins] Error enabling plugin %s for sensor %s: "
                                  "%s" % (plugin, sensor_ip, data))
                    all_ok = False
                else:
                    # Now launch reconfig task
                    alienvault_reconfigure.delay(sensor_ip)

        # Delete temporal copies of the files
        remove_file([local_ip], plugin_tmp_path)
        remove_file([local_ip], sql_tmp_path)

        if not all_ok:
            error_msg = "Plugin %s installation failed for some sensors" % plugin
            return False, error_msg

        info_msg = "Plugin %s installed. Enabled = %s" % (plugin, enable)
        return True, info_msg

    except Exception as e:
        api_log.error("[sync_asec_plugins] Exception catched: %s" % str(e))
        return False, "[sync_asec_plugins] Unknown error"


def system_is_trial(system_id='local'):
    """
    Check if the system has a trial license
    Returns:
        success (bool)
        is_trial (bool)
    """
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error(str(system_ip))
        error_msg = "Error retrieving the system ip for the " + \
                    "system id %s -> %s" % (system_ip, str(system_ip))
        return False, error_msg

    success, license_data = get_license_info(system_ip)
    if success and 'email' in license_data:
        return True, True

    return True, False


def system_is_professional(system_id='local'):
    """
    Check if the system is professional
    Returns:
        success (bool)
        is_professional (bool)
    """
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error(str(system_ip))
        error_msg = "Error retrieving the system ip for the " + \
                    "system id %s -> %s" % (system_ip, str(system_ip))
        return False, error_msg

    success, version_data = get_alienvault_version(system_ip)
    if success and 'ALIENVAULT' in version_data:
        return True, True

    return True, False


def get_system_tags(system_id='local'):
    """Retrieves the list of system tags

    Args:
        system_id (str) : The system_id of the system which you want to get
                          the information
    Returns:
        success (bool)     : True if successful, False elsewhere
        tag_list (list) : A list containing all the system tags.
    """
    tags = []
    success, is_professional = system_is_professional(system_id=system_id)
    if not success:
        return False, []
    if is_professional:
        tags.append('USM')
    else:
        tags.append('OSSIM')
    success, is_trial = system_is_trial(system_id=system_id)
    if not success:
        return False, []
    if is_trial:
        tags.append('TRIAL')
    else:
        tags.append('NO-TRIAL')
    if apimethod_is_otx_enabled():
        tags.append('OTX')
    else:
        tags.append('NO-OTX')
    return tags


def get_jobs_running(system_id='local'):
    """
    Searches a system for running jobs
    """
    # Get system_ip from system id
    (success, system_ip) = get_system_ip_from_system_id(system_id)
    if not success:
        error_msg = "Error retrieving the system ip " + \
                    "for the system id %s " % system_id + \
                    "-> %s" % str(system_ip)
        api_log.error(str(system_ip))
        return False, error_msg

    success, running_tasks = get_running_tasks(system_ip)

    conf_backup_task = ".".join(["celerymethods",
                                 "tasks",
                                 "backup_tasks",
                                 "backup_configuration_for_system_id"])
    conf_backup_file_task = ".".join(["celerymethods",
                                      "tasks",
                                      "backup_tasks",
                                      "get_backup_file"])
    reconfigure_task = ".".join(["celerymethods",
                                 "jobs",
                                 "reconfig",
                                 "alienvault_reconfigure"])
    as_reconfigure_task = ".".join(["celerymethods",
                                    "jobs",
                                    "system",
                                    "alienvault_asynchronous_reconfigure"])
    update_task = ".".join(["celerymethods",
                            "jobs",
                            "system",
                            "alienvault_asynchronous_update"])

    task_types_dict = {conf_backup_task: "configuration_backup",
                       conf_backup_file_task: "get_configuration_backup",
                       reconfigure_task: "reconfigure",
                       as_reconfigure_task: "reconfigure",
                       update_task: "update"}

    jobs_list = []
    for dummy_node, task_list in running_tasks.iteritems():
        for task in task_list:
            if task["name"] in task_types_dict.keys():
                cond1 = system_id in literal_eval(task['args'])
                cond2 = "system_id" in literal_eval(task['kwargs']).keys()
                cond3 = False
                if cond2:
                    cond3 = literal_eval(task['kwargs'])["system_id"] == system_id
                if cond1 or (cond2 and cond3):
                    api_log.error("%s\n" % task['args'])
                    aux_job = {"name": task_types_dict[task["name"]],
                               "time_start": int(task["time_start"]),
                               "job_id": task["id"]}
                    jobs_list.append(aux_job)

    return success, jobs_list


def get_license_devices():
    """
    Retrieves the number of assets for a given license

    Return:
        Number of devices signed for a license
        0 if an error occurs
        1000000 if 'devices' is not specified in the ossim.lic file
    """
    rc, pro = system_is_professional()

    if rc and pro:
        if os.path.isfile("/etc/ossim/ossim.lic"):
            try:
                config = RawConfigParser()
                config.read('/etc/ossim/ossim.lic')
                devices = config.getint('appliance', 'devices')
            except NoOptionError:
                devices = 1000000

        else:
            api_log.debug("License devices can't be determined: License file not found")
            devices = 0

    else:
        devices = 1000000

    return devices


def set_system_certificate(system_id, crt, pem, ca):
    """
    Write given content into ssl certificate files
    Returns:
        success (bool)
        err_msg (string)
    """
    cert_plain = ''
    pem_plain = ''
    ca_cert_plain = ''
    cert_src = 'default'
    pem_src = 'default'
    ca_cert_src = 'default'

    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        error_msg = "Error retrieving the system ip for the system id %s -> %s" % (system_ip, str(system_ip))
        return False, error_msg

    # If empty
    if not crt or not pem:
        success, error_msg = ansible_remove_system_certificate(system_ip)
    else:
        try:
            private_key_obj = OpenSSL.crypto.load_privatekey(OpenSSL.crypto.FILETYPE_PEM, pem)
            cert_obj = OpenSSL.crypto.load_certificate(OpenSSL.crypto.FILETYPE_PEM, crt)

            context = OpenSSL.SSL.Context(OpenSSL.SSL.TLSv1_METHOD)
            context.use_privatekey(private_key_obj)
            context.use_certificate(cert_obj)

            context.check_privatekey()
        except Exception, e:
            return False, "SSL Certificate validation error: %s" % str(e)

        success, error_msg = ansible_set_system_certificate(system_ip, crt, pem, ca)
        cert_plain = crt
        pem_plain = pem
        ca_cert_plain = ca
        cert_src = '/etc/ssl/private/custom_ui_certificate.crt'
        pem_src = '/etc/ssl/private/custom_ui_private.key'
        ca_cert_src = '/etc/ssl/private/custom_ui_ca_certificate.crt' if ca else 'default'

    if not success:
        return False, error_msg

    db_set_config("framework_https_cert_plain", cert_plain)
    db_set_config("framework_https_pem_plain", pem_plain)
    db_set_config("framework_https_ca_cert_plain", ca_cert_plain)
    db_set_config("framework_https_crt", cert_src)
    db_set_config("framework_https_pem", pem_src)
    db_set_config("framework_https_ca_cert", ca_cert_src)

    return job_alienvault_reconfigure(system_ip)


def get_child_alarms(system_id, delay=1, delta=3):
    """
        Return  a hash with key=>event_id, data[key] = server_id from child server
    """
    try:
        (success, system_ip) = get_system_ip_from_system_id(system_id)
        if not success:
            error_msg = "[apimethod_get_child_alarms] Error retrieving " + \
                        "the system ip for the system id " + \
                        "%s -> %s" % (system_id, str(system_ip))
            return success, error_msg
        success, data = ansible_get_child_alarms(system_ip, delay, delta)
    except Exception as err:
        error_msg = "[apimethod_get_child_alarms] " + \
                    "An error occurred while retrieving " + \
                    "the child alarms <%s>" % str(err)
        return False, error_msg
    return success, data


def resend_alarms(server_id, alarms):
    """
        Resend to server each alarm in alarms
    """
    try:
        system_id = str(uuid.UUID(server_id))
        (success, system_ip) = get_system_ip_from_system_id(system_id)
        if not success:
            error_msg = "[apimethod_resend_alarms] Error retrieving " + \
                        "the system ip for the system id " + \
                        "%s -> %s" % (system_id, str(system_ip))
            return success, error_msg
        success, data = ansible_resend_alarms(system_ip, alarms)
    except Exception as err:
        error_msg = "[apimethod_resend_alarms] " + \
                    "An error occurred while retrieving " + \
                    "the child alarms <%s>" % str(err)
        return False, error_msg
    return success, data
