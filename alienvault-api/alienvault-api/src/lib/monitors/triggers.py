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

import json
import re

import celery.utils.log
import yaml
from api.lib.monitors.messages import MessageReader
from api.lib.monitors.monitor import Monitor, MonitorTypes
from apimethods.utils import get_uuid_string_from_bytes
from db.methods.api import (save_current_status_message,
                            purge_current_status_message,
                            get_all_monitor_data)

logger = celery.utils.log.get_logger("celery")

TRIGGERS_FILE = "/etc/alienvault/api/triggers.yml"


class TriggerCondition(object):
    """Trigger Condition Class"""
    __AVAILABLE_MONITORS = {
        "MONITOR_DROPPED_PACKAGES": MonitorTypes.MONITOR_DROPPED_PACKAGES,
        "MONITOR_CPU_LOAD": MonitorTypes.MONITOR_CPU_LOAD,
        "MONITOR_DISK_SPACE": MonitorTypes.MONITOR_DISK_SPACE,
        "MONITOR_ASSET_EVENTS": MonitorTypes.MONITOR_ASSET_LOG_ACTIVITY,
        "MONITOR_DNS": MonitorTypes.MONITOR_SYSTEM_DNS,
        "MONITOR_REMOTE_CERTIFICATES": MonitorTypes.MONITOR_REMOTE_CERTIFICATES,
        "MONITOR_PENDING_UPDATES": MonitorTypes.MONITOR_PENDING_UPDATES,
        "MONITOR_PLUGINS_VERSION": MonitorTypes.MONITOR_PLUGINS_VERSION,
        "MONITOR_PLUGINS_CHECK_INTEGRITY": MonitorTypes.MONITOR_PLUGINS_CHECK_INTEGRITY,
        "MONITOR_PLATFORM_TELEMETRY_DATA": MonitorTypes.MONITOR_PLATFORM_TELEMETRY_DATA,
        "MONITOR_PLATFORM_MESSAGE_CENTER_DATA": MonitorTypes.MONITOR_PLATFORM_MESSAGE_CENTER_DATA,
        "MONITOR_SYSTEM_CHECK_DB": MonitorTypes.MONITOR_SYSTEM_CHECK_DB,
        "MONITOR_WEBUI_DATA": MonitorTypes.MONITOR_WEBUI_DATA,
        "MONITOR_SYSTEM_REBOOT_NEEDED": MonitorTypes.MONITOR_SYSTEM_REBOOT_NEEDED,
        "MONITOR_DOWNLOAD_PULSES": MonitorTypes.MONITOR_DOWNLOAD_PULSES,
        "MONITOR_UPDATE_HOST_PLUGINS": MonitorTypes.MONITOR_UPDATE_HOST_PLUGINS,
        "MONITOR_INSECURE_VPN": MonitorTypes.MONITOR_INSECURE_VPN,
        "MONITOR_FEDERATED_OTX_KEY": MonitorTypes.MONITOR_FEDERATED_OTX_KEY,
        "MONITOR_ENABLED_PLUGINS_LIMIT": MonitorTypes.MONITOR_ENABLED_PLUGINS_LIMIT,
        "MONITOR_FEED_AUTO_UPDATES": MonitorTypes.MONITOR_FEED_AUTO_UPDATES,
        "CHECK_TRIGGERS": MonitorTypes.CHECK_TRIGGERS,
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
        return "<condition msg_id=%s><name>%s</name><when>%s</when></condition>" % (
        self.message_id, self.name, self.when)

    def trigger_message(self, component_id, component_type, message_code, data=""):
        """
        Creates a current status message and saves it in the database
        :param component_id: The component id - uuid cannonical string
        :param message_code: The message id - uuid cannonical string
        :param data: Current Status Message Additional Info
        :return: True is successful, False otherwise
        """
        rt = True
        try:
            rt = save_current_status_message(component_id, component_type, message_code, data)
        except Exception as error:
            logger.error("[trigger_message] %s" % str(error))
            rt = False
        return rt

    def purge_messages(self):
        """Deletes all the messages for this condition
        """
        return purge_current_status_message(self.message_id, self.__trigger_messages)

    def get_monitor_and_parameters_to_be_evaluated(self):
        """Returns a hash table where the keys are the monitor_id and the value
        is a list of parameters name to be evaluated and the total number of parameters
        to be replaced

        Example:
        {
            '1': {'name':"monitorname1", 'parameters':['param1',param2]},
            '2': {'name':"monitor_name2", 'parameters':['param1',param2]},
        }, 4"""
        # Parses the when clause to to retrieve the monitor and the parameter
        # Example when clause
        # $MONITOR_ASSET_EVENTS.last_event_arrival > 84600 and $MONITOR_ASSET_EVENTS.has_events == True
        aux_regex_expr = "\$(\S+)"
        aux_search = re.compile(aux_regex_expr).findall(self.when)

        final_search = []
        for token in aux_search:
            final_search.append(tuple(token.split('.')))

        # search_results = self.__regex_monitor.findall(self.when)
        # logger.error("%s" % search_results)
        # We could use several parameters within a monitor to test a condition.
        # Hash table monitor_id:[param_name]
        # Each search tuple will be (monitor_name,parameter_name)
        monitor_parameters = {}
        total_parameters_in_condition = 0
        # for search_tuple in search_results:
        for search_tuple in final_search:
            if len(search_tuple) == 2:
                monitor_name = search_tuple[0]
                monitor_param = search_tuple[1]
                if monitor_name in self.__AVAILABLE_MONITORS:
                    monitor_id = self.__AVAILABLE_MONITORS[monitor_name]
                    try:
                        monitor_parameters[monitor_id]['parameters'].append(monitor_param)
                    except KeyError:
                        monitor_parameters[monitor_id] = {'name': monitor_name,
                                                          'parameters': []}
                        monitor_parameters[monitor_id]['parameters'].append(monitor_param)
                    finally:
                        total_parameters_in_condition += 1
            elif len(search_tuple) == 3:
                monitor_name = search_tuple[0]
                monitor_param = search_tuple[1]
                monitor_subparam = search_tuple[2]
                if monitor_name in self.__AVAILABLE_MONITORS:
                    monitor_id = self.__AVAILABLE_MONITORS[monitor_name]
                    try:
                        if monitor_param not in monitor_parameters[monitor_id]['parameters']:
                            monitor_parameters[monitor_id]['parameters'].append(monitor_param)
                        if monitor_subparam not in monitor_parameters[monitor_id]['subparameters'][monitor_param]:
                            monitor_parameters[monitor_id]['subparameters'][monitor_param].append(monitor_subparam)
                    except KeyError:
                        monitor_parameters[monitor_id] = {'name': monitor_name,
                                                          'parameters': [],
                                                          'subparameters': {monitor_param: []}}
                        monitor_parameters[monitor_id]['parameters'].append(monitor_param)
                        monitor_parameters[monitor_id]['subparameters'][monitor_param].append(monitor_subparam)
                    finally:
                        total_parameters_in_condition += 1

            else:
                logger.error("[get_monitor_and_parameters_to_be_evaluated] Invalid monitor name <%s>" % monitor_name)

        return monitor_parameters, total_parameters_in_condition

    def evaluate(self):
        """Evaluates the condition
        :returns True on success, False otherwise
        """
        # Get the list of parameters to be evaluate for each monitor on the condition
        monitor_parameters, total_parameters_in_condition = self.get_monitor_and_parameters_to_be_evaluated()
        """
        {
          "1": {
            "name": "MONITOR_DROPPED_PACKAGES",
            "parameters": [
              "packet_loss"
            ]
          },
          "2": {
            "name": "MONITOR_CPU_LOAD",
            "parameters": [
              "cpu_load"
            ]
          }
        }
        """
        logger.info("Running trigger condition... %s" % self.name)

        # Retrieve the information from the monitors that the condition are related with.
        result_set = get_all_monitor_data(monitor_parameters.keys())
        if len(result_set) == 0:
            self.purge_messages()
            return True
        if total_parameters_in_condition == 0:
            self.purge_messages()
            return True
        # Group by component
        component_monitors = {}
        for monitor in result_set:
            monitor_id = monitor.monitor_id
            # Check whether the monitor id is on the monitor to be evaluete for this condition
            if monitor_id not in monitor_parameters.keys():
                continue
            monitor_name = monitor_parameters[monitor_id]['name']
            monitor_data = json.loads(monitor.data)
            monitor_component_type = monitor.component_type
            component_id = get_uuid_string_from_bytes(monitor.component_id)
            if component_id not in component_monitors:
                component_monitors[component_id] = []
            monitor_hash = {"monitor_id": monitor_id,
                            "monitor_name": monitor_name,
                            "monitor_data": monitor_data,
                            "monitor_component_type": monitor_component_type}
            component_monitors[component_id].append(monitor_hash)
        # print (json.dumps(component_monitors))
        for component, component_monitors in component_monitors.iteritems():
            replacements = {}
            for monitor_info in component_monitors:
                monitor_id = monitor_info["monitor_id"]
                monitor_name = monitor_info["monitor_name"]
                monitor_data = monitor_info["monitor_data"]
                monitor_component_type = monitor_info["monitor_component_type"]
                parameters_to_be_evaluated = monitor_parameters[monitor_id]['parameters']
                for parameter in parameters_to_be_evaluated:
                    # print ("Para %s" % parameter)
                    # print (monitor_data)
                    if parameter in monitor_data:
                        # logger.info("*****\n%s\n%s\n*****\n" % (parameter, monitor_data))
                        if not isinstance(monitor_data[parameter], dict):
                            replace_string = "$%s.%s" % (monitor_name, parameter)
                            # print (replace_string)
                            replace_value = monitor_data[parameter]
                            replacements[replace_string] = replace_value
                        else:
                            if 'subparameters' in monitor_parameters[monitor_id].keys():
                                for subparameter in monitor_parameters[monitor_id]['subparameters'][parameter]:
                                    replace_string = "$%s.%s.%s" % (monitor_name, parameter, subparameter)
                                    replace_value = monitor_data[parameter][subparameter]
                                    replacements[replace_string] = replace_value

            # print ("Total %s " % total_parameters_in_condition)
            # print ("R: %s" % replacements)
            if total_parameters_in_condition == len(replacements.keys()):  # We can evaluate the condition
                condition = self.when
                for replacement, new_value in replacements.iteritems():
                    if isinstance(new_value, unicode) or isinstance(new_value, str):
                        new_value = '\"' + new_value + '\"'
                    condition = condition.replace(replacement, str(new_value))
                # print (condition)
                if eval(condition):
                    self.__trigger_messages.append(component)
                    if not self.trigger_message(component, monitor_component_type, self.message_id,
                                                json.dumps(monitor_data)):
                        logger.error("Cannot insert the new notification")
                        print("Cannot insert the new notification")

        self.purge_messages()
        logger.info("Condition has been evaluated.... Saving data..")
        return True


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
        trigger_repr = "<trigger id=%s name=%s><conditions>" % (self.id, self.name)
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
    # Defined at #10062
    reader = MessageReader()
    MESSAGE = reader.message_ids

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

        if 'trigger' in yaml_data:
            trigger = Trigger()
            trigger.id = trigger_id
            trigger.name = yaml_data['trigger']
            if 'conditions' in yaml_data:
                for condition in yaml_data['conditions']:
                    c = TriggerCondition()
                    c.name = condition['name']
                    c.when = condition['when']
                    message_id = 0
                    if condition['trigger_message_id'] in TriggerReader.MESSAGE:
                        message_id = TriggerReader.MESSAGE[condition['trigger_message_id']]
                        c.message_id = message_id
                        trigger.append_condition(c)
                    else:
                        logger.warning("Trigger message id not valid: %s" % condition['trigger_message_id'])
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
            logger.error("Something wrong happen while running the monitor..%s, %s"
                         % (self.get_monitor_id(), str(e)))
            rt = False

        return rt
