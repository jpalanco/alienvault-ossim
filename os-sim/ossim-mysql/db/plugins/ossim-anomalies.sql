-- ossim anomalies
-- plugin_id: 5004

DELETE FROM plugin WHERE id = "5004";
DELETE FROM plugin_sid where plugin_id = "5004";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (5004, 1, "anomalies", "Inventory anomalies");

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5004, 1, NULL, NULL, 1, 1, "Host service change");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5004, 2, NULL, NULL, 1, 1, "Host operating system change");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5004, 3, NULL, NULL, 1, 1, "IP address change");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5004, 4, NULL, NULL, 1, 1, "Hostname change");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5004, 5, NULL, NULL, 1, 1, "Machine state change");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5004, 6, NULL, NULL, 1, 1, "CPU state change");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5004, 7, NULL, NULL, 1, 1, "RAM memory state change");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5004, 8, NULL, NULL, 1, 1, "Graphic card state change");
