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

from flask import Blueprint, abort
from api.lib.common import (make_ok,
                            document_using)

from api.lib.auth import admin_permission
from api.lib.utils import accepted_url
from apimethods.sensor.ntop import configure_ntop

blueprint = Blueprint(__name__, __name__)

@blueprint.route('/<sensor_id>/ntop/enable', methods=['PUT'])
@document_using('static/apidocs/sensors.html')
@admin_permission.require(http_exception=403)
@accepted_url({'sensor_id': {'type': UUID, 'values': ['local']}})
def put_ntop_config(sensor_id):
    """
    Set the Ntop configuration in a Sensor profile.
    @param sensor_id: Sensor id
    """
    (result, data) = configure_ntop (sensor_id)
    if result:
        return make_ok()
    else:
        return abort(500, data)

