-- nfs
-- plugin_id: 1631

DELETE FROM plugin WHERE id = "1631";
DELETE FROM plugin_sid where plugin_id = "1631";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1631, 1, 'nfs', 'NFS');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1631, 1, NULL, NULL, 'NFSD: Defined recovery directory', 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1631, 2, NULL, NULL, 'NFSD: Starting', 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1631, 3, NULL, NULL, 'MOUNTD: Mount request', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1631, 4, NULL, NULL, 'MOUNTD: Authenticated mount request', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1631, 5, NULL, NULL, 'MOUNTD: Authenticated umount request', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1631, 6, NULL, NULL, 'MOUNTD: Refused mount request', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1631, 7, NULL, NULL, 'NFSD: Peername failed', 1, 2);
