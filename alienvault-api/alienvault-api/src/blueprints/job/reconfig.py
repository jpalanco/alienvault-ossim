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
from asynchat import async_chat
from flask import Blueprint, request
from schema import (Schema,Optional)
from api.lib.common import (
    http_method_dispatcher, make_ok, make_error, check,
    document_using, validate_json, if_content_exists_then_is_json)
from celerymethods.jobs.reconfig import alienvault_reconfigure
from celery.result import AsyncResult

from celery.task.control import inspect
from api.lib.auth import logged_permission

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/reconfig/<system_ip>/<operation>', methods=['GET'],defaults={'jobid':None})
@blueprint.route('/reconfig/<system_ip>/<operation>/<jobid>', methods=['GET'])
@document_using('static/apidocs/sensors.html')
@logged_permission.require(http_exception=401)
def alienvault_reconfig(system_ip,operation,jobid):
    current_job_id = None
    is_finished = False
    job_status = None
    job_data = None
    jobs_active = None
    job = None
    msg = ""

    if operation == "start":
        print "Starting a new job..."
        job = alienvault_reconfigure.delay(system_ip)
        msg ="Job launched!"
    elif operation == "status":
        print "Status..."
        job = AsyncResult(jobid,backend=alienvault_reconfigure.backend)
    elif operation == "list":
        i = inspect()
        jobs_active = i.active()
    else:
        print "operation (%s) not allowed!!" % operation
    if job:
        current_job_id = job.id
        job_data = job.info
        job_status = job.status

    return make_ok(job_id=current_job_id, finished=is_finished, status=job_status, task_data=job_data,
                   active_jobs=jobs_active, message=msg)

