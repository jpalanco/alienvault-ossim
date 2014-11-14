USE alienvault;
SET AUTOCOMMIT=0;
BEGIN;

# Device Type changes
DELETE FROM alienvault.device_types WHERE device_types.id = 701;

REPLACE INTO `device_types` (`id`, `name`, `class`) VALUES
(409, 'Hub', 4),
(410, 'Load Balancer', 4),
(411, 'Firewall', 4);

UPDATE alienvault.host_types SET type = 4, subtype = 411 WHERE type = 7 AND subtype = 701;

UPDATE custom_report_types SET name='NetFlows - Traffic Graphs' where name='NetFlows - Trafic Graphs';
UPDATE custom_report_types SET name='NetFlows - Traffic Details' where name='NetFlows - Trafic Details';

REPLACE INTO config (conf, value) VALUES ('last_update', '2013-08-27');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.3.2');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
