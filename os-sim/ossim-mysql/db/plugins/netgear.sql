-- netgear
-- plugin_id: 1519

DELETE FROM plugin WHERE id = "1519";
DELETE FROM plugin_sid where plugin_id = "1519";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1519, 1, 'netgear', 'Netgear');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 1, NULL, NULL, 'Netgear: All ports forwarded');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 2, NULL, NULL, 'Netgear: UDP packet forwarded');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 3, NULL, NULL, 'Netgear: SMTP forwarded');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 4, NULL, NULL, 'Netgear: HTTP forwarded');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 5, NULL, NULL, 'Netgear: HTTPS forwarded');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 6, NULL, NULL, 'Netgear: TCP connection dropped');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 7, NULL, NULL, 'Netgear: IP packet dropped');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 8, NULL, NULL, 'Netgear: UDP packet dropped');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 9, NULL, NULL, 'Netgear: ICMP packet dropped');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 10, NULL, NULL, 'Netgear: Successful administrator login');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1519, 11, NULL, NULL, 'Netgear: Administrator login fail');

