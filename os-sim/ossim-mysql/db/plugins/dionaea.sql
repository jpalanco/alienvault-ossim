-- dionaea 
-- plugin_id: 1669

DELETE FROM plugin WHERE id="1669";
DELETE FROM plugin_sid WHERE plugin_id="1669";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1669, 1, "dionaea", "Dionaea Honeypot");
INSERT IGNORE INTO plugin_sid(plugin_id, sid, name) VALUES (1669, 1, "Dionaea: Incoming Connection");
INSERT IGNORE INTO plugin_sid(plugin_id, sid, name) VALUES (1669, 2, "Dionaea: Malware detected");
