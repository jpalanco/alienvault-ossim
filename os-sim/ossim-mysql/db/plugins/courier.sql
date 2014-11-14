-- courier
-- plugin_id: 1617

DELETE FROM plugin WHERE id = "1617";
DELETE FROM plugin_sid where plugin_id = "1617";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1617, 1, 'courier', 'Courier Mail Server');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 1, NULL, NULL, 'Courier: Login');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 2, NULL, NULL, 'Courier: Logout');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 3, NULL, NULL, 'Courier: New connection');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 4, NULL, NULL, 'Courier: User disconnected');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 5, NULL, NULL, 'Courier: Timeout');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 6, NULL, NULL, 'Courier: Login failed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 99, NULL, NULL, 'Courier: Generic Event');
