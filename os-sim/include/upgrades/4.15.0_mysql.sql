USE alienvault;
SET AUTOCOMMIT=0;

DROP TABLE IF EXISTS protocol;

DELIMITER $$

#Update PCI 2.0 rule ID
DROP PROCEDURE IF EXISTS _remove_wrong_pci_rule$$
CREATE PROCEDURE _remove_wrong_pci_rule()
BEGIN
    IF EXISTS (SELECT * FROM PCI.R01_FW_Config WHERE x1='R' AND x2='1' AND x3='1' AND x4='7')
    THEN
        DELETE FROM PCI.R01_FW_Config WHERE x1='R' AND x2='1' AND x3='1' AND x4='.7';
    ELSE
       UPDATE PCI.R01_FW_Config set x4='7' WHERE x1='R' AND x2='1' AND x3='1' AND x4='.7';
    END IF;
END$$

CALL _remove_wrong_pci_rule()$$
DROP PROCEDURE _remove_wrong_pci_rule$$


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

DROP TRIGGER IF EXISTS host_services_RENAME$$
CREATE TRIGGER `host_services_RENAME` BEFORE INSERT ON `host_services` FOR EACH ROW
BEGIN
    IF NEW.port=40001 AND NEW.service='unknown' THEN
        SET NEW.service = IF(is_pro(),'usm server','ossim server');
    ELSEIF NEW.port=1241 AND NEW.service='unknown' THEN
        SET NEW.service = 'nessus';
    ELSEIF NEW.port=9390 AND (NEW.service='unknown' OR NEW.service='OpenVAS' OR NEW.service='unknow-ssl') THEN
        SET NEW.service = 'openvasmd';
    ELSEIF NEW.port=9391 AND (NEW.service='unknown' OR NEW.service='OpenVAS' OR NEW.service='openvas-ssl') THEN
        SET NEW.service = 'openvassd';
    END IF;
    
    IF NEW.service='ossim server' THEN
        SET NEW.version = 'Open Source Security Information Management server';
    ELSEIF NEW.service='usm server' THEN
        SET NEW.version = 'Unified Security Management server';
    ELSEIF NEW.service='openvasmd' THEN
        SET NEW.version = 'OpenVAS manager';
    ELSEIF NEW.service='openvassd' THEN
        SET NEW.version = 'OpenVAS server';
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

DROP PROCEDURE IF EXISTS _acl_fill_subnets$$
CREATE PROCEDURE _acl_fill_subnets( user VARCHAR(64) )
BEGIN
    IF EXISTS (SELECT 1 FROM net LEFT JOIN user_net_perm ON user_net_perm.asset_id=net.id AND user_net_perm.login=user, user_ctx_perm, net_sensor_reference, sensor WHERE net.id=net_sensor_reference.net_id AND net_sensor_reference.sensor_id=sensor.id AND user_ctx_perm.ctx=net.ctx AND user_ctx_perm.login=user AND user_net_perm.asset_id IS NULL) THEN
        CREATE TEMPORARY TABLE IF NOT EXISTS tmpnet (PRIMARY KEY(asset_id)) AS SELECT asset_id from user_net_perm where login = user;
        CREATE TEMPORARY TABLE IF NOT EXISTS tmpnet1 (PRIMARY KEY(begin,end)) AS SELECT begin,end,net_id from net_cidrs;
    
        REPLACE INTO user_net_perm SELECT DISTINCT user, n1.net_id FROM tmpnet1 n1, tmpnet t INNER JOIN net_cidrs n ON t.asset_id = n.net_id WHERE n.net_id!=n1.net_id AND  n1.begin >= n.begin AND n1.end <= n.end;
    
        DROP TEMPORARY TABLE IF EXISTS tmpnet;
        DROP TEMPORARY TABLE IF EXISTS tmpnet1;
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

DELIMITER ;
    
REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2015-01-13');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.15.0');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
