-- syslog
-- plugin_id: 4007

DELETE FROM plugin WHERE id = "4007";
DELETE FROM plugin_sid where plugin_id = "4007";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (4007, 1, 'syslog', 'Syslog plugin with md5 checksum logging');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4007, 1, NULL, NULL, 'Syslog: syslog entry' , 0, 1);
