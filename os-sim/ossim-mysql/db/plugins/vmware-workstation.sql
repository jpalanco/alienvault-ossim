-- vmware workstation
-- plugin_id: 1562

DELETE FROM plugin WHERE id = "1562";
DELETE FROM plugin_sid where plugin_id = "1562";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES(1562, 1, "vmware_workstation", "Vmware Workstation");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1562, 1, null, null, 1, 1, "wmware_workstation: Incoming Connection");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1562, 2, null, null, 1, 1, "wmware_workstation: New user session");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1562, 3, null, null, 1, 1, "wmware_workstation: User Session Deleted");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1562, 4, null, null, 1, 1, "wmware_workstation: Virtual Machine Start");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1562, 5, null, null, 1, 1, "wmware_workstation: Virtual Machine Pause,Stop");
