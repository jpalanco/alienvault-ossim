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
import sys

from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler

CONFIG_FILE = "/etc/ossim/ossim_setup.conf"
ossim_setup = AVOssimSetupConfigHandler(CONFIG_FILE)

class Config(object):
    DIR = os.path.abspath(os.path.dirname(__file__))
    # Path to our database
    SQLALCHEMY_DATABASE_URI = "mysql://%s:%s@%s/alienvault" % (ossim_setup.get_database_user(),
                                                                                           ossim_setup.get_database_pass(),
                                                                                           ossim_setup.get_database_db_ip())

    SQLALCHEMY_BINDS = {
        "status_message": "mysql://%s:%s@%s/alienvault_api" % (ossim_setup.get_database_user(),
                                                               ossim_setup.get_database_pass(),
                                                               ossim_setup.get_database_db_ip()),
        "status_message_action": "mysql://%s:%s@%s/alienvault_api" % (ossim_setup.get_database_user(),
                                                                      ossim_setup.get_database_pass(),
                                                                      ossim_setup.get_database_db_ip()),
        "status_action": "mysql://%s:%s@%s/alienvault_api" % (ossim_setup.get_database_user(),
                                                              ossim_setup.get_database_pass(),
                                                              ossim_setup.get_database_db_ip()),
        "current_status": "mysql://%s:%s@%s/alienvault_api" % (ossim_setup.get_database_user(),
                                                               ossim_setup.get_database_pass(),
                                                               ossim_setup.get_database_db_ip()),
        "logged_actions" : "mysql://%s:%s@%s/alienvault_api" % (ossim_setup.get_database_user(),
                                                                ossim_setup.get_database_pass(),
                                                                ossim_setup.get_database_db_ip()),
        "monitor_data" : "mysql://%s:%s@%s/alienvault_api" % (ossim_setup.get_database_user(),
                                                              ossim_setup.get_database_pass(),
                                                              ossim_setup.get_database_db_ip()),
        "celery_job" : "mysql://%s:%s@%s/alienvault_api" % (ossim_setup.get_database_user(),
                                                            ossim_setup.get_database_pass(),
                                                            ossim_setup.get_database_db_ip()),
        "acid_event": "mysql://%s:%s@%s/alienvault_siem" % (ossim_setup.get_database_user(),
                                                            ossim_setup.get_database_pass(),
                                                            ossim_setup.get_database_db_ip()),
        "device": "mysql://%s:%s@%s/alienvault_siem" % (ossim_setup.get_database_user(),
                                                        ossim_setup.get_database_pass(),
                                                        ossim_setup.get_database_db_ip()),
        "alienvault_host":"mysql://%s:%s@%s/alienvault" % (ossim_setup.get_database_user(),
                                                           ossim_setup.get_database_pass(),
                                                           ossim_setup.get_database_db_ip()),
                        }
    # Folder where we will store the SQLAlchemy-migrate data files
    SQLALCHEMY_MIGRATE_REPO = os.path.join(DIR, 'db_repository')

class ProductionConfig(Config):
    pass
class DevelConfig(Config):
    pass
