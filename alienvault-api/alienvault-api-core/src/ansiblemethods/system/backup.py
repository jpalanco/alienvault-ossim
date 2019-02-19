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

from ansiblemethods.system.util import rsync_push
from ansiblemethods.ansiblemanager import Ansible
from ansiblemethods.helper import ansible_is_valid_response

ansible = Ansible()


def run_backup(target=None, backup_type="configuration", method="auto", backup_pass=""):

    success, msg = sync_backup_templates(target)
    if not success:
        return False, msg

    args = {"backup_type": "%s" % backup_type, "method": method, 'backup_pass': backup_pass}

    response = ansible.run_module([target], "av_backup", args)
    success, msg = ansible_is_valid_response(target, response)
    if not success and 'failed' in response['contacted'][target]:
        msg = response['contacted'][target]['msg']

    if success:
        msg = response['contacted'][target]['data']

    return success, msg


def run_restore(target=None, backup_type="configuration", backup_file="", backup_pass=""):

    success, msg = sync_backup_templates(target)
    if not success:
        return False, msg

    args = {"backup_type": "%s" % backup_type,
            "backup_file": "%s" % backup_file,
            'backup_pass': backup_pass}

    response = ansible.run_module([target], "av_restore", args)
    success, msg = ansible_is_valid_response(target, response)
    if not success and 'failed' in response['contacted'][target]:
        msg = response['contacted'][target]['msg']

    if success:
        msg = response['contacted'][target]['data']

    return success, msg


def sync_backup_templates(target=None):
    # ansible version 1.4 include module 'syncronize'
    # Consider using it instead of manual rsync
    #
    # Warning! ansible module 'copy' has problems running on celery,
    # probably a multiprocessing issue with file descriptors
    #
    rsync_push(local_ip="127.0.0.1",
               local_file_path='/etc/ansible/backup/',
               remote_ip=target,
               remote_file_path='/etc/ansible/backup/')

    return True, None


def ansible_get_backup_list(target=None):

    args = {"backup_type": "%s" % "configuration"}
    response = ansible.run_module([target], "av_get_backup_files", args)
    success, msg = ansible_is_valid_response(target, response)
    if not success:
        return False, "Cannot retrieve the list of backups"
    return success, response['contacted'][target]['data']
