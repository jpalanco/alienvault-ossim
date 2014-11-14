-- netkeeper-fw
-- plugin_id: 1646

DELETE FROM plugin WHERE id = "1646";
DELETE FROM plugin_sid where plugin_id = "1646";


INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1646, 1, 'netkeeper-fw', 'NetKeeper Firewall');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1646, 1, NULL, NULL, 'Netkeeper: IP Traffic event' , 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1646, 2, NULL, NULL, 'Netkeeper: NET Traffic event' , 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1646, 3, NULL, NULL, 'Netkeeper: ACL Allowed Traffic event' , 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1646, 4, NULL, NULL, 'Netkeeper: Firewall Config Change event' , 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1646, 5, NULL, NULL, 'Netkeeper: NET Probe Traffic event' , 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1646, 6, NULL, NULL, 'Netkeeper: Service Traffic event' , 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1646, 7, NULL, NULL, 'Netkeeper: LCM Traffic event' , 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1646, 100, NULL, NULL, 'Netkeeper: Generic Information event' , 1, 1);
