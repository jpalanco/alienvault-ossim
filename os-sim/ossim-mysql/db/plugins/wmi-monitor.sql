-- wmi
-- plugin_id: 2012

DELETE FROM plugin WHERE id = "2012";
DELETE FROM plugin_sid where plugin_id = "2012";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (2012, 2, 'wmi-monitor', 'wmi-monitor: Windows checks via wmi');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2012, 1, NULL, NULL, 'wmi-monitor: User logged');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2012, 2, NULL, NULL, 'wmi-monitor: Service up');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2012, 3, NULL, NULL, 'wmi-monitor: Process up');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2012, 4, NULL, NULL, 'wmi-monitor: Clsid installed');
