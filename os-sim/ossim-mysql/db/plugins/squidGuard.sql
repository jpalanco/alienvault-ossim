-- SquidGuard
-- Plugin_id: 1587

DELETE FROM plugin WHERE id = 1587;
DELETE FROM plugin_sid WHERE plugin_id=1587;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1587, 1, 'squidguard', 'Accesses identified in squidguards blacklist');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name, aro) VALUES(1587,1,NULL,NULL,3,1,'SquidGuard detects access to spyware site','0.0000'),(1587,2,NULL,NULL,3,1,'SquidGuard detects access to phishing site','0.0000'),(1587,3,NULL,NULL,3,1,'SquidGuard detects access to hacking site','0.0000'),(1587,4,NULL,NULL,3,1,'SquidGuard detects access to proxy site','0.0000'),(1587,5,NULL,NULL,3,1,'SquidGuard detects access to virusinfected site','0.0000');
