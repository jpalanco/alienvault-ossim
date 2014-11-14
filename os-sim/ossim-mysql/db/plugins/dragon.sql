-- Enterasys Dragon
-- Plugin id: 1569

DELETE FROM plugin WHERE id = "1569";
DELETE FROM plugin_sid where plugin_id = "1569"; 

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1569, 1, 'dragon', 'Enterasys Dragon');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1569, 1, NULL, NULL, 'Dragon packet', 1, 3);
