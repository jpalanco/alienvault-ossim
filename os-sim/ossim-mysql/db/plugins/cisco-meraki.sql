-- Cisco-Meraki
-- Cisco-Meraki Syslog Format for Cisco Meraki
-- plugin_id: 1695
--
-- Please, check Cisco Meraki Syslog Format documentation:
--   https://kb.meraki.com/knowledge_base/syslog-server-overview-and-configuration

DELETE FROM plugin WHERE id = "1695";
DELETE FROM plugin_sid where plugin_id = "1695";

INSERT INTO plugin (id, type, name, description) VALUES 
        (1695, 1, 'cisco-meraki', 'Cisco Meraki');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES
(1695, 1, NULL, NULL, 'Cisco-Meraki: flow log message'),
(1695, 2, NULL, NULL, 'Cisco-Meraki: url log message'),
(1695, 3, NULL, NULL, 'Cisco-Meraki: ids alert'),
(1695, 4, NULL, NULL, 'Cisco-Meraki: event log');
