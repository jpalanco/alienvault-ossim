-- suhosin
-- plugin_id: 1685

DELETE FROM plugin WHERE id = 1685;
DELETE FROM plugin_sid where plugin_id = 1685;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1685, 1, 'Suhosin', 'PHP advanced protection system');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1685, 1, NULL, NULL, 'Suhosin: Include filename is an URL that is not allowed', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1685, 2, NULL, NULL, 'Suhosin: ASCII-NUL chars not allowed within request variables', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1685, 3, NULL, NULL, 'Suhosin: Maximum execution depth reached', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1685, 4, NULL, NULL, 'Suhosin: Tried to register forbidden variable', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1685, 5, NULL, NULL, 'Suhosin: Script tried to increase memory_limit', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1685, 6, NULL, NULL, 'Suhosin: Configured request variable value length limit exceeded', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1685, 7, NULL, NULL, 'Suhosin: Overflow detected - canary mismatch', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1685, 8, NULL, NULL, 'Suhosin: Overflow detected - corrupted linked list', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1685, 9, NULL, NULL, 'Suhosin: Include filename is too long', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1685, 10, NULL, NULL, 'Suhosin: Generic alert', 1, 1);