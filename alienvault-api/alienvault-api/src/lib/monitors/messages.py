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
import yaml
#import celery.utils.log
from db.methods.data import load_messages_to_db

#logger = celery.utils.log.get_logger("celery")
from api import app


MESSAGES_FILE = "/etc/alienvault/api/messages.yml"


class Message(object):
    """ Message object """
    def __init__(self):
        self.__id = 0
        self.__level = ""
        self.__type = ""
        self.__message_role = ""
        self.__action_role = ""
        self.__title = ""
        self.__description = ""
        self.__actions = ""
        self.__alternative_actions = ""
        self.__source = ""
    @property
    def id(self):
        """Message ID"""
        return self.__id

    @id.setter
    def id(self, value):
        """Message ID set"""
        self.__id = value

    @property
    def level(self):
        """Message level"""
        return self.__level

    @level.setter
    def level(self, value):
        """Message level set"""
        self.__level = value

    @property
    def type(self):
        """Message type"""
        return self.__type

    @type.setter
    def type(self, value):
        """Message type set"""
        self.__type = value

    @property
    def message_role(self):
        """Message message_role"""
        return self.__message_role

    @message_role.setter
    def message_role(self, value):
        """Message message_role set"""
        self.__message_role = value

    @property
    def action_role(self):
        """Message action_role"""
        return self.__action_role

    @action_role.setter
    def action_role(self, value):
        """Message action_role set"""
        self.__action_role = value

    @property
    def title(self):
        """Message title"""
        return self.__title

    @title.setter
    def title(self, value):
        """Message title set"""
        self.__title = value

    @property
    def description(self):
        """Message description"""
        return self.__description

    @description.setter
    def description(self, value):
        """Message description set"""
        self.__description = value

    @property
    def actions(self):
        """Message actions"""
        return self.__actions

    @actions.setter
    def actions(self, value):
        """Message actions set"""
        self.__actions = value

    @property
    def alternative_actions(self):
        """Message alternative_actions"""
        return self.__alternative_actions

    @alternative_actions.setter
    def alternative_actions(self, value):
        """Message alternative_actions set"""
        self.__alternative_actions = value

    @property
    def source(self):
        """Message source"""
        return self.__source

    @source.setter
    def source(self, value):
        """Message source set"""
        self.__source = value

    def __repr__(self):
        message_repr = '<message id=%s level="%s" type="%s" message_role="%s" action_role="%s" title="%s" description="%s" actions="%s" alternative_actions="%s" source="%s">' % \
                (self.id, self.level, self.type, self.message_role, self.action_role, self.title, self.description, self.actions, self.alternative_actions, self.source)
        return message_repr


class MessageReader(object):
    """Loads the messages.yml file"""

    def __init__(self, messages_file=MESSAGES_FILE):
        self.__messages_file = messages_file
        self.__types = set()
        self.__levels = set()
        self.__sources = set()
        self.__messages = []
        self.__message_ids = {}
        self.__load_messages()

    def __get_message_from_ymldata(self, yaml_data):
        """Parses the yml_data for a message and returns a new Message object
        Sample message data:
           {
             id: 3,
             level: warning,
             type: Deployment
             message_role: "admin",
             action_role: "admin",
             title: "Log management disrupted",
             description: "The system has not received a log from this asset in more than 24 hours. This may be an indicator of the asset \
                         having connection difficulties with AlienVault or a disruptive configuration change on the asset. At TIMESTAMP"
             actions: "*[AV_PATH/asset_details/enable_plugin.php?asset_id=ASSET_ID Configure] data source plugin"
             alternative_actions: ""

           }
        """
        message = None
        try:
            if yaml_data['level'] not in self.__levels or yaml_data['type'] not in self.__types or yaml_data['source'] not in self.__sources:
                app.logger.warning("Invalid message level/type/source found")
            elif not self.__message_ids or not self.__message_ids[yaml_data['id']]:
                app.logger.warning("Invalid message ID: %s" % yaml_data['id'])
            else:
                message = Message()
                message.id = self.__message_ids[yaml_data['id']]
                message.level = yaml_data['level']
                message.type = yaml_data['type']
                message.message_role = yaml_data['message_role']
                message.action_role = yaml_data['action_role']
                message.title = yaml_data['title']
                message.description = yaml_data['description']
                message.actions = yaml_data['actions']
                message.alternative_actions = yaml_data['alternative_actions']
                message.source = yaml_data['source']
        except Exception, e:
            app.logger.error("Error reading message: %s" % e)
        return message

    def __load_messages(self):
        """Load and parse messages in the yaml file"""
        rt = True
        try:
            with open(self.__messages_file, 'r') as yaml_file:
                app.logger.info("Loading message file %s " % self.__messages_file)
                data = yaml.load(yaml_file)
            # Load message IDs
            if "message_ids" in data:
                self.__message_ids = data["message_ids"]
            # Load valid levels and types in messages
            if "message_levels" in data:
                for level in data["message_levels"]:
                    self.__levels.add(level)
            if "message_types" in data:
                for type in data["message_types"]:
                    self.__types.add(type)
            if "message_sources" in data:
                for source in data["message_sources"]:
                    self.__sources.add(source)
            # Load messages
            if "messages" in data:
                for message in data['messages']:
                    new_message = self.__get_message_from_ymldata(message)
                    if new_message:
                        self.__messages.append(new_message)
            else:
                app.logger.warning("No messages found")

        except Exception, e:
            app.logger.error("Error loading the messages file: %s" % str(e))
            rt = False

        return rt

    @property
    def messages(self):
        """List of message objects"""
        return self.__messages

    @property
    def message_ids(self):
        """Dict of message ids"""
        return self.__message_ids


def initial_msg_load():
    """ Read messages from YAML and load into database """
    try:
        message_reader = MessageReader(MESSAGES_FILE)
        if message_reader.messages:
            return load_messages_to_db(message_reader.messages)

    except Exception, e:
        return False, "[initial_msg_load] Error: %s" % str(e)
    return False, "[initial_msg_load] Error: Messages couldn't be loaded."
