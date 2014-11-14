-- ping-monitor
-- plugin_id: 2009

DELETE FROM plugin WHERE id = "2009";
DELETE FROM plugin_sid where plugin_id = "2009";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (2009, 2, 'ping-monitor', 'ping-monitor: Check if a host is alive or unreachable');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2009, 1, NULL, NULL, 'ping-monitor: host alive');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2009, 2, NULL, NULL, 'ping-monitor: host unreachable');
