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
from flask import Blueprint, request

from api.lib.utils import accepted_url
from api.lib.auth import admin_permission
from api.lib.common import (
    make_ok,
    make_error,
    make_bad_request,
    document_using,
    make_error_from_exception)
from apimethods.plugin.plugin import (
    apimethod_get_plugin_list,
    apimethod_upload_plugin,
    apimethod_download_plugin,
    apimethod_remove_plugin
)
from apiexceptions import APIException

blueprint = Blueprint(__name__, __name__)


@blueprint.route('', methods=['GET'])
@document_using('static/apidocs/status.html')
def get_list():
    try:
        data = apimethod_get_plugin_list()
    except APIException as e:
        return make_error_from_exception(e)
    return make_ok(plugins=data)


@blueprint.route('/upload', methods=['POST'])
@document_using('static/apidocs/plugin/plugin.html')
@admin_permission.require(http_exception=403)
@accepted_url({'plugin_file': str,
               'vendor': str,
               'model': str,
               'product_type': int,
               'version': {'type': str, 'optional': True},
               'overwrite': {'type': bool, 'optional': True},
               })
def upload():
    try:
        plugin_file = request.form['plugin_file']
        vendor = request.form.get('vendor', '')
        model = request.form.get('model', '')
        if not model:
            return make_bad_request("Model cannot be null")
        if not vendor:
            return make_bad_request("Vendor cannot be null")
        version = request.form.get('version', '-')
        overwrite = request.form.get('overwrite', False)
        product_type = request.form.get('product_type', '')
        data = apimethod_upload_plugin(plugin_file=plugin_file,
                                       model=model,
                                       vendor=vendor,
                                       version=version,
                                       overwrite=overwrite,
                                       product_type=product_type)
    except APIException as e:
        return make_error_from_exception(e)
    return make_ok(**data)


@blueprint.route('/download', methods=['POST'])
@document_using('static/apidocs/plugin/plugin.html')
@admin_permission.require(http_exception=403)
@accepted_url({'plugin_file': str})
def download():
    try:
        plugin_file = request.form['plugin_file']
        data = apimethod_download_plugin(plugin_file=plugin_file)
        # response = make_response(data)
        # response.headers["Content-Disposition"] = "attachment; filename={}".format(plugin_file)
    except APIException as e:
        return make_error_from_exception(e)
    return make_ok(contents=data)


@blueprint.route('/remove', methods=['DELETE'])
@document_using('static/apidocs/plugin/plugin.html')
@admin_permission.require(http_exception=403)
@accepted_url({'plugin_file': str})
def remove():
    try:
        plugin_file = request.args.get('plugin_file')
        apimethod_remove_plugin(plugin_file=plugin_file)
    except APIException as e:
        return make_error_from_exception(e)
    return make_ok()
