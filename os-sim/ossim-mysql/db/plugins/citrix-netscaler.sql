DELETE FROM plugin WHERE id = "1678";
DELETE FROM plugin_sid where plugin_id = "1678";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1678, 1, 'citrix-netscaler', 'Citrix NetScaler');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1678, 101, 1, NULL, NULL, 'citrix-netscaler: UI Command Executed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1678, 201, 1, NULL, NULL, 'citrix-netscaler: SNMP Trap Sent');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1678, 301, 1, NULL, NULL, 'citrix-netscaler: EVENT Start Save Config');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1678, 302, 1, NULL, NULL, 'citrix-netscaler: EVENT Stop Save Config');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1678, 303, 1, NULL, NULL, 'citrix-netscaler: EVENT State Change');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1678, 304, 1, NULL, NULL, 'citrix-netscaler: EVENT Device Up');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1678, 305, 1, NULL, NULL, 'citrix-netscaler: EVENT Route Up');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1678, 999, 1, NULL, NULL, 'citrix-netscaler: Generic event');
