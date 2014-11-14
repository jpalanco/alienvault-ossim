-- prads
-- plugin_id: 1683

DELETE FROM plugin WHERE id = "1683";
DELETE FROM plugin_sid where plugin_id = "1683";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1683, 1, 'prads', 'Passive RealTime Asset Detection System');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1683, 1, NULL, NULL, 'prads: Service Detected');
