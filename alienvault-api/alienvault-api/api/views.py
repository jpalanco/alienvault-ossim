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
"""
    Views config file
"""

from api import app

# auth
from api.blueprints.auth import auth
# system
from api.blueprints.system import system
from api.blueprints.system import network
# sensor
from api.blueprints.sensor import sensor
from api.blueprints.sensor import ossec_win_deploy
from api.blueprints.sensor import iftraffic
from api.blueprints.sensor import interfaces
from api.blueprints.sensor import ossec_agent
from api.blueprints.sensor import detector
from api.blueprints.sensor import ossec
from api.blueprints.sensor import ntop
from api.blueprints.sensor import ossec_configuration
# server
from api.blueprints.server import server
# data
from api.blueprints.data import status
from api.blueprints.data import host
# job
from api.blueprints.job import common
from api.blueprints.job import reconfig
# Apps
from api.blueprints.system import backup
from api.blueprints.system import doctor
from api.blueprints.system import email
from api.blueprints.system import license as avlicense
from api.blueprints.system import config
from api.blueprints.system import status as system_status

# Attach blueprints.

API_VERSION = "1.0"
API_URL_BEGIN = "/av/api/%s/" % API_VERSION

# auth
app.register_blueprint(auth.blueprint, url_prefix=API_URL_BEGIN + 'auth')

# sensor
app.register_blueprint(sensor.blueprint, url_prefix=API_URL_BEGIN + 'sensor')
app.register_blueprint(interfaces.blueprint, url_prefix=API_URL_BEGIN + 'sensor')
app.register_blueprint(ossec_win_deploy.blueprint, url_prefix=API_URL_BEGIN + 'sensor')
app.register_blueprint(iftraffic.blueprint, url_prefix=API_URL_BEGIN + 'sensor')
app.register_blueprint(detector.blueprint, url_prefix=API_URL_BEGIN + 'sensor')
app.register_blueprint(ossec_agent.blueprint, url_prefix=API_URL_BEGIN + 'sensor')
app.register_blueprint(ossec.blueprint, url_prefix=API_URL_BEGIN + 'sensor')
app.register_blueprint(ossec_configuration.blueprint, url_prefix=API_URL_BEGIN + 'sensor')
app.register_blueprint(ntop.blueprint, url_prefix=API_URL_BEGIN + 'sensor')

# server
app.register_blueprint(server.blueprint, url_prefix=API_URL_BEGIN + 'server')

# system
app.register_blueprint(system.blueprint, url_prefix=API_URL_BEGIN + 'system')
app.register_blueprint(backup.blueprint, url_prefix=API_URL_BEGIN + 'system')
app.register_blueprint(doctor.blueprint, url_prefix=API_URL_BEGIN + 'system')
app.register_blueprint(email.blueprint, url_prefix=API_URL_BEGIN + 'system')
app.register_blueprint(network.blueprint, url_prefix=API_URL_BEGIN + 'system')
app.register_blueprint(avlicense.blueprint, url_prefix=API_URL_BEGIN + 'system')
app.register_blueprint(config.blueprint, url_prefix=API_URL_BEGIN + 'system')

# facts
app.register_blueprint(system_status.blueprint, url_prefix=API_URL_BEGIN + 'system')

# data
app.register_blueprint(status.blueprint, url_prefix=API_URL_BEGIN + 'data/status')
app.register_blueprint(host.blueprint, url_prefix=API_URL_BEGIN + 'data/host')

# jobs
app.register_blueprint(reconfig.blueprint, url_prefix=API_URL_BEGIN + 'job')
app.register_blueprint(common.blueprint, url_prefix=API_URL_BEGIN + 'job')
