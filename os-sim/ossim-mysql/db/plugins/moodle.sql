-- Moodle
-- plugin_id:1604

DELETE FROM plugin WHERE id="1604";
DELETE FROM plugin_sid WHERE plugin_id="1604";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1604, 1, "Moodle", "Moodle");

INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1604, 1, null, null, "Moodle: User action", 1, 1);
