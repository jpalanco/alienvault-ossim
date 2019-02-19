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

from celery.utils.log import get_logger

from ansiblemethods.helper import fetch_file
from apimethods.utils import secure_path_join
from celerymethods.tasks import celery_instance
from celery_once.tasks import QueueOnce
from db.methods.system import (get_systems,
                               get_system_ip_from_system_id,
                               get_system_id_from_local,
                               is_local)
from retrying import retry
import logging
import math
import os
import json

logger = get_logger("celery")
MAX_TRIES = 3  # Maximun number of tries before launch an alarm
TIME_BETWEEN_TRIES = 60000  # Time between tries in miliseconds (1 minute)

from ansiblemethods.system.backup import run_backup
from ansiblemethods.system.system import ansible_get_backup_config_pass, get_av_config
from ansiblemethods.helper import remove_file
from apimethods.system.backup import get_backup_list
from apimethods.data.status import insert_current_status_message

notifier = logging.getLogger("backuptask_notifier")
notifier.setLevel(logging.DEBUG)

# add file handler
fh = logging.FileHandler("/var/log/alienvault/api/backup-notifications.log")
fh.setLevel(logging.DEBUG)

# formatter
frmt = logging.Formatter('%(asctime)s [FRAMEWORKD] -- %(levelname)s -- %(message)s', "%Y-%m-%d %H:%M:%S.000000")
fh.setFormatter(frmt)

# add the Handler to the logger
notifier.addHandler(fh)

# touch the file and change its permissions
if not os.path.isfile("/var/log/alienvault/api/backup-notifications.log"):
    open("/var/log/alienvault/api/backup-notifications.log", "a").close()
if oct(os.stat("/var/log/alienvault/api/backup-notifications.log").st_mode & 0777) != '0644':
    os.chmod("/var/log/alienvault/api/backup-notifications.log", 0644)


def make_system_backup_by_system_ip(system_ip, backup_type, method="auto", backup_pass=""):
    """
    Run backup_type for system_ip
    :param system_ip
    :param backup_type
    :param backup_pass
    :param method
    """
    success, msg = run_backup(target=system_ip, backup_type=backup_type, method=method, backup_pass=backup_pass)
    if not success:
        raise (Exception("Error %s" % msg))


@retry(stop_max_attempt_number=MAX_TRIES, wait_fixed=TIME_BETWEEN_TRIES)
def make_system_backup_by_system_ip_with_retry(system_ip, backup_type, method="auto", backup_pass=""):
    """
     Run backup_type for system_ip
    :param system_ip
    :param backup_type
    :param backup_pass
    :param method
    This routine has a retry / timeout

    """
    return make_system_backup_by_system_ip(system_ip, backup_type, backup_pass=backup_pass)


def make_system_backup(system_id, backup_type, rotate=True, retry=True, method="auto", backup_pass=""):
    """
    Run backup_type for system_id
    :param system_id
    :param backup_type
    """
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        return False, system_ip  # here system_ip contains an error msg

    additional_info = json.dumps({'system_id': system_id,
                                  'system_ip': system_ip})

    if not backup_pass or backup_pass == 'NULL':
        msg = 'Password for configuration backups was not set. Backups will be disabled...'
        notifier.warning(msg)
        insert_current_status_message("00000000-0000-0000-0000-000000010039",
                                      system_id,
                                      "system",
                                      additional_info=additional_info)
        return False, msg

    try:
        notifier.info("Running Backup [%s - %s]" % (system_ip, backup_type))
        if retry:
            # This kind of backup is always auto.
            make_system_backup_by_system_ip_with_retry(system_ip, backup_type, backup_pass=backup_pass)
        else:
            make_system_backup_by_system_ip(system_ip, backup_type, method=method, backup_pass=backup_pass)
    except Exception as e:
        notifier.error("Backup fails [%s - %s]: %s" % (system_ip, backup_type, str(e)))
        # To do: Launch a Notification message
        success, result = insert_current_status_message("00000000-0000-0000-0000-000000010018",
                                                        system_id,
                                                        "system",
                                                        additional_info=additional_info)
        if not success:
            return False, str(result) + " " + str(e)
        else:
            return False, str(e)

    notifier.info("Backup successfully made [%s - %s]" % (system_ip, backup_type))
    # To do: Launch a Notification message

    # Rotate
    if rotate:
        success, result = rotate_backups(system_id, backup_type, 10)
        if not success:
            notifier.warning("Error Rotating %s backups in %s" % (backup_type, system_id))
        else:
            notifier.info("Backups rotated successfully")

    # Refresh cache
    try:
        get_backup_list(system_id=system_id,
                        backup_type=backup_type,
                        no_cache=True)
    except Exception as e:
        error_msg = "Error when trying to flush the cache after deleting backups: %s" % str(e)
        notifier.warning(error_msg)

    return True, None


def make_backup_in_all_systems(backup_type):
    """
    Make the backup for:
       - Local system
       - All connected remote sensors
    return True if all the backups finished successfully, False otherwise
    """
    result, systems = get_systems(system_type='Sensor', directly_connected=True)
    if not result:
        notifier.error("An error occurred while making the Backup [%s]. Cant' retrieve the systems " % backup_type)
        return False

    result, local_system_id = get_system_id_from_local()
    if not result:
        notifier.error("An error occurred while making the Backup [%s]. Cant' retrieve the system ID" % backup_type)
        return False

    system_ids = [x[0] for x in systems]
    if local_system_id not in system_ids:
        system_ids.append(local_system_id)

    # Get server ip in case of distributed deployment (Because only server has the UI / possibility to set backup_pass)
    success, server_ip = get_system_ip_from_system_id(local_system_id)
    if not success:
        return False

    all_backups_ok = True
    backup_config_pass = ansible_get_backup_config_pass(server_ip)
    for system_id in system_ids:
        success, msg = make_system_backup(system_id=system_id,
                                          backup_type=backup_type,
                                          rotate=True,
                                          backup_pass=backup_config_pass)
        if not success:
            all_backups_ok = False

    return all_backups_ok


def expfit(backups, tnow):
    """
        Cost function
    """
    exponent = math.log(tnow) / math.log(len(backups))

    def exp_deviation(backup, backupnr):
        return abs(tnow - backup - backupnr ** exponent)

    return sum(exp_deviation(b['index'], bnr)
               for bnr, b in enumerate(reversed(backups)))


def optimize(backups):
    """
        Remove from vector :backup: the worst fit entry
    """
    remove = min((expfit(backups[:i] + backups[i + 1:],
                         backups[-1]['index']), i)
                 for i in xrange(len(backups)))[1]
    return backups[:remove] + backups[remove + 1:]


def rotate_backups(system_id, backup_type="configuration", nbackups=10):
    """
        Rotate the backups
    """
    (success, result) = ret = get_backup_list(system_id=system_id,
                                              backup_type=backup_type,
                                              no_cache=True)
    if not success:
        return ret
    (success, system_ip) = ret = get_system_ip_from_system_id(system_id)
    if not success:
        return ret
    result = [x for x in result if x['date'] is not None and x['method'] == 'auto']
    if len(result) < nbackups:
        return True, 'No backups to remove'
    # Sort the list
    # Discard entries without date?
    # Clean the x['date'] == None
    origbackup = sorted(result, key=lambda x: x['date'])
    ref = origbackup[0]['date']
    for backup in origbackup:
        backup['index'] = backup['date'] - ref
    backups = origbackup[:nbackups]
    for now_bk in origbackup[nbackups:]:
        backups = optimize(backups + [now_bk])
    # Files we want to retain are in backups.
    keep_files = [x['file'] for x in backups]

    files_to_remove = []
    backup_path = "/var/alienvault/backup/"
    for entry in origbackup:
        filepath = entry['file']
        if filepath not in keep_files:
            files_to_remove.append(os.path.join(backup_path, filepath))
    if len(files_to_remove) == 0:
        return True, 'No backups to remove'
    (success, result) = ret = remove_file([system_ip],
                                          " ".join(files_to_remove))
    if not success:
        return ret
    return True, "Removed %d backups" % len(files_to_remove)


@celery_instance.task
def get_backup_file(backup_name,
                    system_id='local',
                    backup_type='configuration'):
    """
    Get a backup file from a remote system.
    """
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        return False

    backup_path = "/var/alienvault/backup/"
    backup_download_path = "/var/alienvault/backup/downloaded/"
    success, src_file_path = secure_path_join(backup_path, backup_name)
    if not success:
        notifier.warning("Invalid backup name %s" % backup_name)
        return False
    success, dst_file_path = secure_path_join(backup_download_path, backup_name)
    if not success:
        notifier.warning("Invalid backup name %s" % backup_name)
        return False

    return fetch_file(system_ip, src_file_path, dst_file_path, flat=True)


@celery_instance.task
def backup_configuration_all_systems():
    """Task to run periodically."""
    return make_backup_in_all_systems(backup_type="configuration")


@celery_instance.task(base=QueueOnce)
def backup_configuration_for_system_id(system_id='local', method="auto"):
    """ Task to run configuration backup for system """
    result, system_ip = get_system_ip_from_system_id(system_id)
    if not result:
        return False

    # If system_id is remote sensor - we need to get server IP to fetch correct backup password.
    server_ip = system_ip
    if not is_local(system_id):
        conf_key = 'server_server_ip'
        _, data = get_av_config(system_ip, {conf_key: True})
        server_ip = data.get(conf_key, system_ip)

    success, msg = make_system_backup(system_id=system_id,
                                      backup_type='configuration',
                                      rotate=False,
                                      retry=False,
                                      method=method,
                                      backup_pass=ansible_get_backup_config_pass(server_ip))

    return success, msg
