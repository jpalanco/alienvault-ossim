# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2015 AlienVault
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
import os
from ansiblemethods.helper import get_files_in_path, remove_file
from ansiblemethods.system.backup import run_restore
from ansiblemethods.system.backup import ansible_get_backup_list
from apimethods.system.cache import use_cache
from apimethods.utils import secure_path_join
from db.methods.system import get_system_ip_from_system_id

BACKUP_PATH = "/var/alienvault/backup/"


@use_cache(namespace="backup")
def get_backup_list(system_id='local',
                    backup_type='configuration',
                    no_cache=False):
    """
    Get the list of backups in the system
    """
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error(str(system_ip))
        error_msg = "Error retrieving the system ip for the system id "
        error_msg = error_msg + "%s -> %s" % (system_id, str(system_ip))
        return False, error_msg

    success, backup_files = ansible_get_backup_list(target=system_ip)
    if not success:
        return False, backup_files

    if not isinstance(backup_type, list):
        backup_type = [backup_type]
    backup_list = [x for x in backup_files if x['type'] in backup_type]
    # Order by timestamp (Newers fist)
    backup_list = sorted(backup_list, key=lambda k: k['date'], reverse=True)

    return True, backup_list


def restore_backup(system_id='local',
                   backup_type='configuration',
                   backup_name='',
                   backup_pass=''):
    """
    Restore backup in the system
    """
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error(str(system_ip))
        error_msg = "Error retrieving the system ip for the system id %s -> %s" % (system_id, str(system_ip))
        return False, error_msg

    backup_name = os.path.basename(backup_name)
    success, backup_path = secure_path_join(BACKUP_PATH, backup_name)
    if not success:
        api_log.error("restore backup: %s '%s'" % (backup_path, backup_name))
        return False, ""

    try:
        success, msg = run_restore(target=system_ip,
                                   backup_type=backup_type,
                                   backup_file=backup_path,
                                   backup_pass=backup_pass)
        if not success:
            api_log.error("restore_backup: %s" % msg)
            error_msg = "Error trying to restore the backup '%s': %s" % (backup_name, msg)
            return False, error_msg

    except Exception as e:
        api_log.info("restore_backup Error: %s" % str(e))
        error_msg = "Error trying to restore the backup '%s': %s" % (backup_name, str(e))
        return False, error_msg

    return success, msg


def delete_backups(system_id='local',
                   backup_type='configuration',
                   backup_list=None):
    """ Delete backups from the system
    """
    if backup_list is None:
        backup_list = []
    success, system_ip = get_system_ip_from_system_id(system_id)
    if not success:
        api_log.error(str(system_ip))
        error_msg = "Error retrieving the system ip for the system id %s -> %s" % (system_id, str(system_ip))
        return False, error_msg

    success, files = get_files_in_path(system_ip=system_ip, path=BACKUP_PATH)
    if not success:
        return False, files

    # Report warnings for non-existing backup files
    existing_backup_list = []
    backup_name = ''
    for backup_name in backup_list:
        backup_name = os.path.basename(backup_name)
        success, backup_path = secure_path_join(BACKUP_PATH, backup_name)
        if not success:
            api_log.error("delete_backups: %s '%s'" % (backup_path, backup_name))
        elif backup_path not in files.keys():
            api_log.error("delete_backups: %s does not exist" % backup_path)
        else:
            existing_backup_list.append(backup_path)

    # Removing existing backups
    for backup_path in existing_backup_list:
        try:
            success, msg = remove_file(host_list=[system_ip],
                                       file_name=backup_path)
            if not success:
                api_log.error(str(msg))
                error_msg = "Error removing %s from system %s " % (backup_path, system_ip)
                return False, error_msg

        except Exception as e:
            api_log.error("delete_backups Error: %s" % str(e))
            error_msg = "Error trying to delete the backup '%s': %s" % (backup_name, str(e))
            return False, error_msg

    try:
        get_backup_list(system_id=system_id,
                        backup_type=backup_type,
                        no_cache=True)
    except Exception as e:
        error_msg = "Error when trying to flush the cache after deleting backups: %s" % str(e)
        api_log.error(error_msg)

    return success, ''
