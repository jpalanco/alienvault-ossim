#
# License:
#
# Copyright (c) 2013 AlienVault
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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this package; if not, write to the Free Software
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
from uuid import UUID

from flask import Blueprint, request
from flask.ext.login import current_user
from netaddr import IPAddress, IPNetwork

from api import app
from api.lib.auth import logged_permission
from api.lib.common import (make_ok, make_error, document_using)
from api.lib.utils import accepted_url
from apiexceptions.nmap import (
    APINMAPScanKeyNotFound, APINMAPScanException, APINMAPScanCannotRetrieveBaseFolder,
    APINMAPScanCannotCreateLocalFolder, APINMAPScanReportNotFound, APINMAPScanCannotReadReport,
    APINMAPScanReportCannotBeDeleted)
from apiexceptions.sensor import APICannotResolveSensorID
from apimethods.sensor.nmap import (
    apimethod_get_nmap_scan, apimethod_delete_nmap_scan, apimethod_get_nmap_scan_status, apimethods_stop_scan,
    apimethod_get_nmap_scan_list, apimethod_delete_running_scans)
from celerymethods.jobs.nmap import run_nmap_scan, monitor_nmap_scan

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/explain', methods=['POST'])
@document_using('static/apidocs/apps/nmap.html')
@accepted_url({'target': str, 'scan_type': str, 'rdns': str, 'scan_timing': str, 'autodetect': str, 'scan_ports': str})
@logged_permission.require(http_exception=401)
def explain_nmap():
    nmap_explain = {
        "params": {
            "target": "192.168.7.0/16",
            "scan_type": "fast",
            "rdns": "false",
            "scan_timing": "T3",
            "autodetect": "true",
            "scan_ports": ""
        },
        "scan_list": [
            {
                "sensor_id": 1234,
                "sensor_up": "true",
                "scan_list": [
                    {"targets": ["192.168.7.1", "192.168.7.2"]},
                    {"targets": ["192.168.7.3", "192.168.7.4"]}
                ],

            },
            {
                "sensor_id": 1235,
                "sensor_up": "false",
                "scan_list": [
                    {"targets": ["192.168.7.5", "192.168.7.6"]}
                ],
            }

        ],
        "total_assets": 4,

    }
    return make_ok(nmap_explain=nmap_explain)


@blueprint.route('/', methods=['GET'])
@document_using('static/apidocs/apps/nmap.html')
@logged_permission.require(http_exception=401)
def get_list_nmap_scans():
    try:
        user_scans = apimethod_get_nmap_scan_list(user=current_user.login)
    except Exception as exp:
        app.logger.error("Cannot retrieve the scan list {0}".format(str(exp)))
        return make_error("Cannot retrieve the scan list", 500)
    return make_ok(result=user_scans)


@blueprint.route('/', methods=['POST'])
@document_using('static/apidocs/apps/nmap.html')
@accepted_url({'sensor_id': {'type': UUID, 'optional': False},
               'target': {'type': str, 'optional': False},
               'scan_type': {'type': str, 'optional': False},
               'rdns': str,
               'scan_timing': str,
               'autodetect': str,
               'scan_ports': str,
               'idm': str,
               'excludes': str})
@logged_permission.require(http_exception=401)
def do_nmap_scan():
    sensor_id = request.form.get('sensor_id', None)
    target = request.form.get('target', None)
    excludes = request.form.get('excludes', None)
    scan_type = request.form.get('scan_type', None)
    scan_timing = request.form.get('scan_timing', None)
    scan_ports = request.form.get('scan_ports', None)
    rdns = True if request.form.get('rdns', 'true') == 'true' else False
    autodetect = True if request.form.get('autodetect', 'true') == 'true' else False
    idm = True if request.form.get('idm', 'false') == 'true' else False

    targets = target.split(' ')
    ftargets = []
    targets_number = 0
    for t in targets:
        try:
            _ = IPAddress(t)
            ftargets.append(t)
            targets_number += 1
            continue
        except:
            pass

        try:
            cidr = IPNetwork(t)
            ftargets.append(t)
            targets_number += cidr.size
            continue
        except:
            pass

    if len(ftargets) < 1:
        return make_error("No valid targets to scan", 500)

    try:
        # Delete all orphan scans which are running on background for current user.
        apimethod_delete_running_scans(current_user.login)
    except Exception as err:
        return make_error("Cannot flush old scans before running new nmap scan %s" % str(err), 500)

    targets = ','.join(ftargets)
    if targets and excludes:
        # Prepare new targets string with excludes. e.g "192.168.87.0/22,!192.168.87.222/32,!192.168.87.223/32"
        targets += ',' + ','.join(['!{}'.format(exclude_item) for exclude_item in excludes.split(',')])

    job = run_nmap_scan.delay(sensor_id=sensor_id,
                              target=targets,
                              targets_number=targets_number,
                              scan_type=scan_type,
                              rdns=rdns,
                              scan_timing=scan_timing,
                              autodetect=autodetect,
                              scan_ports=scan_ports,
                              idm=idm,
                              user=current_user.login)
    monitor_nmap_scan.delay(sensor_id=sensor_id, task_id=job.id)
    time.sleep(2)
    return make_ok(job_id=job.id)


@blueprint.route('/<task_id>', methods=['DELETE'])
@document_using('static/apidocs/apps/nmap.html')
@accepted_url({'sensor_id': {'type': UUID, 'optional': False},
               'task_id': {'type': UUID, 'optional': False}})
@logged_permission.require(http_exception=401)
def delete_nmap_scan(task_id):
    sensor_id = request.args.get('sensor_id', None)
    try:
        apimethod_delete_nmap_scan(sensor_id=sensor_id, task_id=task_id)
    except (APINMAPScanCannotRetrieveBaseFolder,
            APINMAPScanCannotCreateLocalFolder,
            APINMAPScanReportCannotBeDeleted) as e:
        return make_error(str(e), 500)
    except APINMAPScanReportNotFound as e:
        return make_error(str(e), 404)
    except:
        return make_error("Cannot Delete the report", 500)

    return make_ok(result=True)


@blueprint.route('/<task_id>', methods=['GET'])
@document_using('static/apidocs/apps/nmap.html')
@accepted_url({'sensor_id': {'type': UUID, 'optional': False},
               'task_id': {'type': UUID, 'optional': False}})
@logged_permission.require(http_exception=401)
def get_nmap_scan(task_id):
    sensor_id = request.args.get('sensor_id', None)

    try:
        data = apimethod_get_nmap_scan(sensor_id=sensor_id, task_id=task_id)
    except (APINMAPScanCannotRetrieveBaseFolder, APINMAPScanCannotCreateLocalFolder, APINMAPScanCannotReadReport) as e:
        return make_error(str(e), 500)
    except APINMAPScanReportNotFound as e:
        return make_error(str(e), 404)
    except Exception as e:
        return make_error(str(e), 500)

    return make_ok(result=data)


@blueprint.route('/<task_id>/status', methods=['GET'])
@document_using('static/apidocs/apps/nmap.html')
@accepted_url({'task_id': {'type': UUID, 'optional': False}})
@logged_permission.require(http_exception=401)
def get_nmap_scan_status(task_id):
    try:
        job = apimethod_get_nmap_scan_status(task_id)
    except APINMAPScanKeyNotFound:
        return make_error("Task id not found", 404)
    except APINMAPScanException as exp:
        app.logger.error("Cannot retrieve the scan status {0}".format(str(exp)))
        return make_error("Cannot retrieve the scan status for the given task", 500)
    return make_ok(result=job)


@blueprint.route('/<task_id>/stop', methods=['GET'])
@document_using('static/apidocs/apps/nmap.html')
@accepted_url({'task_id': {'type': UUID, 'optional': False}})
@logged_permission.require(http_exception=401)
def stop_scan(task_id):
    try:
        apimethods_stop_scan(task_id)
    except APICannotResolveSensorID:
        return make_error("Cannot retrieve the task status", 404)
    except APINMAPScanKeyNotFound:
        return make_error("Cannot retrieve the task status", 404)
    except APINMAPScanException:
        return make_error("Cannot stop the scan", 500)

    return make_ok(result=True)
