-- ossim OS listing
-- plugin_id: 5001

DELETE FROM plugin WHERE id = "5001";
DELETE FROM plugin_sid where plugin_id = "5001";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (5001, 4, "os", "Operating Systems");

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 1, NULL, NULL, 1, 1, "Windows");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 2, NULL, NULL, 1, 1, "Linux");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 3, NULL, NULL, 1, 1, "Cisco");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 4, NULL, NULL, 1, 1, "BSD");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 5, NULL, NULL, 1, 1, "FreeBSD");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 6, NULL, NULL, 1, 1, "NetBSD");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 7, NULL, NULL, 1, 1, "OpenBSD");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 8, NULL, NULL, 1, 1, "HP-UX");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 9, NULL, NULL, 1, 1, "Solaris");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 10, NULL, NULL, 1, 1, "Macos");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 11, NULL, NULL, 1, 1, "Plan9");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 12, NULL, NULL, 1, 1, "SCO");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 13, NULL, NULL, 1, 1, "AIX");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 14, NULL, NULL, 1, 1, "UNIX");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 15, NULL, NULL, 1, 1, "SunOS");
