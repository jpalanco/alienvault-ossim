-- f5 load balancer
-- plugin_id: 1614

DELETE FROM plugin WHERE id = "1614";
DELETE FROM plugin_sid where plugin_id = "1614";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1614, 1, 'f5', 'F5 Load Balancer');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 1, NULL, NULL, 'F5 load balancer: HTTP_RESPONSE', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 2, NULL, NULL, 'F5 load balancer: SSL Request', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 3, NULL, NULL, 'F5 load balancer: SSL Access', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 4, NULL, NULL, 'F5 load balancer: Generic ERROR', 5, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 5, NULL, NULL, 'F5 load balancer: TCL ERROR', 5, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 6, NULL, NULL, 'F5 load balancer: AUDIT Event', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 7, NULL, NULL, 'F5 load balancer: Uknown site', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 8, NULL, NULL, 'F5 load balancer: Generic NOTICE', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 9, NULL, NULL, 'F5 load balancer: Generic INFO', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 10, NULL, NULL, 'F5 load balancer: Generic WARNING', 3, 1);

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 11, NULL, NULL, 'F5 load balancer: Node down', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 12, NULL, NULL, 'F5 load balancer: Node up', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 13, NULL, NULL, 'F5 load balancer: HTTP Authentication failure', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 14, NULL, NULL, 'F5 load balancer: HTTP Authentication failure', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 15, NULL, NULL, 'F5 load balancer: SSH login accepted', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1614, 16, NULL, NULL, 'F5 load balancer: Login failed', 1, 1);
