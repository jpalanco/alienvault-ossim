USE alienvault_siem;

CREATE TABLE IF NOT EXISTS `otx_data` (
  `event_id` BINARY(16) NOT NULL,
  `pulse_id` VARBINARY(16) NOT NULL,
  `ioc_hash` VARCHAR(32) NOT NULL,
  `ioc_value` VARCHAR(2048) NULL,
  INDEX `ioc` (`ioc_value`(255) ASC),
  INDEX `pulse` (`pulse_id` ASC),
  PRIMARY KEY (`event_id`, `pulse_id`, `ioc_hash`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

USE alienvault;
SET AUTOCOMMIT=0;

CREATE TABLE IF NOT EXISTS `hids_agents` (
  `sensor_id` BINARY(16) NOT NULL,
  `agent_id` VARCHAR(5) NOT NULL,
  `agent_name` VARCHAR(128) NULL,
  `agent_ip` VARCHAR(32) NULL,
  `agent_status` TINYINT(1) NULL,
  `host_id` BINARY(16) NULL,
  PRIMARY KEY (`sensor_id`, `agent_id`),
  INDEX `status` (`agent_status` ASC),
  INDEX `host_id` (`host_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `plugin_data` (
  `ctx` BINARY(16) NOT NULL,
  `plugin_id` INT NOT NULL,
  `plugin_name` VARCHAR(128) NULL,
  `vendor` VARCHAR(128) NULL,
  `model` VARCHAR(128) NULL,
  `version` VARCHAR(128) NULL,
  `nsids` INT(11) NULL DEFAULT 0,
  `nassets` INT(11) NULL DEFAULT 0,
  `plugin_type` TINYINT(1) NULL DEFAULT 0,
  `last_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ctx`, `plugin_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `sem_stats_events` (
  `day` int(11) NOT NULL,
  `sensor` varchar(15) NOT NULL,
  `counter` int(11) NOT NULL,
  PRIMARY KEY (`day`,`sensor`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `otx_data` (
  `event_id` BINARY(16) NOT NULL,
  `pulse_id` VARBINARY(16) NOT NULL,
  `ioc_hash` VARCHAR(32) NOT NULL,
  `ioc_value` VARCHAR(2048) NULL,
  INDEX `ioc` (`ioc_value`(255) ASC),
  INDEX `pulse` (`pulse_id` ASC),
  PRIMARY KEY (`event_id`, `pulse_id`, `ioc_hash`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

DELIMITER $$

DROP FUNCTION IF EXISTS host_where$$
CREATE FUNCTION host_where( user VARCHAR(64), aka VARCHAR(32) )
RETURNS TEXT
BEGIN
    DECLARE perms TEXT DEFAULT '';
    DECLARE host_where TEXT DEFAULT '';
    DECLARE net_where TEXT DEFAULT '';

    IF NOT ( is_admin(user) ) THEN
    
        SELECT EXISTS (SELECT 1 from user_ctx_perm where login = user) INTO @get_ctx_where;
        SELECT EXISTS (SELECT 1 from user_host_perm where login = user) INTO @get_host_where;
        SELECT EXISTS (SELECT 1 from user_net_perm where login = user) INTO @get_net_where;
        SELECT EXISTS (SELECT 1 from user_host_perm where asset_id=UNHEX('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF') AND login = user) INTO @host_ff;
        SELECT EXISTS (SELECT 1 from user_net_perm where asset_id=UNHEX('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF') AND login = user) INTO @net_ff;
        
        -- Forensic events
        IF aka = 'events' THEN
    
            IF @get_ctx_where THEN
                SET perms = CONCAT(perms,' AND acid_event.ctx IN (select ctx from user_ctx_perm where login = "',user,'")');
            END IF;
    
            IF @get_host_where THEN
                SET host_where = CONCAT(host_where,'(acid_event.src_host in (select asset_id from user_host_perm where login = "',user,'") OR acid_event.dst_host in (select asset_id from user_host_perm where login = "',user,'"))');
            END IF;
    
            IF @get_net_where THEN
                SET net_where = CONCAT(net_where,'(acid_event.src_net in (select asset_id from user_net_perm where login = "',user,'") OR acid_event.dst_net in (select asset_id from user_net_perm where login = "',user,'"))');
            END IF;
    
        -- Host / others tables (using given alias)
        ELSE
    
            IF @get_ctx_where THEN
                SET perms = CONCAT(perms,' AND ',aka,'ctx IN (select ctx from user_ctx_perm where login = "',user,'")');
            END IF;
    
            IF @get_host_where THEN
                SET host_where = CONCAT(host_where,aka,'id IN (select asset_id from user_host_perm where login = "',user,'")');
            END IF;
    
            IF @get_net_where THEN
                SET net_where = CONCAT(net_where,aka,'id IN (SELECT host_id FROM host_net_reference, user_net_perm WHERE host_net_reference.net_id=user_net_perm.asset_id and login = "',user,'")');
            END IF;
    
        END IF;
    
        -- No asset allowed 
        IF NOT @get_ctx_where AND @host_ff AND @net_ff THEN
            SET perms = CONCAT(perms,' AND ( ',host_where,' OR ',net_where,' )');
        ELSE
            -- make sql where
            IF host_where <> '' AND NOT @host_ff THEN
                IF net_where <> '' AND NOT @net_ff THEN
                    SET perms = CONCAT(perms,' AND ( ',host_where,' OR ',net_where,' )');
                ELSE
                    SET perms = CONCAT(perms,' AND ',host_where);
                END IF;
            ELSE
                IF net_where <> '' AND NOT @net_ff THEN
                    SET perms = CONCAT(perms,' AND ',net_where);
                END IF;
            END IF;
        END IF;
        
    END IF;
        
    RETURN perms;
END$$

DROP PROCEDURE IF EXISTS otx_get_top_pulses$$
CREATE PROCEDURE otx_get_top_pulses(
    IN login VARCHAR(64), -- string like - 'admin'
    IN top INT, -- 0 no limit
    IN date_from VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
    IN date_to VARCHAR(19) -- datetime - '2013-07-15 08:00:00'
)
BEGIN
    SELECT if(top>0,CONCAT(' LIMIT ',top),'') into @limit;
    SELECT if(date_from='' AND date_to='','',CONCAT(' AND timestamp BETWEEN "',date_from,'" AND "',date_to,'" ')) into @date_filter;
    SELECT host_where(login,'events') into @perms;
    -- SELECT if(date_from='' AND date_to='','','FORCE INDEX (timestamp)') into @forceindex;
    set @forceindex = '';

    SET @query = CONCAT('SELECT count(distinct event_id) as total,hex(otx_data.pulse_id) as pulse_id FROM alienvault_siem.otx_data, alienvault_siem.acid_event ',@forceindex,' WHERE acid_event.id=otx_data.event_id ',@date_filter,@perms,' GROUP BY otx_data.pulse_id ORDER BY total DESC',@limit);
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
END$$

DROP PROCEDURE IF EXISTS otx_get_trend$$
CREATE PROCEDURE otx_get_trend(
    IN login VARCHAR(64), -- string like - 'admin'
    IN pulse_id TEXT, -- id '0x555E5AD4B45FF57A89E5B43C,0x548B3F4D11D40843C065F6F2'
    IN date_from VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
    IN date_to VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
    IN tz VARCHAR(6) -- Timezone offset '+02:00'
)
BEGIN
    SELECT if(pulse_id='','',CONCAT(' AND pulse_id in (',pulse_id,') ')) into @pulse_id;
    SELECT if(date_from='' AND date_to='','',CONCAT(' AND timestamp BETWEEN "',date_from,'" AND "',date_to,'" ')) into @date_filter;
    SELECT if(tz='','+00:00',tz) into @tz;
    SELECT CONCAT(',date(convert_tz(timestamp,"+00:00","',@tz,'")) as day') into @freq;
    SELECT host_where(login,'events') into @perms;
    -- SELECT if(pulse_id='','FORCE INDEX (timestamp)','') into @forceindex;
    set @forceindex = '';

    SET @query = CONCAT('SELECT count(distinct event_id) as total',@freq,' FROM alienvault_siem.otx_data, alienvault_siem.acid_event ',@forceindex,' WHERE acid_event.id=otx_data.event_id ',@pulse_id,@date_filter,@perms,' GROUP BY day ORDER BY day DESC');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
END$$

DROP PROCEDURE IF EXISTS otx_get_total_events$$
CREATE PROCEDURE otx_get_total_events(
    IN login VARCHAR(64) -- string like - 'admin'
)
BEGIN
    SELECT host_where(login,'events') into @perms;
    
    IF @perms='' THEN
        SET @query = CONCAT('SELECT count(distinct event_id) as total FROM alienvault_siem.otx_data');
    ELSE
        SET @query = CONCAT('SELECT count(distinct event_id) as total FROM alienvault_siem.otx_data, alienvault_siem.acid_event WHERE acid_event.id=otx_data.event_id ',@perms);
    END IF;
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
END$$

DROP PROCEDURE IF EXISTS otx_get_total_alarms$$
CREATE PROCEDURE otx_get_total_alarms(
    IN login VARCHAR(64) -- string like - 'admin'
)
BEGIN
    SET @query = CONCAT('SELECT count(distinct event_id) as total FROM alienvault.alarm WHERE plugin_id=1505 AND plugin_sid=29998');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
END$$

DROP PROCEDURE IF EXISTS _host_default_os$$
CREATE PROCEDURE _host_default_os()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE _ip VARCHAR(32);
    DECLARE cur1 CURSOR FOR SELECT hex(ip) FROM _tmpos;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    CREATE TEMPORARY TABLE IF NOT EXISTS _tmpos (ip VARBINARY(16) NOT NULL, PRIMARY KEY ( ip )) ENGINE=MEMORY;
    INSERT IGNORE INTO _tmpos SELECT admin_ip FROM alienvault.system;
    INSERT IGNORE INTO _tmpos SELECT vpn_ip FROM alienvault.system;
    INSERT IGNORE INTO _tmpos SELECT ha_ip FROM alienvault.system;
    INSERT IGNORE INTO _tmpos SELECT ip FROM alienvault.sensor INNER JOIN alienvault.sensor_properties ON sensor.id=sensor_properties.sensor_id WHERE sensor_properties.version<>'';
    INSERT IGNORE INTO _tmpos SELECT ip FROM alienvault.server;

    OPEN cur1;

    REPEAT
        FETCH cur1 INTO _ip;
        IF NOT done THEN
            IF EXISTS (SELECT 1 FROM alienvault.host_properties hp, alienvault.host_ip hi WHERE hp.host_id=hi.host_id AND hi.ip=UNHEX(_ip) AND hp.property_ref=3 AND hp.source_id<>1) THEN
                DELETE hp FROM alienvault.host_properties hp, alienvault.host_ip hi WHERE hp.host_id=hi.host_id AND hi.ip=UNHEX(_ip) AND hp.property_ref=3;
                INSERT IGNORE INTO alienvault.host_properties (host_id,property_ref,source_id,value) SELECT host_id,3,1,'AlienVault OS' FROM host_ip WHERE ip=UNHEX(_ip);
            END IF;
            IF NOT EXISTS (SELECT 1 FROM alienvault.host_properties hp, alienvault.host_ip hi WHERE hp.host_id=hi.host_id AND hi.ip=UNHEX(_ip) AND hp.property_ref=3) THEN
                INSERT IGNORE INTO alienvault.host_properties (host_id,property_ref,source_id,value) SELECT host_id,3,1,'AlienVault OS' FROM host_ip WHERE ip=UNHEX(_ip);
            END IF;
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur1;

    DROP TABLE IF EXISTS _tmpos;
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

        CALL _host_default_os();

        SELECT CONCAT('System ',_system_id,' updated') as status;

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
            DELETE o FROM otx_data o, _ttmp t WHERE o.event_id = t.id;
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

DROP PROCEDURE IF EXISTS _bp_member_update$$
CREATE PROCEDURE _bp_member_update(
    IN _uuid VARCHAR(64),
    IN _measure VARCHAR(32),
    IN _severity INT
)
BEGIN
    SELECT IFNULL(_severity,0) INTO @_severity;
    DELETE FROM bp_member_status WHERE member_id = unhex(_uuid) and measure_type = _measure;
    INSERT IGNORE INTO bp_member_status (`member_id`, `status_date`, `measure_type`, `severity`) VALUES(unhex(_uuid), UTC_TIMESTAMP(), _measure, @_severity);
END$$

DROP PROCEDURE IF EXISTS business_processes$$
CREATE PROCEDURE business_processes()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE _member_id VARCHAR(64);
    DECLARE _member_type VARCHAR(32);
    DECLARE bp_cursor CURSOR FOR SELECT distinct hex(`member`), `type` FROM bp_asset_member ORDER BY `type` ASC;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION ROLLBACK;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    
    SET AUTOCOMMIT=0;
    
    OPEN bp_cursor;

    REPEAT
        FETCH bp_cursor INTO _member_id, _member_type;
        
        IF NOT done THEN

            -- Hosts
            IF _member_type = 'host' THEN
                -- host_metric
                SELECT MAX(risk) FROM alarm a, alarm_hosts ah WHERE ah.id_alarm=a.backlog_id AND a.status='open' AND ah.id_host=UNHEX(_member_id) AND a.timestamp >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 day) INTO @severity;
                CALL _bp_member_update(_member_id, 'host_metric', @severity);

                -- host_vulnerability
                SELECT MAX(vulnerability) FROM host_vulnerability WHERE host_id = unhex(_member_id) INTO @severity;
                CALL _bp_member_update(_member_id, 'host_vulnerability', @severity);
                -- host_availability will be calculated by NagiosMkLiveManager
            END IF;
            
            -- Host Groups
            IF _member_type = 'host_group' THEN
                -- host_group_metric
                SELECT MAX(s.severity) FROM bp_member_status s, host_group_reference r WHERE r.host_id=s.member_id AND r.host_group_id=UNHEX(_member_id) AND s.measure_type='host_metric' INTO @severity;
                CALL _bp_member_update(_member_id, 'host_group_metric', @severity);

                -- host_group_vulnerability
                SELECT MAX(vulnerability) FROM host_vulnerability v, host_group_reference r WHERE v.host_id=r.host_id AND r.host_group_id = unhex(_member_id) INTO @severity;
                CALL _bp_member_update(_member_id, 'host_group_vulnerability', @severity);
                
                -- net_availability
                SELECT MAX(s.severity) FROM bp_member_status s, host_group_reference r WHERE r.host_id=s.member_id AND r.host_group_id=unhex(_member_id) and s.measure_type='host_availability' INTO @severity;
                CALL _bp_member_update(_member_id, 'host_group_availability', @severity);
            END IF;
            
            -- Networks
            IF _member_type = 'net' THEN
                -- net_metric
                SELECT MAX(risk) FROM alarm a, alarm_nets an WHERE an.id_alarm=a.backlog_id AND a.status='open' AND an.id_net=UNHEX(_member_id) AND a.timestamp >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 day) INTO @severity;
                CALL _bp_member_update(_member_id, 'net_metric', @severity);

                -- net_vulnerability
                SELECT MAX(vulnerability) FROM net_vulnerability WHERE net_id = unhex(_member_id) INTO @severity;
                CALL _bp_member_update(_member_id, 'net_vulnerability', @severity);
                
                -- net_availability
                SELECT MAX(s.severity) FROM bp_member_status s, host_net_reference r WHERE r.host_id=s.member_id AND r.net_id=unhex(_member_id) AND s.measure_type='host_availability' INTO @severity;
                CALL _bp_member_update(_member_id, 'net_availability', @severity);
            END IF;
            
            -- Networks
            IF _member_type = 'net_group' THEN
                -- net_group_metric
                SELECT MAX(s.severity) FROM bp_member_status s, net_group_reference r WHERE r.net_id=s.member_id AND r.net_group_id=unhex(_member_id) and s.measure_type='net_metric' INTO @severity;
                CALL _bp_member_update(_member_id, 'net_group_metric', @severity);

                -- net_group_vulnerability
                SELECT MAX(vulnerability) FROM net_vulnerability v, net_group_reference r WHERE v.net_id=r.net_id AND r.net_group_id=unhex(_member_id) INTO @severity;
                CALL _bp_member_update(_member_id, 'net_group_vulnerability', @severity);
                
                -- net_group_availability
                SELECT max(s.severity) FROM bp_member_status s, net_group_reference r WHERE r.net_id=s.member_id AND r.net_group_id=unhex(_member_id) and s.measure_type='net_availability' INTO @severity;
                CALL _bp_member_update(_member_id, 'net_group_availability', @severity);
            END IF;
            
        END IF;
    UNTIL done END REPEAT;

    CLOSE bp_cursor;

    COMMIT;
END$$

CALL business_processes()$$

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
    
    CREATE TEMPORARY TABLE IF NOT EXISTS _tmp_jobs (id int(11) NOT NULL, targets TEXT) ENGINE=MEMORY;
    CREATE TEMPORARY TABLE IF NOT EXISTS _tmp_net (PRIMARY KEY(begin,end)) AS SELECT begin,end,net_id from net_cidrs LIMIT 0;
    INSERT IGNORE INTO _tmp_net SELECT begin,end,net_id from net_cidrs;
    
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
                    INSERT IGNORE INTO vuln_job_assets SELECT _jid, @jtype, n1.net_id FROM _tmp_net n1, net_cidrs n WHERE n1.begin >= n.begin AND n1.end <= n.end AND n.net_id=UNHEX(@uuid);
                    -- host groups
                    IF @asset_type = 'hostgroup' THEN
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT _jid, @jtype, host_id FROM host_group_reference WHERE host_group_id=UNHEX(@uuid);
                    -- network groups
                    ELSEIF @asset_type = 'netgroup' THEN
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT _jid, @jtype, net_id FROM net_group_reference WHERE net_group_id=UNHEX(@uuid);
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT DISTINCT _jid, @jtype, host_id FROM host_net_reference, net_group_reference WHERE host_net_reference.net_id = net_group_reference.net_id AND net_group_reference.net_group_id=UNHEX(@uuid);
                    -- network cidrs
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
    DROP TABLE IF EXISTS _tmp_net;
END$$


DROP PROCEDURE IF EXISTS host_filter$$
CREATE PROCEDURE host_filter(
    IN session_id VARCHAR(64), -- string like - 'admin'
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
    IN net_cidr TEXT, -- free string (% is allowed)
    IN plugins TEXT, -- unquoted string - 4003,1001,7001
    IN hids_filter CHAR -- integer 0 => not deployed, 1 => disconnected, 2 => connected
)
BEGIN

    DECLARE x INT DEFAULT 0;
    DECLARE y INT DEFAULT 0;

    CREATE TABLE IF NOT EXISTS user_host_filter (
        session_id VARCHAR(64) NOT NULL,
        asset_id VARBINARY(16) NOT NULL,
        PRIMARY KEY (`asset_id`,`session_id`)
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
            IF os = 'unknown' THEN
                REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h LEFT JOIN alienvault.host_properties hp ON hp.host_id=h.id AND hp.property_ref=3 WHERE hp.host_id IS NULL;
            END IF;
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

        -- Host related with a plugin list
        IF plugins <> ''
        THEN
            TRUNCATE filters_add;
            IF plugins = '0' THEN
                REPLACE INTO filters_add SELECT h.id FROM alienvault.host h LEFT JOIN alienvault.host_scan ha ON ha.host_id=h.id AND ha.plugin_id!=2007 WHERE ha.host_id IS NULL;
            ELSE
                SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_scan s WHERE s.host_id=h.id AND s.plugin_id in (',plugins,') AND s.plugin_sid=0;');
                PREPARE sql_query from @query;
                EXECUTE sql_query;
                DEALLOCATE PREPARE sql_query;
            END IF;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Host with hids
        IF hids_filter <> ''
        THEN
            TRUNCATE filters_add;
            IF hids_filter = '0' THEN
                REPLACE INTO filters_add SELECT h.id FROM alienvault.host h LEFT JOIN alienvault.hids_agents ha ON ha.host_id=h.id WHERE ha.host_id IS NULL;
            ELSEIF hids_filter = '1' THEN
                REPLACE INTO filters_add SELECT h.id FROM alienvault.host h, alienvault.hids_agents ha WHERE ha.host_id=h.id AND ha.agent_status in (0,1,2);
            ELSEIF hids_filter = '2' THEN
                REPLACE INTO filters_add SELECT h.id FROM alienvault.host h, alienvault.hids_agents ha WHERE ha.host_id=h.id AND ha.agent_status in (3,4);
            END IF;
            DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
        END IF;

        -- Final Results
        IF ftype = 'host'
        THEN
            IF drop_table = 1
            THEN
                DELETE FROM user_host_filter WHERE user_host_filter.session_id=session_id;
                INSERT INTO user_host_filter SELECT session_id,id from filters_tmp;
            ELSE
                DELETE h FROM user_host_filter h LEFT JOIN filters_tmp t ON h.asset_id=t.id WHERE h.session_id=session_id AND t.id IS NULL;
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
                DELETE FROM user_host_filter WHERE user_host_filter.session_id=session_id;
                INSERT INTO user_host_filter SELECT session_id,id from filters_tmpg;
            ELSE
                DELETE h FROM user_host_filter h LEFT JOIN filters_tmpg t ON h.asset_id=t.id WHERE h.session_id=session_id AND t.id IS NULL;
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
                DELETE FROM user_host_filter WHERE user_host_filter.session_id=session_id;
                INSERT INTO user_host_filter SELECT session_id,id from filters_tmpg;
            ELSE
                DELETE h FROM user_host_filter h LEFT JOIN filters_tmpg t ON h.asset_id=t.id WHERE h.session_id=session_id AND t.id IS NULL;
            END IF;
        END IF;

    COMMIT;

    SELECT COUNT(asset_id) as assets FROM user_host_filter WHERE user_host_filter.session_id=session_id;
END$$

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
    -- Risk maps indicators
    IF NOT EXISTS(SELECT 1 FROM bp_asset_member WHERE member = UNHEX(@uuid)) THEN
        INSERT IGNORE INTO bp_asset_member (member,type) VALUES (UNHEX(@uuid),'host');
    END IF;
    UPDATE risk_indicators SET type_name = @uuid WHERE `type`='sensor' AND length(type_name) != 32;
    -- OCS
    IF NOT EXISTS(SELECT 1 FROM task_inventory WHERE task_sensor = UNHEX(@uuid) AND task_name='default_inventory') THEN
        INSERT IGNORE INTO task_inventory (task_type,task_period,task_enable,task_sensor,task_name) VALUES (3,3600,1,UNHEX(@uuid),'default_inventory');
    END IF;
    -- Host/Networks without ctx
    UPDATE net SET ctx=(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id') WHERE ctx=UNHEX('00000000000000000000000000000000');
    UPDATE host SET ctx=(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id') WHERE ctx=UNHEX('00000000000000000000000000000000');
END$$

DROP PROCEDURE IF EXISTS addcol$$
CREATE PROCEDURE addcol() BEGIN

    IF EXISTS (SELECT support FROM information_schema.engines WHERE engine='tokudb') THEN
        SET @query = 'ALTER TABLE alienvault.otx_data ENGINE=TokuDB';
        PREPARE sql_query from @query;
        EXECUTE sql_query;
        DEALLOCATE PREPARE sql_query;
        SET @query = 'ALTER TABLE alienvault_siem.otx_data ENGINE=TokuDB';
        PREPARE sql_query from @query;
        EXECUTE sql_query;
        DEALLOCATE PREPARE sql_query;
    END IF;

    IF is_pro() THEN
        UPDATE users SET language='en_GB' where language!='en_GB' AND language!='es_ES';
    END IF;

END$$

CALL addcol()$$
DROP PROCEDURE addcol$$

CALL _host_default_os()$$

DELIMITER ;

-- Create New OTX pod
REPLACE INTO `dashboard_custom_type` (`id`, `name`, `type`, `category`, `title_default`, `help_default`, `file`, `params`, `thumb`) VALUES(14001, 'Top Pulses', 'chart', 'OTX', 'Top OTX Activity in Your Environment', 'Top OTX Activity in Your Environment.', 'widgets/data/otx.php?type=top', 'Type:type:select:OSS_LETTER:pie,hbar,vbar::Pie,Horizontal Bar,Vertical Bar;Legend:legend:radiobuttons:OSS_DIGIT:1,0::Yes,No;Position:position:radiobuttons:OSS_LETTER:nw,n,ne,e,se,s,sw,w::North West,North,North East,East,South East,South,South West,West;Legend Columns:columns:text:OSS_DIGIT:1:4:1;Placement:placement:radiobuttons:OSS_LETTER:outsideGrid,insideGrid::Outside Grid,Inside Grid;Number of Pulses:top:text:OSS_DIGIT:5:20:1;Period of Time:range:select:OSS_DIGIT:1,7,14,30:7:Last Day, Last Week, Last Two Weeks, Last Month', '14001.png');

-- Delete pods for executive panel
DELETE FROM `dashboard_widget_config` WHERE `panel_id`=1;

-- Insert new pods in executive panel
REPLACE INTO `dashboard_widget_config` (`panel_id`, `type_id`, `user`, `col`, `fil`, `height`, `title`, `help`, `refresh`, `color`, `file`, `type`, `asset`, `media`, `params`) VALUES
(1, 3006, '0', 0, 0, 320, 'Security Events: Top 5 Alarms', 'Events in the SIEM: Top 5 Events with risk >= than 1', 0, '', 'widgets/data/security.php?type=alarms', 'chart', 'ALL_ASSETS', NULL, 'a:6:{s:3:"top";s:1:"5";s:4:"type";s:4:"vbar";s:6:"legend";s:1:"0";s:8:"position";s:2:"nw";s:7:"columns";s:1:"1";s:9:"placement";s:11:"outsideGrid";}'),
(1, 1005, '0', 1, 0, 320, 'SIEM: Top 10 Event Categories', 'Top 10 Events in the SIEM grouped by Categories', 0, '', 'widgets/data/siem.php?type=category', 'chart', 'ALL_ASSETS', NULL, 'a:7:{s:3:"top";s:1:"5";s:5:"range";s:1:"7";s:4:"type";s:3:"pie";s:6:"legend";s:1:"1";s:8:"position";s:1:"w";s:7:"columns";s:1:"1";s:9:"placement";s:10:"insideGrid";}'),
(1, 14001, '0', 2, 0, 320, 'Top OTX Activity in Your Environment', 'Top OTX Activity in Your Environment.', 0, '', 'widgets/data/otx.php?type=top', 'chart', 'ALL_ASSETS', NULL, 'a:3:{s:4:"type";s:4:"hbar";s:3:"top";s:1:"5";s:5:"range";s:2:"14";}'),
(1, 1001, '0', 0, 1, 240, 'Latest SIEM vs Logger Events', 'Events in the SIEM vs Events in the Logger (Today)', 0, '', 'widgets/data/siem.php?type=siemlogger', 'chart', 'ALL_ASSETS', NULL, 'a:2:{s:4:"type";s:7:"raphael";s:5:"range";s:2:"24";}'),
(1, 3004, '0', 1, 1, 240, 'Top 10 Hosts with Multiple Events', 'Top 10 Hosts by unique events', 0, '', 'widgets/data/security.php?type=unique', 'chart', 'ALL_ASSETS', NULL, 'a:7:{s:3:"top";s:2:"10";s:5:"range";s:1:"7";s:4:"type";s:4:"hbar";s:6:"legend";s:1:"0";s:8:"position";s:2:"nw";s:7:"columns";s:1:"1";s:9:"placement";s:11:"outsideGrid";}'),
(1, 1002, '0', 2, 1, 240, 'SIEM: Events by Sensor/Data Source', 'Events in the SIEM grouped by Sensor and Data Source', 0, 'db_color_13', 'widgets/data/siem.php?type=eventsbysensordata', 'chart', 'ALL_ASSETS', NULL, 'a:1:{s:4:"type";s:5:"radar";}');


REPLACE INTO `alienvault`.`asset_filter_types` (`id`, `filter`, `type`) VALUES (25, 'plugin', 'list'),(26, 'hids', 'value');

-- Menu
DELETE FROM `acl_perm` WHERE `id`=27;
REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(42, 'MENU', 'environment-menu', 'ToolsScan', 'Environment -> Assets & Groups -> Assets -> Discover New Assets', 0, 1, 1, '03.04'),
(84, 'MENU', 'dashboard-menu', 'IPReputation', 'Dashboard -> Open Threat Exchange, Configuration -> Open Threat Exchange', 0, 0, 1, '01.06');

-- Delete NTOP
DELETE FROM config WHERE conf like '%ntop%';
DELETE FROM custom_report_types WHERE `file` like '%GlobalTCPUDPProtocolDistribution.php%' OR `file` like '%HistoricalView.php%' OR `file` like '%Throughput.php%'; 

REPLACE INTO `user_config` (`login`, `category`, `name`, `value`) VALUES ('admin','custom_report','PCI DSS 3.0: Access Control Device Denied','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:41:\"PCI DSS 3.0: Access Control Device Denied\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2027;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:41:\"PCI DSS 3.0: Access Control Device Denied\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 14:15:10\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Admin Access to Systems','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:36:\"PCI DSS 3.0: Admin Access to Systems\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2018;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:36:\"PCI DSS 3.0: Admin Access to Systems\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:23:23\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: All Antivirus Security Risk Events','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:47:\"PCI DSS 3.0: All Antivirus Security Risk Events\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 5\";s:8:\"comments\";s:464:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 5: Use and regularly update &lt;span id=&quot;be18aa3d-5538-4f9e-882a-0633b3061f23&quot; ginger_software_uiphraseguid=&quot;b0a6b6fc-6ae7-4c14-bf97-f10dc6b0474d&quot; class=&quot;GINGER_SOFTWARE_mark&quot;&gt;Anti-Virus&lt;/span&gt;&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 5&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2000;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:47:\"PCI DSS 3.0: All Antivirus Security Risk Events\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:50:43\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: All Virus Events','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:29:\"PCI DSS 3.0: All Virus Events\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 5\";s:8:\"comments\";s:464:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 5: Use and regularly update &lt;span id=&quot;be18aa3d-5538-4f9e-882a-0633b3061f23&quot; ginger_software_uiphraseguid=&quot;b0a6b6fc-6ae7-4c14-bf97-f10dc6b0474d&quot; class=&quot;GINGER_SOFTWARE_mark&quot;&gt;Anti-Virus&lt;/span&gt;&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 5&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2001;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:29:\"PCI DSS 3.0: All Virus Events\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:54:14\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Antivirus Definition Updates','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:41:\"PCI DSS 3.0: Antivirus Definition Updates\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 5\";s:8:\"comments\";s:464:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 5: Use and regularly update &lt;span id=&quot;be18aa3d-5538-4f9e-882a-0633b3061f23&quot; ginger_software_uiphraseguid=&quot;b0a6b6fc-6ae7-4c14-bf97-f10dc6b0474d&quot; class=&quot;GINGER_SOFTWARE_mark&quot;&gt;Anti-Virus&lt;/span&gt;&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 5&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2003;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:41:\"PCI DSS 3.0: Antivirus Definition Updates\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:51:46\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Antivirus Disabled','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:31:\"PCI DSS 3.0: Antivirus Disabled\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 5\";s:8:\"comments\";s:464:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 5: Use and regularly update &lt;span id=&quot;be18aa3d-5538-4f9e-882a-0633b3061f23&quot; ginger_software_uiphraseguid=&quot;b0a6b6fc-6ae7-4c14-bf97-f10dc6b0474d&quot; class=&quot;GINGER_SOFTWARE_mark&quot;&gt;Anti-Virus&lt;/span&gt;&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 5&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2002;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:31:\"PCI DSS 3.0: Antivirus Disabled\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:54:56\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Authentications with Default Credentials','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:53:\"PCI DSS 3.0: Authentications with Default Credentials\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 2\";s:8:\"comments\";s:297:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 2: Do not use vendor-supplied defaults&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the&nbsp; PCI DSS requirement 2&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:1076;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:53:\"PCI DSS 3.0: Authentications with Default Credentials\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:20:58\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Cloaked Wireless Networks with Uncloaked APs','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:57:\"PCI DSS 3.0: Cloaked Wireless Networks with Uncloaked APs\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:461;a:2:{s:8:\"location\";s:1:\"0\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:57:\"PCI DSS 3.0: Cloaked Wireless Networks with Uncloaked APs\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 17:04:27\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Database Errors','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:28:\"PCI DSS 3.0: Database Errors\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2021;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:28:\"PCI DSS 3.0: Database Errors\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:24:08\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Database Failed Logins','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:35:\"PCI DSS 3.0: Database Failed Logins\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2020;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:35:\"PCI DSS 3.0: Database Failed Logins\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:25:10\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Database Successful Logins','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:39:\"PCI DSS 3.0: Database Successful Logins\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2022;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:39:\"PCI DSS 3.0: Database Successful Logins\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:25:29\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Dropped or Denied Connections','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:42:\"PCI DSS 3.0: Dropped or Denied Connections\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 1\";s:8:\"comments\";s:307:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 1: Install and Maintain a Firewall Configuration&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review as stated in PCI DSS requirement 1&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2008;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:42:\"PCI DSS 3.0: Dropped or Denied Connections\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 11:21:05\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Encrypted HTTPS Connections','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:40:\"PCI DSS 3.0: Encrypted HTTPS Connections\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 4\";s:8:\"comments\";s:284:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 4: Encrypt Transmission of Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 4&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2005;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:40:\"PCI DSS 3.0: Encrypted HTTPS Connections\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:46:19\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Encrypted Networks with Unencrypted APs','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:52:\"PCI DSS 3.0: Encrypted Networks with Unencrypted APs\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 4\";s:8:\"comments\";s:284:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 4: Encrypt Transmission of Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 4&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:462;a:2:{s:8:\"location\";s:1:\"0\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:52:\"PCI DSS 3.0: Encrypted Networks with Unencrypted APs\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:49:26\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Encrypted VPN Client Connections Accepted','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:54:\"PCI DSS 3.0: Encrypted VPN Client Connections Accepted\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 4\";s:8:\"comments\";s:284:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 4: Encrypt Transmission of Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 4&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2006;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:54:\"PCI DSS 3.0: Encrypted VPN Client Connections Accepted\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:45:55\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Encrypted VPN Client Connections Failed','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:52:\"PCI DSS 3.0: Encrypted VPN Client Connections Failed\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 4\";s:8:\"comments\";s:284:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 4: Encrypt Transmission of Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 4&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2007;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:52:\"PCI DSS 3.0: Encrypted VPN Client Connections Failed\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:47:37\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Failed Logins','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:26:\"PCI DSS 3.0: Failed Logins\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2025;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:26:\"PCI DSS 3.0: Failed Logins\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:30:52\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Firewall Configuration Changes','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:43:\"PCI DSS 3.0: Firewall Configuration Changes\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 1\";s:8:\"comments\";s:307:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 1: Install and Maintain a Firewall Configuration&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review as stated in PCI DSS requirement 1&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2010;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:43:\"PCI DSS 3.0: Firewall Configuration Changes\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 11:21:30\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Firewall Failed Authentication','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:43:\"PCI DSS 3.0: Firewall Failed Authentication\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 1\";s:8:\"comments\";s:307:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 1: Install and Maintain a Firewall Configuration&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review as stated in PCI DSS requirement 1&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2011;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:43:\"PCI DSS 3.0: Firewall Failed Authentication\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 11:21:50\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Firewall Intrusion Detection','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:41:\"PCI DSS 3.0: Firewall Intrusion Detection\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 1\";s:8:\"comments\";s:307:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 1: Install and Maintain a Firewall Configuration&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review as stated in PCI DSS requirement 1&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2013;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:41:\"PCI DSS 3.0: Firewall Intrusion Detection\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 11:11:26\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Firewall Successful Authentication','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:47:\"PCI DSS 3.0: Firewall Successful Authentication\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 1\";s:8:\"comments\";s:307:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 1: Install and Maintain a Firewall Configuration&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review as stated in PCI DSS requirement 1&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2014;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:47:\"PCI DSS 3.0: Firewall Successful Authentication\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 11:22:19\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Infected Computers','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:31:\"PCI DSS 3.0: Infected Computers\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 5\";s:8:\"comments\";s:464:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 5: Use and regularly update &lt;span id=&quot;be18aa3d-5538-4f9e-882a-0633b3061f23&quot; ginger_software_uiphraseguid=&quot;b0a6b6fc-6ae7-4c14-bf97-f10dc6b0474d&quot; class=&quot;GINGER_SOFTWARE_mark&quot;&gt;Anti-Virus&lt;/span&gt;&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 5&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2004;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:31:\"PCI DSS 3.0: Infected Computers\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:53:41\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Information Security Policy Compliance Checks','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:58:\"PCI DSS 3.0: Information Security Policy Compliance Checks\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:15:\"Requirements 12\";s:8:\"comments\";s:333:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 12: Maintain an Information Security Policy&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;&lt;span style=&quot;font-weight: normal;&quot;&gt;The following report provides support for the systematic review PCI DSS requirement 12&lt;/span&gt;&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2015;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:58:\"PCI DSS 3.0: Information Security Policy Compliance Checks\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:57:36\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Information Security Policy Compliance Failed','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:58:\"PCI DSS 3.0: Information Security Policy Compliance Failed\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:15:\"Requirements 12\";s:8:\"comments\";s:333:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 12: Maintain an Information Security Policy&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;&lt;span style=&quot;font-weight: normal;&quot;&gt;The following report provides support for the systematic review PCI DSS requirement 12&lt;/span&gt;&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2016;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:58:\"PCI DSS 3.0: Information Security Policy Compliance Failed\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:58:19\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Security Device Policy Modifications','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:49:\"PCI DSS 3.0: Security Device Policy Modifications\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:15:\"Requirements 12\";s:8:\"comments\";s:333:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 12: Maintain an Information Security Policy&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;&lt;span style=&quot;font-weight: normal;&quot;&gt;The following report provides support for the systematic review PCI DSS requirement 12&lt;/span&gt;&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2017;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:49:\"PCI DSS 3.0: Security Device Policy Modifications\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:59:16\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Successful Logins','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:30:\"PCI DSS 3.0: Successful Logins\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2029;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:30:\"PCI DSS 3.0: Successful Logins\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:32:28\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Suspicious Clients on Wireless Networks','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:52:\"PCI DSS 3.0: Suspicious Clients on Wireless Networks\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:464;a:2:{s:8:\"location\";s:1:\"0\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:52:\"PCI DSS 3.0: Suspicious Clients on Wireless Networks\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:35:27\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Suspicious Database Events','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:39:\"PCI DSS 3.0: Suspicious Database Events\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2026;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:39:\"PCI DSS 3.0: Suspicious Database Events\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:29:52\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Vulnerability Details','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:34:\"PCI DSS 3.0: Vulnerability Details\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:14:\"Requirement 11\";s:8:\"comments\";s:268:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 11: Regularly Test Security Systems&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 11&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:200;a:6:{s:7:\"serious\";s:1:\"1\";s:4:\"high\";s:1:\"1\";s:6:\"medium\";s:1:\"1\";s:3:\"low\";s:1:\"1\";s:4:\"info\";s:1:\"1\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:34:\"PCI DSS 3.0: Vulnerability Details\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:56:28\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Wireless Networks','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:30:\"PCI DSS 3.0: Wireless Networks\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:460;a:2:{s:8:\"location\";s:1:\"0\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:30:\"PCI DSS 3.0: Wireless Networks\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:34:31\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Wireless Networks Using Weak Encryption','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:52:\"PCI DSS 3.0: Wireless Networks Using Weak Encryption\";s:11:\"it_security\";s:0:\"\";s:7:\"address\";s:0:\"\";s:4:\"tlfn\";s:0:\"\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1240:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should be limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 4\";s:8:\"comments\";s:284:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 4: Encrypt Transmission of Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 4&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:463;a:2:{s:8:\"location\";s:1:\"0\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:52:\"PCI DSS 3.0: Wireless Networks Using Weak Encryption\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:38:41\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}');

-- Tickets/Metric.php report module
DELETE FROM custom_report_types WHERE id = 322;
UPDATE user_config SET value = 'a:12:{s:2:"ds";a:8:{i:440;a:7:{s:4:"logo";N;s:9:"maintitle";s:13:"Ticket Report";s:11:"it_security";s:0:"";s:7:"address";s:0:"";s:4:"tlfn";s:0:"";s:4:"date";s:5:"#DATE";s:5:"notes";s:0:"";}i:320;a:2:{s:6:"status";s:3:"All";s:5:"notes";s:0:"";}i:410;a:1:{s:5:"notes";N;}i:321;a:2:{s:6:"status";s:3:"All";s:5:"notes";s:0:"";}i:403;a:1:{s:5:"notes";N;}i:324;a:2:{s:6:"status";s:3:"All";s:5:"notes";s:0:"";}i:407;a:1:{s:5:"notes";N;}i:323;a:2:{s:6:"status";s:3:"All";s:5:"notes";s:0:"";}}s:5:"rname";s:13:"Ticket Report";s:9:"date_from";N;s:7:"date_to";N;s:10:"date_range";s:6:"last30";s:7:"profile";s:7:"Default";s:5:"cdate";s:19:"2010-05-27 10:18:16";s:5:"mdate";s:19:"2015-04-21 05:41:00";s:6:"assets";s:10:"ALL_ASSETS";s:10:"asset_type";s:0:"";s:4:"user";s:1:"0";s:6:"entity";s:2:"-1";}' WHERE category='custom_report' AND name = 'Ticket Report';

UPDATE incident_type SET id = 'Vulnerability' WHERE id = 'OpenVAS Vulnerability';
UPDATE task_inventory SET task_name = 'default_inventory' WHERE task_name = 'default_ocs';
UPDATE incident SET type_id='Vulnerability' WHERE type_id = 'OpenVAS Vulnerability';

UPDATE plugin_group  SET name = 'AlienVault NIDS sigs', descr = 'AlienVault NIDS signatures' WHERE name = 'Snort IDS sigs';
UPDATE plugin_group  SET name = 'AlienVault NIDS HTTP INSPECT', descr = 'AlienVault NIDS HTTP Inspect preprocessor signatures' WHERE name = 'Snort HTTP INSPECT';

UPDATE host_source_reference SET name = 'HIDS' WHERE name = 'OSSEC';

UPDATE users SET pass=md5(login) WHERE login_method='ldap';

-- OTX Taxonomy
REPLACE INTO `alarm_categories` VALUES (100,'OTX Indicators of Compromise');
REPLACE INTO `alarm_taxonomy` (sid,kingdom,category,subcategory) VALUES (29998,2,100,'PULSE');
CALL alarm_taxonomy_populate();

UPDATE log_config SET descr='User %1% logged out %2%' WHERE code=002;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2015-07-27');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.1.0');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA