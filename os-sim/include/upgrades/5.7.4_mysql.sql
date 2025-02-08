SET AUTOCOMMIT=0;
USE alienvault;

UPDATE dashboard_custom_type SET `name`='Top Hosts with Malware Detected', title_default = 'Top Hosts with Malware Detected', help_default='Top Hosts with the major number of malware events in the SIEM', file = 'widgets/data/taxonomy.php?type=malware_by_host' WHERE id = '4004';
UPDATE dashboard_custom_type SET `name`='Malware Events by Type', title_default = 'Malware Events by Type', help_default='Malware Events in the SIEM grouped by Type of events', file = 'widgets/data/taxonomy.php?type=malware' WHERE id = '4002';
UPDATE dashboard_widget_config SET title = 'Top 10 Hosts with Malware Detected', help='Top 10 Hosts with the major number of malware events in the SIEM', file='widgets/data/taxonomy.php?type=malware_by_host' WHERE type_id= '4004';
UPDATE dashboard_widget_config SET title = 'Malware Events by Type', help='Malware Events in the SIEM grouped by Type of events', file='widgets/data/taxonomy.php?type=malware' WHERE  type_id= '4002';

UPDATE log_config SET descr = 'Note (id:%1%) was created for ticket (id:%2%) by %3%'  WHERE code = 50;
UPDATE log_config SET descr = 'Note (id:%1%) was deleted from ticket (id:%2%) by %3%'  WHERE code = 51;


SELECT UNHEX(REPLACE(value, '-', '')) FROM config WHERE conf = 'default_context_id' into @default_ctx;
REPLACE INTO log_config (ctx, code, log, descr, priority) VALUES
(@default_ctx, 109, 1, 'Vulnerabilities - Scheduled Job: %1% modified', 1);

DROP TABLE IF EXISTS risk_indicators;
DROP TABLE IF EXISTS risk_maps;
DROP TABLE IF EXISTS maps;
DROP TABLE IF EXISTS map_element;
DROP TABLE IF EXISTS map_seq;
DROP TABLE IF EXISTS map_element_seq;

DROP TABLE IF EXISTS bp_asset_member;
DROP TABLE IF EXISTS bp_asset_status;

DROP TABLE IF EXISTS wireless_aps;
DROP TABLE IF EXISTS wireless_clients;
DROP TABLE IF EXISTS wireless_locations;
DROP TABLE IF EXISTS wireless_networks;
DROP TABLE IF EXISTS wireless_sensors;

DELETE FROM acl_perm WHERE id IN (46, 47, 62);
DELETE FROM acl_templates_perms WHERE ac_perm_id IN (46, 47, 62);

DELETE FROM config where conf = 'frameworkd_businessprocesses_period';


REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(360, 'Trends', 'Availability', 'Availability/Trends.php', 'Assumed Host Status:ahstatus:select:OSS_DIGIT.OSS_DOT.OSS_DASH.OSS_NULLABLE:ASSUMEDHOSTSTATE:5', '', 4),
(361, 'Availability State', 'Availability', 'Availability/Availability.php', '', '', 1),
(362, 'Event Histogram', 'Availability', 'Availability/EventHistogram.php', '', '', 1),
(364, 'Event Summary', 'Availability', 'Availability/EventSummary.php', '', '', 1),
(365, 'Notifications', 'Availability', 'Availability/Notifications.php', '', '', 1);


DELETE FROM `custom_report_types` WHERE id IN (460, 461, 462, 463, 464);

DELETE FROM `custom_report_scheduler`
WHERE SUBSTRING_INDEX(FROM_BASE64(id_report), '###', 1) LIKE 'PCI DSS 3.2: Cloaked Wireless Networks with Uncloaked APs'
OR SUBSTRING_INDEX(FROM_BASE64(id_report), '###', 1) LIKE 'PCI DSS 3.2: Encrypted Networks Having Unencrypted APs'
OR SUBSTRING_INDEX(FROM_BASE64(id_report), '###', 1) LIKE 'PCI DSS 3.2: Suspicious Clients on Wireless Networks'
OR SUBSTRING_INDEX(FROM_BASE64(id_report), '###', 1) LIKE 'PCI DSS 3.2: Wireless Networks'
OR SUBSTRING_INDEX(FROM_BASE64(id_report), '###', 1) LIKE 'PCI DSS 3.2: Wireless Networks Using Weak Encryption';


DELIMITER $$

-- Drop deprecated procedures related to Risk Maps
DROP PROCEDURE IF EXISTS business_processes$$
DROP PROCEDURE IF EXISTS _bp_member_update$$


DROP PROCEDURE IF EXISTS _orphans_of_sensor$$
CREATE PROCEDURE _orphans_of_sensor(
    uuid VARCHAR(64)
    )
BEGIN
    SELECT UPPER(uuid) into @uuid;
    -- Update default networks
    REPLACE INTO net_sensor_reference (net_id,sensor_id) SELECT id,UNHEX(@uuid) FROM net WHERE name like 'Pvt_%';
    -- Orphan networks
    REPLACE INTO net_sensor_reference (net_id,sensor_id) SELECT n.id,UNHEX(@uuid) FROM net n LEFT JOIN net_sensor_reference ns ON n.id=ns.net_id WHERE ns.sensor_id IS NULL;
    -- Av component hosts
    REPLACE INTO host_sensor_reference (host_id,sensor_id) SELECT h.id,UNHEX(@uuid) FROM host h WHERE h.av_component=1;
    -- Orphan hosts
    REPLACE INTO host_sensor_reference (host_id,sensor_id) SELECT h.id,UNHEX(@uuid) FROM host h LEFT JOIN host_sensor_reference hs ON h.id=hs.host_id WHERE hs.sensor_id IS NULL;
    -- Fix Host net references
    REPLACE INTO alienvault.host_net_reference SELECT host.id,net_id FROM alienvault.host, alienvault.host_ip, alienvault.net_cidrs WHERE host.id = host_ip.host_id AND host_ip.ip >= net_cidrs.begin AND host_ip.ip <= net_cidrs.end;
    -- OCS
    IF NOT EXISTS(SELECT 1 FROM task_inventory WHERE task_sensor = UNHEX(@uuid) AND task_name='default_inventory') THEN
        INSERT IGNORE INTO task_inventory (task_type,task_period,task_enable,task_sensor,task_name) VALUES (3,3600,1,UNHEX(@uuid),'default_inventory');
    END IF;
    -- Host/Networks without ctx
    UPDATE net SET ctx=(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id') WHERE ctx=UNHEX('00000000000000000000000000000000');
    UPDATE host SET ctx=(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id') WHERE ctx=UNHEX('00000000000000000000000000000000');
END$$


DROP PROCEDURE IF EXISTS system_delete$$
CREATE PROCEDURE system_delete(
    system_id VARCHAR(36)
)
DELETE_SYSTEM:BEGIN

    SET @system_id = REPLACE(system_id,'-','');

    IF NOT EXISTS(SELECT 1 FROM alienvault.system WHERE id=UNHEX(@system_id)) THEN

    SELECT CONCAT('System ',system_id,' does not exists') as status;
        LEAVE DELETE_SYSTEM;
    END IF;

    SELECT HEX(sensor_id) FROM alienvault.system WHERE id=UNHEX(@system_id) into @sensor_id;
    SELECT HEX(server_id) FROM alienvault.system WHERE id=UNHEX(@system_id) into @server_id;

    -- Delete all sensor references if doesn't exists other system
    IF EXISTS(SELECT 1 FROM alienvault.sensor WHERE id=UNHEX(@sensor_id)) AND NOT EXISTS(SELECT 1 FROM alienvault.system WHERE id!=UNHEX(@system_id) AND sensor_id=UNHEX(@sensor_id)) THEN
        -- Sensor related tables
        DELETE FROM acl_sensors WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM sensor_properties WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM sensor_stats WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM location_sensor_reference WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM vuln_job_schedule WHERE email = @sensor_id;
        DELETE FROM vuln_nessus_servers WHERE hostname = @sensor_id;
        UPDATE policy, policy_sensor_reference SET policy.active=0 WHERE policy.id=policy_sensor_reference.policy_id AND policy_sensor_reference.sensor_id = UNHEX(@sensor_id);
        DELETE FROM policy_sensor_reference WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM sensor WHERE id = UNHEX(@sensor_id);

        SET @disable_calc_perms=1;
        -- Host related with sensors
        DELETE FROM host_sensor_reference WHERE sensor_id = UNHEX(@sensor_id);
        CREATE TEMPORARY TABLE IF NOT EXISTS tmpdel (PRIMARY KEY(id)) AS SELECT h.id FROM host h LEFT JOIN host_sensor_reference aux ON h.id=aux.host_id WHERE aux.host_id IS NULL;
        DELETE h FROM host h LEFT JOIN host_sensor_reference aux ON h.id=aux.host_id WHERE aux.host_id IS NULL;
        DELETE aux FROM host_net_reference aux LEFT JOIN tmpdel h ON h.id=aux.host_id WHERE h.id IS NOT NULL;
        DELETE aux FROM host_types aux LEFT JOIN tmpdel h ON h.id=aux.host_id WHERE h.id IS NOT NULL;
        DELETE aux FROM host_services aux LEFT JOIN tmpdel h ON h.id=aux.host_id WHERE h.id IS NOT NULL;
        DELETE aux FROM host_properties aux LEFT JOIN tmpdel h ON h.id=aux.host_id WHERE h.id IS NOT NULL;
        DELETE aux FROM host_software aux LEFT JOIN tmpdel h ON h.id=aux.host_id WHERE h.id IS NOT NULL;
        DELETE aux FROM host_scan aux LEFT JOIN tmpdel h ON h.id=aux.host_id WHERE h.id IS NOT NULL;
        DELETE aux FROM host_vulnerability aux LEFT JOIN tmpdel h ON h.id=aux.host_id WHERE h.id IS NOT NULL;
        DELETE aux FROM repository_relationships aux LEFT JOIN tmpdel h ON h.id=unhex(aux.keyname) WHERE h.id IS NOT NULL;
        DELETE aux FROM host_qualification aux LEFT JOIN tmpdel h ON h.id=aux.host_id WHERE h.id IS NOT NULL;
        DELETE aux FROM host_group_reference aux LEFT JOIN tmpdel h ON h.id=aux.host_id WHERE h.id IS NOT NULL;
        DELETE aux FROM host_ip aux LEFT JOIN tmpdel h ON h.id=aux.host_id WHERE h.id IS NOT NULL;
        DROP TABLE tmpdel;

        -- Networks related with sensors
        DELETE FROM net_sensor_reference WHERE sensor_id = UNHEX(@sensor_id);
        CREATE TEMPORARY TABLE IF NOT EXISTS tmpdel (PRIMARY KEY(id)) AS SELECT n.id FROM net n LEFT JOIN net_sensor_reference aux ON n.id=aux.net_id WHERE aux.net_id IS NULL;
        DELETE n FROM net n LEFT JOIN net_sensor_reference aux ON n.id=aux.net_id WHERE aux.net_id IS NULL;
        DELETE aux FROM host_net_reference aux LEFT JOIN tmpdel n ON n.id=aux.net_id WHERE n.id IS NOT NULL;
        DELETE aux FROM repository_relationships aux LEFT JOIN tmpdel n ON n.id=unhex(aux.keyname) WHERE n.id IS NOT NULL;
        DELETE aux FROM net_cidrs aux LEFT JOIN tmpdel n ON n.id=aux.net_id WHERE n.id IS NOT NULL;
        DELETE aux FROM net_sensor_reference aux LEFT JOIN tmpdel n ON n.id=aux.net_id WHERE n.id IS NOT NULL;
        DELETE aux FROM host_net_reference aux LEFT JOIN tmpdel n ON n.id=aux.net_id WHERE n.id IS NOT NULL;
        DELETE aux FROM net_scan aux LEFT JOIN tmpdel n ON n.id=aux.net_id WHERE n.id IS NOT NULL;
        DELETE aux FROM net_qualification aux LEFT JOIN tmpdel n ON n.id=aux.net_id WHERE n.id IS NOT NULL;
        DELETE aux FROM net_group_reference aux LEFT JOIN tmpdel n ON n.id=aux.net_id WHERE n.id IS NOT NULL;
        DELETE aux FROM net_vulnerability aux LEFT JOIN tmpdel n ON n.id=aux.net_id WHERE n.id IS NOT NULL;
        DROP TABLE tmpdel;

        SET @disable_calc_perms=NULL;
        CALL update_all_users();
    END IF;

    -- Delete all server references if doesn't exists other system
    IF EXISTS(SELECT 1 FROM alienvault.server WHERE id=UNHEX(@server_id)) AND NOT EXISTS(SELECT 1 FROM alienvault.system WHERE id!=UNHEX(@system_id) AND server_id=UNHEX(@server_id)) THEN


        DELETE FROM server_hierarchy WHERE child_id = UNHEX(@server_id) OR parent_id = UNHEX(@server_id);
        DELETE FROM server_forward_role WHERE server_src_id = UNHEX(@server_id) OR server_dst_id = UNHEX(@server_id);
        DELETE FROM server_role WHERE server_id = UNHEX(@server_id);
        DELETE FROM server WHERE id = UNHEX(@server_id);

    END IF;

    -- Delete system
    DELETE FROM alienvault.system WHERE id=UNHEX(@system_id);
    SELECT CONCAT('System deleted') as status;

END$$


DROP PROCEDURE IF EXISTS remove_sensor_properties_columns$$
CREATE PROCEDURE remove_sensor_properties_columns() BEGIN
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'sensor_properties' AND COLUMN_NAME = 'has_nagios')
    THEN
        ALTER TABLE `sensor_properties` DROP COLUMN `has_nagios`;
        ALTER TABLE `sensor_properties` DROP COLUMN `has_kismet`;
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
    ctxs TEXT,
    version VARCHAR(64),
    ntop INT,
    vuln INT,
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
        SELECT IF (ntop<0 OR ntop>1,1,ntop) into @ntop;
        SELECT IF (vuln<0 OR vuln>1,1,vuln) into @vuln;
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

        SET @query = 'REPLACE INTO sensor_properties (sensor_id,version,has_ntop,has_vuln_scanner,ids,passive_inventory,netflows) VALUES (UNHEX(?), ?, ?, ?, ?, ?, ?)';
        PREPARE stmt1 FROM @query;
        EXECUTE stmt1 USING @uuid, @version, @ntop, @vuln, @ids, @passive_inventory, @netflows;
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

CALL remove_sensor_properties_columns();


REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2019-07-09');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.7.4');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
