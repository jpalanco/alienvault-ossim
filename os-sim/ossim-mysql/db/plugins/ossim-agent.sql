-- ossim-agent
-- plugin_id: 6001

DELETE FROM plugin WHERE id = "6001";
DELETE FROM plugin_sid where plugin_id = "6001";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (6001, 1, 'ossim-agent', 'ossim-agent');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (6001, 1, 'ossim-agent: error connecting to server');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (6001, 2, 'ossim-agent: a process has been started');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (6001, 3, 'ossim-agent: a process has been stopped');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (6001, 4, 'ossim-agent: error starting a process');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (6001, 5, 'ossim-agent: error stopping a process');
