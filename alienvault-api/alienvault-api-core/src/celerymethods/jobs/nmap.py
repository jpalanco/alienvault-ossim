# -*- coding: utf-8 -*-
#
# License:
#
# Copyright (c) 2014 AlienVault
# All rights reserved.
#
# This package is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; version 2 dated June, 1991.
# You may not use, modify or distribute this program under any other version
# of the GNU General Public License.
#
# This package is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
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
import time
from celery import current_task
from celery.utils.log import get_logger

from apiexceptions.nmap import APINMAPScanException
from apimethods.sensor.nmap import (apimethod_run_nmap_scan, apimethod_monitor_nmap_scan,
                                    apimethod_nmapdb_add_task, apimethod_nmapdb_get_task, apimethod_nmapdb_update_task)
from celerymethods.tasks import celery_instance
from celerymethods.utils import is_task_in_celery

logger = get_logger("celery")


@celery_instance.task
def run_nmap_scan(sensor_id, target, targets_number, scan_type, rdns, scan_timing, autodetect, scan_ports, idm, user):
    """Launches an NMAP scan
    Args:
        sensor_id: The system ID where you want to get the [sensor]/interfaces from ossim_setup.conf
        target: IP address of the component where the NMAP will be executed
        targets_number: Number of hosts to scan
        scan_type: Sets the NMAP scan type
        rdns: Tells Nmap to do reverse DNS resolution on the active IP addresses it finds
        scan_timing: Set the timing template
        autodetect: Aggressive scan options (enable OS detection)
        scan_ports: Only scan specified ports
        idm: Convert results into idm events
        user: User who launched the scan

    Returns:
        A tuple (success|error, data | msg_error)
    """
    job_id = current_task.request.id

    try:
        # Create a new scan job structure. This JSON will be in redis to keep track of the scan status.
        scan_job = {"job_id": job_id,
                    "sensor_id": sensor_id,
                    "idm": idm,
                    "target_number": targets_number,
                    "scan_params": {"target": target.replace(",", ' '),
                                    "scan_type": scan_type,
                                    "rdns": rdns,
                                    "autodetect": autodetect,
                                    "scan_timing": scan_timing,
                                    "scan_ports": scan_ports},
                    "status": "In Progress",
                    "scanned_hosts": 0,
                    "scan_user": user,
                    "start_time": int(time.time()),
                    "end_time": -1,
                    "remaining_time": -1
                    }
        # Add data about that job in Redis
        apimethod_nmapdb_add_task(job_id, scan_job)
        # Now, launch the real NMAP scan
        apimethod_run_nmap_scan(sensor_id=sensor_id,
                                target=target,
                                idm=idm,
                                scan_type=scan_type,
                                rdns=rdns,
                                scan_timing=scan_timing,
                                autodetect=autodetect,
                                scan_ports=scan_ports,
                                output_file_prefix=str(job_id),
                                save_to_file=True,
                                job_id=job_id)

        # Get current status
        job = apimethod_nmapdb_get_task(job_id)
        # Save the scan status.
        if job is not None:
            job["status"] = "Finished"
            job["scanned_hosts"] = job["target_number"]
            job["remaining_time"] = 0
            job["end_time"] = int(time.time())
            rt = False
            tries = 3
            # Try to save the scan status in redis.
            while not rt and tries > 0:
                rt = apimethod_nmapdb_update_task(job_id, job)
                tries -= 1
                time.sleep(1)
    except Exception as e:
        logger.error("[run_nmap_scan] Error occurred while executing NMAP scan: {0}".format(str(e)))
        job = apimethod_nmapdb_get_task(job_id)
        if job is not None:
            # Could be stopped by the user
            if str(job["status"]).lower() != "finished" and str(job["status"]).lower() != "stopping":
                job["status"] = "Fail"
                job["reason"] = ""
            job["remaining_time"] = 0
            job["end_time"] = int(time.time())
            # Update task when it was finished/failed or stopped
            apimethod_nmapdb_update_task(job_id, job)
    return True


@celery_instance.task
def monitor_nmap_scan(sensor_id, task_id):
    """Monitors an NMAP scan
    Args:
        sensor_id: The sensor id where the NMAP is working.
        task_id: The celery task id that is launching the NMAP
    """
    task = is_task_in_celery(task_id)
    while task is not None:
        try:
            try:
                job = apimethod_nmapdb_get_task(task_id)
            except APINMAPScanException:
                job = None

            if job is not None and job["status"] == "In Progress":
                # check job status
                try:
                    data = apimethod_monitor_nmap_scan(sensor_id, task_id)
                except Exception as error:
                    logger.error("[monitor_nmap_scan:%s] Cannot retrieve scan data...%s" % (task_id, str(error)))
                else:
                    # Scan status is managed by the main scan task. This task should only monitor and retrieve
                    # the scan results...
                    job["scanned_hosts"] = data['scanned_hosts']
                    if data['target_number'] > 0:
                        job["target_number"] = data['target_number']
                        # Estimate end time
                    if data['scanned_hosts'] > 0:
                        average_sec = int((time.time() - job["start_time"]) / data['scanned_hosts'])
                        job["remaining_time"] = (data['target_number'] - data['scanned_hosts']) * average_sec
                    try:
                        apimethod_nmapdb_update_task(task_id, job)
                    except Exception as error:
                        logger.error("[monitor_nmap_scan:%s] Cannot update nmap scan status...%s" % (task_id,
                                                                                                     str(error)))
        except Exception as error:
            logger.error("[monitor_nmap_scan:%s] Unexpected exception while monitoring the NMAP scan status..."
                         "%s:%s" % (task_id, type(error), str(error)))
        time.sleep(10)
        task = is_task_in_celery(task_id)
    logger.warning("[monitor_nmap_scan:%s] It seems that the SCAN has finished.." % str(task_id))
    return True
