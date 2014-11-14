-- McAfee Antivirus
-- Plugin id: 1571

DELETE FROM plugin WHERE id = "1571";
DELETE FROM plugin_sid where plugin_id = "1571";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1571, 1, 'mcafee', 'McAfee Antivirus');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1571, 1, NULL, NULL, 'McAfee Antivirus: BLOCKED', 1, 3);
