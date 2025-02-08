SET AUTOCOMMIT=0;
USE alienvault;

ALTER TABLE `sensor_properties` MODIFY COLUMN `version` VARCHAR(64) NULL DEFAULT NULL;

ALTER TABLE `vuln_jobs` CHANGE COLUMN IF EXISTS `author_uname` `IP_ctx` TEXT NULL DEFAULT NULL;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2022-05-10');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.8.11');

ALTER TABLE datawarehouse.incidents_ssi MODIFY `descr` VARCHAR(512);
ALTER TABLE datawarehouse.ssi MODIFY `descr` VARCHAR(512);
ALTER TABLE datawarehouse.incidents_ssi_user MODIFY `descr` VARCHAR(512);
ALTER TABLE datawarehouse.ssi_user MODIFY `descr` VARCHAR(512);


-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
