-- BMC
-- plugin_id: 90005

-- DELETE FROM plugin WHERE id = "90005";
-- DELETE FROM plugin_sid where plugin_id = "90005";

-- Logon=1
-- Miscellaneous=2
-- TransactionStart=3
-- Successful=10
-- Failed=11


INSERT IGNORE INTO plugin (id, type, name, description) VALUES (90005, 1, 'SAP', 'SAP Software');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90005, 1, NULL, NULL, 'SAP: USERDATA3', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90005, 2, NULL, NULL, 'SAP: USERDATA3', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90005, 3, NULL, NULL, 'SAP: USERDATA3', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90005, 10, NULL, NULL, 'SAP: USERDATA3', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90005, 11, NULL, NULL, 'SAP: USERDATA3', 2, 3);
