-- whois
-- plugin_id: 2010

DELETE FROM plugin WHERE id = "2010";
DELETE FROM plugin_sid where plugin_id = "2010";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (2010, 2, 'whois', 'Whois: Internet domain name and network number directory service');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2010, 1, NULL, NULL, 'whois-monitor: Country request');
