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
from flask.ext.login import login_required, current_user
from functools import wraps
import uuid
import re

from api.lib.common import make_bad_request,make_error
valid_user_regex = re.compile("[0-9a-zA-Z_\-\.]+", re.UNICODE)
# The password is a latin-1 string. When we use python3 string, this would fail
oss_lower="áéíóúýàèìòùäëïöüÿâêîôûãñõ¨åæç½ðøþß".decode('utf-8').encode('latin-1')
oss_upper="'ÁÉÍÓÚÝÀÈÌÒÙÄËÏÖÜ¾ÂÊÎÔÛÃÑÕÅÆÇ¼ÐØÞ".decode('utf-8').encode('latin-1')
valid_password_regex = re.compile("[A-Za-z0-9`~!@#$%^&*\(\)_\-+=\{\}\[\]\\|:;\"'<>,\.\?/ºª\s" + oss_lower + oss_upper+chr(160)+"]+")
valid_canonical_uuid = re.compile("[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}")

#     return Pagination(query, page, per_page, total, items)


def accepted_url(url={}, func=None):
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
    if func is not None:

        func._url = url

        @wraps(func)
        def check_accepts(*args, **kwargs):
            url_params = dict(kwargs, **request.args.to_dict())
            if request.method == "POST":
                #We need to load also the url params or they won't be validated 
                #url_params = {} 
                for key in request.form.keys():
                    url_params[key] = request.form[key]

            url_constraints = url
            invalid_params = [x for x in url_params.keys() if x not in url_constraints.keys()]
            if invalid_params != []:
                return make_bad_request("URL contains parameters not included in the constraint")
            for key, item in url_constraints.items():
                try:
                    # Composite check, with 'values', 'type', and/or 'optional' parameters.
                    if type(item) == dict:
                        obj = item['type'](url_params[key])
                        del obj

                    # Simple check, only with 'type'
                    elif type(item) == type:
                        if item == str:
                            if type(url_params[key]) != str and type(url_params[key]) != unicode:
                                return make_bad_request("URL parameter %s does not meet the simple 'type' constraint" % str(key)[:20])
                        elif item == uuid.UUID:
                            try:
                                u = uuid.UUID(url_params[key])
                                #The uuid should be in its canonical form XXXXXXXX-YYYY-ZZZZ-DDDD-HHHHHHHHHHHH
                                #>>> import uuid
                                #>>> print uuid.UUID("8219d0ff--cf8-0-11-e3-b-84d-000c29764e58")
                                #8219d0ff-cf80-11e3-b84d-000c29764e58
                                if valid_canonical_uuid.match(url_params[key]) is None:
                                    return make_bad_request("URL parameter %s does not meet the simple 'type' constraint" % str(key)[:20])
                            except ValueError:
                                return  make_bad_request("URL parameter %s does not meet the simple 'type' constraint" % str(key)[:20])
                        elif type(url_params[key]) != item:
                            return make_bad_request("URL parameter %s does not meet the simple 'type' constraint" % str(key)[:20])
                    else:
                        # Default case
                        return make_bad_request("Invalid type for the item")
                except (TypeError, ValueError), e:
                    if hasattr(item, '__iter__'):
                        # If the 'type' check fails, try for defined values.
                        if ('values' in item and item['values'] == []) or \
                           ('values' in item and url_params[key] not in item['values']) or \
                           ('values' not in item):
                            return make_bad_request("URL parameter %s does not meet the 'type' constraint" % str(key)[:20])

                except KeyError, e:
                    # Only check the values / optional params if the items is iterable
                    if hasattr(item, '__iter__'):
                        # Ignore the parameter if 'optional' is set to 'True' or is not setted at all.
                        if ('optional' in item and item['optional'] != True and key not in url_params) or \
                           ('optional' not in item and key not in url_params):
                            return make_bad_request("URL parameter %s does not meet the 'optional' constraint" % str(key)[:20])
            return func(*args, **kwargs)

        return check_accepts

    else:
        def partial_check(func):
            return accepted_url(url, func)
        return partial_check


def is_admin_user():
    return current_user.is_admin == 1 or current_user.login == 'admin'


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
