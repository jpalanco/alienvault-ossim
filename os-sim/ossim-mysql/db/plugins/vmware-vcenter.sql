-- vmware-vcenter 
-- plugin_id: 1658

DELETE FROM plugin WHERE id = "1658";
DELETE FROM plugin_sid where plugin_id = "1658";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES(1658, 1, "vmware-vcenter", "VMware Vcenter");

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES(1658, 1, NULL, NULL, 1, 1, "VMware Vcenter: Virtual machine start");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES(1658, 2, NULL, NULL, 1, 1, "VMware Vcenter: Successful authentication");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES(1658, 3, NULL, NULL, 1, 1, "VMware Vcenter: Authentication failure");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES(1658, 4, NULL, NULL, 1, 1, "VMware Vcenter: Session opened");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES(1658, 5, NULL, NULL, 1, 1, "VMware Vcenter: Session closed");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES(1658, 6, NULL, NULL, 1, 1, "VMware Vcenter: Disconnected client");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES(1658, 7, NULL, NULL, 1, 1, "VMware Vcenter: Dissociate dvPort");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES(1658, 8, NULL, NULL, 1, 1, "VMware Vcenter: Disabled port");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES(1658, 1999999999, NULL, NULL, 1, 1, "VMware Vcenter: Generic event");
