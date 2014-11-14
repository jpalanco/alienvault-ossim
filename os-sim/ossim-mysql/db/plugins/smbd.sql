-- Smbd
-- plugin_id: 1666

DELETE FROM plugin WHERE id = "1666";
DELETE FROM plugin_sid where plugin_id = "1666";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1666, 1, 'smbd', 'Smbd: Samba Service');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1666, 1, NULL, NULL, 'Smbd: Connection Closed', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1666, 2, NULL, NULL, 'Smbd: Connection Opened', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1666, 3, NULL, NULL, 'Smbd: File Read', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1666, 4, NULL, NULL, 'Smbd: File Write', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1666, 5, NULL, NULL, 'Smbd: Unknown User', 3, 2);
