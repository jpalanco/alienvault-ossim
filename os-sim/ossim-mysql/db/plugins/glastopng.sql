-- GlastopfNG
-- plugin_id: 1667

DELETE FROM plugin WHERE id = "1667";
DELETE FROM plugin_sid where plugin_id = "1667";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1667, 1, 'GlastopfNG', 'GlastopfNG: Web Honeypot');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1667, 1, NULL, NULL, 'GlastopfNG: Attack detected', 3, 2);

