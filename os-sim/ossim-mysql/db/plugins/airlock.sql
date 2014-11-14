-- airlock
-- plugin_id: 1641

DELETE FROM plugin WHERE id = 1641;
DELETE FROM plugin_sid where plugin_id = 1641;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1641, 1, 'airlock', 'Airlock Reverse Proxy');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 1, NULL, NULL, 'Airlock: Web-Request', 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 2, NULL, NULL, 'Airlock: Access Denied', 2, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 3, NULL, NULL, 'Airlock: Possible Attack', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 4, NULL, NULL, 'Airlock: Possible Backend Problem', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 5, NULL, NULL, 'Airlock: Terminated - Error', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 6, NULL, NULL, 'Airlock: Malformed Packet', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 999, NULL, NULL, 'Airlock: Default', 1, 1);
