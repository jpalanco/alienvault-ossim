-- Honeyd Virtual Honeypot
-- Plugin id: 1570

DELETE FROM plugin WHERE id = "1570";
DELETE FROM plugin_sid where plugin_id = "1570";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1570, 1, 'honeyd', 'Honeyd Virtual Honeypot');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1570, 1, NULL, NULL, 'Honeyd End Connection', 1, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1570, 2, NULL, NULL, 'Honeyd Start Connection', 1, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1570, 3, NULL, NULL, 'Honeyd Packet', 1, 3);
