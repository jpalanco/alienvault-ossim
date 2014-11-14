-- heartbeat
-- plugin_id: 1523

DELETE FROM plugin WHERE id = "1523";
DELETE FROM plugin_sid where plugin_id = "1523";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1523, 1, 'heartbeat', 'Heartbeat without CRM');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 1, NULL, NULL, 'heartbeat: node up');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 2, NULL, NULL, 'heartbeat: node active');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 3, NULL, NULL, 'heartbeat: node dead');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 4, NULL, NULL, 'heartbeat: link up');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 5, NULL, NULL, 'heartbeat: link dead');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 6, NULL, NULL, 'heartbeat: resources being acquired');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 7, NULL, NULL, 'heartbeat: resources acquired');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 8, NULL, NULL, 'heartbeat: no resources to acquire');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 9, NULL, NULL, 'heartbeat: standby');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 10, NULL, NULL, 'heartbeat: standby completed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 11, NULL, NULL, 'heartbeat: shutdown');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 12, NULL, NULL, 'heartbeat: shutdown completed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 13, NULL, NULL, 'heartbeat: late heartbeat');


