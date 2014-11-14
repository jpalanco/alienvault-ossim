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

from flask import Blueprint, current_app

import api.lib.common
from celerymethods import celery_manager
from celerymethods.utils import JobResult
from api.lib.auth import logged_permission

blueprint = Blueprint(__name__, __name__)

@blueprint.route('/<job_id>', methods=['GET'])
#@logged_permission.require(http_exception=401)
def job_status(job_id):
    job_full_status = celery_manager.CeleryManager.get_job_status(job_id)
    success = True
    job_result = ""
    job_message = ""
    job_log = ""
    if job_full_status:
        status = job_full_status['type']
        if 'result' in job_full_status:
            result = eval(job_full_status['result'])
            job_result = result
            if isinstance(result,dict):
                job_result = result['result']
                job_message = result['message']
                job_log = result['log_file']
        else:
            result = ''
        return api.lib.common.make_ok(job_status=status, job_success=success, job_result=job_result, job_message=job_message, job_log=job_log)
    else:
        return api.lib.common.make_error("No job found with ID '%s'" % job_id, 404)


@blueprint.route('/<job_id>/stop', methods=['PUT'])
@logged_permission.require(http_exception=401)
def job_stop(job_id):
    pass
