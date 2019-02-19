# -*- coding: utf-8 -*-
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

"""
+---------------------------------------+------------------------------------------------------+
| conf                                  | value                                                |
+---------------------------------------+------------------------------------------------------+
| frameworkd_acidcache                  | 0                                                    |
| frameworkd_address                    | 127.0.0.1                                            |
| frameworkd_alarmgroup                 | 1                                                    |
| frameworkd_alarmincidentgeneration    | 1                                                    |
| frameworkd_backup                     | 1                                                    |
| frameworkd_backup_dir                 | /etc/ossim/framework/backups/                        |
| frameworkd_businessprocesses          | 1                                                    |
| frameworkd_controlpanelrrd            | 1                                                    |
| frameworkd_dir                        | /usr/share/ossim-framework/ossimframework            |
| frameworkd_donagios                   | 1                                                    |
| frameworkd_eventstats                 | 0                                                    |
| frameworkd_keyfile                    | /etc/ossim/framework/db_encryption_key               |
| frameworkd_listener                   | 1                                                    |
| frameworkd_log_dir                    | /var/log/ossim/                                      |
| frameworkd_nfsen_config_dir           | /etc/nfsen/nfsen.conf                                |
| frameworkd_nfsen_monit_config_dir     | /etc/monit/alienvault/nfcapd.monitrc                 |
| frameworkd_notificationfile           | /var/log/ossim/framework-notifications.log           |
| frameworkd_optimizedb                 | 1                                                    |
| frameworkd_port                       | 40003                                                |
| frameworkd_rrd_bin                    | /usr/bin/rrdtool                                     |
| frameworkd_scheduler                  | 1                                                    |
| frameworkd_soc                        | 0                                                    |
+---------------------------------------+------------------------------------------------------+
24 rows in set (0.00 sec)

INSERT INTO config (conf, value) VALUES ('frameworkd_nagios_mkl_period', '30');

"""

VAR_FRAMEWORK_PORT = 'frameworkd_port'
VAR_FRAMEWORK_ADDRESS = 'frameworkd_address'

# From database
VAR_KEY_FILE = 'frameworkd_keyfile'
VAR_KEY = 'frameworkd_aes_key'
VAR_LOG_DIR = 'frameworkd_log_dir'
VAR_RRD_BINARY = 'frameworkd_rrd_bin'
VAR_RRD_TIME_PERIOD = 'frameworkd_rdd_period'
VAR_BUSINESSPROCESSES_PERIOD = 'frameworkd_businessprocesses_period'
VAR_NAGIOS_MKL_PERIOD = 'frameworkd_nagios_mkl_period'
VAR_SCHEDULED_PERIOD = 'frameworkd_scheduled_period'

VAR_BACKUP_PERIOD = 'frameworkd_backup_period'
VAR_BACKUP_MAX_DISKUSAGE = 'frameworkd_maxdiskusage'  # percentage of disk.

VAR_NOTIFYMANAGER_FILE = 'frameworkd_notificationfile'

# NAGIOS
VAR_NAGIOS_SOCK_PATH = 'frameworkd_nagios_sock_path'
VAR_NAGIOS_CFG = 'nagios_cfgs'

VAR_USE_HTTPS = 'frameworkd_usehttps'

# From ossim_setup.conf
VAR_DB_HOST = 'ossim_host'
VAR_DB_SCHEMA = 'ossim_base'
VAR_DB_USER = 'ossim_user'
VAR_DB_PASSWORD = 'ossim_pass'
VAR_ALERT_EMAIL = 'email_alert'
VAR_ALERT_EMAIL_SENDER = 'email_sender'

VAR_BACKUP_DAYS_LIFETIME = 'frameworkd_backup_storage_days_lifetime'
VAR_FREE_SPACE_ALLOWED = 'backup_events_min_free_disk_space'
