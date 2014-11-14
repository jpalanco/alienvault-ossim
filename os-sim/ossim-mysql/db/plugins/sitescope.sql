-- HP SiteScope
--
-- plugin_id: 1583
-- type: detector 
--
-- Description:
-- Monitor over 75 different targets for critical characteristics
-- such as utilization, response time, usage and resource availability
--
-- Please refer to SiteScopeReference Guide for adding new sids:
--   http://schist.und.nodak.edu:8899/SiteScope/docs/Log.htm
--   http://schist.und.nodak.edu:8899/SiteScope/docs/SSRefG_toc.htm
--   http://schist.und.nodak.edu:8899/SiteScope/docs/log_columns.htm
--
-- $Id: sitescope.sql,v 1.1 2009/05/13 10:38:56 dvgil Exp $

DELETE FROM plugin WHERE id ="1583";
DELETE FROM plugin_sid where plugin_id = "1583";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1583, 1, 'sitescope',
'HP SiteScope');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 1, NULL, NULL, 'HP SiteScope: Error detected by "Apache Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 2, NULL, NULL, 'HP SiteScope: Error detected by "ASP Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 3, NULL, NULL, 'HP SiteScope: Error detected by "Formula Composite" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 4, NULL, NULL, 'HP SiteScope: Error detected by "Browsable Windows Performance Counters" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 5, NULL, NULL, 'HP SiteScope: Error detected by "SNMP by MIB" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 6, NULL, NULL, 'HP SiteScope: Error detected by "BroadVision Application Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 7, NULL, NULL, 'HP SiteScope: Error detected by "CheckPoint Firewall-1" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 8, NULL, NULL, 'HP SiteScope: Error detected by "Cisco Works" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 9, NULL, NULL, 'HP SiteScope: Error detected by "Citrix Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 10, NULL, NULL, 'HP SiteScope: Error detected by "ColdFusion Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 11, NULL, NULL, 'HP SiteScope: Error detected by "Composite" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 12, NULL, NULL, 'HP SiteScope: Error detected by "CPU Utilization" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 13, NULL, NULL, 'HP SiteScope: Error detected by "Database Query" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 14, NULL, NULL, 'HP SiteScope: Error detected by "DB2" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 15, NULL, NULL, 'HP SiteScope: Error detected by "DHCP" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 16, NULL, NULL, 'HP SiteScope: Error detected by "Directory" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 17, NULL, NULL, 'HP SiteScope: Error detected by "Disk Space" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 18, NULL, NULL, 'HP SiteScope: Error detected by "DNS" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 19, NULL, NULL, 'HP SiteScope: Error detected by "Dynamo Application Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 20, NULL, NULL, 'HP SiteScope: Error detected by "eBusiness Chain" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 21, NULL, NULL, 'HP SiteScope: Error detected by "F5 Big-IP" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 22, NULL, NULL, 'HP SiteScope: Error detected by "File" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 23, NULL, NULL, 'HP SiteScope: Error detected by "FTP" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 24, NULL, NULL, 'HP SiteScope: Error detected by "Health of SiteScope Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 25, NULL, NULL, 'HP SiteScope: Error detected by "History Health Monitor" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 26, NULL, NULL, 'HP SiteScope: Error detected by "IIS Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 27, NULL, NULL, 'HP SiteScope: Error detected by "LDAP" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 28, NULL, NULL, 'HP SiteScope: Error detected by "Link Check" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 29, NULL, NULL, 'HP SiteScope: Error detected by "Log Event Health Monitor" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 30, NULL, NULL, 'HP SiteScope: Error detected by "Log File" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 31, NULL, NULL, 'HP SiteScope: Error detected by "Mail" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 32, NULL, NULL, 'HP SiteScope: Error detected by "MAPI" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 33, NULL, NULL, 'HP SiteScope: Error detected by "Master Health Monitor" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 34, NULL, NULL, 'HP SiteScope: Error detected by "Memory" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 35, NULL, NULL, 'HP SiteScope: Error detected by "MG Health Monitor" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 36, NULL, NULL, 'HP SiteScope: Error detected by "Monitor Load Monitor" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 37, NULL, NULL, 'HP SiteScope: Error detected by "iPlanet Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 38, NULL, NULL, 'HP SiteScope: Error detected by "Network Bandwidth" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 39, NULL, NULL, 'HP SiteScope: Error detected by "Network" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 40, NULL, NULL, 'HP SiteScope: Error detected by "News" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 41, NULL, NULL, 'HP SiteScope: Error detected by "Windows Performance Counter" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 42, NULL, NULL, 'HP SiteScope: Error detected by "Windows Dial-up" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 43, NULL, NULL, 'HP SiteScope: Error detected by "Windows Event Log" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 44, NULL, NULL, 'HP SiteScope: Error detected by "Oracle9i Application Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 45, NULL, NULL, 'HP SiteScope: Error detected by "Oracle Database" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 46, NULL, NULL, 'HP SiteScope: Error detected by "Windows Resources" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 47, NULL, NULL, 'HP SiteScope: Error detected by "Ping" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 48, NULL, NULL, 'HP SiteScope: Error detected by "Port" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 49, NULL, NULL, 'HP SiteScope: Error detected by "Global Port" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 50, NULL, NULL, 'HP SiteScope: Error detected by "Radius" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 51, NULL, NULL, 'HP SiteScope: Error detected by "Real Media Player" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 52, NULL, NULL, 'HP SiteScope: Error detected by "Real Media Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 53, NULL, NULL, 'HP SiteScope: Error detected by "RTSP" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 54, NULL, NULL, 'HP SiteScope: Error detected by "SAP" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 55, NULL, NULL, 'HP SiteScope: Error detected by "SAP Portal" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 56, NULL, NULL, 'HP SiteScope: Error detected by "Script" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 57, NULL, NULL, 'HP SiteScope: Error detected by "Service" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 58, NULL, NULL, 'HP SiteScope: Error detected by "SilverStream Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 59, NULL, NULL, 'HP SiteScope: Error detected by "SNMP" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 60, NULL, NULL, 'HP SiteScope: Error detected by "SNMP Trap" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 61, NULL, NULL, 'HP SiteScope: Error detected by "SQL Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 62, NULL, NULL, 'HP SiteScope: Error detected by "SunONE WebServer" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 63, NULL, NULL, 'HP SiteScope: Error detected by "Sybase" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 64, NULL, NULL, 'HP SiteScope: Error detected by "Tuxedo" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 65, NULL, NULL, 'HP SiteScope: Error detected by "URL Content" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 66, NULL, NULL, 'HP SiteScope: Error detected by "URL List" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 67, NULL, NULL, 'HP SiteScope: Error detected by "URL" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 68, NULL, NULL, 'HP SiteScope: Error detected by "URL Original" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 69, NULL, NULL, 'HP SiteScope: Error detected by "URL Sequence" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 70, NULL, NULL, 'HP SiteScope: Error detected by "WebLogic 5.x Application Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 71, NULL, NULL, 'HP SiteScope: Error detected by "WebLogic Application Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 72, NULL, NULL, 'HP SiteScope: Error detected by "Web Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 73, NULL, NULL, 'HP SiteScope: Error detected by "Web Service" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 74, NULL, NULL, 'HP SiteScope: Error detected by "WebSphere Application Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 75, NULL, NULL, 'HP SiteScope: Error detected by "WebSphere Performance Servlet" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 76, NULL, NULL, 'HP SiteScope: Error detected by "Windows Media Server" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 77, NULL, NULL, 'HP SiteScope: Error detected by "Windows Media Player" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 78, NULL, NULL, 'HP SiteScope: Error detected by "Sample Directory" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 79, NULL, NULL, 'HP SiteScope: Error detected by "Sample File" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 80, NULL, NULL, 'HP SiteScope: Error detected by "Sample Script" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 81, NULL, NULL, 'HP SiteScope: Error detected by "Multi-Parameter Sample" monitor', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1583, 999, NULL, NULL, 'HP SiteScope: Error detected by an uknown monitor. Please report it to http://bugs.ossim.net', 2, 3);


