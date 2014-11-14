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

import json
from datetime import datetime
import celery.utils.log


from apimethods.utils import get_bytes_from_uuid
from db.methods.api import remove_monitor_data_by_id, save_monitor_data, \
    add_monitor_data_objects, save_current_status_message
from db.models.alienvault_api import Monitor_Data


#from api.lib.log_action import log_action


logger = celery.utils.log.get_logger("celery")


class ComponentTypes(object):
    NET = 'net'
    HOST = 'host'
    USER = 'user'
    SENSOR = 'sensor'
    SERVER = 'server'
    SYSTEM = 'system'


class MonitorTypes(object):

    MONITOR_DROPPED_PACKAGES = 1
    MONITOR_CPU_LOAD = 2
    MONITOR_DISK_SPACE = 3
    MONITOR_ASSET_LOG_ACTIVITY = 4
    MONITOR_SENSOR_IDS_ENABLED = 5  # NOT USED YET
    MONITOR_SENSOR_LOCATION = 6  # Not used yet
    MONITOR_SENSOR_VULNERABILITY_SCANS = 7  # Not used yet
    MONITOR_SYSTEM_DNS = 8
    MONITOR_REMOTE_CERTIFICATES = 9
    MONITOR_GET_REMOTE_SYSTEM_INFO = 10
    MONITOR_PENDING_UPDATES = 11
    CHECK_TRIGGERS = 1500


class Monitor(object):
    '''
    This class represent a monitor object. This object will do a task, and it could generate messages and save its
    data on the alienvault_api.monitor_data database.
    '''

    def __init__(self, monitor_id):
        '''
        Constructor
        '''
        self.monitor_id = monitor_id
        self.monitor_objects = []

    def start(self):
        #do the job. Overwrite in the child
        pass

    def remove_monitor_data(self):
        """Removes all the monitor data before running it again"""
        return remove_monitor_data_by_id(self.monitor_id)

    def save_data(self, component_id, component_type, data):
        """
        Save the monitor data.
        :param component_id: The component id - uuid canonical string
        :param component_type: Component type (see Component Types)
        :param data: The monitor json data.
        :return: True is successful, False otherwise
        """
        return save_monitor_data(self.monitor_id, component_id, component_type, data)

    def append_monitor_object(self, component_id, component_type, data):
        """
        Save the monitor data.
        :param component_id: The component id - uuid canonical string
        :param component_type: Component type (see Component Types)
        :param data: The monitor json data.
        :return: True is successful, False otherwise
        """
        return_value = True
        try:
            monitor_data = Monitor_Data()
            monitor_data.component_id = get_bytes_from_uuid(component_id)
            monitor_data.timestamp = datetime.now()
            monitor_data.monitor_id = self.monitor_id
            monitor_data.data = data
            monitor_data.component_type = component_type
            #db.session.add(monitor_data)
            self.monitor_objects.append(monitor_data)
        except Exception:
            return_value = False
        return return_value

    def commit_data(self):
        """Commit data"""
        return add_monitor_data_objects(self.monitor_objects)

    def get_message(self):
        """
        :return:
        """
        return self.message

    def get_json_message(self, message_fields={}):
        """
        Builds the JSON monitor message and returns it.
        :param extra_fields: A dict containing the extra values to insert inside the message
        :return: An json string
        """
        return json.dumps(message_fields)

    def get_monitor_id(self):
        return self.monitor_id

    def get_monitor_data(self):
        return self.monitor_data

    def save_message(self, component_id, message_code, level, data):
        """
        Save the monitor data.
        :param component_id: The component id - uuid canonical string
        :param message_code: Message type
        :param level The message level.
        :param data: The monitor json data.
        :return: True is successful, False otherwise
        """
        return save_current_status_message(component_id, 'system', message_code, data)
