# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2015 AlienVault
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
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#
"""
    Get info about context stats, used in bussines proccesses
"""
from db.models.alienvault import Config, Acl_Entities_Stats
from apimethods.decorators import require_db
import db

from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from sqlalchemy import text as sqltext


@require_db
def get_contexts_stats():
    """
        Get the context stats. Return a dict
        whick key is the uuid of the context_id
    """
    result = True, {}
    try:
        sql = sqltext("SELECT hex(entity_id) AS ctx_id, stat "
                      "FROM acl_entities_stats WHERE ts BETWEEN "
                      "TIMESTAMPADD(MINUTE, -30, NOW()) AND NOW()")
        data = db.session.connection(mapper=Acl_Entities_Stats).execute(sql)
        for entity, stats in data:
            result[1][entity] = stats
    except NoResultFound:
        pass
    return result


@require_db
def get_rrdpath_config():
    """
        Return the rrdpath config path
    """
    try:
        data = db.session.query(
            Config).filter(Config.conf == 'rrdpath_stats').one()
        result = True, data.serialize['value']
    except NoResultFound:
        result = True, "/var/lib/ossim/rrd/rrdpath_stats"
    except MultipleResultsFound:
        result = False, "Bad configuration in sysmtem"
    return result
