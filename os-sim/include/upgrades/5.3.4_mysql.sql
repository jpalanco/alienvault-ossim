USE alienvault;

INSERT INTO acl_perm(id,type,name,value,description,granularity_sensor,granularity_net,enabled,ord) VALUES (88,'MENU','report-menu','ReportsClone','Reports -> Clone Reports',1,1,1,'04.05');
INSERT INTO acl_templates_perms select ac_templates_id,88 from acl_templates_perms where ac_perm_id = 74;
alter table action_email add column message_suffix tinyint unsigned NOT NULL default 1;

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES (110, 'Summarized Compliance Status', 'Asset', 'Asset/AssetCommonStatus.php', '', '', 1);


REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2016-12-13');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.3.4');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
