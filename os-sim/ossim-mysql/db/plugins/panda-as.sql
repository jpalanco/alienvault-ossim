-- Panda AdminSecure
-- plugin_id:1578

DELETE FROM plugin WHERE id="1578";
DELETE FROM plugin_sid WHERE plugin_id="1578";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1578, 1, 'Panda-AS', 'Panda AdminSecure');
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1578, 1, NULL, NULL, "Panda AdminSecure: Scheduler Service has been started", 1, 1);
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1578, 2, NULL, NULL, "Panda AdminSecure: Communications Agent Service has been started", 1, 1);
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1578, 3, NULL, NULL, "Panda AdminSecure: Signature file has been updated", 1, 1);
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1578, 4, NULL, NULL, "Panda AdminSecure: Scan started", 1, 1);
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1578, 5, NULL, NULL, "Panda AdminSecure: Scan complete", 1, 1);
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1578, 6, NULL, NULL, "Panda AdminSecure: Signature file update failed", 1, 1);
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1578, 7, NULL, NULL, "Panda AdminSecure: Failed to start Agent", 1, 1);
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1578, 8, NULL, NULL, "Panda AdminSecure: Failed to restore  from quarantine", 1, 1);
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1578, 9, NULL, NULL, "Panda AdminSecure: Failed to install Agent", 1, 1);
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1578, 10, NULL, NULL, "Panda AdminSecure: Virus has been detected", 1, 1);
