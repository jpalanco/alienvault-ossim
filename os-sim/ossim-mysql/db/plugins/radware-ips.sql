-- radware-ips
-- plugin_id: 1645

DELETE FROM plugin WHERE id = "1645";
DELETE FROM plugin_sid where plugin_id = "1645";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1645, 1, 'radware', 'Radware IPS Detector');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1645, 1, NULL, NULL, 'Radware: Info entry' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1645, 2, NULL, NULL, 'Radware: Warning entry' , 2, 2);
