# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2014 AlienVault
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

import uuid
import socket
import json

# We need to obtain info from Alienvault Database and Center Database
from db.models.alienvault import Host, Net, Sensor, Server, Users, Net_Cidrs, System
#from db.models.avcenter import Current_Local

from sqlalchemy import Table, Column, ForeignKey
from sqlalchemy.sql import func
from sqlalchemy.orm import sessionmaker, relationship
from sqlalchemy.orm.collections import mapped_collection
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.dialects.mysql import BIGINT, BINARY, BIT, BLOB, BOOLEAN, CHAR, \
    DATE, DATETIME, DECIMAL, DECIMAL, DOUBLE, ENUM, FLOAT, INTEGER, LONGBLOB, \
    LONGTEXT, MEDIUMBLOB, MEDIUMINT, MEDIUMTEXT, NCHAR, NUMERIC, NVARCHAR, \
    REAL, SET, SMALLINT, TEXT, TIME, TIMESTAMP, TINYBLOB, TINYINT, TINYTEXT, \
    VARBINARY, VARCHAR, YEAR
from apimethods.utils import get_uuid_string_from_bytes,get_ip_str_from_bytes

import db

Base = declarative_base(bind=db.get_engine(database='alienvault_api'))


class UserPermissions(Base):
    __tablename__ = "user_perms"
    component_id = Column('component_id', BINARY(16), primary_key=True)
    login = Column('login', VARCHAR(64), primary_key=False)

    @property
    def serialize(self):
        return {'component_id': get_uuid_string_from_bytes(self.component_id),
                'login': self.login}


class Celery_Job (Base):
    __tablename__ = 'celery_job'
    info = Column('info', BLOB, primary_key=False)
    last_modified = Column('last_modified', TIMESTAMP, primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)

    #
    # Relations:
    #

    @property
    def serialize(self):
        return {'info': self.info,
                'last_modified': self.last_modified,
                'id': str(uuid.UUID(bytes=self.id)) if self.id else ''}


class Logged_Actions (Base):
    __tablename__ = 'logged_actions'
    action_description = Column('action_description', VARCHAR(255), primary_key=False)
    logged_user = Column('logged_user', VARCHAR(45), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    datetime = Column('datetime', TIMESTAMP, primary_key=False)

    #
    # Relations:
    #
    @property
    def serialize(self):
        return {'action_description': self.action_description,
                'logged_user': self.logged_user,
                'id': self.id,
                'datetime': self.datetime}


class Monitor_Data (Base):
    __tablename__ = 'monitor_data'
    monitor_id = Column('monitor_id', INTEGER, primary_key=True)
    timestamp = Column('timestamp', TIMESTAMP, primary_key=True)
    component_id = Column('component_id', BINARY(16), primary_key=True)
    data = Column('data', TEXT, primary_key=False)
    component_type = Column('component_type', VARCHAR(55), primary_key=False)

    #
    # Relations:
    #
    @property
    def serialize(self):
        return {'monitor_id': self.monitor_id,
                'timestamp': self.timestamp,
                'component_id': get_uuid_string_from_bytes(self.component_id) if self.component_id else '',
                'data': self.data,
                'component_type': self.component_type}


class Status_Message(Base):
    __tablename__ = 'status_message'
    id = Column('id', BINARY(16), primary_key=True)
    level = Column('level', TINYINT, primary_key=False)
    title = Column('title', TEXT, primary_key=False)
    description = Column('description', TEXT, primary_key=False)
    type = Column('type', VARCHAR(20), primary_key=False)
    expire = Column('expire', DATETIME, primary_key=False)
    actions = Column('actions', TEXT, primary_key=False)
    alternative_actions = Column('alternative_actions', TEXT, primary_key=False)
    message_role = Column('message_role', TEXT, primary_key=False)
    action_role = Column('action_role', TEXT, primary_key=False)
    source = Column('source', VARCHAR(32), primary_key=False)

    MESSAGE_LEVEL_MAP = {
        1: "info",
        2: "warning",
        3: "error"
    }

    @staticmethod
    def get_message_level_str(level):
        """Returns a string representing the current level"""
        if level in Status_Message.MESSAGE_LEVEL_MAP:
            return Status_Message.MESSAGE_LEVEL_MAP[level]
        return ""

    @staticmethod
    def get_level_integer_from_string(level_str):
        """Returns the level integer value from a string value"""
        level_str = level_str.lower()
        if level_str not in Status_Message.MESSAGE_LEVEL_MAP.values():
            return 0
        for key, value in Status_Message.MESSAGE_LEVEL_MAP.iteritems():
            if value == level_str:
                return key
        return 0

    @property
    def serialize(self):
        """Returns a serialized object from Status_Message object"""
        return {
            'id': get_uuid_string_from_bytes(self.id) if self.id else '',
            'level': Status_Message.get_message_level_str(self.level),
            'title': self.title,
            'description': self.description,
            'type': self.type,
            'expire': self.expire,
            'actions': self.actions,
            'alternative_actions': self.alternative_actions,
            'message_role': self.message_role,
            'action_role': self.action_role,
            'source': self.source
        }


class Current_Status (Base):
    __tablename__ = 'current_status'
    id = Column('id', BINARY(16), primary_key=True)
    message_id = Column('message_id', BINARY(16), ForeignKey(Status_Message.id))
    component_id = Column('component_id', BINARY(16), primary_key=False)
    component_type = Column('component_type', ENUM('net', 'host', 'user', 'sensor', 'server', 'system', 'external'), primary_key=False)
    creation_time = Column('creation_time', TIMESTAMP, primary_key=False)
    viewed = Column('viewed', TINYINT, primary_key=False)
    suppressed = Column('suppressed', TINYINT, primary_key=False)
    suppressed_time = Column('suppressed_time', TIMESTAMP, primary_key=False)
    additional_info = Column('additional_info', TEXT, primary_key=False)
    message = relationship(Status_Message)

    # Sonar warning
    @staticmethod
    def _get_value_string(s):
        return s if s is not None else ''

    @staticmethod
    def _get_value_uuid(s):
        return str(uuid.UUID(bytes=s)) if s is not None else ''

    @property
    def serialize(self):
        component_dict = self.host or self.net or self.sensor or self.user or self.system
        if len(component_dict.keys()) > 0:
            (component_name, component_ip) = component_dict.keys()[0]
        else:
            (component_name, component_ip) = (None, None)
        if self.message is None:
            print "This status_message (%s) doesn't have a related status_messsage...that's wierd! " % (get_uuid_string_from_bytes(self.id))
        message_level = Status_Message.get_message_level_str(self.message.level) if self.message is not None else ""
        message_title = self.message.title if self.message is not None else ""
        message_description = self.message.description if self.message is not None else ""
        message_type = self.message.type if self.message is not None else ""
        message_expire = self.message.expire if self.message is not None else ""
        message_actions = self.message.actions if self.message is not None else ""
        message_role = self.message.message_role if self.message is not None else ""
        message_action_role = self.message.action_role if self.message is not None else ""
        message_alternative_actions = self.message.alternative_actions if self.message is not None else ""
        message_source = self.message.source if self.message is not None else ""
        additional_info_json = {}
        try:
            if self.additional_info is not None and self.additional_info != "":
                additional_info_json = json.loads(self.additional_info)
        except Exception, e:
            additional_info_json = json.loads('{"error": "Invalid json object for this message"}')
        return {
            'id': get_uuid_string_from_bytes(self.id),
            'message_id': get_uuid_string_from_bytes(self.message_id),
            'component_id': self._get_value_uuid(self.component_id) if self.component_id is not None else "",
            'component_type': self.component_type,
            'component_name': self._get_value_string(component_name),
            'component_ip': self._get_value_string(component_ip),
            'suppressed_time': self.suppressed_time,
            'creation_time': self.creation_time,
            'suppressed': self.suppressed,
            'viewed': self.viewed,
            'additional_info': additional_info_json,
            'message_level': message_level,
            'message_title': message_title,
            'message_description': message_description,
            'message_type': message_type,
            'message_expire': message_expire,
            'message_actions': message_actions,
            'message_role': message_role,
            'message_action_role': message_action_role,
            'message_alternative_actions': message_alternative_actions,
            'message_source': message_source
        }


'''
In these relationships, there are two important attributes to check for:
  - collection_class defines how the objects linked by the relationship will be presented. Usually,
    objects are contained inside a regular list. The mapped_collection function allows us to present
    them as dictionaries whose keys are defined by a callable function.
  - lazy defines how the relationship query should be executed. In this case, select means that
    a separate select query will be performed and no object will be retrieved if the user does not
    explicitly refers to the relationship.
'''
#Current_Status.message = relationship(Status_Message,
#                                   primaryjoin = Current_Status.message_id == Status_Message.id,
#                                   foreign_keys = [Status_Message.__table__.c.id],
#                                   lazy=False)
#

Current_Status.host = relationship(Host,
                                   primaryjoin=Current_Status.component_id == Host.id,
                                   foreign_keys=[Host.__table__.c.id],
                                   collection_class=mapped_collection(lambda item: (item.hostname, ",".join([socket.inet_ntoa(x.ip) for x in item.host_ips]))),
                                   lazy=True, passive_deletes=True)

Current_Status.net = relationship(Net,
                                  primaryjoin=Current_Status.component_id == Net.id,
                                  foreign_keys=[Net.__table__.c.id],
                                  collection_class=mapped_collection(lambda item: (item.name, ",".join([x.cidr for x in item.net_cidrs]))),
                                  lazy=True, passive_deletes=True)

Current_Status.user = relationship(Users,
                                   primaryjoin=Current_Status.component_id == Users.uuid,
                                   foreign_keys=[Users.__table__.c.uuid],
                                   collection_class=mapped_collection(lambda item: (item.name, '')),
                                   lazy=True, passive_deletes=True)

Current_Status.server = relationship(Server,
                                     primaryjoin=Current_Status.component_id == Server.id,
                                     foreign_keys=[Server.__table__.c.id],
                                     collection_class=mapped_collection(lambda item: (item.name, socket.inet_ntoa(item.ip))),
                                     lazy=True, passive_deletes=True)

Current_Status.sensor = relationship(Sensor,
                                     primaryjoin=Current_Status.component_id == Sensor.id,
                                     foreign_keys=[Sensor.__table__.c.id],
                                     collection_class=mapped_collection(lambda item: (item.name, socket.inet_ntoa(item.ip))),
                                     lazy=True, passive_deletes=True)

Current_Status.system = relationship(System,
                                     primaryjoin=Current_Status.component_id == System.id,
                                     foreign_keys=[System.__table__.c.id],
                                     collection_class=mapped_collection(lambda item: (item.name, socket.inet_ntoa(item.vpn_ip) if item.vpn_ip is not None else socket.inet_ntoa(item.admin_ip))),
                                     lazy=True, passive_deletes=True)

