-- cisco-vpn
-- plugin_id: 1527

DELETE FROM plugin WHERE id = "1527";
DELETE FROM plugin_sid where plugin_id = "1527";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1527, 1, 'cisco-vpn', 'Cisco VPN box');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 1, NULL, NULL, 'Cisco VPN Box: Connection denied ', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 2, NULL, NULL, 'Cisco VPN Box: Connection permmited', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 3, NULL, NULL, 'Cisco VPN Box: Interface changed status to down', 2, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 4, NULL, NULL, 'Cisco VPN Box: Interface changed status to up', 2, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 5, NULL, NULL, 'Cisco VPN Box: Login failed', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 6, NULL, NULL, 'Cisco VPN Box: Login success', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 7, NULL, NULL, 'Cisco VPN Box: Cisco VPN Box configured', 3, 1);
