-- vandyke-vshell
-- plugin_id: 1677

DELETE FROM plugin WHERE id = 1677;
DELETE FROM plugin_sid where plugin_id = 1677;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1677, 1, 'vandyke-vshell', 'VanDyke VShell');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1677, 100, NULL, NULL, 'vandyke-vshell: Authentication Success' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1677, 200, NULL, NULL, 'vandyke-vshell: Authentication Failed' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1677, 300, NULL, NULL, 'vandyke-vshell: Directory Manipulation' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1677, 400, NULL, NULL, 'vandyke-vshell: File Access' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1677, 500, NULL, NULL, 'vandyke-vshell: File Manipulation' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1677, 900, NULL, NULL, 'vandyke-vshell: General' , 0, 1);
