USE alienvault;

update custom_report_scheduler set file_type = 'pdf' where file_type is null;
alter table custom_report_scheduler modify column file_type varchar(8) not null default 'pdf';
REPLACE INTO config (conf, value) select 'backup_events_min_free_disk_space',  ifnull((select value from config where conf = 'backup_events_min_free_disk_space'),10);

replace into log_config (select ctx, 107, 1, 'User %1%  has cleared SIEM event data', 2 from log_config group by ctx);
replace into log_config (select ctx, 015, 1, 'Ticket (id:%1%) was  modified by %2%', 1 from log_config group by ctx);
replace into log_config (select ctx, 016, 1, 'Ticket (id:%1%) was deleted by %2%', 3 from log_config group by ctx);
replace into log_config (select ctx, 017, 1, 'Ticket (id:%1%) was created by %2%', 2 from log_config group by ctx);
replace into log_config (select ctx, 108, 1, 'Ticket (id:%1%) was closed by %2%', 2 from log_config group by ctx);

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2017-10-25');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.4.3');

-- please no code here

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
