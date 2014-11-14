-- dovecot
-- plugin_id: 1648

DELETE FROM plugin WHERE id = "1648";
DELETE FROM plugin_sid where plugin_id = "1648";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1648, 1, 'dovecot', 'Dovecot Server');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1648, 1, NULL, NULL, 'Dovecot: Login success event' , 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1648, 2, NULL, NULL, 'Dovecot: User Disconnected event' , 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1648, 3, NULL, NULL, 'Dovecot: Connection close event' , 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1648, 4, NULL, NULL, 'Dovecot: Aborted login event' , 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1648, 99, NULL, NULL, 'Dovecot: Strange event' , 2, 2);
