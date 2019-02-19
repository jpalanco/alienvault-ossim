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

import hashlib

from functools import wraps, partial
from redis_cache import SimpleCache
from redis_cache import ExpiredKeyException, CacheMissException

import api_log


def _get_cache(namespace=None, expire=600):
    """
    Get the simple cache object

    Args:
      namespace (str, optional): namespace for the cache
      expire (int, optional): expiration time of cache keys in seconds,
        defaults to 600 (10 minutes)
    """
    if namespace is None:
        namespace = "AlienvaultAPI"

    cache = SimpleCache(limit=1000,
                        expire=expire,
                        hashkeys=True,
                        namespace=namespace)
    return cache


def flush_cache(namespace=None):
    """
    Flush all the cached data of namespace
    """
    cache = _get_cache(namespace)
    cache.flush()


def use_cache(function=None, namespace=None, expire=600):
    """
    Apply this decorator to cache any pure function returning a value. Any function
    with side-effects should be wrapped.

    Arguments and function result must be able to convert to json.
    'no_cache=True' in the function call for avoid matching the cache.
    This can be used to force a cache refresh for a key/value pair.

    Args:
      function : original function decorated
      namespace (str, optional): namespace for the cache
      expire (int, optional): expiration time of cache keys, defaults to 600
    """
    if function is None:
        return partial(use_cache, namespace=namespace, expire=expire)

    @wraps(function)
    def cache_func(*args, **kwargs):
        cache = _get_cache(namespace=namespace, expire=expire)

        # Handle cases where caching is down or otherwise not available.
        if cache.connection is None:
            return function(*args, **kwargs)

        # Clean kwargs. Remove no_cache and convert str to unicode
        key_kwargs = kwargs.copy()
        if 'no_cache' in kwargs:
            key_kwargs.pop('no_cache')
        for key in key_kwargs:
            if isinstance(key_kwargs[key], str):
                try:
                    key_kwargs[key] = unicode(key_kwargs[key])
                except Exception as e:
                    api_log.warning("use_cache: %s" % str(e))
        # Clean args. convert str to unicode
        key_args = []
        for arg in args:
            if isinstance(arg, str):
                unicode_arg = arg
                try:
                    unicode_arg = unicode(arg)
                except Exception as e:
                    api_log.warning("use_cache: %s" % str(e))
                key_args.append(unicode_arg)
            else:
                key_args.append(arg)

        # Use funtion name, args and kwargs for cache key
        cache_key = hashlib.sha256(str(function.__name__) + str(key_args) + str(key_kwargs)).hexdigest()
        if 'no_cache' not in kwargs or not kwargs['no_cache']:
            try:
                # Consider using pickle instead of json
                # json can't store tuples
                return cache.get_json(cache_key)
            except (ExpiredKeyException, CacheMissException):
                pass
            except Exception, msg:
                api_log.error(str(msg))

        result = function(*args, **kwargs)
        try:
            cache.store_json(cache_key, result)
        except Exception, msg:
            api_log.error(str(msg))

        return result
    return cache_func
