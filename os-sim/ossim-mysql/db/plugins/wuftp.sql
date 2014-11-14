-- wuftp
-- plugin_id: 1632

DELETE FROM plugin WHERE id = "1632";
DELETE FROM plugin_sid where plugin_id = "1632";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1632, 1, 'wuftp', 'WU-FTP');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1632, 1, NULL, NULL, 'WU-FTP: Login', 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1632, 2, NULL, NULL, 'WU-FTP: Login Refused', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1632, 3, NULL, NULL, 'WU-FTP: Failed Login', 1, 1);
