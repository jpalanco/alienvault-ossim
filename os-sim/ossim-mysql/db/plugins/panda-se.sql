-- Panda SE
-- plugin_id:1605

DELETE FROM plugin WHERE id="1605";
DELETE FROM plugin_sid WHERE plugin_id="1605";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1605, 1, "PandaSE", "Panda Security For Enterprise");
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1605, 1, null, null, "PandaSE: Virus Detected", 1, 1);
