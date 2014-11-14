-- tarantella
-- plugin_id: 1552

DELETE FROM plugin WHERE id = 1552;
DELETE FROM plugin_sid WHERE plugin_id=1552;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1552, 1, 'tarantella', 'Tarantella');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1552,1,NULL,NULL,'tarantella: full-report');
