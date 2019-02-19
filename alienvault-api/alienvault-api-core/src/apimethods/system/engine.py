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
"""
    Update the RRD information for each context
"""
import os.path
import os
import rrdtool
import time
from db.methods.entities import get_contexts_stats, get_rrdpath_config
# import api_log


def update_rrd_simple(rrdfile, count):
    """
        Refactor the old update_rrd_simple
    """
    timestamp = int(time.time())
    # Create the rrdfile if no exists
    if not os.path.exists(rrdfile):
        rrdtool.create(rrdfile,
                       '-b', str(timestamp), '-s300',
                       'DS:ds0:GAUGE:600:0:1000000',
                       'RRA:AVERAGE:0.5:1:800',
                       'RRA:AVERAGE:0.5:6:800',
                       'RRA:AVERAGE:0.5:24:800',
                       'RRA:AVERAGE:0.5:288:800',
                       'RRA:MAX:0.5:1:800',
                       'RRA:MAX:0.5:6:800',
                       'RRA:MAX:0.5:24:800',
                       'RRA:MAX:0.5:288:800')
        os.chmod(rrdfile, 0644)
    # Update the rrdfile info
    else:
        rrdtool.update(rrdfile, str(timestamp) + ":" + str(count))
        os.chmod(rrdfile, 0644)


def update_engine_stats():
    """
        Update the engine stats
    """
    success, rrdpath = get_rrdpath_config()
    # Create the rrdpath directory if not exists
    if not success:
        return False,rrdpath
    if not os.path.isdir(rrdpath):
        os.makedirs(rrdpath, 0755)
    success, ctxs = get_contexts_stats()
    if not success:
        return False, ctxs
    for ctx_id, stats in ctxs.items():
        filename = os.path.join(rrdpath, ctx_id + ".rrd")
        # Update the rrd
        update_rrd_simple(filename, stats)
    return True, ''
