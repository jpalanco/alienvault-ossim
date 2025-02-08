SET AUTOCOMMIT=0;
USE alienvault_siem;

drop procedure if exists sp_fail;
drop procedure if exists delete_events;
drop procedure if exists reIndexing;
drop procedure if exists sp_populate_while_not_empty;

DELIMITER $$

CREATE PROCEDURE delete_events( tmp_table VARCHAR(64) )
BEGIN
    SET @query = CONCAT('DELETE aux FROM alienvault_siem.reputation_data aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.event_id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;

    SET @query = CONCAT('DELETE aux FROM alienvault_siem.otx_data aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.event_id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;

    SET @query = CONCAT('DELETE aux FROM alienvault_siem.idm_data aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.event_id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;

    SET @query = CONCAT('DELETE aux FROM alienvault_siem.extra_data_content aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.event_id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;

    SET @query = CONCAT('DELETE aux FROM alienvault_siem.acid_event aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
END$$


CREATE PROCEDURE `sp_fail`()
proc_label: BEGIN

  -- continue if the table extra_data_content exists
  IF NOT EXISTS(SELECT
     * FROM
      information_schema.tables
  WHERE
      table_schema = 'alienvault_siem'
          AND table_name = 'extra_data_content') THEN
  LEAVE proc_label;
  END IF;

  -- It makes sure that there is no tables with the same name
  DROP TABLE IF EXISTS extra_data_content_old;
  DROP TABLE IF EXISTS extra_data_old;

  RENAME TABLE extra_data_content TO extra_data_content_old;
  RENAME TABLE extra_data TO extra_data_old;

  --
  -- Table structure for table `extra_data`
  --

  CREATE TABLE IF NOT EXISTS `extra_data` (
    `event_id` binary(16) NOT NULL,
    `filename` varchar(256) DEFAULT NULL,
    `username` varchar(64) DEFAULT NULL,
    `password` varchar(64) DEFAULT NULL,
    `userdata1` varchar(1024) DEFAULT NULL,
    `userdata2` varchar(1024) DEFAULT NULL,
    `userdata3` varchar(1024) DEFAULT NULL,
    `userdata4` varchar(1024) DEFAULT NULL,
    `userdata5` varchar(1024) DEFAULT NULL,
    `userdata6` varchar(1024) DEFAULT NULL,
    `userdata7` varchar(1024) DEFAULT NULL,
    `userdata8` varchar(1024) DEFAULT NULL,
    `userdata9` varchar(1024) DEFAULT NULL,
    `data_payload` text,
    `binary_data` blob
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

  --
  -- Indexes for dumped tables
  --

  --
  -- Indexes for table `extra_data`
  --
  ALTER TABLE `extra_data`
   ADD PRIMARY KEY (`event_id`);

  IF ( alienvault.is_pro() AND EXISTS (SELECT support FROM information_schema.engines WHERE engine='tokudb') ) THEN
      SET @query = 'ALTER TABLE alienvault_siem.extra_data ENGINE=TokuDB';
      PREPARE sql_query from @query;
      EXECUTE sql_query;
      DEALLOCATE PREPARE sql_query;
  END IF;

END$$


CREATE PROCEDURE reIndexing()
  BEGIN

		IF NOT EXISTS(
                    SELECT * FROM information_schema.statistics
                    WHERE TABLE_SCHEMA = 'alienvault'
                    AND `table_name`='alarm'
                    AND `index_name` = 'removable'
                )
    THEN
		  ALTER TABLE alienvault.alarm ADD INDEX (removable);
    END IF;

END $$



DELIMITER ;
CALL reIndexing();
DROP PROCEDURE reIndexing;

call sp_fail();
drop procedure sp_fail;

USE alienvault;


update custom_report_types set `sql` = replace(`sql`,'acid_event.extra_data_id=extra_data.id','acid_event.id=extra_data.event_id') where `sql` like "%extra_data%";


-- we want increase the maxim limit of log in user activity
REPLACE INTO alienvault.custom_report_types (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr` ) VALUES (340, 'User Activity', 'User Activity', 'UserActivity/UserActivity.php', 'User Activity:log:text:OSS_DIGIT:10:200', '', 1);

ALTER TABLE `locations` CHANGE `name` `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
UPDATE config SET value = '/usr/sbin/greenbone-nvt-sync' where value = '/usr/sbin/openvas-nvt-sync';
ALTER TABLE alienvault.event MODIFY COLUMN timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

INSERT IGNORE INTO config (conf, value) VALUES ('event_cache_limit', '102400');
REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2018-12-11');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.7.0');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
