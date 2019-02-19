USE alienvault;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2016-06-07');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.2.5');

INSERT INTO config (conf, value) VALUES ('backup_conf_pass', '');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA