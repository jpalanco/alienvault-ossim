-- arpalert
-- plugin_id: 1512
--
-- Commented, because 1512 is arpwatch. (this is transitional)
-- W: You can use this statements, but be warned: first statements deletes arpwatch plugin db related
--


-- -- DELETE FROM plugin WHERE id = "1512";
-- -- DELETE FROM plugin_sid where plugin_id = "1512";

-- INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1512, 1, 'arpalert', 'Ethernet/FDDI station monitor daemon');

-- INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 1, NULL, NULL, 'arpalert: Mac address New');
-- INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 2, NULL, NULL, 'arpalert: Mac address Change');
-- INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 3, NULL, NULL, 'arpalert: Mac address Deleted');
-- INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 4, NULL, NULL, 'arpalert: Mac address Same');
-- INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 5, NULL, NULL, 'arpalert: Mac address Event unknown');
-- INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 6, NULL, NULL, 'arpalert: IP address Change');
-- INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 7, NULL, NULL, 'arpalert: Mac address Error');
-- INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 8, NULL, NULL, 'arpalert: Flood');
-- INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 9, NULL, NULL, 'arpalert: Blacklisted');

