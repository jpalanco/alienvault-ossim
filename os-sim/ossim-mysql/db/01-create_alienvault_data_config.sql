-- Data: MAIN --
--
INSERT IGNORE INTO config (conf, value) VALUES ('default_context_id', @default_ctx := UUID());
INSERT IGNORE INTO config (conf, value) VALUES ('default_engine_id', @default_engine := UUID());
-- Migration Pop-up --
INSERT IGNORE INTO config (conf, value) VALUES ('migration_pop_up', '1');
INSERT IGNORE INTO config (conf, value) VALUES ('migration_section_pop_up', '1');

INSERT IGNORE INTO acl_entities (id, name, admin_user, timezone, entity_type) VALUES (UNHEX(REPLACE(@default_ctx,'-','')), 'My Company', 'admin', 'UTC', 'context');
INSERT IGNORE INTO acl_entities (id, name, admin_user, timezone, entity_type) VALUES (UNHEX(REPLACE(@default_engine, '-', '')), 'Default Engine', 'admin', 'UTC', 'engine');

select UNHEX(REPLACE(UUID(),'-','')) into @tpl;
INSERT IGNORE INTO `acl_templates` (`id`, `name`) VALUES (@tpl, 'All Sections');
INSERT IGNORE INTO `acl_templates_perms` (`ac_templates_id`, `ac_perm_id`) VALUES (@tpl, 1),(@tpl, 3),(@tpl, 4),(@tpl, 9),(@tpl, 10),(@tpl, 11),(@tpl, 12),(@tpl, 14),(@tpl, 15),(@tpl, 17),(@tpl, 19),(@tpl, 22),(@tpl, 23),(@tpl, 24),(@tpl, 25),(@tpl, 28),(@tpl, 29),(@tpl, 31),(@tpl, 32),(@tpl, 35),(@tpl, 36),(@tpl, 39),(@tpl, 40),(@tpl, 42),(@tpl, 44),(@tpl, 48),(@tpl, 49),(@tpl, 51),(@tpl, 53),(@tpl, 55),(@tpl, 57),(@tpl, 60),(@tpl, 61),(@tpl, 63),(@tpl, 65),(@tpl, 66),(@tpl, 69),(@tpl, 70),(@tpl, 71),(@tpl, 72),(@tpl, 73),(@tpl, 74),(@tpl, 75),(@tpl, 76),(@tpl, 77),(@tpl, 79),(@tpl, 82),(@tpl, 83),(@tpl, 84),(@tpl, 85),(@tpl, 88);

INSERT IGNORE INTO corr_engine_contexts VALUES (UNHEX(REPLACE(@default_engine, '-', '')), UNHEX(REPLACE(@default_ctx,'-','')), 'Default');

INSERT IGNORE INTO port_group (ctx, name, descr) VALUES (UNHEX(REPLACE(@default_ctx,'-','')), 'ANY', 'Any port');
UPDATE port_group SET id=0 WHERE name='ANY';
INSERT IGNORE INTO port_group_reference (port_group_id, port_ctx, port_number, protocol_name) VALUES (0, UNHEX(REPLACE(@default_ctx,'-','')), 0, 'icmp'), (0, UNHEX(REPLACE(@default_ctx,'-','')), 0, 'tcp'), (0, UNHEX(REPLACE(@default_ctx,'-','')), 0, 'udp');

REPLACE INTO host_property_reference (`id`, `name`, `ord`, `description`) VALUES
-- (1, 'software', 3, 'Software'),
(2, 'cpu', 8, 'CPU'),
(3, 'operating-system', 1, 'Operating System'),
(4, 'workgroup', 6, 'Workgroup'),
(5, 'memory', 9, 'Memory'),
(6, 'department', 5, 'Department'),
(7, 'state', 7, 'Machine state'),
(8, 'username', 2, 'Users logged'),
(9, 'acl', 11, 'ACL'),
(10, 'route', 12, 'Route'),
(11, 'storage', 13, 'Storage'),
(12, 'role', 4, 'Role'),
(13, 'video', 10, 'Video'),
(14, 'model', 14, 'Model');


REPLACE INTO host_source_reference (`id`, `name`, `relevance`) VALUES
(0,'UNKNOWN', 1),
(1,'MANUAL-LOCKED', 10),
(2,'MANUAL',  3),
(3,'OCS',     8),
(4,'WMI',     7),
(5,'NMAP',    7),
(6,'PRADS',   4),
(7,'GVM',     5),
(8,'NTOP',    5),
(9,'LDAP',    8),
(10,'NAGIOS', 6),
(11,'NEDI',   5),
(12,'NESSUS', 5),
(13,'DHCP',5),
(14,'SNARE',  5),
(15,'ARPALERT', 5),
(16,'POf', 5),
(17,'PADS',5),
(18,'HIDS', 5);

REPLACE INTO device_types (`id`, `name`, `class`) VALUES
(1,'Server',0),
(2,'Endpoint',0),
(3,'Mobile',0),
(4,'Network Device',0),
(5,'Peripheral',0),
(6,'Industrial Device',0),
(7,'Security Device',0),
(8,'Media Device',0),
(9,'General Purpose',0),
(10,'Medical Device',0),
(100,'HTTP Server',1),
(101,'Mail Server',1),
(102,'Domain Controller',1),
(103,'DNS Server',1),
(104,'File Server',1),
(105,'Proxy Server',1),
(106,'PBX',1),
(107,'Print Server',1),
(108,'Terminal Server',1),
(109,'VoIP Adapter',1),
(110,'Active Directory Server / Domain Controller',1),
(111,'Web Application Firewall',1),
(112,'Firewall',1),
(113,'IDS/IPS',1),
(114,'DDOS Protection',1),
(115,'Anti-Virus',1),
(116,'Network Defense (Other)',1),
(117,'Time Server',1),
(118,'Monitoring Tools',1),
(119,'Database Server',1),
(120,'VPN Gateway',1),
(121,'Workstation',1),
(122,'Application Server (Generic)',1),
(123,'Virtual Host',1),
(124,'Payment Server (ACI in particular)',1),
(125,'Point of Sale Controller',1),
(126,'Server (Other)',1),
(127,'Web Server',1),
(128,'DMZ Server',1),
(129,'Internal Server',1),
(130,'Backup Server',1),
(131,'DHCP Server',1),
(200,'Laptop',2),
(201,'Endpoint (Other)',2),
(202,'Workstation',2),
(301,'Cell Phone',3),
(302,'Tablet',3),
(304,'PDA',3),
(305,'VoIP Phone',3),
(401,'Router',4),
(402,'Switch',4),
(403,'VPN device',4),
(404,'Wireless AP',4),
(405,'Bridge',4),
(406,'Broadband Router',4),
(407,'Remote Management',4),
(408,'Storage',4),
(409,'Hub',4),
(410,'Load Balancer',4),
(411,'Firewall',4),
(501,'Printer',5),
(502,'Camera',5),
(503,'Terminal',5),
(504,'Uninterrupted Power Supply (UPS)',5),
(505,'Power Distribution Unit (PDU)',5),
(506,'Environmental Monitoring',5),
(507,'Peripheral (Other)',5),
(508,'IPMI',5),
(509,'RAID',5),
(601,'PLC',6),
(702,'Intrusion Detection System (IDS)',7),
(703,'Intrusion Prevention System (IPS)',7),
(801,'Game Console',8),
(802,'Television',8),
(803,'Set Top Box',8),
(804,'IoT Device (Other)',8),
(1001,'Other',10),
(1002,'High Priority',10);




REPLACE INTO asset_filter_types (`id`, `filter`, `type`) VALUES
(1, 'asset_created', 'range'),
(2, 'asset_updated', 'range'),
(3, 'alarms', 'value'),
(4, 'events', 'value'),
(5, 'vulnerabilities', 'range'),
(6, 'asset_value', 'range'),
(7, 'network', 'list'),
(8, 'device_type', 'range_list'),
(9, 'software', 'list'),
(10, 'port_service', 'range_list'),
(11, 'ip', 'range_list'),
(12, 'fqdn', 'list'),
(13, 'location', 'list'),
(14, 'sensor', 'list'),
(15, 'user', 'list'),
(16, 'hostname', 'list'),
(17, 'availability', 'value'),
(18, 'groups', 'list'),
(19, 'labels', 'list'),
(20, 'os', 'list'),
(21, 'model', 'list'),
(22, 'group_name', 'list'),
(23, 'network_name', 'list'),
(24, 'network_cidr', 'list'),
(25, 'plugin', 'list'),
(26, 'hids', 'value');


INSERT IGNORE INTO action_type (type, name, descr) VALUES (1, "email", "Send an email message");
INSERT IGNORE INTO action_type (type, name, descr) VALUES (2, "exec", "Execute an external program");
INSERT IGNORE INTO action_type (type, name, descr) VALUES (3, "ticket", "Open a ticket");
INSERT IGNORE INTO users (login, name, pass, uuid) VALUES ('admin', 'AlienVault admin', '21232f297a57a5a743894a0e4a801fc3', UNHEX(REPLACE(UUID(), '-', '')));
INSERT IGNORE INTO log_action (login, ipfrom, date, code, info) VALUES ('admin', '127.0.0.1', current_timestamp , 4, 'Configuration - User admin created');
TRUNCATE incident_type;
INSERT IGNORE INTO `incident_type` (`id`, `descr`, `keywords`) VALUES
('Anomalies', '', ''),
('Application and System Failures', '', ''),
('Corporative Net Attack', '', ''),
('Expansion Virus', '', ''),
('Generic', '', ''),
('Vulnerability', '', ''),
('Net Performance', '', ''),
('Policy Violation', '', ''),
('Security Weakness', '', '');
INSERT IGNORE INTO incident_vulns_seq VALUES(0);
INSERT IGNORE INTO incident_tag_descr_seq VALUES (0);
INSERT IGNORE INTO incident_tag_descr VALUES (65001,'AlienVault_INTERNAL_PENDING','Vulnerability scanner pending tag - Prevents this event from being detected until tag is unset','av_tag_1');
INSERT IGNORE INTO incident_tag_descr VALUES (65002,'AlienVault_INTERNAL_FALSE_POSITIVE','Vulnerability scanner false positive tag - Prevents this event from being detected in the future','av_tag_1');
INSERT IGNORE INTO plugin_scheduler_seq (id) VALUES (0);

INSERT IGNORE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(1, 'MENU', 'dashboard-menu', 'ControlPanelExecutive', 'Dashboard -> Overview', 1, 1, 1, '01.01'),
(3, 'MENU', 'dashboard-menu', 'ControlPanelExecutiveEdit', 'Dashboard -> Overview -> Manage Dashboards', 1, 1, 1, '01.02'),
(4, 'MENU', 'dashboard-menu', 'ControlPanelMetrics', 'Dashboard -> Overview -> Metrics', 1, 1, 1, '01.03'),
(9, 'MENU', 'configuration-menu', 'PolicyPolicy', 'Configuration -> Threat Intelligence -> Policy', 1, 1, 1, '05.08'),
(10, 'MENU', 'environment-menu', 'PolicyHosts', 'Environment -> Assets & Groups -> Assets / Asset Groups', 1, 0, 1, '03.01'),
(11, 'MENU', 'environment-menu', 'PolicyNetworks', 'Environment -> Assets & Groups -> Networks & Network Groups', 0, 1, 1, '03.02'),
(12, 'MENU', 'configuration-menu', 'PolicySensors', 'Configuration -> Deployment -> Components -> Sensors', 1, 0, 1, '05.06'),
(14, 'MENU', 'configuration-menu', 'PolicyPorts', 'Configuration -> Threat Intelligence -> Ports/Port Groups', 0, 0, 1, '05.10'),
(15, 'MENU', 'configuration-menu', 'PolicyActions', 'Configuration -> Threat Intelligence -> Actions', 0, 0, 1, '05.09'),
(17, 'MENU', 'configuration-menu', 'PluginGroups', 'Configuration -> Threat Intelligence -> Data Source -> Manage Data Source Groups', 0, 0, 1, '05.15'),
(19, 'MENU', 'analysis-menu', 'ReportsAlarmReport', 'Analysis -> Alarms -> Reports', 1, 0, 1, '02.06'),
(22, 'MENU', 'analysis-menu', 'IncidentsIncidents', 'Analysis -> Tickets', 1, 0, 1, '02.08'),
(23, 'MENU', 'analysis-menu', 'IncidentsTypes', 'Analysis -> Tickets -> Manage Ticket Types', 0, 0, 1, '02.11'),
(24, 'MENU', 'analysis-menu', 'IncidentsReport', 'Analysis -> Tickets -> Reports', 1, 0, 1, '02.14'),
(25, 'MENU', 'analysis-menu', 'IncidentsTags', 'Analysis -> Tickets -> Manage Ticket Tags', 0, 0, 1, '02.12'),
(28, 'MENU', 'environment-menu', 'MonitorsAvailability', 'Environment -> Availability', 0, 0, 1, '03.13'),
(29, 'MENU', 'configuration-menu', 'ASEC', 'Configuration -> Deployment -> Smart Event Collection', 1, 0, 1, '05.05'),
(31, 'MENU', 'configuration-menu', 'CorrelationDirectives', 'Configuration -> Threat Intelligence -> Directives', 1, 1, 1, '05.11'),
(32, 'MENU', 'configuration-menu', 'CorrelationCrossCorrelation', 'Configuration -> Threat Intelligence -> Cross Correlation', 0, 0, 1, '05.13'),
(35, 'MENU', 'configuration-menu', 'ConfigurationUsers', 'Configuration -> Administration -> Users', 0, 0, 1, '05.01'),
(36, 'MENU', 'configuration-menu', 'ConfigurationPlugins', 'Configuration -> Threat Intelligence -> Data Source', 0, 0, 1, '05.14'),
(39, 'MENU', 'configuration-menu', 'ConfigurationUserActionLog', 'Configuration -> Administration -> Users -> Activity', 0, 0, 1, '05.02'),
(40, 'MENU', 'analysis-menu', 'ConfigurationEmailTemplate', 'Analysis -> Tickets -> Manage Email Templates', 0, 0, 1, '02.13'),
(42, 'MENU', 'environment-menu', 'ToolsScan', 'Environment -> Assets & Groups -> Assets -> Discover New Assets', 0, 1, 1, '03.04'),
(44, 'MENU', 'configuration-menu', 'ToolsBackup', 'Configuration -> Administration -> Backup', 0, 0, 1, '05.03'),
(48, 'MENU', 'analysis-menu', 'EventsForensics', 'Analysis -> Security Events (SIEM)', 1, 1, 1, '02.01'),
(49, 'MENU', 'environment-menu', 'EventsVulnerabilities', 'Environment -> Vulnerabilities', 1, 1, 1, '03.07'),
(51, 'MENU', 'analysis-menu', 'EventsRT', 'Analysis -> Security Events (SIEM) -> Real Time', 1, 0, 1, '02.02'),
(53, 'MENU', 'configuration-menu', 'PolicyServers', 'Configuration -> Deployment -> Components -> Servers', 0, 0, 1, '05.07'),
(55, 'MENU', 'configuration-menu', 'Osvdb', 'Configuration -> Threat Intelligence -> Knowledgebase', 0, 0, 1, '05.16'),
(57, 'MENU', 'support-menu', 'ToolsDownloads', 'Support -> Downloads', 0, 0, 1, '07.01'),
(60, 'MENU', 'analysis-menu', 'ControlPanelAlarms', 'Analysis -> Alarms', 1, 0, 1, '02.04'),
(61, 'MENU', 'analysis-menu', 'ControlPanelSEM', 'Analysis -> Raw Logs', 1, 0, 1, '02.07'),
(63, 'MENU', 'configuration-menu', 'ComplianceMapping', 'Configuration -> Threat Intelligence -> Compliance Mapping', 0, 0, 1, '05.12'),
(65, 'MENU', 'report-menu', 'ReportsReportServer', 'Reports -> View Reports', 0, 0, 1, '04.01'),
(66, 'MENU', 'environment-menu', 'MonitorsNetflows', 'Environment -> Netflows', 1, 1, 1, '03.11'),
(69, 'MENU', 'settings-menu', 'ToolsUserLog', 'Settings -> User Activity', 0, 0, 1, '06.01'),
(70, 'MENU', 'analysis-menu', 'ControlPanelAlarmsDelete', 'Analysis -> Alarms -> Delete Alarms', 0, 0, 1, '02.05'),
(71, 'MENU', 'analysis-menu', 'EventsForensicsDelete', 'Analysis -> Security Events (SIEM) -> Delete Events', 0, 0, 1, '02.03'),
(72, 'MENU', 'environment-menu', 'EventsVulnerabilitiesScan', 'Environment -> Vulnerabilities -> Scan/Import', 1, 1, 1, '03.08'),
(73, 'MENU', 'environment-menu', 'EventsVulnerabilitiesDeleteScan', 'Environment -> Vulnerabilities -> Delete Scan Report', 1, 1, 1, '03.09'),
(74, 'MENU', 'report-menu', 'ReportsCreateCustom', 'Reports -> Create Reports', 1, 1, 1, '04.03'),
(75, 'MENU', 'report-menu', 'ReportsScheduler', 'Reports -> Scheduler Reports', 0, 0, 1, '04.04'),
(76, 'MENU', 'analysis-menu', 'IncidentsOpen', 'Analysis -> Tickets -> Open Tickets', 0, 0, 1, '02.09'),
(77, 'MENU', 'analysis-menu', 'IncidentsDelete', 'Analysis -> Tickets -> Close/Delete Tickets', 0, 0, 1, '02.10'),
(79, 'MENU', 'environment-menu', 'EventsHids', 'Environment -> Detection -> HIDS', 1, 0, 1, '03.14'),
(82, 'MENU', 'environment-menu', 'EventsHidsConfig', 'Environment -> Detection -> HIDS -> Manage HIDS', 1, 0, 1, '03.15'),
(83, 'MENU', 'environment-menu', 'TrafficCapture', 'Environment -> Traffic Capture', 1, 0, 1, '03.12'),
(84, 'MENU', 'dashboard-menu', 'IPReputation', 'Dashboard -> Open Threat Exchange, Configuration -> Open Threat Exchange', 0, 0, 1, '01.06'),
(85, 'MENU', 'environment-menu', 'AlienVaultInventory', 'Environment -> Assets & Groups -> Schedule Scan', 0, 0, 1, '03.03'),
(86, 'MENU', 'configuration-menu', 'AlienVaultInventory', 'Configuration -> Deployment -> Scheduler', 0, 0, 1, '05.04'),
(87, 'MENU', 'message_center-menu', 'MessageCenterDelete', 'MessageCenter',0,0,1,'13.01'),
(88, 'MENU','report-menu','ReportsClone','Reports -> Clone Reports',1,1,1,'04.05'),
(89, 'MENU','analysis-menu','ControlPanelAlarmsClose','Analysis -> Alarms -> Close Alarms',1,1,1,'02.15');

INSERT IGNORE INTO credential_type(name) VALUES ("SSH");
INSERT IGNORE INTO credential_type(name) VALUES ("Windows");
INSERT IGNORE INTO credential_type(name) VALUES ("AD");

INSERT IGNORE INTO `tag` VALUES
(0xA3100000000000000000000000000000,'\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','Analysis in Progress','alarm','av_tag_6'),
(0xA3200000000000000000000000000000,'\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','False Positive','alarm','av_tag_1');

INSERT IGNORE INTO control_panel VALUES ('global_admin','global','day',0,0,FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y-%m-%d %H:%i:%s'),FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y-%m-%d %H:%i:%s'),100,100);

--
-- Default Networks
--
SELECT UNHEX(REPLACE(UUID(),'-','')) into @uuid;
INSERT IGNORE INTO net (id,ctx,name,ips,asset,threshold_c,threshold_a,alert,persistence,rrd_profile,descr) VALUES (@uuid,UNHEX(REPLACE(@default_ctx,'-','')),'Pvt_192','192.168.0.0/16','2','300','300','0','0','NULL','');
INSERT IGNORE INTO net_qualification (net_id,compromise,attack) VALUES (@uuid,2,2);
INSERT IGNORE INTO net_cidrs (net_id,cidr,begin,end) VALUES (@uuid,'192.168.0.0/16',0xC0A80000,0xC0A8FFFF);
SELECT UNHEX(REPLACE(UUID(),'-','')) into @uuid;
INSERT IGNORE INTO net (id,ctx,name,ips,asset,threshold_c,threshold_a,alert,persistence,rrd_profile,descr) VALUES (@uuid,UNHEX(REPLACE(@default_ctx,'-','')),'Pvt_172','172.16.0.0/12','2','300','300','0','0','NULL','');
INSERT IGNORE INTO net_qualification (net_id,compromise,attack) VALUES (@uuid,2,2);
INSERT IGNORE INTO net_cidrs (net_id,cidr,begin,end) VALUES (@uuid,'172.16.0.0/12',0xAC100000,0xAC1FFFFF);
SELECT UNHEX(REPLACE(UUID(),'-','')) into @uuid;
INSERT IGNORE INTO net (id,ctx,name,ips,asset,threshold_c,threshold_a,alert,persistence,rrd_profile,descr) VALUES (@uuid,UNHEX(REPLACE(@default_ctx,'-','')),'Pvt_010','10.0.0.0/8','2','300','300','0','0','NULL','');
INSERT IGNORE INTO net_qualification (net_id,compromise,attack) VALUES (@uuid,2,2);
INSERT IGNORE INTO net_cidrs (net_id,cidr,begin,end) VALUES (@uuid,'10.0.0.0/8',0x0A000000,0x0AFFFFFF);

REPLACE INTO alienvault.host_net_reference SELECT host.id,net_id FROM alienvault.host, alienvault.host_ip, alienvault.net_cidrs WHERE host.id = host_ip.host_id AND host_ip.ip >= net_cidrs.begin AND host_ip.ip <= net_cidrs.end;

--
-- Data: Config
--
INSERT IGNORE INTO config (conf, value) VALUES ('snort_path', '/etc/snort/');
INSERT IGNORE INTO config (conf, value) VALUES ('snort_rules_path', '/etc/snort/rules/');
INSERT IGNORE INTO config (conf, value) VALUES ('snort_type', 'mysql');
INSERT IGNORE INTO config (conf, value) VALUES ('snort_base', 'alienvault_siem');
INSERT IGNORE INTO config (conf, value) VALUES ('snort_user', 'root');
INSERT IGNORE INTO config (conf, value) VALUES ('snort_pass', 'ossim');
INSERT IGNORE INTO config (conf, value) VALUES ('snort_host', 'localhost');
INSERT IGNORE INTO config (conf, value) VALUES ('snort_port', '3306');
INSERT IGNORE INTO config (conf, value) VALUES ('locale_dir', '/usr/share/locale');
INSERT IGNORE INTO config (conf, value) VALUES ('language', 'en_GB');
INSERT IGNORE INTO config (conf, value) VALUES ('server_address', 'localhost');
INSERT IGNORE INTO config (conf, value) VALUES ('server_port', '40001');
INSERT IGNORE INTO config (conf, value) VALUES ('server_correlate', 'yes');
INSERT IGNORE INTO config (conf, value) VALUES ('server_cross_correlate', 'yes');
INSERT IGNORE INTO config (conf, value) VALUES ('server_logger_if_priority', '0');
INSERT IGNORE INTO config (conf, value) VALUES ('server_qualify', 'yes');
INSERT IGNORE INTO config (conf, value) VALUES ('server_store', 'yes');
INSERT IGNORE INTO config (conf, value) VALUES ('server_sim', 'yes');
INSERT IGNORE INTO config (conf, value) VALUES ('server_sem', 'yes');
INSERT IGNORE INTO config (conf, value) VALUES ('server_sign', 'no');
INSERT IGNORE INTO config (conf, value) VALUES ('server_forward_alarm', 'yes');
INSERT IGNORE INTO config (conf, value) VALUES ('server_forward_event', 'yes');
INSERT IGNORE INTO config (conf, value) VALUES ('copy_siem_events', 'no');
INSERT IGNORE INTO config (conf, value) VALUES ('server_alarms_to_syslog', 'no');
INSERT IGNORE INTO config (conf, value) VALUES ('graph_link', '../report/graphs/draw_rrd.php');
INSERT IGNORE INTO config (conf, value) VALUES ('ossim_link', '/ossim/');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_type', 'mysql');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_base', 'alienvault_siem');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_user', 'root');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_pass', 'ossim');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_host', 'localhost');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_port', '3306');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_store', '1');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_dir', '/var/lib/ossim/backup');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_day', '5');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_events', '4000000');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_hour', '01:00');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_netflow', '45');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_conf_pass', '');
INSERT IGNORE INTO config (conf, value) VALUES ('backup_events_min_free_disk_space', 10);
INSERT IGNORE INTO config (conf, value) VALUES ('gvm_path', '/usr/bin/gvm-cli');
INSERT IGNORE INTO config (conf, value) VALUES ('gvm_host', 'localhost');
INSERT IGNORE INTO config (conf, value) VALUES ('gvm_rpt_path', '/usr/share/ossim/www/vulnmeter/');
INSERT IGNORE INTO config (conf, value) VALUES ('gvm_src_path', '/usr/share/ossim/www/vulnmeter/tmp/.gvmsrc');
INSERT IGNORE INTO config (conf, value) VALUES ('gvm_pre_scan_locally', '1');
INSERT IGNORE INTO config (conf, value) VALUES ('ossim_web_user', '');
INSERT IGNORE INTO config (conf, value) VALUES ('ossim_web_pass', '');
INSERT IGNORE INTO config (conf, value) VALUES ('jpgraph_path', '/usr/share/ossim/www/graphs/jpgraph/');
INSERT IGNORE INTO config (conf, value) VALUES ('adodb_path', '/usr/share/adodb/');
INSERT IGNORE INTO config (conf, value) VALUES ('rrdtool_path', '/usr/bin/');
INSERT IGNORE INTO config (conf, value) VALUES ('rrdpath_stats', '/var/lib/ossim/rrd/event_stats/');
INSERT IGNORE INTO config (conf, value) VALUES ('font_path', '/usr/share/fonts/truetype/ttf-bitstream-vera/Vera.ttf');
INSERT IGNORE INTO config (conf, value) VALUES ('nagios_link', '/nagios/');
INSERT IGNORE INTO config (conf, value) VALUES ('acid_link', '/ossim/forensics/');
INSERT IGNORE INTO config (conf, value) VALUES ('acid_path', '/usr/share/ossim/www/forensics/');
INSERT IGNORE INTO config (conf, value) VALUES ('nmap_path', '/usr/bin/nmap');
INSERT IGNORE INTO config (conf, value) VALUES ('report_graph_type', 'images');
INSERT IGNORE INTO config (conf, value) VALUES ('def_asset', '2');
INSERT IGNORE INTO config (conf, value) VALUES ('event_viewer', 'base');
INSERT IGNORE INTO config (conf, value) VALUES ('user_action_log', '1');
INSERT IGNORE INTO config (conf, value) VALUES ('log_syslog', '0');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_address', '127.0.0.1');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_port', '40003');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_dir', '/usr/share/ossim-framework/ossimframework');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_listener', '1');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_donagios','1');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_scheduler', '1');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_nagios_mkl_period', '300');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_backup_days_lifetime', '30');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_nagiosmklivemanager', '1');
INSERT IGNORE INTO config (conf, value) VALUES ('email_subject_template', '');
INSERT IGNORE INTO config (conf, value) VALUES ('email_body_template', '');
INSERT IGNORE INTO config (conf, value) VALUES ('panel_plugins_dir', '');
INSERT IGNORE INTO config (conf, value) VALUES ('panel_configs_dir', '/etc/ossim/framework/panel/configs');
INSERT IGNORE INTO config (conf, value) VALUES ('max_event_tmp', '10000');
INSERT IGNORE INTO config (conf, value) VALUES ('vulnerability_incident_threshold', '2');
INSERT IGNORE INTO config (conf, value) VALUES ('login_enable_ldap', "no");
INSERT IGNORE INTO config (conf, value) VALUES ('login_enforce_existing_user', "yes");
INSERT IGNORE INTO config (conf, value) VALUES ('login_ldap_server', "127.0.0.1");
INSERT IGNORE INTO config (conf, value) VALUES ('login_ldap_o', "o=company");
INSERT IGNORE INTO config (conf, value) VALUES ('login_ldap_cn', "cn");
INSERT IGNORE INTO config (conf, value) VALUES ('login_ldap_ou', "ou=people");
INSERT IGNORE INTO config (conf, value) VALUES ('pass_expire', '0');
INSERT IGNORE INTO config (conf, value) VALUES ('ocs_link','/ossim/ocsreports/index.php?lang=english');
INSERT IGNORE INTO config (conf, value) VALUES ('update_checks_enable','yes');
INSERT IGNORE INTO config (conf, value) VALUES ('update_checks_use_proxy','no');
INSERT IGNORE INTO config (conf, value) VALUES ('proxy_url','');
INSERT IGNORE INTO config (conf, value) VALUES ('proxy_user','');
INSERT IGNORE INTO config (conf, value) VALUES ('proxy_password','');
INSERT IGNORE INTO config (conf, value) VALUES ('update_checks_source','http://data.alienvault.com/updates/update_log.txt');
INSERT IGNORE INTO config (conf, value) VALUES ('update_checks_pro_source','http://data.alienvault.com/updates/update_log_pro.txt');
INSERT IGNORE INTO config (conf, value) VALUES ('repository_upload_dir', "/usr/share/ossim/uploads");
INSERT IGNORE INTO config (conf, value) VALUES ('first_login', 'yes');
INSERT IGNORE INTO config (conf, value) VALUES ('alarms_generate_incidents', 'no');
INSERT IGNORE INTO config (conf, value) VALUES ('incidents_incharge_default', 'admin');
INSERT IGNORE INTO config (conf, value) VALUES ('smtp_server_address', '127.0.0.1');
INSERT IGNORE INTO config (conf, value) VALUES ('smtp_port', '25');
INSERT IGNORE INTO config (conf, value) VALUES ('smtp_user', '');
INSERT IGNORE INTO config (conf, value) VALUES ('smtp_pass', '');
INSERT IGNORE INTO config (conf, value) VALUES ('use_ssl','no');

INSERT IGNORE INTO config (conf, value) VALUES ('nagios_cfgs','/etc/nagios3/conf.d/ossim-configs/');
INSERT IGNORE INTO config (conf, value) VALUES ('nagios_reload_cmd','/etc/init.d/nagios3 reload || { /etc/init.d/nagios3 stop;/etc/init.d/nagios3 start; }');
INSERT IGNORE INTO config (conf, value) VALUES ('snmp_comm','');
INSERT IGNORE INTO config (conf, value) VALUES ('server_resend_alarm','1');
INSERT IGNORE INTO config (conf, value) VALUES ('server_resend_event','1');

INSERT IGNORE INTO config (conf , value) VALUES ('server_remote_logger', 'no');
INSERT IGNORE INTO config (conf , value) VALUES ('server_remote_logger_user', '');
INSERT IGNORE INTO config (conf , value) VALUES ('server_remote_logger_pass', '');
INSERT IGNORE INTO config (conf , value) VALUES ('server_remote_logger_ossim_url', '');

INSERT IGNORE INTO config (conf,  value) VALUES ('network_auto_discovery', '0');
INSERT IGNORE INTO config (conf , value) VALUES ('nedi_autodiscovery', '0');

INSERT IGNORE INTO config (conf , value) VALUES ('tickets_send_mail', 'no');
INSERT IGNORE INTO config (conf , value) VALUES ('tickets_max_days', '15');

INSERT IGNORE INTO config (conf , value) VALUES ('session_timeout', '15');
INSERT IGNORE INTO config (conf , value) VALUES ('unlock_user_interval', '5');
INSERT IGNORE INTO config (conf , value) VALUES ('failed_retries', '5');
INSERT IGNORE INTO config (conf , value) VALUES ('pass_complex', 'no');
INSERT IGNORE INTO config (conf , value) VALUES ('pass_length_min', '7');
INSERT IGNORE INTO config (conf , value) VALUES ('pass_length_max', '32');
INSERT IGNORE INTO config (conf , value) VALUES ('pass_expire_min', '0');
INSERT IGNORE INTO config (conf , value) VALUES ('pass_history', '0');

INSERT IGNORE INTO config (conf, value) VALUES ('solera_enable', '0');

INSERT IGNORE INTO `config` (`conf`, `value`) VALUES
('customize_send_logs', NULL),
('customize_title_background_color', '#8CC221'),
('customize_title_foreground_color', '#000000'),
('customize_subtitle_background_color', '#7A7A7A'),
('customize_subtitle_foreground_color', '#FFFFFF'),
('customize_wizard', '0');
INSERT IGNORE INTO config (conf, value) VALUES ('alarms_lifetime', '90');
INSERT IGNORE INTO config (conf, value) VALUES ('alarms_expire', 'yes');
INSERT IGNORE INTO config (conf, value) VALUES ('logger_storage_days_lifetime', '0');
INSERT IGNORE INTO config (conf, value) VALUES ('logger_expire', 'no');
INSERT IGNORE INTO config (conf, value) VALUES ('enable_idm', '0');
INSERT IGNORE INTO config (conf, value) VALUES ('idm_user_login_timeout', '24');
INSERT IGNORE INTO config (conf, value) VALUES ('server_reputation', 'no');
INSERT IGNORE INTO config (conf, value) VALUES ('storage_type', '3');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_keyfile', '/etc/ossim/framework/db_encryption_key');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_notificationfile', '/var/log/ossim/framework-notifications.log');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_log_dir', '/var/log/ossim/');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_rrd_bin', '/usr/bin/rrdtool');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_rdd_period', '300');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_scheduled_period', '300');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_backup_period', '300');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_backup_dir', '/etc/ossim/framework/backups/');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_nfsen_config_dir', '/etc/nfsen/nfsen.conf');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_nfsen_monit_config_dir', '/etc/monit/alienvault/nfcapd.monitrc');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_nagios_sock_path', '/var/lib/nagios3/rw/live');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_usehttps', '0');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_backup_storage_days_lifetime', 5);
INSERT IGNORE INTO config (conf, value) VALUES
('tcp_max_download',0),
('tcp_max_upload',0),
('udp_max_download',0),
('udp_max_upload',0),
('agg_function',0),
('inspection_window',0);


INSERT IGNORE INTO config (conf, value) VALUES ('internet_connection', 1);

INSERT IGNORE INTO config (conf, value) VALUES ('event_cache_limit', '102400');

INSERT IGNORE INTO config (conf, value) VALUES ('google_maps_key', 'AIzaSyBbMaRbr5jy9HbAf2TGIp4A2mnIKGk4XQ4');

INSERT IGNORE INTO config (conf, value) VALUES ('close_vuln_tickets_automatically', '1');

INSERT IGNORE INTO config (conf, value) VALUES ('hids_update_rate', '60');

INSERT IGNORE INTO config (conf, value) VALUES ('default_sender_email_address', 'no-reply@alienvault.com');



REPLACE INTO config (conf, value) VALUES ('last_update', '2022-05-10');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.8.11');
