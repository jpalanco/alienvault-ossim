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

import flask
import flask.ext.login
import api
import api.lib.common
import api.lib.auth
import celerymethods.jobs.ossec_win_deploy

import celery.result
import celery.task.control

#cm = api.lib.celery_manager.CeleryManager()
from flask import Blueprint, request, current_app
from db.methods.sensor import get_sensor_ip_from_sensor_id
from api.lib.utils import accepted_url
from uuid import UUID
from api.lib.auth import admin_permission

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<sensor_id>/ossec/deploy', methods=['PUT'])
@admin_permission.require(http_exception=403)
@accepted_url(
    {'sensor_id': {'type': UUID, 'values': ['local']}, 'agent_name': str, 'windows_ip': str, 'windows_username': str,
     'windows_domain': str, 'windows_password': str})
def ossec_win_deploy(sensor_id):
    # First obtain the admin_ip
    param_names = ['agent_name', 'windows_ip', 'windows_username', 'windows_domain', 'windows_password']
    (result, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id, local_loopback=False)
    if result is False:
        current_app.logger.error("ossec_win_deploy: ossec_win_deploy error: " % str(sensor_ip))
        return api.lib.common.make_error("Error deploying ossec from sensor %s" % sensor_ip, 404)
    # Now the params. We need
    # agent_name
    # windows_ip
    # windows_user
    # windows_domain
    # windows_password
    for k in param_names:
        if request.args.get(k) is None:
            current_app.logger.error("ossec_win_deploy: ossec_win_deploy error: Bad param %s" % k)
            return api.lib.common.make_error("Bad param %s" % k, 400)
    # Ok, all params presents and with value
    job = celerymethods.jobs.ossec_win_deploy.ossec_win_deploy.delay(sensor_ip, request.args['agent_name'],
                                                           request.args['windows_ip'],
                                                           request.args['windows_username'],
                                                           request.args['windows_domain'],
                                                           request.args['windows_password'])

    current_job_id = job.id
    is_finished = False
    job_status = job.status
    job_data = job.info
    jobs_active = None
    msg = "Job launched!"

    return api.lib.common.make_ok(job_id=current_job_id, finished=is_finished, status=job_status, task_data=job_data,
                                  active_jobs=jobs_active, message=msg)
