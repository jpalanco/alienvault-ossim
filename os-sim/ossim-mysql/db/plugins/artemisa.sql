-- Artemisa
-- plugin_id: 1668

DELETE FROM plugin WHERE id = 1668;
DELETE FROM plugin_sid where plugin_id = 1668;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1668, 1, 'Artemisa', 'Artemisa: VOIP Honeypot');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1668, 1, NULL, NULL, 'Artemisa: Command received', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1668, 2, NULL, NULL, 'Artemisa: Command sent', 3, 2);
