-- nagios
-- plugin_id: 1525
-- plugin_id: 2007

DELETE FROM plugin WHERE id = "1525";
DELETE FROM plugin WHERE id = "2007";
DELETE FROM plugin_sid where plugin_id = "1525";
DELETE FROM plugin_sid where plugin_id = "2007";


INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1525, 1, 'nagios', 'Nagios: host/service/network monitoring and management system');
INSERT IGNORE INTO plugin (id, type, name, description) VALUES (2007, 2, 'nagios-monitor', 'Nagios');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 1, 2, 3, 'nagios: host alert - hard down');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 2, 1, 3, 'nagios: host alert - hard up');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 3, 2, 3, 'nagios: host alert - hard unreachable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 4, 1, 1, 'nagios: host alert - soft down');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 5, 0, 1, 'nagios: host alert - soft up');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 6, 1, 1, 'nagios: host alert - soft unreachable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 7, 2, 3, 'nagios: service alert - hard critical');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 8, 1, 3, 'nagios: service alert - hard ok');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 9, 1, 2, 'nagios: service alert - hard unknown');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 10, 1, 2, 'nagios: service alert - hard warning');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 11, 1, 1, 'nagios: service alert - soft critical');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 12, 0, 1, 'nagios: service alert - soft ok');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 13, 1, 1, 'nagios: service alert - soft unknown');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 14, 1, 1, 'nagios: service alert - soft warning');
