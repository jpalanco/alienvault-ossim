-- forensics-db-1
-- plugin_id: 1801

DELETE FROM plugin WHERE id = "1801";
DELETE FROM plugin_sid where plugin_id = "1801";


INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1801, 1, 'forensics-db-1', 'A post correlation plugin which queries the database on a regular basis in order to catch worms the correlation engine might have missed.');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1801, 1, NULL, NULL, 'forensics-db-1: Too many destinations for a single origin host' ,5, 5);
