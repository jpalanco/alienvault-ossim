-- ironport
-- plugin_id: 1591

DELETE FROM plugin WHERE id = "1591";
DELETE FROM plugin_sid where plugin_id = "1591";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1591, 1, 'ironport', 'IRON PORT log');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 1, NULL, NULL, 'IRON_PORT: Virus detected' ,1, 5);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 2, NULL, NULL, 'IRON_PORT: msg dropped by filter' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 3, NULL, NULL, 'IRON_PORT: spam quarantine' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 4, NULL, NULL, 'IRON_PORT: spam positive' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 5, NULL, NULL, 'IRON_PORT: invalid DNS Response' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 6, NULL, NULL, 'IRON_PORT: Output Mail - Dst' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 7, NULL, NULL, 'IRON_PORT: Output Mail - Src' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 8, NULL, NULL, 'IRON_PORT: Output Mail - Creating' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 9, NULL, NULL, 'IRON_PORT: Match Relaylist' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 10, NULL, NULL, 'IRON_PORT: Reverse dns no verified' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 11, NULL, NULL, 'IRON_PORT: Reverse dns verified' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 12, NULL, NULL, 'IRON_PORT: Input Connection' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 13, NULL, NULL, 'IRON_PORT: Closing Connection' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 14, NULL, NULL, 'IRON_PORT: Closing Connection' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 15, NULL, NULL, 'IRON_PORT: Rejected-List Match' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 16, NULL, NULL, 'IRON_PORT: Accepted-List Match' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 17, NULL, NULL, 'IRON_PORT: Delivery start' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 18, NULL, NULL, 'IRON_PORT: Message ID' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 19, NULL, NULL, 'IRON_PORT: Message Subject' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 20, NULL, NULL, 'IRON_PORT: Message size' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 21, NULL, NULL, 'IRON_PORT: Recipients match' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 22, NULL, NULL, 'IRON_PORT: Message too big for scanning' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 23, NULL, NULL, 'IRON_PORT: Message finished OK' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 24, NULL, NULL, 'IRON_PORT: Message finished ABORTED' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 25, NULL, NULL, 'IRON_PORT: Message aborted' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 26, NULL, NULL, 'IRON_PORT: Antivirus negative' ,1, 5);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 27, NULL, NULL, 'IRON_PORT: Sophos CLEAN' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 28, NULL, NULL, 'IRON_PORT: Invalid DNS response' ,1, 1);
