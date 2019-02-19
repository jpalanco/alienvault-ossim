USE alienvault_api;

CREATE TABLE IF NOT EXISTS `user_perms` (
  `login` VARCHAR(64) NOT NULL,
  `component_id` BINARY(16) NOT NULL,
  PRIMARY KEY (`login`, `component_id`))
ENGINE = InnoDB;

DELIMITER $$

DROP PROCEDURE IF EXISTS fill_user_perms$$
CREATE PROCEDURE fill_user_perms (IN login VARCHAR(64)) 
BEGIN
    SET @user = login;
    DELETE FROM user_perms WHERE user_perms.login=@user;
    IF EXISTS (SELECT 1 FROM alienvault.users WHERE users.login=@user) THEN
        CALL alienvault.acl_user_permissions(@user);
        INSERT INTO user_perms VALUES (@user, 0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF);
        SELECT NOT EXISTS (SELECT 1 FROM alienvault.user_host_perm WHERE user_host_perm.login=@user AND asset_id=0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF) INTO @hosts;
        SELECT NOT EXISTS (SELECT 1 FROM alienvault.user_net_perm WHERE user_net_perm.login=@user AND asset_id=0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF) INTO @nets;
        IF @hosts THEN
            INSERT IGNORE INTO user_perms SELECT @user,asset_id FROM alienvault.user_host_perm WHERE user_host_perm.login=@user;
            IF @nets THEN
                INSERT IGNORE INTO user_perms SELECT @user,asset_id FROM alienvault.user_net_perm WHERE user_net_perm.login=@user;
                INSERT IGNORE INTO user_perms SELECT @user,host_id FROM alienvault.host_net_reference, alienvault.user_net_perm WHERE host_net_reference.net_id=user_net_perm.asset_id AND user_net_perm.login=@user;
            END IF;
        ELSE
            IF @nets THEN
                INSERT IGNORE INTO user_perms SELECT @user,asset_id FROM alienvault.user_net_perm WHERE user_net_perm.login=@user;
                INSERT IGNORE INTO user_perms SELECT @user,host_id FROM alienvault.host_net_reference, alienvault.user_net_perm WHERE host_net_reference.net_id=user_net_perm.asset_id AND user_net_perm.login=@user;
            ELSE
                INSERT IGNORE INTO user_perms SELECT @user,id FROM alienvault.host;
                INSERT IGNORE INTO user_perms SELECT @user,id FROM alienvault.net;
            END IF;
        END IF;
        INSERT IGNORE INTO user_perms SELECT @user,sensor_id FROM alienvault.user_sensor_perm WHERE user_sensor_perm.login=@user;
    END IF;
    SELECT COUNT(component_id) as entries FROM user_perms WHERE user_perms.login=@user;
END$$

DROP PROCEDURE IF EXISTS addcol$$
CREATE PROCEDURE addcol() BEGIN

    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'alienvault_api' AND TABLE_NAME = 'status_action')
    THEN
        DROP TABLE IF EXISTS status_action;
        DELETE FROM status_message;
    END IF;

    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'alienvault_api' AND TABLE_NAME = 'status_message_action')
    THEN
        DROP TABLE IF EXISTS status_message_action;
        DELETE FROM status_message;
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault_api' AND TABLE_NAME = 'status_message' AND COLUMN_NAME = 'type')
    THEN
        ALTER TABLE status_message MODIFY COLUMN `id` BINARY(16) NOT NULL;
        ALTER TABLE status_message MODIFY COLUMN `level` TINYINT(1) NOT NULL DEFAULT 1;
        ALTER TABLE status_message CHANGE COLUMN `content` `title` TEXT NULL;
        ALTER TABLE status_message ADD COLUMN `type`  VARCHAR(20) NOT NULL;
        ALTER TABLE status_message ADD COLUMN `expire` DATETIME NULL;
        ALTER TABLE status_message ADD COLUMN `actions` TEXT NULL;
        ALTER TABLE status_message ADD COLUMN `alternative_actions` TEXT NULL;
        ALTER TABLE status_message ADD COLUMN `message_role` TEXT NULL;
        ALTER TABLE status_message ADD COLUMN `action_role` TEXT NULL;
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault_api' AND TABLE_NAME = 'current_status' AND COLUMN_NAME = 'id')
    THEN
        DELETE FROM current_status;
        ALTER TABLE current_status DROP PRIMARY KEY;
        ALTER TABLE current_status MODIFY COLUMN `message_id` BINARY(16) NOT NULL;
        ALTER TABLE current_status MODIFY COLUMN `component_id` BINARY(16) NULL;
        ALTER TABLE current_status MODIFY COLUMN `component_type` ENUM('net', 'host', 'user', 'sensor', 'server','system','external') NOT NULL;
        ALTER TABLE current_status ADD COLUMN `id` BINARY(16) NOT NULL FIRST;
        ALTER TABLE current_status ADD PRIMARY KEY (`id`);
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault_api' AND TABLE_NAME = 'current_status' AND COLUMN_NAME = 'suppressed')
    THEN
        ALTER TABLE current_status CHANGE COLUMN `viewed` `viewed` TINYINT(1) NULL DEFAULT 0;
        ALTER TABLE current_status CHANGE COLUMN `supressed` `suppressed` TINYINT(1) NULL DEFAULT 0;
        ALTER TABLE current_status CHANGE COLUMN `supressed_time` `suppressed_time` TIMESTAMP NULL;
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault_api' AND TABLE_NAME = 'status_message' AND COLUMN_NAME = 'source')
    THEN
        ALTER TABLE status_message ADD COLUMN `source` VARCHAR(32) NULL;
    END IF;
    
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault_api' AND TABLE_NAME = 'current_status' AND INDEX_NAME = 'message')
    THEN
        ALTER TABLE current_status ADD INDEX `message` (`message_id` ASC);
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault_api' AND TABLE_NAME = 'current_status' AND INDEX_NAME = 'component')
    THEN
        ALTER TABLE current_status ADD INDEX `component` (`component_id` ASC);
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault_api' AND TABLE_NAME = 'current_status' AND INDEX_NAME = 'viewed')
    THEN
        ALTER TABLE current_status ADD INDEX `viewed` (`viewed` ASC);
    END IF;

    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault_api' AND TABLE_NAME = 'status_message' AND COLUMN_NAME = 'level' AND DATA_TYPE = 'varchar')
    THEN
        ALTER TABLE status_message MODIFY COLUMN `level` TINYINT(1) NOT NULL DEFAULT 1;
    END IF;
    
END$$

DROP TRIGGER IF EXISTS `delete_cs`$$
CREATE TRIGGER `delete_cs` BEFORE DELETE ON `status_message` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN

  DELETE FROM current_status WHERE message_id = OLD.id;

END$$

DELIMITER ;

CALL addcol();
DROP PROCEDURE addcol;

USE alienvault_siem;

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

DELIMITER $$

DROP PROCEDURE IF EXISTS alienvault.kill_queries$$
CREATE PROCEDURE alienvault.kill_queries () SQL SECURITY INVOKER
BEGIN
    DECLARE query_id INT;
    DECLARE iteration_complete INT DEFAULT 0;
    DECLARE select_cursor CURSOR FOR SELECT id FROM INFORMATION_SCHEMA.PROCESSLIST WHERE user() REGEXP concat('^',user,'@') AND ID <> connection_id();
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET iteration_complete=1;
    
    OPEN select_cursor;
    cursor_loop: LOOP
        FETCH select_cursor INTO query_id;
        IF iteration_complete THEN
            LEAVE cursor_loop;
        END IF;
        KILL QUERY query_id;
    END LOOP;
    CLOSE select_cursor;
END$$

CALL alienvault.kill_queries()$$

DROP TRIGGER IF EXISTS count_acid_event$$
CREATE TRIGGER `count_acid_event` AFTER INSERT ON `acid_event` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_count IS NULL THEN
        INSERT IGNORE INTO alienvault_siem.po_acid_event (ctx, device_id, plugin_id, plugin_sid, ip_src, ip_dst, timestamp, src_host, dst_host, src_net, dst_net, cnt) VALUES (NEW.ctx, NEW.device_id, NEW.plugin_id, NEW.plugin_sid, NEW.ip_src, NEW.ip_dst, DATE_FORMAT(NEW.timestamp, '%Y-%m-%d %H:00:00'), IFNULL(NEW.src_host,0x00000000000000000000000000000000), IFNULL(NEW.dst_host,0x00000000000000000000000000000000), IFNULL(NEW.src_net,0x00000000000000000000000000000000), IFNULL(NEW.dst_net,0x00000000000000000000000000000000),1) ON DUPLICATE KEY UPDATE cnt = cnt + 1;
        INSERT IGNORE INTO alienvault_siem.ac_acid_event (ctx, device_id, plugin_id, plugin_sid, timestamp, src_host, dst_host, src_net, dst_net, cnt) VALUES (NEW.ctx, NEW.device_id, NEW.plugin_id, NEW.plugin_sid, DATE_FORMAT(NEW.timestamp, '%Y-%m-%d %H:00:00'), IFNULL(NEW.src_host,0x00000000000000000000000000000000), IFNULL(NEW.dst_host,0x00000000000000000000000000000000), IFNULL(NEW.src_net,0x00000000000000000000000000000000), IFNULL(NEW.dst_net,0x00000000000000000000000000000000),1) ON DUPLICATE KEY UPDATE cnt = cnt + 1;
    END IF;
END$$

DROP PROCEDURE IF EXISTS addcol$$
CREATE PROCEDURE addcol() BEGIN

    SELECT count(*) FROM alienvault.config WHERE conf='ossim_server_version' AND value LIKE '%pro%' LIMIT 1 INTO @is_pro;
    IF @is_pro = 1 THEN
        DROP TRIGGER IF EXISTS count_acid_event;
    END IF;
    
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault_siem' AND TABLE_NAME = 'ac_acid_event' AND COLUMN_NAME = 'day')
    THEN
        ALTER TABLE ac_acid_event CHANGE COLUMN `day` `timestamp` DATETIME NOT NULL;
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault_siem' AND TABLE_NAME = 'ac_acid_event' AND INDEX_NAME = 'device_id')
    THEN
        ALTER TABLE ac_acid_event ADD INDEX `device_id` (`device_id` ASC);
        ALTER TABLE ac_acid_event DROP INDEX `plugin_id`, ADD INDEX `plugin_id` (`plugin_id` ASC, `plugin_sid` ASC);
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault_siem' AND TABLE_NAME = 'ac_acid_event' AND INDEX_NAME = 'src_net')
    THEN
        ALTER TABLE ac_acid_event ADD INDEX `src_net` (`src_net` ASC), ADD INDEX `dst_net` (`dst_net` ASC);
    END IF;

END$$

CALL addcol()$$
DROP PROCEDURE addcol$$

DROP TABLE IF EXISTS ip_acid_event$$
DROP TABLE IF EXISTS ah_acid_event$$
DROP TRIGGER IF EXISTS del_count_acid_event$$
DROP EVENT IF EXISTS count_events$$

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

DROP PROCEDURE IF EXISTS `delete_events`$$
CREATE PROCEDURE delete_events( tmp_table VARCHAR(64) )
BEGIN
    SET @query = CONCAT('DELETE aux FROM alienvault_siem.reputation_data aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.event_id WHERE tmp.id IS NOT NULL');
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

DELIMITER ;

USE alienvault;
SET AUTOCOMMIT=0;

DELIMITER $$

DROP PROCEDURE IF EXISTS _update_vuln_assets$$
CREATE PROCEDURE _update_vuln_assets ( IN _job_id INT )
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE _jid INT DEFAULT 0;
    DECLARE _targets TEXT;
    DECLARE x INT DEFAULT 0;
    DECLARE y INT DEFAULT 0;
    DECLARE cur1 CURSOR FOR SELECT id,targets FROM _tmp_jobs;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    
    CREATE TEMPORARY TABLE _tmp_jobs (id int(11) NOT NULL, targets TEXT) ENGINE=MEMORY;
    
    IF _job_id = 0 THEN
        SET @jtype = 0;
        INSERT IGNORE INTO _tmp_jobs SELECT id,meth_TARGET FROM vuln_job_schedule;
    ELSE
        SET @jtype = 1;
        INSERT IGNORE INTO _tmp_jobs SELECT id,meth_TARGET FROM vuln_jobs WHERE id=_job_id;
    END IF;
    
    OPEN cur1;

    REPEAT
        FETCH cur1 INTO _jid,_targets;
        IF NOT done THEN
            DELETE FROM vuln_job_assets WHERE job_id=_jid AND job_type=@jtype;
            -- Line by line iterator
            SELECT LENGTH(_targets) - LENGTH(REPLACE(_targets, '\n', '')) INTO @nCommas;
            SET y = 1; 
            SET x = @nCommas + 1; 
            SET @query = '';
            WHILE y <= x DO 
                SELECT _split_string(_targets, '\n', y) INTO @target;
                IF @target REGEXP '.*#.*' THEN
                    SELECT _split_string(@target, '#', 1) INTO @uuid;
                    SELECT _split_string(@target, '#', 2) INTO @asset_type;
                    -- asset 
                    INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) VALUES (_jid, @jtype, UNHEX(@uuid));
                    -- host groups
                    IF @asset_type = 'hostgroup' THEN
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT _jid, @jtype, host_id FROM host_group_reference WHERE host_group_id=UNHEX(@uuid);
                    -- network groups
                    ELSEIF @asset_type = 'netgroup' THEN
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT _jid, @jtype, net_id FROM net_group_reference WHERE net_group_id=UNHEX(@uuid);
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT DISTINCT _jid, @jtype, host_id FROM host_net_reference, net_group_reference WHERE host_net_reference.net_id = net_group_reference.net_id AND net_group_reference.net_group_id=UNHEX(@uuid);
                    -- networks
                    ELSEIF @asset_type REGEXP '[[.slash.]][[:digit:]]' THEN
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT _jid, @jtype, host_id FROM host_net_reference WHERE net_id=UNHEX(@uuid);
                    END IF;
                END IF;
                SET  y = y + 1; 
            END WHILE; 
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur1;
    
    DROP TABLE IF EXISTS _tmp_jobs;
END$$

DROP PROCEDURE IF EXISTS host_filter$$
CREATE PROCEDURE host_filter(
    IN login VARCHAR(64), -- string like - 'admin'
    IN ftype VARCHAR(16), -- string - 'host' 'group' 'network'
    IN drop_table INT, -- boolean value - 0 or 1
    IN events_filter INT, -- boolean value - 0 or 1
    IN alarms_filter INT, -- boolean value - 0 or 1
    IN vulns_from INT, -- integer between 1 and 7
    IN vulns_to INT, -- integer between 1 and 7 >= vuln_from
    IN nagios CHAR, -- integer 0 => not configured, 1 => up, 2 => down
    IN asset_value_from CHAR, -- interger between 0 and 5
    IN asset_value_to CHAR, -- interger between 0 and 5 >= asset_value_from
    IN last_added_from VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
    IN last_added_to VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
    IN last_updated_from VARCHAR(19), -- datetime - '2013-08-15 22:30:00'
    IN last_updated_to VARCHAR(19), -- datetime - '2013-08-15 22:30:00'
    IN fqdn TEXT, -- free string (% is allowed)
    IN ip_range TEXT, -- ip ranges 192.168.1.1,192.168.1.255;192.168.1.2,192.168.1.2
    IN networks TEXT, -- network hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
    IN agroups TEXT, -- asset group hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
    IN labels TEXT, -- tag hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
    IN os TEXT, -- unquoted string - windows vista,linux debian
    IN model TEXT, -- unquoted string - cisco asa,realtek x5
    IN cpe TEXT, -- unquoted string - cpe:/o:yamaha:srt100:10.00.46,cpe:/o:microsoft:virtual_machine_manager:2007
    IN device_types TEXT, -- unquoted string typeid,subtypeid - 1,0;4,404
    IN services TEXT, -- quoted string port,protocol,'service' - 80,6,'http';0,1,'PING'
    IN sensors TEXT, -- sensor hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
    IN locations TEXT, -- location hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
    IN group_name TEXT, -- free string (% is allowed)
    IN net_name TEXT, -- free string (% is allowed)
    IN net_cidr TEXT -- free string (% is allowed)
)
BEGIN

    DECLARE x INT DEFAULT 0;
    DECLARE y INT DEFAULT 0;

    CREATE TABLE IF NOT EXISTS user_host_filter (
        login VARCHAR(64) NOT NULL,
        asset_id VARBINARY(16) NOT NULL,
        PRIMARY KEY (`asset_id`,`login`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    DROP TEMPORARY TABLE IF EXISTS filters_tmp;
    DROP TEMPORARY TABLE IF EXISTS filters_add;
    CREATE TEMPORARY TABLE filters_tmp (id BINARY(16) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MEMORY;
    CREATE TEMPORARY TABLE filters_add (id BINARY(16) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MEMORY;
    REPLACE INTO filters_tmp SELECT id FROM host;
    
    START TRANSACTION;

        -- Host with events
        IF events_filter = 1
        THEN
            TRUNCATE filters_add;
            REPLACE INTO filters_add SELECT src_host as id FROM alienvault_siem.po_acid_event UNION DISTINCT SELECT DISTINCT dst_host as id FROM alienvault_siem.po_acid_event;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host with alarms
        IF alarms_filter = 1
        THEN
            TRUNCATE filters_add;
            REPLACE INTO filters_add SELECT ah.id_host as id FROM alienvault.alarm_hosts ah, alienvault.alarm a WHERE a.backlog_id=ah.id_alarm;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host with vulnerabilities in range
        IF vulns_from > 0 AND vulns_to > 0 AND vulns_from <= vulns_to
        THEN
            TRUNCATE filters_add;
            REPLACE INTO filters_add SELECT h.id FROM alienvault.host h, alienvault.host_ip hi, alienvault.vuln_nessus_latest_results lr WHERE hi.host_id=h.id AND hi.ip=inet6_aton(lr.hostIP) AND h.ctx=lr.ctx AND lr.risk BETWEEN vulns_from AND vulns_to;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Availability 
        IF nagios <> ''
        THEN
            TRUNCATE filters_add;
            IF nagios = '0' THEN
                REPLACE INTO filters_add SELECT h.id FROM alienvault.host h LEFT JOIN alienvault.host_scan ha ON ha.host_id=h.id AND ha.plugin_id=2007 and ha.plugin_sid=0 WHERE ha.host_id IS NULL;
            ELSEIF nagios = '1' THEN
                REPLACE INTO filters_add SELECT h.id FROM alienvault.host h, alienvault.host_scan ha WHERE ha.host_id=h.id AND ha.plugin_id=2007 and ha.plugin_sid=0 AND ha.status=1;
            ELSEIF nagios = '2' THEN
                REPLACE INTO filters_add SELECT h.id FROM alienvault.host h, alienvault.host_scan ha WHERE ha.host_id=h.id AND ha.plugin_id=2007 and ha.plugin_sid=0 AND ha.status=2;
            END IF;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host with asset value in range
        IF asset_value_from <> '' AND asset_value_to <> '' AND asset_value_from <= asset_value_to AND ftype <> 'network'
        THEN
            TRUNCATE filters_add;
            REPLACE INTO filters_add SELECT h.id FROM alienvault.host h WHERE h.asset BETWEEN asset_value_from AND asset_value_to;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host with asset created date greater or iqual
        IF last_added_from <> '' AND last_added_to <> ''
        THEN
            TRUNCATE filters_add;
            REPLACE INTO filters_add SELECT h.id FROM alienvault.host h WHERE h.created BETWEEN last_added_from AND last_added_to;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host with asset updated date greater or iqual
        IF last_updated_from <> '' AND last_updated_to <> ''
        THEN
            TRUNCATE filters_add;
            REPLACE INTO filters_add SELECT h.id FROM alienvault.host h WHERE h.updated BETWEEN last_updated_from AND last_updated_to;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host with hostname or fqdn
        IF fqdn <> ''
        THEN
            TRUNCATE filters_add;
            SELECT LENGTH(fqdn) - LENGTH(REPLACE(fqdn, ';', '')) INTO @nCommas;
            SET y = 1; 
            SET x = @nCommas + 1; 
            SET @query = '';
            WHILE y <= x DO 
               SELECT _split_string(fqdn, ';', y) INTO @range; 
               SET @query = CONCAT(@query,'h.fqdns like "%',@range,'%" OR h.hostname like "%',@range,'%" OR ');
               SET  y = y + 1;
            END WHILE; 
            SET @query = CONCAT('REPLACE INTO filters_add SELECT h.id FROM alienvault.host h WHERE ',substring(@query,1,length(@query)-4),';');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host with ip in range
        IF ip_range <> ''
        THEN
            TRUNCATE filters_add;
            SELECT LENGTH(ip_range) - LENGTH(REPLACE(ip_range, ';', '')) INTO @nCommas;
            SET y = 1; 
            SET x = @nCommas + 1; 
            SET @query = '';
            WHILE y <= x DO 
               SELECT _split_string(ip_range, ';', y) INTO @range; 
               SET @query = CONCAT(@query,'(hi.ip between inet6_aton("',REPLACE(@range,',','") AND inet6_aton("'),'")) OR '); 
               SET  y = y + 1; 
            END WHILE; 
            SET @query = CONCAT('REPLACE INTO filters_add SELECT h.id FROM alienvault.host h, alienvault.host_ip hi WHERE hi.host_id=h.id AND (',substring(@query,1,length(@query)-4),');');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host in a list of network
        IF networks <> ''
        THEN
            TRUNCATE filters_add;
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_net_reference r WHERE r.host_id=h.id AND r.net_id in (',networks,');');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host in a list of asset group
        IF agroups <> ''
        THEN
            TRUNCATE filters_add;
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_group_reference r WHERE r.host_id=h.id AND r.host_group_id in (',agroups,');');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host within a list of labels/tags
        IF labels <> ''
        THEN
            TRUNCATE filters_add;
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.component_tags t WHERE t.id_component=h.id AND t.id_tag in (',labels,');');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host within a list of operating-systems
        IF os <> ''
        THEN
            TRUNCATE filters_add;
            SET @str = REPLACE(os,',','","');
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_properties hp WHERE hp.host_id=h.id AND hp.property_ref=3 AND hp.value in ("',@str,'");');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host within a list of models
        IF model <> ''
        THEN
            TRUNCATE filters_add;
            SET @str = REPLACE(model,',','","');
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_properties hp WHERE hp.host_id=h.id AND hp.property_ref=14 AND hp.value in ("',@str,'");');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host that contains a software with cpe
        IF cpe <> ''
        THEN
            TRUNCATE filters_add;
            SET @str = REPLACE(cpe,',','","');
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_software s WHERE s.host_id=h.id AND s.cpe in ("',@str,'");');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host from a device type or subtype
        IF device_types <> ''
        THEN
            TRUNCATE filters_add;
            SET @str = REPLACE(device_types,';','),(');
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_types ht WHERE ht.host_id=h.id AND (ht.type, ht.subtype) in ((',@str,'));');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host with services (port, protocol, service)
        IF services <> ''
        THEN
            TRUNCATE filters_add;
            SET @str = REPLACE(services,';','),(');
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_services hs WHERE hs.host_id=h.id AND (hs.port, hs.protocol, hs.service) in ((',@str,'));');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host in a network list
        IF sensors <> ''
        THEN
            TRUNCATE filters_add;
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_sensor_reference r WHERE r.host_id=h.id AND r.sensor_id in (',sensors,');');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host with locations
        IF locations <> ''
        THEN
            TRUNCATE filters_add;
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_sensor_reference r, location_sensor_reference l WHERE h.id=r.host_id AND r.sensor_id=l.sensor_id AND l.location_id in (',locations,');');
            PREPARE sql_query from @query;
            EXECUTE sql_query;
            DEALLOCATE PREPARE sql_query;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Final Results
        IF ftype = 'host'
        THEN
            IF drop_table = 1
            THEN
                DELETE FROM user_host_filter WHERE user_host_filter.login=login;
                INSERT INTO user_host_filter SELECT login,id from filters_tmp;
            ELSE
                DELETE h FROM user_host_filter h LEFT JOIN filters_tmp t ON h.asset_id=t.id WHERE h.login=login AND t.id IS NULL;
            END IF;
        ELSEIF ftype = 'group'
        THEN
            DROP TEMPORARY TABLE IF EXISTS filters_tmpg;
            CREATE TEMPORARY TABLE filters_tmpg (id binary(16) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MEMORY;

            IF group_name <> ''
            THEN
                SELECT LENGTH(group_name) - LENGTH(REPLACE(group_name, ';', '')) INTO @nCommas;
                SET y = 1; 
                SET x = @nCommas + 1; 
                SET @query = '';
                WHILE y <= x DO 
                   SELECT _split_string(group_name, ';', y) INTO @range; 
                   SET @query = CONCAT(@query,'hg.name like "%',@range,'%" OR ');
                   SET  y = y + 1;
                END WHILE; 
                SET @query = CONCAT('INSERT IGNORE INTO filters_tmpg SELECT hg.id FROM host_group hg WHERE ',substring(@query,1,length(@query)-4));
                PREPARE sql_query from @query;
                EXECUTE sql_query;
                DEALLOCATE PREPARE sql_query;
            ELSE
                INSERT IGNORE INTO filters_tmpg SELECT host_group_id FROM host_group_reference, filters_tmp WHERE id=host_id;
            END IF;

            IF drop_table = 1
            THEN
                DELETE FROM user_host_filter WHERE user_host_filter.login=login;
                INSERT INTO user_host_filter SELECT login,id from filters_tmpg;
            ELSE
                DELETE h FROM user_host_filter h LEFT JOIN filters_tmpg t ON h.asset_id=t.id WHERE h.login=login AND t.id IS NULL;
            END IF;
        ELSEIF ftype = 'network'
        THEN
            DROP TEMPORARY TABLE IF EXISTS filters_tmpg;
            CREATE TEMPORARY TABLE filters_tmpg (id binary(16) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MEMORY;
            
            SET @query = '';
            -- Network name
            IF net_name <> ''
            THEN
                SELECT LENGTH(net_name) - LENGTH(REPLACE(net_name, ';', '')) INTO @nCommas;
                SET y = 1; 
                SET x = @nCommas + 1; 
                WHILE y <= x DO 
                   SELECT _split_string(net_name, ';', y) INTO @range; 
                   SET @query = CONCAT(@query,'n.name like "%',@range,'%" OR ');
                   SET  y = y + 1;
                END WHILE; 
            END IF;
            
            -- Network CIDR
            IF net_cidr <> ''
            THEN
                SELECT LENGTH(net_cidr) - LENGTH(REPLACE(net_cidr, ';', '')) INTO @nCommas;
                SET y = 1; 
                SET x = @nCommas + 1; 
                WHILE y <= x DO 
                   SELECT _split_string(net_cidr, ';', y) INTO @range; 
                   SET @query = CONCAT(@query,'n.ips like "%',@range,'%" OR ');
                   SET  y = y + 1;
                END WHILE; 
            END IF;
            
            IF @query <> ''
            THEN
                SET @query = CONCAT('INSERT IGNORE INTO filters_tmpg SELECT n.id FROM net n WHERE ',substring(@query,1,length(@query)-4));
                PREPARE sql_query from @query;
                EXECUTE sql_query;
                DEALLOCATE PREPARE sql_query;
            ELSE
                INSERT IGNORE INTO filters_tmpg SELECT net_id FROM host_net_reference, filters_tmp WHERE id=host_id;
            END IF;

            IF asset_value_from <> '' AND asset_value_to <> '' AND asset_value_from <= asset_value_to
            THEN
                TRUNCATE filters_add;
                REPLACE INTO filters_add SELECT n.id FROM alienvault.net n WHERE n.asset BETWEEN asset_value_from AND asset_value_to;
                DELETE ft FROM filters_tmpg ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
            END IF;
            
            IF drop_table = 1
            THEN
                DELETE FROM user_host_filter WHERE user_host_filter.login=login;
                INSERT INTO user_host_filter SELECT login,id from filters_tmpg;
            ELSE
                DELETE h FROM user_host_filter h LEFT JOIN filters_tmpg t ON h.asset_id=t.id WHERE h.login=login AND t.id IS NULL;
            END IF;
        END IF;

    COMMIT;

    SELECT COUNT(asset_id) as assets FROM user_host_filter WHERE user_host_filter.login=login;
END$$

DROP PROCEDURE IF EXISTS acl_get_allowed_groups$$
CREATE PROCEDURE acl_get_allowed_groups( user VARCHAR(64), uuid VARCHAR(64) )
BEGIN
    SELECT host_where(user,'host.') INTO @perms;
    SET @query = '';
    IF uuid = '' THEN
        SET @query = CONCAT('SELECT DISTINCT HEX(host_group.id) as host_id FROM host_group,host,host_group_reference WHERE host_group.id=host_group_reference.host_group_id AND host_group_reference.host_id=host.id ', @perms);
    ELSE
        SET @query = CONCAT('SELECT DISTINCT HEX(host_group.id) as host_id FROM host_group,host,host_group_reference WHERE host_group.id=host_group_reference.host_group_id AND host_group_reference.host_id=host.id AND host_group.id=UNHEX("', uuid, '") ', @perms);
    END IF;
    PREPARE stmt1 FROM @query;
    EXECUTE stmt1;
    DEALLOCATE PREPARE stmt1;
END$$

DROP PROCEDURE IF EXISTS _delete_orphan_backlogs$$
CREATE PROCEDURE _delete_orphan_backlogs()
BEGIN
    DECLARE num_events INT;
    
    CREATE TEMPORARY TABLE IF NOT EXISTS tmpbckdel (backlog_id BINARY(16) NOT NULL, PRIMARY KEY ( backlog_id )) ENGINE=INNODB;
    CREATE TEMPORARY TABLE IF NOT EXISTS tmpevndel (event_id BINARY(16) NOT NULL, PRIMARY KEY ( event_id )) ENGINE=INNODB;
    INSERT IGNORE INTO tmpbckdel SELECT id FROM backlog WHERE timestamp = '1970-01-01 00:00:00';
    
    IF EXISTS (SELECT 1 FROM tmpbckdel LIMIT 1) THEN
    
        INSERT IGNORE INTO tmpevndel SELECT be.event_id FROM backlog_event be, backlog b WHERE be.backlog_id = b.id AND b.timestamp = '1970-01-01 00:00:00';
    
        CREATE TEMPORARY TABLE IF NOT EXISTS tmpexclude (event_id BINARY(16) NOT NULL, PRIMARY KEY ( event_id )) ENGINE=MEMORY;
        INSERT IGNORE INTO tmpexclude SELECT t.event_id FROM tmpevndel t, backlog_event be LEFT JOIN tmpbckdel b ON be.backlog_id = b.backlog_id WHERE be.event_id = t.event_id AND b.backlog_id IS NULL;
        DELETE t FROM tmpevndel t, tmpexclude ex WHERE t.event_id = ex.event_id;
        DROP TABLE tmpexclude;
        
        -- Delete events
        CREATE TEMPORARY TABLE _ttmp (id binary(16) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MEMORY;
        SELECT COUNT(event_id) FROM tmpevndel INTO @num_events;
        
        WHILE @num_events > 0 DO
            INSERT INTO _ttmp SELECT event_id FROM tmpevndel LIMIT 10000;
            DELETE e FROM event e, _ttmp t WHERE e.id = t.id;
            DELETE i FROM idm_data i, _ttmp t WHERE i.event_id = t.id;
            DELETE x FROM extra_data x, _ttmp t WHERE x.event_id= t.id;
            DELETE te FROM tmpevndel te, _ttmp t WHERE te.event_id=t.id;
            TRUNCATE TABLE _ttmp;
            SET @num_events = @num_events - 10000;
        END WHILE;
        
        -- Delete backlogs
        TRUNCATE TABLE _ttmp;
        SELECT COUNT(backlog_id) FROM tmpbckdel INTO @num_events;
        
        WHILE @num_events > 0 DO
            INSERT INTO _ttmp SELECT backlog_id FROM tmpbckdel LIMIT 10000;
            DELETE be FROM backlog_event be, _ttmp t WHERE be.backlog_id=t.id;
            DELETE b, ta, c, h, n, a FROM backlog b INNER JOIN _ttmp t ON t.id=b.id LEFT JOIN component_tags ta ON ta.id_component = b.id LEFT JOIN alarm_ctxs c ON c.id_alarm = b.id LEFT JOIN alarm_nets n ON n.id_alarm = b.id LEFT JOIN alarm_hosts h ON h.id_alarm = b.id LEFT JOIN alarm a ON a.backlog_id = b.id;
            DELETE te FROM tmpbckdel te, _ttmp t WHERE te.backlog_id=t.id;
            TRUNCATE TABLE _ttmp;
            SET @num_events = @num_events - 10000;
        END WHILE;
    
        DROP TABLE _ttmp;
        
    END IF;
    DROP TABLE tmpevndel;
    DROP TABLE tmpbckdel;
END$$

DROP PROCEDURE IF EXISTS user_del$$
CREATE PROCEDURE user_del (
    IN login VARCHAR(64)
) 
BEGIN
    SET @user = login;
    DELETE FROM alienvault.users WHERE users.login=@user;
    DELETE FROM alienvault.user_host_perm WHERE user_host_perm.login=@user;
    DELETE FROM alienvault.user_net_perm WHERE user_net_perm.login=@user;
    DELETE FROM alienvault.user_sensor_perm WHERE user_sensor_perm.login=@user;
    DELETE FROM alienvault.user_ctx_perm WHERE user_ctx_perm.login=@user;
    DELETE FROM alienvault_api.user_perms WHERE user_perms.login=@user;
    DELETE FROM alienvault.acl_entities_users WHERE acl_entities_users.login=@user;
    DELETE FROM alienvault.acl_assets WHERE acl_assets.login=@user;
    DELETE FROM alienvault.acl_entities_users WHERE acl_entities_users.login=@user;
    DELETE FROM alienvault.acl_login_sensors WHERE acl_login_sensors.login=@user;
    DELETE FROM alienvault.custom_report_scheduler WHERE custom_report_scheduler.user=@user;
    UPDATE alienvault.user_config SET user_config.login='admin' WHERE user_config.login=@user AND category='custom_report';
    DELETE FROM alienvault.dashboard_tab_config WHERE dashboard_tab_config.user=@user;
    DELETE FROM alienvault.dashboard_tab_options WHERE dashboard_tab_options.user=@user;
    DELETE FROM alienvault.user_config WHERE user_config.login=@user;
END$$

DROP PROCEDURE IF EXISTS user_add$$
CREATE PROCEDURE user_add (
    IN login VARCHAR(64),
    IN passwd VARCHAR(128),
    IN is_admin INT
) 
BEGIN
    SET @user = login;
    
    IF EXISTS (SELECT 1 FROM users WHERE users.login=@user)
    THEN
        SELECT CONCAT(@user,' already exist') as status;
    ELSE
        SET @pass = MD5(passwd);
        SET @uuid = SUBSTRING(SHA1(CONCAT(login,'#',passwd)),1,32);
        SET @admin = IF(is_admin=0,0,1);
        SELECT HEX(id) FROM acl_templates LIMIT 1 INTO @template_id;
        INSERT INTO users (login, login_method, name, pass, email, company, department, template_id, language, first_login, timezone, is_admin, uuid, last_logon_try) VALUES (@user, 'pass', @user, @pass, '', '', '', UNHEX(@template_id), 'en_GB', 0, 'US/Eastern', @admin, UNHEX(@uuid), now());
        INSERT INTO acl_entities_users (login, entity_id) VALUES (@user, (SELECT UNHEX(REPLACE (value, '-', '')) FROM config WHERE conf = 'default_context_id'));
        INSERT INTO dashboard_tab_options (`id`, `user`, `visible`, `tab_order`) VALUES (1, @user, 1, 11), (2, @user, 1, 10), (3, @user, 1, 9), (4, @user, 1, 8), (5, @user, 0, 7), (6, @user, 1, 6), (8, @user, 0, 5), (9, @user, 0, 4), (10, @user, 0, 3), (11, @user, 0, 2), (12, @user, 0, 1);
        IF @admin=0 THEN
            CALL acl_user_permissions(@user);
        END IF;
        SELECT CONCAT(@user,' has been successfully created') as status;
    END IF;
    
END$$

DROP FUNCTION IF EXISTS get_ip_by_sensor_id$$
CREATE FUNCTION get_ip_by_sensor_id( component VARCHAR(64) )
RETURNS TEXT
BEGIN
    SET @uuid = REPLACE(component,'-','');
    SET @ip = NULL;
    IF EXISTS (SELECT 1 FROM sensor WHERE id=UNHEX(@uuid)) THEN
        SELECT IFNULL(vpn_ip,IFNULL(ha_ip,IFNULL(admin_ip,ip))) FROM sensor LEFT JOIN system ON sensor.id=system.sensor_id WHERE sensor.id=UNHEX(@uuid) into @ip;
    END IF;
    RETURN INET6_NTOA(@ip);
END$$

DROP FUNCTION IF EXISTS get_ip_by_server_id$$
CREATE FUNCTION get_ip_by_server_id( component VARCHAR(64) )
RETURNS TEXT
BEGIN
    SET @uuid = REPLACE(component,'-','');
    SET @ip = NULL;
    IF EXISTS (SELECT 1 FROM server WHERE id=UNHEX(@uuid)) THEN
        SELECT IFNULL(vpn_ip,IFNULL(ha_ip,IFNULL(admin_ip,ip))) FROM server LEFT JOIN system ON server.id=system.server_id WHERE server.id=UNHEX(@uuid) into @ip;
    END IF;
    RETURN INET6_NTOA(@ip);
END$$

DROP FUNCTION IF EXISTS get_ip_by_system_id$$
CREATE FUNCTION get_ip_by_system_id( component VARCHAR(64) )
RETURNS TEXT
BEGIN
    SET @uuid = REPLACE(component,'-','');
    SET @ip = NULL;
    IF EXISTS (SELECT 1 FROM system WHERE id=UNHEX(@uuid)) THEN
        SELECT IFNULL(vpn_ip,IFNULL(ha_ip,admin_ip)) FROM system WHERE id=UNHEX(@uuid) into @ip;
    END IF;
    RETURN INET6_NTOA(@ip);
END$$

DROP PROCEDURE IF EXISTS incident_ticket_populate$$
CREATE PROCEDURE `incident_ticket_populate`(incident_id INT, src_ip VARBINARY(16), dst_ip VARBINARY(16), prio INT)
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE count INT;
  DECLARE cnt_src, cnt_dst, i INT;
  DECLARE name, subname VARCHAR(255);
  DECLARE first_occ, last_occ TIMESTAMP;
  DECLARE source VARCHAR(39);
  DECLARE dest VARCHAR(39);

  DECLARE cur1 CURSOR FOR select count(*) as cnt,  inet6_ntoa(event.src_ip) as src, inet6_ntoa(event.dst_ip) as dst, plugin.name, plugin_sid.name, min(timestamp) as frst, max(timestamp) as last, count(distinct(event.src_ip)) as cnt_src, count(distinct(event.dst_ip)) as cnt_dst from event, plugin, plugin_sid where (event.src_ip = src_ip or event.dst_ip = src_ip or event.src_ip = dst_ip or event.dst_ip =dst_ip ) and timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY) AND alienvault.plugin.id = event.plugin_id and alienvault.plugin_sid.sid = event.plugin_sid and alienvault.plugin_sid.plugin_id = event.plugin_id group by event.plugin_id, event.plugin_sid ORDER by cnt DESC limit 50;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  SET i = (SELECT IFNULL(MAX(id), 0) + 1 FROM incident_ticket);

OPEN cur1;

INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (i, incident_id, NOW()-1, "Open", prio, "admin", "The following tickets contain information about the top 50 event types the hosts have been generating during the last 7 days.");

SET i = i + 1;

  REPEAT
    FETCH cur1 INTO count, source, dest, name, subname, first_occ, last_occ, cnt_src, cnt_dst;
    IF NOT done THEN
        SET @desc = CONCAT( "Event Type: ",  name, "\nEvent Description: ", subname, "\nOcurrences: ",CAST(count AS CHAR), "\nFirst Ocurrence: ", CAST(first_occ AS CHAR(50)), "\nLast Ocurrence: ", CAST(last_occ AS CHAR(50)),"\nNumber of different sources: ", CAST(cnt_src AS CHAR), "\nNumber of different destinations: ", CAST(cnt_dst AS CHAR), "\nSource: ", source, "\nDest: ", dest);

        INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (i, incident_id, NOW(), "Open", prio, "admin", @desc);

        SET i = i + 1;

    END IF;

  UNTIL done END REPEAT;

  CLOSE cur1;
END$$

DROP PROCEDURE IF EXISTS sensor_update$$
CREATE PROCEDURE sensor_update( 
    user VARCHAR(64),
    uuid VARCHAR(64),
    ip VARCHAR(15),
    name VARCHAR(64),
    prio INT,
    port INT,
    tzone FLOAT,
    descr VARCHAR(255),
    ctxs TEXT,
    version VARCHAR(64),
    nagios INT,
    ntop INT,
    vuln INT,
    kismet INT,
    ids INT,
    passive_inventory INT,
    netflows INT
    )
SENSOR:BEGIN
    DECLARE x INT;
    DECLARE y INT;
    
    -- needed params
    IF INET6_ATON(ip) IS NOT NULL AND NOT user = '' THEN
    
        -- check params
        SELECT IF (uuid='',UPPER(REPLACE(UUID(), '-', '')),UPPER(uuid)) into @uuid;
        SET @ip = HEX(INET6_ATON(ip));
        SELECT IF (name='','(null)',name) into @name;
        SELECT IF (prio<1 OR prio>10,5,prio) into @prio;
        SELECT IF (port<1 OR port>65535,40001,port) into @port;
        SET @tzone = tzone;
        SET @descr = descr;
        SET @ctxs = ctxs;
        SET @version = version;
        SELECT IF (nagios<0 OR nagios>1,0,nagios) into @nagios;
        SELECT IF (ntop<0 OR ntop>1,1,ntop) into @ntop;
        SELECT IF (vuln<0 OR vuln>1,1,vuln) into @vuln;
        SELECT IF (kismet<0 OR kismet>1,0,kismet) into @kismet;
        SELECT IF (ids<0 OR ids>1,0,ids) into @ids;
        SELECT IF (passive_inventory<0 OR passive_inventory>1,0,passive_inventory) into @passive_inventory;
        SELECT IF (netflows<0 OR netflows>1,0,netflows) into @netflows;
        SELECT EXISTS (SELECT 1 from user_ctx_perm where login = user) INTO @get_ctx_where;
        SELECT `value` FROM config WHERE conf='encryption_key' into @system_uuid;
        SELECT `value` FROM config WHERE conf='nessus_host' into @nessus_host;
        
        -- check if exists with permissions
        IF @get_ctx_where THEN 
        	SELECT HEX(sensor.id),sensor.name FROM sensor, acl_sensors WHERE sensor.id=acl_sensors.sensor_id AND acl_sensors.entity_id in (SELECT ctx from user_ctx_perm where login = user) AND sensor.ip = UNHEX(@ip) INTO @sensor_id, @sensor_name;
        ELSE
        	SELECT HEX(sensor.id),sensor.name FROM sensor WHERE sensor.ip = UNHEX(@ip) INTO @sensor_id, @sensor_name;
        END IF;

        -- already exists        
        IF NOT @sensor_id = '' THEN
            IF NOT UPPER(@uuid) = UPPER(@sensor_id) THEN
                IF NOT @sensor_name = '(null)' THEN
                    SELECT CONCAT('Sensor ',ip,' already exists with different uuid') as status, NULL as sensor_id;
                    LEAVE SENSOR;
                END IF;
            ELSE
                -- Update existing
                SET @uuid = @sensor_id;
            END IF;
        END IF;
        
        -- insert
        SET @query = 'REPLACE INTO sensor (id, name, ip, priority, port, tzone, connect, descr) VALUES (UNHEX(?), ?, UNHEX(?), ?, ?, ?, 0, ?)';
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1 USING @uuid, @name, @ip, @prio, @port, @tzone, @descr;
        DEALLOCATE PREPARE stmt1;        

        SET @query = 'INSERT IGNORE INTO sensor_stats (sensor_id) VALUES (UNHEX(?))';
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1 USING @uuid;
        DEALLOCATE PREPARE stmt1;

        SET @query = 'REPLACE INTO sensor_properties (sensor_id,version,has_nagios,has_ntop,has_vuln_scanner,has_kismet,ids,passive_inventory,netflows) VALUES (UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?)';
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1 USING @uuid, @version, @nagios, @ntop, @vuln, @kismet, @ids, @passive_inventory, @netflows;
        DEALLOCATE PREPARE stmt1;        
        
        -- Contexts
        IF @ctxs = '' THEN
            -- get default context
            SELECT UPPER(REPLACE(`value`,'-','')) FROM config WHERE conf='default_context_id' into @ctxs;
        END IF;
        DELETE FROM acl_sensors where sensor_id = UNHEX(@uuid) and entity_id IN (SELECT id FROM acl_entities WHERE entity_type='context');
        SELECT LENGTH(@ctxs) - LENGTH(REPLACE(@ctxs, ',', '')) INTO @nCommas;
        SET y = 1;
        SET x = @nCommas + 1;
        WHILE y <= x DO
            SELECT _split_string(@ctxs, ',', y) INTO @range;
            SET @query = 'REPLACE INTO acl_sensors (entity_id, sensor_id) VALUES (UNHEX(?), UNHEX(?))';
            PREPARE stmt1 FROM @query;
            EXECUTE stmt1 USING @range, @uuid;
            DEALLOCATE PREPARE stmt1;             
            SET  y = y + 1;           
        END WHILE;     
        
        -- has_vuln_scanner?
        IF @vuln AND NOT EXISTS(SELECT 1 FROM vuln_nessus_servers WHERE hostname=@uuid) THEN
            SET @query = 'REPLACE INTO vuln_nessus_servers (name , description, hostname , port, user, PASSWORD, server_nversion, server_feedtype, server_feedversion, max_scans, current_scans, TYPE, site_code, owner, checkin_time, status , enabled) VALUES (?, "RemoteHost", ?, 9390, "ossim", AES_ENCRYPT("ossim",?), "2.2.10", "GPL only", "200704181215", 5, 0, "R", "", "admin", NULL , "A", 1)';
            PREPARE stmt1 FROM @query;
            EXECUTE stmt1 USING @name, @uuid, @system_uuid;
            DEALLOCATE PREPARE stmt1;      
        END IF;
        
        -- Unique sensor
        IF NOT EXISTS(SELECT 1 FROM sensor WHERE id != UNHEX(@uuid) AND name != '(null)') THEN

			CALL _orphans_of_sensor(@uuid);
            
        END IF;

        -- Default nessus host
        IF @nessus_host = '' THEN
            REPLACE INTO alienvault.config VALUES ('nessus_host',INET6_NTOA(UNHEX(@ip)));
            REPLACE INTO alienvault.config VALUES ('nessus_pass',AES_ENCRYPT('ossim',@system_uuid));
        END IF;

        -- Check if a asset exists
        IF NOT EXISTS(SELECT 1 FROM host h, host_ip hi WHERE h.id=hi.host_id AND hi.ip=UNHEX(@ip)) THEN
            SELECT REPLACE(UUID(), '-', '') into @asset_id;
            INSERT IGNORE INTO alienvault.host (id, ctx, hostname, asset, threshold_c, threshold_a, alert, persistence, nat, rrd_profile, descr, lat, lon, av_component) VALUES (UNHEX(@asset_id), UNHEX(@range), @name, '2', '30', '30', '0', '0', '', '', '', '0', '0', '1');
            INSERT IGNORE INTO alienvault.host_ip (host_id,ip) VALUES (UNHEX(@asset_id), UNHEX(@ip));
            INSERT IGNORE INTO alienvault.host_sensor_reference (host_id,sensor_id) VALUES (UNHEX(@asset_id), UNHEX(@uuid));
        ELSE
            INSERT IGNORE INTO alienvault.host_sensor_reference (host_id,sensor_id) VALUES ((SELECT h.id FROM host h, host_ip hi WHERE h.id=hi.host_id AND hi.ip=UNHEX(@ip)), UNHEX(@uuid));
        END IF;
        
        -- Clean Orphans
        DELETE aux FROM sensor_stats aux LEFT JOIN sensor s ON s.id=aux.sensor_id WHERE s.id IS NULL;
        DELETE aux FROM sensor_properties aux LEFT JOIN sensor s ON s.id=aux.sensor_id WHERE s.id IS NULL;
        DELETE aux FROM acl_sensors aux LEFT JOIN sensor s ON s.id=aux.sensor_id WHERE s.id IS NULL;
        DELETE aux FROM vuln_nessus_servers aux LEFT JOIN sensor s ON s.id=UNHEX(aux.hostname) WHERE s.id IS NULL;
        
        SELECT CONCAT('Sensor successfully updated') as status, @uuid as sensor_id;
    
    ELSE
        
        SELECT CONCAT('Invalid IP or User values') as status, NULL as sensor_id;
            
    END IF;
    
END$$

DROP PROCEDURE IF EXISTS system_update$$
CREATE PROCEDURE system_update(
    _system_id VARCHAR(36),
    _name      VARCHAR(64),
    _admin_ip  VARCHAR(64),
    _vpn_ip    VARCHAR(64),
    _profile   VARCHAR(64),
    _ha_ip     VARCHAR(64),
    _ha_name   VARCHAR(64),
    _ha_role   VARCHAR(64),
    _sensor_id VARCHAR(64),
    _server_id VARCHAR(64)
)
UPDATE_SYSTEM:BEGIN

    SELECT REPLACE(_system_id,'-','') into @system_id;

    IF NOT EXISTS(SELECT 1 FROM alienvault.system WHERE id=UNHEX(@system_id)) THEN

        -- create new one if it's possible
        
        IF (_system_id != '' AND _name != '' AND _admin_ip != '' AND _profile != '') THEN

            SELECT IF (_sensor_id='',NULL,REPLACE(_sensor_id,'-','')) into @sensor_id;
            SELECT IF (_server_id='',NULL,REPLACE(_server_id,'-','')) into @server_id;
            REPLACE INTO `system` (id,name,admin_ip,vpn_ip,profile,ha_ip,ha_name,ha_role,sensor_id,server_id) VALUES (UNHEX(@system_id), _name, inet6_aton(_admin_ip), inet6_aton(_vpn_ip), _profile, inet6_aton(_ha_ip), _ha_name, _ha_role, UNHEX(@sensor_id), UNHEX(@server_id));
            
        ELSE
        
            SELECT CONCAT('It needs al least system uuid, name, admin_ip and profile to create a new system') as status;
            LEAVE UPDATE_SYSTEM;
        
        END IF;
        
        SELECT CONCAT('System ',_system_id,' created') as status;
        
    ELSE

        -- update each field
        
        IF (_sensor_id != '') THEN
            UPDATE alienvault.system SET sensor_id=UNHEX(REPLACE(_sensor_id,'-','')) WHERE id=UNHEX(@system_id);    
        END IF;

        IF (_server_id != '') THEN
            UPDATE alienvault.system SET server_id=UNHEX(REPLACE(_server_id,'-','')) WHERE id=UNHEX(@system_id);    
        END IF;

        IF (_name != '') THEN
            UPDATE alienvault.system SET name=_name WHERE id=UNHEX(@system_id);

            -- name populate in server/sensor
            SELECT HEX(sensor_id),HEX(server_id),name FROM alienvault.system WHERE id=UNHEX(@system_id) into @sensor_id, @server_id, @system_name;
            
            UPDATE server SET name=@system_name WHERE id=UNHEX(@server_id);

            UPDATE sensor SET name=@system_name WHERE id=UNHEX(@sensor_id);
                        
        END IF;

        IF (_profile != '') THEN
            UPDATE alienvault.system SET profile=_profile WHERE id=UNHEX(@system_id);    
        END IF;

        IF (_ha_ip != '' AND _ha_name != '' AND _ha_role != '') THEN
            UPDATE alienvault.system SET ha_ip=inet6_aton(_ha_ip), ha_name=_ha_name, ha_role=_ha_role WHERE id=UNHEX(@system_id);    
        END IF;
        
        IF (_admin_ip != '' OR _vpn_ip != '') THEN

            -- admin_ip or vpn_ip populate in server/sensor
            IF (_admin_ip != '') THEN
                UPDATE alienvault.system SET admin_ip=inet6_aton(_admin_ip) WHERE id=UNHEX(@system_id);    
            END IF;

            IF (_vpn_ip != '') THEN
                UPDATE alienvault.system SET vpn_ip=inet6_aton(_vpn_ip) WHERE id=UNHEX(@system_id);    
            END IF;

            -- Populate admin_ip if the system is not HA
            SELECT inet6_ntoa(ha_ip) FROM alienvault.system WHERE id=UNHEX(@system_id) into @ha_ip;

            IF @ha_ip IS NULL OR @ha_ip = '' THEN
                SELECT HEX(sensor_id),HEX(server_id),inet6_ntoa(admin_ip),inet6_ntoa(vpn_ip) FROM alienvault.system WHERE id=UNHEX(@system_id) into @sensor_id, @server_id, @admin_ip, @vpn_ip;
                
                UPDATE server SET ip=inet6_aton(@admin_ip) WHERE id=UNHEX(@server_id);
                
                UPDATE sensor SET ip=IFNULL(inet6_aton(@vpn_ip),inet6_aton(@admin_ip)) WHERE id=UNHEX(@sensor_id);
            END IF;

        END IF;
        
        SELECT CONCAT('System ',_system_id,' updated') as status;
        
    END IF;
            
END$$

DROP PROCEDURE IF EXISTS compliance_aggregate$$
CREATE PROCEDURE compliance_aggregate()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE _ref VARCHAR(512);
    DECLARE _secc VARCHAR(512);
    DECLARE _sids VARCHAR(512);
    DECLARE _a VARCHAR(512);
    DECLARE _b VARCHAR(512);
    DECLARE _c VARCHAR(512);
    DECLARE _d VARCHAR(512);
    DECLARE _e VARCHAR(512);
    DECLARE _i INT;
    DECLARE _j INT;
    DECLARE _k INT;
    DECLARE _l INT;
    DECLARE _m INT;
    DECLARE _n INT;
    DECLARE _o INT;
    DECLARE _p INT;
    DECLARE _q INT;
    DECLARE x INT DEFAULT 0;
    DECLARE y INT DEFAULT 0;
    
    -- ISO27001
    DECLARE cur1 CURSOR FOR SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A05_Security_Policy WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A06_IS_Organization WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A07_Asset_Mgnt WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A08_Human_Resources WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A09_Physical_security WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A10_Com_OP_Mgnt WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A11_Acces_control WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A12_IS_acquisition WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A13_IS_incident_mgnt WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A14_BCM WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A15_Compliance WHERE SIDSS_Ref >= 1;
    DECLARE cur2 CURSOR FOR SELECT DISTINCT(i.destination) AS dest_ip, net.name AS service FROM alienvault.net, alienvault.net_cidrs, datawarehouse.incidents_ssi i WHERE net.id=net_cidrs.net_id AND inet6_aton(i.destination) >=  net_cidrs.begin AND inet6_aton(i.destination) <=  net_cidrs.end AND i.destination <> '' AND i.destination <> '0.0.0.0' GROUP BY 1;
    DECLARE cur3 CURSOR FOR SELECT incident.type_id, incident.title, incident.priority, incident_alarm.src_ips, incident_alarm.dst_ips, ifnull(incident_ticket.description,''), YEAR(incident.event_start), MONTH(incident.event_start), DAY(incident.event_start), HOUR(incident.event_start), MINUTE(incident.event_start), count(distinct(incident.id)) FROM incident LEFT JOIN incident_ticket ON incident_ticket.incident_id=incident.id,incident_alarm, incident_type WHERE incident_alarm.incident_id=incident.id and incident_type.id=incident.type_id GROUP BY 1,2,3,4,5,7,8,9,10,11;
    DECLARE cur4 CURSOR FOR SELECT a.plugin_sid, s.name, a.risk, inet6_ntoa(a.src_ip), inet6_ntoa(a.dst_ip), "no_detail", YEAR(a.timestamp), MONTH(a.timestamp), HOUR(a.timestamp), DAY(a.timestamp), MINUTE(a.timestamp), count(*) as volume FROM alienvault.alarm a, alienvault.plugin_sid s WHERE a.plugin_id=s.plugin_id AND a.plugin_sid=s.sid AND a.status="open" AND s.plugin_id=1505 GROUP BY 1,2,3,4,5,6,7,8,9,10,11;
    DECLARE cur5 CURSOR FOR SELECT inet6_ntoa(a.src_ip) AS ip, net.name AS service FROM alienvault.net, alienvault.alarm a, alienvault.net_cidrs n WHERE n.net_id=net.id AND a.src_ip >= n.begin AND a.src_ip <= n.end GROUP BY 1 UNION SELECT inet6_ntoa(a.dst_ip) AS ip, net.name AS service FROM alienvault.net, alienvault.alarm a, alienvault.net_cidrs n WHERE n.net_id=net.id AND a.dst_ip >= n.begin AND a.dst_ip <= n.end GROUP BY 1;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    DELETE FROM datawarehouse.iso27001sid;
    SET AUTOCOMMIT=0;

    OPEN cur1;

    REPEAT
        FETCH cur1 INTO _ref,_secc,_sids;
        IF NOT done THEN
            SET @ref = _ref;
            SET @secc = _secc;
            SELECT LENGTH(_sids) - LENGTH(REPLACE(_sids, ',', '')) INTO @nCommas;
            SET y = 1;
            SET x = @nCommas + 1;
            WHILE y <= x DO
                SELECT _split_string(_sids, ',', y) INTO @range;
                SET @query = 'INSERT INTO datawarehouse.iso27001sid VALUES (?, ?, ?)';
                PREPARE sql_query FROM @query;
                EXECUTE sql_query USING @ref, @secc, @range;
                DEALLOCATE PREPARE sql_query;
                SET  y = y + 1;
            END WHILE;
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur1;

    COMMIT;

    -- IP2SERVICE DST_IP
    SET done = 0;
    
    OPEN cur2;

    REPEAT
        FETCH cur2 INTO _a,_b;
        IF NOT done THEN
            SET @query = 'REPLACE INTO datawarehouse.ip2service (`dest_ip`, `service`) VALUES (?, ?)';
            SET @a = _a;
            SET @b = _b;
            PREPARE sql_query FROM @query;
            EXECUTE sql_query USING @a, @b;
            DEALLOCATE PREPARE sql_query;
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur2;

    COMMIT;
    
    -- INCIDENTS_SSI
    SET done = 0;
    
    OPEN cur3;

    REPEAT
        FETCH cur3 INTO _a,_b,_j,_c,_d,_e,_k,_l,_m,_n,_o,_p;
        IF NOT done THEN
            SET @query = 'REPLACE INTO datawarehouse.incidents_ssi (`type`, `descr`, `priority`, `source`, `destination`, `details`, `year`, `month`, `day`, `hour`, `minute`, `volume`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            SET @a = _a;
            SET @b = _b;
            SET @c = _c;
            SET @d = _d;
            SET @e = _e;
            SET @i = _i;
            SET @j = _j;
            SET @k = _k;
            SET @l = _l;
            SET @m = _m;
            SET @n = _n;
            SET @o = _o;
            SET @p = _p;
            PREPARE sql_query FROM @query;
            EXECUTE sql_query USING @a, @b, @j, @c, @d, @e, @k, @l, @m, @n, @o, @p;
            DEALLOCATE PREPARE sql_query;
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur3;

    COMMIT;
    
    -- SSI
    SET done = 0;
    
    OPEN cur4;

    REPEAT
        FETCH cur4 INTO _j,_a,_k,_b,_c,_d,_l,_m,_n,_p,_p,_q;
        IF NOT done THEN
            SET @query = 'REPLACE INTO datawarehouse.ssi (`sid`, `descr`, `priority`, `source`, `destination`, `details`, `year`, `month`, `hour`, `day`, `minute`, `volume`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            SET @a = _a;
            SET @b = _b;
            SET @c = _c;
            SET @d = _d;
            SET @i = _i;
            SET @j = _j;
            SET @k = _k;
            SET @l = _l;
            SET @m = _m;
            SET @n = _n;
            SET @o = _o;
            SET @p = _p;
            SET @q = _q;
            PREPARE sql_query FROM @query;
            EXECUTE sql_query USING @j, @a, @k, @b, @c, @d, @l, @m, @n, @p, @p, @q;
            DEALLOCATE PREPARE sql_query;
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur4;    

    COMMIT;
    
    -- IP2SERVICE SRC_IP
    SET done = 0;
    
    OPEN cur5;

    REPEAT
        FETCH cur5 INTO _a,_b;
        IF NOT done THEN
            SET @query = 'REPLACE INTO datawarehouse.ip2service (`dest_ip`, `service`) VALUES (?, ?)';
            SET @a = _a;
            SET @b = _b;
            PREPARE sql_query FROM @query;
            EXECUTE sql_query USING @a, @b;
            DEALLOCATE PREPARE sql_query;
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur5;

    COMMIT;
    
END$$

DROP TRIGGER IF EXISTS auto_incidents$$
CREATE TRIGGER `auto_incidents` AFTER INSERT ON `alarm` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN

  IF EXISTS

   (SELECT value FROM config where conf = "alarms_generate_incidents" and value = "yes")

  THEN

    IF NOT EXISTS (SELECT id FROM incident_alarm WHERE backlog_id = NEW.backlog_id)

    THEN
        SET @tmp_src_ip = NEW.src_ip;
        SET @tmp_dst_ip = NEW.dst_ip;
        SET @tmp_risk = NEW.risk;
        SET @title = 'Unknown Directive';
        IF EXISTS (SELECT 1 from plugin_sid where plugin_ctx=NEW.corr_engine_ctx AND plugin_id = NEW.plugin_id and sid = NEW.plugin_sid)
        THEN
            SET @title = (SELECT ifnull(TRIM(LEADING "directive_event:" FROM name),'Unknown Directive') as name from plugin_sid where plugin_ctx=NEW.corr_engine_ctx AND plugin_id = NEW.plugin_id and sid = NEW.plugin_sid LIMIT 1);
        END IF;
        
        SET @title = REPLACE(@title,"DST_IP", inet6_ntoa(NEW.dst_ip));
        SET @title = REPLACE(@title,"SRC_IP", inet6_ntoa(NEW.src_ip));
        SET @title = REPLACE(@title,"PROTOCOL", NEW.protocol);
        SET @title = REPLACE(@title,"SRC_PORT", NEW.src_port);
        SET @title = REPLACE(@title,"DST_PORT", NEW.dst_port);
        SET @title = CONCAT(@title, " (", inet6_ntoa(NEW.src_ip), ":", CAST(NEW.src_port AS CHAR), " -> ", inet6_ntoa(NEW.dst_ip), ":", CAST(NEW.dst_port AS CHAR), ")");
        
        SELECT value FROM config WHERE conf = 'incidents_incharge_default' into @incharge;
        IF (@incharge IS NULL OR @incharge = '') THEN
            SET @incharge = 'admin';
        END IF;
        
        INSERT INTO incident(uuid,ctx,title,date,ref,type_id,priority,status,last_update,in_charge,submitter,event_start,event_end) values (UNHEX(REPLACE(UUID(),'-','')), NEW.corr_engine_ctx, @title, NEW.timestamp, "Alarm", "Generic", NEW.risk, "Open", NOW(), @incharge, "admin", NEW.timestamp, NEW.timestamp);

        SET @last_incident_id = (SELECT LAST_INSERT_ID() FROM incident LIMIT 1);
        INSERT INTO incident_alarm(incident_id, src_ips, dst_ips, src_ports, dst_ports, backlog_id, event_id, alarm_group_id) values (@last_incident_id, inet6_ntoa(NEW.src_ip), inet6_ntoa(NEW.dst_ip), NEW.src_port, NEW.dst_port, NEW.backlog_id, NEW.event_id, 0);

        CALL incident_ticket_populate(@last_incident_id, @tmp_src_ip, @tmp_dst_ip, @tmp_risk);
    END IF;
  END IF;

END$$

DROP PROCEDURE IF EXISTS _acl_fill_subnets$$
CREATE PROCEDURE _acl_fill_subnets( user VARCHAR(64) )
BEGIN
    IF EXISTS (SELECT 1 FROM net LEFT JOIN user_net_perm ON user_net_perm.asset_id=net.id AND user_net_perm.login=user, user_ctx_perm, net_sensor_reference, sensor WHERE net.id=net_sensor_reference.net_id AND net_sensor_reference.sensor_id=sensor.id AND user_ctx_perm.ctx=net.ctx AND user_ctx_perm.login=user AND user_net_perm.asset_id IS NULL) THEN
        CREATE TEMPORARY TABLE IF NOT EXISTS tmpnet (PRIMARY KEY(asset_id)) AS SELECT asset_id from user_net_perm where login = user;
        CREATE TEMPORARY TABLE IF NOT EXISTS tmpnet1 (PRIMARY KEY(begin,end)) AS SELECT begin,end,net_id from net_cidrs LIMIT 0;
        INSERT IGNORE INTO tmpnet1 SELECT begin,end,net_id from net_cidrs;
    
        REPLACE INTO user_net_perm SELECT DISTINCT user, n1.net_id FROM tmpnet1 n1, tmpnet t INNER JOIN net_cidrs n ON t.asset_id = n.net_id WHERE n.net_id!=n1.net_id AND  n1.begin >= n.begin AND n1.end <= n.end;
    
        DROP TEMPORARY TABLE IF EXISTS tmpnet;
        DROP TEMPORARY TABLE IF EXISTS tmpnet1;
    END IF;
END$$

DROP PROCEDURE IF EXISTS _acl_mr_proper$$
CREATE PROCEDURE _acl_mr_proper( user VARCHAR(64) )
BEGIN
    SELECT EXISTS (SELECT 1 from user_host_perm where login = user) INTO @hosts;
    SELECT EXISTS (SELECT 1 from user_net_perm where login = user) INTO @networks;
    SELECT EXISTS (SELECT 1 from user_sensor_perm where login = user) INTO @sensors;

    -- Check Sensors
    IF NOT @sensors AND @hosts THEN
        REPLACE INTO user_sensor_perm SELECT user, sensor.id FROM sensor, host, host_sensor_reference, user_host_perm WHERE sensor.id = host_sensor_reference.sensor_id AND host_sensor_reference.host_id=host.id AND host.id=user_host_perm.asset_id AND user_host_perm.login = user;
    END IF;
    IF NOT @sensors AND @networks THEN
        REPLACE INTO user_sensor_perm SELECT user, sensor.id FROM sensor, net, net_sensor_reference, user_net_perm WHERE sensor.id = net_sensor_reference.sensor_id AND net_sensor_reference.net_id=net.id AND net.id=user_net_perm.asset_id AND user_net_perm.login = user;    
    END IF;

    -- Check 0xFF    
    IF NOT @hosts THEN
        REPLACE INTO user_host_perm (login, asset_id) VALUES (user, UNHEX('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'));
    END IF;
    IF NOT @networks THEN
        REPLACE INTO user_net_perm (login, asset_id) VALUES (user, UNHEX('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'));
    END IF;
    
    -- If all then none
    IF ( is_pro() ) THEN
        IF NOT EXISTS (SELECT 1 FROM host LEFT JOIN user_host_perm ON user_host_perm.asset_id=host.id AND user_host_perm.login=user, user_ctx_perm, host_sensor_reference, sensor WHERE host.id=host_sensor_reference.host_id AND host_sensor_reference.sensor_id=sensor.id AND user_ctx_perm.ctx=host.ctx AND user_ctx_perm.login=user AND user_host_perm.asset_id IS NULL) THEN
            DELETE FROM user_host_perm WHERE login = user;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM net LEFT JOIN user_net_perm ON user_net_perm.asset_id=net.id AND user_net_perm.login=user, user_ctx_perm, net_sensor_reference, sensor WHERE net.id=net_sensor_reference.net_id AND net_sensor_reference.sensor_id=sensor.id AND user_ctx_perm.ctx=net.ctx AND user_ctx_perm.login=user AND user_net_perm.asset_id IS NULL) THEN
            DELETE FROM user_net_perm WHERE login = user;
        END IF;
    ELSE
        IF NOT EXISTS (SELECT 1 FROM host LEFT JOIN user_host_perm ON user_host_perm.asset_id=host.id AND user_host_perm.login=user, host_sensor_reference, sensor WHERE host.id=host_sensor_reference.host_id AND host_sensor_reference.sensor_id=sensor.id AND user_host_perm.asset_id IS NULL) THEN
            DELETE FROM user_host_perm WHERE login = user;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM net LEFT JOIN user_net_perm ON user_net_perm.asset_id=net.id AND user_net_perm.login=user, net_sensor_reference, sensor WHERE net.id=net_sensor_reference.net_id AND net_sensor_reference.sensor_id=sensor.id AND user_net_perm.asset_id IS NULL) THEN
            DELETE FROM user_net_perm WHERE login = user;
        END IF;
    END IF;

END$$

CALL alienvault.kill_queries()$$

DROP TRIGGER IF EXISTS `host_properties_DELETE`$$
CREATE TRIGGER `host_properties_DELETE` AFTER DELETE ON `host_properties` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_properties_INSERT`$$
CREATE TRIGGER `host_properties_INSERT` AFTER INSERT ON `host_properties` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_properties_UPDATE`$$
CREATE TRIGGER `host_properties_UPDATE` AFTER UPDATE ON `host_properties` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_scan_DELETE`$$
CREATE TRIGGER `host_scan_DELETE` AFTER DELETE ON `host_scan` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_scan_INSERT`$$
CREATE TRIGGER `host_scan_INSERT` AFTER INSERT ON `host_scan` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_scan_UPDATE`$$
CREATE TRIGGER `host_scan_UPDATE` AFTER UPDATE ON `host_scan` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_ip_DELETE`$$
CREATE TRIGGER `host_ip_DELETE` AFTER DELETE ON `host_ip` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_ip_INSERT`$$
CREATE TRIGGER `host_ip_INSERT` AFTER INSERT ON `host_ip` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_ip_UPDATE`$$
CREATE TRIGGER `host_ip_UPDATE` AFTER UPDATE ON `host_ip` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_services_DELETE`$$
CREATE TRIGGER `host_services_DELETE` AFTER DELETE ON `host_services` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_services_INSERT`$$
CREATE TRIGGER `host_services_INSERT` AFTER INSERT ON `host_services` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_services_UPDATE`$$
CREATE TRIGGER `host_services_UPDATE` AFTER UPDATE ON `host_services` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_types_DELETE`$$
CREATE TRIGGER `host_types_DELETE` AFTER DELETE ON `host_types` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_types_INSERT`$$
CREATE TRIGGER `host_types_INSERT` AFTER INSERT ON `host_types` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_types_UPDATE`$$
CREATE TRIGGER `host_types_UPDATE` AFTER UPDATE ON `host_types` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_software_INSERT`$$
CREATE TRIGGER `host_software_INSERT` AFTER INSERT ON `host_software` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_software_UPDATE`$$
CREATE TRIGGER `host_software_UPDATE` AFTER UPDATE ON `host_software` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_software_DELETE`$$
CREATE TRIGGER `host_software_DELETE` AFTER DELETE ON `host_software` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_vulnerability_DELETE`$$
CREATE TRIGGER `host_vulnerability_DELETE` AFTER DELETE ON `host_vulnerability` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_vulnerability_INSERT`$$
CREATE TRIGGER `host_vulnerability_INSERT` AFTER INSERT ON `host_vulnerability` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP TRIGGER IF EXISTS `host_vulnerability_UPDATE`$$
CREATE TRIGGER `host_vulnerability_UPDATE` AFTER UPDATE ON `host_vulnerability` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN
    IF @disable_host_update IS NULL THEN
        UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
    END IF;
END$$

DROP PROCEDURE IF EXISTS addcol$$
CREATE PROCEDURE addcol() BEGIN

    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'sensor' AND INDEX_NAME = 'ip_UNIQUE')
    THEN
        ALTER TABLE `alienvault`.`sensor` DROP KEY `ip_UNIQUE`;
    END IF;
    
    ALTER TABLE `host_properties` CHANGE `value` `value` TEXT NOT NULL;
    ALTER TABLE `host_properties` DROP PRIMARY KEY, ADD PRIMARY KEY (`host_id`, `property_ref`, `value`(255));

    -- Unified tags
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'alarm_tags' AND COLUMN_NAME = 'id_alarm')
    THEN
    
        ALTER TABLE `alarm_tags` CHANGE `id_alarm` `id_component` BINARY(16) NOT NULL, CHANGE `id_tag` `id_tag` BINARY(16) NOT NULL;
        DROP TABLE IF EXISTS `component_tags`;
        RENAME TABLE `alarm_tags` TO `component_tags`;
    
    END IF;

    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'tags_alarm' AND COLUMN_NAME = 'bgcolor')
    THEN
    
        ALTER TABLE `tags_alarm` CHANGE `id` `id` BINARY(16) NOT NULL, CHANGE `bgcolor` `type` VARCHAR(32) NOT NULL, CHANGE `fgcolor` `class` VARCHAR(32) NOT NULL, DROP COLUMN `italic`, DROP COLUMN `bold`;
        ALTER TABLE `tags_alarm` ADD INDEX `type` (`type` ASC);
        DROP TABLE IF EXISTS `tag`;
        RENAME TABLE `tags_alarm` TO `tag`;
        UPDATE `tag` SET class='av_tag_1'  WHERE type='DEE5F2' AND class='5A6986';
        UPDATE `tag` SET class='av_tag_2'  WHERE type='E0ECFF' AND class='206CFF';
        UPDATE `tag` SET class='av_tag_3'  WHERE type='DFE2FF' AND class='0000CC';
        UPDATE `tag` SET class='av_tag_4'  WHERE type='E0D5F9' AND class='5229A3';
        UPDATE `tag` SET class='av_tag_5'  WHERE type='FDE9F4' AND class='854F61';
        UPDATE `tag` SET class='av_tag_6'  WHERE type='FFE3E3' AND class='CC0000';
        UPDATE `tag` SET class='av_tag_7'  WHERE type='5A6986' AND class='DEE5F2';
        UPDATE `tag` SET class='av_tag_8'  WHERE type='206CFF' AND class='E0ECFF';
        UPDATE `tag` SET class='av_tag_9'  WHERE type='0000CC' AND class='DFE2FF';
        UPDATE `tag` SET class='av_tag_10' WHERE type='5229A3' AND class='E0D5F9';
        UPDATE `tag` SET class='av_tag_11' WHERE type='854F61' AND class='FDE9F4';
        UPDATE `tag` SET class='av_tag_12' WHERE type='CC0000' AND class='FFE3E3';
        UPDATE `tag` SET class='av_tag_13' WHERE type='FFF0E1' AND class='EC7000';
        UPDATE `tag` SET class='av_tag_14' WHERE type='FADCB3' AND class='B36D00';
        UPDATE `tag` SET class='av_tag_15' WHERE type='F3E7B3' AND class='AB8B00';
        UPDATE `tag` SET class='av_tag_16' WHERE type='FFFFD4' AND class='636330';
        UPDATE `tag` SET class='av_tag_17' WHERE type='F9FFEF' AND class='64992C';
        UPDATE `tag` SET class='av_tag_18' WHERE type='F1F5EC' AND class='006633';
        UPDATE `tag` SET class='av_tag_19' WHERE type='EC7000' AND class='F8F4F0';
        UPDATE `tag` SET class='av_tag_20' WHERE type='B36D00' AND class='FADCB3';
        UPDATE `tag` SET class='av_tag_21' WHERE type='AB8B00' AND class='F3E7B3';
        UPDATE `tag` SET class='av_tag_22' WHERE type='636330' AND class='FFFFD4';
        UPDATE `tag` SET class='av_tag_23' WHERE type='64992C' AND class='F9FFEF';
        UPDATE `tag` SET class='av_tag_24' WHERE type='006633' AND class='F1F5EC';
        UPDATE `tag` SET type='alarm';
    
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'host_scan' AND COLUMN_NAME = 'status')
    THEN
        ALTER TABLE `host_scan` ADD COLUMN `status` INT NOT NULL DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'host_group_scan' AND COLUMN_NAME = 'status')
    THEN
        ALTER TABLE `host_group_scan` ADD COLUMN `status` INT NOT NULL DEFAULT 0;
    END IF;
    
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'plugin_sid' AND INDEX_NAME = 'search')
    THEN
        ALTER TABLE `alienvault`.`plugin_sid` DROP KEY `search`, ADD INDEX `search` (`plugin_id` ASC, `name`(255) ASC);
    END IF;
    
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'repository' AND INDEX_NAME = 'TEXT')
    THEN
        ALTER TABLE `alienvault`.`repository` DROP KEY `text`;
    END IF;
    
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'host_net_reference' AND INDEX_NAME = 'net')
    THEN
        ALTER TABLE `alienvault`.`host_net_reference` ADD INDEX `net` (`net_id`);
    END IF;
    
END$$

DELIMITER ;

CALL addcol();
DROP PROCEDURE addcol;
DROP PROCEDURE IF EXISTS alienvault.kill_queries;

CREATE TABLE IF NOT EXISTS `vuln_job_assets` (
  `job_id` INT(11) NOT NULL,
  `job_type` INT(11) NOT NULL DEFAULT 0,
  `asset_id` BINARY(16) NOT NULL,
  PRIMARY KEY (`job_id`, `job_type`, `asset_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `user_component_filter` (
  `login` VARCHAR(64) NOT NULL,
  `asset_id` BINARY(16) NOT NULL,
  `asset_type` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`login`, `asset_id`, `asset_type`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

REPLACE INTO asset_filter_types (`id`, `filter`, `type`) VALUES
(1, 'asset_created', 'range'),
(2, 'asset_updated', 'range'),
(3, 'alarms', 'value'),
(4, 'events', 'value'),
(5, 'vulnerabilities', 'range'),
(6, 'asset_value', 'range'),
(7, 'network', 'list'),
(8, 'device_type', 'range_list'),
(9, 'software', 'list'),
(10, 'port_service', 'range_list'),
(11, 'ip', 'range_list'),
(12, 'fqdn', 'list'),
(13, 'location', 'list'),
(14, 'sensor', 'list'),
(15, 'user', 'list'),
(16, 'hostname', 'list'),
(17, 'availability', 'value'),
(18, 'groups', 'list'),
(19, 'labels', 'list'),
(20, 'os', 'list'),
(21, 'model', 'list'),
(22, 'group_name', 'list'),
(23, 'network_name', 'list'),
(24, 'network_cidr', 'list');

DELETE FROM `dashboard_widget_config` WHERE `panel_id`=1 AND `type_id`=1006;
DELETE FROM `dashboard_widget_config` WHERE `panel_id`=1 AND `type_id`=1008;
DELETE FROM `dashboard_widget_config` WHERE `panel_id`=3 AND `type_id`=3001;
DELETE FROM `dashboard_widget_config` WHERE `panel_id`=3 AND `type_id`=3002;

DELETE FROM `dashboard_custom_type` WHERE `id`=1006;

DELETE FROM `dashboard_tab_options` WHERE `id`=12;
DELETE FROM `dashboard_tab_config` WHERE `id`=12;

REPLACE INTO `dashboard_widget_config` (`panel_id`, `type_id`, `user`, `col`, `fil`, `height`, `title`, `help`, `refresh`, `color`, `file`, `type`, `asset`, `media`, `params`) VALUES
(1, 1008, '0', 2, 0, 240, 'Highest Risk Alarm', 'Highest Risk Alarm', 0, 'db_color_13', 'widgets/data/gauge.php?type=alarm', 'gauge', 'ALL_ASSETS', NULL, 'a:1:{s:4:"type";s:3:"max";}');

REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(10, 'MENU', 'environment-menu', 'PolicyHosts', 'Environment -> Assets & Groups -> Assets / Asset Groups', 1, 0, 1, '03.01'),
(11, 'MENU', 'environment-menu', 'PolicyNetworks', 'Environment -> Assets & Groups -> Networks & Network Groups', 0, 1, 1, '03.02'),
(42, 'MENU', 'environment-menu', 'ToolsScan', 'Environment -> Assets & Groups -> Discover New Assets', 0, 1, 1, '03.04'),
(85, 'MENU', 'environment-menu', 'AlienVaultInventory', 'Environment -> Assets & Groups -> Schedule Scan', 0, 0, 1, '03.03'),
(86, 'MENU', 'configuration-menu', 'AlienVaultInventory', 'Configuration -> Deployment -> Scheduler', 0, 0, 1, '05.04');
DELETE FROM `acl_perm` WHERE id=13;
DELETE FROM `acl_templates_perms` WHERE ac_perm_id=13;
INSERT IGNORE INTO `acl_templates_perms` SELECT ac_templates_id, 86 FROM acl_templates_perms WHERE ac_perm_id=85;

REPLACE INTO `host_property_reference` (id,name,ord,description) VALUES (14,'model',14, 'Model');

INSERT IGNORE INTO config (conf, value) VALUES ('track_usage_information', '');

UPDATE incident SET title=REPLACE(title,'nessus: ','Vulnerability - ');

DELETE FROM custom_report_types WHERE id IN ('280', '281', '282', '283', '284');
DELETE FROM user_config WHERE category='custom_report' AND name = 'Metrics';

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2015-04-21');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.0.0');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
