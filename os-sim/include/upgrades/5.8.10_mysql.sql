SET AUTOCOMMIT=0;
USE alienvault;


ALTER TABLE `custom_report_profiles` ADD COLUMN IF NOT EXISTS `header_title_position` ENUM('left','right','center') NOT NULL DEFAULT 'right';

DELETE FROM config where conf = 'track_usage_information';
DELETE FROM config where conf = 'from';
INSERT IGNORE INTO config (conf, value) VALUES ('default_sender_email_address', 'no-reply@alienvault.com');

DELETE FROM alienvault_api.monitor_data where monitor_id = '14';
DELETE FROM alienvault_api.status_message where HEX(id) = '00000000-0000-0000-0000-000000010029';

--  UPGRADING vuln_nessus_servers
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `status`;
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `enabled`;
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `TYPE`;
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `owner`;
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `PASSWORD`;
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `port`;
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `user`;
ALTER TABLE `vuln_nessus_servers` CHANGE COLUMN IF EXISTS `max_scans` `max_scans` INT(11) NOT NULL DEFAULT '3';

DELIMITER $$

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

        SET @query = 'REPLACE INTO sensor_properties (sensor_id) VALUES (UNHEX(?))';
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
REPLACE INTO config (conf, value) VALUES ('last_update', '2022-02-22');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.8.10');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
