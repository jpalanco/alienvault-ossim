SET AUTOCOMMIT=0;
USE alienvault;


ALTER TABLE `sensor_properties` ADD COLUMN IF NOT EXISTS `has_ossec` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `sensor_properties` MODIFY COLUMN `has_vuln_scanner` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `vuln_job_schedule` ADD COLUMN IF NOT EXISTS `ssh_credential_port` INT(11) NOT NULL DEFAULT '22';
ALTER TABLE `vuln_jobs` ADD COLUMN IF NOT EXISTS `ssh_credential_port` INT(11) NOT NULL DEFAULT '22';




-- Changing the -A option for asset discovery in old scanners
UPDATE task_inventory set task_params = REPLACE(task_params, '-A', '-O --osscan-guess --max-os-tries=1') where task_params like '% -A %' and task_type=5;


--
DELETE FROM config WHERE conf = 'user_life_time';
DELETE FROM config WHERE conf = 'use_svg_graphics';
DELETE FROM config WHERE conf = 'recovery';
DELETE FROM config WHERE conf = 'use_resolv';
DELETE FROM config WHERE conf = 'have_scanmap3d';
DELETE FROM config WHERE conf = 'threshold';
DELETE FROM config WHERE conf = 'osvdb_type';
DELETE FROM config WHERE conf = 'osvdb_base';
DELETE FROM config WHERE conf = 'osvdb_user';
DELETE FROM config WHERE conf = 'osvdb_pass';
DELETE FROM config WHERE conf = 'osvdb_host';
DELETE FROM config WHERE conf = 'phpgacl_path';
DELETE FROM config WHERE conf = 'phpgacl_type';
DELETE FROM config WHERE conf = 'phpgacl_host';
DELETE FROM config WHERE conf = 'phpgacl_base';
DELETE FROM config WHERE conf = 'phpgacl_user';
DELETE FROM config WHERE conf = 'phpgacl_pass';
DELETE FROM config WHERE conf = 'md5_salt';
DELETE FROM config WHERE conf = 'ovcp_link';
DELETE FROM config WHERE conf = 'glpi_link';

DELETE FROM config WHERE conf = 'p0f_path';
DELETE FROM config WHERE conf = 'arpwatch_path';
DELETE FROM config WHERE conf = 'mail_path';
DELETE FROM config WHERE conf = 'touch_path';
DELETE FROM config WHERE conf = 'wget_path';
DELETE FROM config WHERE conf = 'fpdf_path';

DELETE FROM config WHERE conf = 'dc_acc';
DELETE FROM config WHERE conf = 'dc_ip';
DELETE FROM config WHERE conf = 'dc_pass';

DELETE FROM config WHERE conf = 'mrtg_path';
DELETE FROM config WHERE conf = 'mrtg_rrd_files_path';
DELETE FROM config WHERE conf = 'rrdtool_lib_path';
REPLACE INTO config (conf, value) VALUES ('close_vuln_tickets_automatically', '1');
REPLACE INTO config (conf, value) VALUES ('hids_update_rate', '60');

UPDATE device_types SET name = 'Monitoring Tools' WHERE id = '118';

ALTER TABLE `incident` CHANGE COLUMN IF EXISTS `priority` `priority` INT(2) UNSIGNED NOT NULL DEFAULT '1';

ALTER TABLE `incident_tmp_email` ADD COLUMN IF NOT EXISTS `subscribers` TEXT NULL DEFAULT '';
ALTER TABLE `incident_tmp_email` DROP PRIMARY KEY;
ALTER TABLE `incident_tmp_email` ADD PRIMARY KEY (`incident_id`,`ticket_id`);

-- -----------------------------------------------------
-- procedure incident_ticket_populate
-- -----------------------------------------------------

DROP PROCEDURE IF EXISTS incident_ticket_populate;
DELIMITER $$
CREATE DEFINER=`root`@`127.0.0.1` PROCEDURE `incident_ticket_populate`(p_incident_id INT, src_ip VARBINARY(16), dst_ip VARBINARY(16), prio INT) BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE count INT;
    DECLARE cnt_src, cnt_dst INT;
    DECLARE name, subname VARCHAR(255);
    DECLARE first_occ, last_occ TIMESTAMP;
    DECLARE source VARCHAR(39);
    DECLARE dest VARCHAR(39);

    DECLARE cur1 CURSOR FOR select count(*) as cnt, inet6_ntoa(event.src_ip) as src, inet6_ntoa(event.dst_ip) as dst, plugin.name, plugin_sid.name, min(timestamp) as frst, max(timestamp) as last, count(distinct(event.src_ip)) as
                                               cnt_src, count(distinct(event.dst_ip)) as cnt_dst from event, plugin, plugin_sid where (event.src_ip = src_ip or event.dst_ip = src_ip or event.src_ip = dst_ip or event.dst_ip =dst_ip ) and timestamp > DATE_SUB(NOW(), INTERVAL 7
                                                                                                                                                                                                                                                                  DAY) AND plugin.id = event.plugin_id and plugin_sid.sid = event.plugin_sid and plugin_sid.plugin_id = event.plugin_id group by event.plugin_id, event.plugin_sid ORDER by cnt DESC limit 50;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    SET @alarm_id = NULL;
    SELECT hex(backlog_id) INTO @alarm_id FROM incident_alarm where `incident_id` = p_incident_id;

    SET @frameworkd_address = NULL;
    SELECT `value` INTO @frameworkd_address FROM config where `conf` = 'frameworkd_address';

    IF (@alarm_id IS NOT NULL) THEN
        INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES
        (NULL, p_incident_id, NOW()-1, "Open", prio, "admin", CONCAT("<a target=\"_blank\" href=\"https://",@frameworkd_address,"/ossim/#analysis/alarms/alarms-",@alarm_id,"\">Link to Alarm</a>"));
    END IF;
    INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES
    (NULL, p_incident_id, NOW()-1, "Open", prio, "admin", "The following tickets contain information about the top 50 event types the hosts have been generating during the last 7 days.");
    SET @ticket_id = LAST_INSERT_ID();

    OPEN cur1;
    REPEAT
        FETCH cur1 INTO count, source, dest, name, subname, first_occ, last_occ, cnt_src, cnt_dst;
        IF NOT done THEN
            SET @desc = CONCAT( "Event Type: ", name, "\nEvent Description: ", subname, "\nOcurrences: ",CAST(count AS CHAR), "\nFirst Occurrence: ", CAST(first_occ AS CHAR(50)), "\nLast Ocurrence: ", CAST(last_occ AS
                CHAR(50)),"\nNumber of different sources: ", CAST(cnt_src AS CHAR), "\nNumber of different destinations: ", CAST(cnt_dst AS CHAR), "\nSource: ", source, "\nDest: ", dest);
            INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (NULL, p_incident_id, NOW(), "Open", prio, "admin", @desc);
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur1;

    IF EXISTS (SELECT value FROM config where conf = "tickets_send_mail" and value = "yes") THEN
        SET @subscribers = NULL;
        SELECT `in_charge` INTO @subscribers FROM incident where `id` = p_incident_id;
        REPLACE INTO incident_tmp_email VALUES (p_incident_id, @ticket_id, "CREATE_INCIDENT", @subscribers);
    END IF;
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
    ctxs TEXT
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
        SELECT EXISTS (SELECT 1 from user_ctx_perm where login = user) INTO @get_ctx_where;
        SELECT `value` FROM config WHERE conf='encryption_key' into @system_uuid;
        SELECT `value` FROM config WHERE conf='gvm_host' into @gvm_host;

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
        ELSE
            SET @query = 'INSERT INTO sensor_properties (`sensor_id`) values (UNHEX(?))';
            PREPARE stmt1 FROM @query;
            EXECUTE stmt1 USING @uuid;
            DEALLOCATE PREPARE stmt1;
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

        -- Added to vuln_nesus_servers by default
        IF NOT EXISTS(SELECT 1 FROM vuln_nessus_servers WHERE hostname=@uuid) THEN
            SET @query = 'REPLACE INTO vuln_nessus_servers (name , description, hostname, max_scans, current_scans) VALUES (?, "RemoteHost", ?, 3, 0)';
            PREPARE stmt1 FROM @query;
            EXECUTE stmt1 USING @name, @uuid;
            DEALLOCATE PREPARE stmt1;
        END IF;

        -- Unique sensor
        IF NOT EXISTS(SELECT 1 FROM sensor WHERE id != UNHEX(@uuid) AND name != '(null)') THEN
            CALL _orphans_of_sensor(@uuid);
        END IF;

        -- Default nessus host
        IF @gvm_host = '' THEN
            REPLACE INTO config VALUES ('gvm_host', INET6_NTOA(UNHEX(@ip)));
        END IF;

        -- Check if a asset exists
        IF NOT EXISTS(SELECT 1 FROM host h, host_ip hi WHERE h.id=hi.host_id AND hi.ip=UNHEX(@ip)) THEN
            SELECT REPLACE(UUID(), '-', '') into @asset_id;
            INSERT IGNORE INTO host (id, ctx, hostname, asset, threshold_c, threshold_a, alert, persistence, nat, rrd_profile, descr, lat, lon, av_component) VALUES (UNHEX(@asset_id), UNHEX(@range), @name, '2', '30', '30', '0', '0', '', '', '', '0', '0', '1');
            INSERT IGNORE INTO host_ip (host_id,ip) VALUES (UNHEX(@asset_id), UNHEX(@ip));
            INSERT IGNORE INTO host_sensor_reference (host_id,sensor_id) VALUES (UNHEX(@asset_id), UNHEX(@uuid));
        ELSE
            INSERT IGNORE INTO host_sensor_reference (host_id,sensor_id) VALUES ((SELECT h.id FROM host h, host_ip hi WHERE h.id=hi.host_id AND hi.ip=UNHEX(@ip)), UNHEX(@uuid));
            UPDATE host h, host_ip hi SET hostname=@name WHERE h.id=hi.host_id AND hi.ip=UNHEX(@ip);
        END IF;

        -- Clean Orphans
        DELETE aux FROM sensor_stats aux LEFT JOIN sensor s ON s.id=aux.sensor_id WHERE s.id IS NULL;
        DELETE aux FROM sensor_properties aux LEFT JOIN sensor s ON s.id=aux.sensor_id WHERE s.id IS NULL;
        DELETE aux FROM acl_sensors aux LEFT JOIN sensor s ON s.id=aux.sensor_id WHERE s.id IS NULL;
        DELETE aux FROM vuln_nessus_servers aux LEFT JOIN sensor s ON s.id=UNHEX(aux.hostname) WHERE s.id IS NULL;

        CALL _host_default_os();

        SELECT CONCAT('Sensor successfully updated') as status, @uuid as sensor_id;

    ELSE

        SELECT CONCAT('Invalid IP or User values') as status, NULL as sensor_id;

    END IF;

END$$

DELIMITER ;


REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2021-11-16');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.8.9');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
