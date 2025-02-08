USE alienvault;


UPDATE `dashboard_custom_type` SET `name` = 'Open Tickets by User', `title_default` = 'Open Tickets by User' where `id` = 2006;
UPDATE `dashboard_widget_config` SET `title` = 'Open Tickets by User' WHERE `type_id` = 2006;


REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2020-07-14');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.8.3');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
