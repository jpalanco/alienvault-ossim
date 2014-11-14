-- serviceguard
-- HP Service Guard (HP-UX Cluster Management)
--
-- plugin_id: 1582

DELETE FROM plugin WHERE id=1582;
DELETE FROM plugin_sid WHERE plugin_id=1582;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1582, 1, 'serviceguard', 'HP Service Guard Cluster');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 1, NULL, NULL, 'ServiceGuard: Package configured with remote data replication', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 2, NULL, NULL, 'ServiceGuard: Starting md', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 3, NULL, NULL, 'ServiceGuard: Activating volume group', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 4, NULL, NULL, 'ServiceGuard: Checking filesystem', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 5, NULL, NULL, 'ServiceGuard: Mounting filesystem', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 6, NULL, NULL, 'ServiceGuard: Adding IP address to subnet', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 7, NULL, NULL, 'ServiceGuard: Starting service', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 8, NULL, NULL, 'ServiceGuard: Halting service', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 9, NULL, NULL, 'ServiceGuard: Remove IP address from subnet', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 10, NULL, NULL, 'ServiceGuard: Unmounting filesystem', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 11, NULL, NULL, 'ServiceGuard: Deactivating volume group', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 12, NULL, NULL, 'ServiceGuard: Deactivating md', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 13, NULL, NULL, 'ServiceGuard: Package start failed', 4, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 14, NULL, NULL, 'ServiceGuard: Starting package', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 15, NULL, NULL, 'ServiceGuard: Package start completed', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 16, NULL, NULL, 'ServiceGuard: Halgint package', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 17, NULL, NULL, 'ServiceGuard: Package halted with error', 4, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES(1582, 18, NULL, NULL, 'ServiceGuard: Package halt completed', 1, 2);

