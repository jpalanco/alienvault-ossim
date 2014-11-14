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

from api import secret
from api.config import Config
from api.lib.common import use_pretty_default_error_handlers
from sqlalchemy import event
from i18n import AlienvaultMessageHandler, AlienvaultApps

# TEMPORAL FIX UNTIL WE DECIDE WHAT TO DO WITH LOGS

import logging
import logging.handlers
from api.api_i18n import messages as i18nmsgs

LOG_FILENAME = '/var/log/alienvault/api/api.log'
API_LOGGER = logging.getLogger('api-logger')

handler = logging.handlers.RotatingFileHandler(LOG_FILENAME, maxBytes=10000, backupCount=1)
# END TEMPORAL FIX

#handler = RotatingApiFileHandler('/var/log/alienvault/api/api.log', maxBytes=10000, backupCount=1)

app = Flask(__name__)
app.config.from_object('api.config.Config')
app.logger.addHandler(handler)
app.secret_key = secret.key

# SetUP the library
API_i18n = AlienvaultMessageHandler
(success, msg) = API_i18n.setup("alienvault_api", os.path.dirname(os.path.abspath(__file__)) + "/api_i18n/locales/",
                                AlienvaultApps.API, i18nmsgs.api_messages)
if not success:
    app.logger.warning("Error handler can't be installed: %s" % (msg))



db=SQLAlchemy(app, session_options={'autocommit': True})

login_manager = LoginManager(app)
principals = Principal(app)

# Use nice error handlers for all common HTTP codes.
use_pretty_default_error_handlers(app)

from celerymethods.celery_manager import CeleryManager
cm = CeleryManager()
cm.start()

# This is the recommended way of packaging a Flask app.
# This seems to be a hack to avoid circulat imports.
# See http://flask.pocoo.org/docs/patterns/packages/
import api.views

# (Keep pyflakes quiet)
api.views

login_manager.login_view = "auth.login"
