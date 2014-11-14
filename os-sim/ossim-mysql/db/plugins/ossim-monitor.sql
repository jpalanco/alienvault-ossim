-- ossim-ca
-- type: monitor
-- plugin_id: 2001
-- description: Ossim Compromise and Attack Monitor
-- DELETE FROM plugin WHERE id = "2001";
-- DELETE FROM plugin_sid where plugin_id = "2001";


INSERT IGNORE INTO plugin (id, type, name, description) VALUES (2001, 2, 'ossim-ca', 'Ossim compromise and attack monitor');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2001, 1, NULL, NULL, 'ossim-monitor: Compromise value');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2001, 2, NULL, NULL, 'ossim-monitor: Attack value');

