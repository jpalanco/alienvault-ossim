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

from ansiblemethods.ansiblemanager import Ansible, PLAYBOOKS, CONFIG_FILE
from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler

ansible = Ansible()
ossim_setup = AVOssimSetupConfigHandler(CONFIG_FILE)

def run_backup(target=None, backup_type="configuration"):

    evars = {"target": "%s" % target,
             "database_host": "%s" % ossim_setup.get_database_db_ip(),
             "database_password": "%s" % ossim_setup.get_database_pass(),
             "database_user": "%s" % ossim_setup.get_database_user(),
             "backup_type": "%s" % backup_type}

    return ansible.run_playbook(playbook=PLAYBOOKS['BACKUP'], host_list=[target], extra_vars=evars)
