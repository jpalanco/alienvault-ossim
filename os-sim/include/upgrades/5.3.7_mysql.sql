USE alienvault;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2017-03-22');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.3.7');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
