#
# License:
#
# Copyright (c) 2013 AlienVault
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

# import api
# import api.lib.common
import celerymethods.jobs.ossec_win_deploy

# import celery.result
# import celery.task.control

from flask import Blueprint, request

from uuid import UUID

import api_log

from api.lib.utils import accepted_url
from api.lib.auth import logged_permission
from api.lib.common import make_ok, make_bad_request, make_error

from apiexceptions.hids import APICannotDeployHIDSAgent

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<sensor_id>/ossec/deploy', methods=['PUT'])
@logged_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']},
               'asset_id': {'type': UUID},
               'windows_ip': str,
               'windows_username': str,
               'windows_password': str,
               'windows_domain': str,
               'agent_id': {'type': str, 'optional': True}})
def ossec_win_deploy(sensor_id):
    asset_id = request.args.get("asset_id", None)
    windows_ip = request.args.get("windows_ip", None)
    windows_username = request.args.get("windows_username", None)
    windows_password = request.args.get("windows_password", None)
    windows_domain = request.args.get("windows_domain", '')
    agent_id = request.args.get("agent_id", None)

    try:
        job = celerymethods.jobs.ossec_win_deploy.ossec_win_deploy.delay(sensor_id,
                                                                         asset_id,
                                                                         windows_ip,
                                                                         windows_username,
                                                                         windows_password,
                                                                         windows_domain,
                                                                         agent_id)

        if not job.failed():
            current_job_id = job.id
            is_finished = False
            job_status = job.status
            job_data = job.info
            active_jobs = None
            msg = "Job launched!"

            res = make_ok(job_id=current_job_id,
                          finished=is_finished,
                          status=job_status,
                          task_data=job_data,
                          active_jobs=active_jobs,
                          message=msg)
        else:
            error_msg = "Sorry, deployment job cannot be launched due to an error when sending the request. " \
                        "Please try again"
            raise APICannotDeployHIDSAgent(error_msg)

    except Exception as e:
        api_log.error(str(e))
        res = make_error(str(e), 500)

    return res
