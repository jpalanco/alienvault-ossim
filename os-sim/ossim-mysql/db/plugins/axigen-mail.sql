-- axigen-mail
-- plugin_id: 1664

DELETE FROM plugin WHERE id = "1664";
DELETE FROM plugin_sid where plugin_id = "1664";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1664, 1, 'axigen', 'Axigen Email Server');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 1, NULL, NULL, 'Axigen CLI Informational', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 2, NULL, NULL, 'Axigen DNR Informational', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 3, NULL, NULL, 'Axigen PROCESSING Informational', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 23, NULL, NULL, 'Axigen PROCESSING Error', 4, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 43, NULL, NULL, 'Axigen PROCESSING Warning', 2, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 4, NULL, NULL, 'Axigen SMTP-OUT Informational', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 5, NULL, NULL, 'Axigen WEBADMIN Informational', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 45, NULL, NULL, 'Axigen WEBADMIN Warning', 2, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 6, NULL, NULL, 'Axigen WEBMAIL Informational', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 26, NULL, NULL, 'Axigen WEBMAIL Error', 4, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 46, NULL, NULL, 'Axigen WEBMAIL Warning', 2, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1664, 99, NULL, NULL, 'Axigen generic event', 1, 1);
