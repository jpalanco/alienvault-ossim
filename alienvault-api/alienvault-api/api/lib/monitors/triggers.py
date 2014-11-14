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
from __future__ import print_function
import traceback
import yaml
import json
import re
from datetime import datetime
import celery.utils.log

from api.lib.monitors.monitor import Monitor, MonitorTypes
from db.methods.api import save_current_status_message, \
                        purge_current_status_message, \
                        add_current_status_messages, \
                        get_all_monitor_data
from db.models.alienvault_api import Current_Status

logger = celery.utils.log.get_logger("celery")

TRIGGERS_FILE = "/etc/alienvault/api/triggers.yml"


class TriggerCondition(object):
    """Trigger Condition Class"""
    __AVAILABLE_MONITORS = {
        "MONITOR_DROPPED_PACKAGES": 1,
        "MONITOR_CPU_LOAD": 2,
        "MONITOR_DISK_SPACE": 3,
        "MONITOR_ASSET_EVENTS": 4,
        "MONITOR_DNS": 8,
        "MONITOR_REMOTE_CERTIFICATES": 9,
        "MONITOR_PENDING_UPDATES": 11,
        "CHECK_TRIGGERS": 1500,
    }
    def __init__(self):
        """Constructor"""
        self.__message_id = ""
        self.__when = ""
        self.__name = ""
        self.__regex_monitor = re.compile("\$(\w+)\.(\w+)")
        self.__trigger_messages = []

    @property
    def message_id(self):
        return self.__message_id

    @message_id.setter
    def message_id(self, value):
        self.__message_id = value

    @property
    def when(self):
        return self.__when
    @when.setter
    def when(self, value):
        self.__when = value

    @property
    def name(self):
        return self.__name

    @name.setter
    def name(self, value):
        self.__name = value

    def __repr__(self):
        return "<condition msg_id=%s><name>%s</name><when>%s</when></condition>" % (self.message_id, self.name, self.when)

    def trigger_message(self, component_id, component_type, message_code, data=""):
        """
        Creates a current status message and saves it in the database
        :param component_id: The component id - uuid canonical string
        :param message_code: Message type
        :param data: Current Status Message Additional Info
        :return: True is successful, False otherwise
        """
        rt = True
        try:
            rt = save_current_status_message(component_id, component_type, message_code, data)
        except Exception as e:
            rt = False
        return rt

    def append_trigger_message(self, component_id, component_type, message_code, data=""):
        """
        Creates a current status message and saves it in the database
        :param component_id: The component id - uuid canonical string
        :param message_code: Message type
        :param data: Current Status Message Additional Info
        :return: True is successful, False otherwise
        """
        rt = True
        try:
            message_data = Current_Status()
            message_data.component_id = component_id
            message_data.component_type = component_type
            message_data.creation_time = datetime.now()
            message_data.message_id = message_code
            message_data.additional_info = data
            message_data.supressed = 0
            message_data.viewed = 'false'
            self.__trigger_messages.append(message_data)
        except Exception, e:
            rt = False
        return rt

    def commit_data(self):
        """Commit data"""
        return_value = True
        try:
            logger.info("There are %s triggers data objects ...saving them" % len(self.__trigger_messages))
            return_value = add_current_status_messages(self.__trigger_messages)
            del self.__trigger_messages[:]
            self.__trigger_messages = []

        except Exception, e:
            logger.error("Error while committing the info %s" % str(e))
            return_value = False
        return return_value

    def purge_messages(self):
        """Deletes all the messages for this condition"""
        return purge_current_status_message(self.message_id)

    def evaluate(self):
        """Evaluates the condition
        :returns True on success, False otherwise
        """
        # get values to retrieve:
        # $monitor.variable

        search = self.__regex_monitor.findall(self.when)
        eval_conditions = []

        logger.info("Running condition... %s" % self.name)
        self.purge_messages()

        result_set = get_all_monitor_data()
        component_monitors = {}

        for monitor_data in result_set:
            if not component_monitors.has_key(monitor_data.component_id):
                component_monitors[monitor_data.component_id] = {}

            if not component_monitors[monitor_data.component_id].has_key(monitor_data.monitor_id):
                component_monitors[monitor_data.component_id][monitor_data.monitor_id] = json.loads(monitor_data.data)

        logger.info("Let's start working... %s" % self.name)
        #For each component evaluates the condition
        for component_id, monitors in component_monitors.iteritems():
            replacements = {}
            for m in search:
                if len(m) < 2:
                    continue
                #m[0] = monitor name
                #m[1] = param_name
                monitor_name = m[0]
                param_name = m[1]
                if monitor_name in self.__AVAILABLE_MONITORS.keys():
                    monitor_id = self.__AVAILABLE_MONITORS[monitor_name]
                    monitor_data = None
                    try:
                        monitor_data = monitors[monitor_id]
                        #logger.info(monitor_data)
                    except KeyError:
                        monitor_data = None
                    if not monitor_data:
                        continue

                    if param_name in monitor_data:
                        replacements["$%s.%s" % (monitor_name, param_name)] = monitor_data[param_name]

            if len(replacements) == len(search):
                condition = self.when
                for replacement, new_value in replacements.iteritems():
                    condition = condition.replace(replacement, str(new_value))
                if eval(condition):
                    #logger.info("Condition (%s) evaluated to TRUE -> Send message" % self.when)
                    #TODO: Modify yaml syntax to specify the component type
                    self.append_trigger_message(component_id, 'system', self.message_id, json.dumps({"condition":condition}))

        logger.info("Condition has been evaluated.... Saving data..")
        self.commit_data()


class Trigger(object):
    def __init__(self):
        self.__id = 0
        self.__name = ""
        self.__conditions = []
    @property
    def id(self):
        return self.__id
    @id.setter
    def id(self, value):
        self.__id = value
    @property
    def name(self):
        return self.__name

    @name.setter
    def name(self, value):
        self.__name = value

    def append_condition(self, condition):
        """Appends a TriggerCondition object to the trigger"""
        if isinstance(condition, TriggerCondition):
            self.__conditions.append(condition)

    def __repr__(self):
        trigger_repr =  "<trigger id=%s name=%s><conditions>" % (self.id, self.name)
        for condition in self.__conditions:
            trigger_repr += str(condition)
        trigger_repr += "</conditions><trigger>"
        return trigger_repr

    def run(self):
        logger.info("Running Trigger: %s" % self.__name)
        for condition in self.__conditions:
            condition.evaluate()


class TriggerReader(object):
    """Loads the triggers file"""
    #Defined at #10062
    MESSAGE = {
        "$MESSAGE_INFO_ASSET_NOT_SENDING_LOGS":1,
        "$MESSAGE_INFO_LOGS_BUT_NOT_PLUGIN_ENABLED": 2,
        "$MESSAGE_WARNING_24_HOURS_WITHOUT_EVENTS":3,
        "$MESSAGE_WARNING_SATURATION":4,
        "$MESSAGE_WARNING_DROPPED_PACKAGES":5,
        "$MESSAGE_WARNING_DISK_SPACE":6,
        "$MESSAGE_ERROR_DISK_SPACE":7,
        "$MESSAGE_EXTERNAL_DNS_CONFIGURED":8,
        "$MESSAGE_SYSTEM_UNREACHEABLE_OR_UNAVAILABLE":9,
        "$MESSAGE_PENDING_UPDATES":10,
        "$MESSAGE_SENSOR_UNREACHEABLE_OR_UNAVAILABLE":11
    }
    def __init__(self, triggers_file):
        self.__trigger_file = triggers_file
        self.__triggers = []


    def get_trigger_from_ymldata(self, yaml_data, trigger_id):
        """Parses the yml_data for a trigger and returns a new Trigger object
        Sample trigger data:
        {
          'trigger': 'Generate asset activity logs',
          'conditions': [
            {
              'trigger_message_id': '$MESSAGE_WARNING_24_HOURS_WITHOUT_EVENTS',
              'when': '$MONITOR_ASSET_EVENTS.last_event_arraival > 84600 and $MONITOR_ASSET_EVENTS.has_events == True',
              'name': 'asset not sending logs in the last 24 hours'
            },
            {
              'trigger_message_id': '$MESSAGE_INFO_ASSET_NOT_SENDING_LOGS',
              'when': '$MONITOR_ASSET_EVENTS.has_events == False',
              'name': 'asset not sending logs to the system'
            },
            {
              'trigger_message_id': '$MESSAGE_INFO_LOGS_BUT_NOT_PLUGIN_ENABLED',
              'when': '$MONITOR_ASSET_EVENTS.has_logs == True and $MONITOR_ASSET_EVENTS.enabled_plugin == False',
              'name': 'asset sending logs but no plugin enabled'
            }
          ]
        }
        """
        trigger = None

        if 'trigger' in  yaml_data:
            trigger = Trigger()
            trigger.id = trigger_id
            trigger.name = yaml_data['trigger']
            if 'conditions' in yaml_data:
                for condition in yaml_data['conditions']:
                    c = TriggerCondition()
                    c.name = condition['name']
                    c.when = condition['when']
                    message_id = 0
                    if TriggerReader.MESSAGE.has_key(condition['trigger_message_id']):
                        message_id = TriggerReader.MESSAGE[condition['trigger_message_id']]
                    c.message_id = message_id
                    trigger.append_condition(c)
            else:
                logger.warning("Trigger without conditions")

        return trigger

    def load_triggers(self):
        """Loads the yaml file, and parses it"""
        rt = True
        try:
            yaml_file = open(self.__trigger_file, 'r')
            logger.info("Loading trigger file .... %s " % self.__trigger_file)
            data = yaml.load(yaml_file)
            yaml_file.close()
            if "triggers" in data:
                trigger_id = 0
                for trigger in data['triggers']:

                    self.__triggers.append(self.get_trigger_from_ymldata(trigger, trigger_id))
                    trigger_id += 1
            else:
                logger.info("No triggers")

        except Exception, e:
            logger.error("Error loading the triggers file: %s" % str(e))
            rt = False
        return rt
    @property
    def triggers(self):
        return self.__triggers


class CheckTriggers(Monitor):
    """Class that reads the monitor data and triggers the appropiate messages"""
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.CHECK_TRIGGERS)
        self.message = 'Sensor Dropped Packages monitor started'
    def start(self):
        """
            Starts the monitor activity
        """
        rt = True
        try:
            logger.info("Monitor %s Working..." % self.monitor_id)
            trigger_reader = TriggerReader(TRIGGERS_FILE)
            if trigger_reader.load_triggers():
                for trigger in trigger_reader.triggers:
                    trigger.run()
            else:
                logger.warning("TriggerReader load_trigger fails")

        except Exception, e:
            logger.error("Something wrong happen while running the monitor..%s, %s" % (self.get_monitor_id(),
                str(e)))
            rt = False

        return rt
