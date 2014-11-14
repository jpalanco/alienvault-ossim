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

from sqlalchemy import Column, ForeignKey
from sqlalchemy.orm import sessionmaker, relationship
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.dialects.mysql import BIGINT, BINARY, BIT, BLOB, BOOLEAN, CHAR, \
    DATE, DATETIME, DECIMAL, DECIMAL, DOUBLE, ENUM, FLOAT, INTEGER, LONGBLOB, \
    LONGTEXT, MEDIUMBLOB, MEDIUMINT, MEDIUMTEXT, NCHAR, NUMERIC, NVARCHAR, \
    REAL, SET, SMALLINT, TEXT, TIME, TIMESTAMP, TINYBLOB, TINYINT, TINYTEXT, \
    VARBINARY, VARCHAR, YEAR

from apimethods.utils import get_uuid_string_from_bytes,get_ip_str_from_bytes

import db

Base = declarative_base(bind=db.get_engine(database='alienvault_siem'))

class Ac_Acid_Event (Base):
    __tablename__='ac_acid_event'
    cnt = Column('cnt', INTEGER,primary_key=False)
    ctx = Column('ctx',BINARY(16),primary_key=True,index=True)
    src_net = Column('src_net',BINARY(16),primary_key=True,index=True)
    day = Column('day',DATETIME,primary_key=True,index=True)
    dst_host = Column('dst_host',BINARY(16),primary_key=True,index=True)
    dst_net = Column('dst_net',BINARY(16),primary_key=True,index=True)
    plugin_id = Column('plugin_id', INTEGER,primary_key=True,autoincrement=False,index=True)
    device_id = Column('device_id', INTEGER,primary_key=True,autoincrement=False,index=True)
    plugin_sid = Column('plugin_sid', INTEGER,primary_key=True,autoincrement=False,index=True)
    src_host = Column('src_host',BINARY(16),primary_key=True,index=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'cnt':self.cnt,
          'ctx':get_uuid_string_from_bytes(self.ctx),
          'src_net':get_uuid_string_from_bytes(self.src_net),
          'day':self.day,
          'dst_host':get_uuid_string_from_bytes(self.dst_host),
          'dst_net':get_uuid_string_from_bytes(self.dst_net),
          'plugin_id':self.plugin_id,
          'device_id':self.device_id,
          'plugin_sid':self.plugin_sid,
          'src_host':get_uuid_string_from_bytes(self.src_host),
        }
class Reputation_Data (Base):
    __tablename__='reputation_data'
    rep_ip_dst = Column('rep_ip_dst',VARBINARY(16),primary_key=False)
    rep_rel_dst = Column('rep_rel_dst',TINYINT,primary_key=False)
    event_id = Column('event_id',BINARY(16),ForeignKey('acid_event.id'),primary_key=True)
    rep_rel_src = Column('rep_rel_src',TINYINT,primary_key=False)
    rep_prio_dst = Column('rep_prio_dst',TINYINT,primary_key=False)
    rep_act_dst = Column('rep_act_dst',VARCHAR(64),primary_key=False)
    rep_ip_src = Column('rep_ip_src',VARBINARY(16),primary_key=False)
    rep_act_src = Column('rep_act_src',VARCHAR(64),primary_key=False)
    rep_prio_src = Column('rep_prio_src',TINYINT,primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'rep_ip_dst':get_ip_str_from_bytes(self.rep_ip_dst),
          'rep_rel_dst':self.rep_rel_dst,
          'event_id':get_uuid_string_from_bytes(self.event_id),
          'rep_rel_src':self.rep_rel_src,
          'rep_prio_dst':self.rep_prio_dst,
          'rep_act_dst':self.rep_act_dst,
          'rep_ip_src':get_ip_str_from_bytes(self.rep_ip_src),
          'rep_act_src':self.rep_act_src,
          'rep_prio_src':self.rep_prio_src,
        }
class Reference (Base):
    __tablename__='reference'
    ref_system_id = Column('ref_system_id',INTEGER(10),ForeignKey('reference_system.ref_system_id'),primary_key=False)
    ref_tag = Column('ref_tag',TEXT,primary_key=False)
    ref_id = Column('ref_id',INTEGER(10),primary_key=True)
    #
    # Relations:
    #
    #sig_reference=relationship('Sig_Reference',backref='reference', primaryjoin='ref_id == Sig_Reference.ref_id' ,lazy='dynamic')
    @property
    def serialize(self):
        return {
          'ref_system_id':self.ref_system_id,
          'ref_tag':self.ref_tag,
          'ref_id':self.ref_id,
          #'sig_reference': [i.serialize for i in self.sig_reference],
        }
# class Idm_Data (Base):
#     __tablename__='idm_data'
#     event_id = Column('event_id',BINARY(16),ForeignKey('acid_event.id'),primary_key=True)
#     username = Column('username',VARCHAR(64),primary_key=True)
#     domain = Column('domain',VARCHAR(64),primary_key=False)
#     from_src = Column('from_src',TINYINT(1),primary_key=True)
#     #
#     # Relations:
#     #
#     @property
#     def serialize(self):
#         return {
#           'event_id':get_uuid_string_from_bytes(self.event_id),
#           'username':self.username,
#           'domain':self.domain,
#           'from_src':self.from_src,
#         }
class Device (Base):
    __tablename__ = 'device'
    interface = Column('interface', VARCHAR(32), primary_key=False)
    device_ip = Column('device_ip', VARBINARY(16), primary_key=False)
    sensor_id = Column('sensor_id', BINARY(16), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    #
    # Relations:
    #

    @property
    def serialize(self):
        """
        Converts the object in a serializable object

        :return: A dict object with all the row field.
        """
        hash_table = {
            'interface':str(self.interface),
            'device_ip': get_ip_str_from_bytes(self.device_ip),
            'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
            'id': str(self.id),
            }
        return hash_table


class Acid_Event (Base):
    __tablename__='acid_event'
    ip_dst = Column('ip_dst',VARBINARY(16),primary_key=False)
    dst_hostname = Column('dst_hostname',VARCHAR(64),primary_key=False)
    src_hostname = Column('src_hostname',VARCHAR(64),primary_key=False)
    plugin_sid = Column('plugin_sid', INTEGER,ForeignKey('ac_acid_event.plugin_sid'),primary_key=False)
    id = Column('id',BINARY(16),ForeignKey('extra_data.event_id'),primary_key=True)
    ip_src = Column('ip_src',VARBINARY(16),primary_key=False)
    ossim_asset_src = Column('ossim_asset_src',TINYINT,primary_key=False)
    layer4_sport = Column('layer4_sport',SMALLINT,primary_key=False)
    ossim_asset_dst = Column('ossim_asset_dst',TINYINT,primary_key=False)
    plugin_id = Column('plugin_id', INTEGER,ForeignKey('ac_acid_event.plugin_id'),primary_key=False)
    src_mac = Column('src_mac',BINARY(6),primary_key=False)
    dst_mac = Column('dst_mac',BINARY(6),primary_key=False)
    ossim_reliability = Column('ossim_reliability',TINYINT,primary_key=False)
    layer4_dport = Column('layer4_dport',SMALLINT,primary_key=False)
    timestamp = Column('timestamp',DATETIME,ForeignKey('ac_acid_event.day'),primary_key=False)
    tzone = Column('tzone',FLOAT,primary_key=False)
    src_net = Column('src_net',BINARY(16),ForeignKey('ac_acid_event.src_net'),primary_key=False)
    ossim_correlation = Column('ossim_correlation',TINYINT,primary_key=False)
    ossim_priority = Column('ossim_priority',TINYINT,primary_key=False)
    dst_net = Column('dst_net',BINARY(16),ForeignKey('ac_acid_event.dst_net'),primary_key=False)
    device_id = Column('device_id', INTEGER,ForeignKey('device.id'),primary_key=False)
    ossim_risk_c = Column('ossim_risk_c',TINYINT,primary_key=False)
    ossim_risk_a = Column('ossim_risk_a',TINYINT,primary_key=False)
    ctx = Column('ctx',BINARY(16),ForeignKey('ac_acid_event.ctx'),primary_key=False)
    dst_host = Column('dst_host',BINARY(16),ForeignKey('ac_acid_event.dst_host'),primary_key=False)
    ip_proto = Column('ip_proto', INTEGER,primary_key=False)
    src_host = Column('src_host',BINARY(16),ForeignKey('ac_acid_event.src_host'),primary_key=False)
    #
    # Relations:
    #
    #reputation_data=relationship('Reputation_Data',backref='acid_event',primaryjoin='id == Reputation_Data.event_id',uselist=False)
    #idm_data=relationship('Idm_Data',backref='acid_event', primaryjoin='id == Idm_Data.event_id' ,lazy='dynamic')
    device = relationship('Device',backref='acid_event', primaryjoin=device_id == Device.id)
    @property
    def serialize(self):
        return {
          'ip_dst':get_ip_str_from_bytes(self.ip_dst),
          'dst_hostname':self.dst_hostname,
          'src_hostname':self.src_hostname,
          'plugin_sid':self.plugin_sid,
          'id':get_uuid_string_from_bytes(self.id),
          'ip_src':get_ip_str_from_bytes(self.ip_src),
          'ossim_asset_src':self.ossim_asset_src,
          'layer4_sport':self.layer4_sport,
          'ossim_asset_dst':self.ossim_asset_dst,
          'plugin_id':self.plugin_id,
          'src_mac':self.src_mac,
          'dst_mac':self.dst_mac,
          'ossim_reliability':self.ossim_reliability,
          'layer4_dport':self.layer4_dport,
          'timestamp':self.timestamp,
          'tzone':self.tzone,
          'src_net':get_uuid_string_from_bytes(self.src_net),
          'ossim_correlation':self.ossim_correlation,
          'ossim_priority':self.ossim_priority,
          'dst_net':get_uuid_string_from_bytes(self.dst_net),
          'device_id':self.device_id,
          'ossim_risk_c':self.ossim_risk_c,
          'ossim_risk_a':self.ossim_risk_a,
          'ctx':get_uuid_string_from_bytes(self.ctx),
          'dst_host':get_uuid_string_from_bytes(self.dst_host),
          'ip_proto':self.ip_proto,
          'src_host':get_uuid_string_from_bytes(self.src_host),
          'device':self.device.serialize
          #'reputation_data': [i.serialize for i in self.reputation_data],
          #'idm_data': [i.serialize for i in self.idm_data],
        }
class Reference_System (Base):
    __tablename__='reference_system'
    url = Column('url',VARCHAR(255),primary_key=False)
    ref_system_id = Column('ref_system_id',INTEGER(10),primary_key=True)
    ref_system_name = Column('ref_system_name',VARCHAR(20),primary_key=False)
    icon = Column('icon',MEDIUMBLOB,primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'url':self.url,
          'ref_system_id':self.ref_system_id,
          'ref_system_name':self.ref_system_name,
          'icon':self.icon,
        }
#NOTE: No primary key defined
# class Last_Update (Base):
#     __tablename__='last_update'
#     date = Column('date',TIMESTAMP,primary_key=False)
#     #
#     # Relations:
#     #
#     @property
#     def serialize(self):
#         return {
#           'date':self.date,
#         }
class Sig_Reference (Base):
    __tablename__='sig_reference'
    plugin_id = Column('plugin_id',INTEGER(11),primary_key=True)
    ctx = Column('ctx',BINARY(16),primary_key=True)
    plugin_sid = Column('plugin_sid',INTEGER(11),primary_key=True)
    ref_id = Column('ref_id',INTEGER(10),ForeignKey('reference.ref_id'),primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'plugin_id':self.plugin_id,
          'ctx':get_uuid_string_from_bytes(self.ctx),
          'plugin_sid':self.plugin_sid,
          'ref_id':self.ref_id,
        }

# defined at alienvault too. 
#InvalidRequestError: Table 'extra_data' is already defined for this MetaData instance.  Specify 'extend_existing=True' to redefine options and columns on an existing Table object.

# class Extra_Data (Base):
#     __tablename__='extra_data'
#     username = Column('username',VARCHAR(64),primary_key=False)
#     userdata2 = Column('userdata2',VARCHAR(1024),primary_key=False)
#     userdata1 = Column('userdata1',VARCHAR(1024),primary_key=False)
#     userdata6 = Column('userdata6',VARCHAR(1024),primary_key=False)
#     userdata7 = Column('userdata7',VARCHAR(1024),primary_key=False)
#     userdata4 = Column('userdata4',VARCHAR(1024),primary_key=False)
#     userdata3 = Column('userdata3',VARCHAR(1024),primary_key=False)
#     event_id = Column('event_id',BINARY(16),primary_key=True)
#     userdata8 = Column('userdata8',VARCHAR(1024),primary_key=False)
#     userdata5 = Column('userdata5',VARCHAR(1024),primary_key=False)
#     data_payload = Column('data_payload',TEXT,primary_key=False)
#     filename = Column('filename',VARCHAR(256),primary_key=False)
#     userdata9 = Column('userdata9',VARCHAR(1024),primary_key=False)
#     password = Column('password',VARCHAR(64),primary_key=False)
#     binary_data = Column('binary_data',BLOB,primary_key=False)
#     #
#     # Relations:
#     #
#     @property
#     def serialize(self):
#         return {
#           'username':self.username,
#           'userdata2':self.userdata2,
#           'userdata1':self.userdata1,
#           'userdata6':self.userdata6,
#           'userdata7':self.userdata7,
#           'userdata4':self.userdata4,
#           'userdata3':self.userdata3,
#           'event_id':get_uuid_string_from_bytes(self.event_id),
#           'userdata8':self.userdata8,
#           'userdata5':self.userdata5,
#           'data_payload':self.data_payload,
#           'filename':self.filename,
#           'userdata9':self.userdata9,
#           'password':self.password,
#           'binary_data':self.binary_data,
#         }
class Schema (Base):
    __tablename__='schema'
    ctime = Column('ctime',DATETIME,primary_key=False)
    vseq = Column('vseq',INTEGER(10),primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'ctime':self.ctime,
          'vseq':self.vseq,
        }
