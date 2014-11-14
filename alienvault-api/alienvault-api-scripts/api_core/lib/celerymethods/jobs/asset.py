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

from celerymethods.tasks import celery_instance
from celery import current_task

import db
from db.models.alienvault_api import Current_Status

@celery_instance.task
def asset_clean_orphan_status_messages():
    task_id = current_task.request.id
    try:
        query = "delete alienvault_api.current_status.* from alienvault_api.current_status left join alienvault.host on component_id=id where component_type='host' and id is null"
        data = db.session.connection(mapper=Current_Status).execute(query)
    except Exception as e:
        db.session.rollback()
        return (False, "A error detected while deleting orphan assets: " + str(e))

    return (True, task_id)
