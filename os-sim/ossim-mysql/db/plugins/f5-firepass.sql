DELETE FROM plugin WHERE id = "1674";
DELETE FROM plugin_sid where plugin_id = "1674";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1674, 1, 'f5-firepass', 'F5 Firepass Network');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1674, 1, 1, NULL, NULL, 'F5 Firepass Maintenance - Backup Aborted');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1674, 2, 1, NULL, NULL, 'F5 Firepass Network - Session Disconnected');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1674, 3, 1, NULL, NULL, 'F5 Firepass Security - Concurrent Logins Exceeded');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1674, 4, 1, NULL, NULL, 'F5 Firepass Security - User Invalid');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1674, 5, 1, NULL, NULL, 'F5 Firepass Security - User Logged In');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1674, 6, 1, NULL, NULL, 'F5 Firepass ZZZ GarbageCollection');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1674, 7, 1, NULL, NULL, 'F5 Firepass ZZZ Kernel');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1674, 8, 1, NULL, NULL, 'F5 Firepass ZZZ Maintenance');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, reliability, category_id, class_id, name) VALUES (1674, 9, 1, NULL, NULL, 'F5 Firepass ZZZ NetworkAccess');
