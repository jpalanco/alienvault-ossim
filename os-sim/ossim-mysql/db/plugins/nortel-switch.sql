-- nortel-switch
-- plugin_id: 1557

DELETE FROM plugin WHERE id = "1557";
DELETE FROM plugin_sid where plugin_id = "1557";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1557, 1, 'nortel-switch', 'Nortel switch and router messages');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1557, 1, NULL, NULL, 'nortel-switch: cli user login via telnet', 2, 4);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1557, 2, NULL, NULL, 'nortel-switch: cli user logout via telnet', 2, 4);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1557, 3, NULL, NULL, 'nortel-switch: cli user console login', 2, 4);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1557, 4, NULL, NULL, 'nortel-switch: cli user console logout', 2, 4);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1557, 5, NULL, NULL, 'nortel-switch: multiple cli login failures', 2, 4);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1557, 6, NULL, NULL, 'nortel-switch: cli login failure', 2, 4);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1557, 999, NULL, NULL, 'nortel-switch: generic event', 1, 1);
