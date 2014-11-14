-- fw1 ng r60
-- Firewall-1 Checkpoint NG R60
-- plugin_id: 1503
--
-- $Id $
--

DELETE FROM plugin WHERE id = "1504";
DELETE FROM plugin_sid WHERE plugin_id = "1504";

INSERT INTO plugin (id, type, name, vendor, description) VALUES (1504, 1, 'fw1ngr60' , 'CheckPoint' ,'Firewall-1 NG R60 Checkpoint');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,1,3,0,0,0,75,'fw1ngr60: Accept');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,2,3,0,0,0,76,'fw1ngr60: Reject');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,3,3,0,0,0,76,'fw1ngr60: Drop');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,6,3,0,1,1,121,'fw1ngr60: Monitor');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,7,3,0,2,1,132,'fw1ngr60: Encrypt');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,8,3,0,2,1,132,'fw1ngr60: Decrypt');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,9,3,0,2,1,132,'fw1ngr60: AuthCrypt');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,10,3,0,2,1,121,'fw1ngr60: Ctl');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,11,3,0,2,1,132,'fw1ngr60: KeyInstall');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,12,3,0,2,3,121,'fw1ngr60: Deauthorize');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,14,3,0,4,2,121,'fw1ngr60: Alert');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,99,3,0,1,1,121,'fw1ngr60: unknown event');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,20000000,3,'NULL',2,2,'NULL','fw1ngr60:  Demo event (limit reached)');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, subcategory_id, name)  VALUES (1504,2000000000,3,'NULL',2,2,'NULL','fw1ngr60: Generic event');

