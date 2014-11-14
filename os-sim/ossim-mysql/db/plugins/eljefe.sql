-- ElJefe
-- plugin_id:1633

DELETE FROM plugin WHERE id="1633";
DELETE FROM plugin_sid WHERE plugin_id="1633";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1633, 1, "ElJefe", "Eljefe");
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1633, 1, null, null, "System: Process FILENAME created by USERDATA1", 1, 1);
