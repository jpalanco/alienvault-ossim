-- iptables
-- plugin_id: 1503
--
DELETE FROM plugin WHERE id = "1503";
DELETE FROM plugin_sid where plugin_id = "1503";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1503, 1, 'iptables', 'Iptables');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1503, 1, 202, NULL, 'iptables: Accept', 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1503, 2, 203, NULL, 'iptables: Reject');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1503, 3, 204, NULL, 'iptables: Drop');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1503, 4, NULL, NULL, 'iptables: traffic inbound');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1503, 5, NULL, NULL, 'iptables: traffic outbound');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1503, 6, NULL, NULL, 'iptables: Generic event');



