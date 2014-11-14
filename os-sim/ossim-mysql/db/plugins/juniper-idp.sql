-- Juniper-idp
-- plugin_id:1693

DELETE FROM plugin WHERE id="1693";
DELETE FROM plugin_sid WHERE plugin_id="1693";

INSERT IGNORE INTO plugin(id, type, name, description) VALUES (1693, 2, 'IDP', 'Juniper IDP');

-- INSERT IGNORE INTO 'plugin_sid' ('plugin_id', 'sid', 'category_id', -- 'class_id', 'name', 'priority', 'reliability') VALUES (1693, 0, NULL, -- NULL, 'Juniper-IDP INFO: ', 0, 0);

INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES 
(1693, 1, NULL, NULL, "IDP INFO", 1, 2),
(1693, 2, NULL, NULL, "IDP MINOR", 2, 2),
(1693, 3, NULL, NULL, "IDP MAJOR", 3, 2),
(1693, 4, NULL, NULL, "IDP CRITICAL", 4, 2);
