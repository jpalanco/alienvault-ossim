-- sidewinder
-- plugin_id: 1572

DELETE FROM plugin WHERE id = "1572";
DELETE FROM plugin_sid where plugin_id = "1572";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1572, 1, 'sidewinder', 'Sidewinder firewall (BSD based)');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 1, NULL, NULL, 'Sidewinder: client connection related' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 2, NULL, NULL, 'Sidewinder: IP Filter: All NAT ports in use' , 0, 8);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 3, NULL, NULL, 'Sidewinder: IP Filter: Got invalid packet in SYN_SENT state.' , 0, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 4, NULL, NULL, 'Sidewinder: IP Filter: Expected SYN, got ACK' , 0, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 5, NULL, NULL, 'Sidewinder: IP Filter: Expected SYN, got RST' , 0, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 6, NULL, NULL, 'Sidewinder: IP Filter: Expected SYN, got FIN-ACK' , 0, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 7, NULL, NULL, 'Sidewinder: IP Filter: Got invalid packet in FIN_WAIT state.' , 0, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 8, NULL, NULL, 'Sidewinder: IP Filter: Got invalid packet in SYN-ACK_SENT state.' , 0, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 9, NULL, NULL, 'Sidewinder: maxed out sockets' , 0, 5);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 10, NULL, NULL, 'Sidewinder: Alarm on auditbot' , 0, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 11, NULL, NULL, 'Sidewinder: Connection refused' , 0, 9);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 12, NULL, NULL, 'Sidewinder: Shutting down sendmail' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1572, 13, NULL, NULL, 'Sidewinder: Lock released by user root' , 0, 1);
