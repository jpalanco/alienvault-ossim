#  License:
#
#  Copyright (c) 2015 AlienVault
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
from flask import (Blueprint)
from api.lib.auth import admin_permission
from api.lib.common import (
    make_ok, make_error, document_using)
from api.lib.utils import accepted_url
from uuid import UUID
from apimethods.host.host import (get_host_details,
                                  get_host_details_list)

blueprint = Blueprint(__name__, __name__)


@blueprint.route('/<host_id>', methods=['GET'])
@document_using('static/apidocs/status.html')
@admin_permission.require(http_exception=403)
@accepted_url({'host_id': {'type': UUID}})
def get_host_info(host_id):

    (success, host_data) = get_host_details(host_id)
    if not success:
        return make_error(host_data, 500)

    data = {host_id: host_data}
    return make_ok(**data)


@blueprint.route('', methods=['GET'])
@document_using('static/apidocs/status.html')
@admin_permission.require(http_exception=403)
def get_host_info_list():

    (success, data) = get_host_details_list()
    if not success:
        make_error(data, 500)

    return make_ok(**data)


# @blueprint.route('', methods=['POST'])
# @document_using('static/apidocs/status.html')
# @admin_permission.require(http_exception=403)
# @accepted_url({'ctx': {'type': UUID, 'optional': True},
#                'hostname': {'type': str, 'optional': True},
#                'ips': {'optional': True},
#                'sensors': {'optional': True},
#                'fqdns': {'type': str, 'optional': True},
#                'asset_value': {'type': int, 'optional': True},
#                'threshold_c': {'type': int, 'optional': True},
#                'threshold_a': {'type': int, 'optional': True},
#                'alert': {'type': int, 'optional': True},
#                'persistence': {'type': int, 'optional': True},
#                'nat': {'type': str, 'optional': True},
#                'rrd_profile': {'type': str, 'optional': True},
#                'desc': {'type': str, 'optional': True},
#                'lat': {'type': int, 'optional': True},
#                'lon': {'type': int, 'optional': True},
#                'icon': {'type': str, 'optional': True},
#                'country': {'type': str, 'optional': True},
#                'external_host': {'type': int, 'optional': True},
#                'permissions': {'type': str, 'optional': True},
#                'av_component': {'type': int, 'optional': True}})
# def create_new_host(ctx, hostname, ips, sensors, fqdns, asset_value, threshold_c, threshold_a, alert, persistence, nat,
#                     rrd_profile, desc, lat, lon, icon, country, external_host, permissions, av_component):

#     (success, data) = create_host(ctx=ctx,
#                                   hostname=hostname,
#                                   ips=ips,
#                                   sensors=sensors,
#                                   fqdns=fqdns,
#                                   asset_value=asset_value,
#                                   threshold_c=threshold_c,
#                                   threshold_a=threshold_a,
#                                   alert=alert,
#                                   persistence=persistence,
#                                   nat=nat,
#                                   rrd_profile=rrd_profile,
#                                   desc=desc,
#                                   lat=lat,
#                                   lon=lon,
#                                   icon=icon,
#                                   country=country,
#                                   external_host=external_host,
#                                   permissions=permissions,
#                                   av_component=av_component)
#     if not success:
#         make_error(data, 500)

#     return make_ok(**data)

# @blueprint.route('/<host_id>', methods=['PUT'])
# @document_using('static/apidocs/status.html')
# @admin_permission.require(http_exception=403)
# @accepted_url({'host_id': {'type': UUID}})
# def modify_host(host_id):

#     (success, data) = modify_host_details(host_id)
#     if not success:
#         make_error(data, 500)

#     return make_ok()
