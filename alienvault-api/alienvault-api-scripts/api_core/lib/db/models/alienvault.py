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

from flask.ext.login import UserMixin

from sqlalchemy import Column, ForeignKey
from sqlalchemy.orm import sessionmaker, relationship
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.dialects.mysql import BIGINT, BINARY, BIT, BLOB, BOOLEAN, CHAR, \
    DATE, DATETIME, DECIMAL, DECIMAL, DOUBLE, ENUM, FLOAT, INTEGER, LONGBLOB, \
    LONGTEXT, MEDIUMBLOB, MEDIUMINT, MEDIUMTEXT, NCHAR, NUMERIC, NVARCHAR, \
    REAL, SET, SMALLINT, TEXT, TIME, TIMESTAMP, TINYBLOB, TINYINT, TINYTEXT, \
    VARBINARY, VARCHAR, YEAR

from apimethods.utils import get_uuid_string_from_bytes, \
         get_ip_str_from_bytes, get_mac_str_from_bytes

import db

Base = declarative_base(bind=db.get_engine(database='alienvault'))


class Rrd_Anomalies_Global (Base):
    __tablename__ = 'rrd_anomalies_global'
    count = Column('count', INTEGER(11), primary_key=False)
    anomaly_range = Column('anomaly_range', VARCHAR(30), primary_key=False)
    what = Column('what', VARCHAR(100), primary_key=False)
    over = Column('over', INTEGER(11), primary_key=False)
    acked = Column('acked', INTEGER(11), primary_key=False)
    anomaly_time = Column('anomaly_time', VARCHAR(40), primary_key=True)
    #
    # Relations:
    #

    @property
    def serialize(self):
        return {
          'count': self.count,
          'anomaly_range': self.anomaly_range,
          'what': self.what,
          'over': self.over,
          'acked': self.acked,
          'anomaly_time': self.anomaly_time,
        }


class Vuln_Nessus_Report_Stats (Base):
    __tablename__ = 'vuln_nessus_report_stats'
    iScantime = Column('iScantime', DECIMAL(4, 0), primary_key=False)
    dtLastScanned = Column('dtLastScanned', DATETIME, primary_key=False)
    vExceptions = Column('vExceptions', INTEGER(6), primary_key=False)
    name = Column('name', VARCHAR(25), primary_key=False)
    trend = Column('trend', INTEGER(4), primary_key=False)
    vMedLow = Column('vMedLow', INTEGER(6), primary_key=False)
    vHigh = Column('vHigh', INTEGER(6), primary_key=False)
    dtLastUpdated = Column('dtLastUpdated', DATETIME, primary_key=False)
    vLowMed = Column('vLowMed', INTEGER(6), primary_key=False)
    vSerious = Column('vSerious', INTEGER(6), primary_key=False)
    vMed = Column('vMed', INTEGER(6), primary_key=False)
    vInfo = Column('vInfo', INTEGER(6), primary_key=False)
    iHostCnt = Column('iHostCnt', INTEGER(4), primary_key=False)
    vLow = Column('vLow', INTEGER(6), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    report_id = Column('report_id', INTEGER(11), ForeignKey('vuln_nessus_reports.report_id'), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'iScantime': self.iScantime,
          'dtLastScanned': self.dtLastScanned,
          'vExceptions': self.vExceptions,
          'name': self.name,
          'trend': self.trend,
          'vMedLow': self.vMedLow,
          'vHigh': self.vHigh,
          'dtLastUpdated': self.dtLastUpdated,
          'vLowMed': self.vLowMed,
          'vSerious': self.vSerious,
          'vMed': self.vMed,
          'vInfo': self.vInfo,
          'iHostCnt': self.iHostCnt,
          'vLow': self.vLow,
          'id': self.id,
          'report_id': self.report_id,
        }


class Protocol (Base):
    __tablename__='protocol'
    alias = Column('alias', VARCHAR(24), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    name = Column('name', VARCHAR(24), primary_key=False)
    #
    # Relations:
    #
    #host_services=relationship('Host_Services', backref='protocol', primaryjoin='id == Host_Services.protocol' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'alias': self.alias,
          'id': self.id,
          'descr': self.descr,
          'name': self.name,
          #'host_services': [i.serialize for i in self.host_services],
        }


class Dashboard_Custom_Type (Base):
    __tablename__='dashboard_custom_type'
    category = Column('category', VARCHAR(128), primary_key=False)
    name = Column('name', VARCHAR(128), primary_key=False)
    thumb = Column('thumb', VARCHAR(128), primary_key=False)
    params = Column('params', TEXT, primary_key=False)
    file = Column('file', VARCHAR(128), primary_key=False)
    title_default = Column('title_default', VARCHAR(128), primary_key=False)
    type = Column('type', VARCHAR(128), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    help_default = Column('help_default', TEXT, primary_key=False)
    #
    # Relations:
    #
    #dashboard_widget_config=relationship('Dashboard_Widget_Config', backref='dashboard_custom_type', primaryjoin='id == Dashboard_Widget_Config.type_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'category': self.category,
          'name': self.name,
          'thumb': self.thumb,
          'params': self.params,
          'file': self.file,
          'title_default': self.title_default,
          'type': self.type,
          'id': self.id,
          'help_default': self.help_default,
          #'dashboard_widget_config': [i.serialize for i in self.dashboard_widget_config],
        }


class Policy_Port_Reference (Base):
    __tablename__='policy_port_reference'
    port_group_id = Column('port_group_id', INTEGER(10), ForeignKey('port_group.id'), primary_key=True)
    direction = Column('direction', ENUM('source','dest'), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'port_group_id': self.port_group_id,
          'direction': self.direction,
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Net_Group_Reference (Base):
    __tablename__='net_group_reference'
    net_group_id = Column('net_group_id', BINARY(16), ForeignKey('net_group.id'), primary_key=True)
    net_id = Column('net_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'net_group_id': get_uuid_string_from_bytes(self.net_group_id),
          'net_id': get_uuid_string_from_bytes(self.net_id),
        }


class Tags_Alarm (Base):
    __tablename__='tags_alarm'
    name = Column('name', VARCHAR(128), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    fgcolor = Column('fgcolor', VARCHAR(7), primary_key=False)
    bgcolor = Column('bgcolor', VARCHAR(7), primary_key=False)
    italic = Column('italic', INTEGER(1), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    bold = Column('bold', TINYINT(1), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'name': self.name,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'fgcolor': self.fgcolor,
          'bgcolor': self.bgcolor,
          'italic': self.italic,
          'id': self.id,
          'bold': self.bold,
        }


class Vuln_Nessus_Settings_Family (Base):
    __tablename__='vuln_nessus_settings_family'
    status = Column('status', INTEGER(11), primary_key=False)
    fid = Column('fid', INTEGER(11), ForeignKey('vuln_nessus_family.id'), primary_key=True)
    sid = Column('sid', INTEGER(11), ForeignKey('vuln_nessus_settings.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'status': self.status,
          'fid': self.fid,
          'sid': self.sid,
        }


class Alarm_Groups (Base):
    __tablename__='alarm_groups'
    status = Column('status', ENUM('open','closed'), primary_key=False)
    timestamp = Column('timestamp', TIMESTAMP, primary_key=False)
    group_id = Column('group_id', VARCHAR(255), primary_key=True)
    description = Column('description', TEXT, primary_key=False)
    owner = Column('owner', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'status': self.status,
          'timestamp': self.timestamp,
          'group_id': self.group_id,
          'description': self.description,
          'owner': self.owner,
        }


class Plugin_Group_Descr (Base):
    __tablename__='plugin_group_descr'
    plugin_ctx = Column('plugin_ctx', BINARY(16), primary_key=True)
    group_ctx = Column('group_ctx', BINARY(16), ForeignKey('plugin_group.group_ctx'), primary_key=True)
    group_id = Column('group_id', BINARY(16), ForeignKey('plugin_group.group_id'), primary_key=True)
    plugin_id = Column('plugin_id', INTEGER(11), primary_key=True, autoincrement=False, index=True)
    plugin_sid = Column('plugin_sid', TEXT, primary_key=False, autoincrement=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'plugin_ctx': get_uuid_string_from_bytes(self.plugin_ctx),
          'group_ctx': get_uuid_string_from_bytes(self.group_ctx),
          'group_id': get_uuid_string_from_bytes(self.group_id),
          'plugin_id': self.plugin_id,
          'plugin_sid': self.plugin_sid,
        }
        

class Vuln_Nessus_Settings_Plugins (Base):
    __tablename__='vuln_nessus_settings_plugins'
    category = Column('category', INTEGER(11), primary_key=False)
    enabled = Column('enabled', CHAR(1), primary_key=False)
    id = Column('id', INTEGER(11), ForeignKey('vuln_nessus_plugins.id'), primary_key=True)
    family = Column('family', INTEGER(11), primary_key=False)
    sid = Column('sid', INTEGER(11), ForeignKey('vuln_nessus_settings.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'category': self.category,
          'enabled': self.enabled,
          'id': self.id,
          'family': self.family,
          'sid': self.sid,
        }


class User_Config (Base):
    __tablename__='user_config'
    category = Column('category', VARCHAR(64), primary_key=True)
    login = Column('login', VARCHAR(64), ForeignKey('users.login'), primary_key=True)
    name = Column('name', VARCHAR(255), primary_key=True)
    value = Column('value', MEDIUMTEXT, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'category': self.category,
          'login': self.login,
          'name': self.name,
          'value': self.value,
        }


class Subcategory (Base):
    __tablename__='subcategory'
    cat_id = Column('cat_id', INTEGER, ForeignKey('category.id'), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', INTEGER, ForeignKey('subcategory_changes.id'), primary_key=True)
    name = Column('name', VARCHAR(100), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'cat_id': self.cat_id,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'name': self.name,
        }


class Alarm_Nets (Base):
    __tablename__='alarm_nets'
    id_alarm = Column('id_alarm', BINARY(16), ForeignKey('alarm.backlog_id'), primary_key=True)
    id_net = Column('id_net', BINARY(16), ForeignKey('net.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id_alarm': get_uuid_string_from_bytes(self.id_alarm),
          'id_net': get_uuid_string_from_bytes(self.id_net),
        }


class Incident_File (Base):
    __tablename__='incident_file'
    incident_id = Column('incident_id', INTEGER, ForeignKey('incident.id'), primary_key=False)
    name = Column('name', VARCHAR(50), primary_key=False)
    content = Column('content', MEDIUMBLOB, primary_key=False)
    incident_ticket = Column('incident_ticket', INTEGER, ForeignKey('incident_ticket.id'), primary_key=False)
    type = Column('type', VARCHAR(50), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'incident_id': self.incident_id,
          'name': self.name,
          'content': self.content,
          'incident_ticket': self.incident_ticket,
          'type': self.type,
          'id': self.id,
        }

class Policy_Idm_Reference (Base):
    __tablename__='policy_idm_reference'
    username = Column('username', VARCHAR(64), primary_key=False)
    domain = Column('domain', VARCHAR(64), primary_key=False)
    from_src = Column('from_src', TINYINT(1), primary_key=False)
    hostname = Column('hostname', VARCHAR(64), primary_key=False)
    mac = Column('mac', BINARY(6), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'username': self.username,
          'domain': self.domain,
          'from_src': self.from_src,
          'hostname': self.hostname,
          'mac': self.mac,
          'id': self.id,
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Net_Scan (Base):
    __tablename__='net_scan'
    plugin_id = Column('plugin_id', INTEGER(11), primary_key=True)
    net_id = Column('net_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    plugin_sid = Column('plugin_sid', INTEGER(11), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'plugin_id': self.plugin_id,
          'net_id': get_uuid_string_from_bytes(self.net_id),
          'plugin_sid': self.plugin_sid,
        }


class Host_Source_Reference (Base):
    __tablename__='host_source_reference'
    relevance = Column('relevance', SMALLINT, primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    name = Column('name', VARCHAR(100), primary_key=False)
    #
    # Relations:
    #
    #host_services=relationship('Host_Services', backref='host_source_reference', primaryjoin='id == Host_Services.source_id' , lazy='dynamic')
    #task_inventory=relationship('Task_Inventory', backref='host_source_reference', primaryjoin='id == Task_Inventory.task_type' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'relevance': self.relevance,
          'id': self.id,
          'name': self.name,
          #'host_services': [i.serialize for i in self.host_services],
          #'task_inventory': [i.serialize for i in self.task_inventory],
        }


class Log_Action (Base):
    __tablename__='log_action'
    info = Column('info', VARCHAR(255), primary_key=True)
    code = Column('code', INTEGER(10), primary_key=True, autoincrement=False)
    ipfrom = Column('ipfrom', VARBINARY(16), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    date = Column('date', TIMESTAMP, primary_key=True)
    login = Column('login', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'info': self.info,
          'code': self.code,
          'ipfrom': get_ip_str_from_bytes(self.ipfrom),
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'date': self.date,
          'login': self.login,
        }


class Incident_Ticket (Base):
    __tablename__='incident_ticket'
    status = Column('status', ENUM('Open','Assigned','Studying','Waiting','Testing','Closed'), primary_key=False)
    incident_id = Column('incident_id', INTEGER, ForeignKey('incident.id'), primary_key=False)
    users = Column('users', VARCHAR(64), primary_key=False)
    transferred = Column('transferred', VARCHAR(64), primary_key=False)
    in_charge = Column('in_charge', VARCHAR(64), primary_key=False)
    priority = Column('priority', INTEGER(11), primary_key=False)
    action = Column('action', TEXT, primary_key=False)
    date = Column('date', DATETIME, primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    description = Column('description', TEXT, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'status': self.status,
          'incident_id': self.incident_id,
          'users': self.users,
          'transferred': self.transferred,
          'in_charge': self.in_charge,
          'priority': self.priority,
          'action': self.action,
          'date': self.date,
          'id': self.id,
          'description': self.description,
        }


class Policy (Base):
    __tablename__='policy'
    group = Column('group', BINARY(16), ForeignKey('policy_group.id'), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    priority = Column('priority', SMALLINT(6), primary_key=False)
    active = Column('active', INTEGER(11), primary_key=False)
    order = Column('order', INTEGER, primary_key=False)
    permissions = Column('permissions', BINARY(8), primary_key=False)
    #
    # Relations:
    #
    #policy_actions=relationship('Policy_Actions', backref='policy', primaryjoin='id == Policy_Actions.policy_id' , lazy='dynamic')
    #policy_extra_data_reference=relationship('Policy_Extra_Data_Reference', backref='policy', primaryjoin='id == Policy_Extra_Data_Reference.policy_id' , lazy='dynamic')
    #policy_forward_reference=relationship('Policy_Forward_Reference', backref='policy', primaryjoin='id == Policy_Forward_Reference.policy_id' , lazy='dynamic')
    #policy_host_group_reference=relationship('Policy_Host_Group_Reference', backref='policy', primaryjoin='id == Policy_Host_Group_Reference.policy_id' , lazy='dynamic')
    #policy_host_reference=relationship('Policy_Host_Reference', backref='policy', primaryjoin='id == Policy_Host_Reference.policy_id' , lazy='dynamic')
    #policy_idm_reference=relationship('Policy_Idm_Reference', backref='policy', primaryjoin='id == Policy_Idm_Reference.policy_id' , lazy='dynamic')
    #policy_net_group_reference=relationship('Policy_Net_Group_Reference', backref='policy', primaryjoin='id == Policy_Net_Group_Reference.policy_id' , lazy='dynamic')
    #policy_net_reference=relationship('Policy_Net_Reference', backref='policy', primaryjoin='id == Policy_Net_Reference.policy_id' , lazy='dynamic')
    #policy_plugin_group_reference=relationship('Policy_Plugin_Group_Reference', backref='policy', primaryjoin='id == Policy_Plugin_Group_Reference.policy_id' , lazy='dynamic')
    #policy_port_reference=relationship('Policy_Port_Reference', backref='policy', primaryjoin='id == Policy_Port_Reference.policy_id' , lazy='dynamic')
    #policy_reputation_reference=relationship('Policy_Reputation_Reference', backref='policy', primaryjoin='id == Policy_Reputation_Reference.policy_id' , lazy='dynamic')
    #policy_risk_reference=relationship('Policy_Risk_Reference', backref='policy', primaryjoin='id == Policy_Risk_Reference.policy_id' , lazy='dynamic')
    #policy_role_reference=relationship('Policy_Role_Reference', backref='policy', primaryjoin='id == Policy_Role_Reference.policy_id' , lazy='dynamic')
    #policy_sensor_reference=relationship('Policy_Sensor_Reference', backref='policy', primaryjoin='id == Policy_Sensor_Reference.policy_id' , lazy='dynamic')
    #policy_target_reference=relationship('Policy_Target_Reference', backref='policy', primaryjoin='id == Policy_Target_Reference.policy_id' , lazy='dynamic')
    #policy_taxonomy_reference=relationship('Policy_Taxonomy_Reference', backref='policy', primaryjoin='id == Policy_Taxonomy_Reference.policy_id' , lazy='dynamic')
    #policy_time_reference=relationship('Policy_Time_Reference', backref='policy', primaryjoin='id == Policy_Time_Reference.policy_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'group': get_uuid_string_from_bytes(self.group),
          'descr': self.descr,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': get_uuid_string_from_bytes(self.id),
          'priority': self.priority,
          'active': self.active,
          'order': self.order,
          'permissions': self.permissions,
          #'policy_actions': [i.serialize for i in self.policy_actions],
          #'policy_extra_data_reference': [i.serialize for i in self.policy_extra_data_reference],
          #'policy_forward_reference': [i.serialize for i in self.policy_forward_reference],
          #'policy_host_group_reference': [i.serialize for i in self.policy_host_group_reference],
          #'policy_host_reference': [i.serialize for i in self.policy_host_reference],
          #'policy_idm_reference': [i.serialize for i in self.policy_idm_reference],
          #'policy_net_group_reference': [i.serialize for i in self.policy_net_group_reference],
          #'policy_net_reference': [i.serialize for i in self.policy_net_reference],
          #'policy_plugin_group_reference': [i.serialize for i in self.policy_plugin_group_reference],
          #'policy_port_reference': [i.serialize for i in self.policy_port_reference],
          #'policy_reputation_reference': [i.serialize for i in self.policy_reputation_reference],
          #'policy_risk_reference': [i.serialize for i in self.policy_risk_reference],
          #'policy_role_reference': [i.serialize for i in self.policy_role_reference],
          #'policy_sensor_reference': [i.serialize for i in self.policy_sensor_reference],
          #'policy_target_reference': [i.serialize for i in self.policy_target_reference],
          #'policy_taxonomy_reference': [i.serialize for i in self.policy_taxonomy_reference],
          #'policy_time_reference': [i.serialize for i in self.policy_time_reference],
        }


class Extra_Data (Base):
    __tablename__='extra_data'
    event_id = Column('event_id', BINARY(16), primary_key=True)
    binary_data = Column('binary_data', BLOB, primary_key=False)
    data_payload = Column('data_payload', TEXT, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'event_id': get_uuid_string_from_bytes(self.event_id),
          'binary_data': self.binary_data,
          'data_payload': self.data_payload,
        }


class Corr_Engine_Contexts (Base):
    __tablename__='corr_engine_contexts'
    engine_ctx = Column('engine_ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    event_ctx = Column('event_ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    descr = Column('descr', VARCHAR(128), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'engine_ctx': get_uuid_string_from_bytes(self.engine_ctx),
          'event_ctx': get_uuid_string_from_bytes(self.event_ctx),
          'descr': self.descr,
        }

class Server_Hierarchy (Base):
    __tablename__='server_hierarchy'
    parent_id = Column('parent_id', BINARY(16), ForeignKey('server.id'), primary_key=True)
    child_id = Column('child_id', BINARY(16), ForeignKey('server.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'parent_id': get_uuid_string_from_bytes(self.parent_id),
          'child_id': get_uuid_string_from_bytes(self.child_id),
        }
class Vuln_Nessus_Latest_Reports (Base):
    __tablename__='vuln_nessus_latest_reports'
    username = Column('username', VARCHAR(255), primary_key=True, index=True)
    deleted = Column('deleted', TINYINT(1), primary_key=False)
    domain = Column('domain', VARCHAR(255), primary_key=False)
    scantype = Column('scantype', CHAR(1), primary_key=False)
    server_ip = Column('server_ip', VARBINARY(16), primary_key=False)
    failed = Column('failed', TINYINT(1), primary_key=False)
    scantime = Column('scantime', VARCHAR(14), primary_key=False)
    cred_used = Column('cred_used', VARCHAR(25), primary_key=False)
    hostIP = Column('hostIP', VARCHAR(40), primary_key=True)
    ctx = Column('ctx', BINARY(16), primary_key=True)
    server_feedtype = Column('server_feedtype', VARCHAR(32), primary_key=False)
    note = Column('note', TEXT, primary_key=False)
    server_nversion = Column('server_nversion', VARCHAR(100), primary_key=False)
    results_sent = Column('results_sent', INTEGER(11), primary_key=False)
    report_path = Column('report_path', VARCHAR(255), primary_key=False)
    report_type = Column('report_type', CHAR(1), primary_key=False)
    sid = Column('sid', INTEGER(11), primary_key=True, autoincrement=False, index=True)
    fk_name = Column('fk_name', VARCHAR(50), primary_key=False)
    report_key = Column('report_key', VARCHAR(16), primary_key=False)
    server_feedversion = Column('server_feedversion', VARCHAR(12), primary_key=False)
    #
    # Relations:
    #
    #vuln_nessus_latest_results=relationship('Vuln_Nessus_Latest_Results', backref='vuln_nessus_latest_reports', primaryjoin='sid == Vuln_Nessus_Latest_Results.sid' , lazy='dynamic')
#TODO: ---#    #vuln_nessus_latest_results=relationship('Vuln_Nessus_Latest_Results', backref='vuln_nessus_latest_reports', primaryjoin='username == Vuln_Nessus_Latest_Results.sid' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'username': self.username,
          'deleted': self.deleted,
          'domain': self.domain,
          'scantype': self.scantype,
          'server_ip': get_ip_str_from_bytes(self.server_ip),
          'failed': self.failed,
          'scantime': self.scantime,
          'cred_used': self.cred_used,
          'hostIP': self.hostIP,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'server_feedtype': self.server_feedtype,
          'note': self.note,
          'server_nversion': self.server_nversion,
          'results_sent': self.results_sent,
          'report_path': self.report_path,
          'report_type': self.report_type,
          'sid': self.sid,
          'fk_name': self.fk_name,
          'report_key': self.report_key,
          'server_feedversion': self.server_feedversion,
          #'vuln_nessus_latest_results': [i.serialize for i in self.vuln_nessus_latest_results],
          #'vuln_nessus_latest_results': [i.serialize for i in self.vuln_nessus_latest_results],
        }


class Incident_Vulns (Base):
    __tablename__='incident_vulns'
    incident_id = Column('incident_id', INTEGER, ForeignKey('incident.id'), primary_key=True)
    risk = Column('risk', VARCHAR(255), primary_key=False)
    ip = Column('ip', VARCHAR(40), primary_key=False)
    ctx = Column('ctx', BINARY(16), primary_key=False)
    id = Column('id', INTEGER, primary_key=True, autoincrement=False)
    nessus_id = Column('nessus_id', VARCHAR(255), primary_key=False)
    port = Column('port', VARCHAR(255), primary_key=False)
    description = Column('description', TEXT, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'incident_id': self.incident_id,
          'risk': self.risk,
          'ip': self.ip,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'nessus_id': self.nessus_id,
          'port': self.port,
          'description': self.description,
        }


class Repository_Attachments (Base):
    __tablename__='repository_attachments'
    id_document = Column('id_document', INTEGER(11), primary_key=False)
    type = Column('type', VARCHAR(4), primary_key=False)
    id = Column('id', INTEGER(10), ForeignKey('repository.id'), primary_key=True)
    name = Column('name', VARCHAR(256), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id_document': self.id_document,
          'type': self.type,
          'id': self.id,
          'name': self.name,
        }


class Plugin_Scheduler_Netgroup_Reference (Base):
    __tablename__='plugin_scheduler_netgroup_reference'
    netgroup_id = Column('netgroup_id', BINARY(16), ForeignKey('net_group.id'), primary_key=True)
    plugin_scheduler_id = Column('plugin_scheduler_id', BINARY(16), ForeignKey('plugin_scheduler.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'netgroup_id': get_uuid_string_from_bytes(self.netgroup_id),
          'plugin_scheduler_id': get_uuid_string_from_bytes(self.plugin_scheduler_id),
        }


class Subcategory_Changes (Base):
    __tablename__='subcategory_changes'
    cat_id = Column('cat_id', INTEGER, ForeignKey('category.id'), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    name = Column('name', VARCHAR(100), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'cat_id': self.cat_id,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'name': self.name,
        }


class Host_Ip (Base):
    __tablename__='host_ip'
    interface = Column('interface', VARCHAR(32), primary_key=False)
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    mac = Column('mac', BINARY(6), ForeignKey('host_mac_vendors.mac'), primary_key=False)
    ip = Column('ip', VARBINARY(16), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'interface': self.interface,
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'mac': get_mac_str_from_bytes(self.mac),
          'ip': get_ip_str_from_bytes(self.ip),
        }


class Vuln_Nessus_Reports (Base):
    __tablename__='vuln_nessus_reports'
    username = Column('username', VARCHAR(255), primary_key=False)
    domain = Column('domain', VARCHAR(255), primary_key=False)
    scantype = Column('scantype', CHAR(1), primary_key=False)
    name = Column('name', VARCHAR(50), primary_key=False)
    failed = Column('failed', TINYINT(1), primary_key=False)
    scantime = Column('scantime', VARCHAR(14), primary_key=False)
    cred_used = Column('cred_used', VARCHAR(25), primary_key=False)
    results_sent = Column('results_sent', TINYINT(2), primary_key=False)
    deleted = Column('deleted', TINYINT(1), primary_key=False)
    server_feedtype = Column('server_feedtype', VARCHAR(32), primary_key=False)
    note = Column('note', TEXT, primary_key=False)
    server_nversion = Column('server_nversion', VARCHAR(100), primary_key=False)
    server_ip = Column('server_ip', VARBINARY(16), primary_key=False)
    report_path = Column('report_path', VARCHAR(255), primary_key=False)
    report_type = Column('report_type', CHAR(1), primary_key=False)
    sid = Column('sid', INTEGER(11), primary_key=False)
    fk_name = Column('fk_name', VARCHAR(50), primary_key=False)
    report_key = Column('report_key', VARCHAR(16), primary_key=False)
    server_feedversion = Column('server_feedversion', VARCHAR(12), primary_key=False)
    report_id = Column('report_id', INTEGER(11), primary_key=True)
    #
    # Relations:
    #
    #vuln_nessus_results=relationship('Vuln_Nessus_Results', backref='vuln_nessus_reports', primaryjoin='report_id == Vuln_Nessus_Results.report_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'username': self.username,
          'domain': self.domain,
          'scantype': self.scantype,
          'name': self.name,
          'failed': self.failed,
          'scantime': self.scantime,
          'cred_used': self.cred_used,
          'results_sent': self.results_sent,
          'deleted': self.deleted,
          'server_feedtype': self.server_feedtype,
          'note': self.note,
          'server_nversion': self.server_nversion,
          'server_ip': get_ip_str_from_bytes(self.server_ip),
          'report_path': self.report_path,
          'report_type': self.report_type,
          'sid': self.sid,
          'fk_name': self.fk_name,
          'report_key': self.report_key,
          'server_feedversion': self.server_feedversion,
          'report_id': self.report_id,
          #'vuln_nessus_results': [i.serialize for i in self.vuln_nessus_results],
        }


class Host_Vulnerability (Base):
    __tablename__='host_vulnerability'
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    scan_date = Column('scan_date', DATETIME, primary_key=True)
    vulnerability = Column('vulnerability', INTEGER, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'scan_date': self.scan_date,
          'vulnerability': self.vulnerability,
        }


class Map_Element_Seq (Base):
    __tablename__='map_element_seq'
    id = Column('id', INTEGER(10), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id': self.id,
        }


class Vuln_Nessus_Preferences_Defaults (Base):
    __tablename__='vuln_nessus_preferences_defaults'
    category = Column('category', VARCHAR(255), primary_key=False)
    flag = Column('flag', CHAR(1), primary_key=False)
    nessusgroup = Column('nessusgroup', VARCHAR(255), primary_key=False)
    value = Column('value', VARCHAR(255), primary_key=False)
    field = Column('field', VARCHAR(255), primary_key=False)
    nessus_id = Column('nessus_id', VARCHAR(255), primary_key=True)
    type = Column('type', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'category': self.category,
          'flag': self.flag,
          'nessusgroup': self.nessusgroup,
          'value': self.value,
          'field': self.field,
          'nessus_id': self.nessus_id,
          'type': self.type,
        }


class Credentials (Base):
    __tablename__='credentials'
    username = Column('username', TEXT, primary_key=False)
    extra = Column('extra', TEXT, primary_key=False)
    sensor_ip = Column('sensor_ip', VARBINARY(16), primary_key=False)
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=False)
    password = Column('password', TEXT, primary_key=False)
    type = Column('type', INTEGER(11), ForeignKey('credential_type.id'), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'username': self.username,
          'extra': self.extra,
          'sensor_ip': get_ip_str_from_bytes(self.sensor_ip),
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'password': self.password,
          'type': self.type,
          'id': self.id,
        }


class Incident_Metric (Base):
    __tablename__='incident_metric'
    metric_type = Column('metric_type', ENUM('Compromise','Attack','Level'), primary_key=False)
    incident_id = Column('incident_id', INTEGER, ForeignKey('incident.id'), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    metric_value = Column('metric_value', INTEGER(11), primary_key=False)
    target = Column('target', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'metric_type': self.metric_type,
          'incident_id': self.incident_id,
          'id': self.id,
          'metric_value': self.metric_value,
          'target': self.target,
        }


class Port_Group (Base):
    __tablename__='port_group'
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', INTEGER(10), primary_key=True)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    name = Column('name', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    #port_group_reference=relationship('Port_Group_Reference', backref='port_group', primaryjoin='id == Port_Group_Reference.port_group_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'descr': self.descr,
          'name': self.name,
          #'port_group_reference': [i.serialize for i in self.port_group_reference],
        }


class Reputation_Activities (Base):
    __tablename__='reputation_activities'
    id = Column('id', SMALLINT, primary_key=True)
    descr = Column('descr', VARCHAR(128), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id': self.id,
          'descr': self.descr,
        }


class Incident_Tag_Descr_Seq (Base):
    __tablename__='incident_tag_descr_seq'
    id = Column('id', INTEGER(11), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id': self.id,
        }


class Sensor_Interfaces (Base):
    __tablename__='sensor_interfaces'
    interface = Column('interface', VARCHAR(64), primary_key=True)
    main = Column('main', INTEGER(11), primary_key=False)
    sensor_id = Column('sensor_id', BINARY(16), ForeignKey('sensor.id'), primary_key=True)
    name = Column('name', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'interface': self.interface,
          'main': self.main,
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
          'name': self.name,
        }


class Action_Email (Base):
    __tablename__='action_email'
    _from = Column('_from', VARCHAR(255), primary_key=False)
    _to = Column('_to', VARCHAR(255), primary_key=False)
    subject = Column('subject', TEXT, primary_key=False)
    message = Column('message', TEXT, primary_key=False)
    action_id = Column('action_id', BINARY(16), ForeignKey('action.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          '_from': self._from,
          '_to': self._to,
          'subject': self.subject,
          'message': self.message,
          'action_id': get_uuid_string_from_bytes(self.action_id),
        }


class Plugin_Sid_Orig (Base):
    __tablename__='plugin_sid_orig'
    plugin_ctx = Column('plugin_ctx', BINARY(16), ForeignKey('plugin_sid.plugin_ctx'), primary_key=True)
    name = Column('name', VARCHAR(255), primary_key=False)
    class_id = Column('class_id', INTEGER(11), primary_key=False)
    priority = Column('priority', INTEGER(11), primary_key=False)
    subcategory_id = Column('subcategory_id', INTEGER(11), primary_key=False)
    reliability = Column('reliability', INTEGER(11), primary_key=False)
    sid = Column('sid', INTEGER, ForeignKey('plugin_sid.sid'), primary_key=True)
    plugin_id = Column('plugin_id', INTEGER, ForeignKey('plugin_sid.plugin_id'), primary_key=True)
    category_id = Column('category_id', INTEGER(11), primary_key=False)
    aro = Column('aro', DECIMAL(11, 4), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'plugin_ctx': get_uuid_string_from_bytes(self.plugin_ctx),
          'name': self.name,
          'class_id': self.class_id,
          'priority': self.priority,
          'subcategory_id': self.subcategory_id,
          'reliability': self.reliability,
          'sid': self.sid,
          'plugin_id': self.plugin_id,
          'category_id': self.category_id,
          'aro': self.aro,
        }


class Vuln_Nessus_Category (Base):
    __tablename__='vuln_nessus_category'
    id = Column('id', INTEGER(11), primary_key=True)
    name = Column('name', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    #vuln_nessus_settings_category=relationship('Vuln_Nessus_Settings_Category', backref='vuln_nessus_category', primaryjoin='id == Vuln_Nessus_Settings_Category.cid' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'id': self.id,
          'name': self.name,
          #'vuln_nessus_settings_category': [i.serialize for i in self.vuln_nessus_settings_category],
        }

class Sensor_Properties (Base):
    __tablename__='sensor_properties'
    sensor_id = Column('sensor_id', BINARY(16), ForeignKey('sensor.id'), primary_key=True)
    has_nagios = Column('has_nagios', TINYINT(1), primary_key=False)
    has_ntop = Column('has_ntop', TINYINT(1), primary_key=False)
    version = Column('version', VARCHAR(64), primary_key=False)
    has_kismet = Column('has_kismet', TINYINT(1), primary_key=False)
    has_vuln_scanner = Column('has_vuln_scanner', TINYINT(1), primary_key=False)
    ids = Column('ids', TINYINT(1), primary_key=False)
    passive_inventory = Column('passive_inventory', TINYINT(1), primary_key=False)
    netflows = Column('netflows', TINYINT(1), primary_key=False)

    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
          'has_nagios': self.has_nagios,
          'has_ntop': self.has_ntop,
          'version': self.version,
          'has_kismet': self.has_kismet,
          'has_vuln_scanner': self.has_vuln_scanner,
          'ids':self.ids,
          'passive_inventory':self.passive_inventory,
          'netflows':self.netflows
        }


class Sensor_Stats (Base):
    __tablename__='sensor_stats'
    sensor_id = Column('sensor_id', BINARY(16), ForeignKey('sensor.id'), primary_key=True)
    mac_events = Column('mac_events', INTEGER(11), primary_key=False)
    os_events = Column('os_events', INTEGER(11), primary_key=False)
    service_events = Column('service_events', INTEGER(11), primary_key=False)
    ids_events = Column('ids_events', INTEGER(11), primary_key=False)
    events = Column('events', INTEGER(11), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
          'mac_events': self.mac_events,
          'os_events': self.os_events,
          'service_events': self.service_events,
          'ids_events': self.ids_events,
          'events': self.events,
        }


class Locations (Base):
    __tablename__='locations'
    name = Column('name', VARCHAR(64), primary_key=False)
    lon = Column('lon', FLOAT, primary_key=False)
    ctx = Column('ctx', BINARY(16), primary_key=False)
    checks = Column('checks', BINARY(3), primary_key=False)
    location = Column('location', VARCHAR(255), primary_key=False)
    country = Column('country', VARCHAR(2), primary_key=False)
    lat = Column('lat', FLOAT, primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    desc = Column('desc', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    #location_sensor_reference=relationship('Location_Sensor_Reference', backref='locations', primaryjoin='id == Location_Sensor_Reference.location_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'name': self.name,
          'lon': self.lon,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'checks': self.checks,
          'location': self.location,
          'country': self.country,
          'lat': self.lat,
          'id': get_uuid_string_from_bytes(self.id),
          'desc': self.desc,
          #'location_sensor_reference': [i.serialize for i in self.location_sensor_reference],
        }


class Location_Sensor_Reference (Base):
    __tablename__='location_sensor_reference'
    sensor_id = Column('sensor_id', BINARY(16), ForeignKey('sensor.id'), primary_key=True)
    location_id = Column('location_id', BINARY(16), ForeignKey('locations.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
          'location_id': get_uuid_string_from_bytes(self.location_id),
        }


class Sensor (Base):
    __tablename__='sensor'
    name = Column('name', VARCHAR(64), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    ip = Column('ip', VARBINARY(16), primary_key=False)
    tzone = Column('tzone', FLOAT, primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    priority = Column('priority', SMALLINT(6), primary_key=False)
    connect = Column('connect', SMALLINT(6), primary_key=False)
    port = Column('port', INTEGER(11), primary_key=False)
    #
    # Relations:
    #
    sensor_interfaces=relationship('Sensor_Interfaces', backref='sensor', primaryjoin=id == Sensor_Interfaces.sensor_id , lazy='dynamic')
    sensor_properties=relationship('Sensor_Properties', backref='sensor', primaryjoin=id == Sensor_Properties.sensor_id , lazy='dynamic')
    sensor_stats=relationship('Sensor_Stats', backref='sensor', primaryjoin=id == Sensor_Stats.sensor_id , lazy='dynamic')
    #acl_sensors=relationship('Acl_Sensors', backref='sensor', primaryjoin='id == Acl_Sensors.sensor_id' , lazy='dynamic')
    #host_sensor_reference=relationship('Host_Sensor_Reference', backref='sensor', primaryjoin='id == Host_Sensor_Reference.sensor_id' , lazy='dynamic')
    #net_sensor_reference=relationship('Net_Sensor_Reference', backref='sensor', primaryjoin='id == Net_Sensor_Reference.sensor_id' , lazy='dynamic')
    #task_inventory=relationship('Task_Inventory', backref='sensor', primaryjoin='id == Task_Inventory.task_sensor' , lazy='dynamic')
    location_sensor_reference=relationship('Location_Sensor_Reference', backref='sensor', primaryjoin=id == Location_Sensor_Reference.sensor_id , lazy='dynamic')
    #acl_login_sensors=relationship('Acl_Login_Sensors', backref='sensor', primaryjoin='id == Acl_Login_Sensors.sensor_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'name': self.name,
          'descr': self.descr,
          'ip': get_ip_str_from_bytes(self.ip),
          'tzone': self.tzone,
          'id': get_uuid_string_from_bytes(self.id),
          'priority': self.priority,
          'connect': self.connect,
          'port': self.port,
          'sensor_interfaces': [i.serialize for i in self.sensor_interfaces],
          'sensor_properties': [i.serialize for i in self.sensor_properties],
          'sensor_stats': [i.serialize for i in self.sensor_stats],
          #'acl_sensors': [i.serialize for i in self.acl_sensors],
          #'host_sensor_reference': [i.serialize for i in self.host_sensor_reference],
          #'net_sensor_reference': [i.serialize for i in self.net_sensor_reference],
          #'task_inventory': [i.serialize for i in self.task_inventory],
          'location_sensor_reference': [i.serialize for i in self.location_sensor_reference],
          #'acl_login_sensors': [i.serialize for i in self.acl_login_sensors],
        }


class Notes (Base):
    __tablename__='notes'
    asset_id = Column('asset_id', BINARY(16), primary_key=False)
    note = Column('note', TEXT, primary_key=False)
    user = Column('user', VARCHAR(64), primary_key=False)
    date = Column('date', DATETIME, primary_key=False)
    type = Column('type', ENUM('host','net', 'host_group', 'net_group'), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'asset_id': get_uuid_string_from_bytes(self.asset_id),
          'note': self.note,
          'user': self.user,
          'date': self.date,
          'type': self.type,
          'id': self.id,
        }


class Policy_Target_Reference (Base):
    __tablename__='policy_target_reference'
    target_id = Column('target_id', BINARY(16), ForeignKey('server.id'), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'target_id': get_uuid_string_from_bytes(self.target_id),
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Software_Cpe (Base):
    __tablename__='software_cpe'
    line = Column('line', VARCHAR(255), primary_key=False)
    version = Column('version', VARCHAR(255), primary_key=False)
    cpe = Column('cpe', VARCHAR(255), primary_key=True)
    name = Column('name', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'line': self.line,
          'version': self.version,
          'cpe': self.cpe,
          'name': self.name,
        }


class Action_Exec (Base):
    __tablename__='action_exec'
    command = Column('command', TEXT)
    action_id = Column('action_id', BINARY(16), ForeignKey('action.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'command': self.command,
          'action_id': get_uuid_string_from_bytes(self.action_id),
        }


class Custom_Report_Scheduler (Base):
    __tablename__='custom_report_scheduler'
    next_launch = Column('next_launch', DATETIME, primary_key=False)
    id_report = Column('id_report', VARCHAR(100), primary_key=False)
    schedule = Column('schedule', TEXT, primary_key=False)
    schedule_name = Column('schedule_name', VARCHAR(20), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    schedule_type = Column('schedule_type', VARCHAR(5), primary_key=False)
    date_range = Column('date_range', VARCHAR(30), primary_key=False)
    email = Column('email', VARCHAR(255), primary_key=False)
    save_in_repository = Column('save_in_repository', TINYINT(1), primary_key=False)
    user = Column('user', VARCHAR(64), primary_key=False)
    date_to = Column('date_to', DATE, primary_key=False)
    name_report = Column('name_report', VARCHAR(100), primary_key=False)
    assets = Column('assets', TINYTEXT, primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    date_from = Column('date_from', DATE, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'next_launch': self.next_launch,
          'id_report': self.id_report,
          'schedule': self.schedule,
          'schedule_name': self.schedule_name,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'schedule_type': self.schedule_type,
          'date_range': self.date_range,
          'email': self.email,
          'save_in_repository': self.save_in_repository,
          'user': self.user,
          'date_to': self.date_to,
          'name_report': self.name_report,
          'assets': self.assets,
          'id': self.id,
          'date_from': self.date_from,
        }


class Host_Qualification (Base):
    __tablename__='host_qualification'
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    attack = Column('attack', INTEGER(11), primary_key=False)
    compromise = Column('compromise', INTEGER(11), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'attack': self.attack,
          'compromise': self.compromise,
        }


class Incident_Type (Base):
    __tablename__='incident_type'
    keywords = Column('keywords', VARCHAR(255), primary_key=False)
    id = Column('id', VARCHAR(64), primary_key=True)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'keywords': self.keywords,
          'id': self.id,
          'descr': self.descr,
        }


class Action_Type (Base):
    __tablename__='action_type'
    type = Column('type', INTEGER, primary_key=True)
    name = Column('name', ENUM('email','exec','ticket'), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'type': self.type,
          'name': self.name,
          'descr': self.descr,
        }


class Acl_Sensors (Base):
    __tablename__='acl_sensors'
    entity_id = Column('entity_id', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    sensor_id = Column('sensor_id', BINARY(16), ForeignKey('sensor.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'entity_id': get_uuid_string_from_bytes(self.entity_id),
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
        }


class Plugin_Scheduler_Seq (Base):
    __tablename__='plugin_scheduler_seq'
    id = Column('id', INTEGER(11), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id': self.id,
        }


class Backlog (Base):
    __tablename__='backlog'
    last = Column('last', DATETIME, primary_key=False)
    timestamp = Column('timestamp', DATETIME, primary_key=False)
    corr_engine_ctx = Column('corr_engine_ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    directive_id = Column('directive_id', INTEGER(11), primary_key=False)
    id = Column('id', BINARY(16), ForeignKey('alarm.backlog_id'), primary_key=True)
    matched = Column('matched', TINYINT(4), primary_key=False)
    #
    # Relations:
    #
    #backlog_event=relationship('Backlog_Event', backref='backlog', primaryjoin='id == Backlog_Event.backlog_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'last': self.last,
          'timestamp': self.timestamp,
          'corr_engine_ctx': get_uuid_string_from_bytes(self.corr_engine_ctx),
          'directive_id': self.directive_id,
          'id': get_uuid_string_from_bytes(self.id),
          'matched': self.matched,
          #'backlog_event': [i.serialize for i in self.backlog_event],
        }


class Inventory_Search (Base):
    __tablename__='inventory_search'
    ruleorder = Column('ruleorder', INTEGER(11), primary_key=False)
    list = Column('list', VARCHAR(255), primary_key=False)
    subtype = Column('subtype', VARCHAR(32), primary_key=True)
    query = Column('query', TEXT, primary_key=False)
    type = Column('type', VARCHAR(32), primary_key=True)
    match = Column('match', ENUM('text','ip','fixed','boolean','date','number','concat','fixedText'), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'ruleorder': self.ruleorder,
          'list': self.list,
          'subtype': self.subtype,
          'query': self.query,
          'type': self.type,
          'match': self.match,
        }


class Port_Group_Reference (Base):
    __tablename__='port_group_reference'
    protocol_name = Column('protocol_name', VARCHAR(12), ForeignKey('port.protocol_name'), primary_key=True)
    port_group_id = Column('port_group_id', INTEGER(10), ForeignKey('port_group.id'), primary_key=True)
    port_ctx = Column('port_ctx', BINARY(16), ForeignKey('port.ctx'), primary_key=True)
    port_number = Column('port_number', INTEGER(11), ForeignKey('port.port_number'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'protocol_name': self.protocol_name,
          'port_group_id': self.port_group_id,
          'port_ctx': get_uuid_string_from_bytes(self.port_ctx),
          'port_number': self.port_number,
        }


class Host_Types (Base):
    __tablename__='host_types'
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    type = Column('type', INTEGER, ForeignKey('device_types.id'), primary_key=True)
    subtype = Column('subtype', INTEGER, ForeignKey('device_types.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'type': self.type,
          'subtype': self.subtype,
        }


class Acl_Entities_Stats (Base):
    __tablename__='acl_entities_stats'
    stat = Column('stat', FLOAT, primary_key=False)
    entity_id = Column('entity_id', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    ts = Column('ts', TIMESTAMP, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'stat': self.stat,
          'entity_id': get_uuid_string_from_bytes(self.entity_id),
          'ts': self.ts,
        }


class Incident_Anomaly (Base):
    __tablename__='incident_anomaly'
    incident_id = Column('incident_id', INTEGER, ForeignKey('incident.id'), primary_key=False)
    ip = Column('ip', VARCHAR(255), primary_key=False)
    anom_type = Column('anom_type', ENUM('mac','service','os'), primary_key=False)
    data_new = Column('data_new', VARCHAR(255), primary_key=False)
    data_orig = Column('data_orig', VARCHAR(255), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'incident_id': self.incident_id,
          'ip': self.ip,
          'anom_type': self.anom_type,
          'data_new': self.data_new,
          'data_orig': self.data_orig,
          'id': self.id,
        }


class Vuln_Job_Schedule (Base):
    __tablename__='vuln_job_schedule'
    time_interval = Column('time_interval', SMALLINT, primary_key=False)
    job_TYPE = Column('job_TYPE', ENUM('C','M','R','S'), primary_key=False)
    day_of_month = Column('day_of_month', INTEGER(2), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    meth_Wfile = Column('meth_Wfile', TEXT, primary_key=False)
    scan_ASSIGNED = Column('scan_ASSIGNED', VARCHAR(64), primary_key=False)
    meth_TIMEOUT = Column('meth_TIMEOUT', INTEGER(11), primary_key=False)
    resolve_names = Column('resolve_names', TINYINT(1), primary_key=False)
    email = Column('email', TEXT, primary_key=False)
    username = Column('username', VARCHAR(255), primary_key=False)
    IP_ctx = Column('IP_ctx', TEXT, primary_key=False)
    meth_CPLUGINS = Column('meth_CPLUGINS', TEXT, primary_key=False)
    createdate = Column('createdate', DATETIME, primary_key=False)
    meth_Wcheck = Column('meth_Wcheck', TEXT, primary_key=False)
    credentials = Column('credentials', VARCHAR(128), primary_key=False)
    fk_name = Column('fk_name', VARCHAR(50), primary_key=False)
    meth_CUSTOM = Column('meth_CUSTOM', ENUM('N','A','R'), primary_key=False)
    name = Column('name', VARCHAR(255), primary_key=False)
    enabled = Column('enabled', ENUM('0','1'), primary_key=False)
    meth_TARGET = Column('meth_TARGET', TEXT, primary_key=False)
    day_of_week = Column('day_of_week', ENUM('Su','Mo','Tu','We','Th','Fr','Sa'), primary_key=False)
    schedule_type = Column('schedule_type', ENUM('O','D','W','M','NW'), primary_key=False)
    meth_Ucheck = Column('meth_Ucheck', TEXT, primary_key=False)
    next_CHECK = Column('next_CHECK', VARCHAR(14), primary_key=False)
    time = Column('time', TIME, primary_key=False)
    meth_CRED = Column('meth_CRED', INTEGER(11), primary_key=False)
    meth_VSET = Column('meth_VSET', INTEGER(11), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'time_interval': self.time_interval,
          'job_TYPE': self.job_TYPE,
          'day_of_month': self.day_of_month,
          'id': self.id,
          'meth_Wfile': self.meth_Wfile,
          'scan_ASSIGNED': self.scan_ASSIGNED,
          'meth_TIMEOUT': self.meth_TIMEOUT,
          'resolve_names': self.resolve_names,
          'email': self.email,
          'username': self.username,
          'IP_ctx': self.IP_ctx,
          'meth_CPLUGINS': self.meth_CPLUGINS,
          'createdate': self.createdate,
          'meth_Wcheck': self.meth_Wcheck,
          'credentials': self.credentials,
          'fk_name': self.fk_name,
          'meth_CUSTOM': self.meth_CUSTOM,
          'name': self.name,
          'enabled': self.enabled,
          'meth_TARGET': self.meth_TARGET,
          'day_of_week': self.day_of_week,
          'schedule_type': self.schedule_type,
          'meth_Ucheck': self.meth_Ucheck,
          'next_CHECK': self.next_CHECK,
          'time': self.time,
          'meth_CRED': self.meth_CRED,
          'meth_VSET': self.meth_VSET,
        }


class Host_Scan (Base):
    __tablename__='host_scan'
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    plugin_id = Column('plugin_id', INTEGER(11), primary_key=True, autoincrement=False)
    plugin_sid = Column('plugin_sid', INTEGER(11), primary_key=True, autoincrement=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'plugin_id': self.plugin_id,
          'plugin_sid': self.plugin_sid,
        }


class Wireless_Locations (Base):
    __tablename__='wireless_locations'
    description = Column('description', VARCHAR(255), primary_key=False)
    location = Column('location', VARCHAR(100), primary_key=True)
    user = Column('user', VARCHAR(64), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'description': self.description,
          'location': self.location,
          'user': self.user,
        }


class Policy_Extra_Data_Reference (Base):
    __tablename__='policy_extra_data_reference'
    username = Column('username', VARCHAR(128), primary_key=False)
    userdata2 = Column('userdata2', VARCHAR(128), primary_key=False)
    userdata1 = Column('userdata1', VARCHAR(128), primary_key=False)
    userdata6 = Column('userdata6', VARCHAR(128), primary_key=False)
    userdata7 = Column('userdata7', VARCHAR(128), primary_key=False)
    userdata4 = Column('userdata4', VARCHAR(128), primary_key=False)
    userdata3 = Column('userdata3', VARCHAR(128), primary_key=False)
    data_payload = Column('data_payload', VARCHAR(128), primary_key=False)
    userdata8 = Column('userdata8', VARCHAR(128), primary_key=False)
    userdata5 = Column('userdata5', VARCHAR(128), primary_key=False)
    filename = Column('filename', VARCHAR(128), primary_key=False)
    userdata9 = Column('userdata9', VARCHAR(128), primary_key=False)
    password = Column('password', VARCHAR(128), primary_key=False)
    id = Column('id', INTEGER(10), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'username': self.username,
          'userdata2': self.userdata2,
          'userdata1': self.userdata1,
          'userdata6': self.userdata6,
          'userdata7': self.userdata7,
          'userdata4': self.userdata4,
          'userdata3': self.userdata3,
          'data_payload': self.data_payload,
          'userdata8': self.userdata8,
          'userdata5': self.userdata5,
          'filename': self.filename,
          'userdata9': self.userdata9,
          'password': self.password,
          'id': self.id,
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Plugin_Sid (Base):
    __tablename__='plugin_sid'
    plugin_ctx = Column('plugin_ctx', BINARY(16), ForeignKey('plugin_sid_changes.plugin_ctx'), primary_key=True)
    name = Column('name', VARCHAR(512), primary_key=False)
    class_id = Column('class_id', INTEGER(11), primary_key=False)
    priority = Column('priority', INTEGER(11), primary_key=False)
    subcategory_id = Column('subcategory_id', INTEGER, ForeignKey('subcategory.id'), primary_key=False)
    reliability = Column('reliability', INTEGER(11), primary_key=False)
    sid = Column('sid', INTEGER, ForeignKey('plugin_sid_changes.sid'), primary_key=True)
    plugin_id = Column('plugin_id', INTEGER, ForeignKey('plugin_sid_changes.plugin_id'), primary_key=True)
    category_id = Column('category_id', INTEGER, ForeignKey('category.id'), primary_key=False)
    aro = Column('aro', DECIMAL(11, 4), primary_key=False)
    #
    # Relations:
    #
    #plugin_sid_orig=relationship('Plugin_Sid_Orig', backref='plugin_sid', primaryjoin='plugin_ctx == Plugin_Sid_Orig.plugin_ctx' , lazy='dynamic')
#TODO: ---#    #plugin_sid_orig=relationship('Plugin_Sid_Orig', backref='plugin_sid', primaryjoin='plugin_id == Plugin_Sid_Orig.plugin_ctx' , lazy='dynamic')
#TODO: ---#    #plugin_sid_orig=relationship('Plugin_Sid_Orig', backref='plugin_sid', primaryjoin='sid == Plugin_Sid_Orig.plugin_ctx' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'plugin_ctx': get_uuid_string_from_bytes(self.plugin_ctx),
          'name': self.name,
          'class_id': self.class_id,
          'priority': self.priority,
          'subcategory_id': self.subcategory_id,
          'reliability': self.reliability,
          'sid': self.sid,
          'plugin_id': self.plugin_id,
          'category_id': self.category_id,
          'aro': self.aro,
          #'plugin_sid_orig': [i.serialize for i in self.plugin_sid_orig],
          #'plugin_sid_orig': [i.serialize for i in self.plugin_sid_orig],
          #'plugin_sid_orig': [i.serialize for i in self.plugin_sid_orig],
        }


class Server (Base):
    __tablename__='server'
    remoteurl = Column('remoteurl', VARCHAR(128), primary_key=False)
    name = Column('name', VARCHAR(64), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    ip = Column('ip', VARBINARY(16), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    remotepass = Column('remotepass', VARCHAR(128), primary_key=False)
    remoteadmin = Column('remoteadmin', VARCHAR(64), primary_key=False)
    port = Column('port', INTEGER(11), primary_key=False)
    #
    # Relations:
    #
    #server_forward_role=relationship('Server_Forward_Role', backref='server', primaryjoin='id == Server_Forward_Role.server_src_id' , lazy='dynamic')
#TODO: ---#    #server_forward_role=relationship('Server_Forward_Role', backref='server', primaryjoin='id == Server_Forward_Role.server_src_id' , lazy='dynamic')
#TODO: ---#    #server_forward_role=relationship('Server_Forward_Role', backref='server', primaryjoin='id == Server_Forward_Role.server_dst_id' , lazy='dynamic')
    #server_role=relationship('Server_Role', backref='server', primaryjoin='id == Server_Role.server_id', uselist=False)
    #acl_entities=relationship('Acl_Entities', backref='server', primaryjoin='id == Acl_Entities.server_id' , lazy='dynamic')
    #server_hierarchy=relationship('Server_Hierarchy', backref='server', primaryjoin='id == Server_Hierarchy.child_id' , lazy='dynamic')
#TODO: ---#    #server_hierarchy=relationship('Server_Hierarchy', backref='server', primaryjoin='id == Server_Hierarchy.parent_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          #'remoteurl': self.remoteurl,
          'name': self.name,
          'descr': self.descr,
          'ip': get_ip_str_from_bytes(self.ip),
          'id': get_uuid_string_from_bytes(self.id),
          #'remotepass': self.remotepass,
          #'remoteadmin': self.remoteadmin,
          'port': self.port,
          #'server_forward_role': [i.serialize for i in self.server_forward_role],
          #'server_forward_role': [i.serialize for i in self.server_forward_role],
          #'server_forward_role': [i.serialize for i in self.server_forward_role],
          #'server_role': [i.serialize for i in self.server_role],
          #'acl_entities': [i.serialize for i in self.acl_entities],
          #'server_hierarchy': [i.serialize for i in self.server_hierarchy],
          #'server_hierarchy': [i.serialize for i in self.server_hierarchy],
        }


class Host_Group_Reference (Base):
    __tablename__='host_group_reference'
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    host_group_id = Column('host_group_id', BINARY(16), ForeignKey('host_group.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'host_group_id': get_uuid_string_from_bytes(self.host_group_id),
        }


class Idm_Data (Base):
    __tablename__='idm_data'
    event_id = Column('event_id', BINARY(16), ForeignKey('event.id'), primary_key=True)
    username = Column('username', VARCHAR(64), primary_key=True)
    domain = Column('domain', VARCHAR(64), primary_key=False)
    from_src = Column('from_src', TINYINT(1), primary_key=True, autoincrement=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'event_id': get_uuid_string_from_bytes(self.event_id),
          'username': self.username,
          'domain': self.domain,
          'from_src': self.from_src,
        }


class Event (Base):
    __tablename__='event'
    userdata2 = Column('userdata2', VARCHAR(1024), primary_key=False)
    username = Column('username', VARCHAR(64), primary_key=False)
    sensor_id = Column('sensor_id', BINARY(16), primary_key=False)
    protocol = Column('protocol', INTEGER(11), ForeignKey('protocol.id'), primary_key=False)
    userdata7 = Column('userdata7', VARCHAR(1024), primary_key=False)
    userdata4 = Column('userdata4', VARCHAR(1024), primary_key=False)
    userdata3 = Column('userdata3', VARCHAR(1024), primary_key=False)
    userdata8 = Column('userdata8', VARCHAR(1024), primary_key=False)
    userdata9 = Column('userdata9', VARCHAR(1024), primary_key=False)
    agent_ctx = Column('agent_ctx', BINARY(16), primary_key=False)
    rep_prio_src = Column('rep_prio_src', INTEGER(10), primary_key=False)
    reliability = Column('reliability', INTEGER(11), primary_key=False)
    userdata6 = Column('userdata6', VARCHAR(1024), primary_key=False)
    plugin_sid = Column('plugin_sid', INTEGER(11), primary_key=False)
    rep_act_dst = Column('rep_act_dst', VARCHAR(64), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    refs = Column('refs', INTEGER, primary_key=False)
    absolute = Column('absolute', TINYINT(4), primary_key=False)
    src_ip = Column('src_ip', VARBINARY(16), primary_key=False)
    src_port = Column('src_port', INTEGER(11), primary_key=False)
    rep_rel_dst = Column('rep_rel_dst', INTEGER(10), primary_key=False)
    userdata5 = Column('userdata5', VARCHAR(1024), primary_key=False)
    src_hostname = Column('src_hostname', VARCHAR(64), primary_key=False)
    filename = Column('filename', VARCHAR(256), primary_key=False)
    priority = Column('priority', INTEGER(11), primary_key=False)
    rulename = Column('rulename', TEXT, primary_key=False)
    rep_act_src = Column('rep_act_src', VARCHAR(64), primary_key=False)
    plugin_id = Column('plugin_id', INTEGER(11), primary_key=False)
    type = Column('type', INTEGER(11), primary_key=False)
    src_mac = Column('src_mac', BINARY(6), primary_key=False)
    time_interval = Column('time_interval', INTEGER(11), primary_key=False)
    dst_mac = Column('dst_mac', BINARY(6), primary_key=False)
    dst_hostname = Column('dst_hostname', VARCHAR(64), primary_key=False)
    asset_dst = Column('asset_dst', INTEGER(11), primary_key=False)
    timestamp = Column('timestamp', TIMESTAMP, primary_key=False)
    tzone = Column('tzone', FLOAT, primary_key=False)
    src_net = Column('src_net', BINARY(16), primary_key=False)
    rep_prio_dst = Column('rep_prio_dst', INTEGER(10), primary_key=False)
    asset_src = Column('asset_src', INTEGER(11), primary_key=False)
    interface = Column('interface', VARCHAR(32), primary_key=False)
    dst_net = Column('dst_net', BINARY(16), primary_key=False)
    password = Column('password', VARCHAR(64), primary_key=False)
    userdata1 = Column('userdata1', VARCHAR(1024), primary_key=False)
    event_condition = Column('event_condition', INTEGER(11), primary_key=False)
    alarm = Column('alarm', TINYINT(4), primary_key=False)
    rep_rel_src = Column('rep_rel_src', INTEGER(10), primary_key=False)
    value = Column('value', TEXT, primary_key=False)
    risk_c = Column('risk_c', INTEGER(11), primary_key=False)
    dst_host = Column('dst_host', BINARY(16), primary_key=False)
    risk_a = Column('risk_a', INTEGER(11), primary_key=False)
    dst_port = Column('dst_port', INTEGER(11), primary_key=False)
    dst_ip = Column('dst_ip', VARBINARY(16), primary_key=False)
    src_host = Column('src_host', BINARY(16), primary_key=False)
    #
    # Relations:
    #
    idm_data=relationship('Idm_Data', backref='event', primaryjoin=id == Idm_Data.event_id , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'userdata2': self.userdata2,
          'username': self.username,
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
          'protocol': self.protocol,
          'userdata7': self.userdata7,
          'userdata4': self.userdata4,
          'userdata3': self.userdata3,
          'userdata8': self.userdata8,
          'userdata9': self.userdata9,
          'agent_ctx': get_uuid_string_from_bytes(self.agent_ctx),
          'rep_prio_src': self.rep_prio_src,
          'reliability': self.reliability,
          'userdata6': self.userdata6,
          'plugin_sid': self.plugin_sid,
          'rep_act_dst': self.rep_act_dst,
          'id': get_uuid_string_from_bytes(self.id),
          'refs': self.refs,
          'absolute': self.absolute,
          'src_ip': get_ip_str_from_bytes(self.src_ip),
          'src_port': self.src_port,
          'rep_rel_dst': self.rep_rel_dst,
          'userdata5': self.userdata5,
          'src_hostname': self.src_hostname,
          'filename': self.filename,
          'priority': self.priority,
          'rulename': self.rulename,
          'rep_act_src': self.rep_act_src,
          'plugin_id': self.plugin_id,
          'type': self.type,
          'src_mac': self.src_mac,
          'time_interval': self.time_interval,
          'dst_mac': self.dst_mac,
          'dst_hostname': self.dst_hostname,
          'asset_dst': self.asset_dst,
          'timestamp': self.timestamp,
          'tzone': self.tzone,
          'src_net': get_uuid_string_from_bytes(self.src_net),
          'rep_prio_dst': self.rep_prio_dst,
          'asset_src': self.asset_src,
          'interface': self.interface,
          'dst_net': get_uuid_string_from_bytes(self.dst_net),
          'password': self.password,
          'userdata1': self.userdata1,
          'event_condition': self.event_condition,
          'alarm': self.alarm,
          'rep_rel_src': self.rep_rel_src,
          'value': self.value,
          'risk_c': self.risk_c,
          'dst_host': get_uuid_string_from_bytes(self.dst_host),
          'risk_a': self.risk_a,
          'dst_port': self.dst_port,
          'dst_ip': get_ip_str_from_bytes(self.dst_ip),
          'src_host': get_uuid_string_from_bytes(self.src_host),
          'idm_data': [i.serialize for i in self.idm_data],
        }


class Category (Base):
    __tablename__='category'
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    name = Column('name', VARCHAR(100), primary_key=False)
    #
    # Relations:
    #
    #category_changes=relationship('Category_Changes', backref='category', primaryjoin='id == Category_Changes.id', uselist=False)
    #plugin_sid=relationship('Plugin_Sid', backref='category', primaryjoin='id == Plugin_Sid.category_id' , lazy='dynamic')
    #policy_taxonomy_reference=relationship('Policy_Taxonomy_Reference', backref='category', primaryjoin='id == Policy_Taxonomy_Reference.category_id' , lazy='dynamic')
    #subcategory=relationship('Subcategory', backref='category', primaryjoin='id == Subcategory.cat_id' , lazy='dynamic')
    #subcategory_changes=relationship('Subcategory_Changes', backref='category', primaryjoin='id == Subcategory_Changes.cat_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'name': self.name,
          #'category_changes': [i.serialize for i in self.category_changes],
          #'plugin_sid': [i.serialize for i in self.plugin_sid],
          #'policy_taxonomy_reference': [i.serialize for i in self.policy_taxonomy_reference],
          #'subcategory': [i.serialize for i in self.subcategory],
          #'subcategory_changes': [i.serialize for i in self.subcategory_changes],
        }


class Acl_Entities_Users (Base):
    __tablename__='acl_entities_users'
    entity_id = Column('entity_id', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    login = Column('login', VARCHAR(64), ForeignKey('users.login'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'entity_id': get_uuid_string_from_bytes(self.entity_id),
          'login': self.login,
        }


class Vuln_Nessus_Results (Base):
    __tablename__='vuln_nessus_results'
    falsepositive = Column('falsepositive', CHAR(1), primary_key=False)
    protocol = Column('protocol', VARCHAR(5), primary_key=False)
    risk = Column('risk', ENUM('1','2','3','4','5','6','7'), primary_key=False)
    service = Column('service', VARCHAR(40), primary_key=False)
    scantime = Column('scantime', VARCHAR(14), primary_key=False)
    app = Column('app', VARCHAR(20), primary_key=False)
    hostname = Column('hostname', VARCHAR(100), primary_key=False)
    ctx = Column('ctx', BINARY(16), primary_key=False)
    record_type = Column('record_type', CHAR(1), primary_key=False)
    scriptid = Column('scriptid', VARCHAR(40), primary_key=False)
    hostIP = Column('hostIP', VARCHAR(40), primary_key=False)
    msg = Column('msg', TEXT, primary_key=False)
    result_id = Column('result_id', INTEGER(11), primary_key=True)
    port = Column('port', INTEGER(11), primary_key=False)
    report_id = Column('report_id', INTEGER(11), ForeignKey('vuln_nessus_reports.report_id'), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'falsepositive': self.falsepositive,
          'protocol': self.protocol,
          'risk': self.risk,
          'service': self.service,
          'scantime': self.scantime,
          'app': self.app,
          'hostname': self.hostname,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'record_type': self.record_type,
          'scriptid': self.scriptid,
          'hostIP': self.hostIP,
          'msg': self.msg,
          'result_id': self.result_id,
          'port': self.port,
          'report_id': self.report_id,
        }


class Wireless_Aps (Base):
    __tablename__='wireless_aps'
    gpsbestlat = Column('gpsbestlat', FLOAT, primary_key=False)
    encoding = Column('encoding', VARCHAR(32), primary_key=False)
    ip = Column('ip', VARBINARY(16), primary_key=False)
    gpsminlat = Column('gpsminlat', FLOAT, primary_key=False)
    gpsmaxlon = Column('gpsmaxlon', FLOAT, primary_key=False)
    gpsmaxspd = Column('gpsmaxspd', FLOAT, primary_key=False)
    maxseenrate = Column('maxseenrate', INTEGER(11), primary_key=False)
    llc = Column('llc', INTEGER(11), primary_key=False)
    total = Column('total', INTEGER(11), primary_key=False)
    ssid = Column('ssid', VARCHAR(255), primary_key=True)
    encryption = Column('encryption', VARCHAR(64), primary_key=False)
    maxrate = Column('maxrate', FLOAT, primary_key=False)
    datasize = Column('datasize', INTEGER(11), primary_key=False)
    sensor = Column('sensor', VARBINARY(16), primary_key=True)
    channel = Column('channel', INTEGER(11), primary_key=False)
    gpsbestalt = Column('gpsbestalt', FLOAT, primary_key=False)
    iptype = Column('iptype', VARCHAR(32), primary_key=False)
    weak = Column('weak', INTEGER(11), primary_key=False)
    dupeiv = Column('dupeiv', INTEGER(11), primary_key=False)
    bestnoise = Column('bestnoise', INTEGER(11), primary_key=False)
    beacon = Column('beacon', INTEGER(11), primary_key=False)
    bestsignal = Column('bestsignal', INTEGER(11), primary_key=False)
    lasttime = Column('lasttime', TIMESTAMP, primary_key=False)
    data = Column('data', INTEGER(11), primary_key=False)
    info = Column('info', VARCHAR(255), primary_key=False)
    decrypted = Column('decrypted', ENUM('Yes','No'), primary_key=False)
    gpsmaxalt = Column('gpsmaxalt', FLOAT, primary_key=False)
    firsttime = Column('firsttime', TIMESTAMP, primary_key=False)
    gpsminalt = Column('gpsminalt', FLOAT, primary_key=False)
    notes = Column('notes', TINYTEXT, primary_key=False)
    gpsmaxlat = Column('gpsmaxlat', FLOAT, primary_key=False)
    mac = Column('mac', VARCHAR(18), primary_key=True)
    bestquality = Column('bestquality', INTEGER(11), primary_key=False)
    carrier = Column('carrier', VARCHAR(32), primary_key=False)
    cloaked = Column('cloaked', ENUM('Yes','No'), primary_key=False)
    gpsminlon = Column('gpsminlon', FLOAT, primary_key=False)
    nettype = Column('nettype', VARCHAR(32), primary_key=False)
    crypt = Column('crypt', INTEGER(11), primary_key=False)
    gpsbestlon = Column('gpsbestlon', FLOAT, primary_key=False)
    gpsminspd = Column('gpsminspd', FLOAT, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'gpsbestlat': self.gpsbestlat,
          'encoding': self.encoding,
          'ip': get_ip_str_from_bytes(self.ip),
          'gpsminlat': self.gpsminlat,
          'gpsmaxlon': self.gpsmaxlon,
          'gpsmaxspd': self.gpsmaxspd,
          'maxseenrate': self.maxseenrate,
          'llc': self.llc,
          'total': self.total,
          'ssid': self.ssid,
          'encryption': self.encryption,
          'maxrate': self.maxrate,
          'datasize': self.datasize,
          'sensor': get_ip_str_from_bytes(self.sensor),
          'channel': self.channel,
          'gpsbestalt': self.gpsbestalt,
          'iptype': self.iptype,
          'weak': self.weak,
          'dupeiv': self.dupeiv,
          'bestnoise': self.bestnoise,
          'beacon': self.beacon,
          'bestsignal': self.bestsignal,
          'lasttime': self.lasttime,
          'data': self.data,
          'info': self.info,
          'decrypted': self.decrypted,
          'gpsmaxalt': self.gpsmaxalt,
          'firsttime': self.firsttime,
          'gpsminalt': self.gpsminalt,
          'notes': self.notes,
          'gpsmaxlat': self.gpsmaxlat,
          'mac': self.mac,
          'bestquality': self.bestquality,
          'carrier': self.carrier,
          'cloaked': self.cloaked,
          'gpsminlon': self.gpsminlon,
          'nettype': self.nettype,
          'crypt': self.crypt,
          'gpsbestlon': self.gpsbestlon,
          'gpsminspd': self.gpsminspd,
        }


class Policy_Group (Base):
    __tablename__='policy_group'
    name = Column('name', VARCHAR(100), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True, index=True)
    id = Column('id', BINARY(16), primary_key=True, index=True)
    order = Column('order', INTEGER, primary_key=False)
    permissions = Column('permissions', BINARY(8), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'name': self.name,
          'descr': self.descr,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': get_uuid_string_from_bytes(self.id),
          'order': self.order,
          'permissions': self.permissions,
        }


class Incident_Custom (Base):
    __tablename__='incident_custom'
    content = Column('content', BLOB, primary_key=False)
    incident_id = Column('incident_id', INTEGER, ForeignKey('incident.id'), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    incident_custom_type_id = Column('incident_custom_type_id', VARCHAR(64), ForeignKey('incident_custom_types.id'), primary_key=False)
    name = Column('name', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'content': self.content,
          'incident_id': self.incident_id,
          'id': self.id,
          'incident_custom_type_id': self.incident_custom_type_id,
          'name': self.name,
        }


class Host_Group (Base):
    __tablename__='host_group'
    rrd_profile = Column('rrd_profile', VARCHAR(64), primary_key=False)
    name = Column('name', VARCHAR(128), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    threshold_c = Column('threshold_c', INTEGER(11), primary_key=False)
    threshold_a = Column('threshold_a', INTEGER(11), primary_key=False)
    same_host = Column('same_host', TINYINT(1), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    permissions = Column('permissions', BINARY(8), primary_key=False)
    #
    # Relations:
    #
    #host_group_reference=relationship('Host_Group_Reference', backref='host_group', primaryjoin='id == Host_Group_Reference.host_group_id' , lazy='dynamic')
    #host_group_scan=relationship('Host_Group_Scan', backref='host_group', primaryjoin='id == Host_Group_Scan.host_group_id' , lazy='dynamic')
    #plugin_scheduler_hostgroup_reference=relationship('Plugin_Scheduler_Hostgroup_Reference', backref='host_group', primaryjoin='id == Plugin_Scheduler_Hostgroup_Reference.hostgroup_id' , lazy='dynamic')
    #policy_host_group_reference=relationship('Policy_Host_Group_Reference', backref='host_group', primaryjoin='id == Policy_Host_Group_Reference.host_group_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'rrd_profile': self.rrd_profile,
          'name': self.name,
          'descr': self.descr,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'threshold_c': self.threshold_c,
          'threshold_a': self.threshold_a,
          'same_host': self.same_host,
          'id': get_uuid_string_from_bytes(self.id),
          'permissions': self.permissions,
          #'host_group_reference': [i.serialize for i in self.host_group_reference],
          #'host_group_scan': [i.serialize for i in self.host_group_scan],
          #'plugin_scheduler_hostgroup_reference': [i.serialize for i in self.plugin_scheduler_hostgroup_reference],
          #'policy_host_group_reference': [i.serialize for i in self.policy_host_group_reference],
        }


class Bp_Asset_Member (Base):
    __tablename__='bp_asset_member'
    member = Column('member', BINARY(16), ForeignKey('sensor.id'), primary_key=False)
    type = Column('type', ENUM('file', 'host', 'host_group', 'net', 'net_group'), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    #
    # Relations:
    #
    #bp_member_status=relationship('Bp_Member_Status', backref='bp_asset_member', primaryjoin='id == Bp_Member_Status.member_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'member': get_uuid_string_from_bytes(self.member),
          'type': self.type,
          'id': self.id,
          #'bp_member_status': [i.serialize for i in self.bp_member_status],
        }


class Rrd_Config (Base):
    __tablename__='rrd_config'
    profile = Column('profile', VARCHAR(64), primary_key=True)
    enable = Column('enable', TINYINT(4), primary_key=False)
    description = Column('description', TEXT, primary_key=False)
    rrd_attrib = Column('rrd_attrib', VARCHAR(60), primary_key=True)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    priority = Column('priority', INTEGER(10), primary_key=False)
    beta = Column('beta', FLOAT, primary_key=False)
    threshold = Column('threshold', INTEGER(10), primary_key=False)
    alpha = Column('alpha', FLOAT, primary_key=False)
    persistence = Column('persistence', INTEGER(10), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'profile': self.profile,
          'enable': self.enable,
          'description': self.description,
          'rrd_attrib': self.rrd_attrib,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'priority': self.priority,
          'beta': self.beta,
          'threshold': self.threshold,
          'alpha': self.alpha,
          'persistence': self.persistence,
        }


class Host_Agentless_Entries (Base):
    __tablename__='host_agentless_entries'
    sensor_id = Column('sensor_id', VARCHAR(36), primary_key=False)
    ip = Column('ip', VARCHAR(15), ForeignKey('host_agentless.ip'), primary_key=False)
    state = Column('state', VARCHAR(20), primary_key=False)
    frequency = Column('frequency', INTEGER(10), primary_key=False)
    arguments = Column('arguments', TEXT, primary_key=False)
    type = Column('type', VARCHAR(64), primary_key=False)
    id = Column('id', INTEGER(10), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'sensor_id': self.sensor_id,
          'ip': self.ip,
          'state': self.state,
          'frequency': self.frequency,
          'arguments': self.arguments,
          'type': self.type,
          'id': self.id,
        }


class Category_Changes (Base):
    __tablename__='category_changes'
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', INTEGER, ForeignKey('category.id'), primary_key=True)
    name = Column('name', VARCHAR(100), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'name': self.name,
        }


class Plugin_Reference (Base):
    __tablename__='plugin_reference'
    plugin_id = Column('plugin_id', INTEGER(11), ForeignKey('plugin_sid.plugin_id'), primary_key=True)
    reference_sid = Column('reference_sid', INTEGER(11), ForeignKey('plugin_sid.sid'), primary_key=True)
    ctx = Column('ctx', BINARY(16), ForeignKey('plugin_sid.plugin_ctx'), primary_key=True)
    plugin_sid = Column('plugin_sid', INTEGER(11), ForeignKey('plugin_sid.sid'), primary_key=True)
    reference_id = Column('reference_id', INTEGER(11), ForeignKey('plugin_sid.plugin_id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'plugin_id': self.plugin_id,
          'reference_sid': self.reference_sid,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'plugin_sid': self.plugin_sid,
          'reference_id': self.reference_id,
        }


class Repository (Base):
    __tablename__='repository'
    title = Column('title', VARCHAR(256), primary_key=False)
    text = Column('text', TEXT, primary_key=False)
    creator = Column('creator', VARCHAR(64), primary_key=False)
    in_charge = Column('in_charge', VARCHAR(64), primary_key=False)
    keywords = Column('keywords', VARCHAR(256), primary_key=False)
    date = Column('date', DATE, primary_key=False)
    id = Column('id', INTEGER(10), primary_key=True)
    #
    # Relations:
    #
    #repository_attachments=relationship('Repository_Attachments', backref='repository', primaryjoin='id == Repository_Attachments.id' , lazy='dynamic')
    #repository_relationships=relationship('Repository_Relationships', backref='repository', primaryjoin='id == Repository_Relationships.id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'title': self.title,
          'text': self.text,
          'creator': self.creator,
          'in_charge': self.in_charge,
          'keywords': self.keywords,
          'date': self.date,
          'id': self.id,
          #'repository_attachments': [i.serialize for i in self.repository_attachments],
          #'repository_relationships': [i.serialize for i in self.repository_relationships],
        }


class Vuln_Nessus_Settings_Preferences (Base):
    __tablename__='vuln_nessus_settings_preferences'
    category = Column('category', VARCHAR(255), primary_key=False)
    value = Column('value', TEXT, primary_key=False)
    nessus_id = Column('nessus_id', VARCHAR(255), primary_key=False)
    sid = Column('sid', INTEGER(11), ForeignKey('vuln_nessus_settings.id'), primary_key=True)
    type = Column('type', CHAR(1), primary_key=False)
    id = Column('id', VARCHAR(255), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'category': self.category,
          'value': self.value,
          'nessus_id': self.nessus_id,
          'sid': self.sid,
          'type': self.type,
          'id': self.bp_member_statusid,
        }


class Bp_Member_Status (Base):
    __tablename__='bp_member_status'
    status_date = Column('status_date', DATETIME, primary_key=True)
    measure_type = Column('measure_type', VARCHAR(255), primary_key=True)
    severity = Column('severity', INTEGER(2), primary_key=False)
    member_id = Column('member_id', BINARY(16), ForeignKey('bp_asset_member.member'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'status_date': self.status_date,
          'measure_type': self.measure_type,
          'severity': self.severity,
          'member_id': get_uuid_string_from_bytes(self.member_id),
        }


class Backlog_Event (Base):
    __tablename__='backlog_event'
    occurrence = Column('occurrence', INTEGER(11), primary_key=False)
    event_id = Column('event_id', BINARY(16), ForeignKey('event.id'), primary_key=True)
    rule_level = Column('rule_level', INTEGER(11), primary_key=False)
    backlog_id = Column('backlog_id', BINARY(16), ForeignKey('backlog.id'), primary_key=True)
    time_out = Column('time_out', INTEGER(11), primary_key=False)
    matched = Column('matched', TINYINT(4), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'occurrence': self.occurrence,
          'event_id': get_uuid_string_from_bytes(self.event_id),
          'rule_level': self.rule_level,
          'backlog_id': get_uuid_string_from_bytes(self.backlog_id),
          'time_out': self.time_out,
          'matched': self.matched,
        }


class Incident (Base):
    __tablename__='incident'
    status = Column('status', ENUM('Open','Assigned','Studying','Waiting','Testing','Closed'), primary_key=False)
    uuid = Column('uuid', BINARY(16), primary_key=False)
    title = Column('title', VARCHAR(128), primary_key=False)
    type_id = Column('type_id', VARCHAR(64), ForeignKey('incident_type.id'), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    event_start = Column('event_start', DATETIME, primary_key=False)
    last_update = Column('last_update', DATETIME, primary_key=False)
    priority = Column('priority', INTEGER(11), primary_key=False)
    submitter = Column('submitter', VARCHAR(64), primary_key=False)
    event_end = Column('event_end', DATETIME, primary_key=False)
    in_charge = Column('in_charge', VARCHAR(64), primary_key=False)
    date = Column('date', DATETIME, primary_key=False)
    ref = Column('ref', ENUM('Alarm','Alert','Event','Metric','Anomaly','Vulnerability','Custom'), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    #
    # Relations:
    #
    #incident_alarm=relationship('Incident_Alarm', backref='incident', primaryjoin='id == Incident_Alarm.incident_id' , lazy='dynamic')
    #incident_anomaly=relationship('Incident_Anomaly', backref='incident', primaryjoin='id == Incident_Anomaly.incident_id' , lazy='dynamic')
    #incident_custom=relationship('Incident_Custom', backref='incident', primaryjoin='id == Incident_Custom.incident_id' , lazy='dynamic')
    #incident_event=relationship('Incident_Event', backref='incident', primaryjoin='id == Incident_Event.incident_id' , lazy='dynamic')
    #incident_file=relationship('Incident_File', backref='incident', primaryjoin='id == Incident_File.incident_id' , lazy='dynamic')
    #incident_metric=relationship('Incident_Metric', backref='incident', primaryjoin='id == Incident_Metric.incident_id' , lazy='dynamic')
    #incident_subscrip=relationship('Incident_Subscrip', backref='incident', primaryjoin='id == Incident_Subscrip.incident_id' , lazy='dynamic')
    #incident_tag=relationship('Incident_Tag', backref='incident', primaryjoin='id == Incident_Tag.incident_id' , lazy='dynamic')
    #incident_ticket=relationship('Incident_Ticket', backref='incident', primaryjoin='id == Incident_Ticket.incident_id' , lazy='dynamic')
    #incident_vulns=relationship('Incident_Vulns', backref='incident', primaryjoin='id == Incident_Vulns.incident_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'status': self.status,
          'uuid': get_uuid_string_from_bytes(self.uuid),
          'title': self.title,
          'type_id': self.type_id,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'event_start': self.event_start,
          'last_update': self.last_update,
          'priority': self.priority,
          'submitter': self.submitter,
          'event_end': self.event_end,
          'in_charge': self.in_charge,
          'date': self.date,
          'ref': self.ref,
          'id': self.id,
          #'incident_alarm': [i.serialize for i in self.incident_alarm],
          #'incident_anomaly': [i.serialize for i in self.incident_anomaly],
          #'incident_custom': [i.serialize for i in self.incident_custom],
          #'incident_event': [i.serialize for i in self.incident_event],
          #'incident_file': [i.serialize for i in self.incident_file],
          #'incident_metric': [i.serialize for i in self.incident_metric],
          #'incident_subscrip': [i.serialize for i in self.incident_subscrip],
          #'incident_tag': [i.serialize for i in self.incident_tag],
          #'incident_ticket': [i.serialize for i in self.incident_ticket],
          #'incident_vulns': [i.serialize for i in self.incident_vulns],
        }


class Vuln_Nessus_Settings_Category (Base):
    __tablename__='vuln_nessus_settings_category'
    status = Column('status', INTEGER(11), primary_key=False)
    cid = Column('cid', INTEGER(11), ForeignKey('vuln_nessus_category.id'), primary_key=True)
    sid = Column('sid', INTEGER(11), ForeignKey('vuln_nessus_settings.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'status': self.status,
          'cid': self.cid,
          'sid': self.sid,
        }


class Net_Group_Scan (Base):
    __tablename__='net_group_scan'
    plugin_id = Column('plugin_id', INTEGER(11), primary_key=True)
    net_group_id = Column('net_group_id', BINARY(16), ForeignKey('net_group.id'), primary_key=True)
    plugin_sid = Column('plugin_sid', INTEGER(11), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'plugin_id': self.plugin_id,
          'net_group_id': get_uuid_string_from_bytes(self.net_group_id),
          'plugin_sid': self.plugin_sid,
        }


class Restoredb_Log (Base):
    __tablename__='restoredb_log'
    status = Column('status', SMALLINT(6), primary_key=False)
    users = Column('users', VARCHAR(64), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    percent = Column('percent', SMALLINT(6), primary_key=False)
    pid = Column('pid', INTEGER(11), primary_key=False)
    date = Column('date', TIMESTAMP, primary_key=False)
    data = Column('data', TEXT, primary_key=False)
    id = Column('id', INTEGER(10), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'status': self.status,
          'users': self.users,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'percent': self.percent,
          'pid': self.pid,
          'date': self.date,
          'data': self.data,
          'id': self.id,
        }


class Plugin_Scheduler_Sensor_Reference (Base):
    __tablename__='plugin_scheduler_sensor_reference'
    sensor_id = Column('sensor_id', BINARY(16), ForeignKey('sensor.id'), primary_key=True)
    plugin_scheduler_id = Column('plugin_scheduler_id', BINARY(16), ForeignKey('plugin_scheduler.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
          'plugin_scheduler_id': get_uuid_string_from_bytes(self.plugin_scheduler_id),
        }


class Acl_Assets (Base):
    __tablename__='acl_assets'
    asset_id = Column('asset_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    login = Column('login', VARCHAR(64), ForeignKey('users.login'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'asset_id': get_uuid_string_from_bytes(self.asset_id),
          'login': self.login,
        }


class Plugin (Base):
    __tablename__='plugin'
    product_type = Column('product_type', INTEGER(11), ForeignKey('product_type.id'), primary_key=False)
    description = Column('description', TEXT, primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    vendor = Column('vendor', TEXT, primary_key=False)
    type = Column('type', SMALLINT(6), primary_key=False)
    id = Column('id', INTEGER, ForeignKey('plugin_group_descr.plugin_id'), primary_key=True)
    name = Column('name', VARCHAR(100), primary_key=False)
    #
    # Relations:
    #
    #plugin_sid=relationship('Plugin_Sid', backref='plugin', primaryjoin='ctx == Plugin_Sid.plugin_ctx' , lazy='dynamic')
#TODO: ---#    #plugin_sid=relationship('Plugin_Sid', backref='plugin', primaryjoin='id == Plugin_Sid.plugin_ctx' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'product_type': self.product_type,
          'description': self.description,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'vendor': self.vendor,
          'type': self.type,
          'id': self.id,
          'name': self.name,
          #'plugin_sid': [i.serialize for i in self.plugin_sid],
          #'plugin_sid': [i.serialize for i in self.plugin_sid],
        }


class Host_Sensor_Reference (Base):
    __tablename__='host_sensor_reference'
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    sensor_id = Column('sensor_id', BINARY(16), ForeignKey('sensor.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
        }


class Host (Base):
    __tablename__= 'host'

    rrd_profile = Column('rrd_profile', VARCHAR(64), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    lon = Column('lon', VARCHAR(255), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    hostname = Column('hostname', VARCHAR(128), primary_key=False)
    threshold_c = Column('threshold_c', INTEGER(11), primary_key=False)
    fqdns = Column('fqdns', VARCHAR(255), primary_key=False)
    threshold_a = Column('threshold_a', INTEGER(11), primary_key=False)
    alert = Column('alert', INTEGER(11), primary_key=False)
    av_component = Column('av_component', TINYINT(1), primary_key=False)
    external_host = Column('external_host', TINYINT(1), primary_key=False)
    asset = Column('asset', SMALLINT(6), primary_key=False)
    nat = Column('nat', VARCHAR(15), primary_key=False)
    country = Column('country', VARCHAR(64), primary_key=False)
    lat = Column('lat', VARCHAR(255), primary_key=False)
    permissions = Column('permissions', BINARY(8), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    persistence = Column('persistence', INTEGER(11), primary_key=False)
    icon = Column('icon', MEDIUMBLOB, primary_key=False)
    #
    # Relations:
    #
    #host_group_reference=relationship('Host_Group_Reference', backref='host', primaryjoin='id == Host_Group_Reference.host_id' , lazy='dynamic')
    #host_plugin_sid=relationship('Host_Plugin_Sid', backref='host', primaryjoin='id == Host_Plugin_Sid.host_ip' , lazy='dynamic')
    #host_properties=relationship('Host_Properties', backref='host', primaryjoin='id == Host_Properties.host_id' , lazy='dynamic')
    #host_qualification=relationship('Host_Qualification', backref='host', primaryjoin='id == Host_Qualification.host_id' , lazy='dynamic')
    #host_scan=relationship('Host_Scan', backref='host', primaryjoin='id == Host_Scan.host_id' , lazy='dynamic')
    #host_vulnerability=relationship('Host_Vulnerability', backref='host', primaryjoin='id == Host_Vulnerability.host_id' , lazy='dynamic')
    #plugin_scheduler_host_reference=relationship('Plugin_Scheduler_Host_Reference', backref='host', primaryjoin='id == Plugin_Scheduler_Host_Reference.host_id' , lazy='dynamic')
    #policy_host_reference=relationship('Policy_Host_Reference', backref='host', primaryjoin='id == Policy_Host_Reference.host_id' , lazy='dynamic')
    host_ips = relationship('Host_Ip', backref='host', primaryjoin=id == Host_Ip.host_id, lazy='dynamic')
    host_sensor_reference = relationship('Host_Sensor_Reference', backref='host', primaryjoin=id == Host_Sensor_Reference.host_id , lazy='dynamic')
    #host_services=relationship('Host_Services', backref='host', primaryjoin='id == Host_Services.host_id' , lazy='dynamic')
    #acl_assets=relationship('Acl_Assets', backref='host', primaryjoin='id == Acl_Assets.asset_id' , lazy='dynamic')
    #acl_entities_assets=relationship('Acl_Entities_Assets', backref='host', primaryjoin='id == Acl_Entities_Assets.asset_id' , lazy='dynamic')
    #alarm_ctxs=relationship('Alarm_Ctxs', backref='host', primaryjoin='id == Alarm_Ctxs.id_ctx' , lazy='dynamic')
    #alarm_hosts=relationship('Alarm_Hosts', backref='host', primaryjoin='id == Alarm_Hosts.id_host' , lazy='dynamic')
    #host_types=relationship('Host_Types', backref='host', primaryjoin='id == Host_Types.host_id' , lazy='dynamic')
    #host_net_reference=relationship('Host_Net_Reference', backref='host', primaryjoin='id == Host_Net_Reference.host_id' , lazy='dynamic')
    #host_software=relationship('Host_Software', backref='host', primaryjoin='id == Host_Software.host_id' , lazy='dynamic')

    @property
    def serialize(self):
        return {
          'rrd_profile': self.rrd_profile,
          'descr': self.descr,
          'lon': self.lon,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'hostname': self.hostname,
          'threshold_c': self.threshold_c,
          'fqdns': self.fqdns,
          'threshold_a': self.threshold_a,
          'alert': self.alert,
          'av_component': self.av_component,
          'external_host': self.external_host,
          'asset': self.asset,
          'nat': self.nat,
          'country': self.country,
          'lat': self.lat,
          'permissions': self.permissions,
          'id': get_uuid_string_from_bytes(self.id),
          'persistence': self.persistence,
          'icon': self.icon,
          #'host_group_reference': [i.serialize for i in self.host_group_reference],
          #'host_plugin_sid': [i.serialize for i in self.host_plugin_sid],
          #'host_properties': [i.serialize for i in self.host_properties],
          #'host_qualification': [i.serialize for i in self.host_qualification],
          #'host_scan': [i.serialize for i in self.host_scan],
          #'host_vulnerability': [i.serialize for i in self.host_vulnerability],
          #'plugin_scheduler_host_reference': [i.serialize for i in self.plugin_scheduler_host_reference],
          #'policy_host_reference': [i.serialize for i in self.policy_host_reference],
          'host_ip': [i.serialize for i in self.host_ips],
          'host_sensor_reference': [i.serialize for i in self.host_sensor_reference],
          #'host_services': [i.serialize for i in self.host_services],
          #'acl_assets': [i.serialize for i in self.acl_assets],
          #'acl_entities_assets': [i.serialize for i in self.acl_entities_assets],
          #'alarm_ctxs': [i.serialize for i in self.alarm_ctxs],
          #'alarm_hosts': [i.serialize for i in self.alarm_hosts],
          #'host_types': [i.serialize for i in self.host_types],
          #'host_net_reference': [i.serialize for i in self.host_net_reference],
          #'host_software': [i.serialize for i in self.host_software],
        }


class Credential_Type (Base):
    __tablename__='credential_type'
    id = Column('id', INTEGER(11), primary_key=True)
    name = Column('name', TEXT, primary_key=False)
    #
    # Relations:
    #
    #credentials=relationship('Credentials', backref='credential_type', primaryjoin='id == Credentials.type' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'id': self.id,
          'name': self.name,
          #'credentials': [i.serialize for i in self.credentials],
        }


class Policy_Plugin_Group_Reference (Base):
    __tablename__='policy_plugin_group_reference'
    plugin_group_id = Column('plugin_group_id', BINARY(16), ForeignKey('plugin_group.group_id'), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'plugin_group_id': get_uuid_string_from_bytes(self.plugin_group_id),
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Host_Properties (Base):
    __tablename__='host_properties'
    property_ref = Column('property_ref', INTEGER, ForeignKey('host_property_reference.id'), primary_key=True)
    extra = Column('extra', TEXT, primary_key=False)
    tzone = Column('tzone', FLOAT, primary_key=False)
    value = Column('value', TEXT, primary_key=False)
    last_modified = Column('last_modified', TIMESTAMP, primary_key=False)
    source_id = Column('source_id', INTEGER, ForeignKey('host_source_reference.id'), primary_key=False)
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'property_ref': self.property_ref,
          'extra': self.extra,
          'tzone': self.tzone,
          'value': self.value,
          'last_modified': self.last_modified,
          'source_id': self.source_id,
          'host_id': get_uuid_string_from_bytes(self.host_id),
        }


class Signature (Base):
    __tablename__='signature'
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', INTEGER(10), primary_key=True)
    name = Column('name', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    #signature_group_reference=relationship('Signature_Group_Reference', backref='signature', primaryjoin='id == Signature_Group_Reference.signature_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'name': self.name,
          #'signature_group_reference': [i.serialize for i in self.signature_group_reference],
        }


class Action (Base):
    __tablename__='action'
    on_risk = Column('on_risk', TINYINT(1), primary_key=False)
    name = Column('name', VARCHAR(128), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    cond = Column('cond', VARCHAR(255), primary_key=False)
    action_type = Column('action_type', INTEGER, ForeignKey('action_type.type'), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    #
    # Relations:
    #
    #action_email=relationship('Action_Email', backref='action', primaryjoin='id == Action_Email.action_id' , lazy='dynamic')
    #action_exec=relationship('Action_Exec', backref='action', primaryjoin='id == Action_Exec.action_id' , lazy='dynamic')
    #action_risk=relationship('Action_Risk', backref='action', primaryjoin='id == Action_Risk.action_id' , lazy='dynamic')
    #policy_actions=relationship('Policy_Actions', backref='action', primaryjoin='id == Policy_Actions.action_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'on_risk': self.on_risk,
          'name': self.name,
          'descr': self.descr,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'cond': self.cond,
          'action_type': self.action_type,
          'id': get_uuid_string_from_bytes(self.id),
          #'action_email': [i.serialize for i in self.action_email],
          #'action_exec': [i.serialize for i in self.action_exec],
          #'action_risk': [i.serialize for i in self.action_risk],
          #'policy_actions': [i.serialize for i in self.policy_actions],
        }


class Acl_Templates (Base):
    __tablename__='acl_templates'
    id = Column('id', BINARY(16), primary_key=True)
    name = Column('name', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    #acl_templates_perms=relationship('Acl_Templates_Perms', backref='acl_templates', primaryjoin='id == Acl_Templates_Perms.ac_templates_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'id': get_uuid_string_from_bytes(self.id),
          'name': self.name,
          #'acl_templates_perms': [i.serialize for i in self.acl_templates_perms],
        }


class Plugin_Scheduler (Base):
    __tablename__='plugin_scheduler'
    type_scan = Column('type_scan', VARCHAR(255), primary_key=False)
    plugin = Column('plugin', VARCHAR(255), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    plugin_day_week = Column('plugin_day_week', VARCHAR(255), primary_key=False)
    plugin_day_month = Column('plugin_day_month', VARCHAR(255), primary_key=False)
    plugin_minute = Column('plugin_minute', VARCHAR(255), primary_key=False)
    plugin_month = Column('plugin_month', VARCHAR(255), primary_key=False)
    plugin_hour = Column('plugin_hour', VARCHAR(255), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    #
    # Relations:
    #
    #plugin_scheduler_host_reference=relationship('Plugin_Scheduler_Host_Reference', backref='plugin_scheduler', primaryjoin='id == Plugin_Scheduler_Host_Reference.plugin_scheduler_id' , lazy='dynamic')
    #plugin_scheduler_hostgroup_reference=relationship('Plugin_Scheduler_Hostgroup_Reference', backref='plugin_scheduler', primaryjoin='id == Plugin_Scheduler_Hostgroup_Reference.plugin_scheduler_id' , lazy='dynamic')
    #plugin_scheduler_net_reference=relationship('Plugin_Scheduler_Net_Reference', backref='plugin_scheduler', primaryjoin='id == Plugin_Scheduler_Net_Reference.plugin_scheduler_id' , lazy='dynamic')
    #plugin_scheduler_netgroup_reference=relationship('Plugin_Scheduler_Netgroup_Reference', backref='plugin_scheduler', primaryjoin='id == Plugin_Scheduler_Netgroup_Reference.plugin_scheduler_id' , lazy='dynamic')
    #plugin_scheduler_sensor_reference=relationship('Plugin_Scheduler_Sensor_Reference', backref='plugin_scheduler', primaryjoin='id == Plugin_Scheduler_Sensor_Reference.plugin_scheduler_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'type_scan': self.type_scan,
          'plugin': self.plugin,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'plugin_day_week': self.plugin_day_week,
          'plugin_day_month': self.plugin_day_month,
          'plugin_minute': self.plugin_minute,
          'plugin_month': self.plugin_month,
          'plugin_hour': self.plugin_hour,
          'id': get_uuid_string_from_bytes(self.id),
          #'plugin_scheduler_host_reference': [i.serialize for i in self.plugin_scheduler_host_reference],
          #'plugin_scheduler_hostgroup_reference': [i.serialize for i in self.plugin_scheduler_hostgroup_reference],
          #'plugin_scheduler_net_reference': [i.serialize for i in self.plugin_scheduler_net_reference],
          #'plugin_scheduler_netgroup_reference': [i.serialize for i in self.plugin_scheduler_netgroup_reference],
          #'plugin_scheduler_sensor_reference': [i.serialize for i in self.plugin_scheduler_sensor_reference],
        }


class Plugin_Scheduler_Hostgroup_Reference (Base):
    __tablename__='plugin_scheduler_hostgroup_reference'
    hostgroup_id = Column('hostgroup_id', BINARY(16), ForeignKey('host_group.id'), primary_key=True)
    plugin_scheduler_id = Column('plugin_scheduler_id', BINARY(16), ForeignKey('plugin_scheduler.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'hostgroup_id': get_uuid_string_from_bytes(self.hostgroup_id),
          'plugin_scheduler_id': get_uuid_string_from_bytes(self.plugin_scheduler_id),
        }


class Policy_Reputation_Reference (Base):
    __tablename__='policy_reputation_reference'
    from_src = Column('from_src', TINYINT(1), primary_key=False)
    rep_prio = Column('rep_prio', TINYINT, primary_key=False)
    rep_rel = Column('rep_rel', TINYINT, primary_key=False)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    rep_act = Column('rep_act', SMALLINT, ForeignKey('reputation_activities.id'), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'from_src': self.from_src,
          'rep_prio': self.rep_prio,
          'rep_rel': self.rep_rel,
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
          'id': self.id,
          'rep_act': self.rep_act,
        }


class Classification (Base):
    __tablename__='classification'
    priority = Column('priority', INTEGER(11), primary_key=False)
    description = Column('description', TEXT, primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    name = Column('name', VARCHAR(100), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'priority': self.priority,
          'description': self.description,
          'id': self.id,
          'name': self.name,
        }


class Host_Agentless (Base):
    __tablename__='host_agentless'
    status = Column('status', INTEGER(2), primary_key=False)
    sensor_id = Column('sensor_id', VARCHAR(36), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    ppass = Column('ppass', VARCHAR(128), primary_key=False)
    ip = Column('ip', VARCHAR(15), primary_key=True)
    hostname = Column('hostname', VARCHAR(128), primary_key=False)
    user = Column('user', VARCHAR(128), primary_key=False)
    av_pass = Column('pass', VARCHAR(128), primary_key=False)
    #
    # Relations:
    #
    #host_agentless_entries=relationship('Host_Agentless_Entries', backref='host_agentless', primaryjoin='ip == Host_Agentless_Entries.ip' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'status': self.status,
          'sensor_id': self.sensor_id,
          'descr': self.descr,
          'ppass': self.ppass,
          'ip': self.ip,
          'hostname': self.hostname,
          'user': self.user,
          'av_pass': self.av_pass,
          #'host_agentless_entries': [i.serialize for i in self.host_agentless_entries],
        }


class Wireless_Networks (Base):
    __tablename__='wireless_networks'
    firsttime = Column('firsttime', TIMESTAMP, primary_key=False)
    ssid = Column('ssid', VARCHAR(255), primary_key=True)
    aps = Column('aps', INTEGER(11), primary_key=False)
    notes = Column('notes', TINYTEXT, primary_key=False)
    clients = Column('clients', INTEGER(11), primary_key=False)
    type = Column('type', ENUM('Un-Trusted','Trusted'), primary_key=False)
    cloaked = Column('cloaked', VARCHAR(15), primary_key=False)
    encryption = Column('encryption', VARCHAR(255), primary_key=False)
    macs = Column('macs', TINYTEXT, primary_key=False)
    lasttime = Column('lasttime', TIMESTAMP, primary_key=False)
    sensor = Column('sensor', VARBINARY(16), primary_key=True)
    description = Column('description', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'firsttime': self.firsttime,
          'ssid': self.ssid,
          'aps': self.aps,
          'notes': self.notes,
          'clients': self.clients,
          'type': self.type,
          'cloaked': self.cloaked,
          'encryption': self.encryption,
          'macs': self.macs,
          'lasttime': self.lasttime,
          'sensor': get_ip_str_from_bytes(self.sensor),
          'description': self.description,
        }


class Vuln_Settings (Base):
    __tablename__='vuln_settings'
    settingDescription = Column('settingDescription', VARCHAR(255), primary_key=False)
    settingID = Column('settingID', INTEGER(11), primary_key=True)
    developerNotes = Column('developerNotes', TEXT, primary_key=False)
    settingName = Column('settingName', VARCHAR(100), primary_key=False)
    settingSection = Column('settingSection', VARCHAR(50), primary_key=False)
    settingValue = Column('settingValue', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'settingDescription': self.settingDescription,
          'settingID': self.settingID,
          'developerNotes': self.developerNotes,
          'settingName': self.settingName,
          'settingSection': self.settingSection,
          'settingValue': self.settingValue,
        }


class Acl_Templates_Perms (Base):
    __tablename__='acl_templates_perms'
    ac_perm_id = Column('ac_perm_id', INTEGER(11), ForeignKey('acl_perm.id'), primary_key=True)
    ac_templates_id = Column('ac_templates_id', BINARY(16), ForeignKey('acl_templates.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'ac_perm_id': self.ac_perm_id,
          'ac_templates_id': get_uuid_string_from_bytes(self.ac_templates_id),
        }


class Map_Seq (Base):
    __tablename__='map_seq'
    id = Column('id', INTEGER(10), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id': self.id,
        }


class Policy_Host_Group_Reference (Base):
    __tablename__='policy_host_group_reference'
    direction = Column('direction', ENUM('source','dest'), primary_key=True)
    host_group_id = Column('host_group_id', BINARY(16), ForeignKey('host_group.id'), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'direction': self.direction,
          'host_group_id': get_uuid_string_from_bytes(self.host_group_id),
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Dashboard_Widget_Config (Base):
    __tablename__='dashboard_widget_config'
    panel_id = Column('panel_id', INTEGER(11), ForeignKey('dashboard_tab_config.id'), primary_key=False)
    help = Column('help', VARCHAR(128), primary_key=False)
    type_id = Column('type_id', INTEGER(11), ForeignKey('dashboard_custom_type.id'), primary_key=False)
    color = Column('color', VARCHAR(15), primary_key=False)
    media = Column('media', MEDIUMBLOB, primary_key=False)
    title = Column('title', VARCHAR(128), primary_key=False)
    refresh = Column('refresh', INTEGER(11), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    params = Column('params', TEXT, primary_key=False)
    user = Column('user', VARCHAR(128), primary_key=False)
    file = Column('file', VARCHAR(128), primary_key=False)
    height = Column('height', INTEGER(11), primary_key=False)
    type = Column('type', VARCHAR(50), primary_key=False)
    col = Column('col', INTEGER(11), primary_key=False)
    fil = Column('fil', INTEGER(11), primary_key=False)
    asset = Column('asset', VARCHAR(128), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'panel_id': self.panel_id,
          'help': self.help,
          'type_id': self.type_id,
          'color': self.color,
          'media': self.media,
          'title': self.title,
          'refresh': self.refresh,
          'id': self.id,
          'params': self.params,
          'user': self.user,
          'file': self.file,
          'height': self.height,
          'type': self.type,
          'col': self.col,
          'fil': self.fil,
          'asset': self.asset,
        }


class Repository_Relationships (Base):
    __tablename__='repository_relationships'
    id_document = Column('id_document', INTEGER(11), primary_key=False)
    keyname = Column('keyname', VARCHAR(128), primary_key=False)
    type = Column('type', VARCHAR(16), primary_key=False)
    id = Column('id', INTEGER(10), ForeignKey('repository.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id_document': self.id_document,
          'keyname': self.keyname,
          'type': self.type,
          'id': self.id,
        }


class Acl_Entities_Assets (Base):
    __tablename__='acl_entities_assets'
    asset_id = Column('asset_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    entity_id = Column('entity_id', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'asset_id': get_uuid_string_from_bytes(self.asset_id),
          'entity_id': get_uuid_string_from_bytes(self.entity_id),
        }


class Control_Panel (Base):
    __tablename__='control_panel'
    max_c = Column('max_c', INTEGER(11), primary_key=False)
    max_a = Column('max_a', INTEGER(11), primary_key=False)
    c_sec_level = Column('c_sec_level', FLOAT, primary_key=False)
    a_sec_level = Column('a_sec_level', FLOAT, primary_key=False)
    time_range = Column('time_range', VARCHAR(5), primary_key=True)
    max_c_date = Column('max_c_date', DATETIME, primary_key=False)
    rrd_type = Column('rrd_type', VARCHAR(6), primary_key=True)
    id = Column('id', VARCHAR(128), primary_key=True)
    max_a_date = Column('max_a_date', DATETIME, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'max_c': self.max_c,
          'max_a': self.max_a,
          'c_sec_level': self.c_sec_level,
          'a_sec_level': self.a_sec_level,
          'time_range': self.time_range,
          'max_c_date': self.max_c_date,
          'rrd_type': self.rrd_type,
          'id': self.id,
          'max_a_date': self.max_a_date,
        }


class Pass_History (Base):
    __tablename__='pass_history'
    av_pass = Column('pass', VARCHAR(41), primary_key=False)
    hist_number = Column('hist_number', INTEGER(11), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', INTEGER(10), primary_key=True)
    user = Column('user', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'av_pass': self.av_pass,
          'hist_number': self.hist_number,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'user': self.user,
        }


class Incident_Subscrip (Base):
    __tablename__='incident_subscrip'
    incident_id = Column('incident_id', INTEGER, ForeignKey('incident.id'), primary_key=True)
    login = Column('login', VARCHAR(64), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'incident_id': self.incident_id,
          'login': self.login,
        }


class Alarm_Hosts (Base):
    __tablename__='alarm_hosts'
    id_alarm = Column('id_alarm', BINARY(16), ForeignKey('alarm.backlog_id'), primary_key=True)
    id_host = Column('id_host', BINARY(16), ForeignKey('host.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id_alarm': get_uuid_string_from_bytes(self.id_alarm),
          'id_host': get_uuid_string_from_bytes(self.id_host),
        }


class Incident_Tag_Descr (Base):
    __tablename__='incident_tag_descr'
    id = Column('id', INTEGER, primary_key=True)
    descr = Column('descr', TEXT, primary_key=False)
    name = Column('name', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id': self.id,
          'descr': self.descr,
          'name': self.name,
        }


class Vuln_Nessus_Preferences (Base):
    __tablename__='vuln_nessus_preferences'
    category = Column('category', VARCHAR(255), primary_key=False)
    nessus_id = Column('nessus_id', VARCHAR(255), primary_key=True)
    type = Column('type', CHAR(1), primary_key=False)
    id = Column('id', VARCHAR(255), primary_key=False)
    value = Column('value', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'category': self.category,
          'nessus_id': self.nessus_id,
          'type': self.type,
          'id': self.id,
          'value': self.value,
        }


class Host_Software (Base):
    __tablename__='host_software'
    extra = Column('extra', TEXT, primary_key=False)
    cpe = Column('cpe', VARCHAR(255), ForeignKey('software_cpe.cpe'), primary_key=True)
    last_modified = Column('last_modified', TIMESTAMP, primary_key=False)
    source_id = Column('source_id', INTEGER(11), primary_key=False)
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    banner = Column('banner', TEXT, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'extra': self.extra,
          'cpe': self.cpe,
          'last_modified': self.last_modified,
          'source_id': self.source_id,
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'banner': self.banner,
        }


class Server_Role (Base):
    __tablename__='server_role'
    server_id = Column('server_id', BINARY(16), ForeignKey('server.id'), primary_key=True)
    correlate = Column('correlate', TINYINT(1), primary_key=False)
    resend_alarm = Column('resend_alarm', TINYINT(1), primary_key=False)
    alarms_to_syslog = Column('alarms_to_syslog', TINYINT(1), primary_key=False)
    sign = Column('sign', INTEGER(10), primary_key=False)
    resend_event = Column('resend_event', TINYINT(1), primary_key=False)
    cross_correlate = Column('cross_correlate', TINYINT(1), primary_key=False)
    qualify = Column('qualify', TINYINT(1), primary_key=False)
    reputation = Column('reputation', TINYINT(1), primary_key=False)
    sim = Column('sim', TINYINT(1), primary_key=False)
    sem = Column('sem', TINYINT(1), primary_key=False)
    store = Column('store', TINYINT(1), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'server_id': get_uuid_string_from_bytes(self.server_id),
          'correlate': self.correlate,
          'resend_alarm': self.resend_alarm,
          'alarms_to_syslog': self.alarms_to_syslog,
          'sign': self.sign,
          'resend_event': self.resend_event,
          'cross_correlate': self.cross_correlate,
          'qualify': self.qualify,
          'reputation': self.reputation,
          'sim': self.sim,
          'sem': self.sem,
          'store': self.store,
        }


class Incident_Event (Base):
    __tablename__='incident_event'
    incident_id = Column('incident_id', INTEGER, ForeignKey('incident.id'), primary_key=False)
    dst_ips = Column('dst_ips', VARCHAR(255), primary_key=False)
    dst_ports = Column('dst_ports', VARCHAR(255), primary_key=False)
    src_ips = Column('src_ips', VARCHAR(255), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    src_ports = Column('src_ports', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'incident_id': self.incident_id,
          'dst_ips': self.dst_ips,
          'dst_ports': self.dst_ports,
          'src_ips': self.src_ips,
          'id': self.id,
          'src_ports': self.src_ports,
        }


class Dashboard_Tab_Options (Base):
    __tablename__='dashboard_tab_options'
    tab_order = Column('tab_order', INTEGER(11), primary_key=False)
    visible = Column('visible', TINYINT(1), primary_key=False)
    id = Column('id', INTEGER(11), ForeignKey('dashboard_tab_config.id'), primary_key=True)
    user = Column('user', VARCHAR(128), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'tab_order': self.tab_order,
          'visible': self.visible,
          'id': self.id,
          'user': self.user,
        }


class Policy_Taxonomy_Reference (Base):
    __tablename__='policy_taxonomy_reference'
    subcategory_id = Column('subcategory_id', INTEGER(11), ForeignKey('subcategory.id'), primary_key=False)
    product_type_id = Column('product_type_id', INTEGER(11), ForeignKey('product_type.id'), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    category_id = Column('category_id', INTEGER(11), ForeignKey('category.id'), primary_key=False)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'subcategory_id': self.subcategory_id,
          'product_type_id': self.product_type_id,
          'id': self.id,
          'category_id': self.category_id,
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Acl_Perm (Base):
    __tablename__='acl_perm'
    granularity_net = Column('granularity_net', TINYINT(1), primary_key=False)
    description = Column('description', VARCHAR(128), primary_key=False)
    granularity_sensor = Column('granularity_sensor', TINYINT(1), primary_key=False)
    enabled = Column('enabled', TINYINT(4), primary_key=False)
    value = Column('value', VARCHAR(255), primary_key=False)
    ord = Column('ord', VARCHAR(5), primary_key=False)
    type = Column('type', ENUM('MENU'), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    name = Column('name', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    #acl_templates_perms=relationship('Acl_Templates_Perms', backref='acl_perm', primaryjoin='id == Acl_Templates_Perms.ac_perm_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'granularity_net': self.granularity_net,
          'description': self.description,
          'granularity_sensor': self.granularity_sensor,
          'enabled': self.enabled,
          'value': self.value,
          'ord': self.ord,
          'type': self.type,
          'id': self.id,
          'name': self.name,
          #'acl_templates_perms': [i.serialize for i in self.acl_templates_perms],
        }


class Sessions (Base):
    __tablename__='sessions'
    logon_date = Column('logon_date', DATETIME, primary_key=False)
    ip = Column('ip', VARCHAR(40), primary_key=False)
    agent = Column('agent', VARCHAR(255), primary_key=False)
    activity = Column('activity', DATETIME, primary_key=False)
    login = Column('login', VARCHAR(64), primary_key=True)
    id = Column('id', VARCHAR(64), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'logon_date': self.logon_date,
          'ip': self.ip,
          'agent': self.agent,
          'activity': self.activity,
          'login': self.login,
          'id': self.id,
        }


class Vuln_Nessus_Family (Base):
    __tablename__='vuln_nessus_family'
    id = Column('id', INTEGER(11), primary_key=True)
    name = Column('name', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    #vuln_nessus_settings_family=relationship('Vuln_Nessus_Settings_Family', backref='vuln_nessus_family', primaryjoin='id == Vuln_Nessus_Settings_Family.fid' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'id': self.id,
          'name': self.name,
          #'vuln_nessus_settings_family': [i.serialize for i in self.vuln_nessus_settings_family],
        }


class Alarm_Tags (Base):
    __tablename__='alarm_tags'
    id_alarm = Column('id_alarm', BINARY(16), ForeignKey('alarm.backlog_id'), primary_key=True)
    id_tag = Column('id_tag', INTEGER, ForeignKey('tags_alarm.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id_alarm': get_uuid_string_from_bytes(self.id_alarm),
          'id_tag': self.id_tag,
        }


class Policy_Risk_Reference (Base):
    __tablename__='policy_risk_reference'
    priority = Column('priority', SMALLINT, primary_key=False)
    reliability = Column('reliability', SMALLINT, primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'priority': self.priority,
          'reliability': self.reliability,
          'id': self.id,
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Net_Cidrs (Base):
    __tablename__='net_cidrs'
    begin = Column('begin', VARBINARY(16), primary_key=True)
    cidr = Column('cidr', VARCHAR(20), primary_key=True)
    end = Column('end', VARBINARY(16), primary_key=True)
    net_id = Column('net_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'begin': get_ip_str_from_bytes(self.begin),
          'cidr': self.cidr,
          'end': get_ip_str_from_bytes(self.end),
          'net_id': get_uuid_string_from_bytes(self.net_id),
        }


class Net (Base):
    __tablename__='net'
    rrd_profile = Column('rrd_profile', VARCHAR(64), primary_key=False)
    name = Column('name', VARCHAR(128), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    threshold_c = Column('threshold_c', INTEGER(11), primary_key=False)
    external_net = Column('external_net', TINYINT(1), primary_key=False)
    threshold_a = Column('threshold_a', INTEGER(11), primary_key=False)
    alert = Column('alert', INTEGER(11), primary_key=False)
    ips = Column('ips', TEXT, primary_key=False)
    asset = Column('asset', INTEGER(11), primary_key=False)
    permissions = Column('permissions', BINARY(8), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    persistence = Column('persistence', INTEGER(11), primary_key=False)
    icon = Column('icon', MEDIUMBLOB, primary_key=False)
    #
    # Relations:
    #
    net_cidrs=relationship('Net_Cidrs', primaryjoin= id == Net_Cidrs.net_id , lazy='select')
    #net_group_reference=relationship('Net_Group_Reference', backref='net', primaryjoin='id == Net_Group_Reference.net_id' , lazy='dynamic')
    #net_qualification=relationship('Net_Qualification', backref='net', primaryjoin='id == Net_Qualification.net_id' , lazy='dynamic')
    #net_scan=relationship('Net_Scan', backref='net', primaryjoin='id == Net_Scan.net_id' , lazy='dynamic')
    #net_vulnerability=relationship('Net_Vulnerability', backref='net', primaryjoin='id == Net_Vulnerability.net_id' , lazy='dynamic')
    #plugin_scheduler_net_reference=relationship('Plugin_Scheduler_Net_Reference', backref='net', primaryjoin='id == Plugin_Scheduler_Net_Reference.net_id' , lazy='dynamic')
    #policy_net_reference=relationship('Policy_Net_Reference', backref='net', primaryjoin='id == Policy_Net_Reference.net_id' , lazy='dynamic')
    #net_sensor_reference=relationship('Net_Sensor_Reference', backref='net', primaryjoin='id == Net_Sensor_Reference.net_id' , lazy='dynamic')
    #acl_assets=relationship('Acl_Assets', backref='net', primaryjoin='id == Acl_Assets.asset_id' , lazy='dynamic')
    #acl_entities_assets=relationship('Acl_Entities_Assets', backref='net', primaryjoin='id == Acl_Entities_Assets.asset_id' , lazy='dynamic')
    #alarm_nets=relationship('Alarm_Nets', backref='net', primaryjoin='id == Alarm_Nets.id_net' , lazy='dynamic')
    #host_net_reference=relationship('Host_Net_Reference', backref='net', primaryjoin='id == Host_Net_Reference.net_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'rrd_profile': self.rrd_profile,
          'name': self.name,
          'descr': self.descr,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'threshold_c': self.threshold_c,
          'external_net': self.external_net,
          'threshold_a': self.threshold_a,
          'alert': self.alert,
          'ips': self.ips,
          'asset': self.asset,
          'permissions': self.permissions,
          'id': get_uuid_string_from_bytes(self.id),
          'persistence': self.persistence,
          'icon': self.icon,
          #'net_cidrs': [i.serialize for i in self.net_cidrs],
          #'net_group_reference': [i.serialize for i in self.net_group_reference],
          #'net_qualification': [i.serialize for i in self.net_qualification],
          #'net_scan': [i.serialize for i in self.net_scan],
          #'net_vulnerability': [i.serialize for i in self.net_vulnerability],
          #'plugin_scheduler_net_reference': [i.serialize for i in self.plugin_scheduler_net_reference],
          #'policy_net_reference': [i.serialize for i in self.policy_net_reference],
          #'net_sensor_reference': [i.serialize for i in self.net_sensor_reference],
          #'acl_assets': [i.serialize for i in self.acl_assets],
          #'acl_entities_assets': [i.serialize for i in self.acl_entities_assets],
          #'alarm_nets': [i.serialize for i in self.alarm_nets],
          #'host_net_reference': [i.serialize for i in self.host_net_reference],
        }



class Incident_Vulns_Seq (Base):
    __tablename__='incident_vulns_seq'
    id = Column('id', INTEGER(11), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id': self.id,
        }


class Incident_Ticket_Seq (Base):
    __tablename__='incident_ticket_seq'
    id = Column('id', INTEGER(11), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id': self.id,
        }



class Plugin_Group (Base):
    __tablename__='plugin_group'
    group_ctx = Column('group_ctx', BINARY(16), primary_key=True, index=True)
    group_id = Column('group_id', BINARY(16), primary_key=True, index=True)
    name = Column('name', VARCHAR(125), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    #policy_plugin_group_reference=relationship('Policy_Plugin_Group_Reference', backref='plugin_group', primaryjoin='group_id == Policy_Plugin_Group_Reference.plugin_group_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'group_ctx': get_uuid_string_from_bytes(self.group_ctx),
          'group_id': get_uuid_string_from_bytes(self.group_id),
          'name': self.name,
          'descr': self.descr,
          #'policy_plugin_group_reference': [i.serialize for i in self.policy_plugin_group_reference],
        }


class Host_Net_Reference (Base):
    __tablename__='host_net_reference'
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    net_id = Column('net_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'net_id': get_uuid_string_from_bytes(self.net_id),
        }


class Device_Types (Base):
    __tablename__='device_types'
    av_class = Column('class', INTEGER, primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    name = Column('name', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    #host_types=relationship('Host_Types', backref='device_types', primaryjoin='id == Host_Types.type' , lazy='dynamic')
#TODO: ---#    #host_types=relationship('Host_Types', backref='device_types', primaryjoin='id == Host_Types.subtype' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'av_class': self.av_class,
          'id': self.id,
          'name': self.name,
          #'host_types': [i.serialize for i in self.host_types],
          #'host_types': [i.serialize for i in self.host_types],
        }


class Vuln_Jobs (Base):
    __tablename__='vuln_jobs'
    status = Column('status', CHAR(1), primary_key=False)
    scan_SUBMIT = Column('scan_SUBMIT', DATETIME, primary_key=False)
    resolve_names = Column('resolve_names', TINYINT(1), primary_key=False)
    job_TYPE = Column('job_TYPE', CHAR(1), primary_key=False)
    scan_PRIORITY = Column('scan_PRIORITY', TINYINT(1), primary_key=False)
    notify = Column('notify', TEXT, primary_key=False)
    scan_SERVER = Column('scan_SERVER', INTEGER(11), ForeignKey('vuln_nessus_servers.id'), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    meth_Wfile = Column('meth_Wfile', TEXT, primary_key=False)
    scan_ASSIGNED = Column('scan_ASSIGNED', VARCHAR(64), primary_key=False)
    scan_PID = Column('scan_PID', INTEGER(11), primary_key=False)
    meth_TIMEOUT = Column('meth_TIMEOUT', INTEGER(6), primary_key=False)
    scan_NEXT = Column('scan_NEXT', VARCHAR(14), primary_key=False)
    authorized = Column('authorized', TINYINT(1), primary_key=False)
    author_uname = Column('author_uname', TEXT, primary_key=False)
    report_id = Column('report_id', INTEGER(11), primary_key=False)
    username = Column('username', VARCHAR(64), primary_key=False)
    failed_attempts = Column('failed_attempts', TINYINT(1), primary_key=False)
    meth_CPLUGINS = Column('meth_CPLUGINS', TEXT, primary_key=False)
    meth_Wcheck = Column('meth_Wcheck', TEXT, primary_key=False)
    tracker_id = Column('tracker_id', INTEGER(11), primary_key=False)
    credentials = Column('credentials', VARCHAR(128), primary_key=False)
    fk_name = Column('fk_name', VARCHAR(50), primary_key=False)
    scan_END = Column('scan_END', DATETIME, primary_key=False)
    meth_CUSTOM = Column('meth_CUSTOM', ENUM('N','A','R'), primary_key=False)
    name = Column('name', VARCHAR(50), primary_key=True)
    meth_SCHED = Column('meth_SCHED', CHAR(1), primary_key=False)
    meth_TARGET = Column('meth_TARGET', TEXT, primary_key=False)
    meth_Ucheck = Column('meth_Ucheck', TEXT, primary_key=False)
    scan_START = Column('scan_START', DATETIME, primary_key=False)
    meth_CRED = Column('meth_CRED', INTEGER(11), primary_key=False)
    meth_VSET = Column('meth_VSET', INTEGER(11), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'status': self.status,
          'scan_SUBMIT': self.scan_SUBMIT,
          'resolve_names': self.resolve_names,
          'job_TYPE': self.job_TYPE,
          'scan_PRIORITY': self.scan_PRIORITY,
          'notify': self.notify,
          'scan_SERVER': self.scan_SERVER,
          'id': self.id,
          'meth_Wfile': self.meth_Wfile,
          'scan_ASSIGNED': self.scan_ASSIGNED,
          'scan_PID': self.scan_PID,
          'meth_TIMEOUT': self.meth_TIMEOUT,
          'scan_NEXT': self.scan_NEXT,
          'authorized': self.authorized,
          'author_uname': self.author_uname,
          'report_id': self.report_id,
          'username': self.username,
          'failed_attempts': self.failed_attempts,
          'meth_CPLUGINS': self.meth_CPLUGINS,
          'meth_Wcheck': self.meth_Wcheck,
          'tracker_id': self.tracker_id,
          'credentials': self.credentials,
          'fk_name': self.fk_name,
          'scan_END': self.scan_END,
          'meth_CUSTOM': self.meth_CUSTOM,
          'name': self.name,
          'meth_SCHED': self.meth_SCHED,
          'meth_TARGET': self.meth_TARGET,
          'meth_Ucheck': self.meth_Ucheck,
          'scan_START': self.scan_START,
          'meth_CRED': self.meth_CRED,
          'meth_VSET': self.meth_VSET,
        }


class Signature_Group_Reference (Base):
    __tablename__='signature_group_reference'
    signature_group_id = Column('signature_group_id', INTEGER(10), ForeignKey('signature_group.id'), primary_key=True)
    signature_id = Column('signature_id', INTEGER(10), ForeignKey('signature.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'signature_group_id': self.signature_group_id,
          'signature_id': self.signature_id,
        }


class Net_Group (Base):
    __tablename__='net_group'
    rrd_profile = Column('rrd_profile', VARCHAR(64), primary_key=False)
    name = Column('name', VARCHAR(128), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    threshold_c = Column('threshold_c', INTEGER(11), primary_key=False)
    threshold_a = Column('threshold_a', INTEGER(11), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    permissions = Column('permissions', BINARY(8), primary_key=False)
    #
    # Relations:
    #
    #net_group_reference=relationship('Net_Group_Reference', backref='net_group', primaryjoin='id == Net_Group_Reference.net_group_id' , lazy='dynamic')
    #net_group_scan=relationship('Net_Group_Scan', backref='net_group', primaryjoin='id == Net_Group_Scan.net_group_id' , lazy='dynamic')
    #plugin_scheduler_netgroup_reference=relationship('Plugin_Scheduler_Netgroup_Reference', backref='net_group', primaryjoin='id == Plugin_Scheduler_Netgroup_Reference.netgroup_id' , lazy='dynamic')
    #policy_net_group_reference=relationship('Policy_Net_Group_Reference', backref='net_group', primaryjoin='id == Policy_Net_Group_Reference.net_group_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'rrd_profile': self.rrd_profile,
          'name': self.name,
          'descr': self.descr,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'threshold_c': self.threshold_c,
          'threshold_a': self.threshold_a,
          'id': get_uuid_string_from_bytes(self.id),
          'permissions': self.permissions,
          #'net_group_reference': [i.serialize for i in self.net_group_reference],
          #'net_group_scan': [i.serialize for i in self.net_group_scan],
          #'plugin_scheduler_netgroup_reference': [i.serialize for i in self.plugin_scheduler_netgroup_reference],
          #'policy_net_group_reference': [i.serialize for i in self.policy_net_group_reference],
        }


class Signature_Group (Base):
    __tablename__='signature_group'
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', INTEGER(10), primary_key=True)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    name = Column('name', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    #signature_group_reference=relationship('Signature_Group_Reference', backref='signature_group', primaryjoin='id == Signature_Group_Reference.signature_group_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'descr': self.descr,
          'name': self.name,
          #'signature_group_reference': [i.serialize for i in self.signature_group_reference],
        }


class Policy_Role_Reference (Base):
    __tablename__='policy_role_reference'
    correlate = Column('correlate', TINYINT(1), primary_key=False)
    resend_alarm = Column('resend_alarm', TINYINT(1), primary_key=False)
    sign = Column('sign', INTEGER(10), primary_key=False)
    resend_event = Column('resend_event', TINYINT(1), primary_key=False)
    cross_correlate = Column('cross_correlate', TINYINT(1), primary_key=False)
    qualify = Column('qualify', TINYINT(1), primary_key=False)
    reputation = Column('reputation', TINYINT(1), primary_key=False)
    sim = Column('sim', TINYINT(1), primary_key=False)
    sem = Column('sem', TINYINT(1), primary_key=False)
    store = Column('store', TINYINT(1), primary_key=False)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'correlate': self.correlate,
          'resend_alarm': self.resend_alarm,
          'sign': self.sign,
          'resend_event': self.resend_event,
          'cross_correlate': self.cross_correlate,
          'qualify': self.qualify,
          'reputation': self.reputation,
          'sim': self.sim,
          'sem': self.sem,
          'store': self.store,
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Net_Sensor_Reference (Base):
    __tablename__='net_sensor_reference'
    sensor_id = Column('sensor_id', BINARY(16), ForeignKey('sensor.id'), primary_key=True)
    net_id = Column('net_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
          'net_id': get_uuid_string_from_bytes(self.net_id),
        }


class Plugin_Scheduler_Host_Reference (Base):
    __tablename__='plugin_scheduler_host_reference'
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    plugin_scheduler_id = Column('plugin_scheduler_id', BINARY(16), ForeignKey('plugin_scheduler.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'plugin_scheduler_id': get_uuid_string_from_bytes(self.plugin_scheduler_id),
        }


class Web_Interfaces (Base):
    __tablename__='web_interfaces'
    status = Column('status', INTEGER(1), primary_key=False)
    ip = Column('ip', VARBINARY(16), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    name = Column('name', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'status': self.status,
          'ip': get_ip_str_from_bytes(self.ip),
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'name': self.name,
        }


class Vuln_Nessus_Plugins (Base):
    __tablename__='vuln_nessus_plugins'
    category = Column('category', INTEGER(11), primary_key=False)
    family = Column('family', INTEGER(11), primary_key=False)
    xref = Column('xref', BLOB, primary_key=False)
    cve_id = Column('cve_id', VARCHAR(255), primary_key=False)
    description = Column('description', BLOB, primary_key=False)
    copyright = Column('copyright', VARCHAR(255), primary_key=False)
    created = Column('created', VARCHAR(14), primary_key=False)
    deleted = Column('deleted', VARCHAR(14), primary_key=False)
    oid = Column('oid', VARCHAR(50), primary_key=False)
    enabled = Column('enabled', CHAR(1), primary_key=False)
    modified = Column('modified', VARCHAR(14), primary_key=False)
    summary = Column('summary', VARCHAR(255), primary_key=False)
    bugtraq_id = Column('bugtraq_id', VARCHAR(255), primary_key=False)
    version = Column('version', VARCHAR(255), primary_key=False)
    custom_risk = Column('custom_risk', INTEGER(1), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    risk = Column('risk', INTEGER(11), primary_key=False)
    name = Column('name', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    #vuln_nessus_settings_plugins=relationship('Vuln_Nessus_Settings_Plugins', backref='vuln_nessus_plugins', primaryjoin='id == Vuln_Nessus_Settings_Plugins.id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'category': self.category,
          'family': self.family,
          'xref': self.xref,
          'cve_id': self.cve_id,
          'description': self.description,
          'copyright': self.copyright,
          'created': self.created,
          'deleted': self.deleted,
          'oid': self.oid,
          'enabled': self.enabled,
          'modified': self.modified,
          'summary': self.summary,
          'bugtraq_id': self.bugtraq_id,
          'version': self.version,
          'custom_risk': self.custom_risk,
          'id': self.id,
          'risk': self.risk,
          'name': self.name,
          #'vuln_nessus_settings_plugins': [i.serialize for i in self.vuln_nessus_settings_plugins],
        }


class Policy_Net_Group_Reference (Base):
    __tablename__='policy_net_group_reference'
    direction = Column('direction', ENUM('source','dest'), primary_key=True)
    net_group_id = Column('net_group_id', BINARY(16), ForeignKey('net_group.id'), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'direction': self.direction,
          'net_group_id': get_uuid_string_from_bytes(self.net_group_id),
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Webservice_Default (Base):
    __tablename__='webservice_default'
    field = Column('field', VARCHAR(64), primary_key=True)
    ws_id = Column('ws_id', BINARY(16), ForeignKey('webservice.id'), primary_key=True)
    value = Column('value', VARCHAR(512), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'field': self.field,
          'ws_id': get_uuid_string_from_bytes(self.ws_id),
          'value': self.value,
        }


class Policy_Actions (Base):
    __tablename__='policy_actions'
    action_id = Column('action_id', BINARY(16), ForeignKey('action.id'), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'action_id': get_uuid_string_from_bytes(self.action_id),
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Net_Vulnerability (Base):
    __tablename__='net_vulnerability'
    scan_date = Column('scan_date', DATETIME, primary_key=True)
    net_id = Column('net_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    vulnerability = Column('vulnerability', INTEGER(11), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'scan_date': self.scan_date,
          'net_id': get_uuid_string_from_bytes(self.net_id),
          'vulnerability': self.vulnerability,
        }



class Server_Forward_Role (Base):
    __tablename__='server_forward_role'
    priority = Column('priority', SMALLINT(5), primary_key=False)
    server_dst_id = Column('server_dst_id', BINARY(16), ForeignKey('server.id'), primary_key=True)
    server_src_id = Column('server_src_id', BINARY(16), ForeignKey('server.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'priority': self.priority,
          'server_dst_id': get_uuid_string_from_bytes(self.server_dst_id),
          'server_src_id': get_uuid_string_from_bytes(self.server_src_id),
        }


class Task_Inventory (Base):
    __tablename__='task_inventory'
    task_type = Column('task_type', INTEGER, ForeignKey('host_source_reference.id'), primary_key=False)
    task_enable = Column('task_enable', TINYINT(1), primary_key=False)
    task_name = Column('task_name', VARCHAR(255), primary_key=False)
    task_id = Column('task_id', BIGINT, primary_key=True)
    task_targets = Column('task_targets', VARCHAR(255), primary_key=False)
    task_sensor = Column('task_sensor', BINARY(16), ForeignKey('sensor.id'), primary_key=False)
    task_period = Column('task_period', INTEGER, primary_key=False)
    task_params = Column('task_params', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'task_type': self.task_type,
          'task_enable': self.task_enable,
          'task_name': self.task_name,
          'task_id': self.task_id,
          'task_targets': self.task_targets,
          'task_sensor': get_uuid_string_from_bytes(self.task_sensor),
          'task_period': self.task_period,
          'task_params': self.task_params,
        }


class Map_Element (Base):
    __tablename__='map_element'
    map_id = Column('map_id', BINARY(16), ForeignKey('map.id'), primary_key=False)
    ossim_element_key = Column('ossim_element_key', VARCHAR(255), primary_key=False)
    y = Column('y', VARCHAR(255), primary_key=False)
    x = Column('x', VARCHAR(255), primary_key=False)
    type = Column('type', ENUM('host','sensor','network','server'), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'map_id': get_uuid_string_from_bytes(self.map_id),
          'ossim_element_key': self.ossim_element_key,
          'y': self.y,
          'x': self.x,
          'type': self.type,
          'id': get_uuid_string_from_bytes(self.id),
        }


class Host_Mac_Vendors (Base):
    __tablename__='host_mac_vendors'
    mac = Column('mac', BINARY(3), primary_key=True)
    vendor = Column('vendor', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    #host_ip=relationship('Host_Ip', backref='host_mac_vendors', primaryjoin='mac == Host_Ip.mac' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'mac': self.mac,
          'vendor': self.vendor,
          #'host_ip': [i.serialize for i in self.host_ip],
        }


class Host_Property_Reference (Base):
    __tablename__='host_property_reference'
    ord = Column('ord', INTEGER(11), primary_key=False)
    description = Column('description', VARCHAR(128), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    name = Column('name', VARCHAR(100), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'ord': self.ord,
          'description': self.description,
          'id': self.id,
          'name': self.name,
        }


class Dashboard_Tab_Config (Base):
    __tablename__='dashboard_tab_config'
    title = Column('title', VARCHAR(128), primary_key=False)
    layout = Column('layout', INTEGER(11), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    icon = Column('icon', VARCHAR(128), primary_key=False)
    user = Column('user', VARCHAR(128), primary_key=True)
    #
    # Relations:
    #
    #dashboard_widget_config=relationship('Dashboard_Widget_Config', backref='dashboard_tab_config', primaryjoin='id == Dashboard_Widget_Config.panel_id' , lazy='dynamic')
    #dashboard_tab_options=relationship('Dashboard_Tab_Options', backref='dashboard_tab_config', primaryjoin='id == Dashboard_Tab_Options.id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'title': self.title,
          'layout': self.layout,
          'id': self.id,
          'icon': self.icon,
          'user': self.user,
          #'dashboard_widget_config': [i.serialize for i in self.dashboard_widget_config],
          #'dashboard_tab_options': [i.serialize for i in self.dashboard_tab_options],
        }


class Host_Plugin_Sid (Base):
    __tablename__='host_plugin_sid'
    plugin_id = Column('plugin_id', INTEGER(11), ForeignKey('plugin_sid.plugin_id'), primary_key=True)
    ctx = Column('ctx', BINARY(16), ForeignKey('plugin_sid.plugin_ctx'), primary_key=True)
    host_ip = Column('host_ip', VARBINARY(16), ForeignKey('host.id'), primary_key=True)
    plugin_sid = Column('plugin_sid', INTEGER(11), ForeignKey('plugin_sid.sid'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'plugin_id': self.plugin_id,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'host_ip': get_ip_str_from_bytes(self.host_ip),
          'plugin_sid': self.plugin_sid,
        }


class Vuln_Nessus_Settings (Base):
    __tablename__='vuln_nessus_settings'
    update_host_tracker = Column('update_host_tracker', TINYINT(1), primary_key=False)
    description = Column('description', VARCHAR(255), primary_key=False)
    auto_cat_status = Column('auto_cat_status', INTEGER(10), primary_key=False)
    deleted = Column('deleted', ENUM('0','1'), primary_key=False)
    autoenable = Column('autoenable', CHAR(1), primary_key=False)
    auto_fam_status = Column('auto_fam_status', INTEGER(10), primary_key=False)
    owner = Column('owner', VARCHAR(255), primary_key=False)
    type = Column('type', CHAR(1), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    name = Column('name', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    #vuln_nessus_settings_category=relationship('Vuln_Nessus_Settings_Category', backref='vuln_nessus_settings', primaryjoin='id == Vuln_Nessus_Settings_Category.sid' , lazy='dynamic')
    #vuln_nessus_settings_family=relationship('Vuln_Nessus_Settings_Family', backref='vuln_nessus_settings', primaryjoin='id == Vuln_Nessus_Settings_Family.sid' , lazy='dynamic')
    #vuln_nessus_settings_plugins=relationship('Vuln_Nessus_Settings_Plugins', backref='vuln_nessus_settings', primaryjoin='id == Vuln_Nessus_Settings_Plugins.sid' , lazy='dynamic')
    #vuln_nessus_settings_preferences=relationship('Vuln_Nessus_Settings_Preferences', backref='vuln_nessus_settings', primaryjoin='id == Vuln_Nessus_Settings_Preferences.sid' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'update_host_tracker': self.update_host_tracker,
          'description': self.description,
          'auto_cat_status': self.auto_cat_status,
          'deleted': self.deleted,
          'autoenable': self.autoenable,
          'auto_fam_status': self.auto_fam_status,
          'owner': self.owner,
          'type': self.type,
          'id': self.id,
          'name': self.name,
          #'vuln_nessus_settings_category': [i.serialize for i in self.vuln_nessus_settings_category],
          #'vuln_nessus_settings_family': [i.serialize for i in self.vuln_nessus_settings_family],
          #'vuln_nessus_settings_plugins': [i.serialize for i in self.vuln_nessus_settings_plugins],
          #'vuln_nessus_settings_preferences': [i.serialize for i in self.vuln_nessus_settings_preferences],
        }


class Vuln_Hosts (Base):
    __tablename__='vuln_hosts'
    status = Column('status', VARCHAR(45), primary_key=False)
    lastscandate = Column('lastscandate', DATETIME, primary_key=False)
    description = Column('description', VARCHAR(200), primary_key=False)
    scanstate = Column('scanstate', VARCHAR(25), primary_key=False)
    createdate = Column('createdate', DATETIME, primary_key=False)
    site_code = Column('site_code', VARCHAR(25), primary_key=False)
    hostname = Column('hostname', VARCHAR(64), primary_key=False)
    creport_id = Column('creport_id', INTEGER(11), primary_key=False)
    inactive = Column('inactive', TINYINT(1), primary_key=False)
    contact = Column('contact', VARCHAR(45), primary_key=False)
    hostip = Column('hostip', VARCHAR(40), primary_key=False)
    ORG = Column('ORG', VARCHAR(25), primary_key=False)
    report_id = Column('report_id', INTEGER(11), primary_key=False)
    os = Column('os', VARCHAR(100), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    workgroup = Column('workgroup', VARCHAR(25), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'status': self.status,
          'lastscandate': self.lastscandate,
          'description': self.description,
          'scanstate': self.scanstate,
          'createdate': self.createdate,
          'site_code': self.site_code,
          'hostname': self.hostname,
          'creport_id': self.creport_id,
          'inactive': self.inactive,
          'contact': self.contact,
          'hostip': self.hostip,
          'ORG': self.ORG,
          'report_id': self.report_id,
          'os': self.os,
          'id': self.id,
          'workgroup': self.workgroup,
        }


class Wireless_Clients (Base):
    __tablename__='wireless_clients'
    encoding = Column('encoding', VARCHAR(32), primary_key=False)
    ip = Column('ip', VARBINARY(16), primary_key=False)
    gpsminlat = Column('gpsminlat', FLOAT, primary_key=False)
    gpsmaxlon = Column('gpsmaxlon', FLOAT, primary_key=False)
    gpsmaxspd = Column('gpsmaxspd', FLOAT, primary_key=False)
    maxseenrate = Column('maxseenrate', INTEGER(11), primary_key=False)
    llc = Column('llc', INTEGER(11), primary_key=False)
    plugin_sid = Column('plugin_sid', INTEGER(11), primary_key=False)
    total = Column('total', INTEGER(11), primary_key=False)
    client_mac = Column('client_mac', VARCHAR(18), primary_key=True)
    ssid = Column('ssid', VARCHAR(255), primary_key=True)
    encryption = Column('encryption', VARCHAR(64), primary_key=False)
    datasize = Column('datasize', INTEGER(11), primary_key=False)
    type = Column('type', VARCHAR(32), primary_key=False)
    gpsminlon = Column('gpsminlon', FLOAT, primary_key=False)
    iptype = Column('iptype', VARCHAR(32), primary_key=False)
    weak = Column('weak', INTEGER(11), primary_key=False)
    dupeiv = Column('dupeiv', INTEGER(11), primary_key=False)
    mac = Column('mac', VARCHAR(18), primary_key=True)
    lasttime = Column('lasttime', TIMESTAMP, primary_key=False)
    data = Column('data', INTEGER(11), primary_key=False)
    gpsmaxalt = Column('gpsmaxalt', FLOAT, primary_key=False)
    firsttime = Column('firsttime', TIMESTAMP, primary_key=False)
    gpsminalt = Column('gpsminalt', FLOAT, primary_key=False)
    sensor = Column('sensor', VARBINARY(16), primary_key=True)
    notes = Column('notes', TINYTEXT, primary_key=False)
    gpsmaxlat = Column('gpsmaxlat', FLOAT, primary_key=False)
    maxrate = Column('maxrate', FLOAT, primary_key=False)
    channel = Column('channel', INTEGER(11), primary_key=False)
    crypt = Column('crypt', INTEGER(11), primary_key=False)
    gpsminspd = Column('gpsminspd', FLOAT, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'encoding': self.encoding,
          'ip': get_ip_str_from_bytes(self.ip),
          'gpsminlat': self.gpsminlat,
          'gpsmaxlon': self.gpsmaxlon,
          'gpsmaxspd': self.gpsmaxspd,
          'maxseenrate': self.maxseenrate,
          'llc': self.llc,
          'plugin_sid': self.plugin_sid,
          'total': self.total,
          'client_mac': self.client_mac,
          'ssid': self.ssid,
          'encryption': self.encryption,
          'datasize': self.datasize,
          'type': self.type,
          'gpsminlon': self.gpsminlon,
          'iptype': self.iptype,
          'weak': self.weak,
          'dupeiv': self.dupeiv,
          'mac': self.mac,
          'lasttime': self.lasttime,
          'data': self.data,
          'gpsmaxalt': self.gpsmaxalt,
          'firsttime': self.firsttime,
          'gpsminalt': self.gpsminalt,
          'sensor': get_ip_str_from_bytes(self.sensor),
          'notes': self.notes,
          'gpsmaxlat': self.gpsmaxlat,
          'maxrate': self.maxrate,
          'channel': self.channel,
          'crypt': self.crypt,
          'gpsminspd': self.gpsminspd,
        }


class Log_Config (Base):
    __tablename__='log_config'
    priority = Column('priority', INTEGER(10), primary_key=False)
    code = Column('code', INTEGER(10), primary_key=True)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    log = Column('log', TINYINT(1), primary_key=False)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'priority': self.priority,
          'code': self.code,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'log': self.log,
          'descr': self.descr,
        }


class Port (Base):
    __tablename__='port'
    port_number = Column('port_number', INTEGER(11), primary_key=True)
    protocol_name = Column('protocol_name', VARCHAR(12), primary_key=True, index=True)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=True)
    descr = Column('descr', VARCHAR(255), primary_key=False)
    service = Column('service', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    #port_group_reference=relationship('Port_Group_Reference', backref='port', primaryjoin='ctx == Port_Group_Reference.port_ctx' , lazy='dynamic')
#TODO: ---#    #port_group_reference=relationship('Port_Group_Reference', backref='port', primaryjoin='port_number == Port_Group_Reference.port_ctx' , lazy='dynamic')
#TODO: ---#    #port_group_reference=relationship('Port_Group_Reference', backref='port', primaryjoin='protocol_name == Port_Group_Reference.port_ctx' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'port_number': self.port_number,
          'protocol_name': self.protocol_name,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'descr': self.descr,
          'service': self.service,
          #'port_group_reference': [i.serialize for i in self.port_group_reference],
          #'port_group_reference': [i.serialize for i in self.port_group_reference],
          #'port_group_reference': [i.serialize for i in self.port_group_reference],
        }


class Policy_Sensor_Reference (Base):
    __tablename__='policy_sensor_reference'
    sensor_id = Column('sensor_id', BINARY(16), ForeignKey('sensor.id'), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Incident_Alarm (Base):
    __tablename__='incident_alarm'
    incident_id = Column('incident_id', INTEGER, ForeignKey('incident.id'), primary_key=False)
    event_id = Column('event_id', BINARY(16), primary_key=False)
    dst_ips = Column('dst_ips', VARCHAR(255), primary_key=False)
    alarm_group_id = Column('alarm_group_id', BINARY(16), primary_key=False)
    dst_ports = Column('dst_ports', VARCHAR(255), primary_key=False)
    backlog_id = Column('backlog_id', BINARY(16), primary_key=False)
    src_ips = Column('src_ips', VARCHAR(255), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    src_ports = Column('src_ports', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'incident_id': self.incident_id,
          'event_id': get_uuid_string_from_bytes(self.event_id),
          'dst_ips': self.dst_ips,
          'alarm_group_id': get_uuid_string_from_bytes(self.alarm_group_id),
          'dst_ports': self.dst_ports,
          'backlog_id': get_uuid_string_from_bytes(self.backlog_id),
          'src_ips': self.src_ips,
          'id': self.id,
          'src_ports': self.src_ports,
        }


class Acl_Entities (Base):
    __tablename__='acl_entities'
    server_id = Column('server_id', BINARY(16), ForeignKey('server.id'), primary_key=False)
    admin_user = Column('admin_user', VARCHAR(64), primary_key=False)
    name = Column('name', VARCHAR(128), primary_key=False)
    entity_type = Column('entity_type', ENUM('logical','context','engine'), primary_key=False)
    parent_id = Column('parent_id', BINARY(16), primary_key=False)
    address = Column('address', TINYTEXT, primary_key=False)
    timezone = Column('timezone', VARCHAR(64), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    #
    # Relations:
    #
    #corr_engine_contexts=relationship('Corr_Engine_Contexts', backref='acl_entities', primaryjoin='id == Corr_Engine_Contexts.engine_ctx' , lazy='dynamic')
#TODO: ---#    #corr_engine_contexts=relationship('Corr_Engine_Contexts', backref='acl_entities', primaryjoin='id == Corr_Engine_Contexts.event_ctx' , lazy='dynamic')
    #acl_entities_users=relationship('Acl_Entities_Users', backref='acl_entities', primaryjoin='id == Acl_Entities_Users.entity_id' , lazy='dynamic')
    #acl_entities_assets=relationship('Acl_Entities_Assets', backref='acl_entities', primaryjoin='id == Acl_Entities_Assets.entity_id' , lazy='dynamic')
    #acl_entities_stats=relationship('Acl_Entities_Stats', backref='acl_entities', primaryjoin='id == Acl_Entities_Stats.entity_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'server_id': get_uuid_string_from_bytes(self.server_id),
          'admin_user': self.admin_user,
          'name': self.name,
          'entity_type': self.entity_type,
          'parent_id': get_uuid_string_from_bytes(self.parent_id),
          'address': self.address,
          'timezone': self.timezone,
          'id': get_uuid_string_from_bytes(self.id),
          #'corr_engine_contexts': [i.serialize for i in self.corr_engine_contexts],
          #'corr_engine_contexts': [i.serialize for i in self.corr_engine_contexts],
          #'acl_entities_users': [i.serialize for i in self.acl_entities_users],
          #'acl_entities_assets': [i.serialize for i in self.acl_entities_assets],
          #'acl_entities_stats': [i.serialize for i in self.acl_entities_stats],
        }


class Incident_Tag (Base):
    __tablename__='incident_tag'
    incident_id = Column('incident_id', INTEGER, ForeignKey('incident.id'), primary_key=True)
    tag_id = Column('tag_id', INTEGER, ForeignKey('incident_tag_descr.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'incident_id': self.incident_id,
          'tag_id': self.tag_id,
        }


class Policy_Forward_Reference (Base):
    __tablename__='policy_forward_reference'
    priority = Column('priority', SMALLINT(5), primary_key=False)
    parent_id = Column('parent_id', BINARY(16), ForeignKey('server.id'), primary_key=True)
    child_id = Column('child_id', BINARY(16), ForeignKey('server.id'), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'priority': self.priority,
          'parent_id': get_uuid_string_from_bytes(self.parent_id),
          'child_id': get_uuid_string_from_bytes(self.child_id),
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Config (Base):
    __tablename__='config'
    value = Column('value', TEXT, primary_key=False)
    conf = Column('conf', VARCHAR(255), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'value': self.value,
          'conf': self.conf,
        }


class Plugin_Scheduler_Net_Reference (Base):
    __tablename__='plugin_scheduler_net_reference'
    net_id = Column('net_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    plugin_scheduler_id = Column('plugin_scheduler_id', BINARY(16), ForeignKey('plugin_scheduler.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'net_id': get_uuid_string_from_bytes(self.net_id),
          'plugin_scheduler_id': get_uuid_string_from_bytes(self.plugin_scheduler_id),
        }


class Map (Base):
    __tablename__='map'
    engine = Column('engine', ENUM('openlayers_op','openlayers_ve','openlayers_yahoo','openlayers_image'), primary_key=False)
    name = Column('name', VARCHAR(255), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    center_x = Column('center_x', VARCHAR(255), primary_key=False)
    zoom = Column('zoom', INTEGER(11), primary_key=False)
    engine_data3 = Column('engine_data3', TEXT, primary_key=False)
    engine_data2 = Column('engine_data2', TEXT, primary_key=False)
    engine_data1 = Column('engine_data1', MEDIUMTEXT, primary_key=False)
    center_y = Column('center_y', VARCHAR(255), primary_key=False)
    engine_data4 = Column('engine_data4', TEXT, primary_key=False)
    show_controls = Column('show_controls', TINYINT(1), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    #
    # Relations:
    #
    #map_element=relationship('Map_Element', backref='map', primaryjoin='id == Map_Element.map_id' , lazy='dynamic')
    #risk_indicators=relationship('Risk_Indicators', backref='map', primaryjoin='id == Risk_Indicators.map' , lazy='dynamic')
    #risk_maps=relationship('Risk_Maps', backref='map', primaryjoin='id == Risk_Maps.map' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'engine': self.engine,
          'name': self.name,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'center_x': self.center_x,
          'zoom': self.zoom,
          'engine_data3': self.engine_data3,
          'engine_data2': self.engine_data2,
          'engine_data1': self.engine_data1,
          'center_y': self.center_y,
          'engine_data4': self.engine_data4,
          'show_controls': self.show_controls,
          'id': get_uuid_string_from_bytes(self.id),
          #'map_element': [i.serialize for i in self.map_element],
          #'risk_indicators': [i.serialize for i in self.risk_indicators],
          #'risk_maps': [i.serialize for i in self.risk_maps],
        }


class Host_Services (Base):
    __tablename__='host_services'
    protocol = Column('protocol', INTEGER(11), ForeignKey('protocol.id'), primary_key=True)
    service = Column('service', VARCHAR(128), primary_key=False)
    tzone = Column('tzone', FLOAT, primary_key=False)
    host_ip = Column('host_ip', VARBINARY(16), primary_key=True)
    nagios = Column('nagios', TINYINT(1), primary_key=False)
    version = Column('version', VARCHAR(255), primary_key=False)
    last_modified = Column('last_modified', TIMESTAMP, primary_key=False)
    source_id = Column('source_id', INTEGER(11), ForeignKey('host_source_reference.id'), primary_key=False)
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    port = Column('port', INTEGER(11), primary_key=True, autoincrement=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'protocol': self.protocol,
          'service': self.service,
          'tzone': self.tzone,
          'host_ip': get_ip_str_from_bytes(self.host_ip),
          'nagios': self.nagios,
          'version': self.version,
          'last_modified': self.last_modified,
          'source_id': self.source_id,
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'port': self.port,
        }


class Users (UserMixin, Base):
    __tablename__='users'
    last_logon_try = Column('last_logon_try', DATETIME, primary_key=False)
    expires = Column('expires', DATETIME, primary_key=False)
    name = Column('name', VARCHAR(128), primary_key=False)
    language = Column('language', VARCHAR(12), primary_key=False)
    enabled = Column('enabled', TINYINT(1), primary_key=False)
    company = Column('company', VARCHAR(128), primary_key=False)
    first_login = Column('first_login', TINYINT(1), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    template_id = Column('template_id', BINARY(16), primary_key=False)
    login_method = Column('login_method', VARCHAR(4), primary_key=False)
    last_pass_change = Column('last_pass_change', TIMESTAMP, primary_key=False)
    is_admin = Column('is_admin', TINYINT(1), primary_key=False)
    av_pass = Column('pass', VARCHAR(41), primary_key=False)
    department = Column('department', VARCHAR(128), primary_key=False)
    timezone = Column('timezone', VARCHAR(64), primary_key=False)
    login = Column('login', VARCHAR(64), primary_key=True)
    email = Column('email', VARCHAR(64), primary_key=False)
    uuid = Column('uuid', BINARY(16), primary_key=False)

    def get_id (self):
        return unicode(self.login)

    #
    # Relations:
    #
    #acl_entities_users=relationship('Acl_Entities_Users', backref='users', primaryjoin='login == Acl_Entities_Users.login' , lazy='dynamic')
    #acl_assets=relationship('Acl_Assets', backref='users', primaryjoin='login == Acl_Assets.login' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'last_logon_try': self.last_logon_try,
          'expires': self.expires,
          'name': self.name,
          'language': self.language,
          'enabled': self.enabled,
          'company': self.company,
          'first_login': self.first_login,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'template_id': get_uuid_string_from_bytes(self.template_id),
          'login_method': self.login_method,
          'last_pass_change': self.last_pass_change,
          'is_admin': self.is_admin,
          'av_pass': self.av_pass,
          'department': self.department,
          'timezone': self.timezone,
          'login': self.login,
          'email': self.email,
          'uuid': get_uuid_string_from_bytes(self.uuid),
          #'acl_entities_users': [i.serialize for i in self.acl_entities_users],
          #'acl_assets': [i.serialize for i in self.acl_assets],
        }


class Custom_Report_Profiles (Base):
    __tablename__='custom_report_profiles'
    lfooter = Column('lfooter', VARCHAR(64), primary_key=False)
    name = Column('name', VARCHAR(64), primary_key=False)
    creator = Column('creator', VARCHAR(64), primary_key=False)
    color1 = Column('color1', VARCHAR(64), primary_key=False)
    header = Column('header', VARCHAR(64), primary_key=False)
    color3 = Column('color3', VARCHAR(64), primary_key=False)
    color2 = Column('color2', VARCHAR(64), primary_key=False)
    color4 = Column('color4', VARCHAR(64), primary_key=False)
    rfooter = Column('rfooter', VARCHAR(64), primary_key=False)
    id = Column('id', INTEGER(5), primary_key=True)
    permissions = Column('permissions', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'lfooter': self.lfooter,
          'name': self.name,
          'creator': self.creator,
          'color1': self.color1,
          'header': self.header,
          'color3': self.color3,
          'color2': self.color2,
          'color4': self.color4,
          'rfooter': self.rfooter,
          'id': self.id,
          'permissions': self.permissions,
        }


class Acl_Login_Sensors (Base):
    __tablename__='acl_login_sensors'
    login = Column('login', VARCHAR(64), primary_key=True)
    sensor_id = Column('sensor_id', BINARY(16), ForeignKey('sensor.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'login': self.login,
          'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
        }


class Vuln_Nessus_Latest_Results (Base):
    __tablename__='vuln_nessus_latest_results'
    username = Column('username', VARCHAR(255), ForeignKey('vuln_nessus_latest_reports.username'), primary_key=False)
    falsepositive = Column('falsepositive', CHAR(1), primary_key=False)
    hostname = Column('hostname', VARCHAR(100), primary_key=False)
    protocol = Column('protocol', VARCHAR(5), primary_key=False)
    risk = Column('risk', ENUM('1','2','3','4','5','6','7'), primary_key=False)
    service = Column('service', VARCHAR(40), primary_key=False)
    scantime = Column('scantime', VARCHAR(14), primary_key=False)
    app = Column('app', VARCHAR(20), primary_key=False)
    sid = Column('sid', INTEGER(11), ForeignKey('vuln_nessus_latest_reports.sid'), primary_key=False, autoincrement=False)
    ctx = Column('ctx', BINARY(16), primary_key=False)
    record_type = Column('record_type', CHAR(1), primary_key=False)
    scriptid = Column('scriptid', VARCHAR(40), primary_key=False)
    hostIP = Column('hostIP', VARCHAR(40), primary_key=False)
    msg = Column('msg', TEXT, primary_key=False)
    result_id = Column('result_id', INTEGER(11), primary_key=True)
    port = Column('port', INTEGER(11), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'username': self.username,
          'falsepositive': self.falsepositive,
          'hostname': self.hostname,
          'protocol': self.protocol,
          'risk': self.risk,
          'service': self.service,
          'scantime': self.scantime,
          'app': self.app,
          'sid': self.sid,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'record_type': self.record_type,
          'scriptid': self.scriptid,
          'hostIP': self.hostIP,
          'msg': self.msg,
          'result_id': self.result_id,
          'port': self.port,
        }


class Policy_Time_Reference (Base):
    __tablename__='policy_time_reference'
    hour_end = Column('hour_end', INTEGER(11), primary_key=False)
    week_day_end = Column('week_day_end', INTEGER(11), primary_key=False)
    minute_end = Column('minute_end', INTEGER(11), primary_key=False)
    week_day_start = Column('week_day_start', INTEGER(11), primary_key=False)
    minute_start = Column('minute_start', INTEGER(11), primary_key=False)
    month_start = Column('month_start', INTEGER(11), primary_key=False)
    month_day_start = Column('month_day_start', INTEGER(11), primary_key=False)
    timezone = Column('timezone', VARCHAR(64), primary_key=False)
    month_end = Column('month_end', INTEGER(11), primary_key=False)
    hour_start = Column('hour_start', INTEGER(11), primary_key=False)
    month_day_end = Column('month_day_end', INTEGER(11), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'hour_end': self.hour_end,
          'week_day_end': self.week_day_end,
          'minute_end': self.minute_end,
          'week_day_start': self.week_day_start,
          'minute_start': self.minute_start,
          'month_start': self.month_start,
          'month_day_start': self.month_day_start,
          'timezone': self.timezone,
          'month_end': self.month_end,
          'hour_start': self.hour_start,
          'month_day_end': self.month_day_end,
          'id': self.id,
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Wireless_Sensors (Base):
    __tablename__='wireless_sensors'
    mounting_location = Column('mounting_location', VARCHAR(255), primary_key=False)
    free_space = Column('free_space', VARCHAR(45), primary_key=False)
    version = Column('version', VARCHAR(45), primary_key=False)
    location = Column('location', VARCHAR(100), primary_key=True)
    avg_signal = Column('avg_signal', INTEGER(10), primary_key=False)
    model = Column('model', VARCHAR(150), primary_key=False)
    sensor = Column('sensor', VARCHAR(64), primary_key=True)
    serial = Column('serial', VARCHAR(150), primary_key=False)
    last_scraped = Column('last_scraped', TIMESTAMP, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'mounting_location': self.mounting_location,
          'free_space': self.free_space,
          'version': self.version,
          'location': self.location,
          'avg_signal': self.avg_signal,
          'model': self.model,
          'sensor': self.sensor,
          'serial': self.serial,
          'last_scraped': self.last_scraped,
        }


class Incident_Custom_Types (Base):
    __tablename__='incident_custom_types'
    name = Column('name', VARCHAR(255), primary_key=True)
    required = Column('required', INTEGER(1), primary_key=False)
    id = Column('id', VARCHAR(64), primary_key=True, index=True)
    ord = Column('ord', INTEGER(11), primary_key=False)
    type = Column('type', VARCHAR(255), primary_key=False)
    options = Column('options', TEXT, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'name': self.name,
          'required': self.required,
          'id': self.id,
          'ord': self.ord,
          'type': self.type,
          'options': self.options,
        }


class Plugin_Sid_Changes (Base):
    __tablename__='plugin_sid_changes'
    plugin_ctx = Column('plugin_ctx', BINARY(16), primary_key=True)
    name = Column('name', VARCHAR(255), primary_key=False)
    class_id = Column('class_id', INTEGER(11), primary_key=False)
    priority = Column('priority', INTEGER(11), primary_key=False)
    subcategory_id = Column('subcategory_id', INTEGER(11), primary_key=False)
    reliability = Column('reliability', INTEGER(11), primary_key=False)
    sid = Column('sid', INTEGER, primary_key=True, autoincrement=False, index=True)
    plugin_id = Column('plugin_id', INTEGER, primary_key=True, autoincrement=False, index=True)
    category_id = Column('category_id', INTEGER(11), primary_key=False, autoincrement=False)
    aro = Column('aro', DECIMAL(11, 4), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'plugin_ctx': get_uuid_string_from_bytes(self.plugin_ctx),
          'name': self.name,
          'class_id': self.class_id,
          'priority': self.priority,
          'subcategory_id': self.subcategory_id,
          'reliability': self.reliability,
          'sid': self.sid,
          'plugin_id': self.plugin_id,
          'category_id': self.category_id,
          'aro': self.aro,
        }


class Policy_Host_Reference (Base):
    __tablename__='policy_host_reference'
    host_id = Column('host_id', BINARY(16), ForeignKey('host.id'), primary_key=True)
    direction = Column('direction', ENUM('source','dest'), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'host_id': get_uuid_string_from_bytes(self.host_id),
          'direction': self.direction,
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Host_Group_Scan (Base):
    __tablename__='host_group_scan'
    plugin_id = Column('plugin_id', INTEGER(11), primary_key=True)
    plugin_sid = Column('plugin_sid', INTEGER(11), primary_key=True)
    host_group_id = Column('host_group_id', BINARY(16), ForeignKey('host_group.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'plugin_id': self.plugin_id,
          'plugin_sid': self.plugin_sid,
          'host_group_id': get_uuid_string_from_bytes(self.host_group_id),
        }


class Custom_Report_Types (Base):
    __tablename__='custom_report_types'
    inputs = Column('inputs', TEXT, primary_key=False)
    name = Column('name', VARCHAR(128), primary_key=False)
    dr = Column('dr', INTEGER(11), primary_key=False)
    file = Column('file', VARCHAR(128), primary_key=False)
    sql = Column('sql', LONGTEXT, primary_key=False)
    type = Column('type', VARCHAR(128), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'inputs': self.inputs,
          'name': self.name,
          'dr': self.dr,
          'file': self.file,
          'sql': self.sql,
          'type': self.type,
          'id': self.id,
        }


class Product_Type (Base):
    __tablename__='product_type'
    id = Column('id', INTEGER(11), primary_key=True)
    name = Column('name', VARCHAR(100), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id': self.id,
          'name': self.name,
        }


class Webservice (Base):
    __tablename__='webservice'
    name = Column('name', VARCHAR(64), primary_key=False)
    descr = Column('descr', VARCHAR(256), primary_key=False)
    url = Column('url', VARCHAR(256), primary_key=False)
    namespace = Column('namespace', VARCHAR(64), primary_key=False)
    ctx = Column('ctx', BINARY(16), primary_key=False)
    source = Column('source', ENUM('ticket','None'), primary_key=False)
    user = Column('user', VARCHAR(64), primary_key=False)
    av_pass = Column('pass', VARCHAR(64), primary_key=False)
    type = Column('type', VARCHAR(32), primary_key=False)
    id = Column('id', BINARY(16), primary_key=True)
    #
    # Relations:
    #
    #webservice_operation=relationship('Webservice_Operation', backref='webservice', primaryjoin='id == Webservice_Operation.ws_id' , lazy='dynamic')
    #webservice_default=relationship('Webservice_Default', backref='webservice', primaryjoin='id == Webservice_Default.ws_id' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'name': self.name,
          'descr': self.descr,
          'url': self.url,
          'namespace': self.namespace,
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'source': self.source,
          'user': self.user,
          'av_pass': self.av_pass,
          'type': self.type,
          'id': get_uuid_string_from_bytes(self.id),
          #'webservice_operation': [i.serialize for i in self.webservice_operation],
          #'webservice_default': [i.serialize for i in self.webservice_default],
        }


class Net_Qualification (Base):
    __tablename__='net_qualification'
    attack = Column('attack', INTEGER(11), primary_key=False)
    compromise = Column('compromise', INTEGER(11), primary_key=False)
    net_id = Column('net_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'attack': self.attack,
          'compromise': self.compromise,
          'net_id': get_uuid_string_from_bytes(self.net_id),
        }


class Policy_Net_Reference (Base):
    __tablename__='policy_net_reference'
    direction = Column('direction', ENUM('source','dest'), primary_key=True)
    net_id = Column('net_id', BINARY(16), ForeignKey('net.id'), primary_key=True)
    policy_id = Column('policy_id', BINARY(16), ForeignKey('policy.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'direction': self.direction,
          'net_id': get_uuid_string_from_bytes(self.net_id),
          'policy_id': get_uuid_string_from_bytes(self.policy_id),
        }


class Alarm (Base):
    __tablename__='alarm'
    status = Column('status', ENUM('open','closed'), primary_key=False)
    stats = Column('stats', TEXT, primary_key=False)
    protocol = Column('protocol', INTEGER(11), ForeignKey('protocol.id'), primary_key=False)
    risk = Column('risk', INTEGER(11), primary_key=False)
    event_id = Column('event_id', BINARY(16), primary_key=False)
    timestamp = Column('timestamp', TIMESTAMP, primary_key=False)
    efr = Column('efr', INTEGER(11), primary_key=False)
    src_port = Column('src_port', INTEGER(11), primary_key=False)
    corr_engine_ctx = Column('corr_engine_ctx', BINARY(16), primary_key=False)
    src_ip = Column('src_ip', VARBINARY(16), primary_key=False)
    backlog_id = Column('backlog_id', BINARY(16), primary_key=True)
    dst_port = Column('dst_port', INTEGER(11), primary_key=False)
    removable = Column('removable', TINYINT(1), primary_key=False)
    dst_ip = Column('dst_ip', VARBINARY(16), primary_key=False)
    plugin_id = Column('plugin_id', INTEGER(11), primary_key=False)
    similar = Column('similar', VARCHAR(40), primary_key=False)
    plugin_sid = Column('plugin_sid', INTEGER(11), primary_key=False)
    in_file = Column('in_file', TINYINT(1), primary_key=False)
    #
    # Relations:
    #
    #alarm_tags=relationship('Alarm_Tags', backref='alarm', primaryjoin='backlog_id == Alarm_Tags.id_alarm' , lazy='dynamic')
    #backlog=relationship('Backlog', backref='alarm', primaryjoin='backlog_id == Backlog.id', uselist=False)
    #alarm_ctxs=relationship('Alarm_Ctxs', backref='alarm', primaryjoin='backlog_id == Alarm_Ctxs.id_alarm' , lazy='dynamic')
    #alarm_hosts=relationship('Alarm_Hosts', backref='alarm', primaryjoin='backlog_id == Alarm_Hosts.id_alarm' , lazy='dynamic')
    #alarm_nets=relationship('Alarm_Nets', backref='alarm', primaryjoin='backlog_id == Alarm_Nets.id_alarm' , lazy='dynamic')
    @property
    def serialize(self):
        return {
          'status': self.status,
          'stats': self.stats,
          'protocol': self.protocol,
          'risk': self.risk,
          'event_id': get_uuid_string_from_bytes(self.event_id),
          'timestamp': self.timestamp,
          'efr': self.efr,
          'src_port': self.src_port,
          'corr_engine_ctx': get_uuid_string_from_bytes(self.corr_engine_ctx),
          'src_ip': get_ip_str_from_bytes(self.src_ip),
          'backlog_id': get_uuid_string_from_bytes(self.backlog_id),
          'dst_port': self.dst_port,
          'removable': self.removable,
          'dst_ip': get_ip_str_from_bytes(self.dst_ip),
          'plugin_id': self.plugin_id,
          'similar': self.similar,
          'plugin_sid': self.plugin_sid,
          'in_file': self.in_file,
          #'alarm_tags': [i.serialize for i in self.alarm_tags],
          #'backlog': [i.serialize for i in self.backlog],
          #'alarm_ctxs': [i.serialize for i in self.alarm_ctxs],
          #'alarm_hosts': [i.serialize for i in self.alarm_hosts],
          #'alarm_nets': [i.serialize for i in self.alarm_nets],
        }


class Rrd_Anomalies (Base):
    __tablename__='rrd_anomalies'
    count = Column('count', INTEGER(11), primary_key=False)
    anomaly_range = Column('anomaly_range', VARCHAR(30), primary_key=False)
    what = Column('what', VARCHAR(100), primary_key=False)
    ip = Column('ip', VARBINARY(16), primary_key=False)
    over = Column('over', INTEGER(11), primary_key=False)
    acked = Column('acked', INTEGER(11), primary_key=False)
    anomaly_time = Column('anomaly_time', VARCHAR(40), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'count': self.count,
          'anomaly_range': self.anomaly_range,
          'what': self.what,
          'ip': get_ip_str_from_bytes(self.ip),
          'over': self.over,
          'acked': self.acked,
          'anomaly_time': self.anomaly_time,
        }


class Vuln_Nessus_Servers (Base):
    __tablename__='vuln_nessus_servers'
    status = Column('status', CHAR(1), primary_key=False)
    description = Column('description', VARCHAR(255), primary_key=False)
    checkin_time = Column('checkin_time', DATETIME, primary_key=False)
    max_scans = Column('max_scans', INTEGER(11), primary_key=False)
    server_feedtype = Column('server_feedtype', VARCHAR(32), primary_key=False)
    site_code = Column('site_code', VARCHAR(25), primary_key=False)
    hostname = Column('hostname', VARCHAR(255), primary_key=False)
    enabled = Column('enabled', TINYINT(1), primary_key=False)
    id = Column('id', INTEGER(11), primary_key=True)
    server_nversion = Column('server_nversion', VARCHAR(100), primary_key=False)
    user = Column('user', VARCHAR(255), primary_key=False)
    owner = Column('owner', VARCHAR(255), primary_key=False)
    current_scans = Column('current_scans', INTEGER(11), primary_key=False)
    PASSWORD = Column('PASSWORD', VARCHAR(255), primary_key=False)
    TYPE = Column('TYPE', CHAR(1), primary_key=False)
    port = Column('port', INTEGER(11), primary_key=False)
    server_feedversion = Column('server_feedversion', VARCHAR(12), primary_key=False)
    name = Column('name', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'status': self.status,
          'description': self.description,
          'checkin_time': self.checkin_time,
          'max_scans': self.max_scans,
          'server_feedtype': self.server_feedtype,
          'site_code': self.site_code,
          'hostname': self.hostname,
          'enabled': self.enabled,
          'id': self.id,
          'server_nversion': self.server_nversion,
          'user': self.user,
          'owner': self.owner,
          'current_scans': self.current_scans,
          'PASSWORD': self.PASSWORD,
          'TYPE': self.TYPE,
          'port': self.port,
          'server_feedversion': self.server_feedversion,
          'name': self.name,
        }


class Risk_Maps (Base):
    __tablename__='risk_maps'
    map = Column('map', BINARY(16), ForeignKey('map.id'), primary_key=True)
    name = Column('name', VARCHAR(128), primary_key=False)
    perm = Column('perm', VARCHAR(64), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'map': get_uuid_string_from_bytes(self.map),
          'name': self.name,
          'perm': self.perm,
        }


class Risk_Indicators (Base):
    __tablename__='risk_indicators'
    map = Column('map', BINARY(16), ForeignKey('map.id'), primary_key=False)
    name = Column('name', VARCHAR(100), primary_key=False)
    url = Column('url', VARCHAR(255), primary_key=False)
    h = Column('h', INTEGER(11), primary_key=False)
    type_name = Column('type_name', VARCHAR(255), primary_key=False)
    w = Column('w', INTEGER(11), primary_key=False)
    y = Column('y', INTEGER(11), primary_key=False)
    x = Column('x', INTEGER(11), primary_key=False)
    size = Column('size', INTEGER(11), primary_key=False)
    type = Column('type', VARCHAR(100), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    icon = Column('icon', VARCHAR(255), primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'map': get_uuid_string_from_bytes(self.map),
          'name': self.name,
          'url': self.url,
          'h': self.h,
          'type_name': self.type_name,
          'w': self.w,
          'y': self.y,
          'x': self.x,
          'size': self.size,
          'type': self.type,
          'id': self.id,
          'icon': self.icon,
        }


class Alarm_Ctxs (Base):
    __tablename__='alarm_ctxs'
    id_alarm = Column('id_alarm', BINARY(16), ForeignKey('alarm.backlog_id'), primary_key=True)
    id_ctx = Column('id_ctx', BINARY(16), ForeignKey('host.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'id_alarm': get_uuid_string_from_bytes(self.id_alarm),
          'id_ctx': get_uuid_string_from_bytes(self.id_ctx),
        }


class Databases (Base):
    __tablename__='databases'
    name = Column('name', VARCHAR(64), primary_key=False)
    ip = Column('ip', VARBINARY(16), primary_key=False)
    ctx = Column('ctx', BINARY(16), ForeignKey('acl_entities.id'), primary_key=False)
    id = Column('id', INTEGER, primary_key=True)
    user = Column('user', VARCHAR(64), primary_key=False)
    av_pass = Column('pass', VARCHAR(64), primary_key=False)
    port = Column('port', INTEGER(11), primary_key=False)
    icon = Column('icon', MEDIUMBLOB, primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'name': self.name,
          'ip': get_ip_str_from_bytes(self.ip),
          'ctx': get_uuid_string_from_bytes(self.ctx),
          'id': self.id,
          'user': self.user,
          'av_pass': self.av_pass,
          'port': self.port,
          'icon': self.icon,
        }


class Action_Risk (Base):
    __tablename__='action_risk'
    backlog_id = Column('backlog_id', BINARY(16), ForeignKey('alarm.backlog_id'), primary_key=True)
    risk = Column('risk', INTEGER, primary_key=False)
    action_id = Column('action_id', BINARY(16), ForeignKey('action.id'), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'backlog_id': get_uuid_string_from_bytes(self.backlog_id),
          'risk': self.risk,
          'action_id': get_uuid_string_from_bytes(self.action_id),
        }


class Webservice_Operation (Base):
    __tablename__='webservice_operation'
    ws_id = Column('ws_id', BINARY(16), ForeignKey('webservice.id'), primary_key=True)
    type = Column('type', ENUM('insert','query','update','delete','auth'), primary_key=False)
    attrs = Column('attrs', VARCHAR(512), primary_key=False)
    op = Column('op', VARCHAR(64), primary_key=True)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'ws_id': get_uuid_string_from_bytes(self.ws_id),
          'type': self.type,
          'attrs': self.attrs,
          'op': self.op,
        }


class System(Base):
    __tablename__ = 'system'
    id = Column('id', BINARY(16), primary_key=True, nullable=False)
    name = Column('name', VARCHAR(64), nullable=False)
    admin_ip = Column('admin_ip', VARBINARY(16), nullable=False)
    vpn_ip = Column('vpn_ip', VARBINARY(16))
    profile = Column('profile', VARCHAR(255), nullable=False)
    sensor_id = Column('sensor_id', BINARY(16))
    server_id = Column('server_id', BINARY(16))
    database_id = Column('database_id', BINARY(16))
    host_id = Column('host_id', BINARY(16))
    ha_ip = Column('ha_ip', VARBINARY(16))
    ha_name = Column('ha_name', VARCHAR(64))
    ha_role = Column('ha_role', VARCHAR(32))
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
                'uuid': get_uuid_string_from_bytes(self.id),
                'hostname': self.name,
                'admin_ip': get_ip_str_from_bytes(self.admin_ip),
                'vpn_ip': get_ip_str_from_bytes(self.vpn_ip),
                'profile': self.profile,
                'sensor_id': get_uuid_string_from_bytes(self.sensor_id),
                'server_id': get_uuid_string_from_bytes(self.server_id),
                'database_id': get_uuid_string_from_bytes(self.database_id),
                'host_id': get_uuid_string_from_bytes(self.host_id),
                'ha_ip': get_ip_str_from_bytes(self.ha_ip),
                'ha_name': self.ha_name,
                'ha_role': self.ha_role,
        }
