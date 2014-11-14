-- sophos
-- plugin_id: 1558

DELETE FROM plugin WHERE id = "1558";
DELETE FROM plugin_sid where plugin_id = "1558";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1558, 1, 'sophos', 'Sophos Antivirus');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 1, NULL, NULL, 'Sophos: Trojan found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 2, NULL, NULL, 'Sophos: Forbidden software found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 4, NULL, NULL, 'Sophos: Malware found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 5, NULL, NULL, 'Sophos: Malware found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 6, NULL, NULL, 'Sophos: Forbidden software found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 99, NULL, NULL, 'Sophos: Unknown event');
