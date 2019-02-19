USE alienvault;

UPDATE users SET first_login=0 WHERE login_method='ldap';

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2016-10-06');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.3.3');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA