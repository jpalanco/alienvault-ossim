USE alienvault;
SET AUTOCOMMIT=0;

UPDATE `dashboard_widget_config` SET `height`=240 WHERE `height`=300;
UPDATE `dashboard_widget_config` SET `height`=320 WHERE `height`=400;
UPDATE `dashboard_widget_config` SET `height`=560 WHERE `height`=700 || `height`=702;

UPDATE `dashboard_tab_config` SET `layout`=3 WHERE id IN(1,2,3,4,5,9);

/* Executive Panel */
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=0 WHERE `id`=1;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=0 WHERE `id`=2;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=1 WHERE `id`=3;
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=1 WHERE `id`=4;
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=0 WHERE `id`=5;
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=1 WHERE `id`=6;
/* Ticket Panel */
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=0 WHERE `id`=7;
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=0 WHERE `id`=8;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=1 WHERE `id`=9;
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=1 WHERE `id`=10;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=0 WHERE `id`=11;
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=1 WHERE `id`=12;
/* Security Panel */
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=2 WHERE `id`=13;
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=2 WHERE `id`=14;
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=0 WHERE `id`=15;
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=1 WHERE `id`=16;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=0 WHERE `id`=17;
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=0 WHERE `id`=18;
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=1 WHERE `id`=19;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=1 WHERE `id`=20;

UPDATE `dashboard_widget_config` SET `height`=240 WHERE `id`=17;
UPDATE `dashboard_widget_config` SET `height`=240 WHERE `id`=18;

/* Taxonomy Panel */
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=0 WHERE `id`=21;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=0 WHERE `id`=22;
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=1 WHERE `id`=23;
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=0 WHERE `id`=24;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=1 WHERE `id`=25;
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=1 WHERE `id`=26;
/* Network Panel */
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=0 WHERE `id`=27;
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=0 WHERE `id`=28;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=0 WHERE `id`=29;
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=1 WHERE `id`=30;
/* Honeypot Panel */
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=0 WHERE `id`=33;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=0 WHERE `id`=34;
UPDATE `dashboard_widget_config` SET `col`=0, `fil`=1 WHERE `id`=35;
UPDATE `dashboard_widget_config` SET `col`=2, `fil`=1 WHERE `id`=36;
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=1 WHERE `id`=37;
UPDATE `dashboard_widget_config` SET `col`=1, `fil`=0 WHERE `id`=38;


UPDATE `repository` SET `text`=REPLACE(`text`, '($SRCIP) ', '(($SRCIP)) ')  WHERE `id` IN (10018, 10158);
UPDATE `repository` SET `text`=REPLACE(`text`, '((#SRCUSER))', '(($SRCUSER))')  WHERE `id` IN (10020, 10024);

REPLACE INTO config (conf, value) VALUES ('logger_storage_days_lifetime', '0');
REPLACE INTO config (conf, value) VALUES ('logger_expire', 'no');

-- Deployment Status
TRUNCATE TABLE `alienvault_api`.`status_message`;
TRUNCATE TABLE `alienvault_api`.`status_message_action`;
TRUNCATE TABLE `alienvault_api`.`status_action`;

-- Actions
REPLACE INTO `alienvault_api`.`status_action` (`action_id`,`is_admin`,`content`,`link`) VALUES
(0,false,'An account with administrative privileges is required to fix this issue.','none'),
(1001,true,'<<Configure>> data source','AV_PATH/asset_details/enable_plugin.php?asset_id=ASSET_ID'),
(1002,true,'<<Configure>> data source plugin','AV_PATH/asset_details/enable_plugin.php?asset_id=ASSET_ID&enable=true'),
(1003,true,'<<Confirm>> AlienVault Sensor is running normally','AV_PATH/sensor/sensor.php?m_opt=configuration&sm_opt=deployment&h_opt=components&l_opt=sensors'),
(1004,true,'<<Validate>> there is network connectivity between Sensor and asset','AV_PATH/netscan/index.php?m_opt=environment&sm_opt=assets&h_opt=asset_discovery&action=custom_scan&host_id=ASSET_ID&sensor=automatic&scan_mode=fast'),
(1005,true,'<<Confirm>> the data source plugin is properly enabled','AV_PATH/assets/enable_plugin.php?asset_id=ASSET_ID'),
(1010,true,'<<Confirm>> network load does not exceed supported load for AlienVault Sensor','AV_PATH/ntop/index.php?opc=services&m_opt=environment&sm_opt=profiles&h_opt=services'),
(1011,true,'<<Verify>> system resources are not overloaded','AV_PATH/av_center/index.php?m_opt=configuration&sm_opt=deployment&h_opt=components'),
(1020,true,'Clean cache of software updates:\n1. Go to Alienvault console (Alienvault-setup)\n2. Maintenance & Troubleshooting / Mantain Disk and Logs / Clear System Update Cache',''),
(1021,true,'Purge old System logs:\n1. Go to AlienVault console (alienvault-setup)\n2. Maintenance & Troubleshooting / Mantain Disk and Logs / Purge Old System Logs',''),
(1022,true,'Adjust your <<Backup options>>','AV_PATH/conf/index.php?m_opt=configuration&sm_opt=administration&h_opt=main&open=0'),
(1030,true,'Configure an internal DNS:\n1. Go to Alienvault console (Alienvault-setup)\n2. System Preferences / Configure Network / Name Server DNS','');

-- Messages
REPLACE INTO `alienvault_api`.`status_message` (`id`,`level`,`description`,`content`) VALUES
(1,'info','Enable log management','As of TIMESTAMP, no logs have been processed for this asset. Integrating logs from your assets allows for more accurate correlation and retention of these logs may be necessary for compliance. This process should only take 3-5 minutes.'),
(2,'warning','Asset logs are not being processed','The asset is sending logs to the system but they are not being processed. Ensure that the appropriate data source plugin is enabled. At TIMESTAMP'),
(3,'warning','Log management disrupted','The system has not received a log from this asset in more than 24 hours. This may be an indicator of the asset having connection difficulties with AlienVault or a disruptive configuraiton change on the asset. At TIMESTAMP'),
(4,'warning','Unable to analyze all network traffic','The system is receiving more packets than it can process, causing packet loss. This will result in some network traffic being excluded from analysis. Fix this issue to ensure full network visibility. At TIMESTAMP'),
(5,'warning','Unable to analyze all network traffic','The system is receiving network packets that it can not process, likely due to malformed packets or unsupported packet sizes or  network protocols. This will result in some network traffic being excluded from analysis. Fix this issue to ensure full network visibility. At TIMESTAMP'),
(6,'warning','Disk space is low','The system has less than 25% of the total disk space available. Please address this issue soon to avoid a service disruption. At TIMESTAMP'),
(7,'error','Disk space is critically low','The system has less than 10% of the total disk space available. Please address this issue immediately to avoid a service disruption. At TIMESTAMP'),
(8,'info','Configured DNS is external',"The configured Domain Name Server is external to your environment. This will cause your asset names won't be discovered. At TIMESTAMP");

-- Actions for messages
REPLACE INTO `alienvault_api`.`status_message_action` (`message_id`,`action_id`) VALUES
(1,1001),
(2,1002),
(3,1003),
(3,1004),
(3,1005),
(4,1010),
(4,1011),
(5,1010),
(6,1020),
(6,1021),
(6,1022),
(7,1020),
(7,1021),
(7,1022),
(8,1030);

-- Software_cpe
UPDATE `software_cpe` SET plugin='apache:1501' where plugin='apache';
UPDATE `software_cpe` SET plugin='apache-syslog:1501' where plugin='apache-syslog';
UPDATE `software_cpe` SET plugin='spamassassin:1524' where plugin='spamassassin';
UPDATE `software_cpe` SET plugin='avast:1567' where plugin='avast';
UPDATE `software_cpe` SET plugin='bluecoat:1642' where plugin='bluecoat';
UPDATE `software_cpe` SET plugin='fw1ngr60:1504' where plugin='fw1ngr60';
UPDATE `software_cpe` SET plugin='cisco-ace:1653' where plugin='cisco-ace';
UPDATE `software_cpe` SET plugin='cisco-asa:1636' where plugin='cisco-asa';
UPDATE `software_cpe` SET plugin='cisco-fw:1514' where plugin='cisco-fw';
UPDATE `software_cpe` SET plugin='cisco-ids:1515' where plugin='cisco-ids';
UPDATE `software_cpe` SET plugin='cisco-pix:1514' where plugin='cisco-pix';
UPDATE `software_cpe` SET plugin='cisco-acs:1594' where plugin='cisco-acs';
UPDATE `software_cpe` SET plugin='citrix-netscaler:1678' where plugin='citrix-netscaler';
UPDATE `software_cpe` SET plugin='clamav:1555' where plugin='clamav';
UPDATE `software_cpe` SET plugin='sonicwall:1573' where plugin='sonicwall';
UPDATE `software_cpe` SET plugin='dovecot:1648' where plugin='dovecot';
UPDATE `software_cpe` SET plugin='drupal-wiki:1675' where plugin='drupal-wiki';
UPDATE `software_cpe` SET plugin='f5:1614' where plugin='f5';
UPDATE `software_cpe` SET plugin='axigen-mail:1664' where plugin='axigen-mail';
UPDATE `software_cpe` SET plugin='gfi:1530' where plugin='gfi';
UPDATE `software_cpe` SET plugin='suhosin:1685' where plugin='suhosin';
UPDATE `software_cpe` SET plugin='serviceguard:1582' where plugin='serviceguard';
UPDATE `software_cpe` SET plugin='sitescope:1583' where plugin='sitescope';
UPDATE `software_cpe` SET plugin='aix-audit:1649' where plugin='aix-audit';
UPDATE `software_cpe` SET plugin='realsecure:1506' where plugin='realsecure';
UPDATE `software_cpe` SET plugin='storewize-V7000:33002' where plugin='storewize-V7000';
UPDATE `software_cpe` SET plugin='imperva-securesphere:1679' where plugin='imperva-securesphere';
UPDATE `software_cpe` SET plugin='bind:1577' where plugin='bind';
UPDATE `software_cpe` SET plugin='siteprotector:1611' where plugin='siteprotector';
UPDATE `software_cpe` SET plugin='juniper-vpn:1609' where plugin='juniper-vpn';
UPDATE `software_cpe` SET plugin='heartbeat:1523' where plugin='heartbeat';
UPDATE `software_cpe` SET plugin='mcafee:1571' where plugin='mcafee';
UPDATE `software_cpe` SET plugin='mcafee-epo:4008' where plugin='mcafee-epo';
UPDATE `software_cpe` SET plugin='exchange:1603' where plugin='exchange';
UPDATE `software_cpe` SET plugin='iis:1502' where plugin='iis';
UPDATE `software_cpe` SET plugin='isa:1565' where plugin='isa';
UPDATE `software_cpe` SET plugin='W2003DNS:1689' where plugin='W2003DNS';
UPDATE `software_cpe` SET plugin='modsecurity:1561' where plugin='modsecurity';
UPDATE `software_cpe` SET plugin='moodle:1604' where plugin='moodle';
UPDATE `software_cpe` SET plugin='nagios:1525' where plugin='nagios';
UPDATE `software_cpe` SET plugin='ssh:4003' where plugin='ssh';
UPDATE `software_cpe` SET plugin='openldap:1586' where plugin='openldap';
UPDATE `software_cpe` SET plugin='postfix:1521' where plugin='postfix';
UPDATE `software_cpe` SET plugin='pureftpd:1616' where plugin='pureftpd';
UPDATE `software_cpe` SET plugin='token-rsa:90008' where plugin='token-rsa';
UPDATE `software_cpe` SET plugin='rsa-secureid:1593' where plugin='rsa-secureid';
UPDATE `software_cpe` SET plugin='smbd:1666' where plugin='smbd';
UPDATE `software_cpe` SET plugin='sidewinder:1572' where plugin='sidewinder';
UPDATE `software_cpe` SET plugin='snort_syslog:1001' where plugin='snort_syslog';
UPDATE `software_cpe` SET plugin='squid:1553' where plugin='squid';
UPDATE `software_cpe` SET plugin='symantec-ams:1556' where plugin='symantec-ams';
UPDATE `software_cpe` SET plugin='symantec-epm:1619' where plugin='symantec-epm';
UPDATE `software_cpe` SET plugin='nessus:3001' where plugin='nessus';
UPDATE `software_cpe` SET plugin='nessus-detector:3001' where plugin='nessus-detector';
UPDATE `software_cpe` SET plugin='sudo:4005' where plugin='sudo';
UPDATE `software_cpe` SET plugin='trendmicro:1574' where plugin='trendmicro';
UPDATE `software_cpe` SET plugin='vandyke-vshell:1677' where plugin='vandyke-vshell';
UPDATE `software_cpe` SET plugin='vmware-vcenter-sql:90007' where plugin='vmware-vcenter-sql';
UPDATE `software_cpe` SET plugin='vmware-vcenter:1658' where plugin='vmware-vcenter';
UPDATE `software_cpe` SET plugin='vmware-workstation:1562' where plugin='vmware-workstation';
UPDATE `software_cpe` SET plugin='websense:19004' where plugin='websense';
UPDATE `software_cpe` SET plugin='cisco-wlc:1663' where plugin='cisco-wlc';
UPDATE `software_cpe` SET plugin='cisco-asr:1670' where plugin='cisco-asr';
UPDATE `software_cpe` SET plugin='extreme-wireless:1673' where plugin='extreme-wireless';
UPDATE `software_cpe` SET plugin='extreme-switch:1672' where plugin='extreme-switch';
UPDATE `software_cpe` SET plugin='f5-firepass:1674' where plugin='f5-firepass';
UPDATE `software_cpe` SET plugin='fortigate:1554' where plugin='fortigate';
UPDATE `software_cpe` SET plugin='iphone:4006' where plugin='iphone';
UPDATE `software_cpe` SET plugin='cisco-nexus-nx-os:1652' where plugin='cisco-nexus-nx-os';
UPDATE `software_cpe` SET plugin='cisco-3030:1657' where plugin='cisco-3030';
UPDATE `software_cpe` SET plugin='cisco-vpn:1527' where plugin='cisco-vpn';
UPDATE `software_cpe` SET plugin='vmware-esxi:1686' where plugin='vmware-esxi';

REPLACE INTO `software_cpe` VALUES ('cpe:/a:cyberguard:sg565:1.0.0','SG565','1.0.0','CyberGuard SG565 1.0.0','CyberGuard','cyberguard:1575');

REPLACE INTO `software_cpe_links` VALUES
(1,'','','DC-00101_01.pdf'),
(2,'Cisco','ASA','DC-00102_01.pdf'),
(3,'Cisco','PIX','DC-00103_01.pdf'),
(4,'Dell','SonicWALL','DC-00120_00.pdf'),
(5,'Cisco','2000 Series Wireless LAN Controller','DC-00121_00.pdf'),
(6,'Citrix','NetScaler','DC-00122_00.pdf'),
(7,'CyberGuard','SG565','DC-00123_00.pdf'),
(8,'F5','FirePass','DC-00124_00.pdf'),
(9,'Fortinet','FortiGate','DC-00125_00.pdf');

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2014-04-01');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.6.0');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
