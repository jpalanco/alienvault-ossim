-- tacacs-plus
-- plugin_id: 1665

DELETE FROM plugin WHERE id = "1665";
DELETE FROM plugin_sid where plugin_id = "1665";

INSERT INTO plugin (id, type, name, description) VALUES (1665, 1, 'tacacs-plus', 'TACACS+');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1665, 1, NULL, NULL, 'Tacacs+: Administration session started');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1665, 2, NULL, NULL, 'Tacacs+: Administration session finished');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1665, 3, NULL, NULL, 'Tacacs+: Key Mismatch');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1665, 4, NULL, NULL, 'Tacacs+: CS user unknown');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1665, 5, NULL, NULL, 'Tacacs+: CS password invalid');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1665, 6, NULL, NULL, 'Tacacs+: Authentication OK');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1665, 7, NULL, NULL, 'Tacacs+: Accounting start');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1665, 8, NULL, NULL, 'Tacacs+: Accounting stop');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1665, 9, NULL, NULL, 'Tacacs+: Terminal Command Issued from SRC_IP');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1665, 99999, NULL, NULL, 'Tacacs+: Unknown message, please check payload and notify admin or vendor');
