-- VMware ESXi 
-- plugin_id=1686
--
-- $Id: esxi.sql,v 1.0 17/05/2012

DELETE FROM plugin WHERE id = "1686";
DELETE FROM plugin_sid where plugin_id = "1686";
INSERT INTO plugin(id, type, name, description) VALUES (1686, 1, 'vmware-esxi', 'VMware ESXi server');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 1, NULL, NULL, 'vmware-esxi: Warning');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 2, NULL, NULL, 'vmware-esxi: Error');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 3, NULL, NULL, 'vmware-esxi: Info');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 4, NULL, NULL, 'vmware-esxi: Verbose');

-- #7989
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 10, NULL, NULL, 'vmware-esxi: HostCtl Exception');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 11, NULL, NULL, 'vmware-esxi: Version Info');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 9995, NULL, NULL, 'vmware-esxi: vthread message');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 9996, NULL, NULL, 'vmware-esxi: vmkwarning message');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 9997, NULL, NULL, 'vmware-esxi: vmkernel message');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 9998, NULL, NULL, 'vmware-esxi: Debug Traceback');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1686, 9999, NULL, NULL, 'vmware-esxi: General');
