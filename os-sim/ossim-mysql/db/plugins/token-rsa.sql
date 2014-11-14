-- BMC
-- plugin_id: 90005

-- DELETE FROM plugin WHERE id = "90005";
-- DELETE FROM plugin_sid where plugin_id = "90005";

-- Authentication Success=1
-- Authorization Success=2
-- Check Resource=3
-- Returned Groups For User=4
-- Server Test=5
-- User -DPM Created=6
-- User -DPM Failed=7
-- _DEFAULT_=100

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (90008, 1, 'RSA-DPM', 'RSA DPM');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90008, 1, NULL, NULL, 'RSA: USERNAME USERDATA3 ', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90008, 2, NULL, NULL, 'RSA-DPM: USERNAME USERDATA3', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90008, 3, NULL, NULL, 'RSA-DPM: USERDATA3', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90008, 4, NULL, NULL, 'RSA-DPM: USERDATA3', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90008, 5, NULL, NULL, 'RSA-DPM: USERDATA3', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90008, 6, NULL, NULL, 'RSA-DPM: USERDATA3', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90008, 7, NULL, NULL, 'RSA-DPM: USERDATA3', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90008, 100, NULL, NULL, 'RSA-DPM: USERDATA3', 2, 2);
