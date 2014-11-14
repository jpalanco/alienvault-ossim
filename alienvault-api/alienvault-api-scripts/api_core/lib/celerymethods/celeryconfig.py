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
from datetime import timedelta
from celery.schedules import crontab
#TODO: Create user on rabbitmq server and use it
BROKER_URL = 'amqp://guest@127.0.0.1:5672//'
CELERY_RESULT_BACKEND = 'amqp'
CELERY_IMPORTS = (
                  "celerymethods.tasks.monitor_tasks",
                  "celerymethods.tasks.backup_tasks",
                  "celerymethods.tasks.system_tasks",
                  "celerymethods.tasks.celery_tasks",
                  "celerymethods.jobs.reconfig",
                  "celerymethods.jobs.system",
                  "celerymethods.tasks.maintenance"
                )
CELERYBEAT_LOG_LEVEL = "DEBUG"
CELERY_TASK_SERIALIZER = 'json'
CELERY_RESULT_SERIALIZER = 'json'
CELERY_SEND_EVENTS = True
CELERY_SEND_TASK_SENT_EVENT = True
#TODO: Read this from  ossim_setup.conf
CELERY_TIMEZONE = 'Europe/Madrid'
CELERY_ENABLE_UTC = True
PERIOD_1_DAY = 86400
PERIOD_1_HOUR = 3600
PERIOD_30_MINS = 1800
PERIOD_10_MINS = 600
PERIOD_15_MINS = 900
PERIOD_5_MINS = 300
PERIOD_2_MINS = 120
PERIOD_1_MINS = 60
PERIOD_30_SECONDS = 30
PERIOD_10_SECONDS = 10

CELERYBEAT_SCHEDULE = {
     'check_ansible_components': {
         'task': 'celerymethods.tasks.system_tasks.check_ansible_components',
         'schedule': timedelta(seconds=PERIOD_5_MINS),
         'args': [],
     },
     'run_triggers': {
         'task': 'celerymethods.tasks.system_tasks.run_triggers',
         'schedule': timedelta(seconds=PERIOD_5_MINS),
         'args': [],
     },
     'configuration_backup': {
         'task': 'celerymethods.tasks.backup_tasks.backup_configuration',
         'schedule':crontab(hour=7, minute=00), #Every day at 7:00h
         'args': [],
     },
     'environment_backup': {
         'task': 'celerymethods.tasks.backup_tasks.backup_environment',
         'schedule':crontab(hour=7, minute=00), #Every day at 7:00h
         'args': [],
     },
     'celery_maintenance': {
         'task': 'celerymethods.tasks.celery_tasks.cleanup_db_celery_jobs',
         'schedule':crontab(hour=6, minute=00), #Every day at 6:00h
         'args': [],
     },
    'backup_maintenance': {
        'task': 'celerymethods.tasks.maintenance.remove_old_database_files',
        'schedule': timedelta(seconds=PERIOD_1_HOUR),
        'args': [],
    },
    'system_cpu_load': {
         'task': 'celerymethods.tasks.monitor_tasks.monitor_system_cpu_load',
         'schedule': timedelta(seconds=PERIOD_15_MINS),
         'args' : [],
     },
     'sensor_get_dropped_packages': {
          'task': 'celerymethods.tasks.monitor_tasks.monitor_sensor_dropped_packages',
          'schedule': timedelta(seconds=PERIOD_5_MINS),
          'args': [],
     },
     'asset_log_activity': {
          'task': 'celerymethods.tasks.monitor_tasks.monitor_asset_log_activity',
          'schedule': timedelta(seconds=PERIOD_1_HOUR),
          'args': [],
     },
     'monitor_system_disk_usage': {
          'task': 'celerymethods.tasks.monitor_tasks.monitor_system_disk_usage',
          'schedule': timedelta(seconds=PERIOD_15_MINS),
          'args': [],
     },
     'monitor_system_dns': {
          'task': 'celerymethods.tasks.monitor_tasks.monitor_system_dns',
          'schedule': timedelta(seconds=PERIOD_15_MINS),
          'args': [],
     },
     'monitor_remote_certificates': {
          'task': 'celerymethods.tasks.monitor_tasks.monitor_remote_certificates',
          'schedule': timedelta(seconds=PERIOD_15_MINS),
          'args': [],
     },
     'monitor_retrieves_remote_info': {
          'task': 'celerymethods.tasks.monitor_tasks.monitor_retrieves_remote_info',
          'schedule': timedelta(seconds=PERIOD_5_MINS),
          'args': [],
     },
     'sync_databases': {
          'task': 'celerymethods.tasks.system_tasks.sync_databases',
          'schedule': timedelta(seconds=PERIOD_2_MINS),
          'args': [],
     },
     'monitor_check_pending_updates': {
          'task': 'celerymethods.tasks.monitor_tasks.monitor_check_pending_updates',
          'schedule': timedelta(seconds=PERIOD_1_DAY),
          'args': [],
     },
}
