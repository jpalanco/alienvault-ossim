SET AUTOCOMMIT=0;
USE alienvault;

ALTER TABLE `vuln_jobs` CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `vuln_nessus_reports` CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `vuln_nessus_report_stats` CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';



REPLACE INTO config (conf, value) VALUES ('google_maps_key', 'AIzaSyBbMaRbr5jy9HbAf2TGIp4A2mnIKGk4XQ4');

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2019-05-07');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.7.3');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
