-- Bro IDS
-- plugin_id: 1568

DELETE FROM plugin WHERE id="1568";
DELETE FROM plugin_sid WHERE plugin_id="1568";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1568, 1, 'bro-ids', 'Bro-IDS');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1568, 1, NULL, NULL, 'Bro-IDS: Address dropped', 1, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1568, 2, NULL, NULL, 'Bro-IDS: Port scan', 1, 3);
