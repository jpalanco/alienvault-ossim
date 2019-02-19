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
import ast
import json
import os
import time

import api_log
from ansiblemethods.sensor.nmap import (ansible_run_nmap_scan, ansible_nmap_get_scan_progress, ansible_nmap_stop,
                                        ansible_nmap_purge_scan_files, ansible_get_partial_results)
from apiexceptions.nmap import (
    APINMAPScanKeyNotFound, APINMAPScanException, APINMAPScanCannotBeSaved, APINMAPScanCannotRetrieveBaseFolder,
    APINMAPScanCannotCreateLocalFolder, APINMAPScanReportNotFound, APINMAPScanCannotReadReport,
    APINMAPScanReportCannotBeDeleted, APINMAPScanCannotRun, APINMAPScanCannotRetrieveScanProgress)

from apiexceptions.sensor import APICannotResolveSensorID
from apimethods.data.idmconn import IDMConnection
from apimethods.sensor.sensor import get_base_path_from_sensor_id
from apimethods.utils import create_local_directory
from celerymethods.utils import is_task_in_celery
from db.methods.sensor import get_sensor_ip_from_sensor_id
from db.redis.nmapdb import NMAPScansDB, NMAPScanCannotBeSaved
from db.redis.redisdb import RedisDBKeyNotFound


def get_nmap_directory(sensor_id):
    """Returns the nmap folder for the given sensor ID
    Args:
        sensor_id(str): Canonical Sensor ID
    Returns:
        destination_path: is an string containing the nmap folder when the method works properly or an
                         error string otherwise.
    Raises:
        APINMAPScanCannotRetrieveBaseFolder
        APINMAPScanCannotCreateLocalFolder

    """
    success, base_path = get_base_path_from_sensor_id(sensor_id)
    if not success:
        raise APINMAPScanCannotRetrieveBaseFolder(base_path)
    destination_path = base_path + "/nmap/"

    # Create directory if not exists
    success, msg = create_local_directory(destination_path)
    if not success:
        api_log.error(str(msg))
        raise APINMAPScanCannotCreateLocalFolder(msg)

    return destination_path


def apimethod_run_nmap_scan(sensor_id, target, idm, scan_type, rdns, scan_timing, autodetect, scan_ports,
                            output_file_prefix="", save_to_file=False, job_id=""):
    """Launches an MAP scan
    Args:
        sensor_id: The system IP where you want to get the [sensor]/interfaces from ossim_setup.conf
        target: IP address of the component where the NMAP will be executed
        idm: Convert results into idm events
        scan_type: Sets the NMAP scan type
        rdns: Tells Nmap to do reverse DNS resolution on the active IP addresses it finds
        scan_timing: Set the timing template
        autodetect: Aggressive scan options (enable OS detection)
        scan_ports: Only scan specified ports
        output_file_prefix: Prefix string to be added to the output filename
        save_to_file: Indicates whether you want to save the NMAP report to a file or not.
        job_id: Celery job ID.

    Returns:
        nmap_report: The NMAP report or the filename where the report has been saved.

    Raises:
        APINMAPScanCannotRun
        APICannotResolveSensorID
        APINMAPScanCannotRetrieveBaseFolder
        APINMAPScanCannotCreateLocalFolder
    """
    (result, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id, local_loopback=False)
    if result is False:
        api_log.error(
            "[apimethod_run_nmap_scan] Cannot retrieve the sensor ip from the given sensor id <%s>" % sensor_id)
        raise APICannotResolveSensorID(sensor_id)
    success, nmap_report = ansible_run_nmap_scan(sensor_ip=sensor_ip, target=target, scan_type=scan_type, rdns=rdns,
                                                 scan_timing=scan_timing, autodetect=autodetect, scan_ports=scan_ports,
                                                 job_id=job_id)
    if not success:
        api_log.error('Failed to launch NMAP scan: %s' % nmap_report)
        raise APINMAPScanCannotRun(nmap_report)

    filename = None
    if save_to_file:
        base_path = get_nmap_directory(sensor_id)
        filename = "%s/nmap_report_%s.json" % (base_path, output_file_prefix)
        with open(filename, "w") as f:
            f.write(json.dumps(nmap_report))

    if idm:
        conn = IDMConnection(sensor_id=sensor_id)
        if conn.connect():
            conn.send_events_from_hosts(nmap_report)
            try:
                if filename is not None:
                    os.remove(filename)
            except Exception:
                pass
        else:
            api_log.error("[apimethod_run_nmap_scan] Cannot connect with the IDM Service")
    try:
        apimethods_nmap_purge_scan_files(job_id)
    except Exception as exp:
        api_log.warning("[apimethod_run_nmap_scan] Cannot purge the scan files %s" % str(exp))
    return nmap_report


def apimethod_get_nmap_scan(sensor_id, task_id):
    """Retrieves the result of an nmap scan
    Args:

    Raises:
        APINMAPScanCannotRetrieveBaseFolder
        APINMAPScanCannotCreateLocalFolder
        APINMAPScanReportNotFound
        APINMAPScanCannotReadReport
    """
    directory = get_nmap_directory(sensor_id)
    nmap_report_path = "{0}/nmap_report_{1}.json".format(directory, task_id)
    if not os.path.isfile(nmap_report_path):
        raise APINMAPScanReportNotFound(nmap_report_path)

    try:
        with open(nmap_report_path, "r") as f:
            return json.loads(f.read())
    except Exception as e:
        api_log.error("[apimethod_get_nmap_scan] {0}".format(str(e)))
        raise APINMAPScanCannotReadReport(nmap_report_path)


def apimethod_delete_nmap_scan(sensor_id, task_id):
    """
    Args:
        sensor_id
        task_id
    Returns:

    Raises:
        APINMAPScanCannotRetrieveBaseFolder
        APINMAPScanReportNotFound
        APINMAPScanCannotCreateLocalFolder
        APINMAPScanReportCannotBeDeleted
    """
    try:
        # When the NMAP scan has been stopped by the user, it could leave some files in tmp folder.
        apimethods_nmap_purge_scan_files(task_id)
    except Exception as exp:
        api_log.warning("[apimethod_delete_nmap_scan] Cannot purge the scan files %s" % str(exp))
    apimethod_nmapdb_delete_task(task_id)
    directory = get_nmap_directory(sensor_id)
    nmap_report_path = "{0}/nmap_report_{1}.json".format(directory, task_id)

    # if not os.path.isfile(nmap_report_path):
    #     raise APINMAPScanReportNotFound(nmap_report_path)

    try:
        if os.path.isfile(nmap_report_path):
            os.remove(nmap_report_path)
    except Exception as e:
        api_log.error("[apimethod_delete_nmap_scan] {0}".format(str(e)))
        raise APINMAPScanReportCannotBeDeleted()


def apimethod_monitor_nmap_scan(sensor_id, task_id):
    """Monitors an NMAP scan
    Args:
        sensor_id: The sensor id where the NMAP is working.
        task_id: The celery task id that is launching the NMAP
    Raises
        APICannotResolveSensorID
        APINMAPScanCannotRetrieveScanProgress

    """
    (result, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id, local_loopback=False)
    if result is False:
        api_log.error(
            "[apimethod_monitor_nmap_scan] Cannot retrieve the sensor ip from the given sensor id <%s>" % sensor_id)
        raise APICannotResolveSensorID(sensor_id)
    try:
        nhosts = ansible_nmap_get_scan_progress(sensor_ip=sensor_ip, task_id=task_id)
    except Exception as e:
        api_log.error("[apimethod_monitor_nmap_scan]  Cannot retrieve scan progress {0}".format(str(e)))
        raise APINMAPScanCannotRetrieveScanProgress()
    return nhosts


def apimethod_get_nmap_scan_list(user):
    """Monitors all NMAP scan list
    Args:
        user: User login
    Returns:
        scans(dic): A python dic with all jobs.
    Raises:
        Exception: When something wrong happen
    """
    user_scans = []
    db = NMAPScansDB()
    scans = db.get_all()
    del db

    for scan in scans:
        if scan['scan_user'] == user:
            user_scans.append(scan)
    return user_scans


def apimethod_delete_running_scans(user):
    """Deletes orphaned NMAP scans which are running on background and not tracked from UI.

    Args:
        user: User login
    Raises:
        Exception: When something wrong happen
    """
    active_scan_list = [scan for scan in apimethod_get_nmap_scan_list(user) if scan.get('status', '') == 'In Progress']
    for scan in active_scan_list:
        apimethod_delete_nmap_scan(scan['sensor_id'], scan['job_id'])


def apimethod_get_nmap_scan_status(task_id):
    """Returns the nmap status for the given task
    Args:
        task_id: The task id which status you want to know
    Returns:
        job(str): A python dic with the job information.
    Raises:
        APINMAPScanKeyNotFound: When the given id doesn't exist
        APINMAPScanException: When something wrong happen
    """
    db = None  # To prevent from NameError in finally clause
    try:
        # the nmap could be scheduled in celery but not launched.
        # in this case there is no nmap status on the database.
        job = None
        db = NMAPScansDB()
        tries = 3
        while tries > 0:
            try:
                raw_data = db.get(task_id)
                job = ast.literal_eval(raw_data)
                tries = 0
            except RedisDBKeyNotFound:
                # Maybe the job is not in the database yet
                # check if the job is scheduled.
                task = is_task_in_celery(task_id)
                if task is not None:
                    if task_id == task['id']:
                        task_kwargs = ast.literal_eval(task['kwargs'])
                        # La info va a de kwargs
                        job = {"job_id": task['id'],
                               "sensor_id": task_kwargs['sensor_id'],
                               "idm": task_kwargs['idm'],
                               "target_number": task_kwargs['targets_number'],
                               "scan_params": {"target": task_kwargs['target'],
                                               "scan_type": task_kwargs['scan_type'],
                                               "rdns": task_kwargs['rdns'],
                                               "autodetect": task_kwargs['autodetect'],
                                               "scan_timing": task_kwargs['scan_timing'],
                                               "scan_ports": task_kwargs['scan_ports']},
                               "status": "In Progress",
                               "scanned_hosts": 0,
                               "scan_user": task_kwargs['user'],
                               "start_time": int(time.time()),
                               "end_time": -1,
                               "remaining_time": -1}
                        tries = 0

                time.sleep(1)
            tries -= 1
    except Exception as e:
        raise APINMAPScanException(str(e))
    finally:
        del db
    if job is None:
        raise APINMAPScanKeyNotFound()
    return job


def apimethods_stop_scan(task_id):
    """Stops the given scan id
    Raises:
        APICannotResolveSensorID
        APINMAPScanKeyNotFound
        APINMAPScanException
    """
    # Stops the celery task.
    job = apimethod_get_nmap_scan_status(task_id)
    job["status"] = "Stopping"
    apimethod_nmapdb_update_task(task_id, job)
    (result, sensor_ip) = get_sensor_ip_from_sensor_id(job["sensor_id"], local_loopback=False)
    if not result:
        raise APICannotResolveSensorID(job["sensor_id"])

    base_path = get_nmap_directory(job["sensor_id"])
    success, result = ansible_nmap_stop(sensor_ip, task_id)
    if not success:
        raise APINMAPScanException(str(result))
    job["status"] = "Finished"
    job["reason"] = "Stopped by the user"
    apimethod_nmapdb_update_task(task_id, job)
    success, result_file = ansible_get_partial_results(sensor_ip, task_id)
    if success:
        try:
            results = {}
            if os.path.isfile(result_file):
                with open(result_file, "r") as f:
                    for line in f.readlines():
                        d = json.loads(line)
                        results[d["host"]] = d["scan"]
                filename = "%s/nmap_report_%s.json" % (base_path, task_id)
                with open(filename, "w") as f:
                    f.write(json.dumps(results))
        except Exception as e:
            raise APINMAPScanException(str(e))


def apimethods_nmap_purge_scan_files(task_id):
    """Purge the given scan files
    Raises:
        APICannotResolveSensorID
        APINMAPScanKeyNotFound
        APINMAPScanException
    """
    job = apimethod_get_nmap_scan_status(task_id)

    (result, sensor_ip) = get_sensor_ip_from_sensor_id(job["sensor_id"], local_loopback=False)
    if not result:
        return False, "Cannot retrieve the sensor ip from the given sensor id {0}".format(job["sensor_id"])
    success, result = ansible_nmap_purge_scan_files(sensor_ip, task_id)
    return success, result


def apimethod_nmapdb_add_task(task_id, task_data):
    """Add a new nmap task to the nmapdb
    Raises:
        APINMAPScanCannotBeSaved
    """
    rt = False
    db = None  # To prevent from NameError in finally clause
    try:
        db = NMAPScansDB()
        db.add(task_id, task_data)
        rt = True
    except NMAPScanCannotBeSaved:
        api_log.error("[apimethod_nmapdb_add_task] NMAPScanCannotBeSaved - Cannot save task")
        raise APINMAPScanCannotBeSaved()
    except Exception as e:
        api_log.error("[apimethod_nmapdb_add_task] Cannot save task %s" % str(e))
    finally:
        del db
    return rt


def apimethod_nmapdb_get_task(task_id):
    """Returns the given task information
    Args:
        task_id: The task id which you want
    Returns:
        job(str): A python dic with the job information.
    Raises:
        APINMAPScanKeyNotFound: When the given id doesn't exist
        APINMAPScanException: When something wrong happen"""
    return apimethod_get_nmap_scan_status(task_id)


def apimethod_nmapdb_update_task(task_id, task_data):
    """Update nmap task in the nmapdb
    Raises:
        APINMAPScanCannotBeSaved
    """
    # When you add a new task if the task already exists, it updates it.
    apimethod_nmapdb_add_task(task_id, task_data)


def apimethod_nmapdb_delete_task(task_id):
    """Delete task from the nmapdb
    Raises:
        APINMAPScanCannotBeSaved
    """
    db = None
    try:
        db = NMAPScansDB()
        db.delete_key(task_id)
    finally:
        del db


def apimethod_nmap_purge_database():
    """Purge the redis database"""
    db = None
    try:
        db = NMAPScansDB()
        db.flush()
    finally:
        del db
