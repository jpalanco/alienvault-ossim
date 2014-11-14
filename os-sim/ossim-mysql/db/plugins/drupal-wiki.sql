-- drupal-wiki 
-- plugin_id: 1675

DELETE FROM plugin WHERE id = "1675";
DELETE FROM plugin_sid where plugin_id = "1675";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1675, 1, 'drupal-wiki', 'Drupal Wiki');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) values (1675, 1 , "Drupal Wiki: CRON");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) values (1675, 2 , "Drupal Wiki: Search");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) values (1675, 3 , "Drupal Wiki: Content");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) values (1675, 4 , "Drupal Wiki: User");
