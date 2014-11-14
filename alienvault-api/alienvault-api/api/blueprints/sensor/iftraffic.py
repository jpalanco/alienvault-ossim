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
from flask import Blueprint, request, current_app
from schema import (Schema,Optional)
from api.lib.utils import accepted_url
from api.lib.common import (
    http_method_dispatcher, make_ok, make_error, check,
    document_using, validate_json, if_content_exists_then_is_json)
from celerymethods.jobs.iftraffic import check_traffic_get_rx_stats
from celery.result import AsyncResult
from api.lib.auth import admin_permission

from werkzeug.contrib.cache import SimpleCache
cache = SimpleCache()

from uuid import UUID

blueprint = Blueprint(__name__, __name__)

@blueprint.route('/<sensor_id>/interfaces/traffic_stats',methods=['PUT'])
@document_using('static/apidocs/iftraffic.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def traffic_stats(sensor_id):
    # Check for delay
    errmsg = "Internal Error"
    errcode = 500
    error = True
    # We need to obtain the system_id from the sensor_id

    result = check_traffic_get_rx_stats.apply_async(args=[sensor_id,[],40], expires =120)
    if result.state != 'FAILURE':
    # Store the task id
    # Task ID of the object, 5 m
        #app.logger.info ("Result.id => " + result.id)
    #cache.add(result.id,result,timeout = 5 * 60)
        error = False
    else:
        errmsg = "Can't start task .check_traffic_get_rx_stats param system_id = " + sensor_id
    if error:
        current_app.logger.error("iftraffic: traffic_stats error: " % errmsg)
        return make_error(errmsg, errcode)
    else:
        return make_ok(data={'sensor_id':sensor_id, 'jobid' : result.id})





