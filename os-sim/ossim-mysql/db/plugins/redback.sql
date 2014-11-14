-- Redback
-- plugin_id: 1606

DELETE FROM plugin WHERE id = "1606";
DELETE FROM plugin_sid where plugin_id = "1606";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1606, 1, 'redback', 'Redback edge');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1606, 1, NULL, NULL, 'redback: Local Administrator logged in' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1606, 2, NULL, NULL, 'redback: Recorded local login for administrator' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1606, 3, NULL, NULL, 'redback: SSHD Accepted password' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1606, 10, NULL, NULL, 'redback: Wrong password for administrator', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1606, 11, NULL, NULL, 'redback: AAA Administrator login failed for all authentication methods' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1606, 12, NULL, NULL, 'redback: SSHD Failed password' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1606, 13, NULL, NULL, 'redback: SNMP Authentication failure' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1606, 30, NULL, NULL, 'redback: SNMP Arp info overwritten' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1606, 31, NULL, NULL, 'redback: Link state DOWN' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1606, 32, NULL, NULL, 'redback: Link state UP' ,1, 1);
