-- p0f
-- plugin_id: 1511

DELETE FROM plugin WHERE id = "1511";
DELETE FROM plugin_sid where plugin_id = "1511";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1511, 1, 'p0f', 'Passive OS fingerprinting tool');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1511, 1, NULL, NULL, 'p0f: New OS');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1511, 2, NULL, NULL, 'p0f: OS Change');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1511, 3, NULL, NULL, 'p0f: OS Deleted');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1511, 4, NULL, NULL, 'p0f: OS Same');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1511, 5, NULL, NULL, 'p0f: OS Event unknown');
