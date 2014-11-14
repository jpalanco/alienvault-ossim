-- clamav - Clam antivirus
-- plugin_id: 1555

DELETE FROM plugin WHERE id = 1555;
DELETE FROM plugin_sid WHERE plugin_id=1555;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1555, 1, 'clamav', 'Clam AntiVirus');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1555, 1, NULL, NULL, 'clamav: Virus Found', 3, 5);
