DELETE FROM plugin WHERE id = "1596";
DELETE FROM plugin_sid where plugin_id = "1596";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1596, 1, 'Kismet', 'Kismet Wireless IDS');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 1, NULL, NULL, 'Kismet: Found new probed network' ,1, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 2, NULL, NULL, 'Kismet: Found new network' ,1, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 3, NULL, NULL, 'Kismet: Associated Client' ,1, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 4, NULL, NULL, 'Kismet: Deauthenticate/Disassociate flood detected' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 5, NULL, NULL, 'Kismet: Null Probe Response detected' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 6, NULL, NULL, 'Kismet: Unknown deauthentication reason code' ,2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 7, NULL, NULL, 'Kismet: Unknown disassociation reason code' ,2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 8, NULL, NULL, 'Kismet: Illegal SSID length' ,2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 9, NULL, NULL, 'Kismet: Suspicious client probing networks but never participating' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 10, NULL, NULL, 'Kismet: Possible AP Spoofing detected (channel change)' ,4, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 11, NULL, NULL, 'Kismet: Possible AP Spoofing detected (bss timestamp)' ,4, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 12, NULL, NULL, 'Kismet: Netstumbler detected' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 13, NULL, NULL, 'Kismet: Lucent link test detected' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 14, NULL, NULL, 'Kismet: Wellenteiter probe detected' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 15, NULL, NULL, 'Kismet: Broadcast disassociation detected' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 16, NULL, NULL, 'Kismet: Broadcast deathentication detected' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 17, NULL, NULL, 'Kismet: Airjack wireless hacking tool detected' ,4, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 18, NULL, NULL, 'Kismet: Suspicious traffic detected' ,2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1596, 19, NULL, NULL, 'Kismet: New IP detected' ,2, 2);
