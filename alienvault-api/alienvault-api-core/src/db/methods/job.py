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
import datetime
import binascii
import db
from db.models.alienvault_api import Celery_Job
from apimethods.decorators import require_db

import api_log


@require_db
def get_job_status(job_id):
    try:
        job_status = db.session.query(Celery_Job).filter(Celery_Job.id == job_id).first()
    except Exception as e:
        api_log.error("[get_job_status] {0}".format(str(e)))
        job_status = None
    return job_status


@require_db
def cleanup_jobs():
    deleted_rows = 0
    try:
        db.session.begin()
        old_db_jobs = db.session.query(Celery_Job).filter(Celery_Job.last_modified < (datetime.datetime.now() - datetime.timedelta(days=1))).all()

        for job in old_db_jobs:
            db.session.delete(job)
            deleted_rows = deleted_rows + 1
        db.session.commit()
    except Exception as e:
        api_log.error("[cleanup_jobs] {0}".format(str(e)))
        db.session.rollback()

    return deleted_rows


@require_db
def update_job_data(job_id, data):
    try:
        query = "REPLACE INTO alienvault_api.celery_job (id, info) VALUES (0x%s, 0x%s)" % (binascii.hexlify(job_id), binascii.hexlify(data))
        db.session.begin()
        db.session.connection(mapper=Celery_Job).execute(query)
        db.session.commit()
    except Exception as e:
        api_log.error("[update_job_data] {0}".format(str(e)))
        db.session.rollback()
        return False
    return True
