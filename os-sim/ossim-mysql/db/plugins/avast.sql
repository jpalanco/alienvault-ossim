-- Avast Antivirus Home 4.0
-- Plugin id:1567

DELETE FROM plugin WHERE id = "1567";
DELETE FROM plugin_sid where plugin_id = "1567";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1567, 1, 'avast', 'Avast Antivirus Home 4.0');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1567, 1, NULL, NULL, 'Avast: VIRUS FOUND', 1, 3);
