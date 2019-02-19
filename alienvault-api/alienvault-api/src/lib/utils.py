#
# -*- coding: latin-1 -*-
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
#
# Note about codification of this file.
# I must assume tahat the password param recieved from the browser is a string
# codec as latin-1 / iso-8859-1. 
# 
from flask import redirect, request, abort
from flask.ext.login import current_user
from functools import wraps, partial
import uuid
import re
import redis

from api import app
from api.lib.common import make_bad_request, make_error
from db.methods.auth import has_admin_users

valid_user_regex = re.compile("[0-9a-zA-Z_\-\.]+", re.UNICODE)
# The password is a latin-1 string. When we use python3 string, this would fail
valid_windows_user_regex = re.compile(r'^[^/\\\[\]:;|=,+*?<>]*$')
oss_lower="áéíóúýàèìòùäëïöüÿâêîôûãñõ¨åæç½ðøþß".decode('utf-8').encode('latin-1')
oss_upper="'ÁÉÍÓÚÝÀÈÌÒÙÄËÏÖÜ¾ÂÊÎÔÛÃÑÕÅÆÇ¼ÐØÞ".decode('utf-8').encode('latin-1')
valid_password_regex = re.compile("[A-Za-z0-9`~!@#$%^&*\(\)_\-+=\{\}\[\]\\|:;\"'<>,\.\?/ºª\s" + oss_lower + oss_upper+chr(160)+"]+")
valid_canonical_uuid = re.compile("[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}")

#     return Pagination(query, page, per_page, total, items)


def accepted_url(url=None, func=None):
    """ Check for url values.
     This can be used in a variety of ways:
      * Check for a parameter type:
        @accepted_url({'du': dict})

      * Check for accepted values for a parameter:
        @accepted_url({'du': ['hast']})

      * Check for both:
        @accepted_url({'du': {'type': hast, 'values': ['mich']}})

      * Ignore if it is optional and is not provided in the url
        @accepted_url({'du': {'type': hast, 'values': ['mich'], 'optional': True}})

     Please note, if you use this decorator, you should write a check for *every* parameter in the URL.
    """

    url = {} if url is None else url

    if func is not None:

        func._url = url

        @wraps(func)
        def check_accepts(*args, **kwargs):
            url_params = dict(kwargs, **request.args.to_dict())
            if request.method == "POST":
                for key in request.form.keys():
                    url_params[key] = request.form[key]

            url_constraints = url
            invalid_params = [x for x in url_params.keys() if x not in url_constraints.keys()]
            if invalid_params != []:
                return make_bad_request("URL contains parameters not included in the constraint")
            for key, item in url_constraints.items():
                try:
                    # Composite check, with 'values', 'type', and/or 'optional' parameters.
                    item_is_dict = type(item) == dict
                    if item_is_dict:
                        if (key not in url_params and 'optional' in item and item['optional'] is True):
                            continue
                        item_type = item.get('type', None)
                    else:
                        item_type = item

                    item_value = url_params[key]
                    # UUID exception. 'local' value allowed
                    if item_type == uuid.UUID and item_value in ['local']:
                        continue

                    if item_type:
                        obj = item_type(item_value)
                        del obj

                    # Check allowed values except for UUID
                    if item_is_dict and item_type != uuid.UUID and \
                       'values' in item and item_value not in item['values']:
                            return make_bad_request("URL parameter %s does not meet the allowed values" % str(key)[:20])

                except (ValueError, TypeError) as e:
                    return make_bad_request("Invalid type %s" % str(key))
                except KeyError as e:
                    return make_bad_request("Missing parameter %s" % str(key))
                except Exception as e:
                    app.logger.error(str(e))
                    return make_bad_request("Paramerter verification failure")

            return func(*args, **kwargs)

        return check_accepts

    else:
        def partial_check(func):
            return accepted_url(url, func)
        return partial_check


def has_permission(func = None):
    if func is None:
        return partial(has_permission)

    @wraps(func)
    def check_permission (*args, **kwargs):
        allowed_check_params = ['host_id', 'host_group_id']
        url_params = kwargs
        if request.method == "POST":
            url_params = dict(url_params, **request.form)

        params_to_check = {}
        params_not_to_check = {}
        for key in url_params.keys():
            if key in allowed_check_params:
                try:
                    splitted = url_params[key].split(',')
                    params_to_check[key] = [uuid.UUID(x).hex for x in splitted]
                except:
                    raise AssertionError("arg '%s' is not an UUID" % url_params[key])
            else:
                params_not_to_check[key] = url_params[key]

        if not params_to_check:
            # No need to check anything.
            return func(*args, **kwargs)

        params_checked = {}
        for key, value in params_to_check.iteritems():
            filtered = filter(lambda x: current_user.is_allowed(x, kind=key), value)
            if filtered:
                params_checked[key] = ','.join(filtered)

        if not params_checked:
            return make_error("User '%s' does not have any permission on the specified assets" % current_user.login, 403)

        params = dict(params_not_to_check, **params_checked)

        return func(*args, **params)
    return check_permission


def is_admin_user():
    if current_user.is_authenticated():
        return current_user.is_admin == 1 or current_user.login == 'admin'
    return False


def is_valid_user(username):
    """Check whether a username is valid or not
    Allowed characters are:  LETTERS, DIGITS, SCORE (_ -) and DOT"""
    if username is None:
        return False
    if username == "":
        return False
    if valid_user_regex.match(username) is not None:
        return True
    return False


def is_valid_windows_user(username):
    """ Check whether a windows username is valid
    Not Allowed characters are: " / \ [ ] : ; | = , + * ? < >
    """
    if username is None:
        return False
    if username == "":
        return False
    if valid_windows_user_regex.match(username) is not None:
        return True
    return False


def is_valid_user_password(password):
    """Check whether a password is valid or not
    Allowed characters are:  define('OSS_PASSWORD', OSS_NOECHARS . OSS_DIGIT . OSS_ALPHA . OSS_PUNC_EXT . OSS_SPACE . '\>\<');
    """

    password = password.encode('latin-1')
    if password is None:
        return False
    if password == "":
        return False
    if valid_password_regex.match(password) is not None:
        return True
    return False


def first_init_admin_access():
    if is_admin_user() or not has_admin_users():
        return True

    return False


def only_one_call_without_caching(function=None, timeout=120):
    """Allow only one function call without caching at the moment (no_cache=True).
    If there is the same function running - force it to use cache.

    Args:
        function(obj): wrapped function.
        timeout(int): time to live for the lock.
    """
    def _dec(run_func):
        """Decorator."""

        def _caller(*args, **kwargs):
            """Caller."""
            lock = None
            try:
                # Perform this action only on function calls that don't use cache
                if kwargs.get('no_cache') and run_func:
                    lock = redis.Redis("localhost").lock(run_func.__name__, timeout=timeout)
                    kwargs['no_cache'] = lock.acquire(blocking=False)
            except (ValueError, AttributeError, redis.exceptions.RedisError):
                kwargs['no_cache'] = False

            finally:
                result = run_func(*args, **kwargs)
                try:
                    lock.release()
                except (AttributeError, ValueError, redis.exceptions.RedisError):
                    pass

            return result

        return _caller

    return _dec(function) if function is not None else _dec
