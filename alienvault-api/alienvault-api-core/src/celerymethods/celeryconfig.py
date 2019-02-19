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
##

from kombu import Queue, Exchange

CELERY_REDIS_SCHEDULER_URL = "redis://localhost:6379/1"
CELERY_REDIS_SCHEDULER_KEY_PREFIX = 'av:api:task'
CELERY_REDIS_SCHEDULER_DEFAULT_TASKS_FILE = '/etc/alienvault/api/default_tasks.yml'
CELERY_REDIS_SCHEDULER_CUSTOM_TASKS_FILE = '/etc/alienvault/api/custom_tasks.yml'

BROKER_URL = 'amqp://guest@127.0.0.1:5672//'
CELERY_RESULT_BACKEND = 'amqp'
CELERY_IMPORTS = ("celerymethods.tasks.monitor_tasks",
                  "celerymethods.tasks.backup_tasks",
                  "celerymethods.tasks.system_tasks",
                  "celerymethods.tasks.celery_tasks",
                  "celerymethods.tasks.business_process",
                  "celerymethods.jobs.reconfig",
                  "celerymethods.jobs.system",
                  "celerymethods.jobs.nmap",
                  "celerymethods.tasks.maintenance",
                  "celerymethods.tasks.hids",
                  "celerymethods.tasks.start_up")

CELERY_QUEUES = (
    Queue('default', Exchange('default'), routing_key='default'),
    Queue('sys-maint', Exchange('sys-maint'), routing_key='sys-maint'),
    Queue('scans', Exchange('scans'), routing_key='scans'),
)

CELERY_DEFAULT_QUEUE = 'default'
CELERY_DEFAULT_EXCHANGE = 'default'
CELERY_DEFAULT_ROUTING_KEY = 'default'

CELERY_ROUTES = {
    # -- SCANS QUEUE -- #
    'celerymethods.jobs.nmap.run_nmap_scan': {'queue': 'scans'},
    'celerymethods.jobs.nmap.monitor_nmap_scan': {'queue': 'scans'},
    # -- SYSTEM MAINTENANCE QUEUE -- #
    'celerymethods.tasks.backup_tasks.backup_configuration_all_systems': {'queue': 'sys-maint'},
    'celerymethods.tasks.maintenance.clean_old_loggger_entries': {'queue': 'sys-maint'},
    'celerymethods.tasks.celery_tasks.cleanup_db_celery_jobs': {'queue': 'sys-maint'},
    'celerymethods.tasks.maintenance.remove_old_database_files': {'queue': 'sys-maint'}
}

CELERYBEAT_LOG_LEVEL = "DEBUG"
CELERY_TASK_SERIALIZER = 'json'
CELERY_RESULT_SERIALIZER = 'json'
CELERY_SEND_EVENTS = True
CELERY_SEND_TASK_SENT_EVENT = True
CELERY_ENABLE_UTC = False
PERIOD_1_WEEK = 604800
PERIOD_1_DAY = 86400
PERIOD_4_HOURS = 14400
PERIOD_1_HOUR = 3600
PERIOD_30_MINS = 1800
PERIOD_10_MINS = 600
PERIOD_15_MINS = 900
PERIOD_5_MINS = 300
PERIOD_2_MINS = 120
PERIOD_1_MINS = 60
PERIOD_30_SECONDS = 30
PERIOD_10_SECONDS = 10
