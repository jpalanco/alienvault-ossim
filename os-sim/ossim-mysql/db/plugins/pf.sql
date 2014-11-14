-- IPFW
-- plugin_id: 1560

DELETE FROM plugin WHERE id = "1560";
DELETE FROM plugin_sid where plugin_id = "1560";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1560, 1, 'pf', 'pf Firewall log');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1560, 1, NULL, NULL, 'pf: Accept' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1560, 2, NULL, NULL, 'pf: Block' ,2, 1);
