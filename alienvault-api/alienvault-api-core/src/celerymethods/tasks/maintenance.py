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

from db.methods.system import get_systems, get_config_backup_days, get_system_ip_from_local
from ansiblemethods.system.maintenance import remove_old_files, ansible_launch_compliance_procedure
from logger_maintenance import clean_logger

from apimethods.sensor.nmap import apimethod_nmap_purge_database
logger = get_logger("celery")

@celery_instance.task
def remove_old_database_files():
    """Task to run periodically."""
    result, systems = get_systems()
    all_task_ok = True
    if result:
        for system_id, system_ip in systems:
            try:
                backup_days = get_config_backup_days()
                logger.info("Backup days... %s" % backup_days)
                data = remove_old_files(target=system_ip, rm_filter="configuration*", n_days=backup_days)
                if data[system_ip]['failures'] > 0 or data[system_ip]['unreachable'] > 0:
                    logger.info("Removing old configuration files Error %s" % data)
                    all_task_ok = False
                data = remove_old_files(target=system_ip, rm_filter="environment*", n_days=backup_days)
                if data[system_ip]['failures'] > 0 or data[system_ip]['unreachable'] > 0:
                    logger.info("Removing old files Error %s" % data)
                    all_task_ok = False
            except Exception, e:
                logger.info("Removing old files error %s" % str(e))
    else:
        logger.warning("Error performing a maintenance task (remove old files): %s" % str(systems))
        all_task_ok = False
    return all_task_ok


@celery_instance.task
def clean_old_loggger_entries():
    """
        Task to clean the logger data.
    """
    result = True
    if not clean_logger():
        logger.error("An error occurred while cleaning the logger logs.")
        result = False
    return result


@celery_instance.task
def launch_compliance_procedure():
    """
    Task to run compliance procedure
    """
    success, system_ip = get_system_ip_from_local()
    if not success:
        return False, "[launch_compliance_procedure] Error obtaining local IP"
    rc, msg = ansible_launch_compliance_procedure(system_ip)
    return (rc, msg)

@celery_instance.task
def purge_nmap_scans():
    """Purges the NMAP scans database. This task should be executed only in the startup."""
    try:
        apimethod_nmap_purge_database()
    except Exception as e:
        logger.error("[purge_nmap_scans] Cannot purge the NMAP database")
    return True
