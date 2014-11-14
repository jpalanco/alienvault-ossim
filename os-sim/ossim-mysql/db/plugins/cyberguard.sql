-- Cyberguard
-- plugin_id: 1575

DELETE FROM plugin where id=1575;
DELETE FROM plugin_sid where plugin_id=1575;

INSERT IGNORE INTO plugin (id, type, name, description) values (1575, 1, 'cyberguard', 'Snort Rules');

INSERT IGNORE INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1575, 1, NULL, NULL, 1, 3, 'Firewall Cyberguard: DENY');
INSERT IGNORE INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1575, 2, NULL, NULL, 1, 3, 'Firewall Cyberguard: DROP');
INSERT IGNORE INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1575, 3, NULL, NULL, 1, 3, 'Firewall Cyberguard: REJECT');
INSERT IGNORE INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1575, 4, NULL, NULL, 1, 3, 'Firewall Cyberguard: ALLOW');
INSERT IGNORE INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1575, 5, NULL, NULL, 1, 3, 'Firewall Cyberguard: ACCEPT');


