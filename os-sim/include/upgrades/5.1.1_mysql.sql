USE alienvault;
SET AUTOCOMMIT=0;


-- Raw Logs - Custom List fields validation changes
UPDATE custom_report_types SET inputs = 'Top Events List:top:text:OSS_DIGIT:50:150;Source Database:source:select:OSS_ALPHA:EVENTSOURCELOGGER:;Filter:filter:select:OSS_ALPHA.OSS_PUNC:FILTERLOGGER' WHERE id = 149;

DELETE FROM port WHERE protocol_name in ('6','17');

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2015-09-15');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.1.1');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA