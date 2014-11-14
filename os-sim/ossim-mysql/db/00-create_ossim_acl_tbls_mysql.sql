--
-- Database`ossim_acl`
--

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

DROP TABLE IF EXISTS `acl`;
CREATE TABLE IF NOT EXISTS `acl` (
  `id` int(11) NOT NULL default '0',
  `section_value` varchar(230) NOT NULL default 'system',
  `allow` int(11) NOT NULL default '0',
  `enabled` int(11) NOT NULL default '0',
  `return_value` text,
  `note` text,
  `updated_date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `enabled_acl` (`enabled`),
  KEY `section_value_acl` (`section_value`),
  KEY `updated_date_acl` (`updated_date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `acl` (`id`, `section_value`, `allow`, `enabled`, `return_value`, `note`, `updated_date`) VALUES
(13, 'system', 1, 1, NULL, NULL, 1363559010);

DROP TABLE IF EXISTS `acl_sections`;
CREATE TABLE IF NOT EXISTS `acl_sections` (
  `id` int(11) NOT NULL default '0',
  `value` varchar(230) NOT NULL,
  `order_value` int(11) NOT NULL default '0',
  `name` varchar(230) NOT NULL,
  `hidden` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `value_acl_sections` (`value`),
  KEY `hidden_acl_sections` (`hidden`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `acl_sections` (`id`, `value`, `order_value`, `name`, `hidden`) VALUES
(1, 'system', 1, 'System', 0),
(2, 'user', 2, 'User', 0);

DROP TABLE IF EXISTS `acl_seq`;
CREATE TABLE IF NOT EXISTS `acl_seq` (
  `id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `acl_seq` (`id`) VALUES
(13);

DROP TABLE IF EXISTS `aco`;
CREATE TABLE IF NOT EXISTS `aco` (
  `id` int(11) NOT NULL default '0',
  `section_value` varchar(240) NOT NULL default '0',
  `value` varchar(240) NOT NULL,
  `order_value` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `hidden` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `section_value_value_aco` (`section_value`,`value`),
  KEY `hidden_aco` (`hidden`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aco` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES
(10, 'DomainAccess', 'All', 1, 'All', 0),
(11, 'DomainAccess', 'Login', 2, 'Login', 0),
(12, 'DomainAccess', 'Nets', 3, 'Nets', 0),
(13, 'DomainAccess', 'Sensors', 4, 'Sensors', 0),
(14, 'MainMenu', 'Index', 1, 'Index', 0),
(15, 'MenuControlPanel', 'ControlPanelExecutive', 1, 'ControlPanelExecutive', 0),
(16, 'MenuControlPanel', 'ControlPanelExecutiveEdit', 2, 'ControlPanelExecutiveEdit', 0),
(17, 'MenuControlPanel', 'ControlPanelMetrics', 3, 'ControlPanelMetrics', 0),
(18, 'MenuControlPanel', 'ControlPanelAlarms', 4, 'ControlPanelAlarms', 0),
(19, 'MenuControlPanel', 'ControlPanelEvents', 5, 'ControlPanelEvents', 0),
(20, 'MenuControlPanel', 'ControlPanelVulnerabilities', 6, 'ControlPanelVulnerabilities', 0),
(21, 'MenuControlPanel', 'ControlPanelAnomalies', 7, 'ControlPanelAnomalies', 0),
(22, 'MenuControlPanel', 'ControlPanelHids', 8, 'ControlPanelHids', 0),
(23, 'MenuIntelligence', 'PolicyPolicy', 1, 'PolicyPolicy', 0),
(24, 'MenuPolicy', 'PolicyHosts', 2, 'PolicyHosts', 0),
(25, 'MenuPolicy', 'PolicyNetworks', 3, 'PolicyNetworks', 0),
(26, 'MenuConfiguration', 'PolicySensors', 4, 'PolicySensors', 0),
(27, 'MenuPolicy', 'PolicySignatures', 5, 'PolicySignatures', 0),
(28, 'MenuPolicy', 'PolicyPorts', 6, 'PolicyPorts', 0),
(29, 'MenuIntelligence', 'PolicyActions', 7, 'PolicyActions', 0),
(30, 'MenuPolicy', 'PolicyResponses', 8, 'PolicyResponses', 0),
(31, 'MenuPolicy', 'PolicyPluginGroups', 9, 'PolicyPluginGroups', 0),
(32, 'MenuReports', 'ReportsHostReport', 1, 'ReportsHostReport', 0),
(33, 'MenuIncidents', 'ReportsAlarmReport', 2, 'ReportsAlarmReport', 0),
(34, 'MenuReports', 'ReportsSecurityReport', 3, 'ReportsSecurityReport', 0),
(35, 'MenuReports', 'ReportsPDFReport', 4, 'ReportsPDFReport', 0),
(36, 'MenuIncidents', 'IncidentsIncidents', 1, 'IncidentsIncidents', 0),
(37, 'MenuIncidents', 'IncidentsTypes', 2, 'IncidentsTypes', 0),
(38, 'MenuIncidents', 'IncidentsReport', 3, 'IncidentsReport', 0),
(39, 'MenuIncidents', 'IncidentsTags', 4, 'IncidentsTags', 0),
(40, 'MenuMonitors', 'MonitorsSession', 1, 'MonitorsSession', 0),
(41, 'MenuMonitors', 'MonitorsNetwork', 2, 'MonitorsNetwork', 0),
(42, 'MenuMonitors', 'MonitorsAvailability', 4, 'MonitorsAvailability', 0),
(43, 'MenuConfiguration', 'MonitorsSensors', 4, 'MonitorsSensors', 0),
(44, 'MenuControlPanel', 'MonitorsRiskmeter', 5, 'MonitorsRiskmeter', 0),
(45, 'MenuIntelligence', 'CorrelationDirectives', 1, 'CorrelationDirectives', 0),
(46, 'MenuIntelligence', 'CorrelationCrossCorrelation', 2, 'CorrelationCrossCorrelation', 0),
(47, 'MenuIntelligence', 'CorrelationBacklog', 3, 'CorrelationBacklog', 0),
(48, 'MenuConfiguration', 'ConfigurationMain', 1, 'ConfigurationMain', 0),
(49, 'MenuConfiguration', 'ConfigurationUsers', 2, 'ConfigurationUsers', 0),
(50, 'MenuConfiguration', 'ConfigurationPlugins', 3, 'ConfigurationPlugins', 0),
(51, 'MenuConfiguration', 'ConfigurationRRDConfig', 4, 'ConfigurationRRDConfig', 0),
(52, 'MenuConfiguration', 'ConfigurationHostScan', 5, 'ConfigurationHostScan', 0),
(53, 'MenuConfiguration', 'ConfigurationUserActionLog', 6, 'ConfigurationUserActionLog', 0),
(54, 'MenuIncidents', 'ConfigurationEmailTemplate', 6, 'ConfigurationEmailTemplate', 0),
-- (55, 'MenuConfiguration', 'ConfigurationUpgrade', 8, 'ConfigurationUpgrade', 0),
(56, 'MenuPolicy', 'ToolsScan', 1, 'ToolsScan', 0),
(57, 'MenuTools', 'ToolsRuleViewer', 2, 'ToolsRuleViewer', 0),
(58, 'MenuConfiguration', 'ToolsBackup', 3, 'ToolsBackup', 0),
(59, 'MenuConfiguration', 'ToolsUserLog', 4, 'ToolsUserLog', 0),
(60, 'MenuControlPanel', 'BusinessProcesses', 3, 'BusinessProcesses', 0),
(61, 'MenuControlPanel', 'BusinessProcessesEdit', 4, 'BusinessProcessesEdit', 0),
(62, 'MenuEvents', 'EventsForensics', 1, 'EventsForensics', 0),
(63, 'MenuEvents', 'EventsVulnerabilities', 2, 'EventsVulnerabilities', 0),
(64, 'MenuEvents', 'EventsAnomalies', 3, 'EventsAnomalies', 0),
(65, 'MenuEvents', 'EventsRT', 4, 'EventsRT', 0),
(66, 'MenuEvents', 'EventsViewer', 5, 'EventsViewer', 0),
(67, 'MenuConfiguration', 'PolicyServers', 5, 'PolicyServers', 0),
(68, 'MenuPolicy', 'ReportsOCSInventory', 5, 'ReportsOCSInventory', 0),
(69, 'MenuIncidents', 'Osvdb', 5, 'Osvdb', 0),
(70, 'MenuConfiguration', 'ConfigurationMaps', 9, 'ConfigurationMaps', 0),
(71, 'MenuConfiguration', 'ToolsDownloads', 5, 'ToolsDownloads', 0),
(72, 'MenuReports', 'ReportsGLPI', 5, 'ReportsGLPI', 0),
(73, 'MenuMonitors', 'MonitorsVServers', 4, 'MonitorsVServers', 0),
(74, 'MenuIncidents', 'ControlPanelAlarms', 1, 'ControlPanelAlarms', 0),
(75, 'MenuEvents', 'ControlPanelSEM', 1, 'ControlPanelSEM', 0),
(76, 'MenuEvents', 'ReportsWireless', 6, 'ReportsWireless', 0),
(77, 'MenuIntelligence', 'ComplianceMapping', 4, 'ComplianceMapping', 0),
(78, 'MenuPolicy', '5DSearch', 2, '5DSearch', 0),
(79, 'MenuReports', 'ReportsReportServer', 3, 'ReportsReportServer', 0),
(80, 'MenuMonitors', 'MonitorsNetflows', 2, 'MonitorsNetflows', 0),
(81, 'MenuReports', '5DSearch', 1, '5DSearch', 0),
(82, 'MenuConfiguration', 'PluginGroups', 0, 'PluginGroups', 0),
(83, 'MenuIncidents', 'IncidentsOpen', 0, 'IncidentsOpen', 0),
(84, 'MenuIncidents', 'IncidentsDelete', 0, 'IncidentsDelete', 0),
(85, 'MenuEvents', 'EventsForensicsDelete', 0, 'EventsForensicsDelete', 0),
(86, 'MenuEvents', 'EventsNids', 0, 'EventsNids', 0),
(87, 'MenuEvents', 'EventsHids', 0, 'EventsHids', 0),
(88, 'MenuConfiguration', 'NetworkDiscovery', 0, 'NetworkDiscovery', 0),
(89, 'MenuMonitors', 'TrafficCapture', 3, 'TrafficCapture', 0),
(90, 'MenuIncidents', 'ControlPanelAlarmsDelete', 2, 'ControlPanelAlarmsDelete', 0),
(91, 'MenuEvents', 'EventsVulnerabilitiesScan', 7, 'EventsVulnerabilitiesScan', 0),
(92, 'MenuEvents', 'EventsVulnerabilitiesDeleteScan', 8, 'EventsVulnerabilitiesDeleteScan', 0),
(93, 'MenuEvents', 'EventsHidsConfig', 11, 'EventsHidsConfig', 0),
(94, 'MenuMonitors', 'IPReputation', 5, 'IPReputation', 0);


DROP TABLE IF EXISTS `aco_map`;
CREATE TABLE IF NOT EXISTS `aco_map` (
  `acl_id` int(11) NOT NULL default '0',
  `section_value` varchar(230) NOT NULL default '0',
  `value` varchar(230) NOT NULL,
  PRIMARY KEY  (`acl_id`,`section_value`,`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aco_map` (`acl_id`, `section_value`, `value`) VALUES
(12, 'DomainAccess', 'All'),
(13, 'DomainAccess', 'All');

DROP TABLE IF EXISTS `aco_sections`;
CREATE TABLE IF NOT EXISTS `aco_sections` (
  `id` int(11) NOT NULL default '0',
  `value` varchar(230) NOT NULL,
  `order_value` int(11) NOT NULL default '0',
  `name` varchar(230) NOT NULL,
  `hidden` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `value_aco_sections` (`value`),
  KEY `hidden_aco_sections` (`hidden`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aco_sections` (`id`, `value`, `order_value`, `name`, `hidden`) VALUES
(10, 'DomainAccess', 1, 'DomainAccess', 0),
(11, 'MainMenu', 10, 'MainMenu', 0),
(12, 'MenuControlPanel', 11, 'MenuControlPanel', 0),
(13, 'MenuPolicy', 12, 'MenuPolicy', 0),
(14, 'MenuReports', 13, 'MenuReports', 0),
(15, 'MenuIncidents', 14, 'MenuIncidents', 0),
(16, 'MenuMonitors', 15, 'MenuMonitors', 0),
(17, 'MenuCorrelation', 16, 'MenuCorrelation', 0),
(18, 'MenuConfiguration', 17, 'MenuConfiguration', 0),
(19, 'MenuTools', 18, 'MenuTools', 0),
(23, 'MenuEvents', 12, 'MenuEvents', 0),
(149, 'MenuIntelligence', 17, 'MenuIntelligence', 0);

DROP TABLE IF EXISTS `aco_sections_seq`;
CREATE TABLE IF NOT EXISTS `aco_sections_seq` (
  `id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aco_sections_seq` (`id`) VALUES
(222);

DROP TABLE IF EXISTS `aco_seq`;
CREATE TABLE IF NOT EXISTS `aco_seq` (
  `id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aco_seq` (`id`) VALUES
(81);

DROP TABLE IF EXISTS `aro`;
CREATE TABLE IF NOT EXISTS `aro` (
  `id` int(11) NOT NULL default '0',
  `section_value` varchar(240) NOT NULL default '0',
  `value` varchar(240) NOT NULL,
  `order_value` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `hidden` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `section_value_value_aro` (`section_value`,`value`),
  KEY `hidden_aro` (`hidden`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aro` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES
(10, 'users', 'admin', 1, 'Admin', 0);

DROP TABLE IF EXISTS `aro_groups`;
CREATE TABLE IF NOT EXISTS `aro_groups` (
  `id` int(11) NOT NULL default '0',
  `parent_id` int(11) NOT NULL default '0',
  `lft` int(11) NOT NULL default '0',
  `rgt` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`,`value`),
  UNIQUE KEY `value_aro_groups` (`value`),
  KEY `parent_id_aro_groups` (`parent_id`),
  KEY `lft_rgt_aro_groups` (`lft`,`rgt`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aro_groups` (`id`, `parent_id`, `lft`, `rgt`, `name`, `value`) VALUES
(10, 0, 1, 4, 'OSSIM', 'ossim'),
(11, 10, 2, 3, 'Users', 'users');

DROP TABLE IF EXISTS `aro_groups_id_seq`;
CREATE TABLE IF NOT EXISTS `aro_groups_id_seq` (
  `id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aro_groups_id_seq` (`id`) VALUES
(17);

DROP TABLE IF EXISTS `aro_groups_map`;
CREATE TABLE IF NOT EXISTS `aro_groups_map` (
  `acl_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`acl_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `aro_map`;
CREATE TABLE IF NOT EXISTS `aro_map` (
  `acl_id` int(11) NOT NULL default '0',
  `section_value` varchar(230) NOT NULL default '0',
  `value` varchar(230) NOT NULL,
  PRIMARY KEY  (`acl_id`,`section_value`,`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aro_map` (`acl_id`, `section_value`, `value`) VALUES
(10, 'users', 'admin'),
(12, 'users', 'admin'),
(13, 'users', 'admin');

DROP TABLE IF EXISTS `aro_sections`;
CREATE TABLE IF NOT EXISTS `aro_sections` (
  `id` int(11) NOT NULL default '0',
  `value` varchar(230) NOT NULL,
  `order_value` int(11) NOT NULL default '0',
  `name` varchar(230) NOT NULL,
  `hidden` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `value_aro_sections` (`value`),
  KEY `hidden_aro_sections` (`hidden`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aro_sections` (`id`, `value`, `order_value`, `name`, `hidden`) VALUES
(10, 'users', 1, 'Users', 0);

DROP TABLE IF EXISTS `aro_sections_seq`;
CREATE TABLE IF NOT EXISTS `aro_sections_seq` (
  `id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aro_sections_seq` (`id`) VALUES
(13);

DROP TABLE IF EXISTS `aro_seq`;
CREATE TABLE IF NOT EXISTS `aro_seq` (
  `id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `aro_seq` (`id`) VALUES
(11);

DROP TABLE IF EXISTS `axo`;
CREATE TABLE IF NOT EXISTS `axo` (
  `id` int(11) NOT NULL default '0',
  `section_value` varchar(240) NOT NULL default '0',
  `value` varchar(240) NOT NULL,
  `order_value` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `hidden` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `section_value_value_axo` (`section_value`,`value`),
  KEY `hidden_axo` (`hidden`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `axo_groups`;
CREATE TABLE IF NOT EXISTS `axo_groups` (
  `id` int(11) NOT NULL default '0',
  `parent_id` int(11) NOT NULL default '0',
  `lft` int(11) NOT NULL default '0',
  `rgt` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`,`value`),
  UNIQUE KEY `value_axo_groups` (`value`),
  KEY `parent_id_axo_groups` (`parent_id`),
  KEY `lft_rgt_axo_groups` (`lft`,`rgt`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `axo_groups_map`;
CREATE TABLE IF NOT EXISTS `axo_groups_map` (
  `acl_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`acl_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `axo_map`;
CREATE TABLE IF NOT EXISTS `axo_map` (
  `acl_id` int(11) NOT NULL default '0',
  `section_value` varchar(230) NOT NULL default '0',
  `value` varchar(230) NOT NULL,
  PRIMARY KEY  (`acl_id`,`section_value`,`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `axo_sections`;
CREATE TABLE IF NOT EXISTS `axo_sections` (
  `id` int(11) NOT NULL default '0',
  `value` varchar(230) NOT NULL,
  `order_value` int(11) NOT NULL default '0',
  `name` varchar(230) NOT NULL,
  `hidden` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `value_axo_sections` (`value`),
  KEY `hidden_axo_sections` (`hidden`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `groups_aro_map`;
CREATE TABLE IF NOT EXISTS `groups_aro_map` (
  `group_id` int(11) NOT NULL default '0',
  `aro_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`group_id`,`aro_id`),
  KEY `aro_id` (`aro_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `groups_axo_map`;
CREATE TABLE IF NOT EXISTS `groups_axo_map` (
  `group_id` int(11) NOT NULL default '0',
  `axo_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`group_id`,`axo_id`),
  KEY `axo_id` (`axo_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `phpgacl`;
CREATE TABLE IF NOT EXISTS `phpgacl` (
  `name` varchar(230) NOT NULL,
  `value` varchar(230) NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `phpgacl` (`name`, `value`) VALUES
('version', '3.3.7'),
('schema_version', '2.1');

-- Dashboards
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =15;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =16;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =60;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =61;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =17;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =20;
UPDATE `ossim_acl`.`aco` SET `order_value` = '7' WHERE `aco`.`id` =21;
UPDATE `ossim_acl`.`aco` SET `order_value` = '8' WHERE `aco`.`id` =22;
UPDATE `ossim_acl`.`aco` SET `order_value` = '9' WHERE `aco`.`id` =44;
UPDATE `ossim_acl`.`aco` SET `order_value` = '10' WHERE `aco`.`id` =19;
UPDATE `ossim_acl`.`aco` SET `order_value` = '11' WHERE `aco`.`id` =18;

-- Incidents
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =74;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =90;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =33;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =36;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =83;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =84;
UPDATE `ossim_acl`.`aco` SET `order_value` = '7' WHERE `aco`.`id` =38;
UPDATE `ossim_acl`.`aco` SET `order_value` = '8' WHERE `aco`.`id` =37;
UPDATE `ossim_acl`.`aco` SET `order_value` = '9' WHERE `aco`.`id` =39;
UPDATE `ossim_acl`.`aco` SET `order_value` = '10' WHERE `aco`.`id` =54;
UPDATE `ossim_acl`.`aco` SET `order_value` = '11' WHERE `aco`.`id` =69;

-- Analysis
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id`=66;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id`=62;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id`=85;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id`=65;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id`=75;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id`=63;
UPDATE `ossim_acl`.`aco` SET `order_value` = '7' WHERE `aco`.`id`=91;
UPDATE `ossim_acl`.`aco` SET `order_value` = '8' WHERE `aco`.`id`=92;
UPDATE `ossim_acl`.`aco` SET `order_value` = '9' WHERE `aco`.`id`=86;
UPDATE `ossim_acl`.`aco` SET `order_value` = '10' WHERE `aco`.`id`=87;
UPDATE `ossim_acl`.`aco` SET `order_value` = '10' WHERE `aco`.`id`=93;
UPDATE `ossim_acl`.`aco` SET `order_value` = '12' WHERE `aco`.`id`=76;
UPDATE `ossim_acl`.`aco` SET `order_value` = '13' WHERE `aco`.`id`=64;

-- Reports
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =81;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =79;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =32;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =34;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =35;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =72;

-- Assets
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =78;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =24;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =25;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =28;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =68;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =56;
UPDATE `ossim_acl`.`aco` SET `order_value` = '7' WHERE `aco`.`id` =27;
UPDATE `ossim_acl`.`aco` SET `order_value` = '8' WHERE `aco`.`id` =30;
UPDATE `ossim_acl`.`aco` SET `order_value` = '9' WHERE `aco`.`id` =31;

-- Intelligence
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =23;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =29;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =45;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =47;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =77;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =46;

-- Situational Awareness
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =80;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =89;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =41;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =42;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =73;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =40;

-- Configuration
UPDATE `ossim_acl`.`aco` SET `order_value` = '1' WHERE `aco`.`id` =48;
UPDATE `ossim_acl`.`aco` SET `order_value` = '2' WHERE `aco`.`id` =49;
UPDATE `ossim_acl`.`aco` SET `order_value` = '3' WHERE `aco`.`id` =53;
UPDATE `ossim_acl`.`aco` SET `order_value` = '4' WHERE `aco`.`id` =26;
UPDATE `ossim_acl`.`aco` SET `order_value` = '5' WHERE `aco`.`id` =27;
UPDATE `ossim_acl`.`aco` SET `order_value` = '6' WHERE `aco`.`id` =50;
UPDATE `ossim_acl`.`aco` SET `order_value` = '7' WHERE `aco`.`id` =82;
UPDATE `ossim_acl`.`aco` SET `order_value` = '8' WHERE `aco`.`id` =71;
UPDATE `ossim_acl`.`aco` SET `order_value` = '9' WHERE `aco`.`id` =43;
UPDATE `ossim_acl`.`aco` SET `order_value` = '10' WHERE `aco`.`id` =59;
UPDATE `ossim_acl`.`aco` SET `order_value` = '11' WHERE `aco`.`id` =88;
UPDATE `ossim_acl`.`aco` SET `order_value` = '12' WHERE `aco`.`id` =51;
UPDATE `ossim_acl`.`aco` SET `order_value` = '13' WHERE `aco`.`id` =58;
UPDATE `ossim_acl`.`aco` SET `order_value` = '14' WHERE `aco`.`id` =52;
UPDATE `ossim_acl`.`aco` SET `order_value` = '15' WHERE `aco`.`id` =55;
UPDATE `ossim_acl`.`aco` SET `order_value` = '16' WHERE `aco`.`id` =70;
