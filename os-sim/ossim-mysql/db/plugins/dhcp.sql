-- MS DHCP
-- plugin_id: 1584
--

DELETE FROM plugin WHERE id = 1584;
DELETE FROM plugin_sid WHERE plugin_id=1584;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1584, 1, 'DHCP', 'Microsoft DHCP Service Activity');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 33, NULL, NULL, 1, 1, 'The log was started.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 1, NULL, NULL, 1, 1, 'The log was stopped.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 2, NULL, NULL, 1, 1, 'The log was temporarily paused due to low disk space.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 10, NULL, NULL, 1, 1, 'A new IP address was leased to a client.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 11, NULL, NULL, 1, 1, 'A lease was renewed by a client.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 12, NULL, NULL, 1, 1, 'A lease was released by a client.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 13, NULL, NULL, 1, 1, 'An IP address was found to be in use on the network.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 14, NULL, NULL, 1, 1, 'A lease request could not be satisfied because the scope s address pool was exhausted.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 15, NULL, NULL, 1, 1, 'A lease was denied.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 16, NULL, NULL, 1, 1, 'A lease was deleted.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 17, NULL, NULL, 1, 1, 'A lease was expired.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 20, NULL, NULL, 1, 1, 'A BOOTP address was leased to a client.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 21, NULL, NULL, 1, 1, 'A dynamic BOOTP address was leased to a client.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 22, NULL, NULL, 1, 1, 'A BOOTP request could not be satisfied because the scopes address pool for BOOTP was exhausted.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 23, NULL, NULL, 1, 1, 'A BOOTP IP address was deleted after checking to see it was not in use.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 24, NULL, NULL, 1, 1, 'IP address cleanup operation has began.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 25, NULL, NULL, 1, 1, 'IP address cleanup statistics.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 30, NULL, NULL, 1, 1, 'DNS update request to the named DNS server.');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 31, NULL, NULL, 1, 1, 'DNS update failed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1584, 32, NULL, NULL, 1, 1, 'DNS update successful');
