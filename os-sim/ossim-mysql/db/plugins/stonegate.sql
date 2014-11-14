-- Stonegate firewall
-- plugin_id: 1526

DELETE FROM plugin WHERE id = "1526";
DELETE FROM plugin_sid WHERE plugin_id = "1526";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1526, 1, 'stonegate', 'Stonegate Firewall');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 1, 'NULL', 'NULL', 1, 1, 'stonegate: Accounting event');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 2, 'NULL', 'NULL', 1, 1, 'stonegate: Authentication event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 3, 'NULL', 'NULL', 1, 1, 'stonegate: Blacklisting event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 4, 'NULL', 'NULL', 1, 1, 'stonegate: Cluster Daemon event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 5, 'NULL', 'NULL', 1, 1, 'stonegate: Cluster Protocol event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 6, 'NULL', 'NULL', 1, 1, 'stonegate: Connection Tracking event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 7, 'NULL', 'NULL', 1, 1, 'stonegate: Data Synchronization event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 8, 'NULL', 'NULL', 1, 1, 'stonegate: DHCP Client event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 9, 'NULL', 'NULL', 1, 1, 'stonegate: DHCP Relay event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 10, 'NULL', 'NULL', 1, 1, 'stonegate: Invalid event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 11, 'NULL', 'NULL', 1, 1, 'stonegate: IPsec event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 12, 'NULL', 'NULL', 1, 1, 'stonegate: License event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 13, 'NULL', 'NULL', 1, 1, 'stonegate: Load balancing filter event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 14, 'NULL', 'NULL', 1, 1, 'stonegate: Log Server event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 15, 'NULL', 'NULL', 1, 1, 'stonegate: Logging System event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 16, 'NULL', 'NULL', 1, 1, 'stonegate: Management event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 17, 'NULL', 'NULL', 1, 1, 'stonegate: Monitoring event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 18, 'NULL', 'NULL', 1, 1, 'stonegate: NetLink Incoming HA event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 19, 'NULL', 'NULL', 1, 1, 'stonegate: Network Address Translation event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 20, 'NULL', 'NULL', 1, 1, 'stonegate: Packet Filter event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 21, 'NULL', 'NULL', 1, 1, 'stonegate: Protocol Agent event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 22, 'NULL', 'NULL', 1, 1, 'stonegate: Server Pool event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 23, 'NULL', 'NULL', 1, 1, 'stonegate: SNMP Monitoring event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 24, 'NULL', 'NULL', 1, 1, 'stonegate: State Synchronization event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 25, 'NULL', 'NULL', 1, 1, 'stonegate: Syslog event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 26, 'NULL', 'NULL', 1, 1, 'stonegate: System event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 27, 'NULL', 'NULL', 1, 1, 'stonegate: Tester event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 28, 'NULL', 'NULL', 1, 1, 'stonegate: Undefined event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1526, 29, 'NULL', 'NULL', 1, 1, 'stonegate: User Defined event');


