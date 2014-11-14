-- imperva-securesphere
-- plugin_id: 1679

DELETE FROM plugin WHERE id = "1679";
DELETE FROM plugin_sid where plugin_id = "1679";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1679, 1, 'imperva-securesphere', 'Imperva SecureSphere');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 10, NULL, NULL, 'imperva-securesphere: Signature Violation' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 20, NULL, NULL, 'imperva-securesphere: Custom Rule Violation' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 30, NULL, NULL, 'imperva-securesphere: SSL Untraceable' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 40, NULL, NULL, 'imperva-securesphere: Illegal HTTP Version' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 50, NULL, NULL, 'imperva-securesphere: URL is Above Root Directory' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 60, NULL, NULL, 'imperva-securesphere: Abnormally Long Request' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 70, NULL, NULL, 'imperva-securesphere: Unauthorized Method for Known URL' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 80, NULL, NULL, 'imperva-securesphere: Abnormally Long Header Line' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 90, NULL, NULL, 'imperva-securesphere: Extremely Long Parameter' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 100, NULL, NULL, 'imperva-securesphere: Too Many Cookies in a Request' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 110, NULL, NULL, 'imperva-securesphere: Unknown HTTP Request Method' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 120, NULL, NULL, 'imperva-securesphere: Gateway Throughput Threshold' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 130, NULL, NULL, 'imperva-securesphere: Login Failed' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 140, NULL, NULL, 'imperva-securesphere: HTTP Signature Violation' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 150, NULL, NULL, 'imperva-securesphere: Policy Changed' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 160, NULL, NULL, 'imperva-securesphere: Parameter Type Violation' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 998, NULL, NULL, 'imperva-securesphere: Generic Alert' , 0, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1679, 999, NULL, NULL, 'imperva-securesphere: Generic Event' , 0, 1);
