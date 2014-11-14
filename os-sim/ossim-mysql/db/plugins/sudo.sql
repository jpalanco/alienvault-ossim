-- sudo
-- plugin_id: 4005

DELETE FROM plugin WHERE id = "4005";
DELETE FROM plugin_sid where plugin_id = "4005";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (4005, 1, 'sudo', 'Sudo allows users to run programs with the security privileges of another user in a secure manner');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 1, NULL, NULL, 'sudo: Failed su ' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 2, NULL, NULL, 'sudo: Successful su' ,1, 2);

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 3, NULL, NULL, 'sudo: Command executed' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 4, NULL, NULL, 'sudo: User not in sudoers' ,3, 2);

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 5, NULL, NULL, 'sudo: Session opened' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 6, NULL, NULL, 'sudo: Session closed' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 7, NULL, NULL, 'sudo: Authentication failure' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 8, NULL, NULL, 'sudo: Command not allowed' ,3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 9, NULL, NULL, 'sudo: Incorrect password attempts' ,3, 2);
