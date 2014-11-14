-- Cisco ACS 
-- plugin_id: 1594

DELETE FROM plugin WHERE id = "1594";
DELETE FROM plugin_sid where plugin_id = "1594";
INSERT INTO plugin (id, type, name, description) VALUES (1594, 1, 'cisco-acs-sidb', 'Cisco-ACS-4-SIDB ');
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 1,  2, 24, NULL, 'PassedAuth: Cisco ACS passed authentications.', 1 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id,class_id, name, priority, reliability) VALUES (1594, 2,  2, 25, NULL, 'FailedAuth: Cisco ACS failed attempts.', 3 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 3,  2, 214, NULL, 'RADIUSAcc: Cisco ACS RADIUS accounting.', 1 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 4,  2, 214, NULL, 'TACACSAcc: Cisco ACS TACACS+ accounting.', 1 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 5,  11, 187, NULL, 'TACACSAdmin: Cisco ACS TACACS+ administration.', 2 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 6,  2, 214, NULL, 'VoIPAcc: Cisco ACS VoIP accounting.', 1 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 11, 11, 138, NULL, 'BackRestore: ACS backup and restore log messages.', 2 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 12, 11, 138, NULL, 'Replication: ACS database replication log messages.', 2 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 13, 2, 89, NULL, 'AdminAudit: ACS administration audit log messages.', 2 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 14, 2, 214, NULL, 'PassChanges: ACS user password changes log messages.', 2 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 15, 11, 138, NULL, 'ServiceMon: ACS service monitoring log messages.', 3 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 16, 11, 138, NULL, 'RDBMSSync: ACS RDBMS Synchronization Audit log messages.', 2 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 17, 2, 89, NULL, 'ApplAdmin: ACS Appliance Administration Audit log messages.', 2 , 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, class_id, name, priority, reliability) VALUES (1594, 99999, NULL, NULL, NULL, 'ACSGenericEvent: Better parsing might be required at agent level', 1 , 1);
