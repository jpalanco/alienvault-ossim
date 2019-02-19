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

import api_log
import ast
import hashlib
from datetime import datetime, timedelta

from apimethods.otx.pulse import OTXv2, InvalidAPIKey, BadRequest
# DB methods
from db.methods.system import db_set_config, db_get_config
from db.methods.otx import (db_get_otx_alarms,
                            db_get_otx_events,
                            db_get_otx_top_pulses,
                            db_get_otx_event_trend)
from db.redis.pulsedb import PulseDB
from db.redis.pulsecorrelationdb import PulseCorrelationDB
from db.redis.redisdb import RedisDBKeyNotFound

from apimethods.utils import get_tz_offset


def apimethod_register_otx_token(token):
    """
    Get all the information available about registered systems.
    """
    from celerymethods.tasks.monitor_tasks import monitor_download_pulses

    try:
        pulseapi = OTXv2(key=token)
        user_data = pulseapi.check_token()
    except InvalidAPIKey, err:
        api_log.error("[Apimethod apimethod_register_otx_token] ERROR_NOT_REGISTERED_TOKEN: %s" % str(err))
        return False, "ERROR_NOT_REGISTERED_TOKEN"
    except BadRequest, err:
        api_log.error("[Apimethod apimethod_register_otx_token] ERROR_BAD_REQUEST: %s" % str(err))
        return False, "ERROR_BAD_REQUEST"
    except Exception, err:
        api_log.error("[Apimethod apimethod_register_otx_token] ERROR_CONNECTION: %s" % str(err))
        return False, "ERROR_CONNECTION"

    username = user_data.get('username')
    user_id = user_data.get('user_id')
    #if username is user_needs_profile, the otx key is not updated.
    key_version = 1 if username == 'user_needs_profile' else 2

    #First we remove everything related to OTX
    apimethod_remove_otx_account()

    db_set_config("open_threat_exchange", "yes")
    db_set_config("open_threat_exchange_key", token)
    db_set_config("open_threat_exchange_username", username)
    db_set_config("open_threat_exchange_user_id", user_id)
    db_set_config("open_threat_exchange_last", "1969-01-01 00:00:00")
    db_set_config("open_threat_exchange_key_version", key_version)

    #Downloading the pulses
    monitor_download_pulses.delay()

    #Formatting result response
    result = {"token": token,
              "username": username,
              "user_id": user_id,
              "contributing": True,
              "latest_update": "",
              "latest_contribution": "1969-01-01 00:00:00",
              "key_version": key_version}

    return True, result


def apimethod_is_otx_enabled():
    """Retrieves whether a system has OTX enabled or not

    Args:
        system_id (str): The system_id of the system which you want to get the information

    Returns:
        otx_enabled(bool): True if OTX is enabled, otherwise False
    """
    success, value = db_get_config("open_threat_exchange_key")

    if not success:
        api_log.error("[apimethod_is_otx_enabled] %s" % str(value))
        return False
    else:
        #If the token is registered, the otx is enabled
        return True if value else False


def apimethod_get_open_threat_exchange_config():
    """Retrieves the OTX configuration from the database

    Returns:
        success (bool): True if successful, False elsewhere
        result(dict)  : A python dic containing all the OTX configuration.
    """
    result = {}

    keys = {"token": "open_threat_exchange_key",
            "username": "open_threat_exchange_username",
            "user_id": "open_threat_exchange_user_id",
            "latest_update": "open_threat_exchange_latest_update",
            "latest_contribution": "open_threat_exchange_last",
            "contributing": "open_threat_exchange",
            "key_version": "open_threat_exchange_key_version"}

    for result_key, db_key in keys.iteritems():
        success, value = db_get_config(db_key)
        if not success:
            api_log.error("[apimethod_get_open_threat_exchange_config] %s" % str(value))
            return False, str(value)
        else:
            if result_key == "contributing":
                result[result_key] = True if value == "yes" else "no"
            else:
                result[result_key] = value

    #Check problem with OTX keys that are not updated.
    if result["token"] and result["key_version"] < "2":
        try:
            otxapi = OTXv2(key=result["token"])
            user_data = otxapi.check_token()
            username = user_data.get('username')
            user_id = user_data.get('user_id')

            if username != 'user_needs_profile':
                db_set_config("open_threat_exchange_key_version", 2)
                db_set_config("open_threat_exchange_username", username)
                db_set_config("open_threat_exchange_user_id", user_id)
                result["username"] = username
                result["user_id"] = user_id
                result["key_version"] = "2"

        except Exception as err:
            api_log.error("Cannot check if the OTX Key is valid: %s" % str(err))

    return True, result


def apimethod_remove_otx_account():
    """Remove the OTX configuration from the database

    Returns:
        success (bool): True if successful, False elsewhere
        result(string): Error message if there was an error or empty string otherwise.
    """
    #Removing the OTX config vars
    keys = ["open_threat_exchange",
            "open_threat_exchange_key",
            "open_threat_exchange_username",
            "open_threat_exchange_user_id",
            "open_threat_exchange_last",
            "open_threat_exchange_latest_update",
            "open_threat_exchange_key_version"]

    for k in keys:
        success, info = db_set_config(k, "")
        if not success:
            api_log.error("[apimethod_remove_otx_account] %s" % str(info))
            return False, str(info)

    #Removing the pulse database
    try:
        pulse_db = PulseDB()
        pulse_correlation_db = PulseCorrelationDB()

        pulse_db.flush_db()
        pulse_correlation_db.purge_all()
        pulse_correlation_db.sync()

        del pulse_db
        del pulse_correlation_db
    except Exception as err:
        api_log.error("[apimethod_remove_otx_account] %s" % str(err))
        return False, "Error removing OTX Account: Pulse List Cannot Be removed at this time."

    return True, ""


def apimethod_start_contributing_otx():
    """Enable the config flag to start contributing to OTX

    Returns:
        success (bool): True if successful, False elsewhere
        result(string): Error message if there was an error or empty string otherwise.
    """
    success, info = db_set_config("open_threat_exchange", "yes")

    if not success:
        api_log.error("[apimethod_start_contributing_otx] %s" % str(info))
        return False, str(info)

    return True, ""


def apimethod_stop_contributing_otx():
    """Disable the config flag to start contributing to OTX

    Returns:
        success (bool): True if successful, False elsewhere
        result(string): Error message if there was an error or empty string otherwise.
    """
    success, info = db_set_config("open_threat_exchange", "no")

    if not success:
        api_log.error("[apimethod_stop_contributing_otx] %s" % str(info))
        return False, str(info)

    return True, ""


def apimethod_get_pulse_list(page=0, page_row=10):
    """Returns the list of current_status messages matching the given criteria.

    Args:
        page(int)    : Page number
        page_row(int): Number of items per page

    Returns:
        A tuple (boolean,data) where the first argument indicates whether the operation went well or not,
        and the second one contains the data, in case the operation went wll or an error string otherwise

    """
    pulse_list = {"total": 0, "pulses": []}
    start = page
    end = start + page_row - 1

    try:
        pulse_db = PulseDB()
        p_keys = pulse_db.keys()
        p_vals = pulse_db.get_range(start, end, 'desc')
        del pulse_db

        pulses = []
        for p in p_vals:
            pulses.append({"id": p.get('id'),
                           "name": p.get('name'),
                           "author_name": p.get('author_name'),
                           "created": p.get('created'),
                           "description": p.get('description'),
                           "modified": p.get('modified'),
                           "tags": p.get('tags')})

        pulse_list["total"] = len(p_keys)
        pulse_list["pulses"] = pulses

    except Exception as err:
        api_log.error("[apimethod_get_pulse_list] %s" % str(err))
        return False, "Error retrieving the Pulse List: %s" % str(err)

    return True, pulse_list


def apimethod_get_pulse_detail(pulse_id, hide_ioc=False):
    """Disable the config flag to start contributing to OTX

    Args:
        pulse_id(string): Pulse ID

    Returns:
        success (bool): True if successful, False elsewhere
        result(string): Error message if there was an error or empty string otherwise.
    """
    pulse_id = pulse_id.lower()
    try:
        pulse_db = PulseDB()
        p_data = pulse_db.get(pulse_id)
        del pulse_db

        pulse = ast.literal_eval(p_data)

        pulse['total_indicators'] = len(pulse['indicators'])

        if hide_ioc is True:
            pulse.pop('indicators', False)
        elif len(pulse['indicators']) > 0:
            indicators = {}
            for ioc in pulse['indicators']:
                ioc_key = hashlib.md5(ioc.get('indicator', '')).hexdigest()
                indicators[ioc_key] = ioc
            pulse['indicators'] = indicators

    except RedisDBKeyNotFound, err:
        api_log.error("[apimethod_get_pulse_detail] Cannot find the Pulse ID [%s]: %s" % (str(pulse_id), str(err)))
        return False, "Cannot find the Pulse ID [%s]: %s" % (str(pulse_id), str(err))
    except Exception as err:
        api_log.error("[apimethod_get_pulse_detail] %s" % str(err))
        return False, "Error retrieving the Pulse Detail: %s" % str(err)

    return True, pulse


def apimethod_get_otx_pulse_stats_summary(user):
    """Get the pulse statistics:
        #Pulses, #IOCs, Last Updated, #Alarms with Pulses, #Events with Pulses

    Args:
        user(string):  User Login

    Returns:
        success (bool): True if successful, False elsewhere
        result(dic)   : Error message if there was an error or dic with the pulse stats.
    """
    stats = {"pulses": 0, "iocs": 0, "last_updated": "", "alarms": 0, "events": 0}

    if apimethod_is_otx_enabled() is False:
        return False, 'OTX is not activated'

    try:
        pulse_db = PulseDB()
        pulses = pulse_db.get_range(0, -1)
        del pulse_db
        # Getting the number of pulses
        stats['pulses'] = len(pulses)
        # Counting the number of indicators for each pulse.
        for p in pulses:
            stats['iocs'] += len(p.get('indicators', {}))

        stats['alarms'] = db_get_otx_alarms(user)
        stats['events'] = db_get_otx_events(user)
    except Exception as err:
        api_log.error("[apimethod_get_otx_pulse_stats] %s" % str(err))
        return False, "Error retrieving the Pulse Stats: %s" % str(err)

    success, last_updated = db_get_config("open_threat_exchange_latest_update")
    if not success:
        api_log.error("[apimethod_get_otx_pulse_stats] %s" % str(last_updated))
        return False, "Error retrieving the Pulse Stats: %s" % str(last_updated)
    stats['last_updated'] = last_updated

    return True, stats


def apimethod_get_otx_pulse_stats_top(user_dic, top, day_range):
    """Get the top pulses within a given time period:

    Args:
        user_dic(dic) :  User Filter (Login and TZ)
        top(int)      :  Number of Pulses to Display
        day_range(int):  Number of days to count - empty means everything

    Returns:
        success (bool) : True if successful, False elsewhere
        top_list (dict): Error message if there was an error or dic with the top pulses.
    """
    user = user_dic['login']

    if day_range == 0:
        date_from = ''
        date_to = ''
    else:
        date_from = (datetime.utcnow() - timedelta(days=day_range)).strftime('%Y-%m-%d %H:%M:%S')
        date_to = datetime.utcnow().strftime('%Y-%m-%d %H:%M:%S')

    try:
        top_list = db_get_otx_top_pulses(user, top, date_from, date_to)

        for pulse_id, top_data in top_list.iteritems():
            success, p_detail = apimethod_get_pulse_detail(pulse_id)
            if not success:
                api_log.error("[apimethod_get_otx_pulse_stats_top] Cannot Retrieve Pulse Detail for pulse %s: %s" % (str(pulse_id), str(p_detail)))
                top_data['name'] = 'No information available. You are no longer subscribed to this pulse.'
            else:
                top_data['name'] = p_detail.get('name')

    except Exception as err:
        api_log.error("[apimethod_get_otx_pulse_stats_top] %s" % str(err))
        return False, "Error retrieving the Top Pulses: %s" % str(err)

    return True, top_list


def apimethod_get_otx_pulse_stats_event_trend(user_dic, pulse_id='', day_range=0):
    """Get the trend of events with pulses:

    Args:
        user_dic(dic) :  User Filter (Login and TZ)
        pulse_id(str) :  ID of the pulse we want to get the trend of events or empty for all pulses
        day_range(int):  Number of days to count - empty means everything

    Returns:
        success (bool)   : True if successful, False elsewhere
        trend_list (dict): Error message if there was an error or dic with the trend pulses.
    """
    user = user_dic['login']
    tz = user_dic['timezone']
    offset = get_tz_offset(tz)

    if day_range == 0:
        date_from = ''
        date_to = ''
    else:
        date_from = (datetime.utcnow() - timedelta(days=day_range)).strftime('%Y-%m-%d %H:%M:%S')
        date_to = datetime.utcnow().strftime('%Y-%m-%d %H:%M:%S')

    try:
        trend_list = db_get_otx_event_trend(user, pulse_id, date_from, date_to, offset)

    except Exception as err:
        api_log.error("[apimethod_get_otx_pulse_stats_event_trend] %s" % str(err))
        return False, "Error retrieving the events from Pulses: %s" % str(err)

    return True, trend_list


def apimethod_get_otx_pulse_stats_event_top(user_dic, top, day_range):
    """Get the event trend of the top pulses:

    Args:
        user_dic(dic) :  User Filter (Login and TZ)
        top(int)      :  Number of Pulses to Display
        day_range(int):  Number of days to count - empty means everything

    Returns:
        success (bool) : True if successful, False elsewhere
        top_list (dict): Error message if there was an error or dic with the top pulses.
    """
    top_list = {}

    try:
        #First we get the N top pulses
        success, top = apimethod_get_otx_pulse_stats_top(user_dic, top, day_range)
        if not success:
            api_log.error("[apimethod_get_otx_pulse_stats_event_top] %s" % str(top))
            return False, str(top)

        #For each pulse, we get the event trend
        for pulse_id, p_data in top.iteritems():
            success, values = apimethod_get_otx_pulse_stats_event_trend(user_dic, pulse_id, day_range)
            if not success:
                api_log.error("[apimethod_get_otx_pulse_stats_event_top] %s" % str(values))
                return False, str(values)

            p_data['values'] = values
            top_list[pulse_id] = p_data

    except Exception as err:
        api_log.error("[apimethod_get_otx_pulse_stats_event_top] %s" % str(err))
        return False, "Error retrieving the events for top Pulses: %s" % str(err)

    return True, top_list
