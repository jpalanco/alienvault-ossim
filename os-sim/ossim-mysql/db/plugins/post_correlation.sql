-- post_correlation directives
-- plugin_id: 20505

DELETE FROM plugin WHERE id = "20505";
DELETE FROM plugin_sid where plugin_id = "20505";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (20505, 1, 'post_correlation_directive', 'Alienvault post correlation engine for SQl queries');
-- All the plugin_sids will be inserted by the alienvault-server. The framework will send the list of directives to the server

--
-- post_correlation
-- plugin_id: 12001
-- DELETE FROM plugin WHERE id = "12001";
-- DELETE FROM plugin_sid where plugin_id = "12001";


INSERT IGNORE INTO plugin (id, type, name, description) VALUES (12001, 1, 'post_correlation', 'Alienvault post correlation engine');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 1, NULL, NULL, 'New suspicious host detected' ,3, 2);

REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 51, NULL, NULL, 'Too many (200+) different Exploit events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 501, NULL, NULL, 'Too many different (3+) Exploit events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 52, NULL, NULL, 'Too many (200+) different Authentication events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 502, NULL, NULL, 'Too many different (3+) Authentication events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 53, NULL, NULL, 'Too many (200+) different Access events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 503, NULL, NULL, 'Too many different (3+) Access events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 54, NULL, NULL, 'Too many (200+) different Malware events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 504, NULL, NULL, 'Too many different (3+) Malware events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 55, NULL, NULL, 'Too many (200+) different Policy events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 505, NULL, NULL, 'Too many different (3+) Policy events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 56, NULL, NULL, 'Too many (200+) different Denial_Of_Service events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 506, NULL, NULL, 'Too many different (3+) Denial_Of_Service events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 57, NULL, NULL, 'Too many (200+) different Suspicious events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 507, NULL, NULL, 'Too many different (3+) Suspicious events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 58, NULL, NULL, 'Too many (200+) different Network events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 508, NULL, NULL, 'Too many different (3+) Network events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 59, NULL, NULL, 'Too many (200+) different Recon events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 509, NULL, NULL, 'Too many different (3+) Recon events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 60, NULL, NULL, 'Too many (200+) different Info events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 510, NULL, NULL, 'Too many different (3+) Info events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 61, NULL, NULL, 'Too many (200+) different System events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 511, NULL, NULL, 'Too many different (3+) System events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 62, NULL, NULL, 'Too many (200+) different Antivirus events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 512, NULL, NULL, 'Too many different (3+) Antivirus events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 63, NULL, NULL, 'Too many (200+) different Application events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 513, NULL, NULL, 'Too many different (3+) Application events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 64, NULL, NULL, 'Too many (200+) different Voip events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 514, NULL, NULL, 'Too many different (3+) Voip events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 65, NULL, NULL, 'Too many (200+) different Alert events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 515, NULL, NULL, 'Too many different (3+) Alert events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 66, NULL, NULL, 'Too many (200+) different Availability events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 516, NULL, NULL, 'Too many different (3+) Availability events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 67, NULL, NULL, 'Too many (200+) different Wireless events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 517, NULL, NULL, 'Too many different (3+) Wireless events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 68, NULL, NULL, 'Too many (200+) different Inventory events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 518, NULL, NULL, 'Too many different (3+) Inventory events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 69, NULL, NULL, 'Too many (200+) different Honeypot events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 519, NULL, NULL, 'Too many different (3+) Honeypot events in same category' ,5, 3);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 70, NULL, NULL, 'Too many (200+) different Database events in same category' ,3, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (12001, 520, NULL, NULL, 'Too many different (3+) Database events in same category' ,5, 3);

