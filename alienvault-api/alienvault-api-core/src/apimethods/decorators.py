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

import os
from functools import wraps, partial

import api_log


def accepted_types(*types):
    """
    Check for argument types.
    Idea from: http://www.python.org/dev/peps/pep-0318/
    Decorators for functions and methods.
    """
    def check_accepts(f):
        def new_f(*args, **kwds):
            for (a, t) in zip(args, types):
                try:
                    obj = t(a)
                    del obj
                except Exception, e:
                    raise AssertionError("arg %r does not match %s" % (a, str(t)))

            return f(*args, **kwds)
        new_f.func_name = f.func_name
        return new_f
    return check_accepts


def accepted_values(*values):
    """Check for argument values.
    Idea from: http://www.python.org/dev/peps/pep-0318/
    Decorators for functions and methods.
    """
    def check_accepts(f):
        def new_f(*args, **kwds):
            for (a, v) in zip(args, values):
                if (v != []) and (not a in v):
                    raise AssertionError("arg %r does not belong to '%s'" % (a, str(v)))

            return f(*args, **kwds)
        new_f.func_name = f.func_name
        return new_f
    return check_accepts


def require_db(func=None, accept_local=False):
    """
    Check if this profile has access to the DB
    """
    if func is None:
        return partial(require_db, accept_local=accept_local)

    @wraps(func)
    def f(*args, **kwargs):
        local_passed = False
        # Lame way to check for a Server or Database profile
        if not os.path.isfile('/usr/bin/ossim-server') and not os.path.isfile('/usr/bin/mysql'):
            if accept_local:
                local_passed = True
                is_local_valid = ['system_id', 'server_id', 'sensor_id']
                local_params = [x for x in is_local_valid if x in kwargs.keys() and kwargs[x].lower() == 'local'] + [y for y in args if y.lower() == 'local']
                if local_params == []:
                    raise AssertionError("Tried to access the database from a non connected profile")
            else:
                raise AssertionError("Tried to access the database from a non connected profile")

        if not local_passed:
            import db
            db.session = db.Session()
        return func(*args, **kwargs)

    return f
