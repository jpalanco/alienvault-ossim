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

import db
from db.models.alienvault import System

import api_log
from sqlalchemy import text as sqltext

from apimethods.decorators import require_db


@require_db
def db_get_otx_alarms(user):
    """Get the Number of Alarms with Pulses:

    Returns:
        alarms (int): Number of alarms with pulses
    """
    try:
        sp_call = sqltext("CALL otx_get_total_alarms(:user);")
        result = db.session.connection(mapper=System).execute(sp_call, user=user).first()
        alarms = int(result[0])
    except Exception as err:
        api_log.error("[db_get_otx_events] Error retrieving the top Pulses: %s" % str(err))
        raise

    return alarms


@require_db
def db_get_otx_events(user):
    """Get the Number of Events with Pulses:

    Returns:
        events (int): Number of events with pulses
    """
    try:
        sp_call = sqltext("CALL otx_get_total_events(:user);")
        result = db.session.connection(mapper=System).execute(sp_call, user=user).first()
        events = int(result[0])
    except Exception as err:
        api_log.error("[db_get_otx_events] Error retrieving the top Pulses: %s" % str(err))
        raise

    return events


@require_db
def db_get_otx_top_pulses(user='', top='', date_from='', date_to=''):
    """Get the Number of Events with Pulses:

    Args:
        user(string)      :  User Login  - empty means any
        top(int)          :  Number of Pulses to Display - empty means everything
        date_from(string) :  Date From - empty means everything
        date_to(string)   :  Date To - empty means everything

    Returns:
        top_list (list): List of top pulses
    """
    top_list = {}
    try:
        sp_call = sqltext("CALL otx_get_top_pulses(:user, :top, :date_from, :date_to);")
        result = db.session.connection(mapper=System).execute(sp_call, user=user, top=top, date_from=date_from, date_to=date_to).fetchall()
        for count, pulse_id in result:
            top_list[pulse_id] = {'total': int(count)}
    except Exception as err:
        api_log.error("[db_get_otx_top_pulses] Error retrieving the top Pulses: %s" % str(err))
        raise

    return top_list


@require_db
def db_get_otx_event_trend(user='', pulse='', date_from='', date_to='', offset_tz=''):
    """Get the Trend of Events with Pulses:

    Args:
        user(string)      :  User Login  - empty means any
        pulse(string)     :  Number of Pulses to Display - empty means everything
        date_from(string) :  Date From - empty means everything
        date_to(string)   :  Date To - empty means everything
        offset_tz(string) :  Timezone Offset

    Returns:
        trend_list (list) : List of event trend with pulses
    """
    trend_list = {}
    pulse_id = "0x%s" % pulse if pulse != '' else ''
    try:
        sp_call = sqltext("CALL otx_get_trend(:user, :pulse, :date_from, :date_to, :tz);")
        result = db.session.connection(mapper=System).execute(sp_call, user=user, pulse=pulse_id, date_from=date_from, date_to=date_to, tz=offset_tz).fetchall()
        for t_total, t_day in result:
            trend_list[str(t_day)] = {'date': str(t_day),
                                      'value': int(t_total)}
    except Exception as err:
        api_log.error("[db_get_otx_top_pulses] Error retrieving the top Pulses: %s" % str(err))
        raise

    return trend_list