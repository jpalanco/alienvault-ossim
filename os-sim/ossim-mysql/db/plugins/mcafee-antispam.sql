-- Mcafee-Antispam
-- plugin_id:1618

DELETE FROM plugin WHERE id="1618";
DELETE FROM plugin_sid WHERE plugin_id="1618";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1618, 1, "Mcafee-AntiSpam", "Mcafee AntiSpam");

INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1618, 102, null, null, "Mcafee AntiSpam: Spam received", 2, 3);
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1618, 108, null, null, "Mcafee AntiSpam: Message quarantined", 2, 3);

