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
from flask.ext.login import current_user
from api.lib.utils import accepted_url
from api.lib.common import make_ok, make_bad_request, make_error, document_using
from api.lib.auth import admin_permission, logged_permission
import api_log
from apimethods.utils import is_json_true

from apimethods.otx.otx import (apimethod_register_otx_token,
                                apimethod_get_open_threat_exchange_config,
                                apimethod_remove_otx_account,
                                apimethod_start_contributing_otx,
                                apimethod_stop_contributing_otx,
                                apimethod_get_pulse_list,
                                apimethod_get_pulse_detail,
                                apimethod_get_otx_pulse_stats_summary,
                                apimethod_get_otx_pulse_stats_top,
                                apimethod_get_otx_pulse_stats_event_trend,
                                apimethod_get_otx_pulse_stats_event_top)

blueprint = Blueprint(__name__, __name__)



@blueprint.route('/otx/', methods=['GET'])
@document_using('static/apidocs/system.html')
@logged_permission.require(http_exception=401)
def get_otx_config():

    success, otx_data = apimethod_get_open_threat_exchange_config()
    if not success:
        current_app.logger.error("OTX: It wasn't possible to retrieve the OTX configuration data: %s" % str(otx_data))
        return make_error(otx_data, 500)

    return make_ok(**otx_data)


@blueprint.route('/otx/<otx_token>', methods=['POST'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
def register_otx_token(otx_token):

    success, otx_data = apimethod_register_otx_token(otx_token)
    if not success:
        current_app.logger.error("OTX: It wasn't possible to register the OTX token: %s" % str(otx_data))
        return make_error(otx_data, 500)

    return make_ok(**otx_data)


@blueprint.route('/otx/', methods=['DELETE'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
def remove_otx_account():

    success, otx_data = apimethod_remove_otx_account()
    if not success:
        current_app.logger.error("OTX: It wasn't possible to remove the OTX account: %s" % str(otx_data))
        return make_error(otx_data, 500)

    return make_ok()


@blueprint.route('/otx/contribute/', methods=['POST'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
def start_contributing_otx():

    success, otx_data = apimethod_start_contributing_otx()
    if not success:
        current_app.logger.error("OTX: It wasn't possible to start contributing to OTX: %s" % str(otx_data))
        return make_error(otx_data, 500)

    return make_ok()


@blueprint.route('/otx/contribute/', methods=['DELETE'])
@document_using('static/apidocs/system.html')
@admin_permission.require(http_exception=403)
def stop_contributing_otx():

    success, otx_data = apimethod_stop_contributing_otx()
    if not success:
        current_app.logger.error("OTX: It wasn't possible to stop contributing to OTX: %s" % str(otx_data))
        return make_error(otx_data, 500)

    return make_ok()
    
    
@blueprint.route('/otx/pulse/', methods=['GET'])
@document_using('static/apidocs/system.html')
@logged_permission.require(http_exception=401)
@accepted_url({'page': {'type': int, 'optional': True},
               'page_rows': {'type': int, 'optional': True}})
def get_pulse_list():

    page = request.args.get('page', 1)
    if page is not None:
        page = int(page)

    page_row = request.args.get('page_rows', 10)
    if page_row is not None:
        page_row = int(page_row)
        
    success, pulse_list = apimethod_get_pulse_list(page, page_row)
    
    if not success:
        current_app.logger.error("OTX: It wasn't possible to retrieve the Pulse list: %s" % str(pulse_list))
        return make_error(pulse_list, 500)

    return make_ok(**pulse_list)


@blueprint.route('/otx/pulse/<pulse_id>', methods=['GET'])
@document_using('static/apidocs/system.html')
@logged_permission.require(http_exception=401)
@accepted_url({'pulse_id': {'type': str, 'optional': False},
               'hide_ioc': {'type': bool, 'optional': True}})
def get_pulse_detail(pulse_id):

    hide_ioc = is_json_true(request.args.get('hide_ioc'))

    success, pulse_detail = apimethod_get_pulse_detail(pulse_id, hide_ioc)
    if not success:
        current_app.logger.error("OTX: It wasn't possible to retrieve the Pulse detail: %s" % str(pulse_detail))
        return make_error(pulse_detail, 500)

    return make_ok(**pulse_detail)


@blueprint.route('/otx/pulse/stats/summary/', methods=['GET'])
@document_using('static/apidocs/system.html')
@logged_permission.require(http_exception=401)
@accepted_url()
def get_otx_pulse_stats_summary():

    user = current_user.login

    success, pulse_detail = apimethod_get_otx_pulse_stats_summary(user)
    if not success:
        current_app.logger.error("OTX: It wasn't possible to retrieve the Pulse summary: %s" % str(pulse_detail))
        return make_error(pulse_detail, 500)

    return make_ok(**pulse_detail)


@blueprint.route('/otx/pulse/stats/top/', methods=['GET'])
@document_using('static/apidocs/system.html')
@logged_permission.require(http_exception=401)
@accepted_url({'top': {'type': int, 'optional': False},
               'range': {'type': int, 'optional': True}})
def get_otx_pulse_stats_top():

    top = int(request.args.get('top', 10))
    day_range = int(request.args.get('range', 0))
    user_dic = current_user.serialize

    success, top_pulses = apimethod_get_otx_pulse_stats_top(user_dic, top, day_range)
    if not success:
        current_app.logger.error("OTX: It wasn't possible to retrieve the Top Pulses: %s" % str(top_pulses))
        return make_error(top_pulses, 500)

    return make_ok(**top_pulses)


@blueprint.route('/otx/pulse/stats/event_trend/', methods=['GET'])
@document_using('static/apidocs/system.html')
@logged_permission.require(http_exception=401)
@accepted_url({'range': {'type': int, 'optional': True}})
def get_otx_pulse_stats_event_trend():

    day_range = int(request.args.get('range', 0))
    user_dic = current_user.serialize

    success, trend_pulses = apimethod_get_otx_pulse_stats_event_trend(user_dic, '', day_range)
    if not success:
        current_app.logger.error("OTX: It wasn't possible to retrieve the Events from all OTX Pulses: %s" % str(trend_pulses))
        return make_error(trend_pulses, 500)

    return make_ok(**trend_pulses)


@blueprint.route('/otx/pulse/stats/event_top/', methods=['GET'])
@document_using('static/apidocs/system.html')
@logged_permission.require(http_exception=401)
@accepted_url({'top': {'type': int, 'optional': False},
               'range': {'type': int, 'optional': True}})
def get_otx_pulse_stats_event_top():

    top = int(request.args.get('top', 10))
    day_range = int(request.args.get('range', 0))
    user_dic = current_user.serialize

    success, top_pulses = apimethod_get_otx_pulse_stats_event_top(user_dic, top, day_range)
    if not success:
        current_app.logger.error("OTX: It wasn't possible to retrieve the Top Pulses: %s" % str(top_pulses))
        return make_error(top_pulses, 500)

    return make_ok(**top_pulses)
