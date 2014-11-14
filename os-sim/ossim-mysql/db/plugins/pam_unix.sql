-- pam_unix
-- plugin_id: 4004

DELETE FROM plugin WHERE id = "4004";
DELETE FROM plugin_sid where plugin_id = "4004";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (4004, 1, 'pam_unix', 'Pam Unix authentication mechanism');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (4004, 1, NULL, NULL, 'pam_unix: session opened');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (4004, 11, NULL, NULL, 'pam_unix: session closed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (4004, 2, NULL, NULL, 'pam_unix: authentication failure');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (4004, 3, NULL, NULL, 'pam_unix: X more authentication failures');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 4, NULL, NULL, 'adduser: User created' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 5, NULL, NULL, 'adduser: Group created' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 6, NULL, NULL, 'passwd: Password Changed' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 7, NULL, NULL, 'userdel: User deleted' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 8, NULL, NULL, 'userdel: Group deleted' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 9, NULL, NULL, 'userdel: Check pass' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 10, NULL, NULL, 'Unable open env file' ,3, 2);
