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

from flask import Blueprint, request


from api.lib.auth import logged_permission
from api.lib.common import (make_ok, make_error, document_using)
from api.lib.utils import accepted_url
from ansiblemethods.app.gvm import ansible_execute_gvm_command


blueprint = Blueprint(__name__, __name__)


@blueprint.route('/execute_gvm_command', methods=['POST'])
@document_using('static/apidocs/apps/gvm.html')
@accepted_url({'sensor_ip': str, 'gvm_file': str})
@logged_permission.require(http_exception=401)
def execute_gvm_command():
    sensor_ip = request.form.get('sensor_ip', None)
    gvm_file = request.form.get("gvm_file", None)

    success, result = ansible_execute_gvm_command(sensor_ip=sensor_ip, gvm_file=gvm_file)

    if not success:
        return make_error("Error executing command %s" % result, 500)

    return make_ok(command_return=result)
