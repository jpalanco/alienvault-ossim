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

import threading
import time
import datetime
import pickle
import celery.app
import celery.events
import celery.states
import celery.utils.log
from celery.signals import task_postrun

import logging

import apimethods.utils
from db.methods.job import get_job_status,cleanup_jobs,update_job_data

logger = celery.utils.log.get_logger("celery")
logger.setLevel(logging.INFO)

@task_postrun.connect
def close_session(*args, **kwargs):
    # Flask SQLAlchemy will automatically create new sessions for you from
    # a scoped session factory, given that we are maintaining the same app
    # context, this ensures tasks have a fresh session (e.g. session errors
    # won't propagate across tasks)
    #api.db.session.remove()
    pass


class CeleryManager(threading.Thread):
    """
    This class does two things:
     - Updates the status of celery jobs in a mysql database
     - Gets the status of jobs from a mysql database
    """

    def __init__(self):
        """Constructor"""
        threading.Thread.__init__(self)
        self.daemon = True
        self.celery_connection = celery.app.app_or_default(None).connection()

    def run(self):
        while True:
            try:
                def _error_handler(exc, interval):
                    logger.error("Cannot connect to %s: %s. Trying again in %s" %
                                 (conn.as_uri(), exc, str(interval)))
                with self.celery_connection as conn:
                    conn.ensure_connection(_error_handler)
                    recv = celery.events.EventReceiver(conn,
                                                       handlers={"task-sent": CeleryManager.on_event,
                                                                 "task-received": CeleryManager.on_event,
                                                                 "task-started": CeleryManager.on_event,
                                                                 "task-failed": CeleryManager.on_event,
                                                                 "task-retried": CeleryManager.on_event,
                                                                 "task-succeeded": CeleryManager.on_event,
                                                                 "task-revoked": CeleryManager.on_event,
                                                                 })
                    recv.capture(limit=None, timeout=None)
            except (KeyboardInterrupt, SystemExit):
                try:
                    import _thread as thread
                except ImportError:
                    import thread
                thread.exit()
            except Exception, e:
                logger.error("Failed to capture events: '%s'." % str(e))
                logger.info(e, exc_info=True)

    @staticmethod
    def get_job_status(job_id):
        """
        Returns the job status or None if the job doesn't exist
        @param job_id: job id string to check in canonical uuid format
        @return: a celery.states.state object or None
        """
        job_status = None

        job_id_bytes = apimethods.utils.get_bytes_from_uuid(job_id)
        job_status = get_job_status(job_id_bytes)
        if job_status:
            return pickle.loads(job_status.info)
        else:
            return job_status

    @staticmethod
    def cleanup_db_jobs():
        return cleanup_jobs()

    @staticmethod
    def on_event(event):
        job_id = apimethods.utils.get_bytes_from_uuid(event['uuid'])
        data = pickle.dumps(event)
        return update_job_data(job_id,data)


"""
Example:

cm = CeleryManager()
cm.get_job_status("744d2fe2-17e8-44e0-ab59-991a20edc795")
"""
