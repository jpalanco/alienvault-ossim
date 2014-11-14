-- IPFW
-- plugin_id: 1529

DELETE FROM plugin WHERE id = "1529";
DELETE FROM plugin_sid where plugin_id = "1529";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1529, 1, 'ipfw', 'FreeBSD ipfw');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1529, 1, NULL, NULL, 'ipfw: Accept' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1529, 2, NULL, NULL, 'ipfw: Check-State' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1529, 3, NULL, NULL, 'ipfw: Deny' ,2, 1);
