-- Trend Micro
-- plugin_id: 1574

DELETE FROM plugin WHERE id="1574";
DELETE FROM plugin_sid WHERE plugin_id="1574";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1574, 1, 'trendmicro', 'Trend Micro Messaging Security');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1574, 1, NULL, NULL, 'Trend Micro: Virus found', 1, 3);
