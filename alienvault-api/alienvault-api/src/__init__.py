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
from flask import Flask
from flask.ext.sqlalchemy import SQLAlchemy
from flask.ext.login import LoginManager
from flask.ext.principal import Principal

from .lib.common import use_pretty_default_error_handlers
from i18n import AlienvaultMessageHandler, AlienvaultApps

import logging
from logging import Formatter
import logging.handlers
from api.api_i18n import messages as i18nmsgs

LOG_FILENAME = '/var/log/alienvault/api/api.log'
API_LOGGER = logging.getLogger('api-logger')
handler = logging.handlers.RotatingFileHandler(LOG_FILENAME,
                                               maxBytes=10000,
                                               backupCount=1)

handler.setFormatter(Formatter('%(asctime)s alienvault-api [%(levelname)s]: %(message)s'))

app = Flask(__name__)
app.config.from_object('api.config.Config')
app.logger.addHandler(handler)

try:
    from api import secret
    app.secret_key = secret.key
except ImportError:
    app.logger.warning("Error importing secret key. Using a randomly generated key")
    import random
    import string
    app.secret_key = ''.join(random.SystemRandom().choice(string.ascii_lowercase + string.digits) for _ in range(24))


# SetUP the library
API_i18n = AlienvaultMessageHandler
locales_dir = os.path.dirname(os.path.abspath(__file__)) + "/api_i18n/locales/"
(success, msg) = API_i18n.setup("alienvault_api",
                                locales_dir,
                                AlienvaultApps.API, i18nmsgs.api_messages)
if not success:
    app.logger.warning("Error handler can't be installed: %s" % (msg))

db = SQLAlchemy(app, session_options={'autocommit': True})

login_manager = LoginManager(app)
principals = Principal(app)

# Use nice error handlers for all common HTTP codes.
use_pretty_default_error_handlers(app)

from celerymethods.celery_manager import CeleryManager
cm = CeleryManager()
cm.start()

try:
    from celerymethods.tasks.tasks import Scheduler
    scheduler = Scheduler()
    scheduler.restore_tasks_to_db()
except Exception as e:
    app.logger.error("Error loading tasks to scheduler: '{0}'".format(str(e)))

from apimethods.system.cache import flush_cache
try:
    flush_cache(namespace='system_packages')
except Exception, msg:
    app.logger.warning("Error flushing system_packages namespace: %s" % (msg))

try:
    from api.lib.monitors.messages import initial_msg_load
    success, data = initial_msg_load()
    if not success:
        app.logger.warning("Messages couldn't be loaded in the database, %s" % str(data))
    else:
        app.logger.info("Messages have been successfully loaded")
except Exception, msg:
    app.logger.warning("Error loading messages in database")

# Log permissions
try:
    if os.path.isdir("/var/log/alienvault/api"):
        for api_logfile in os.listdir("/var/log/alienvault/api"):
            os.chmod("/var/log/alienvault/api/%s" % api_logfile, 0644)
except Exception as e:
    pass

# Purge celery-once references from redis
from celery_once.helpers import queue_once_key
from celery_once.tasks import QueueOnce
from db.methods.system import get_system_id_from_local
system_id=get_system_id_from_local()[1]
args={'system_id' : u'%s' % system_id}
task_name = "celerymethods.tasks.backup_tasks.backup_configuration_for_system_id"
key = queue_once_key(task_name, args, None)
aux = QueueOnce()
aux.clear_lock(key)

# This is the recommended way of packaging a Flask app.
# This seems to be a hack to avoid circulat imports.
# See http://flask.pocoo.org/docs/patterns/packages/
import api.views

# (Keep pyflakes quiet)
views

login_manager.login_view = "auth.login"
