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

from sqlalchemy import Column, ForeignKey
from sqlalchemy.orm import sessionmaker, relationship, deferred
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.dialects.mysql import BIGINT, BINARY, BIT, BLOB, BOOLEAN, CHAR, \
    DATE, DATETIME, DECIMAL, DECIMAL, DOUBLE, ENUM, FLOAT, INTEGER, LONGBLOB, \
    LONGTEXT, MEDIUMBLOB, MEDIUMINT, MEDIUMTEXT, NCHAR, NUMERIC, NVARCHAR, \
    REAL, SET, SMALLINT, TEXT, TIME, TIMESTAMP, TINYBLOB, TINYINT, TINYTEXT, \
    VARBINARY, VARCHAR, YEAR

import db

Base = declarative_base(bind=db.get_engine('avcenter'))

class Current_Local (Base):
    __tablename__='current_local'

    hostname = Column('hostname',VARCHAR(50),primary_key=False)
    database_ossim = Column('database_ossim',VARCHAR(50),primary_key=False)
    ocs_db = Column('ocs_db',VARCHAR(50),primary_key=False)
    ha_password = deferred(Column('ha_password',VARCHAR(50),primary_key=False))
    ntp_server = Column('ntp_server',VARCHAR(50),primary_key=False)
    admin_ip = Column('admin_ip',VARCHAR(15),primary_key=False)
    netflow = Column('netflow',VARCHAR(50),primary_key=False)
    ha_heartbeat_comm = deferred(Column('ha_heartbeat_comm',VARCHAR(50),primary_key=False))
    upgrade = Column('upgrade',VARCHAR(50),primary_key=False)
    uuid = Column('uuid',VARCHAR(36),primary_key=False)
    server_license = Column('server_license',VARCHAR(50),primary_key=False)
    rservers = Column('rservers',VARCHAR(1000),primary_key=False)
    admin_gateway = Column('admin_gateway',VARCHAR(50),primary_key=False)
    timestamp = Column('timestamp',TIMESTAMP,primary_key=False)
    database_type = Column('database_type',VARCHAR(50),primary_key=False)
    database_acl = Column('database_acl',VARCHAR(50),primary_key=False)
    sensor_priority = Column('sensor_priority',VARCHAR(50),primary_key=False)
    vpn_port = Column('vpn_port',VARCHAR(50),primary_key=False)
    sensor_interfaces = Column('sensor_interfaces',VARCHAR(50),primary_key=False)
    firewall_active = Column('firewall_active',VARCHAR(50),primary_key=False)
    sensor_name = Column('sensor_name',VARCHAR(50),primary_key=False)
    ha_local_node_ip = deferred(Column('ha_local_node_ip',VARCHAR(50),primary_key=False))
    update_proxy_port = Column('update_proxy_port',VARCHAR(50),primary_key=False)
    mailserver_relay_port = Column('mailserver_relay_port',VARCHAR(50),primary_key=False)
    innodb = Column('innodb',VARCHAR(50),primary_key=False)
    framework_ip = Column('framework_ip',VARCHAR(50),primary_key=False)
    update_proxy_dns = Column('update_proxy_dns',VARCHAR(50),primary_key=False)
    netflow_remote_collector_port = Column('netflow_remote_collector_port',INTEGER(11),primary_key=False)
    domain = Column('domain',VARCHAR(30),primary_key=False)
    ha_device = deferred(Column('ha_device',VARCHAR(50),primary_key=False))
    sensor_ip = Column('sensor_ip',VARCHAR(50),primary_key=False)
    vpn_net = Column('vpn_net',VARCHAR(50),primary_key=False)
    override_sensor = Column('override_sensor',VARCHAR(50),primary_key=False)
    ha_ping_node = deferred(Column('ha_ping_node',VARCHAR(50),primary_key=False))
    ha_other_node_ip = deferred(Column('ha_other_node_ip',VARCHAR(50),primary_key=False))
    config_group = Column('config_group',VARCHAR(50),primary_key=False)
    version = Column('version',VARCHAR(50),primary_key=False)
    server_port = Column('server_port',VARCHAR(50),primary_key=False)
    idm_mssp = Column('idm_mssp',VARCHAR(50),primary_key=False)
    ha_deadtime = deferred(Column('ha_deadtime',VARCHAR(50),primary_key=False))
    sensor_tzone = Column('sensor_tzone',VARCHAR(50),primary_key=False)
    ha_heartbeat_start = deferred(Column('ha_heartbeat_start',VARCHAR(50),primary_key=False))
    database_osvdb = Column('database_osvdb',VARCHAR(50),primary_key=False)
    interface = Column('interface',VARCHAR(50),primary_key=False)
    snmp_comunity = Column('snmp_comunity',VARCHAR(50),primary_key=False)
    mailserver_relay = Column('mailserver_relay',VARCHAR(50),primary_key=False)
    language = Column('language',VARCHAR(50),primary_key=False)
    update_proxy_user = Column('update_proxy_user',VARCHAR(50),primary_key=False)
    vpn_netmask = Column('vpn_netmask',VARCHAR(50),primary_key=False)
    server_plugins = Column('server_plugins',VARCHAR(50),primary_key=False)
    ha_keepalive = deferred(Column('ha_keepalive',VARCHAR(50),primary_key=False))
    rebuild_database = Column('rebuild_database',VARCHAR(50),primary_key=False)
    update_proxy_pass = Column('update_proxy_pass',VARCHAR(50),primary_key=False)
    ha_log = deferred(Column('ha_log',VARCHAR(50),primary_key=False))
    email_notify = Column('email_notify',VARCHAR(50),primary_key=False)
    snmptrap = Column('snmptrap',VARCHAR(50),primary_key=False)
    database_event = Column('database_event',VARCHAR(50),primary_key=False)
    snmpd = Column('snmpd',VARCHAR(50),primary_key=False)
    sensor_ctx = Column('sensor_ctx',VARCHAR(50),primary_key=False)
    ha_other_node_name = deferred(Column('ha_other_node_name',VARCHAR(50),primary_key=False))
    distro_version = Column('distro_version',VARCHAR(50),primary_key=False)
    server_ip = Column('server_ip',VARCHAR(50),primary_key=False)
    ha_role = deferred(Column('ha_role',VARCHAR(50),primary_key=False))
    alienvault_ip_reputation = Column('alienvault_ip_reputation',VARCHAR(50),primary_key=False)
    profile = Column('profile',VARCHAR(50),primary_key=False)
    framework_port = Column('framework_port',VARCHAR(50),primary_key=False)
    mailserver_relay_passwd = Column('mailserver_relay_passwd',VARCHAR(255),primary_key=False)
    database_ip = Column('database_ip',VARCHAR(50),primary_key=False)
    vpn_ip = Column('vpn_ip',VARCHAR(50),primary_key=False)
    pci_express = Column('pci_express',VARCHAR(50),primary_key=False)
    admin_netmask = Column('admin_netmask',VARCHAR(50),primary_key=False)
    framework_https_cert = Column('framework_https_cert',VARCHAR(150),primary_key=False)
    admin_dns = Column('admin_dns',VARCHAR(50),primary_key=False)
    server_pro = Column('server_pro',VARCHAR(50),primary_key=False)
    rsyslogdns_disable = Column('rsyslogdns_disable',VARCHAR(50),primary_key=False)
    fixed_server_plugins = Column('fixed_server_plugins',VARCHAR(50),primary_key=False)
    framework_https = Column('framework_https',VARCHAR(50),primary_key=False)
    framework_https_key = Column('framework_https_key',VARCHAR(150),primary_key=False)
    update_proxy = Column('update_proxy',VARCHAR(50),primary_key=False)
    id_component = Column('id_component',INTEGER(11),primary_key=True)
    first_init = Column('first_init',VARCHAR(50),primary_key=False)
    vpn_infraestructure = Column('vpn_infraestructure',VARCHAR(50),primary_key=False)
    database_pass = Column('database_pass',VARCHAR(50),primary_key=False)
    mailserver_relay_user = Column('mailserver_relay_user',VARCHAR(50),primary_key=False)
    mservers = Column('mservers',VARCHAR(1024),primary_key=False)
    ha_autofailback = deferred(Column('ha_autofailback',VARCHAR(50),primary_key=False))
    sensor_networks = Column('sensor_networks',VARCHAR(2000),primary_key=False)
    ids_rules_flow_control = Column('ids_rules_flow_control',VARCHAR(50),primary_key=False)
    database_user = Column('database_user',VARCHAR(50),primary_key=False)
    ha_virtual_ip = deferred(Column('ha_virtual_ip',VARCHAR(50),primary_key=False))
    expert_profile = Column('expert_profile',VARCHAR(50),primary_key=False)
    sensor_monitors = Column('sensor_monitors',VARCHAR(2000),primary_key=False)
    database_port = Column('database_port',VARCHAR(50),primary_key=False)
    distro_type = Column('distro_type',VARCHAR(50),primary_key=False)
    sensor_detectors = Column('sensor_detectors',VARCHAR(2000),primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'hostname':self.hostname,
          'database_ossim':self.database_ossim,
          'ocs_db':self.ocs_db,
          'ha_password':self.ha_password,
          'ntp_server':self.ntp_server,
          'admin_ip':self.admin_ip,
          'netflow':self.netflow,
          'ha_heartbeat_comm':self.ha_heartbeat_comm,
          'upgrade':self.upgrade,
          'uuid':self.uuid,
          'server_license':self.server_license,
          'rservers':self.rservers,
          'admin_gateway':self.admin_gateway,
          'timestamp':self.timestamp,
          'database_type':self.database_type,
          'database_acl':self.database_acl,
          'sensor_priority':self.sensor_priority,
          'vpn_port':self.vpn_port,
          'sensor_interfaces':self.sensor_interfaces,
          'firewall_active':self.firewall_active,
          'sensor_name':self.sensor_name,
          'ha_local_node_ip':self.ha_local_node_ip,
          'update_proxy_port':self.update_proxy_port,
          'mailserver_relay_port':self.mailserver_relay_port,
          'innodb':self.innodb,
          'framework_ip':self.framework_ip,
          'update_proxy_dns':self.update_proxy_dns,
          'netflow_remote_collector_port':self.netflow_remote_collector_port,
          'domain':self.domain,
          'ha_device':self.ha_device,
          'sensor_ip':self.sensor_ip,
          'vpn_net':self.vpn_net,
          'override_sensor':self.override_sensor,
          'ha_ping_node':self.ha_ping_node,
          'ha_other_node_ip':self.ha_other_node_ip,
          'config_group':self.config_group,
          'version':self.version,
          'server_port':self.server_port,
          'idm_mssp':self.idm_mssp,
          'ha_deadtime':self.ha_deadtime,
          'sensor_tzone':self.sensor_tzone,
          'ha_heartbeat_start':self.ha_heartbeat_start,
          'database_osvdb':self.database_osvdb,
          'interface':self.interface,
          'snmp_comunity':self.snmp_comunity,
          'mailserver_relay':self.mailserver_relay,
          'language':self.language,
          'update_proxy_user':self.update_proxy_user,
          'vpn_netmask':self.vpn_netmask,
          'server_plugins':self.server_plugins,
          'ha_keepalive':self.ha_keepalive,
          'rebuild_database':self.rebuild_database,
          'update_proxy_pass':self.update_proxy_pass,
          'ha_log':self.ha_log,
          'email_notify':self.email_notify,
          'snmptrap':self.snmptrap,
          'database_event':self.database_event,
          'snmpd':self.snmpd,
          'sensor_ctx':self.sensor_ctx,
          'ha_other_node_name':self.ha_other_node_name,
          'distro_version':self.distro_version,
          'server_ip':self.server_ip,
          'ha_role':self.ha_role,
          'alienvault_ip_reputation':self.alienvault_ip_reputation,
          'profile':self.profile,
          'framework_port':self.framework_port,
          'mailserver_relay_passwd':self.mailserver_relay_passwd,
          'database_ip':self.database_ip,
          'vpn_ip':self.vpn_ip,
          'pci_express':self.pci_express,
          'admin_netmask':self.admin_netmask,
          'framework_https_cert':self.framework_https_cert,
          'admin_dns':self.admin_dns,
          'server_pro':self.server_pro,
          'rsyslogdns_disable':self.rsyslogdns_disable,
          'fixed_server_plugins':self.fixed_server_plugins,
          'framework_https':self.framework_https,
          'framework_https_key':self.framework_https_key,
          'update_proxy':self.update_proxy,
          'id_component':self.id_component,
          'first_init':self.first_init,
          'vpn_infraestructure':self.vpn_infraestructure,
          'database_pass':self.database_pass,
          'mailserver_relay_user':self.mailserver_relay_user,
          'mservers':self.mservers,
          'ha_autofailback':self.ha_autofailback,
          'sensor_networks':self.sensor_networks,
          'ids_rules_flow_control':self.ids_rules_flow_control,
          'database_user':self.database_user,
          'ha_virtual_ip':self.ha_virtual_ip,
          'expert_profile':self.expert_profile,
          'sensor_monitors':self.sensor_monitors,
          'database_port':self.database_port,
          'distro_type':self.distro_type,
          'sensor_detectors':self.sensor_detectors,
          'uuidbin': uuid.UUID(self.uuid).bytes,
        }
class Register (Base):
    __tablename__='register'

    hostname = Column('hostname',VARCHAR(50),primary_key=False)
    database_ossim = Column('database_ossim',VARCHAR(50),primary_key=False)
    ocs_db = Column('ocs_db',VARCHAR(50),primary_key=False)
    ha_password = Column('ha_password',VARCHAR(50),primary_key=False)
    ntp_server = Column('ntp_server',VARCHAR(50),primary_key=False)
    admin_ip = Column('admin_ip',VARCHAR(15),primary_key=False)
    netflow = Column('netflow',VARCHAR(50),primary_key=False)
    ha_heartbeat_comm = Column('ha_heartbeat_comm',VARCHAR(50),primary_key=False)
    upgrade = Column('upgrade',VARCHAR(50),primary_key=False)
    uuid = Column('uuid',VARCHAR(36),primary_key=False)
    server_license = Column('server_license',VARCHAR(50),primary_key=False)
    rservers = Column('rservers',VARCHAR(1000),primary_key=False)
    admin_gateway = Column('admin_gateway',VARCHAR(50),primary_key=False)
    timestamp = Column('timestamp',TIMESTAMP,primary_key=False)
    database_type = Column('database_type',VARCHAR(50),primary_key=False)
    database_acl = Column('database_acl',VARCHAR(50),primary_key=False)
    sensor_priority = Column('sensor_priority',VARCHAR(50),primary_key=False)
    vpn_port = Column('vpn_port',VARCHAR(50),primary_key=False)
    sensor_interfaces = Column('sensor_interfaces',VARCHAR(50),primary_key=False)
    firewall_active = Column('firewall_active',VARCHAR(50),primary_key=False)
    sensor_name = Column('sensor_name',VARCHAR(50),primary_key=False)
    ha_local_node_ip = Column('ha_local_node_ip',VARCHAR(50),primary_key=False)
    update_proxy_port = Column('update_proxy_port',VARCHAR(50),primary_key=False)
    mailserver_relay_port = Column('mailserver_relay_port',VARCHAR(50),primary_key=False)
    innodb = Column('innodb',VARCHAR(50),primary_key=False)
    framework_ip = Column('framework_ip',VARCHAR(50),primary_key=False)
    update_proxy_dns = Column('update_proxy_dns',VARCHAR(50),primary_key=False)
    netflow_remote_collector_port = Column('netflow_remote_collector_port',INTEGER(11),primary_key=False)
    domain = Column('domain',VARCHAR(30),primary_key=False)
    ha_device = Column('ha_device',VARCHAR(50),primary_key=False)
    sensor_ip = Column('sensor_ip',VARCHAR(50),primary_key=False)
    vpn_net = Column('vpn_net',VARCHAR(50),primary_key=False)
    override_sensor = Column('override_sensor',VARCHAR(50),primary_key=False)
    ha_ping_node = Column('ha_ping_node',VARCHAR(50),primary_key=False)
    ha_other_node_ip = Column('ha_other_node_ip',VARCHAR(50),primary_key=False)
    config_group = Column('config_group',VARCHAR(50),primary_key=False)
    version = Column('version',VARCHAR(50),primary_key=False)
    server_port = Column('server_port',VARCHAR(50),primary_key=False)
    idm_mssp = Column('idm_mssp',VARCHAR(50),primary_key=False)
    ha_deadtime = Column('ha_deadtime',VARCHAR(50),primary_key=False)
    sensor_tzone = Column('sensor_tzone',VARCHAR(50),primary_key=False)
    ha_heartbeat_start = Column('ha_heartbeat_start',VARCHAR(50),primary_key=False)
    database_osvdb = Column('database_osvdb',VARCHAR(50),primary_key=False)
    interface = Column('interface',VARCHAR(50),primary_key=False)
    snmp_comunity = Column('snmp_comunity',VARCHAR(50),primary_key=False)
    mailserver_relay = Column('mailserver_relay',VARCHAR(50),primary_key=False)
    language = Column('language',VARCHAR(50),primary_key=False)
    update_proxy_user = Column('update_proxy_user',VARCHAR(50),primary_key=False)
    vpn_netmask = Column('vpn_netmask',VARCHAR(50),primary_key=False)
    server_plugins = Column('server_plugins',VARCHAR(50),primary_key=False)
    ha_keepalive = Column('ha_keepalive',VARCHAR(50),primary_key=False)
    rebuild_database = Column('rebuild_database',VARCHAR(50),primary_key=False)
    update_proxy_pass = Column('update_proxy_pass',VARCHAR(50),primary_key=False)
    ha_log = Column('ha_log',VARCHAR(50),primary_key=False)
    email_notify = Column('email_notify',VARCHAR(50),primary_key=False)
    snmptrap = Column('snmptrap',VARCHAR(50),primary_key=False)
    database_event = Column('database_event',VARCHAR(50),primary_key=False)
    snmpd = Column('snmpd',VARCHAR(50),primary_key=False)
    sensor_ctx = Column('sensor_ctx',VARCHAR(50),primary_key=False)
    ha_other_node_name = Column('ha_other_node_name',VARCHAR(50),primary_key=False)
    distro_version = Column('distro_version',VARCHAR(50),primary_key=False)
    server_ip = Column('server_ip',VARCHAR(50),primary_key=False)
    ha_role = Column('ha_role',VARCHAR(50),primary_key=False)
    alienvault_ip_reputation = Column('alienvault_ip_reputation',VARCHAR(50),primary_key=False)
    profile = Column('profile',VARCHAR(50),primary_key=False)
    framework_port = Column('framework_port',VARCHAR(50),primary_key=False)
    mailserver_relay_passwd = Column('mailserver_relay_passwd',VARCHAR(255),primary_key=False)
    database_ip = Column('database_ip',VARCHAR(50),primary_key=False)
    vpn_ip = Column('vpn_ip',VARCHAR(50),primary_key=False)
    pci_express = Column('pci_express',VARCHAR(50),primary_key=False)
    admin_netmask = Column('admin_netmask',VARCHAR(50),primary_key=False)
    framework_https_cert = Column('framework_https_cert',VARCHAR(150),primary_key=False)
    admin_dns = Column('admin_dns',VARCHAR(50),primary_key=False)
    server_pro = Column('server_pro',VARCHAR(50),primary_key=False)
    rsyslogdns_disable = Column('rsyslogdns_disable',VARCHAR(50),primary_key=False)
    fixed_server_plugins = Column('fixed_server_plugins',VARCHAR(50),primary_key=False)
    framework_https = Column('framework_https',VARCHAR(50),primary_key=False)
    framework_https_key = Column('framework_https_key',VARCHAR(150),primary_key=False)
    update_proxy = Column('update_proxy',VARCHAR(50),primary_key=False)
    id_component = Column('id_component',INTEGER(11),primary_key=True)
    first_init = Column('first_init',VARCHAR(50),primary_key=False)
    vpn_infraestructure = Column('vpn_infraestructure',VARCHAR(50),primary_key=False)
    database_pass = Column('database_pass',VARCHAR(50),primary_key=False)
    mailserver_relay_user = Column('mailserver_relay_user',VARCHAR(50),primary_key=False)
    mservers = Column('mservers',VARCHAR(1024),primary_key=False)
    ha_autofailback = Column('ha_autofailback',VARCHAR(50),primary_key=False)
    sensor_networks = Column('sensor_networks',VARCHAR(2000),primary_key=False)
    ids_rules_flow_control = Column('ids_rules_flow_control',VARCHAR(50),primary_key=False)
    database_user = Column('database_user',VARCHAR(50),primary_key=False)
    ha_virtual_ip = Column('ha_virtual_ip',VARCHAR(50),primary_key=False)
    expert_profile = Column('expert_profile',VARCHAR(50),primary_key=False)
    sensor_monitors = Column('sensor_monitors',VARCHAR(2000),primary_key=False)
    database_port = Column('database_port',VARCHAR(50),primary_key=False)
    distro_type = Column('distro_type',VARCHAR(50),primary_key=False)
    sensor_detectors = Column('sensor_detectors',VARCHAR(2000),primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'hostname':self.hostname,
          'database_ossim':self.database_ossim,
          'ocs_db':self.ocs_db,
          'ha_password':self.ha_password,
          'ntp_server':self.ntp_server,
          'admin_ip':self.admin_ip,
          'netflow':self.netflow,
          'ha_heartbeat_comm':self.ha_heartbeat_comm,
          'upgrade':self.upgrade,
          'uuid':self.uuid,
          'server_license':self.server_license,
          'rservers':self.rservers,
          'admin_gateway':self.admin_gateway,
          'timestamp':self.timestamp,
          'database_type':self.database_type,
          'database_acl':self.database_acl,
          'sensor_priority':self.sensor_priority,
          'vpn_port':self.vpn_port,
          'sensor_interfaces':self.sensor_interfaces,
          'firewall_active':self.firewall_active,
          'sensor_name':self.sensor_name,
          'ha_local_node_ip':self.ha_local_node_ip,
          'update_proxy_port':self.update_proxy_port,
          'mailserver_relay_port':self.mailserver_relay_port,
          'innodb':self.innodb,
          'framework_ip':self.framework_ip,
          'update_proxy_dns':self.update_proxy_dns,
          'netflow_remote_collector_port':self.netflow_remote_collector_port,
          'domain':self.domain,
          'ha_device':self.ha_device,
          'sensor_ip':self.sensor_ip,
          'vpn_net':self.vpn_net,
          'override_sensor':self.override_sensor,
          'ha_ping_node':self.ha_ping_node,
          'ha_other_node_ip':self.ha_other_node_ip,
          'config_group':self.config_group,
          'version':self.version,
          'server_port':self.server_port,
          'idm_mssp':self.idm_mssp,
          'ha_deadtime':self.ha_deadtime,
          'sensor_tzone':self.sensor_tzone,
          'ha_heartbeat_start':self.ha_heartbeat_start,
          'database_osvdb':self.database_osvdb,
          'interface':self.interface,
          'snmp_comunity':self.snmp_comunity,
          'mailserver_relay':self.mailserver_relay,
          'language':self.language,
          'update_proxy_user':self.update_proxy_user,
          'vpn_netmask':self.vpn_netmask,
          'server_plugins':self.server_plugins,
          'ha_keepalive':self.ha_keepalive,
          'rebuild_database':self.rebuild_database,
          'update_proxy_pass':self.update_proxy_pass,
          'ha_log':self.ha_log,
          'email_notify':self.email_notify,
          'snmptrap':self.snmptrap,
          'database_event':self.database_event,
          'snmpd':self.snmpd,
          'sensor_ctx':self.sensor_ctx,
          'ha_other_node_name':self.ha_other_node_name,
          'distro_version':self.distro_version,
          'server_ip':self.server_ip,
          'ha_role':self.ha_role,
          'alienvault_ip_reputation':self.alienvault_ip_reputation,
          'profile':self.profile,
          'framework_port':self.framework_port,
          'mailserver_relay_passwd':self.mailserver_relay_passwd,
          'database_ip':self.database_ip,
          'vpn_ip':self.vpn_ip,
          'pci_express':self.pci_express,
          'admin_netmask':self.admin_netmask,
          'framework_https_cert':self.framework_https_cert,
          'admin_dns':self.admin_dns,
          'server_pro':self.server_pro,
          'rsyslogdns_disable':self.rsyslogdns_disable,
          'fixed_server_plugins':self.fixed_server_plugins,
          'framework_https':self.framework_https,
          'framework_https_key':self.framework_https_key,
          'update_proxy':self.update_proxy,
          'id_component':self.id_component,
          'first_init':self.first_init,
          'vpn_infraestructure':self.vpn_infraestructure,
          'database_pass':self.database_pass,
          'mailserver_relay_user':self.mailserver_relay_user,
          'mservers':self.mservers,
          'ha_autofailback':self.ha_autofailback,
          'sensor_networks':self.sensor_networks,
          'ids_rules_flow_control':self.ids_rules_flow_control,
          'database_user':self.database_user,
          'ha_virtual_ip':self.ha_virtual_ip,
          'expert_profile':self.expert_profile,
          'sensor_monitors':self.sensor_monitors,
          'database_port':self.database_port,
          'distro_type':self.distro_type,
          'sensor_detectors':self.sensor_detectors,
        }
class Current_Remote (Base):
    __tablename__='current_remote'

    hostname = Column('hostname',VARCHAR(50),primary_key=False)
    database_ossim = Column('database_ossim',VARCHAR(50),primary_key=False)
    ocs_db = Column('ocs_db',VARCHAR(50),primary_key=False)
    ha_password = deferred(Column('ha_password',VARCHAR(50),primary_key=False))
    ntp_server = Column('ntp_server',VARCHAR(50),primary_key=False)
    admin_ip = Column('admin_ip',VARCHAR(15),primary_key=False)
    netflow = Column('netflow',VARCHAR(50),primary_key=False)
    ha_heartbeat_comm = deferred(Column('ha_heartbeat_comm',VARCHAR(50),primary_key=False))
    upgrade = Column('upgrade',VARCHAR(50),primary_key=False)
    uuid = Column('uuid',VARCHAR(36),primary_key=False)
    server_license = Column('server_license',VARCHAR(50),primary_key=False)
    rservers = Column('rservers',VARCHAR(1000),primary_key=False)
    admin_gateway = Column('admin_gateway',VARCHAR(50),primary_key=False)
    timestamp = Column('timestamp',TIMESTAMP,primary_key=False)
    database_type = Column('database_type',VARCHAR(50),primary_key=False)
    database_acl = Column('database_acl',VARCHAR(50),primary_key=False)
    sensor_priority = Column('sensor_priority',VARCHAR(50),primary_key=False)
    vpn_port = Column('vpn_port',VARCHAR(50),primary_key=False)
    sensor_interfaces = Column('sensor_interfaces',VARCHAR(50),primary_key=False)
    firewall_active = Column('firewall_active',VARCHAR(50),primary_key=False)
    sensor_name = Column('sensor_name',VARCHAR(50),primary_key=False)
    ha_local_node_ip = deferred(Column('ha_local_node_ip',VARCHAR(50),primary_key=False))
    update_proxy_port = Column('update_proxy_port',VARCHAR(50),primary_key=False)
    mailserver_relay_port = Column('mailserver_relay_port',VARCHAR(50),primary_key=False)
    innodb = Column('innodb',VARCHAR(50),primary_key=False)
    framework_ip = Column('framework_ip',VARCHAR(50),primary_key=False)
    update_proxy_dns = Column('update_proxy_dns',VARCHAR(50),primary_key=False)
    netflow_remote_collector_port = Column('netflow_remote_collector_port',INTEGER(11),primary_key=False)
    domain = Column('domain',VARCHAR(30),primary_key=False)
    ha_device = deferred(Column('ha_device',VARCHAR(50),primary_key=False))
    sensor_ip = Column('sensor_ip',VARCHAR(50),primary_key=False)
    vpn_net = Column('vpn_net',VARCHAR(50),primary_key=False)
    override_sensor = Column('override_sensor',VARCHAR(50),primary_key=False)
    ha_ping_node = deferred(Column('ha_ping_node',VARCHAR(50),primary_key=False))
    ha_other_node_ip = deferred(Column('ha_other_node_ip',VARCHAR(50),primary_key=False))
    config_group = Column('config_group',VARCHAR(50),primary_key=False)
    version = Column('version',VARCHAR(50),primary_key=False)
    server_port = Column('server_port',VARCHAR(50),primary_key=False)
    idm_mssp = Column('idm_mssp',VARCHAR(50),primary_key=False)
    ha_deadtime = deferred(Column('ha_deadtime',VARCHAR(50),primary_key=False))
    sensor_tzone = Column('sensor_tzone',VARCHAR(50),primary_key=False)
    ha_heartbeat_start = deferred(Column('ha_heartbeat_start',VARCHAR(50),primary_key=False))
    database_osvdb = Column('database_osvdb',VARCHAR(50),primary_key=False)
    interface = Column('interface',VARCHAR(50),primary_key=False)
    snmp_comunity = Column('snmp_comunity',VARCHAR(50),primary_key=False)
    mailserver_relay = Column('mailserver_relay',VARCHAR(50),primary_key=False)
    language = Column('language',VARCHAR(50),primary_key=False)
    update_proxy_user = Column('update_proxy_user',VARCHAR(50),primary_key=False)
    vpn_netmask = Column('vpn_netmask',VARCHAR(50),primary_key=False)
    server_plugins = Column('server_plugins',VARCHAR(50),primary_key=False)
    ha_keepalive = deferred(Column('ha_keepalive',VARCHAR(50),primary_key=False))
    rebuild_database = Column('rebuild_database',VARCHAR(50),primary_key=False)
    update_proxy_pass = Column('update_proxy_pass',VARCHAR(50),primary_key=False)
    ha_log = deferred(Column('ha_log',VARCHAR(50),primary_key=False))
    email_notify = Column('email_notify',VARCHAR(50),primary_key=False)
    snmptrap = Column('snmptrap',VARCHAR(50),primary_key=False)
    database_event = Column('database_event',VARCHAR(50),primary_key=False)
    snmpd = Column('snmpd',VARCHAR(50),primary_key=False)
    sensor_ctx = Column('sensor_ctx',VARCHAR(50),primary_key=False)
    ha_other_node_name = deferred(Column('ha_other_node_name',VARCHAR(50),primary_key=False))
    distro_version = Column('distro_version',VARCHAR(50),primary_key=False)
    server_ip = Column('server_ip',VARCHAR(50),primary_key=False)
    ha_role = deferred(Column('ha_role',VARCHAR(50),primary_key=False))
    alienvault_ip_reputation = Column('alienvault_ip_reputation',VARCHAR(50),primary_key=False)
    profile = Column('profile',VARCHAR(50),primary_key=False)
    framework_port = Column('framework_port',VARCHAR(50),primary_key=False)
    mailserver_relay_passwd = Column('mailserver_relay_passwd',VARCHAR(255),primary_key=False)
    database_ip = Column('database_ip',VARCHAR(50),primary_key=False)
    vpn_ip = Column('vpn_ip',VARCHAR(50),primary_key=False)
    pci_express = Column('pci_express',VARCHAR(50),primary_key=False)
    admin_netmask = Column('admin_netmask',VARCHAR(50),primary_key=False)
    framework_https_cert = Column('framework_https_cert',VARCHAR(150),primary_key=False)
    admin_dns = Column('admin_dns',VARCHAR(50),primary_key=False)
    server_pro = Column('server_pro',VARCHAR(50),primary_key=False)
    rsyslogdns_disable = Column('rsyslogdns_disable',VARCHAR(50),primary_key=False)
    fixed_server_plugins = Column('fixed_server_plugins',VARCHAR(50),primary_key=False)
    framework_https = Column('framework_https',VARCHAR(50),primary_key=False)
    framework_https_key = Column('framework_https_key',VARCHAR(150),primary_key=False)
    update_proxy = Column('update_proxy',VARCHAR(50),primary_key=False)
    id_component = Column('id_component',INTEGER(11),primary_key=True)
    first_init = Column('first_init',VARCHAR(50),primary_key=False)
    vpn_infraestructure = Column('vpn_infraestructure',VARCHAR(50),primary_key=False)
    database_pass = Column('database_pass',VARCHAR(50),primary_key=False)
    mailserver_relay_user = Column('mailserver_relay_user',VARCHAR(50),primary_key=False)
    mservers = Column('mservers',VARCHAR(1024),primary_key=False)
    ha_autofailback = deferred(Column('ha_autofailback',VARCHAR(50),primary_key=False))
    sensor_networks = Column('sensor_networks',VARCHAR(2000),primary_key=False)
    ids_rules_flow_control = Column('ids_rules_flow_control',VARCHAR(50),primary_key=False)
    database_user = Column('database_user',VARCHAR(50),primary_key=False)
    ha_virtual_ip = deferred(Column('ha_virtual_ip',VARCHAR(50),primary_key=False))
    expert_profile = Column('expert_profile',VARCHAR(50),primary_key=False)
    sensor_monitors = Column('sensor_monitors',VARCHAR(2000),primary_key=False)
    database_port = Column('database_port',VARCHAR(50),primary_key=False)
    distro_type = Column('distro_type',VARCHAR(50),primary_key=False)
    sensor_detectors = Column('sensor_detectors',VARCHAR(2000),primary_key=False)
    #
    # Relations:
    #
    @property
    def serialize(self):
        return {
          'hostname':self.hostname,
          'database_ossim':self.database_ossim,
          'ocs_db':self.ocs_db,
          'ha_password':self.ha_password,
          'ntp_server':self.ntp_server,
          'admin_ip':self.admin_ip,
          'netflow':self.netflow,
          'ha_heartbeat_comm':self.ha_heartbeat_comm,
          'upgrade':self.upgrade,
          'uuid':self.uuid,
          'server_license':self.server_license,
          'rservers':self.rservers,
          'admin_gateway':self.admin_gateway,
          'timestamp':self.timestamp,
          'database_type':self.database_type,
          'database_acl':self.database_acl,
          'sensor_priority':self.sensor_priority,
          'vpn_port':self.vpn_port,
          'sensor_interfaces':self.sensor_interfaces,
          'firewall_active':self.firewall_active,
          'sensor_name':self.sensor_name,
          'ha_local_node_ip':self.ha_local_node_ip,
          'update_proxy_port':self.update_proxy_port,
          'mailserver_relay_port':self.mailserver_relay_port,
          'innodb':self.innodb,
          'framework_ip':self.framework_ip,
          'update_proxy_dns':self.update_proxy_dns,
          'netflow_remote_collector_port':self.netflow_remote_collector_port,
          'domain':self.domain,
          'ha_device':self.ha_device,
          'sensor_ip':self.sensor_ip,
          'vpn_net':self.vpn_net,
          'override_sensor':self.override_sensor,
          'ha_ping_node':self.ha_ping_node,
          'ha_other_node_ip':self.ha_other_node_ip,
          'config_group':self.config_group,
          'version':self.version,
          'server_port':self.server_port,
          'idm_mssp':self.idm_mssp,
          'ha_deadtime':self.ha_deadtime,
          'sensor_tzone':self.sensor_tzone,
          'ha_heartbeat_start':self.ha_heartbeat_start,
          'database_osvdb':self.database_osvdb,
          'interface':self.interface,
          'snmp_comunity':self.snmp_comunity,
          'mailserver_relay':self.mailserver_relay,
          'language':self.language,
          'update_proxy_user':self.update_proxy_user,
          'vpn_netmask':self.vpn_netmask,
          'server_plugins':self.server_plugins,
          'ha_keepalive':self.ha_keepalive,
          'rebuild_database':self.rebuild_database,
          'update_proxy_pass':self.update_proxy_pass,
          'ha_log':self.ha_log,
          'email_notify':self.email_notify,
          'snmptrap':self.snmptrap,
          'database_event':self.database_event,
          'snmpd':self.snmpd,
          'sensor_ctx':self.sensor_ctx,
          'ha_other_node_name':self.ha_other_node_name,
          'distro_version':self.distro_version,
          'server_ip':self.server_ip,
          'ha_role':self.ha_role,
          'alienvault_ip_reputation':self.alienvault_ip_reputation,
          'profile':self.profile,
          'framework_port':self.framework_port,
          'mailserver_relay_passwd':self.mailserver_relay_passwd,
          'database_ip':self.database_ip,
          'vpn_ip':self.vpn_ip,
          'pci_express':self.pci_express,
          'admin_netmask':self.admin_netmask,
          'framework_https_cert':self.framework_https_cert,
          'admin_dns':self.admin_dns,
          'server_pro':self.server_pro,
          'rsyslogdns_disable':self.rsyslogdns_disable,
          'fixed_server_plugins':self.fixed_server_plugins,
          'framework_https':self.framework_https,
          'framework_https_key':self.framework_https_key,
          'update_proxy':self.update_proxy,
          'id_component':self.id_component,
          'first_init':self.first_init,
          'vpn_infraestructure':self.vpn_infraestructure,
          'database_pass':self.database_pass,
          'mailserver_relay_user':self.mailserver_relay_user,
          'mservers':self.mservers,
          'ha_autofailback':self.ha_autofailback,
          'sensor_networks':self.sensor_networks,
          'ids_rules_flow_control':self.ids_rules_flow_control,
          'database_user':self.database_user,
          'ha_virtual_ip':self.ha_virtual_ip,
          'expert_profile':self.expert_profile,
          'sensor_monitors':self.sensor_monitors,
          'database_port':self.database_port,
          'distro_type':self.distro_type,
          'sensor_detectors':self.sensor_detectors,
          'uuidbin': uuid.UUID(self.uuid).bytes
        }
