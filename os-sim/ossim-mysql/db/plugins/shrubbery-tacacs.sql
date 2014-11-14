-- shrubbery-tacacs
-- plugin_id: 1676

DELETE FROM plugin WHERE id = 1676;
DELETE FROM plugin_sid where plugin_id = 1676;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1676, 1, 'shrubbery-tacacs', 'Shrubbery TACACS+');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1676, 10, NULL, NULL, 'shrubbery-tacacs: Login Success');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1676, 20, NULL, NULL, 'shrubbery-tacacs: Login Failure');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1676, 30, NULL, NULL, 'shrubbery-tacacs: Command Executed entry');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1676, 99, NULL, NULL, 'shrubbery-tacacs: Minutia');
