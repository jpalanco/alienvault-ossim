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

from celery.signals import worker_ready

from celerymethods.tasks.monitor_tasks import monitor_update_host_plugins
from celerymethods.tasks.monitor_tasks import monitor_system_reboot_needed
from celerymethods.tasks.monitor_tasks import monitor_download_pulses_ha
from celerymethods.tasks.hids import update_hids_agents
from celerymethods.tasks.maintenance import  purge_nmap_scans

import api_log


@worker_ready.connect
def start_up_tasks(sender=None, conf=None, **kwargs):
    """ Launch tasks at start up when celery workers are ready
    """
    try:
        monitor_update_host_plugins.delay()
    except Exception as e:
        api_log.error("monitor_update_host_plugins: '{0}'".format(str(e)))

    try:
        monitor_system_reboot_needed.delay()
    except Exception as e:
        api_log.error("monitor_system_reboot_needed: '{0}'".format(str(e)))

    try:
        update_hids_agents.delay()
    except Exception as e:
        api_log.error("update_hids_agents: '{0}'".format(str(e)))

    try:
        monitor_download_pulses_ha.delay()
    except Exception as e:
        api_log.error("monitor_download_pulses_ha: '{0}'".format(str(e)))

    try:
        purge_nmap_scans.delay()
    except Exception as e:
        api_log.error("purge_nmap_scans: '{0}'".format(str(e)))
