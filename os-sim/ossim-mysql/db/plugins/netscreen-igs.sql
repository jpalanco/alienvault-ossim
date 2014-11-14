-- netscreen-igs
-- plugin_id: 1635

DELETE FROM plugin WHERE id = '1635';
DELETE FROM plugin_sid where plugin_id = '1635';

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1635, 1, 'netscreen-igs', 'Netscreen Device');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 1, NULL, NULL, 'netscreen-igs: Generic message');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 2, NULL, NULL, 'netscreen-igs: Syn Flood from SRC_IP to DST_IP:DST_PORT');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 3, NULL, NULL, 'netscreen-igs: SNMP request unknown community FROM SRC_IP:SRC_PORT');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 4, NULL, NULL, 'netscreen-igs: System configuration saved from SRC_IP by USERNAME');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 5, NULL, NULL, 'netscreen-igs: IP added to zone by USERNAME from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 6, NULL, NULL, 'netscreen-igs: Policy modified by USERNAME from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 7, NULL, NULL, 'netscreen-igs: Transport protocol for syslog was changed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 8, NULL, NULL, 'netscreen-igs: System clock updated');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 9, NULL, NULL, 'netscreen-igs: Admin USERNAME logged out from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 10, NULL, NULL, 'netscreen-igs: Local admin authentication failed for USERNAME');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 11, NULL, NULL, 'netscreen-igs: Admin USERNAME logged in from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 12, NULL, NULL, 'netscreen-igs: Environment variable changed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 13, NULL, NULL, 'netscreen-igs: Remote admin USERNAME authentication failed from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 14, NULL, NULL, 'netscreen-igs: IP deleted from zone by USERNAME from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 15, NULL, NULL, 'netscreen-igs: Service added to policy USERDATA3 by USERNAME from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 16, NULL, NULL, 'netscreen-igs: Policy USERDATA3 added by USERNAME from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 17, NULL, NULL, 'netscreen-igs: Policy USERDATA3 disabled by USERNAME from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 18, NULL, NULL, 'netscreen-igs: Source USERDATA2 added to policy USERDATA3 by USERNAME from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 19, NULL, NULL, 'netscreen-igs: Destination USERDATA2 added to policy USERDATA3 by USERNAME from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 20, NULL, NULL, 'netscreen-igs: Policy USERDATA3 deleted by USERNAME from SRC_IP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 21, NULL, NULL, 'netscreen-igs: Policy USERDATA3 has been moved by USERNAME');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1635, 22, NULL, NULL, 'netscreen-igs: Syslog has been enabled');
