-- PROCOM IST E-Merchant
-- plugin_id=1688
--
--
-- $Id: storage.sql,v 1.0 25/05/2012

DELETE FROM plugin WHERE id = "1688";
DELETE FROM plugin_sid where plugin_id = "1688";

INSERT INTO plugin(id, type, name, description) VALUES (1688, 1, 'Storage - StorewizeV7000', 'Storage');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1688, 1, NULL, NULL, 'Remote Copy suffered loss of synchronization');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1688, 2, NULL, NULL, 'Connection to a configured remote cluster has been lost');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1688, 3, NULL, NULL, 'Failure to bring up Ethernet interface');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1688, 4, NULL, NULL, 'FlashCopy stopped');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1688, 5, NULL, NULL, 'InterCanisterPCIelinkdegraded');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1688, 6, NULL, NULL, 'Login Excluded');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1688, 7, NULL, NULL, 'Remote Copy feature license limit exceeded');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1688, 8, NULL, NULL, 'Space Efficient Disk Copy space warning');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1688, 999, NULL, NULL, 'Generic');


