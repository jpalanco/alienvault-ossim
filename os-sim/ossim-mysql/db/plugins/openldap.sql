-- OPENLDAP
-- plugin_id: 1586

DELETE FROM plugin where id=1586;
DELETE FROM plugin_sid where plugin_id=1586;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1586, 1, 'openldap', 'OpenLDAP');

INSERT IGNORE INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1586, 1, NULL, NULL, 1, 3, 'OpenLDAP: Authentication Failure');
INSERT IGNORE INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1586, 2, NULL, NULL, 1, 3, 'OpenLDAP: Authentication Success');
