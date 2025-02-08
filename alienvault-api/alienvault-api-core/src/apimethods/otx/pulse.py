#
# License:
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

# Standard imports
import ast
import datetime
import functools
import json
import time
import urllib

# Related third-party imports
import requests
from requests.exceptions import RequestException

# Local specific imports
import api_log

from ansiblemethods.system.about import get_alienvault_version
from apimethods.system.proxy import AVProxy
from db.methods.system import db_set_config
from db.redis.redisdb import RedisDBKeyNotFound
from db.redis.pulsedb import PulseDB
from db.redis.pulsecorrelationdb import PulseCorrelationDB

INTERNAL_SERVER_ERROR = 500
BAD_GATEWAY = 502
INVALID_API_KEY_CODE = 403
BAD_REQUEST_CODE = 400
TOO_MANY_REQUESTS = 429

# Threshold to control the time downloading pulses
THRESHOLD_TIME_OTX = 1500


def retry(exception_or_tuple, tries=4, delay=20, backoff=2, logger=None):
    """Retry calling the decorated function using an exponential backoff.

    :param exception_or_tuple: the exception(or tuple) to check.
    :type exception_or_tuple: Exception or tuple
    :param tries: number of times to try (not retry) before giving up
    :type tries: int
    :param delay: initial delay between retries in seconds
    :type delay: int
    :param backoff: backoff multiplier e.g. value of 2 will double the delay
    :type backoff: int
    :param logger: logger to use. If None, print
    :type logger: logging.Logger instance
    """
    def deco_retry(f):

        @functools.wraps(f)
        def f_retry(*args, **kwargs):
            mtries, mdelay = tries, delay
            while mtries > 1:
                try:
                    return f(*args, **kwargs)
                except exception_or_tuple as e:
                    msg = "{}, Retrying in {} seconds...".format(e, mdelay)
                    if logger:
                        logger.warning(msg)
                    time.sleep(mdelay)
                    mtries -= 1
                    mdelay *= backoff
            return f(*args, **kwargs)
        return f_retry
    return deco_retry


# TODO We need to move exception to the separate file
class InvalidAPIKey(Exception):
    pass


class BadRequest(Exception):
    pass

class TooManyRequests(Exception):
    pass


class OTXv2(object):
    def __init__(self, key, server="https://otx.alienvault.com/"):
        self.key = key
        self.server = server
        self.url_base = "{}api/v1".format(server)
        self.avproxy = AVProxy()
        self.pulse_db = PulseDB()
        self.pulse_correlation_db = PulseCorrelationDB()

        self.date_types = {"events": "latest_events_call_date",
                           "subscribed": "latest_subscribed_call_date"}
        self.otx_user_version = self.get_otx_user_version()

    def update_latest_request(self, d_type, d_update=None):
        """Update the latest otx request timestamp
        Args:
            type (str): The type of date to update.
            update_date (str): Update Date
        Returns:
            Boolean
        """
        date_type = self.date_types.get(d_type, None)
        if date_type is None:
            return False

        update_date = datetime.datetime.utcnow().strftime("%Y-%m-%dT%H:%M:%S") if d_update is None else d_update
        try:
            self.pulse_db.set_key_value(date_type, update_date)
        except Exception as err:
            api_log.error("Cannot save messages revision: %s" % str(err))
            return False

        return True

    def get_latest_request(self, d_type):
        """Loads the latest request timestamp
        Args:
            type (str): The type of date to get.
        Returns:
            None or the date in string format
        """
        date_type = self.date_types.get(d_type, None)
        if date_type is None:
            return date_type

        try:
            latest_timestamp = self.pulse_db.get(date_type)
        except Exception as err:
            api_log.warning("Cannot get messages revision: {}".format(err))
            return None

        return None if latest_timestamp == "" else latest_timestamp

    @retry(RequestException, tries=5, backoff=2, logger=api_log)
    def make_request(self, url):
        """Make a request against the OTX server
        Args:
            url (str): The url with the request.
        Returns:
            response_data(json): The OTX response
            Raise an exception when something is wrong
        """

        api_log.info("trying to make a request: {}".format(url))
        custom_headers = {'X-OTX-API-KEY': self.key}
        if self.otx_user_version:
            # Information about system that is using OTX
            custom_headers['User-Agent'] = self.otx_user_version

        # http://docs.python-requests.org/en/master/user/advanced/#proxies
        proxies = self.avproxy.get_proxies()
        response_data = requests.get(url, headers=custom_headers, proxies=proxies, timeout=120)
        api_log.info("Status code: {}".format(response_data.status_code))

        if response_data.status_code == INTERNAL_SERVER_ERROR:
            # Making timeout
            raise RequestException("Internal Server Error")

        if response_data.status_code == BAD_GATEWAY:
            # Making timeout
            raise RequestException("Bad Gateway")

        if response_data.status_code == TOO_MANY_REQUESTS:
            raise TooManyRequests("Too Many Requests")

        if response_data.status_code == INVALID_API_KEY_CODE:
            raise InvalidAPIKey("Invalid API Key")

        if response_data.status_code == BAD_REQUEST_CODE:
            raise BadRequest("Bad Request")

        return response_data.json()

    def check_token(self):
        """Checks if a OTX token is valid and return user info if so.

        Returns:
            user_data(dict): A dict with the user info.
        """
        url = "{}/user/".format(self.url_base)

        try:
            user_data = self.make_request(url)
        except Exception as err:
            api_log.warning("OTX key activation error: {}".format(err))
            raise

        return user_data

    def save_pulses(self, pulses):
        """Save a list of pulses in redis.
        Args:
            pulses(list): List of pulses we want to save
        Returns:
            integer: Number of new pulses saved.
        """
        if len(pulses) > 0:
            self.pulse_db.merge(pulses)
            self.pulse_correlation_db.store(pulses)
        return len(pulses)

    def remove_pulses(self, pulses):
        """Delete a list of pulses from redis.
        Args:
            pulses(list): List of pulse IDs we want to remove
        Returns:
            integer: Number of pulses removed.
        """
        del_pulses = len(pulses)
        if del_pulses > 0:
            for p_id in pulses:
                try:
                    pulse = ast.literal_eval(self.pulse_db.get(p_id))
                    self.pulse_db.delete_key(p_id)
                    self.pulse_correlation_db.delete_pulse(pulse)
                except RedisDBKeyNotFound:
                    del_pulses -= 1
                    continue
                except Exception as err:
                    api_log.error("Error deleting Pulse: {}".format(err))
                    del_pulses -= 1
                    continue
        return del_pulses

    def add_pulses_from_list(self, pulses):
        """Add the pulses given.
        Args:
            pulses(list): List of pulses we want to add
        Returns:
            integer: Number of new pulses downloaded.
        """
        p_download = []
        for p_id in pulses:
            request = "{}/pulses/{}/".format(self.url_base, p_id)
            try:
                json_data = self.make_request(request)
                # Save pulse data on redis
                p_download.append(json_data)
            except Exception as err:
                api_log.warning("Cannot download pulse {}: {}".format(p_id, err))
                continue

        return self.save_pulses(p_download)

    def add_pulses_from_authors(self, authors):
        """Add the pulses from some given authors.
        Args:
            authors(list): List of authors we want their pulses to be added
        Returns:
            integer: Number of new pulses added.
        """
        pulse_downloaded = 0
        for author in authors:
            next_request = "{}/pulses/subscribed?limit=20&author_name={}".format(self.url_base, author)
            while next_request:
                try:
                    json_data = self.make_request(next_request)
                    # Save pulse data on redis
                    pulse_downloaded += self.save_pulses(json_data.get('results'))
                    # Get next request
                    next_request = json_data.get('next')
                except Exception as err:
                    api_log.warning("Cannot download pulses from author {}: {}".format(author, err))
                    continue

        return pulse_downloaded

    def remove_pulses_from_authors(self, authors):
        """Remove the pulses from some given authors.
        Args:
            authors(list): List of authors we want their pulses to be removed
        Returns:
            integer: Number of pulses removed.
        """
        if len(authors) < 1:
            return 0

        pulse_list = []
        all_pulses = self.pulse_db.get_all()
        for pulse in all_pulses:
            if pulse.get('author_name', '') in authors:
                pulse_list.append(pulse.get('id'))

        return self.remove_pulses(pulse_list)

    def get_pulse_updates(self):
        """Update the redis with the pulses that must been re-added and deleted.
        Returns:
            tuple: Number of pulses updated and deleted.
        """
        total_add = 0
        total_del = 0
        subscribed_timestamp = self.get_latest_request('subscribed')
        events_timestamp = self.get_latest_request('events')

        # If it is the first time we download the pulses we don't execute this call.
        if subscribed_timestamp is not None:
            # Getting event time or subscribed time in case event time is null by any reason.
            events_timestamp = subscribed_timestamp if events_timestamp is None else events_timestamp
            next_request = "{}/pulses/events?limit=20&since={}".format(self.url_base, events_timestamp)
        else:
            return total_add, total_del

        event = {}
        while next_request:
            try:
                json_data = self.make_request(next_request)
                # We need to apply the action in each iteration to keep the order of each modification.
                for event in json_data.get('results'):
                    e_type = event.get('object_type')
                    e_action = event.get('action')
                    e_id = event.get('object_id')
                    # Authors to delete
                    if e_type == 'user' and e_action in ['unsubscribe', 'delete']:
                        total_del += self.remove_pulses_from_authors([e_id])
                    # Authors to subscribe
                    elif e_type == 'user' and e_action == 'subscribe':
                        total_add += self.add_pulses_from_authors([e_id])
                    # Pulses to delete
                    elif e_type == 'pulse' and e_action in ['unsubscribe', 'delete']:
                        total_del += self.remove_pulses([e_id])
                    # Pulses to add
                    elif e_type == 'pulse' and e_action == 'subscribe':
                        total_add += self.add_pulses_from_list([e_id])

                # Get next request
                next_request = json_data.get('next')

            except Exception as err:
                api_log.warning("Cannot download pulse updates: {}".format(err))
                raise

        update_timestamp = event.get('created', None)
        if update_timestamp is not None:
            self.update_latest_request('events', update_timestamp)

        return total_add, total_del

    def get_new_pulses(self):
        """Update the redis with the pulses that must been added.
        Args:
            None
        Returns:
            integer: Number of new pulses downloaded.
        """
        pulse_downloaded = 0
        subscribed_timestamp = self.get_latest_request('subscribed')
        error_on_downloading = False

        if subscribed_timestamp is not None:
            next_request = "{}/pulses/subscribed?limit=20&modified_since={}&sort=modified".format(self.url_base, subscribed_timestamp)
        else:
            next_request = "{}/pulses/subscribed?limit=20&sort=modified".format(self.url_base)

        start_timer = datetime.datetime.now()

        # This var will store the date of the newest pulse that will be used to query the next time.
        update_timestamp = None
        while next_request:
            try:
                json_data = self.make_request(next_request)
                p_data = json_data.get('results', [])
                # First we remove the pulse to avoid IOC inconsistency problems.
                self.remove_pulses([p.get('id', '') for p in p_data])
                # Save pulse data on redis
                pulse_downloaded += self.save_pulses(p_data)
                # Save the newest pulse date
                try:
                    # We save the last pulse modified date.
                    update_timestamp = p_data[len(p_data)-1]['modified']
                except:
                    pass
                # Get next request
                next_request = json_data.get('next')
                update_timer = datetime.datetime.now()
                if (update_timer - start_timer).seconds > THRESHOLD_TIME_OTX:
                    break
            except Exception as err:
                api_log.warning("Error downloading pulses from: {}".format(next_request) + "\nCannot download new pulses: {}".format(err))
                error_on_downloading = True
                break

        # Saving the request date
        if update_timestamp is not None:
            self.update_latest_request('subscribed', update_timestamp)
        # If it is the first time we download the pulses, we update the event request time to the current UTC timestamp.
        if subscribed_timestamp is None:
            self.update_latest_request('events')

        if pulse_downloaded > 0 or (error_on_downloading is False and pulse_downloaded == 0):
            db_set_config("open_threat_exchange_latest_update", datetime.datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S"))

        return error_on_downloading, pulse_downloaded

    def download_pulses(self):
        """Retrieves all the pulses information, both new and deleted
        Args:
            None
        Returns:
            result(dict): number of new pulses downloaded, updated and deleted pulses.
        """
        try:
            p_update, p_delete = self.get_pulse_updates()
            error_on_downloading, p_new = self.get_new_pulses()
            self.pulse_correlation_db.sync()
            if error_on_downloading:
                raise Exception
        except Exception:
            raise Exception("Error downloading pulses")

        return {'new_pulses': p_new, 'updated_pulses': p_update, 'deleted_pulses': p_delete}

    @staticmethod
    def get_otx_user_version():
        """ Returns string like 'OTX USM/5.2.4' if able to get system version or '' otherwise
        """
        otx_user_version = ''
        try:
            data_retrieved, version_data = get_alienvault_version()
            if data_retrieved:
                # we need only product name and version number, so get first 2
                version_data = version_data.replace('ALIENVAULT', 'USM').split()[:2]
                otx_user_version = 'OTX {}'.format('/'.join(version_data))
            else:
                api_log.warning('Bad result returned for alienvault version: {}'.format(version_data))
        except Exception as err:
            api_log.warning('Failed to get alienvault version. Reason: {}'.format(err))

        return otx_user_version
