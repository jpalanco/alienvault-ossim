-- iphone
-- plugin_id: 4006

DELETE FROM plugin WHERE id = "4006";
DELETE FROM plugin_sid where plugin_id = "4006";


INSERT IGNORE INTO plugin (id, type, name, description) VALUES (4006, 1, 'iphone', 'Apple iPhone');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 1, NULL, NULL, "iPhone: Youtube video started", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 2, NULL, NULL, "iPhone: Youtube video finished", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 3, NULL, NULL, "iPhone: Process Crashed", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 4, NULL, NULL, "iPhone: Program accesed", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 5, NULL, NULL, "iPhone: Out of memory", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 6, NULL, NULL, "iPhone: IPod started", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 7, NULL, NULL, "iPhone: Installer started", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 8, NULL, NULL, "iPhone: Source fetched", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 90, NULL, NULL, "iPhone: Installing software", 3, 9);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 10, NULL, NULL, "iPhone: Uninstalling software", 3, 9);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 11, NULL, NULL, "iPhone: Folder created", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 12, NULL, NULL, "iPhone: File created", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 13, NULL, NULL, "iPhone: Path removed", 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4006, 14, NULL, NULL, "iPhone: Call Performed", 2, 2);

