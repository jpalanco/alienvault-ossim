-- netkeeper-nids 
-- plugin_id: 1647

DELETE FROM plugin WHERE id = "1647";
DELETE FROM plugin_sid where plugin_id = "1647";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1647, 1, 'netkeeper-nids', 'NetKeeper NIDS Detection');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1647, 1, NULL, NULL, 'Netkeeper: Normal Event' , 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1647, 2, NULL, NULL, 'Netkeeper: Start service event' , 1, 1);
