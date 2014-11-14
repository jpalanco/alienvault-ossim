-- suricata-http
-- type: detector
-- plugin_id: 8201

DELETE FROM plugin WHERE id = "8201";
DELETE FROM plugin_sid where plugin_id = "8201";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (8201, 1, 'suricata-http', 'Suricata HTTP Event');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, subcategory_id, priority, reliability, name) VALUES (8201, 200, NULL, NULL, 1, 1, 'suricata-http: 200');
