-- monit
-- plugin_id: 1687

DELETE FROM plugin WHERE id = 1687;
DELETE FROM plugin_sid where plugin_id = 1687;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1687, 1, 'monit', 'Monit Plugin');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 1, NULL, NULL, 'Process not running', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 2, NULL, NULL, 'Start process', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 3, NULL, NULL, 'Process Running with pid X', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 4, NULL, NULL, 'Generated unique id for monit', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 5, NULL, NULL, 'Starting monit', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 6, NULL, NULL, 'Trying to restart process', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 7, NULL, NULL, 'No mail server defined', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 8, NULL, NULL, 'Monit started', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 9, NULL, NULL, 'Failed to start process', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 10, NULL, NULL, 'Aborting Event', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 11, NULL, NULL, 'Monit stopped', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 12, NULL, NULL, 'Monit start delay set', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 13, NULL, NULL, 'Monit killed', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 14, NULL, NULL, 'Process started', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 15, NULL, NULL, 'Checksum was changed', 3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1687, 16, NULL, NULL, 'Checksum has not changed', 3, 3);
