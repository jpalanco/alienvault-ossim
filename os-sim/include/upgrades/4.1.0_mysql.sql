USE alienvault;
SET AUTOCOMMIT=0;
BEGIN;

DELETE FROM port WHERE protocol_name='Q';

REPLACE INTO host_property_reference (`id`, `name`, `ord`, `description`) VALUES
(9, 'acl', 11, 'ACL'),
(10, 'route', 12, 'Route'),
(11, 'storage', 13, 'Storage'),
(12, 'role', 4, 'Role'),
(13, 'video', 10, 'Video');

UPDATE custom_report_types SET  `inputs` = 'Source Database:source:select:OSS_ALPHA:EVENTSOURCELOGGER:;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;DS Groups:plugin_groups:select:OSS_INPUT.OSS_NULLABLE:PLUGINGROUPS:' WHERE `id` =144;

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(149, 'Custom List', 'Raw Logs', 'Logger/CustomList.php', 'Top Events List:top:text:OSS_DIGIT:50:150;Source Database:source:select:OSS_ALPHA:EVENTSOURCELOGGER:;Filter:filter:select:OSS_ALPHA.OSS_SPACE:FILTERLOGGER', '', 1),
(360, 'Trends', 'Availability', 'Availability/Trends.php', 'Sensor:sensor_nagios:select:OSS_HEX,OSS_NULLABLE:SENSORNAGIOS:', '', 1),
(361, 'Availability State', 'Availability', 'Availability/Availability.php', 'Sensor:sensor_nagios:select:OSS_HEX,OSS_NULLABLE:SENSORNAGIOS:', '', 1),
(362, 'Event Histogram', 'Availability', 'Availability/EventHistogram.php', 'Sensor:sensor_nagios:select:OSS_HEX,OSS_NULLABLE:SENSORNAGIOS:', '', 1),
(364, 'Event Summary', 'Availability', 'Availability/EventSummary.php', 'Sensor:sensor_nagios:select:OSS_HEX,OSS_NULLABLE:SENSORNAGIOS:', '', 1),
(365, 'Notifications', 'Availability', 'Availability/Notifications.php', 'Sensor:sensor_nagios:select:OSS_HEX,OSS_NULLABLE:SENSORNAGIOS:', '', 1);

DELETE FROM `acl_perm` WHERE id=81;
REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(12, 'MENU', 'configuration-menu', 'PolicySensors', 'Deployment -> AlienVault Components -> Sensors', 1, 0, 1, '08.04'),
(17, 'MENU', 'configuration-menu', 'PluginGroups', 'Deployment -> Collection -> DS Groups', 0, 0, 1, '08.07'),
(29, 'MENU', 'configuration-menu', 'ASEC', 'Deployment -> Collection -> Smart Event Collection', 1, 0, 1, '08.09'),
(35, 'MENU', 'configuration-menu', 'ConfigurationUsers', 'Deployment -> Users', 0, 0, 1, '08.02'),
(36, 'MENU', 'configuration-menu', 'ConfigurationPlugins', 'Deployment -> Collection -> Data Sources', 0, 0, 1, '08.06'),
(39, 'MENU', 'configuration-menu', 'ConfigurationUserActionLog', 'Deployment -> Users -> User activity', 0, 0, 1, '08.03'),
(44, 'MENU', 'configuration-menu', 'ToolsBackup', 'Deployment -> Backup', 0, 0, 1, '08.13'),
(53, 'MENU', 'configuration-menu', 'PolicyServers', 'Deployment -> AlienVault Components -> Servers', 0, 0, 1, '08.05'),
(57, 'MENU', 'configuration-menu', 'ToolsDownloads', 'Deployment -> Collection -> Downloads', 0, 0, 1, '08.08'),
(69, 'MENU', 'configuration-menu', 'ToolsUserLog', 'Deployment -> Collection -> User Activity', 0, 0, 1, '08.10'),
(80, 'MENU', 'configuration-menu', 'NetworkDiscovery', 'Deployment -> Network Discovery', 0, 0, 1, '08.11'),
(85, 'MENU', 'configuration-menu', 'AlienVaultInventory', 'Deployment -> Collection -> Inventory', 0, 0, 1, '08.06');

UPDATE `risk_indicators` SET name='Alabama',icon='pixmaps/uploaded/logis01.jpg',x=723,y=388 WHERE id=34;
UPDATE `risk_indicators` SET name='Mississippi',icon='pixmaps/uploaded/logis26.jpg',x=659,y=450 WHERE id=35;

SET @newuuid = REPLACE(UUID(),'-','');
REPLACE INTO alienvault.plugin_group_descr (group_id,plugin_id,plugin_sid) VALUES (@newuuid, 1505, "ANY");
REPLACE INTO alienvault.plugin_group (group_id,name,descr) VALUES (@newuuid, "Directive events", "Directive events signatures");

-- Asset Notes
CREATE TABLE IF NOT EXISTS `alienvault`.`notes` (
`id` INT NOT NULL AUTO_INCREMENT,
`type` ENUM( 'host', 'net', 'host_group', 'net_group' ) NOT NULL ,
`date` datetime NOT NULL,
`user` varchar(64) NOT NULL,
`asset_id` BINARY( 16 ) NOT NULL ,
`note` TEXT NOT NULL,
PRIMARY KEY (`id`),
KEY `type` (`type`,`asset_id`,`date`)
) ENGINE = INNODB DEFAULT CHARSET=utf8;

DELETE FROM user_config where name like '%layout';

-- Dashboard
REPLACE INTO `dashboard_custom_type` (`id`, `name`, `type`, `category`, `title_default`, `help_default`, `file`, `params`, `thumb`) VALUES (1001, 'Siem vs Logger', 'chart', 'SIEM', 'Last SIEM vs Logger Events', 'Events in the SIEM vs Events in the
Logger', 'widgets/data/siem.php?type=siemlogger', 'Type:type:select:OSS_LETTER:raphael::Trend Chart', '1001.png');


DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  -- KDB
  IF EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'repository_relationships' AND COLUMN_NAME = 'name')
  THEN
      ALTER TABLE  `repository_relationships` DROP `name`;
      ALTER TABLE  `repository` CHANGE  `ctx`  `creator` VARCHAR( 64 ) NOT NULL DEFAULT '0';
      ALTER TABLE  `repository` CHANGE  `user` `in_charge` VARCHAR( 64 ) NOT NULL;
      ALTER TABLE  `repository` AUTO_INCREMENT=100000;
  END IF;
  
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'repository_relationships' AND INDEX_NAME='keyname')
  THEN
      ALTER TABLE `repository_relationships` ADD INDEX `keyname` ( `keyname` );
  END IF;  
  
  -- ALARMS
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'alarm' AND COLUMN_NAME = 'stats')
  THEN
      ALTER TABLE  `alarm` ADD  `stats` TEXT NOT NULL AFTER  `similar`;
      ALTER TABLE  `alarm` ADD INDEX  `plugins` (  `plugin_id` ,  `plugin_sid` );
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

UPDATE `repository` SET `creator`=0;
UPDATE alarm SET removable=0;

-- Locations
CREATE TABLE IF NOT EXISTS `alienvault`.`locations` (
  `id` binary(16) NOT NULL,
  `ctx` binary(16) NOT NULL,
  `name` varchar(64) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `lat` float NOT NULL,
  `lon` float NOT NULL,
  `country` varchar(2) NOT NULL,
  `checks` BINARY(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `alienvault`.`location_sensor_reference` (
  `location_id` binary(16) NOT NULL,
  `sensor_id` binary(16) NOT NULL,
  PRIMARY KEY (location_id,sensor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Device types
CREATE TABLE IF NOT EXISTS `alienvault`.`device_types` (
`id` INT NOT NULL,
`name` varchar(64) NOT NULL,
`class` INT NOT NULL,
PRIMARY KEY (`id`)
) ENGINE = INNODB DEFAULT CHARSET=utf8;

REPLACE INTO `alienvault`.`device_types` (`id`, `name`, `class`) VALUES
(1,'Server',0),
(2,'Endpoint',0),
(3,'Mobile',0),
(4,'Network device',0),
(5,'Peripheral',0),
(6,'Industrial Device',0),
(7,'Security Device',0),

(100,'HTTP Server',1),
(101,'Mail Server',1),
(102,'Domain Controller',1),
(103,'DNS Server',1),
(104,'File Server',1),
(105,'Proxy Server',1),

(301,'Mobile',3),
(302,'Tablet',3),

(401,'Router',4),
(402,'Switch',4),
(403,'VPN device',4),
(404,'Wireless AP',4),

(501,'Printer',5),
(502,'Camera',5),

(601,'PLC',6),

(701,'Firewall',7),
(702,'Intrusion Detection System',7),
(703,'Intrusion Prevention System',7);

CREATE TABLE IF NOT EXISTS `alienvault`.`host_types` (
`host_id` binary(16) NOT NULL,
`type` INT NOT NULL ,
`subtype` INT NOT NULL,
PRIMARY KEY (`host_id`,`type`,`subtype`)
) ENGINE = INNODB DEFAULT CHARSET=utf8;


-- Asset Search
REPLACE INTO `alienvault`.`inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES
('Alarms', 'Has Alarm', 'boolean', '', 'SELECT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm UNION SELECT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('Alarms', 'Has closed Alarms', 'boolean', '', 'SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE status=''closed'' UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE status=''closed''', 999),
('Alarms', 'Has no Alarm', 'boolean', '', 'SELECT DISTINCT HEX(ip) AS ip, HEX(ctx) AS ctx FROM (select i.ip AS ip, h.ctx AS ctx from host h, host_ip i WHERE h.id=i.host_id UNION SELECT DISTINCT ip_src as ip, ctx FROM alienvault_siem.acid_event) AS todas WHERE CONCAT(ip,'','',ctx) NOT IN (SELECT DISTINCT CONCAT(src_ip,'','',corr_engine_ctx) FROM alarm) AND CONCAT(ip,'','',ctx) NOT IN (SELECT DISTINCT CONCAT(dst_ip,'','',corr_engine_ctx) FROM alarm)', 999),
('Alarms', 'Has open Alarms', 'boolean', '', 'SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE status=''open'' UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE status=''open''', 999),
('Alarms', 'IP is Dst', 'boolean', '', 'SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('Alarms', 'IP is Src', 'boolean', '', 'SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('Asset', 'Asset is', 'fixed', 'SELECT DISTINCT asset FROM host ORDER BY asset', 'SELECT DISTINCT HEX(h.id) AS id, HEX(h.ctx) AS ctx FROM host h WHERE asset = ? UNION SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_src = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_dst = ?', 999),
('Asset', 'Asset is greater than', 'number', '', 'SELECT DISTINCT HEX(i.ip) AS ip, HEX(h.ctx) AS ctx FROM host h, host_ip i WHERE h.id = i.host_id AND h.asset > ? UNION SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_src > ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_dst > ?', 999),
('Asset', 'Asset is local', 'fixed', 'SELECT DISTINCT asset FROM host ORDER BY asset', 'SELECT DISTINCT HEX(id) AS id, HEX(ctx) AS ctx FROM host WHERE asset = ?', 999),
('Asset', 'Asset is lower than', 'number', '', 'SELECT DISTINCT HEX(i.ip) AS ip, HEX(h.ctx) AS ctx FROM host h, host_ip i WHERE h.id = i.host_id AND h.asset < ? AND h.asset > 0 UNION SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_src < ? AND ossim_asset_src > 0 UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_dst < ? AND ossim_asset_dst > 0', 999),
('Asset', 'Asset is remote', 'number', '', 'SELECT HEX(ip) AS ip, HEX(ctx) AS ctx FROM (SELECT DISTINCT ip_src as ip, ctx FROM alienvault_siem.acid_event WHERE ossim_asset_src = ? UNION SELECT DISTINCT ip_dst as ip, ctx FROM alienvault_siem.acid_event WHERE ossim_asset_dst = ?) remote WHERE CONCAT(ip,'','',ctx) NOT IN (SELECT DISTINCT CONCAT(i.ip,'','',h.ctx) FROM host h, host_ip i WHERE h.id=i.host_id)', 999),
('Mac', 'Has Mac', 'boolean', '', 'SELECT DISTINCT HEX(host_ip.ip) AS ip, HEX(host.ctx) AS ctx FROM host, host_ip WHERE host.id = host_ip.host_id AND host_ip.mac IS NOT NULL', 3),
('Mac', 'Has No Mac', 'boolean', '', 'SELECT DISTINCT HEX(host_ip.ip) AS ip, HEX(host.ctx) AS ctx FROM host, host_ip WHERE host.id = host_ip.host_id AND host_ip.mac IS NULL', 3),
('META', 'Date After', 'date', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE timestamp > ? UNION SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE timestamp > ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE timestamp > ? UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE timestamp > ?', 999),
('META', 'Date Before', 'date', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE timestamp < ? UNION SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE timestamp < ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE timestamp < ? UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE timestamp < ?', 999),
('META', 'Destination Port', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_dport = ? AND ip_proto = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_dport = ? AND ip_proto = ?', 999),
('META', 'Has Dst IP', 'boolean', '', 'SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('META', 'Has Src IP', 'boolean', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('META', 'Has Src or Dst IP', 'boolean', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('META', 'IP as Dst', 'ip', 'SELECT DISTINCT INET_NTOA(conv(HEX(ip), 16, 10)) as value FROM host_ip', 'SELECT DISTINCT HEX(ip_src) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_dst), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(alarm.src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE INET_NTOA(conv(HEX(alarm.dst_ip), 16, 10)) %op% ?', 999),
('META', 'IP as Src', 'ip', 'SELECT DISTINCT INET_NTOA(conv(HEX(ip), 16, 10)) as value FROM host_ip', 'SELECT DISTINCT HEX(ip_dst) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_src), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(alarm.dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE INET_NTOA(conv(HEX(alarm.src_ip), 16, 10)) %op% ?', 999),
('META', 'IP as Src or Dst', 'ip', 'SELECT DISTINCT INET_NTOA(conv(HEX(ip), 16, 10)) as value FROM host_ip', 'SELECT DISTINCT HEX(ip_dst) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_src), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE INET_NTOA(conv(HEX(alarm.src_ip), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(ip_src) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_dst), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE INET_NTOA(conv(HEX(dst_ip), 16, 10)) %op% ?', 999),
('META', 'Port as Src or Dst', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_dport = ? AND ip_proto = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_dport = ? AND ip_proto = ?', 999),
('META', 'Source Port', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_sport = ? AND ip_proto = ?', 999),
('OS', 'Has Anomaly', 'boolean', '', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host_properties, host WHERE host_properties.host_id = host.id AND host_properties.property_ref = 3 AND host_properties.source_id != 1 AND host_properties.source_id != 2', 1),
('OS', 'Has no Anomaly', 'boolean', '', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host_properties, host WHERE host_properties.host_id = host.id AND host_properties.property_ref = 3 AND (host_properties.source_id = 1 OR host_properties.source_id = 2)', 1),
('OS', 'OS is', 'text', 'SELECT DISTINCT value FROM host_properties WHERE property_ref = 3 ORDER BY value', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host_properties, host WHERE host_properties.host_id = host.id AND host_properties.property_ref = 3 AND host_properties.value %op% ?', 1),
('OS', 'OS is Not', 'text', 'SELECT DISTINCT value FROM host_properties WHERE property_ref = 3 ORDER BY value', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host WHERE id NOT IN (SELECT host.id FROM host_properties, host WHERE host_properties.host_id = host.id AND host_properties.property_ref = 3 AND host_properties.value %op% ?)', 1),
('Property', 'Contains', 'fixedText', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT HEX(p.host_id) AS id, HEX(h.ctx) AS ctx FROM host h, host_properties p WHERE h.id = p.host_id AND p.property_ref = ? AND (p.value LIKE ''%$value2%'' OR p.extra LIKE ''%$value2%'')', 999),
('Property', 'Has not Property', 'fixed', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT HEX(id) AS id, HEX(ctx) AS ctx FROM host WHERE id NOT IN (SELECT DISTINCT p.host_id FROM host h, host_properties p WHERE h.id = p.host_id AND p.property_ref = ?)', 999),
('Property', 'Has Property', 'fixed', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT HEX(p.host_id) AS id, HEX(h.ctx) AS ctx FROM host h, host_properties p WHERE h.id = p.host_id AND p.property_ref = ?', 999),
('Services', 'Doesnt have service', 'fixed', 'SELECT DISTINCT service as service_value, service as service_text FROM host_services', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host WHERE id NOT IN (SELECT host.id FROM host, host_services WHERE host.id = host_services.host_id AND service=?)', 2),
('Services', 'Has Anomaly', 'boolean', '', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host, host_services WHERE host.id = host_services.host_id AND source_id != 1 AND source_id != 2', 2),
('Services', 'Has no Anomaly', 'boolean', '', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host, host_services WHERE host.id = host_services.host_id AND (source_id = 1 OR source_id = 2)', 2),
('Services', 'Has services', 'fixed', 'SELECT DISTINCT service as service_value, service as service_text FROM host_services', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host, host_services WHERE host.id = host_services.host_id AND service=?', 2),
('SIEM Events', 'Has Different', 'number', '', 'SELECT ip, ctx FROM (SELECT count(distinct plugin_id, plugin_sid) AS total, ip, ctx FROM (select plugin_id, plugin_sid, HEX(ip_src) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION select plugin_id, plugin_sid, HEX(ip_dst) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event) AS t group by ip, ctx) AS t1 WHERE t1.total >= ?', 5),
('SIEM Events', 'Has Dst IP', 'ip', 'SELECT DISTINCT INET_NTOA(conv(HEX(ip), 16, 10)) as value FROM host_ip', 'SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_dst), 16, 10)) %op% ?', 5),
('SIEM Events', 'Has Dst Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto = ? AND layer4_dport = ?', 5),
('SIEM Events', 'Has Event', 'boolean', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event', 5),
('SIEM Events', 'Has Events', 'text', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event s, plugin_sid p WHERE s.plugin_id=p.plugin_id AND s.plugin_sid=p.sid AND p.name %op% ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event s, plugin_sid p WHERE s.plugin_id=p.plugin_id AND s.plugin_sid=p.sid AND p.name %op% ?', 5),
('SIEM Events', 'Has IP', 'ip', 'SELECT DISTINCT inet_ntoa(conv(HEX(ip), 16, 10)) AS ip FROM host_ip', 'SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE inet_ntoa(conv(HEX(ip_dst), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE inet_ntoa(conv(HEX(ip_src), 16, 10)) %op% ?', 5),
('SIEM Events', 'Has no Event', 'boolean', '', 'SELECT DISTINCT HEX(host.id) AS id, HEX(host.ctx) AS ctx FROM host, host_ip WHERE host.id = host_ip.host_id AND CONCAT(host_ip.ip,'','',host.ctx) NOT IN (SELECT DISTINCT CONCAT (ip_src,'','',ctx) FROM alienvault_siem.acid_event WHERE CONCAT (ip_src,'','',ctx) != NULL UNION SELECT DISTINCT CONCAT(ip_dst,'','',ctx) FROM alienvault_siem.acid_event WHERE CONCAT(ip_dst,'','',ctx) != "NULL")', 5),
('SIEM Events', 'Has Plugin Groups', 'fixed', 'SELECT HEX(group_id) AS value, name FROM plugin_group', 'SELECT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE plugin_id in (SELECT plugin_id FROM alienvault.plugin_group WHERE group_id=UNHEX(?)) UNION SELECT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE plugin_id in (SELECT plugin_id FROM alienvault.plugin_group WHERE group_id=UNHEX(?))', 5),
('SIEM Events', 'Has Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto = ? AND layer4_sport = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto = ? AND layer4_dport = ?', 5),
('SIEM Events', 'Has Protocol', 'fixed', 'SELECT id,alias FROM protocol', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto=? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto=? LIMIT 999', 5),
('SIEM Events', 'Has Src IP', 'ip', 'SELECT DISTINCT INET_NTOA(conv(HEX(ip), 16, 10)) as value FROM host_ip', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_src), 16, 10)) %op% ?', 5),
('SIEM Events', 'Has Src Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto = ? AND layer4_sport = ?', 5),
('SIEM Events', 'Has user', 'text', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event, alienvault_siem.extra_data WHERE alienvault_siem.extra_data.event_id = alienvault_siem.acid_event.id AND alienvault_siem.extra_data.username %op% ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event, alienvault_siem.extra_data WHERE alienvault_siem.extra_data.event_id = alienvault_siem.acid_event.id AND alienvault_siem.extra_data.username %op% ?', 5),
('SIEM Events', 'IP is Dst', 'boolean', '', 'SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event LIMIT 999', 5),
('SIEM Events', 'IP is Src', 'boolean', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event LIMIT 999', 5),
('Tickets', 'Has no Ticket', 'boolean', '', 'SELECT HEX(i.ip) as ip, HEX(h.ctx) AS ctx FROM host h, host_ip i WHERE h.id = i.host_id AND concat(INET_NTOA(conv(ip, 16, 10)),'','',ctx) NOT IN (SELECT DISTINCT concat(a.src_ips, '','', HEX(i.ctx)) FROM incident i,incident_alarm a WHERE i.id=a.incident_id)', 999),
('Tickets', 'Has Ticket Tag', 'fixed', 'SELECT id as tag_id,name as tag_name FROM incident_tag_descr', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a,incident_tag t WHERE i.id=a.incident_id AND i.id=t.incident_id AND t.tag_id=?', 999),
('Tickets', 'Has Ticket Type', 'fixed', 'SELECT id as type_value,id as type_text FROM incident_type', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.type_id=?', 999),
('Tickets', 'Has Tickets', 'boolean', '', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a WHERE i.id=a.incident_id', 999),
('Tickets', 'Is older Than Days', 'number', '', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND DATEDIFF(CURRENT_TIMESTAMP ,i.last_update) > ?', 999),
('Tickets', 'Priority is greater than', 'number', '', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.priority>?', 999),
('Tickets', 'Priority is lower than', 'number', '', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.priority<?', 999),
('Vulnerabilities', 'Has CVE', 'text', '', 'SELECT DISTINCT HEX(s.host_ip) AS ip, HEX(s.ctx) AS ctx FROM vuln_nessus_plugins p,host_plugin_sid s WHERE s.plugin_id=3001 and s.plugin_sid=p.id AND p.cve_id %op% ?', 4),
('Vulnerabilities', 'Has no Vulns', 'boolean', '', 'SELECT DISTINCT HEX(host.id) AS id, HEX(host.ctx) AS ctx FROM host, host_ip WHERE host.id = host_ip.host_id AND CONCAT (host_ip.ip, '','', host.ctx) NOT IN (SELECT CONCAT(host_ip, '','', ctx) FROM host_plugin_sid WHERE plugin_id = 3001)', 4),
('Vulnerabilities', 'Has Vuln', 'fixed', 'SELECT plugin_sid.sid as plugin_value, plugin_sid.name as plugin_text FROM plugin_sid, host_plugin_sid WHERE plugin_sid.plugin_id =3001 AND plugin_sid.sid = host_plugin_sid.plugin_sid LIMIT 999', 'SELECT DISTINCT HEX(host_ip) AS ip, HEX(ctx) AS ctx FROM host_plugin_sid WHERE plugin_id = 3001 AND plugin_sid = ?', 4),
('Vulnerabilities', 'Has Vuln Service', 'text', 'SELECT DISTINCT app FROM vuln_nessus_results', 'SELECT DISTINCT conv(INET_ATON(hostIP), 10, 16) as ip FROM vuln_nessus_results WHERE app %op% ?', 999),
('Vulnerabilities', 'Has Vulns', 'boolean', '', 'SELECT DISTINCT HEX(host_ip) AS ip, HEX(ctx) AS ctx FROM host_plugin_sid WHERE plugin_id = 3001', 4),
('Vulnerabilities', 'Vuln Contains', 'text', '', 'SELECT DISTINCT HEX(hp.host_ip) AS ip, HEX(hp.ctx) AS ctx FROM host_plugin_sid hp, plugin_sid p WHERE hp.plugin_id = 3001 AND p.plugin_id = 3001 AND hp.plugin_sid = p.sid AND p.name %op% ? UNION SELECT DISTINCT HEX(s.host_ip) AS ip, HEX(s.ctx) AS ctx FROM vuln_nessus_plugins p,host_plugin_sid s WHERE s.plugin_id=3001 and s.plugin_sid=p.id AND p.name %op% ?', 4),
('Vulnerabilities', 'Vuln Level is greater than', 'number', '', 'SELECT DISTINCT HEX(host.id) AS id, HEX(host.ctx) AS ctx FROM host_vulnerability, host WHERE host_vulnerability.host_id = host.id AND vulnerability > ?', 4),
('Vulnerabilities', 'Vuln Level is lower than', 'number', '', 'SELECT DISTINCT HEX(host.id) AS id, HEX(host.ctx) AS ctx FROM host_vulnerability, host WHERE host_vulnerability.host_id = host.id AND vulnerability < ?', 4),
('Vulnerabilities', 'Vuln risk is greater than', 'number', '', 'SELECT DISTINCT HEX(h.host_ip) AS ip, HEX(h.ctx) AS ctx FROM host_plugin_sid h,vuln_nessus_plugins p WHERE h.plugin_id=3001 AND h.plugin_sid=p.id AND 8-p.risk > ?', 4),
('Vulnerabilities', 'Vuln risk is lower than', 'number', '', 'SELECT DISTINCT HEX(h.host_ip) AS ip, HEX(h.ctx) AS ctx FROM host_plugin_sid h,vuln_nessus_plugins p WHERE h.plugin_id=3001 AND h.plugin_sid=p.id AND 8-p.risk < ?', 4);

-- Entities RRD's
CREATE TABLE IF NOT EXISTS `acl_entities_stats` (
`entity_id` BINARY(16) NOT NULL,
`ts` TIMESTAMP NULL DEFAULT NULL,
`stat` FLOAT(10,2) NULL DEFAULT NULL,
PRIMARY KEY (`entity_id`)
)
ENGINE = InnoDB DEFAULT CHARSET=utf8;


-- BBDD alienvault_asec
CREATE SCHEMA IF NOT EXISTS alienvault_asec DEFAULT CHARACTER SET utf8 ;
USE alienvault_asec;

CREATE TABLE IF NOT EXISTS `suggestions` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `suggestion_group_id` BINARY(16) NOT NULL ,
  `filename` VARCHAR(255) NOT NULL ,
  `location` VARCHAR(255) NOT NULL ,
  `datetime` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `alarm_coincidence` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `data` TEXT NOT NULL ,
  `sample_log` TEXT NOT NULL ,
  `sensor_id` BINARY(16) NOT NULL ,
  `datetime` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Table to store the asec alarms.' ;

CREATE TABLE IF NOT EXISTS `notification` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `plugin_id` INT(11)  NOT NULL ,
  `rule_name` VARCHAR(45) NOT NULL ,
  `log_file` VARCHAR(45) NOT NULL ,
  `ignore` TINYINT(4)  NULL DEFAULT 0 ,
  `ignore_timestamp` DATETIME NULL ,
  `sensor_id` BINARY(16) NOT NULL ,
  `datetime` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `event_fields` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `tag` TEXT NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `suggestion_pattern` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `suggestion_group_id` BINARY(16) NOT NULL ,
  `pattern_json` TEXT NOT NULL ,
  `status` TINYINT(1) NOT NULL DEFAULT '0' ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `data_sources` (
  `id` INT(11) NOT NULL,
  `name` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

REPLACE INTO event_fields VALUES(1,'type');
REPLACE INTO event_fields VALUES(2,'date');
REPLACE INTO event_fields VALUES(3,'sensor');
REPLACE INTO event_fields VALUES(4,'device');
REPLACE INTO event_fields VALUES(5,'interface');
REPLACE INTO event_fields VALUES(6,'plugin_id');
REPLACE INTO event_fields VALUES(7,'plugin_sid');
REPLACE INTO event_fields VALUES(8,'protocol');
REPLACE INTO event_fields VALUES(9,'src_ip');
REPLACE INTO event_fields VALUES(10,'src_port');
REPLACE INTO event_fields VALUES(11,'dst_ip');
REPLACE INTO event_fields VALUES(12,'dst_port');
REPLACE INTO event_fields VALUES(13,'username');
REPLACE INTO event_fields VALUES(14,'password');
REPLACE INTO event_fields VALUES(15,'filename');
REPLACE INTO event_fields VALUES(16,'userdata1');
REPLACE INTO event_fields VALUES(17,'userdata2');
REPLACE INTO event_fields VALUES(18,'userdata3');
REPLACE INTO event_fields VALUES(19,'userdata4');
REPLACE INTO event_fields VALUES(20,'userdata5');
REPLACE INTO event_fields VALUES(21,'userdata6');
REPLACE INTO event_fields VALUES(22,'userdata7');
REPLACE INTO event_fields VALUES(23,'userdata8');
REPLACE INTO event_fields VALUES(24,'userdata9');

USE alienvault;
REPLACE INTO config (conf, value) VALUES ('last_update', '2012-10-30');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.1.0');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
