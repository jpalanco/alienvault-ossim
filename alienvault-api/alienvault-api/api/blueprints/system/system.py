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
from flask import Blueprint, request, current_app
from api.lib.utils import accepted_url
from uuid import UUID
from api.lib.common import make_ok, make_bad_request, make_error, document_using
from api.lib.auth import admin_permission, logged_permission
import api_log
from apimethods.system import system
from apimethods.system.system import apimethod_get_pending_packges
from apimethods.system.system import apimethod_get_remote_software_update
from apimethods.utils import is_valid_ipv4
from apimethods.utils import is_json_boolean, is_json_true
from celerymethods.jobs.system import alienvault_asynchronous_update


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


@blueprint.route('/<system_id>', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_system(system_id):
    (success, ip) = system.get(system_id)
    if not success:
        current_app.logger.error ("system: get_system error: " + str(ip))
        return make_error("Cannot retrieve system %s info" % system_id, 500)

    return make_ok(info=ip)


@blueprint.route('', methods=['POST'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_ip': str, 'password': str})
def add_system():

    if not is_valid_ipv4(request.form['system_ip']):
        return make_bad_request("Bad system_ip: %s" % request.form['system_ip'])

    (success, system_data) = system.add_system_from_ip(request.form['system_ip'], request.form['password'])
    if not success:
       current_app.logger.error("system: add_system error: " + str(system_data))
       return make_error(system_data, 500)

    return make_ok(**system_data)


@blueprint.route('/<system_id>', methods=['DELETE'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def delete_system(system_id):
    (success, msg) = system.apimethod_delete_system(system_id)
    if not success:
        return make_error("An error occurred while deleting the system <%s>: %s" % (system_id, msg), 500)

    return make_ok(message=msg)


@blueprint.route('/<system_id>/authenticate', methods=['PUT'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'password': {'type': str, 'optional': False}})
def put_system_authenticate(system_id):

    password = request.args.get("password")

    success, msg = system.add_system(system_id, password)
    if not success:
        api_log.error(str(msg))
        return make_error("Cannot add system %s. Please verify that the system is reachable and the password is correct." % system_id, 500)

    return make_ok(**msg)


@blueprint.route('/<system_id>/status/pending_packages', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'no_cache' : {'type': str, 'optional': False},
              })
def get_pending_packages(system_id):
    """Get pending update packages from a given AlienVault system

    The blueprint handle the following url:
    GET /av/api/1.0/system/<system_id>/status/pending_packages

    Args:
        system_id (str): String with system id (uuid) or local

    """
    no_cache = request.args.get('no_cache')
    if not is_json_boolean(no_cache):
        return make_error("Invalid value for the no_cache parameter",500)
    no_cache = is_json_true(no_cache)
    success, result = apimethod_get_pending_packges(system_id,no_cache)
    if not success:
        api_log.error("Error: " + str(result))
        return make_error("Cannot retrieve packages status " + str(result), 500)
    return make_ok(available_updates=result)


@blueprint.route('/<system_id>/status/software', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']},
               'no_cache': {'type': str, 'optional': False},
              })
def get_remote_software_status(system_id):
    """Get the software status from a given AlienVault system

    The blueprint handle the following url:
    GET /av/api/1.0/system/<system_id>/status/software

    Args:
        system_id (str): String with system id (uuid) or local

    """
    no_cache = request.args.get('no_cache')
    if not is_json_boolean(no_cache):
        return make_error("Invalid value for the no_cache parameter",500)
    no_cache = is_json_true(no_cache)
    success, result = apimethod_get_remote_software_update(system_id,no_cache)
    if not success:
        api_log.error("Error: " + str(result))
        return make_error("Cannot retrieve packages status " + str(result), 500)
    return make_ok(**result)


@blueprint.route('/<system_id>/update', methods=['PUT'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def update_system(system_id):
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
              "message": "Cannot update system 564D9762-9196-99CD-46E6-3D941F32AA6. Please verify that the system is reachable.",
              "status": "error",
              "status_code": 500,
              "status_long_message": "Server got itself in trouble",
              "status_short_message": "Internal Server Error"
            }

    """
    job = alienvault_asynchronous_update.delay(system_id, only_feed=False)
    if not job:
        api_log.error("Cannot update system %s. Please verify that the system is reachable." % system_id, 500)
        return make_error("Cannot update system %s. Please verify that the system is reachable." % system_id, 500)
    
    return make_ok(job_id=job.id)


@blueprint.route('/<system_id>/update/feed', methods=['PUT'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def update_system_feed(system_id):
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
              "message": "Cannot update system 564D9762-9196-99CD-46E6-3D941F32AA6. Please verify that the system is reachable.",
              "status": "error",
              "status_code": 500,
              "status_long_message": "Server got itself in trouble",
              "status_short_message": "Internal Server Error"
            }

    """
    job = alienvault_asynchronous_update.delay(system_id, only_feed=True)
    if not job:
        api_log.error("Cannot update system %s. Please verify that the system is reachable." % system_id, 500)
        return make_error("Cannot update system %s. Please verify that the system is reachable." % system_id, 500)

    return make_ok(job_id=job.id)
    
    
@blueprint.route('/<system_id>/tasks', methods=['GET'])
@document_using('static/apidocs/system.html')
#@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}})
def get_task_status(system_id):
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
              "message": "Cannot retrieve tasks for system 564D9762-9196-99CD-46E6-3D941F32AA6. Please verify that the system is reachable.",
              "status": "error",
              "status_code": 500,
              "status_long_message": "Server got itself in trouble",
              "status_short_message": "Internal Server Error"
            }

    """
    t_list = {"alienvault-update" : {'task': 'alienvault_asynchronous_update', 'process': 'alienvault-update', 'param_value': system_id,'param_argnum': 0}, 
              "alienvault-reconfig" : {'task': 'alienvault_asynchronous_reconfigure', 'process': 'alienvault-reconfig', 'param_value': system_id,'param_argnum': 0}}
    """"
    This is the list of task to check. the format is the following:
    {
        <Name of the task>: {'task': <name of the celery task>, 'process': <name of the process>, 'param_value': <task condition>, 'param_argnum': <position of the condition>}
    }
    
    In this particular case, we check the alienvault-update and alienvault-reconfig. The condition is that the task has to belong to the given system_id
    """
    
    success, tasks = system.apimethod_check_task_status(system_id, t_list)
    if not success:
        return make_error("Cannot retrieve task status for system %s. Please verify that the system is reachable." % system_id, 500)
        
    return make_ok(tasks=tasks)


@blueprint.route('/<system_id>/log', methods=['GET'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
@accepted_url({'system_id': {'type': UUID, 'values': ['local']}, 'log_file': {'type': str, 'optional': False}, 
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
