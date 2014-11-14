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

from celerymethods.tasks import celery_instance
from db.methods.system import get_systems,get_system_ip_from_local
from logger_maintenance import clean_logger
import logging

logger = get_logger("celery")
MAX_TRIES = 3  # Maximun number of tries before launch an alarm
TIME_BETWEEN_TRIES = 60  # Time between tries in seconds

from ansiblemethods.system.backup import run_backup

notifier = logging.getLogger("backuptask_notifier")
notifier.setLevel(logging.DEBUG)

#add file handler
fh = logging.FileHandler("/var/log/alienvault/api/backup-notifications.log")
fh.setLevel(logging.DEBUG)

#formatter
frmt = logging.Formatter('%(asctime)s [FRAMEWORKD] -- %(levelname)s -- %(message)s', "%Y-%m-%d %H:%M:%S.000000")
fh.setFormatter(frmt)

# add the Handler to the logger
notifier.addHandler(fh)


def make_backup(bk_type):
    """Make the backup and return the result"""
    current_tries = 0    # Current try.
    all_backups_ok = True
    result, systems = get_systems('Sensor')
    if not result:
        notifier.error("An error occurred while making the Backup  [%s]. Cant' retrieve the systems " % bk_type)
        return False

    result, local_system_ip = get_system_ip_from_local(local_loopback=False)
    if not result:
        notifier.error("An error occurred while making the Backup  [%s]. Cant' retrieve the systems " % bk_type)
        return False
    system_ips = [x[1] for x in systems]
    if local_system_ip not in system_ips:
        system_ips.append(local_system_ip)

    for system_ip in system_ips:
        backup_error = ""
        backup_made = False
        current_tries = 0
        while current_tries < MAX_TRIES:
            try:
                data = run_backup(target=system_ip, backup_type=bk_type)
                if data[system_ip]['failures'] > 0 or data[system_ip]['unreachable'] > 0:
                    backup_error = "Backup (%s) Error %s" % (bk_type, data)
                else:
                    notifier.info("Backup successfully made [%s - %s] " % (system_ip,bk_type))
                    backup_made = True
                    current_tries=MAX_TRIES+1
            except Exception as e:
                backup_error = "An exception occurred while making the Backup(%s)  %s" % (bk_type,str( e))
                notifier.error("An exception occurred while making the Backup  [%s - %s]" % (system_ip,bk_type))
            finally:
                current_tries+=1

        if not backup_made:
            all_backups_ok = False
            notifier.error("Backup(%s) Fails: %s" % (bk_type,backup_error))
    #TODO: It should throw an alarm
    # Don't launch the clean logger if backup fails
    if all_backups_ok:
        if not clean_logger():
            notifier.error("An error occurred while cleaning the logger logs.")
    return all_backups_ok


@celery_instance.task
def backup_configuration():
    """Task to run periodically."""
    return make_backup(bk_type="configuration")

@celery_instance.task
def backup_environment():
    """Task to run periodically."""
    return make_backup(bk_type="environment")
