
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
from flask import Blueprint, request
from api.lib.common import make_ok, make_error, make_bad_request
from api.lib.auth import admin_permission
from api.lib.utils import accepted_url
from uuid import UUID
import os
import json
from celerymethods.tasks.backup_tasks import (
    backup_configuration_for_system_id,
    get_backup_file)
from celery_once.tasks import AlreadyQueued
from apimethods.system.backup import get_backup_list, delete_backups
from apimethods.utils import is_json_true

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<system_id>/backup', methods=['POST'])
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'type': {'type': str, 'values': ['configuration']}})
def post_system_backup(system_id):
    """
    Launch a configuration backup
    """
    backup_type = request.form.get('type', '')
    if backup_type not in ["configuration"]:
        return make_bad_request("Backup type not allowed")

    try:
        job = backup_configuration_for_system_id.delay(system_id=system_id, method="manual")
        if job is None:
            return make_error('Something bad happened running the backup', 500)
    except AlreadyQueued:
        error_msg = "There is an existing backup task running"
        return make_error(error_msg, 500)

    return make_ok(job_id=job.id)


@blueprint.route('/<system_id>/backup', methods=['GET'])
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'type': {'type': str, 'values': ['configuration']},
               'no_cache': {'optional': True, 'values': ['true', 'false']}})
def get_system_backup_list(system_id):
    """
    Get the list of configuration backups in the system
    """
    backup_type = request.args.get('type', '')
    no_cache = request.args.get('no_cache', 'false')
    no_cache = is_json_true(no_cache)

    success, backup_list = get_backup_list(system_id=system_id,
                                           backup_type=backup_type,
                                           no_cache=no_cache)
    if not success:
        return make_error("Error getting backup list. Please check the system is reachable", 500)

    return make_ok(backups=backup_list)


@blueprint.route('/<system_id>/backup/<backup_name>', methods=['GET'])
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'type': {'type': str, 'values': ['configuration']},
               'backup_name': str})
def get_system_backup_file(system_id, backup_name):
    """
    Get a backup file from a remote system.
    """
    backup_type = request.args.get('type', '')
    if backup_type not in ["configuration"]:
        return make_bad_request("Backup type not allowed")

    # Create file first.
    dst_file_path = "/var/alienvault/backup/downloaded/%s" % backup_name

    if os.path.exists(dst_file_path):
        os.remove(dst_file_path)
    try:
        with open(dst_file_path, 'w'):
            os.utime(dst_file_path, None)
    except Exception, e:
        return make_error('Cannot create destination file "%s": %s' % (dst_file_path, str(e)), 500)

    try:
        os.chmod(dst_file_path, 0666)
    except Exception, e:
        os.unlink(dst_file_path)
        return make_error('Cannot change permissions of destination file "%s": %s' % (dst_file_path, str(e)), 500)

    job = get_backup_file.delay(backup_name,
                                system_id=system_id,
                                backup_type=backup_type)
    if job is None:
        return make_error('Something bad happened getting the backup')

    return make_ok(job_id=job.id, path=dst_file_path)


@blueprint.route('/<system_id>/backup', methods=['DELETE'])
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'type': {'type': str, 'values': ['configuration']},
               'backups': str})
def delete_system_backups(system_id):
    """
    Delete the backups specified from the system
    """
    backup_type = request.args.get('type', '')
    try:
        backup_list = json.loads(request.args.get('backups', None))
    except ValueError:
        return make_bad_request("backups not valid")

    success, msg = delete_backups(system_id=system_id,
                                  backup_type=backup_type,
                                  backup_list=backup_list)

    if not success:
        return make_error("Error deleting backups: %s" % msg, 500)

    return make_ok()
