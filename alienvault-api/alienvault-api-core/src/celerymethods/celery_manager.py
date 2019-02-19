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
import celery
import celery.events
import celery.states
import celery.utils.log
from celery.signals import task_postrun
from celerymethods.utils import is_task_in_celery

logger = celery.utils.log.get_logger("celery")
import api_log
import apimethods.utils
from db.methods.job import get_job_status as db_get_job_status
from db.methods.job import cleanup_jobs,update_job_data

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
        self.celery_connection = celery.current_app.connection()

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
        job_status = db_get_job_status(job_id_bytes)
        if job_status:
            return pickle.loads(job_status.info)
        else:
            # Maybe it's not in the database yet. Let's inspect celery queues.
            job_status = is_task_in_celery(job_id)
            # When the task is stored in the database it is stored with the following formant
            #              {u'retries': 0, 
            #              u'expires': None, 
            #              u'uuid': u'e8e2a4ce-bc09-47f8-9ce8-6d3798acc2eb', 
            #              u'clock': 49, 
            #              u'timestamp': 
            #              1441353908.687852, 
            #              u'args': u"[u'DF08B5C7521111E5A4AE000C295288BF', u'5DADB3A8662F91844C62172498696BB1', u'192.168.2.213', u'crosa', u'alien4ever!', u'bosquewin2008.alienvault.com', u'001']",
            #              u'eta': None, 
            #              u'kwargs': u'{}', 
            #              'type': u'task-received', 
            #              u'hostname': u'w1.VirtualUSMAllInOneLite', 
            #              u'name': u'celerymethods.jobs.ossec_win_deploy.ossec_win_deploy'}
            # And when the task is obtained from the inspect, the task has the following format. 
            # We use the type
            #             {u'hostname': u'w1.VirtualUSMAllInOneLite', 
            #              u'time_start': 1441354117.651205, 
            #              u'name': u'celerymethods.jobs.ossec_win_deploy.ossec_win_deploy', 
            #              u'delivery_info': {u'routing_key': u'celery', u'exchange': u'celery'}, 
            #              u'args': u"[u'DF08B5C7521111E5A4AE000C295288BF', u'5DADB3A8662F91844C62172498696BB1', u'192.168.2.213', u'crosa', u'alien4ever!', u'bosquewin2008.alienvault.com', u'001']",
            #              u'acknowledged': True, 
            #              u'kwargs': u'{}', u'id': u'720d8fdb-9c2a-4be7-8889-5244d8c980df', 
            #              u'worker_pid': 14330}
            if job_status is not None:
                job_status['type'] = "task-received"
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
