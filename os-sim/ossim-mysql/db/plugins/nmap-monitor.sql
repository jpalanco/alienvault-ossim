-- nmap
-- plugin_id: 2008

DELETE FROM plugin WHERE id = "2008";
DELETE FROM plugin_sid where plugin_id = "2008";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (2008, 2, 'nmap-monitor', 'Nmap: network mapper');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2008, 1, NULL, NULL, 'nmap-monitor: TCP Port opened');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2008, 2, NULL, NULL, 'nmap-monitor: TCP Port closed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2008, 3, NULL, NULL, 'nmap-monitor: UDP Port opened');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2008, 4, NULL, NULL, 'nmap-monitor: UDP Port closed');
