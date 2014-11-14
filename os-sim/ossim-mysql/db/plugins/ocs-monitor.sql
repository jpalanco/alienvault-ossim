-- OCS-Monitor
-- plugin_id:2013

DELETE FROM plugin WHERE id = "2013";
DELETE FROM plugin_sid where plugin_id = "2013";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (2013, 2, 'OCS-Monitor', 'OCS inventory monitor');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2013, 1, NULL, NULL, 'OCS-Monitor: Operating System');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2013, 2, NULL, NULL, 'OCS-Monitor: Service Pack');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2013, 3, NULL, NULL, 'OCS-Monitor: Kernel Version');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2013, 4, NULL, NULL, 'OCS-Monitor: Software Installed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2013, 5, NULL, NULL, 'OCS-Monitor: Software,Version Installed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2013, 6, NULL, NULL, 'OCS-Monitor: Antivirus Installed');
