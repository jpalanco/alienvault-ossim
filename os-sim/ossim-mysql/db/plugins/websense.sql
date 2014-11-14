DELETE FROM plugin WHERE id = "19004";
DELETE FROM plugin_sid where plugin_id = "19004";


INSERT INTO plugin (id, type, name, description) VALUES (19004, 1, 'websense', 'Websense: Websense Data Securityi Suite');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 1, NULL, NULL, 'Websense: SMTP Agent event', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 2, NULL, NULL, 'Websense: ISA Agent event', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 3, NULL, NULL, 'Websense: Endpoint Removable Media event', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 4, NULL, NULL, 'Websense: Safend event', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 50, NULL, NULL, 'Websense Alert - Adult Content', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 51, NULL, NULL, 'Websense Alert - Illegal or Questionable', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 52, NULL, NULL, 'Websense Alert - Peer-to-Peer File Sharing', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 53, NULL, NULL, 'Websense Alert - Personal Network Storage and Backup', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 54, NULL, NULL, 'Websense Alert - Proxy Avoidance', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 55, NULL, NULL, 'Websense Alert - Alert Sex', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 56, NULL, NULL, 'Websense Alert - Uncategorized', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (19004, 99, NULL, NULL, 'Websense Alert - General Gategory', 2, 2);
