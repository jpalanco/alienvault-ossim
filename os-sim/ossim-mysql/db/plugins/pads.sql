-- pads
-- plugin_id: 1516

DELETE FROM plugin WHERE id = "1516";
DELETE FROM plugin_sid where plugin_id = "1516";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1516, 1, 'pads', 'Passive Asset Detection System');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 1, NULL, NULL, 'pads: New service detected');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 2, NULL, NULL, 'pads: Service Change');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 3, NULL, NULL, 'pads: Service Deleted');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 4, NULL, NULL, 'pads: Service Same');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 5, NULL, NULL, 'pads: Service Event unknown');
