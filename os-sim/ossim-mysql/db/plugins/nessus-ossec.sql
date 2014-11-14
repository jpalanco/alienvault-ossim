-- nessus-ossec
-- plugin_id: 90003

DELETE FROM plugin WHERE id = "90003";
DELETE FROM plugin_sid where plugin_id = "90003";


INSERT IGNORE INTO plugin (id, type, name, description) VALUES (90003, 1, 'nessus-info', 'nessus-ossec');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 1, NULL, NULL, 'nessus-ossec: Critical Accept Risk', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 2, NULL, NULL, 'nessus-ossec: Info  Accept Risk', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 3, NULL, NULL, 'nessus-ossec: Warning Accept Risk', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 4, NULL, NULL, 'nessus-ossec: Critical Alerts', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 5, NULL, NULL, 'nessus-ossec: Info  Alert', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 6, NULL, NULL, 'nessus-ossec: Warning Alert', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 7, NULL, NULL, 'nessus-ossec: Critical Asset', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 8, NULL, NULL, 'nessus-ossec: Info  Asset', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 9, NULL, NULL, 'nessus-ossec: Warning Asset', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 10, NULL, NULL, 'nessus-ossec: Critical Authentication', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 11, NULL, NULL, 'nessus-ossec: Info  Authentication', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 12, NULL, NULL, 'nessus-ossec: Warning Authentication', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 13, NULL, NULL, 'nessus-ossec: Critical Credentials', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 14, NULL, NULL, 'nessus-ossec: Info  Credentials', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 15, NULL, NULL, 'nessus-ossec: Warning Credentials', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 16, NULL, NULL, 'nessus-ossec: Critical Dashboard', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 17, NULL, NULL, 'nessus-ossec: Info  Dashboard', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 18, NULL, NULL, 'nessus-ossec: Warning Dashboard', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 19, NULL, NULL, 'nessus-ossec: Critical Database', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 20, NULL, NULL, 'nessus-ossec: Info  Database', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 21, NULL, NULL, 'nessus-ossec: Warning Database', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 22, NULL, NULL, 'nessus-ossec: Critical Error', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 23, NULL, NULL, 'nessus-ossec: Info  Error', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 24, NULL, NULL, 'nessus-ossec: Warning Error', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 25, NULL, NULL, 'nessus-ossec: Critical Import', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 26, NULL, NULL, 'nessus-ossec: Info  Import', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 27, NULL, NULL, 'nessus-ossec: Warning Import', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 28, NULL, NULL, 'nessus-ossec: Critical Message', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 29, NULL, NULL, 'nessus-ossec: Info  Message', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 30, NULL, NULL, 'nessus-ossec: Warning Message', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 31, NULL, NULL, 'nessus-ossec: Critical Policy', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 32, NULL, NULL, 'nessus-ossec: Info  Policy', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 33, NULL, NULL, 'nessus-ossec: Warning Policy', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 34, NULL, NULL, 'nessus-ossec: Critical Prepare Assets', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 35, NULL, NULL, 'nessus-ossec: Info  Prepare Assets', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 36, NULL, NULL, 'nessus-ossec: Warning Prepare Assets', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 37, NULL, NULL, 'nessus-ossec: Critical Query', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 38, NULL, NULL, 'nessus-ossec: Info  Query', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 39, NULL, NULL, 'nessus-ossec: Warning Query', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 40, NULL, NULL, 'nessus-ossec: Critical Report', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 41, NULL, NULL, 'nessus-ossec: Info Report', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 42, NULL, NULL, 'nessus-ossec: Warning Report', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 43, NULL, NULL, 'nessus-ossec: Critical Repository', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 44, NULL, NULL, 'nessus-ossec: Info  Repository', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 45, NULL, NULL, 'nessus-ossec: Warning Repository', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 46, NULL, NULL, 'nessus-ossec: Critical Scan', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 47, NULL, NULL, 'nessus-ossec: Info Scan', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 48, NULL, NULL, 'nessus-ossec: Warning Scan', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 49, NULL, NULL, 'nessus-ossec: Critical Scan Result ', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 50, NULL, NULL, 'nessus-ossec: Info Scan Result', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 51, NULL, NULL, 'nessus-ossec: Warning Scan Result', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 52, NULL, NULL, 'nessus-ossec: Critical Ticket', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 53, NULL, NULL, 'nessus-ossec: Info Ticket', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 54, NULL, NULL, 'nessus-ossec: Warning Ticket', 2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 55, NULL, NULL, 'nessus-ossec: Critical User', 3, 4 );
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 56, NULL, NULL, 'nessus-ossec: Info User', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 57, NULL, NULL, 'nessus-ossec: Warning User', 2, 3);

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 101, NULL, NULL, 'nessus-ossec: Login event', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (90003, 102, NULL, NULL, 'nessus-ossec: Logout event', 2, 2);

