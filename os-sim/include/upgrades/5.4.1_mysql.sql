USE alienvault;

INSERT IGNORE INTO config (conf, value) VALUES ('backup_events_min_free_disk_space', 10);

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2017-08-31');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.4.1');

-- please no code here

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
