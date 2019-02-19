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
import os
import logging
from datetime import datetime, timedelta

from celery.utils.log import get_logger
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from celerymethods.tasks import celery_instance

from db.methods.system import get_logger_storage_days_life_time, get_server_address_from_config
from ansiblemethods.server.logger import delete_raw_logs

logger = get_logger("celery")
MAX_TRIES = 3  # Maximun number of tries before launch an alarm
TIME_BETWEEN_TRIES = 60  # Time between tries in seconds

notifier = logging.getLogger("loggertask_notifier")
notifier.setLevel(logging.DEBUG)

# add file handler
fh = logging.FileHandler("/var/log/alienvault/api/logger-notifications.log")
fh.setLevel(logging.DEBUG)

# formatter
frmt = logging.Formatter('%(asctime)s [FRAMEWORKD] -- %(levelname)s -- %(message)s', "%Y-%m-%d %H:%M:%S.000000")
fh.setFormatter(frmt)

# add the Handler to the logger
notifier.addHandler(fh)

# touch the file and change its permissions
if not os.path.isfile("/var/log/alienvault/api/logger-notifications.log"):
    open("/var/log/alienvault/api/logger-notifications.log", "a").close()
if oct(os.stat("/var/log/alienvault/api/logger-notifications.log").st_mode & 0777) != '0644':
    os.chmod("/var/log/alienvault/api/logger-notifications.log", 0644)


def clean_logger():
    # First obtain the logger conf from
    return_value = False
    try:
        conf = get_logger_storage_days_life_time()
        if conf > 0:
            d = datetime.utcnow().date() + timedelta(days=-conf)
            args = "end=%s" % datetime.strftime(d, "%Y/%m/%d")
            # Call ansible
            # I need to obtain the IP from the Alienvault_Config
            try:
                server_ip = get_server_address_from_config()
                if server_ip is not None:
                    # Verify the ip
                    (result, msg) = delete_raw_logs(server_ip, end=datetime.strftime(d, "%Y/%m/%d"))
                    return_value = result
                    if not result:
                        notifier.error("Can't delete all logs in %s msg: %s" % (server_ip, str(msg)))
                    else:
                        notifier.debug("Result from delete_raw_logs" + str(msg))
                else:
                    notifier.error("Bad configuration. The server ip address is not a valid ip address")
            except NoResultFound:
                notifier.error("Bad configuration. No server_ip in Alienvault_Config")
            except MultipleResultsFound:
                notifier.error("Bad configurarion. Several servers_ip in Alienvault_Config")
        else:
            notifier.info("Logger clean disabled")
            return_value = True
    except ValueError:
        notifier.error("Bad error in  logger_storage_days_lifetime. Must be a number >=0")
    except NoResultFound:
        notifier.info("Logger window not configured")
    except MultipleResultsFound:
        notifier.error("Multiple entris in Alienvault_Config with key  logger_storage_days_lifetime")

    return return_value
