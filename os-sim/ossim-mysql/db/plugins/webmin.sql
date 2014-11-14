-- webmin
-- plugin_id: 1580

DELETE FROM plugin WHERE id = "1580";
DELETE FROM plugin_sid where plugin_id = "1580";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1580, 1, 'webmin', 'Webmin');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1580, 1, NULL, NULL, "Webmin: Invalid Login ", 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1580, 2, NULL, NULL, "Webmin: Non-existent Login", 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1580, 10, NULL, NULL, "Webmin: Succesful Login ", 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1580, 20, NULL, NULL, "Webmin: Logout", 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1580, 30, NULL, NULL, "Webmin: Starting", 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1580, 100, NULL, NULL, "Webmin: Generic event", 1, 1);
