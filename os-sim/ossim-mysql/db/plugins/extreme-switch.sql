DELETE FROM plugin WHERE id = "1672";
DELETE FROM plugin_sid where plugin_id = "1672";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1672, 1, 'extreme-switch', 'Extreme Switch');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1672, 10, 1, NULL, NULL, 'Extreme Switch - Login Success');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1672, 11, 1, NULL, NULL, 'Extreme Switch -  Login Failure');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1672, 12, 1, NULL, NULL, 'Extreme Switch -  Logout');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1672, 20, 1, NULL, NULL, 'Extreme Switch - Link UP');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1672, 21, 1, NULL, NULL, 'Extreme Switch - Link DOWN');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1672, 50, 1, NULL, NULL, 'Extreme Switch - Clock Sync');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1672, 2000000000, 1, NULL, NULL, 'Extreme Switch - Generic Event');
