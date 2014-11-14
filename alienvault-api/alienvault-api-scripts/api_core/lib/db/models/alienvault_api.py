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
from db.models.alienvault import  Host, Net, Sensor, Server, Users, Net_Cidrs, System
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

class Celery_Job (Base):
    __tablename__='celery_job'
    info = Column('info',BLOB,primary_key=False)
    last_modified = Column('last_modified',TIMESTAMP,primary_key=False)
    id = Column('id',BINARY(16),primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'info':self.info,
          'last_modified':self.last_modified,
          'id':str(uuid.UUID(bytes=self.id)) if self.id else '',
        }
class Logged_Actions (Base):
    __tablename__='logged_actions'
    action_description = Column('action_description',VARCHAR(255),primary_key=False)
    logged_user = Column('logged_user',VARCHAR(45),primary_key=False)
    id = Column('id', INTEGER,primary_key=True)
    datetime = Column('datetime',TIMESTAMP,primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'action_description':self.action_description,
          'logged_user':self.logged_user,
          'id':self.id,
          'datetime':self.datetime,
        }
class Monitor_Data (Base):
    __tablename__='monitor_data'
    monitor_id = Column('monitor_id', INTEGER,primary_key=True)
    timestamp = Column('timestamp',TIMESTAMP,primary_key=True)
    component_id = Column('component_id',BINARY(16),primary_key=True)
    data = Column('data',TEXT,primary_key=False)
    component_type = Column('component_type',VARCHAR(55),primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'monitor_id':self.monitor_id,
          'timestamp':self.timestamp,
          'component_id':str(uuid.UUID(bytes=self.component_id)) if self.component_id else '',
          'data':self.data,
          'component_type':self.component_type,
        }


'''
Status_Action class.
Mapped to alienvault_api.status_action
'''
class Status_Action (Base):
  __tablename__ = 'status_action'
  __table_args__ = {
    'mysql_engine': 'InnoDB',
    'mysql_charset': 'utf8'
    }

  action_id = Column (INTEGER, primary_key = True)
  is_admin = Column (BOOLEAN)
  content = Column (TEXT)
  link = Column (TEXT)

  @property
  def serialize(self):
      return {
          'action_id': self.action_id,
          'is_admin': self.is_admin,
          'content': self.content,
          'link': self.link
      }

Status_Message_Action = Table('status_message_action',
                              Base.metadata,
                              Column('message_id', SMALLINT, ForeignKey('status_message.id')),
                              Column('action_id', INTEGER, ForeignKey('status_action.action_id')))

class Status_Message(Base):
    __tablename__ = 'status_message'
    id = Column('id', SMALLINT, primary_key=True)
    level = Column('level', ENUM('info','error','warning'), primary_key=False)
    content = Column('content', TEXT, primary_key=False)
    desc = Column('description', TEXT, primary_key=False)
    actions = relationship('Status_Action',
                           secondary = Status_Message_Action,
                           lazy = 'select')

    @property
    def serialize(self):
        actions = [{'content': x.content, 'link': x.link} for x in self.actions]
        return {
            'msg_id': self.id,
            'level': self.level,
            'content': self.content,
            'desc': self.desc,
            'actions': actions
        }

class Current_Status (Base):
    __tablename__='current_status'
    message_id = Column('message_id',SMALLINT, ForeignKey('status_message.id'), primary_key=True)
    component_id = Column('component_id',BINARY(16),primary_key=True)
    component_type = Column('component_type',ENUM('net','host','user','sensor','server','system'),primary_key=False)
    creation_time = Column('creation_time',TIMESTAMP,primary_key=False)
    viewed = Column('viewed',ENUM('true','false'),primary_key = False)
    supressed = Column('supressed',TINYINT,primary_key=False)
    supressed_time = Column('supressed_time',TIMESTAMP,primary_key=False)
    additional_info = Column('additional_info', TEXT, primary_key=False)
    message = relationship(Status_Message, lazy=False   )

    # Sonar warning
    @staticmethod
    def _get_value_string(s):
        return s if s is not None else ''
    #
    @staticmethod
    def _get_value_uuid(s):
        return str(uuid.UUID(bytes=s)) if s is not None else ''


    @property
    def serialize(self):
        component_dict = self.host or self.net or self.sensor or self.user or self.system
        if len(component_dict.keys()) > 0:
            (component_name, component_ip) = component_dict.keys()[0]
        else:
            (component_name, component_ip) = (None,None)
        # TODO: Fix relationship in BBDD. Added this to avoid crashing in
        # apimethods.status
        #component_name = ""
        #component_ip = ""
        message_level = self.message.level if self.message is not None else ""
        message_description = self.message.desc if self.message is not None else ""
        return {
          'message_id':self.message_id,
          'component_id':self._get_value_uuid(self.component_id),
          'component_type': self.component_type,
          'component_name': self._get_value_string(component_name),
          'component_ip': self._get_value_string(component_ip),
          'supressed_time':self.supressed_time,
          'creation_time':self.creation_time,
          'supressed':self.supressed,
          'viewed' : True if self.viewed != 'false' else False,
          'level'  : message_level,
          'description' : message_description
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

Current_Status.host = relationship(Host,
                                   primaryjoin = Current_Status.component_id == Host.id,
                                   foreign_keys = [Host.__table__.c.id],
                                   collection_class = mapped_collection(lambda item: (item.hostname, ",".join([socket.inet_ntoa(x.ip) for x in item.host_ips]))),
                                   lazy='false', passive_deletes='all')

Current_Status.net = relationship(Net,
                                  primaryjoin = Current_Status.component_id == Net.id,
                                  foreign_keys = [Net.__table__.c.id],
                                  collection_class = mapped_collection(lambda item: (item.name, ",".join([x.cidr for x in item.net_cidrs]))),
                                  lazy='false', passive_deletes=True)

Current_Status.user = relationship(Users,
                                   primaryjoin = Current_Status.component_id == Users.uuid,
                                   foreign_keys = [Users.__table__.c.uuid],
                                   collection_class = mapped_collection(lambda item: (item.name, '')),
                                   lazy='false', passive_deletes=True)

Current_Status.server = relationship(Server,
                                     primaryjoin = Current_Status.component_id == Server.id,
                                     foreign_keys = [Server.__table__.c.id],
                                     collection_class = mapped_collection(lambda item: (item.name, socket.inet_ntoa(item.ip) )),
                                     lazy='false', passive_deletes=True)

Current_Status.sensor = relationship(Sensor,
                                     primaryjoin = Current_Status.component_id == Sensor.id,
                                     foreign_keys = [Sensor.__table__.c.id],
                                     collection_class = mapped_collection(lambda item: (item.name, socket.inet_ntoa(item.ip) )),
                                     lazy='false', passive_deletes=True)

Current_Status.system = relationship (System,
                                      primaryjoin=Current_Status.component_id == System.id,
                                      foreign_keys=[System.__table__.c.id],
                                      collection_class=mapped_collection(lambda item: (item.name, socket.inet_ntoa(item.vpn_ip) if item.vpn_ip is not None else socket.inet_ntoa(item.admin_ip))),
                                      lazy='false', passive_deletes=True)

