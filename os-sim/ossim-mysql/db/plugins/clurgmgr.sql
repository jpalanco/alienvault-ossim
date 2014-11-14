-- clurmgmr
-- plugin_id: 1528

DELETE FROM plugin WHERE id = "1528";
DELETE FROM plugin_sid where plugin_id = "1528";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1528, 1, 'clurmgmr', 'Cluster Service Manager Daemon');

-- Debug messages
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 100, NULL, NULL, 'Clurmgmr: Debug event', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 101, NULL, NULL, 'Clurmgmr: (Debug) Checking ip adress', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 102, NULL, NULL, 'Clurmgmr: Ip adress present on interface', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 103, NULL, NULL, 'Clurmgmr: Link detected on interface', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 104, NULL, NULL, 'Clurmgmr: Local ping succeeded', 1);
-- Info messages
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 200, NULL, NULL, 'Clurmgmr: Info event', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 201, NULL, NULL, 'Clurmgmr: (Info) Executing process status', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 202, NULL, NULL, 'Clurmgmr: (Info) Removing IPv4 adress', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 203, NULL, NULL, 'Clurmgmr: (Info) Adding IPv4 adress', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 204, NULL, NULL, 'Clurmgmr: (Info) Unmounting partition', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 205, NULL, NULL, 'Clurmgmr: (Info) Mounting partition', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 206, NULL, NULL, 'Clurmgmr: (Info) Partition not mounted', 1);

-- Notice messages
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 300, NULL, NULL, 'Clurmgmr: Info event', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 301, NULL, NULL, 'Clurmgmr: (notice) Starting stopped service', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 302, NULL, NULL, 'Clurmgmr: (notice) Service started', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 303, NULL, NULL, 'Clurmgmr: (notice) Status returned generic error', 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 304, NULL, NULL, 'Clurmgmr: (notice) Service is recovering', 1);

-- Warning messages
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 400, NULL, NULL, 'Clurmgmr: Warning event', 2);

-- Error messages
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 500, NULL, NULL, 'Clurmgmr: Error event', 2);

-- Critical messages
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 600, NULL, NULL, 'Clurmgmr: Critical event', 3);

-- Emergency messages
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 700, NULL, NULL, 'Clurmgmr: Emergency event', 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority) VALUES (1528, 701, NULL, NULL, 'Clurmgmr: (emergency) Queorum dissolved', 3);
