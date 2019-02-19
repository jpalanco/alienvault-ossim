# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2014 AlienVault
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
from uuid import UUID

import api_log
from api.lib.auth import admin_permission
from api.lib.common import (make_ok,
                            make_bad_request,
                            make_error,
                            make_error_from_exception,
                            document_using)
from api.lib.utils import accepted_url
from apimethods.system import system
from apimethods.system.system import (asynchronous_update,
                                      check_update_and_reconfig_status,
                                      set_feed_auto_update,
                                      set_system_certificate)
from apimethods.system.system import get_jobs_running
from apimethods.system.system import sync_asec_plugins as api_sync_asec
from apimethods.utils import is_valid_ipv4, is_json_true, BOOL_VALUES
from apiexceptions import APIException

from flask import Blueprint, request, current_app


blueprint = Blueprint(__name__, __name__)


@blueprint.route('', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
def get_systems():
    (success, system_data) = system.get_all()
    if not success:
        current_app.logger.error("system: get_systems error: " + str(system_data))
        return make_error("Cannot retrieve systems info", 500)

    return make_ok(systems=system_data)


# This method should not check authentication since it's used during user's
# login process. Please see ENG-105020 for details
@blueprint.route('/local/info', methods=['GET'])
@document_using('static/apidocs/system.html')
def get_local_info():
    success, system_data = system.get_local_info()
    if not success:
        current_app.logger.error("system: get_local_info error: " + str(system_data))
        return make_error("Cannot retrieve local system info", 500)

    return make_ok(**system_data)


@blueprint.route('/<system_id>', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_system(system_id):
    (success, ip) = system.get(system_id)
    if not success:
        current_app.logger.error("system: get_system error: " + str(ip))
        return make_error("Cannot retrieve system %s info" % system_id, 500)

    return make_ok(info=ip)


@blueprint.route('', methods=['POST'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_ip': str, 'password': str})
def add_system():
    if not is_valid_ipv4(request.form['system_ip']):
        return make_bad_request("Bad system_ip: %s" % request.form['system_ip'])

    (success, system_data) = system.add_system_from_ip(request.form['system_ip'],
                                                       request.form['password'])
    if not success:
        current_app.logger.error("system: add_system error: " + str(system_data))
        return make_error(system_data, 500)

    return make_ok(**system_data)


@blueprint.route('/<system_id>', methods=['DELETE'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def delete_system(system_id):
    success, msg = system.apimethod_delete_system(system_id)
    if not success:
        error_msg = "An error occurred while deleting the system <%s>: %s" % (system_id, msg)
        return make_error(error_msg, 500)
    return make_ok(message=msg)


@blueprint.route('/<system_id>/authenticate', methods=['PUT'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'password': {'type': str, 'optional': False}})
def put_system_authenticate(system_id):
    password = request.args.get("password")

    success, msg = system.add_system(system_id, password)
    if not success:
        api_log.error(str(msg))
        error_msg = "Cannot add system %s. " \
                    "Please verify that the system is reachable and the password is correct." % system_id
        return make_error(error_msg, 500)

    return make_ok(**msg)


@blueprint.route('/<system_id>/update', methods=['PUT'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def put_system_update(system_id):
    """Blueprint to update system asynchronously

    Args:
        system_id (UUID): system to update

    Returns:
        data: JSON with status and OK/ERROR message
            success example:
            {
              "data": {
                "job_id": "fe7df875-1939-4c55-a499-af99880f3351"
              },
              "status": "success"
            }
            error example:
            {
              "message": "Cannot update system 564D9762-9196-99CD-46E6-3D941F32AA6.
                          Please verify that the system is reachable.",
              "status": "error",
              "status_code": 500,
              "status_long_message": "Server got itself in trouble",
              "status_short_message": "Internal Server Error"
            }

    """
    (success, job_id) = asynchronous_update(system_id, only_feed=False)
    if not success:
        error_msg = "Cannot update system %s" % system_id
        api_log.error(error_msg + ": %s" % job_id)
        error_msg = error_msg + ". Please verify that the system is reachable."
        return make_error(error_msg, 500)

    return make_ok(job_id=job_id)


@blueprint.route('/<system_id>/update/feed', methods=['PUT'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def put_system_update_feed(system_id):
    """Blueprint to launch local/remote feed update

    Args:
        system_id (UUID): system to update

    Returns:
        data: JSON with status and job ID or error message
            success example:
            {
              "data": {
                "job_id": "fe7df875-1939-4c55-a499-af99880f3351"
              },
              "status": "success"
            }
            error example:
            {
              "message": "Cannot update system 564D9762-9196-99CD-46E6-3D941F32AA6.
                          Please verify that the system is reachable.",
              "status": "error",
              "status_code": 500,
              "status_long_message": "Server got itself in trouble",
              "status_short_message": "Internal Server Error"
            }

    """
    (success, job_id) = asynchronous_update(system_id, only_feed=True)
    if not success:
        error_msg = "Cannot update system %s" % system_id
        api_log.error(error_msg + ": %s" % job_id)
        error_msg += ". Please verify that the system is reachable."
        return make_error(error_msg, 500)

    return make_ok(job_id=job_id)


@blueprint.route('/auto_update', methods=['PUT'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'enabled': {'type': str, 'values': BOOL_VALUES}})
def set_auto_updates():
    enabled = is_json_true(request.args.get('enabled'))
    try:
        set_feed_auto_update(enabled=enabled)
    except APIException as e:
        return make_error_from_exception(e)

    return make_ok()


# This method is used in system's welcome wizard, which doesn't have a user
# context, so it should not check authentication. Please see ENG-105020 for
# details
@blueprint.route('/<system_id>/tasks', methods=['GET'])
@document_using('static/apidocs/system.html')
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_tasks(system_id):
    """
    Blueprint to get the status of system tasks

    Args:
        system_id (UUID): system to update

    Returns:
        data: JSON with status and job ID or error message
            success example:
            {
              "data": {
                  tasks:{
                  "alienvault-update" : {"job_id": "XXXXXXXXX",    "job_status": "<job_status>"},
                  "alienvault-reconfig" : {"job_id": "XXXXXXXXX",    "job_status": "<job_status>"}
                }
              },
              "status": "success"
            }
            error example:
            {
              "message": "Cannot retrieve tasks for system 564D9762-9196-99CD-46E6-3D941F32AA6.
                          Please verify that the system is reachable.",
              "status": "error",
              "status_code": 500,
              "status_long_message": "Server got itself in trouble",
              "status_short_message": "Internal Server Error"
            }

    """
    success, tasks = check_update_and_reconfig_status(system_id)
    if not success:
        error_msg = "Cannot retrieve task status for system %s. Please verify that the system is reachable." % system_id
        return make_error(error_msg, 500)

    return make_ok(tasks=tasks)


@blueprint.route('/<system_id>/log', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'log_file': {'type': str, 'optional': False},
               'lines': {'type': str, 'optional': False}})
def get_last_log_lines(system_id):
    """Get a certain number of log lines from a given log file

        The blueprint handle the following url:
        GET /av/api/1.0/system/<system_id>/log?log_file=<log_file>&lines=<line_number>

        Args:
            system_id (str): String with system id (uuid) or local.
            log_file (str): String with the name of the log file.
            lines (integer): Integer with the number of lines to display.
    """
    log_file = request.args.get("log_file")
    lines = request.args.get("lines")

    success, msg = system.get_last_log_lines(system_id, log_file, int(lines))
    if not success:
        return make_error("Cannot get log lines for given file: %s" % str(msg), 500)

    return make_ok(lines=msg)


@blueprint.route('/asec', methods=['PUT'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'plugins': {'type': str, 'optional': False}})
def sync_asec_plugins():
    """Send ASEC plugins to all sensors

        The blueprint handle the following url:
        PUT /av/api/1.0/system/asec?plugins=<plugins>

        Args:
            plugins (str): Comma separated plugin list
    """
    plugins = request.args.get("plugins")
    plugin_list = plugins.split(',')
    all_ok = True
    failed_plugins = []
    for plugin in plugin_list:
        (success, msg) = api_sync_asec(plugin=plugin, enable=True)
        if not success:
            all_ok = False
            failed_plugins.append(plugin)
            api_log.error("Sync failed for plugin %s: %s" % (plugin, msg))
        else:
            api_log.debug("Sync OK for plugin %s" % plugin)

    if not all_ok:
        error_msg = "ASEC plugins sync failed for plugins: %s" % ','.join(failed_plugins)
        return make_error(error_msg, 500)

    return make_ok(msg="ASEC plugins sync OK")


@blueprint.route('/<system_id>/jobs', methods=['GET'])
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_jobs(system_id):
    """
    Blueprint to get the jobs running on a system

    GET /av/api/1.0/system/<system_id>/jobs

    Args:
        system_id (str): String with system id (uuid) or local.

    Returns:
        data: JSON with job ID, job name and its start time, or error message

        {
            "status": "success",
            "data": {
                "jobs": [
                    {
                        "job_id": "9c83c664-5d8a-4daf-ac2c-532c0209a734",
                        "name": "configuration_backup",
                        "time_start": 1381734702
                    },
                    ...
        }
    """

    success, jobs = get_jobs_running(system_id)
    if not success:
        error_msg = "Can't retrieve jobs running for system %s. Please verify that the system is reachable." % system_id
        return make_error(error_msg, 500)

    return make_ok(jobs=jobs)


@blueprint.route('/<system_id>/certificate', methods=['POST'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'crt': {'type': str},
               'pem': {'type': str},
               'ca': {'type': str}})
def set_certificate(system_id):
    crt = request.form.get('crt', '')
    pem = request.form.get('pem', '')
    ca = request.form.get('ca', '')

    success, job_id = set_system_certificate(system_id=system_id, crt=crt, pem=pem, ca=ca)
    if not success:
        current_app.logger.error("system: set_certificate error: " + str(job_id))
        return make_error(str(job_id), 500)

    return make_ok(job_id=job_id)
