-- -----------------------------------------------------
-- Table `extra_data`
-- -----------------------------------------------------
RENAME TABLE extra_data TO _extra_data;
CREATE TABLE `extra_data` (
  `event_id` BINARY(16) NOT NULL,
  `filename` VARCHAR(256) NULL DEFAULT NULL,
  `username` VARCHAR(64) NULL DEFAULT NULL,
  `password` VARCHAR(64) NULL DEFAULT NULL,
  `userdata1` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata2` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata3` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata4` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata5` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata6` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata7` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata8` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata9` VARCHAR(1024) NULL DEFAULT NULL,
  `data_payload` TEXT NULL DEFAULT NULL,
  `binary_data` BLOB NULL DEFAULT NULL,
  PRIMARY KEY (`event_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `reputation_data`
-- -----------------------------------------------------
RENAME TABLE reputation_data TO _reputation_data;
CREATE TABLE `reputation_data` (
  `event_id` BINARY(16) NOT NULL,
  `rep_ip_src` VARBINARY(16) NULL DEFAULT NULL,
  `rep_ip_dst` VARBINARY(16) NULL DEFAULT NULL,
  `rep_prio_src` TINYINT UNSIGNED NULL DEFAULT NULL,
  `rep_prio_dst` TINYINT UNSIGNED NULL DEFAULT NULL,
  `rep_rel_src` TINYINT UNSIGNED NULL DEFAULT NULL,
  `rep_rel_dst` TINYINT UNSIGNED NULL DEFAULT NULL,
  `rep_act_src` VARCHAR(64) NULL DEFAULT NULL,
  `rep_act_dst` VARCHAR(64) NULL DEFAULT NULL,
  PRIMARY KEY (`event_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `idm_data`
-- -----------------------------------------------------
RENAME TABLE idm_data TO _idm_data;
CREATE TABLE `idm_data` (
  `event_id` BINARY(16) NOT NULL,
  `username` VARCHAR(64) NULL DEFAULT NULL,
  `domain` VARCHAR(64) NULL DEFAULT NULL,
  `from_src` TINYINT(1) NULL DEFAULT NULL,
  INDEX `event_id` (`event_id` ASC),
  INDEX `usrdmn` (`username` ASC, `domain` ASC),
  INDEX `domain` (`domain` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `otx_data`
-- -----------------------------------------------------
RENAME TABLE otx_data TO _otx_data;
CREATE TABLE IF NOT EXISTS `otx_data` (
  `event_id` BINARY(16) NOT NULL,
  `pulse_id` BINARY(16) NOT NULL,
  `ioc_hash` VARCHAR(32) NOT NULL,
  `ioc_value` VARCHAR(2048) NULL,
  INDEX `ioc` (`ioc_value`(255) ASC),
  INDEX `pulse` (`pulse_id` ASC),
  PRIMARY KEY (`event_id`, `pulse_id`, `ioc_hash`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


CREATE TABLE IF NOT EXISTS tmp_events (id binary(16) NOT NULL, PRIMARY KEY (`id`)) ENGINE = InnoDB;

SELECT sleep(10) into @sleep;


-- -----------------------------------------------------
-- Table `ac_acid_event`
-- -----------------------------------------------------
RENAME TABLE ac_acid_event TO _ac_acid_event;
CREATE TABLE IF NOT EXISTS `ac_acid_event` (
  `ctx` BINARY(16) NOT NULL,
  `device_id` INT UNSIGNED NOT NULL,
  `plugin_id` INT UNSIGNED NOT NULL,
  `plugin_sid` INT UNSIGNED NOT NULL,
  `timestamp` DATETIME NOT NULL,
  `src_host` BINARY(16) NOT NULL DEFAULT 0x0,
  `dst_host` BINARY(16) NOT NULL DEFAULT 0x0,
  `src_net` BINARY(16) NOT NULL DEFAULT 0x0,
  `dst_net` BINARY(16) NOT NULL DEFAULT 0x0,
  `cnt` INT UNSIGNED NOT NULL DEFAULT 0,
  INDEX `day` (`timestamp` ASC),
  INDEX `src_host` (`src_host` ASC),
  INDEX `dst_host` (`dst_host` ASC),
  INDEX `plugin_id` (`plugin_id` ASC, `plugin_sid` ASC),
  PRIMARY KEY (`ctx`, `device_id`, `plugin_id`, `plugin_sid`, `timestamp`, `src_host`, `dst_host`, `src_net`, `dst_net`),
  INDEX `src_net` (`src_net` ASC),
  INDEX `dst_net` (`dst_net` ASC),
  INDEX `device_id` (`device_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `po_acid_event`
-- -----------------------------------------------------
RENAME TABLE po_acid_event TO _po_acid_event;
CREATE TABLE IF NOT EXISTS `po_acid_event` (
  `ctx` BINARY(16) NOT NULL,
  `device_id` INT UNSIGNED NOT NULL,
  `plugin_id` INT UNSIGNED NOT NULL,
  `plugin_sid` INT UNSIGNED NOT NULL,
  `ip_src` VARBINARY(16) NOT NULL,
  `ip_dst` VARBINARY(16) NOT NULL,
  `timestamp` DATETIME NOT NULL,
  `src_host` BINARY(16) NOT NULL DEFAULT 0x0,
  `dst_host` BINARY(16) NOT NULL DEFAULT 0x0,
  `src_net` BINARY(16) NOT NULL DEFAULT 0x0,
  `dst_net` BINARY(16) NOT NULL DEFAULT 0x0,
  `cnt` INT UNSIGNED NOT NULL DEFAULT 0,
  INDEX `day` (`timestamp` ASC),
  INDEX `plugin_id` (`plugin_id` ASC, `plugin_sid` ASC),
  INDEX `src_host` (`src_host` ASC),
  INDEX `dst_host` (`dst_host` ASC),
  INDEX `src_net` (`src_net` ASC),
  INDEX `dst_net` (`dst_net` ASC),
  PRIMARY KEY (`ctx`, `device_id`, `plugin_id`, `plugin_sid`, `ip_src`, `ip_dst`, `timestamp`, `src_host`, `dst_host`, `src_net`, `dst_net`),
  INDEX `ip_src` (`ip_src` ASC),
  INDEX `ip_dst` (`ip_dst` ASC),
  INDEX `device_id` (`device_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `acid_event`
-- -----------------------------------------------------
RENAME TABLE acid_event TO _acid_event;
CREATE TABLE `acid_event` (
  `id` BINARY(16) NOT NULL,
  `device_id` INT UNSIGNED NOT NULL,
  `ctx` BINARY(16) NOT NULL DEFAULT 0x0,
  `timestamp` DATETIME NOT NULL,
  `ip_src` VARBINARY(16) NULL DEFAULT NULL,
  `ip_dst` VARBINARY(16) NULL DEFAULT NULL,
  `ip_proto` INT NULL DEFAULT NULL,
  `layer4_sport` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `layer4_dport` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `ossim_priority` TINYINT NULL DEFAULT '1',
  `ossim_reliability` TINYINT NULL DEFAULT '1',
  `ossim_asset_src` TINYINT NULL DEFAULT '1',
  `ossim_asset_dst` TINYINT NULL DEFAULT '1',
  `ossim_risk_c` TINYINT NULL DEFAULT '1',
  `ossim_risk_a` TINYINT NULL DEFAULT '1',
  `plugin_id` INT UNSIGNED NULL DEFAULT NULL,
  `plugin_sid` INT UNSIGNED NULL DEFAULT NULL,
  `tzone` FLOAT NOT NULL DEFAULT '0',
  `ossim_correlation` TINYINT NULL DEFAULT '0',
  `src_hostname` VARCHAR(64) NULL DEFAULT NULL,
  `dst_hostname` VARCHAR(64) NULL DEFAULT NULL,
  `src_mac` BINARY(6) NULL DEFAULT NULL,
  `dst_mac` BINARY(6) NULL DEFAULT NULL,
  `src_host` BINARY(16) NULL DEFAULT NULL,
  `dst_host` BINARY(16) NULL DEFAULT NULL,
  `src_net` BINARY(16) NULL DEFAULT NULL,
  `dst_net` BINARY(16) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `timestamp` (`timestamp` ASC),
  INDEX `layer4_dport` (`layer4_dport` ASC),
  INDEX `ip_src` (`ip_src` ASC),
  INDEX `ip_dst` (`ip_dst` ASC),
  INDEX `acid_event_ossim_risk_a` (`ossim_risk_a` ASC),
  INDEX `plugin` (`plugin_id` ASC, `plugin_sid` ASC),
  INDEX `src_host` (`src_host` ASC),
  INDEX `dst_host` (`dst_host` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


DELIMITER $$

DROP TRIGGER IF EXISTS `count_acid_event`$$
CREATE TRIGGER `count_acid_event` AFTER INSERT ON `acid_event` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_count IS NULL THEN
        INSERT IGNORE INTO alienvault_siem.po_acid_event (ctx, device_id, plugin_id, plugin_sid, ip_src, ip_dst, timestamp, src_host, dst_host, src_net, dst_net, cnt) VALUES (NEW.ctx, NEW.device_id, NEW.plugin_id, NEW.plugin_sid, NEW.ip_src, NEW.ip_dst, DATE_FORMAT(NEW.timestamp, '%Y-%m-%d %H:00:00'), IFNULL(NEW.src_host,0x00000000000000000000000000000000), IFNULL(NEW.dst_host,0x00000000000000000000000000000000), IFNULL(NEW.src_net,0x00000000000000000000000000000000), IFNULL(NEW.dst_net,0x00000000000000000000000000000000),1) ON DUPLICATE KEY UPDATE cnt = cnt + 1;
        INSERT IGNORE INTO alienvault_siem.ac_acid_event (ctx, device_id, plugin_id, plugin_sid, timestamp, src_host, dst_host, src_net, dst_net, cnt) VALUES (NEW.ctx, NEW.device_id, NEW.plugin_id, NEW.plugin_sid, DATE_FORMAT(NEW.timestamp, '%Y-%m-%d %H:00:00'), IFNULL(NEW.src_host,0x00000000000000000000000000000000), IFNULL(NEW.dst_host,0x00000000000000000000000000000000), IFNULL(NEW.src_net,0x00000000000000000000000000000000), IFNULL(NEW.dst_net,0x00000000000000000000000000000000),1) ON DUPLICATE KEY UPDATE cnt = cnt + 1;
    END IF;
END$$

DROP TRIGGER IF EXISTS `del_count_acid_event`$$

DROP PROCEDURE IF EXISTS `delete_events`$$
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

    SET @query = CONCAT('DELETE aux FROM alienvault_siem.extra_data aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.event_id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;

    SET @query = CONCAT('DELETE aux FROM alienvault_siem.acid_event aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
END$$

DROP PROCEDURE IF EXISTS `fill_tables`$$
CREATE PROCEDURE fill_tables(
    IN date_from VARCHAR(19),
    IN date_to VARCHAR(19)
)
BEGIN
    IF date_from <> '' AND date_to <> '' THEN
        DELETE FROM po_acid_event WHERE timestamp BETWEEN date_from AND date_to;
        REPLACE INTO po_acid_event (select ctx, device_id, plugin_id, plugin_sid, ip_src, ip_dst, DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00'), IFNULL(src_host,0x00000000000000000000000000000000), IFNULL(dst_host,0x00000000000000000000000000000000), IFNULL(src_net,0x00000000000000000000000000000000), IFNULL(dst_net,0x00000000000000000000000000000000), count(*) FROM acid_event  WHERE timestamp BETWEEN date_from AND date_to GROUP BY 1,2,3,4,5,6,7,8,9,10,11);
        DELETE FROM ac_acid_event WHERE timestamp BETWEEN date_from AND date_to;
        REPLACE INTO ac_acid_event (select ctx, device_id, plugin_id, plugin_sid, DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00'), IFNULL(src_host,0x00000000000000000000000000000000), IFNULL(dst_host,0x00000000000000000000000000000000), IFNULL(src_net,0x00000000000000000000000000000000), IFNULL(dst_net,0x00000000000000000000000000000000), count(*) FROM acid_event  WHERE timestamp BETWEEN date_from AND date_to GROUP BY 1,2,3,4,5,6,7,8,9);
    ELSE
        TRUNCATE TABLE po_acid_event;
        REPLACE INTO po_acid_event (select ctx, device_id, plugin_id, plugin_sid, ip_src, ip_dst, DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00'), IFNULL(src_host,0x00000000000000000000000000000000), IFNULL(dst_host,0x00000000000000000000000000000000), IFNULL(src_net,0x00000000000000000000000000000000), IFNULL(dst_net,0x00000000000000000000000000000000), count(*) FROM acid_event GROUP BY 1,2,3,4,5,6,7,8,9,10,11);
        TRUNCATE TABLE ac_acid_event;
        REPLACE INTO ac_acid_event (select ctx, device_id, plugin_id, plugin_sid, DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00'), IFNULL(src_host,0x00000000000000000000000000000000), IFNULL(dst_host,0x00000000000000000000000000000000), IFNULL(src_net,0x00000000000000000000000000000000), IFNULL(dst_net,0x00000000000000000000000000000000), count(*) FROM acid_event GROUP BY 1,2,3,4,5,6,7,8,9);
    END IF;
END$$

DROP PROCEDURE IF EXISTS `update_tables`$$
CREATE PROCEDURE update_tables(
    IN event_id VARCHAR(32)
)
BEGIN
    SELECT ctx, device_id, DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00'), plugin_id, plugin_sid, ip_src, ip_dst, IFNULL(src_host,0x00000000000000000000000000000000), IFNULL(dst_host,0x00000000000000000000000000000000), IFNULL(src_net,0x00000000000000000000000000000000), IFNULL(dst_net,0x00000000000000000000000000000000) FROM acid_event WHERE id=UNHEX(event_id) INTO @ctx, @device_id, @timestamp, @plugin_id, @plugin_sid, @ip_src, @ip_dst, @src_host, @dst_host, @src_net, @dst_net;
    IF @plugin_id IS NOT NULL THEN
        UPDATE po_acid_event SET cnt = cnt - 1 WHERE ctx=@ctx AND device_id=@device_id AND timestamp=@timestamp AND plugin_id=@plugin_id AND plugin_sid=@plugin_sid AND ip_src=@ip_src AND ip_dst=@ip_dst AND src_host=@src_host AND dst_host=@dst_host AND src_net=@src_net AND dst_net=@dst_net AND cnt>0;
        UPDATE ac_acid_event SET cnt = cnt - 1 WHERE ctx=@ctx AND device_id=@device_id AND timestamp=@timestamp AND plugin_id=@plugin_id AND plugin_sid=@plugin_sid AND src_host=@src_host AND dst_host=@dst_host AND src_net=@src_net AND dst_net=@dst_net AND cnt>0;
    END IF;
END$$

DROP PROCEDURE IF EXISTS get_events$$
CREATE PROCEDURE get_events( 
    IN _type VARCHAR(16), -- host, network or group
    IN _table VARCHAR(64), -- tmp table name or id if host
    IN _from INT, -- 0
    IN _max INT, -- 50
    IN _order VARCHAR(32), -- timestamp desc
    search_str TEXT
)
BEGIN
    SELECT IF (_type='','host',_type) into @_type;
    SELECT IF (_from<0,0,_from) into @_from;
    SELECT IF (_max<10,10,_max) into @_max;
    SET @max = @_from + @_max;
    SELECT IF (_order='','timestamp desc',_order) into @_order;
    SELECT concat("events_",replace(uuid(), '-', '')) into @tmp_name;
    SET @ids = _table;
    SET @joins = '';
    SET @where = '';

    IF search_str <> '' THEN
        SET @joins = ', alienvault.plugin_sid';
        SET @where = CONCAT('AND acid_event.plugin_id=plugin_sid.plugin_id AND acid_event.plugin_sid=plugin_sid.sid AND plugin_sid.name LIKE "%',search_str,'%"');
    END IF;
    
    SET @query = CONCAT('CREATE TEMPORARY TABLE IF NOT EXISTS ',@tmp_name,' ENGINE=MEMORY AS (SELECT * FROM alienvault_siem.acid_event LIMIT 0)');
    PREPARE stmt1 FROM @query;
    EXECUTE stmt1;
    DEALLOCATE PREPARE stmt1;

    SET @query = CONCAT('ALTER TABLE ',@tmp_name,' ADD PRIMARY KEY (id)');
    PREPARE stmt1 FROM @query;
    EXECUTE stmt1;
    DEALLOCATE PREPARE stmt1;
    
    IF _type = 'host' THEN

        SET @query = CONCAT('INSERT IGNORE INTO ',@tmp_name,' SELECT acid_event.* FROM acid_event ',@joins,' WHERE src_host=UNHEX(?) ',@where,' ORDER BY ',@_order,' LIMIT ',@max);
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1 USING @ids;
        DEALLOCATE PREPARE stmt1;

        SET @query = CONCAT('INSERT IGNORE INTO ',@tmp_name,' SELECT acid_event.* FROM acid_event ',@joins,' WHERE dst_host=UNHEX(?) ',@where,' ORDER BY ',@_order,' LIMIT ',@max);
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1 USING @ids;
        DEALLOCATE PREPARE stmt1;

    ELSEIF _type = 'group' THEN

        SET @joins = CONCAT(@joins, ', ', @ids);

        SET @query = CONCAT('INSERT IGNORE INTO ',@tmp_name,' SELECT acid_event.* FROM acid_event ',@joins,' WHERE src_host=',@ids,'.id ',@where,' ORDER BY ',@_order,' LIMIT ',@max);
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

        SET @query = CONCAT('INSERT IGNORE INTO ',@tmp_name,' SELECT acid_event.* FROM acid_event ',@joins,' WHERE dst_host=',@ids,'.id ',@where,' ORDER BY ',@_order,' LIMIT ',@max);
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

    ELSEIF _type = 'network' THEN

        SET @joins = CONCAT(@joins, ', ', @ids);

        SET @query = CONCAT('INSERT IGNORE INTO ',@tmp_name,' SELECT acid_event.* FROM acid_event ',@joins,' WHERE src_net=',@ids,'.id ',@where,' ORDER BY ',@_order,' LIMIT ',@max);
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

        SET @query = CONCAT('INSERT IGNORE INTO ',@tmp_name,' SELECT acid_event.* FROM acid_event ',@joins,' WHERE dst_net=',@ids,'.id ',@where,' ORDER BY ',@_order,' LIMIT ',@max);
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

    END IF;

    SET @query = CONCAT('SELECT *,id as eid FROM ',@tmp_name,' ORDER BY ',@_order,' LIMIT ',@_from,',',@_max);
    PREPARE stmt1 FROM @query;
    EXECUTE stmt1;
    DEALLOCATE PREPARE stmt1;
END$$

DROP PROCEDURE IF EXISTS get_event_count$$
CREATE PROCEDURE get_event_count( 
    IN _type VARCHAR(16), -- host, network or group
    IN _table VARCHAR(64), -- tmp table name or id if host
    search_str TEXT
)
BEGIN
    DECLARE events INT DEFAULT 0;
    SELECT IF (_type='','host',_type) into @_type;
    SET @ids = _table;
    SET @joins = '';
    SET @where = '';

    IF search_str <> '' THEN
        SET @joins = ', alienvault.plugin_sid';
        SET @where = CONCAT('AND acid_event.plugin_id=plugin_sid.plugin_id AND acid_event.plugin_sid=plugin_sid.sid AND plugin_sid.name LIKE "%',search_str,'%"');
    END IF;
    
    IF _type = 'host' THEN

        SET @query = CONCAT('SELECT ifnull(sum(cnt),0) FROM ac_acid_event acid_event ',@joins,' WHERE src_host=UNHEX(?) ',@where,' into @event_src');
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1 USING @ids;
        DEALLOCATE PREPARE stmt1;

        SET @query = CONCAT('SELECT ifnull(sum(cnt),0) FROM ac_acid_event acid_event ',@joins,' WHERE dst_host=UNHEX(?) ',@where,' into @event_dst');
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1 USING @ids;
        DEALLOCATE PREPARE stmt1;

        SET @query = CONCAT('SELECT ifnull(sum(cnt),0) FROM ac_acid_event acid_event ',@joins,' WHERE src_host=UNHEX(?) AND dst_host=UNHEX(?) ',@where,' into @event_add');
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1 USING @ids, @ids;
        DEALLOCATE PREPARE stmt1;

    ELSEIF _type = 'group' THEN

        SET @joins = CONCAT(@joins, ', ', @ids);

        SET @query = CONCAT('SELECT ifnull(sum(cnt),0) FROM ac_acid_event acid_event ',@joins,' WHERE src_host=',@ids,'.id ',@where,' into @event_src');
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

        SET @query = CONCAT('SELECT ifnull(sum(cnt),0) FROM ac_acid_event acid_event ',@joins,' WHERE dst_host=',@ids,'.id ',@where,' into @event_dst');
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

        SET @query = CONCAT('SELECT ifnull(sum(cnt),0) FROM ac_acid_event acid_event ',@joins,' WHERE src_host=',@ids,'.id AND dst_host=',@ids,'.id ',@where,' into @event_add');
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

    ELSEIF _type = 'network' THEN

        SET @joins = CONCAT(@joins, ', ', @ids);

        SET @query = CONCAT('SELECT ifnull(sum(cnt),0) FROM ac_acid_event acid_event ',@joins,' WHERE src_net=',@ids,'.id ',@where,' into @event_src');
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

        SET @query = CONCAT('SELECT ifnull(sum(cnt),0) FROM ac_acid_event acid_event ',@joins,' WHERE dst_net=',@ids,'.id ',@where,' into @event_dst');
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

        SET @query = CONCAT('SELECT ifnull(sum(cnt),0) FROM ac_acid_event acid_event ',@joins,' WHERE src_net=',@ids,'.id AND dst_net=',@ids,'.id ',@where,' into @event_add');
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

    END IF;

    SET events = @event_src + @event_dst - @event_add;
    SELECT events;
END$$

DROP PROCEDURE IF EXISTS _clean_devices$$
CREATE PROCEDURE _clean_devices()
BEGIN
    ALTER TABLE `device` CHANGE `interface` `interface` TEXT DEFAULT NULL;
    SELECT ".";
    ALTER TABLE _acid_event ADD INDEX device (device_id);
    SELECT ".";
    DELETE d FROM device d LEFT JOIN _acid_event a on a.device_id=d.id LEFT JOIN acid_event ae on ae.device_id=d.id WHERE a.id IS NULL AND ae.id IS NULL;
END$$

DROP PROCEDURE IF EXISTS _delete_orphans$$
CREATE PROCEDURE _delete_orphans()
BEGIN
    DECLARE num_events INT;

    -- Select valid events
    TRUNCATE TABLE alienvault_siem.tmp_events;
    INSERT INTO alienvault_siem.tmp_events SELECT id FROM alienvault_siem._acid_event;

    CREATE TEMPORARY TABLE _ttmp_events (id binary(16) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MEMORY;

    SELECT count(id) FROM alienvault_siem.tmp_events INTO @num_events;
    SELECT ".";
    
    WHILE @num_events > 0 DO
       INSERT IGNORE INTO _ttmp_events SELECT id FROM alienvault_siem.tmp_events LIMIT 100000;
       INSERT IGNORE INTO alienvault_siem.reputation_data SELECT aux.* FROM alienvault_siem._reputation_data aux, _ttmp_events t WHERE aux.event_id=t.id;
       INSERT IGNORE INTO alienvault_siem.otx_data SELECT aux.* FROM alienvault_siem._otx_data aux, _ttmp_events t WHERE aux.event_id=t.id;
       INSERT IGNORE INTO alienvault_siem.idm_data SELECT aux.* FROM alienvault_siem._idm_data aux, _ttmp_events t WHERE aux.event_id=t.id;
       INSERT IGNORE INTO alienvault_siem.extra_data SELECT aux.* FROM alienvault_siem._extra_data aux, _ttmp_events t WHERE aux.event_id=t.id;
       INSERT IGNORE INTO alienvault_siem.acid_event SELECT aux.* FROM alienvault_siem._acid_event aux, _ttmp_events t WHERE aux.id=t.id;
       DELETE tt FROM alienvault_siem.tmp_events tt LEFT JOIN _ttmp_events tmp ON tmp.id=tt.id WHERE tmp.id IS NOT NULL;
       TRUNCATE TABLE _ttmp_events;
       SELECT ".";
       SET @num_events = @num_events - 100000;
    END WHILE;
END$$

DELIMITER ;
