# -*- coding: utf-8 -*-
#
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

import BaseHTTPServer  # For HTTP codes.
from flask import (
    jsonify, request, render_template, redirect, abort, make_response,Response)
import mimeparse
from functools import wraps
from werkzeug.exceptions import default_exceptions, HTTPException
from decorator import decorator

# Table mapping response codes to messages; entries have the
# form {code: (shortmessage, longmessage)}.
# See RFC 2616.
HTTP_CODES = BaseHTTPServer.BaseHTTPRequestHandler.responses


def request_prefers_json_over_html():
    """True if request Accept header indicates preference for json over html."""
    try:
        best_mimetype = mimeparse.best_match(
            ['application/json', 'text/html'], request.headers['Accept'])
        return best_mimetype == 'application/json'
    except:
        # E.g. best_matches raises if mimetype does not even contain a '/'.
        return False


def use_pretty_default_error_handlers(app):
    """Set default error handlers to use lib.make_error.

    With thanks to http://flask.pocoo.org/snippets/15/

    """
    def make_json_error(ex):
        status_code = ex.code if isinstance(ex, HTTPException) else 500
        return make_error('', status_code)

    for code in default_exceptions.iterkeys():
        app.error_handler_spec[None][code] = make_json_error


def make_ok(**kwargs):
    """Make JSON OK message response."""
    if kwargs:
        return jsonify(status='success', data=kwargs)
    else:
        return jsonify(status='success', data=None)


def make_error(message, status_code, additional_headers=None):
    """Return a suitable HTML or JSON error message response."""
    short_message, long_message = HTTP_CODES.get(status_code, ('', ''))
    result = dict(
        status='error',
        message=message,
        status_code=status_code,
        status_short_message=short_message,
        status_long_message=long_message)
    if request_prefers_json_over_html():
        response = jsonify(result)
        response.status_code = status_code
    else:
        response = make_response(
            render_template('error.html', **result), status_code)
    if additional_headers:
        response.headers.extend(additional_headers)
    return response


def make_bad_request(message):
    """Return a JSON error message for bad requests"""
    return make_error(message='Bad request: ' + str(message), status_code=400)


def http_method_dispatcher(cls):
    """Decorate a class so as to dispatch by HTTP method.

    Converts the class into a function containing a single instance of the class
    and the necessary switching code to call the class method corresponding to
    the HTTP method. There is nothing special about the class so long as it
    defines the necessary methods (lower case versions of the HTTP methods e.g.
    "get" and "put") with the arguments as per the routed URL.

    There are two notable differences between this approach and the method views
    described in:

        http://flask.pocoo.org/docs/views/#method-views-for-apis

    First, normal Flask decorators may be used to decorate the result of this
    decorator. Further, normal Flask decorators may also be used on the
    individual class methods.  In other words, we retain the clean declarative
    Flask routing pattern using decorators.

    Second, this class decorator returns a function holding a closure containing
    an instantiation of the class. The class is instantiated once at the time of
    "application setup state". See http://flask.pocoo.org/docs/appcontext/.

    """
    instance = cls()

    def method_dispatcher(*args, **kwargs):
        method_name = request.method.lower()
        try:
            f = getattr(instance, method_name)
        except AttributeError:
            abort(405)
        return f(*args, **kwargs)

    # Name the method_dispatcher function after the class so that it is unique.
    # Otherwise the flask routing tries to attach every route to the same
    # function (Gotcha!).
    method_dispatcher.__name__ = cls.__name__
    # Pass on the docstring and fix the source module to make Sphinx work.
    method_dispatcher.__doc__ = cls.__doc__
    method_dispatcher.__module__ = cls.__module__
    return method_dispatcher


def document_using(doc_url):
    """Decorator to redirect to HTML documentation at given url.

    This is only done for HTTP GET's which express a preference for HTML over
    JSON in the Accept header. Otherwise there is no redirection.

    """
    @decorator
    def intercept_GET(f, *args, **kwargs):
        if request.method == 'GET' and not request_prefers_json_over_html():
            return redirect(doc_url)
        return f(*args, **kwargs)
    return intercept_GET


def check(checker_function):
    """Decorator to abort a call through to a view if given check fails.

    The given checker_function is executed. If the result is non-None then the
    call stack is terminated early with that non-None value. Otherwise the call
    stack continues.

    """
    @decorator
    def check_with_checker_function(f, *args, **kwargs):
        result = checker_function(*args, **kwargs)
        if result is not None:
            return result
        return f(*args, **kwargs)
    return check_with_checker_function


def validate_json(validate_function, default=None):
    """Decorator to validate and marshal the incoming JSON.

    Sets request.json to the value returned from calling validate_function on
    request.json (or default() if request.json is None). If this raises
    an exception then the call stack is terminated early with a
    :func:`make_error` from the contents of the exception stack trace and a 400.

    If request.json is None then default() is used. Note that
      1) default is a callable which must create the default value
         (to avoid accidental re-use of mutables)
      2) the result is still passed through the validate_function
         (to ensure the invariant holds)

    """
    @decorator
    def validate_with_validate_function(f, *args, **kwargs):
        input_json = request.json
        if input_json is None and callable(default):
            input_json = default()
        try:
            request.json = validate_function(input_json)
        except Exception, e:
            return make_error(str(e), 400)
        return f(*args, **kwargs)
    return validate_with_validate_function


def if_content_exists_then_is_json(*args, **kwargs):
    """Return error response if content exists and is declared as not JSON.

    Otherwise return None.

    Intended to be used with :func:`check`.

    """
    if len(request.data.strip()) > 0:
        if 'application/json' not in request.headers['Content-Type']:
            return make_error(
                'API only accepts Content-Type: application/json', 406)
