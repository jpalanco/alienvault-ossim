USE alienvault;
SET AUTOCOMMIT=0;

CREATE TABLE IF NOT EXISTS `user_host_perm` (
  `login` VARCHAR(64) NOT NULL,
  `asset_id` BINARY(16) NOT NULL,
  PRIMARY KEY (`login`, `asset_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `user_net_perm` (
  `login` VARCHAR(64) NOT NULL,
  `asset_id` BINARY(16) NOT NULL,
  PRIMARY KEY (`login`, `asset_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `system` (
  `id` BINARY(16) NOT NULL,
  `name` VARCHAR(64) NOT NULL,
  `admin_ip` VARBINARY(16) NOT NULL,
  `vpn_ip` VARBINARY(16) NULL,
  `profile` VARCHAR(255) NOT NULL,
  `sensor_id` BINARY(16) NULL DEFAULT NULL,
  `server_id` BINARY(16) NULL DEFAULT NULL,
  `database_id` BINARY(16) NULL DEFAULT NULL,
  `host_id` BINARY(16) NULL DEFAULT NULL,
  `ha_ip` VARBINARY(16) NULL DEFAULT NULL,
  `ha_name` VARCHAR(64) NULL DEFAULT '',
  `ha_role` VARCHAR(32) NULL DEFAULT '',  
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- modify/add columns procedure
DROP PROCEDURE IF EXISTS addcol;
DELIMITER $$
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'sensor_properties' AND COLUMN_NAME = 'netflows')
  THEN
        ALTER TABLE `sensor_properties` ADD `ids` TINYINT(1) NOT NULL DEFAULT 0, ADD `passive_inventory` TINYINT(1) NOT NULL DEFAULT 0, ADD `netflows` TINYINT(1) NOT NULL DEFAULT 0;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'system' AND COLUMN_NAME = 'ha_ip')
  THEN
        ALTER TABLE `system` ADD `ha_ip` VARBINARY(16) NULL DEFAULT NULL, ADD `ha_name` VARCHAR(64) NULL DEFAULT '', ADD `ha_role` VARCHAR(32) NULL DEFAULT '';
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'system' AND COLUMN_NAME = 'host_id')
  THEN
        ALTER TABLE `system` ADD `host_id` BINARY(16) NULL DEFAULT NULL AFTER `database_id`;
  END IF;  
END$$

DELIMITER ;
CALL addcol();
DROP PROCEDURE addcol;

-- Migrate from avcenter.current_local to alienvault.system
DELIMITER $$
DROP PROCEDURE IF EXISTS _migrate_currentlocal$$
CREATE PROCEDURE _migrate_currentlocal()
BEGIN
    DECLARE done       INT DEFAULT 0;
    DECLARE _hostname  VARCHAR(64);
    DECLARE _profile   VARCHAR(255);
    DECLARE _admin_ip  VARCHAR(64);
    DECLARE _vpn_ip    VARCHAR(64);
    DECLARE _uuid      VARCHAR(64);
    DECLARE _detectors VARCHAR(255);
    DECLARE _flows     VARCHAR(64);
    DECLARE _ha_ip     VARCHAR(64);
    DECLARE _node_ip   VARCHAR(64);
    DECLARE _ha_role   VARCHAR(64);
    DECLARE _sensor_id VARCHAR(64);

    DECLARE cur0 CURSOR FOR SELECT hostname,profile,admin_ip,uuid,sensor_detectors,netflow FROM avcenter.current_local;
    DECLARE cur1 CURSOR FOR SELECT hostname,profile,admin_ip,vpn_ip,uuid,sensor_detectors,netflow,ha_virtual_ip,ha_local_node_ip,ha_role FROM avcenter.current_local;
    DECLARE cur2 CURSOR FOR SELECT DISTINCT REPLACE(sensor_id,'-','') FROM host_agentless;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'avcenter' AND TABLE_NAME = 'current_local' AND COLUMN_NAME = 'ha_virtual_ip') THEN

        OPEN cur0;
        
        REPEAT
            FETCH cur0 INTO _hostname, _profile, _admin_ip, _uuid, _detectors, _flows;
            IF NOT done THEN
    
                SET @id = REPLACE(_uuid,'-','');

                REPLACE INTO `system` (id,name,admin_ip,profile) VALUES (UNHEX(@id),_hostname,inet6_pton(_admin_ip),_profile);
    
                -- Looking for sensor_id and server_id
                UPDATE `system` SET sensor_id=(SELECT sensor.id FROM sensor WHERE sensor.ip=inet6_pton(_admin_ip)),
                                    server_id=(SELECT server.id FROM server WHERE server.name=_hostname AND (server.ip=inet6_pton(_admin_ip)) LIMIT 1)
                                    WHERE id=UNHEX(@id);
                -- Ids
                IF _detectors REGEXP 'snort|suricata' THEN
                    UPDATE sensor_properties sp, sensor s SET sp.ids=1 WHERE sp.sensor_id=s.id AND (s.ip=inet6_pton(_admin_ip));
                END IF;
                -- Passive inventory
                IF _detectors REGEXP 'prads' THEN
                    UPDATE sensor_properties sp, sensor s SET sp.passive_inventory=1 WHERE sp.sensor_id=s.id AND (s.ip=inet6_pton(_admin_ip));
                END IF;
                -- Netflows
                IF _flows = 'yes' THEN
                    UPDATE sensor_properties sp, sensor s SET sp.netflows=1 WHERE sp.sensor_id=s.id AND (s.ip=inet6_pton(_admin_ip));
                END IF;
                -- Update host_agentless and host_agentless_entries            
                UPDATE IGNORE host_agentless SET sensor_id=(SELECT HEX(sensor.id) FROM sensor WHERE sensor.ip=inet6_pton(_admin_ip)) WHERE UNHEX(sensor_id)=UNHEX(@id);
                UPDATE IGNORE host_agentless_entries SET sensor_id=(SELECT HEX(sensor.id) FROM sensor WHERE sensor.ip=inet6_pton(_admin_ip)) WHERE UNHEX(sensor_id)=UNHEX(@id);
    
            END IF;
        UNTIL done END REPEAT;
    
        CLOSE cur0;

    ELSE

        OPEN cur1;
    
        REPEAT
            FETCH cur1 INTO _hostname, _profile, _admin_ip, _vpn_ip, _uuid, _detectors, _flows, _ha_ip, _node_ip, _ha_role;
            IF NOT done THEN
    
                SET @id = REPLACE(_uuid,'-','');
                
                IF NOT EXISTS (SELECT 1 FROM server_hierarchy WHERE parent_id=UNHEX(@id)) THEN
                    
                    SELECT IF (_ha_ip = 'unconfigured' OR _ha_ip = '','',_ha_role) into @role;
                    SELECT IF (_ha_ip = _admin_ip,_node_ip,_admin_ip) into @admin_ip;
                    
                    REPLACE INTO `system` (id,name,admin_ip,vpn_ip,profile,ha_ip,ha_role) VALUES (UNHEX(@id),_hostname,inet6_pton(@admin_ip),inet6_pton(_vpn_ip),_profile,inet6_pton(_ha_ip),@role);
        
                    -- Looking for sensor_id and server_id
                    UPDATE `system` SET sensor_id=(SELECT sensor.id FROM sensor WHERE sensor.ip=inet6_pton(_admin_ip) OR sensor.ip=inet6_pton(_vpn_ip)),
                                        server_id=(SELECT server.id FROM server WHERE server.name=_hostname AND (server.ip=inet6_pton(_admin_ip) OR server.ip=inet6_pton(_vpn_ip)) LIMIT 1)
                                        WHERE id=UNHEX(@id);
                    -- Ids
                    IF _detectors REGEXP 'snort|suricata' THEN
                        UPDATE sensor_properties sp, sensor s SET sp.ids=1 WHERE sp.sensor_id=s.id AND (s.ip=inet6_pton(_admin_ip) OR s.ip=inet6_pton(_vpn_ip));
                    END IF;
                    -- Passive inventory
                    IF _detectors REGEXP 'prads' THEN
                        UPDATE sensor_properties sp, sensor s SET sp.passive_inventory=1 WHERE sp.sensor_id=s.id AND (s.ip=inet6_pton(_admin_ip) OR s.ip=inet6_pton(_vpn_ip));
                    END IF;
                    -- Netflows
                    IF _flows = 'yes' THEN
                        UPDATE sensor_properties sp, sensor s SET sp.netflows=1 WHERE sp.sensor_id=s.id AND (s.ip=inet6_pton(_admin_ip) OR s.ip=inet6_pton(_vpn_ip));
                    END IF;
                    -- Update host_agentless and host_agentless_entries            
                    UPDATE IGNORE host_agentless SET sensor_id=(SELECT HEX(sensor.id) FROM sensor WHERE sensor.ip=inet6_pton(_admin_ip) OR sensor.ip=inet6_pton(_vpn_ip)) WHERE UNHEX(sensor_id)=UNHEX(@id);
                    UPDATE IGNORE host_agentless_entries SET sensor_id=(SELECT HEX(sensor.id) FROM sensor WHERE sensor.ip=inet6_pton(_admin_ip) OR sensor.ip=inet6_pton(_vpn_ip)) WHERE UNHEX(sensor_id)=UNHEX(@id);
                    
                END IF;
    
            END IF;
        UNTIL done END REPEAT;
    
        CLOSE cur1;

    END IF;

    -- Update host_agentless and host_agentless_entries
    SET done = 0;
    OPEN cur2;

    REPEAT
        FETCH cur2 INTO _sensor_id;
        IF NOT done THEN

            SELECT HEX(sensor_id) FROM system WHERE id=UNHEX(_sensor_id) into @sid;
            UPDATE IGNORE host_agentless SET sensor_id=IF(@sid='',sensor_id,@sid) WHERE REPLACE(sensor_id,'-','')=_sensor_id;
            UPDATE IGNORE host_agentless_entries SET sensor_id=IF(@sid='',sensor_id,@sid) WHERE REPLACE(sensor_id,'-','')=_sensor_id;

        END IF;
    UNTIL done END REPEAT;

    CLOSE cur2;
    
    DELETE FROM host_agentless WHERE sensor_id LIKE '%-%';
    DELETE FROM host_agentless_entries WHERE sensor_id LIKE '%-%';
            
END$$

DELIMITER ;

CALL _migrate_currentlocal;
DROP PROCEDURE IF EXISTS _migrate_currentlocal;

-- DROP DATABASE avcenter;

DELIMITER $$
-- Sensor update procedure
-- CALL sensor_update ('admin','','192.168.200.5','test',0,40001,0,'','','4.5.0',0,1,1,0,0,0,0);
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
    IF INET6_PTON(ip) IS NOT NULL AND NOT user = '' THEN
    
        -- check params
        SELECT IF (uuid='',UPPER(REPLACE(UUID(), '-', '')),UPPER(uuid)) into @uuid;
        SET @ip = HEX(INET6_PTON(ip));
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
        IF NOT EXISTS(SELECT 1 FROM sensor WHERE id != UNHEX(@uuid)) THEN

            CALL _orphans_of_sensor(@uuid);
            
        END IF;

        -- Default nessus host
        IF @nessus_host = '' THEN
            REPLACE INTO alienvault.config VALUES ('nessus_host',INET6_NTOP(UNHEX(@ip)));
            REPLACE INTO alienvault.config VALUES ('nessus_pass',AES_ENCRYPT('ossim',@system_uuid));
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
    -- Risk maps indicators
    IF NOT EXISTS(SELECT 1 FROM bp_asset_member WHERE member = UNHEX(@uuid)) THEN
        INSERT IGNORE INTO bp_asset_member (member,type) VALUES (UNHEX(@uuid),'host');
    END IF;
    UPDATE risk_indicators SET type_name = @uuid WHERE `type`='sensor' AND length(type_name) != 32;
    -- OCS
    IF NOT EXISTS(SELECT 1 FROM task_inventory WHERE task_sensor = UNHEX(@uuid) AND task_name='default_ocs') THEN
        INSERT IGNORE INTO task_inventory (task_type,task_period,task_enable,task_sensor,task_name) VALUES (3,1800,1,UNHEX(@uuid),'default_ocs');
    END IF;
    -- Host/Networks without ctx
    UPDATE net SET ctx=(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id') WHERE ctx=UNHEX('00000000000000000000000000000000');
    UPDATE host SET ctx=(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id') WHERE ctx=UNHEX('00000000000000000000000000000000');
END$$

-- System delete procedure
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

        DELETE FROM acl_sensors WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM sensor_properties WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM sensor_stats WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM location_sensor_reference WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM vuln_job_schedule WHERE email = @sensor_id;
        DELETE FROM vuln_nessus_servers WHERE hostname = @sensor_id;
        DELETE FROM host_sensor_reference WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM net_sensor_reference WHERE sensor_id = UNHEX(@sensor_id);
        UPDATE policy, policy_sensor_reference SET policy.active=0 WHERE policy.id=policy_sensor_reference.policy_id AND policy_sensor_reference.sensor_id = UNHEX(@sensor_id);
        DELETE FROM policy_sensor_reference WHERE sensor_id = UNHEX(@sensor_id);
        DELETE FROM sensor WHERE id = UNHEX(@sensor_id);

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

-- Clone Taxonomy for all engines
DROP PROCEDURE IF EXISTS alarm_taxonomy_populate$$
CREATE PROCEDURE alarm_taxonomy_populate()
BEGIN
  DECLARE done        INT DEFAULT 0;
  DECLARE engine_uuid VARCHAR(255);

  DECLARE cur1 CURSOR FOR select hex(id) from acl_entities where entity_type='engine';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  OPEN cur1;

  REPEAT
    FETCH cur1 INTO engine_uuid;
    IF NOT done THEN
        SET @engine = unhex(engine_uuid);
        REPLACE INTO alarm_taxonomy SELECT sid,@engine,kingdom,category,subcategory FROM alarm_taxonomy WHERE engine_id=unhex('00000000000000000000000000000000');
    END IF;
  UNTIL done END REPEAT;

  CLOSE cur1;
END$$
DELIMITER ;

-- New suggestion
REPLACE INTO `alienvault_api`.`status_action` (`action_id`,`is_admin`,`content`,`link`) VALUES
(1040,true,'Confirm if the remote system is up and reachable',''),
(1041,true,'Use the following form to configure the remote system: <<Authenticate>> the remote system','AV_PATH/av_center/data/sections/main/add_system.php?id=ASSET_ID');

REPLACE INTO `alienvault_api`.`status_message` (`id`,`level`,`description`,`content`) VALUES
(9,'error','The remote system is not connected to the AlienVault API','The remote system is not connected to the AlienVault API. The remote system is unreachable or it has not been configured properly. At TIMESTAMP');

REPLACE INTO `alienvault_api`.`status_message_action` (`message_id`,`action_id`) VALUES (9,1040), (9,1041);

-- Software cpe links
REPLACE INTO `software_cpe_links` VALUES (10,'Checkpoint','Firewall-1','Device Integration Checkpoint FW-1.pdf');

DELETE FROM `software_cpe` WHERE vendor='McAffe';
REPLACE INTO `software_cpe` (`cpe`, `name`, `version`, `line`, `vendor`, `plugin`) VALUES 
('cpe:/h:f5:firepass_1000','FirePass 1000 SSL VPN','','F5 FirePass 1000 SSL VPN','F5','f5-firepass:1674'),
('cpe:/h:f5:firepass_1000:5.5','FirePass 1000 SSL VPN','5.5','F5 FirePass 1000 SSL VPN 5.5','F5','f5-firepass:1674'),
('cpe:/a:mcafee:antivirus_engine:-','Antivirus','-','Mcafee Antivirus -','Mcafee','mcafee:1571'),
('cpe:/a:mcafee:antispam:-','Antispam','-','Mcafee Antispam -','Mcafee','mcafee-antispam:1618');

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2014-06-03');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.8.0');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
