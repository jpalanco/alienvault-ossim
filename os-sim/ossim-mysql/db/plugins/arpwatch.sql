-- arpwatch
-- plugin_id: 1512

DELETE FROM plugin WHERE id = 1512;
DELETE FROM plugin_sid where plugin_id = 1512;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1512, 1, 'arpwatch', 'Arpwatch');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 1, NULL, NULL, 'Arpwatch: Mac address New');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 2, NULL, NULL, 'Arpwatch: Mac address Change');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 3, NULL, NULL, 'Arpwatch: Mac address Deleted');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 4, NULL, NULL, 'Arpwatch: Mac address Same');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 5, NULL, NULL, 'Arpwatch: Mac address Event unknown');
