USE alienvault;
SET AUTOCOMMIT=0;
BEGIN;


UPDATE  `alienvault`.`incident_type` SET  `id` =  'Corporative Net Attack' WHERE  `incident_type`.`id` =  'Corporative Nets Attack';

UPDATE  `alienvault`.`incident_type` SET  `id` =  'Application and System Failures' WHERE  `incident_type`.`id` =  'Applications and Systems Failures';


UPDATE  `alienvault`.`incident` SET  `type_id` =  'Corporative Net Attack' WHERE  `type_id` =  'Corporative Nets Attack';

UPDATE  `alienvault`.`incident` SET  `type_id` =  'Application and System Failures' WHERE  `type_id` =  'Applications and Systems Failures';




REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2014-01-28');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.4.1');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
