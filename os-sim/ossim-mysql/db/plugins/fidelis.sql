-- Fidelis 
-- plugin_id: 1592

DELETE FROM plugin WHERE id = "1592";
DELETE FROM plugin_sid where plugin_id = "1592";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1592, 1, 'fidelis', 'Fidelis');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1592, 1, NULL, NULL, 'Fidelis: Alert Low', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1592, 2, NULL, NULL, 'Fidelis: Alert Medium', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1592, 3, NULL, NULL, 'Fidelis: Alert High', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1592, 4, NULL, NULL, 'Fidelis: Alert Critical', 1, 1); 
