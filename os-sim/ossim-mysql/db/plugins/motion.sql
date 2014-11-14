-- Motion
-- plugin_id: 1613

DELETE FROM plugin WHERE id = "1613";
DELETE FROM plugin_sid where plugin_id = "1613";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1613, 1, 'motion', 'Motion: Motion detector');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1613, 1, NULL, NULL, 'Motion: motion detected in camera', 4, 3);
