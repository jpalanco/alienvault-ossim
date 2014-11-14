-- IPFW
-- plugin_id: 1559

DELETE FROM plugin WHERE id = "1559";
DELETE FROM plugin_sid where plugin_id = "1559";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1559, 1, 'm0n0wall', 'm0n0wall Firewall log');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1559, 1, NULL, NULL, 'm0n0wall: Accept' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1559, 2, NULL, NULL, 'm0n0wall: Deny' ,2, 1);
