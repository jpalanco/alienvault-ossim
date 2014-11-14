-- symantec-ams
-- plugin_id: 1556

DELETE FROM plugin WHERE id = 1556;
DELETE FROM plugin_sid WHERE plugin_id=1556;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1556, 1, 'symantec-ams', 'Symantec AntiVirus Corporate Edition');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1556, 1, NULL, NULL, 'symantec-ams: Virus Found', 3, 5);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1556, 2, NULL, NULL, 'symantec-ams: Risk Repaired', 2, 5);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1556, 3, NULL, NULL, 'symantec-ams: Risk Repaired Failed', 4, 5);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1556, 4, NULL, NULL, 'symantec-ams: Virus Definition File Update', 0, 3);

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1556, 50, NULL, NULL, 'symantec-ams: Configuration Error');
