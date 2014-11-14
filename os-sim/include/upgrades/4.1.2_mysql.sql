USE alienvault;

-- New report modules: Security - IDM
REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(1026, 'IDM Users', 'Security', 'SIEM/IDMuser.php', 'Top Users:top:text:OSS_DIGIT:10:50;Top Events:topevents:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;DS Groups:plugin_groups:select:OSS_INPUT.OSS_NULLABLE:PLUGINGROUPS:', '', 30),
(1027, 'IDM Domains', 'Security', 'SIEM/IDMdomain.php', 'Top Domains:top:text:OSS_DIGIT:10:50;Top Events:topevents:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;DS Groups:plugin_groups:select:OSS_INPUT.OSS_NULLABLE:PLUGINGROUPS:', '', 30);

-- New report module: Security Events - Top Events by Entity
REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(197, 'Top Events by Entity', 'Security Events', 'SIEM/ByEntity.php', 'Source Database:source:select:OSS_ALPHA:EVENTSOURCE:;Entities:ENTITIES:multiselect:OSS_HEX.OSS_NULLABLE', '', 1);

-- New report module: Tickets - By Entity
REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(325, 'By Entity', 'Tickets', 'Tickets/ByEntity.php', 'Status:status:select:OSS_LETTER:All,Open,Assigned,Studying,Waiting,Testing,Closed;Entities:ENTITIES:multiselect:OSS_HEX', '', 1);

-- New report module: Tickets - By Type and Entity
REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(326, 'By Type and Entity', 'Tickets', 'Tickets/ByTypeEntity.php', 'Status:status:select:OSS_LETTER:All,Open,Assigned,Studying,Waiting,Testing,Closed;Types:TICKETTYPES:multiselect:OSS_ALPHA.OSS_SPACE.OSS_PUNC;Entities:ENTITIES:multiselect:OSS_HEX', '', 1);

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  -- KDB
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'datawarehouse' AND TABLE_NAME = 'report_data' AND COLUMN_NAME = 'cell_data')
  THEN
      -- For SIEM CSV import on custom views
      ALTER TABLE `datawarehouse`.`report_data` ADD `cell_data` TEXT NOT NULL;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

ALTER TABLE `host_agentless_entries` CHANGE `arguments` `arguments` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

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

-- Host-Network performance
CREATE TABLE IF NOT EXISTS `host_net_reference` (
  `host_id` binary(16) NOT NULL,
  `net_id` binary(16) NOT NULL,
  PRIMARY KEY (`host_id`,`net_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TRUNCATE TABLE host_net_reference;
REPLACE INTO host_net_reference SELECT host.id,net_id FROM host,host_ip,net_cidrs WHERE host.id=host_ip.host_id AND host_ip.ip>=net_cidrs.begin AND host_ip.ip<=net_cidrs.end;

-- Host software
DELETE FROM host_property_reference WHERE id=1;
CREATE TABLE IF NOT EXISTS `host_software` (
  `host_id` BINARY(16) NOT NULL ,
  `cpe` VARCHAR(255) NOT NULL ,
  `banner` TEXT NULL DEFAULT NULL ,
  `last_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
  `source_id` INT(11) NULL DEFAULT NULL ,
  `extra` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`host_id`, `cpe`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `software_cpe` (
  `cpe` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `version` VARCHAR(255) NOT NULL,
  `line` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`cpe`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;


REPLACE INTO `inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES
('Software', 'Software name is', 'fixed', 'SELECT DISTINCT s.name as software_value, s.name as software_text FROM host_software h,software_cpe s WHERE h.cpe=s.cpe', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host, host_software, software_cpe WHERE host.id = host_software.host_id AND host_software.cpe=software_cpe.cpe AND software_cpe.name=?', 2),
('Software', 'Software name is not', 'fixed', 'SELECT DISTINCT s.name as software_value, s.name as software_text FROM host_software h,software_cpe s WHERE h.cpe=s.cpe', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host WHERE id NOT IN (SELECT host.id FROM host, host_software, software_cpe WHERE host.id = host_software.host_id AND host_software.cpe=software_cpe.cpe AND software_cpe.name=?)', 4),
('Software', 'Software version is', 'fixed', 'SELECT DISTINCT s.version as software_value, s.version as software_text FROM host_software h,software_cpe s WHERE h.cpe=s.cpe', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host, host_software, software_cpe WHERE host.id = host_software.host_id AND host_software.cpe=software_cpe.cpe AND software_cpe.version=?', 6),
('Software', 'Software version is not', 'fixed', 'SELECT DISTINCT s.version as software_value, s.version as software_text FROM host_software h,software_cpe s WHERE h.cpe=s.cpe', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host WHERE id NOT IN (SELECT host.id FROM host, host_software, software_cpe WHERE host.id = host_software.host_id AND host_software.cpe=software_cpe.cpe AND software_cpe.version=?)', 8);

REPLACE INTO config (conf, value) VALUES ('last_update', '2012-12-20');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.1.2');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
