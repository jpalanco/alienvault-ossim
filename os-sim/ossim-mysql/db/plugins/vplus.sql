-- Vision Plus
-- plugin_id: 1650

DELETE FROM plugin WHERE id = "1650";
DELETE FROM plugin_sid where plugin_id = "1650";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1650, 1, 'vplus', 'Vision Plus');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1650, 1, NULL, NULL, 'Vision Plus: Login success', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1650, 2, NULL, NULL, 'Vision Plus: Login failed', 1, 1);
