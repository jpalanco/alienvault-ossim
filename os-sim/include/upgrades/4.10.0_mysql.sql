USE alienvault_api;
ALTER TABLE status_message CHANGE `level` `level` enum('info','error','warning','notification');
REPLACE INTO `alienvault_api`.`status_action` (`action_id`,`is_admin`,`content`,`link`) VALUES (1050,true,'<<Update>> the system','AV_PATH/av_center/index.php?m_opt=configuration&sm_opt=deployment&h_opt=components');
REPLACE INTO `alienvault_api`.`status_message` (`id`,`level`,`description`,`content`) VALUES (10,'notification','New Updates Available','New system updates pending. At TIMESTAMP'),(11,'notification','Sensor connection lost','Can not connect to the sensor. At TIMESTAMP');
REPLACE INTO `alienvault_api`.`status_message_action` (`message_id`,`action_id`) VALUES (10,1050),(11,1003),(11,1040),(11,1041);

USE alienvault;
SET AUTOCOMMIT=0;

DELIMITER $$

-- server_delete_parent for remote systems
DROP PROCEDURE IF EXISTS server_delete_parent$$
CREATE PROCEDURE server_delete_parent( 
    server_id VARCHAR(36)
    )
BEGIN
    SELECT REPLACE(server_id,'-','') into @server_id;

    DELETE FROM server_hierarchy WHERE parent_id = UNHEX(@server_id);
    DELETE FROM server_forward_role WHERE server_dst_id = UNHEX(@server_id);
    DELETE FROM server_role WHERE server_id = UNHEX(@server_id);
    DELETE FROM server WHERE id = UNHEX(@server_id);
    
END$$

-- system_update
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

            SELECT HEX(sensor_id),HEX(server_id),inet6_ntoa(admin_ip),inet6_ntoa(vpn_ip) FROM alienvault.system WHERE id=UNHEX(@system_id) into @sensor_id, @server_id, @admin_ip, @vpn_ip;
            
            UPDATE server SET ip=inet6_aton(@admin_ip) WHERE id=UNHEX(@server_id);

            UPDATE sensor SET ip=IFNULL(inet6_aton(@vpn_ip),inet6_aton(@admin_ip)) WHERE id=UNHEX(@sensor_id);

        END IF;
        
        SELECT CONCAT('System ',_system_id,' updated') as status;
        
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
        INSERT IGNORE INTO task_inventory (task_type,task_period,task_enable,task_sensor,task_name) VALUES (3,3600,1,UNHEX(@uuid),'default_ocs');
    END IF;
    -- Host/Networks without ctx
    UPDATE net SET ctx=(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id') WHERE ctx=UNHEX('00000000000000000000000000000000');
    UPDATE host SET ctx=(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id') WHERE ctx=UNHEX('00000000000000000000000000000000');
END$$

-- Triggers
DROP TRIGGER IF EXISTS nsr_INS$$
DROP TRIGGER IF EXISTS nsr_DEL$$
DROP TRIGGER IF EXISTS hsr_INS$$
DROP TRIGGER IF EXISTS hsr_DEL$$
DROP TRIGGER IF EXISTS ncidrs_INS$$
DROP TRIGGER IF EXISTS net_DEL$$

CREATE TRIGGER `nsr_INS` AFTER INSERT ON `net_sensor_reference` FOR EACH ROW
BEGIN
    IF @disable_calc_perms IS NULL THEN
        CALL update_users_affected_by_sensors(NEW.sensor_id);
    END IF;
END$$

CREATE TRIGGER `nsr_DEL` AFTER DELETE ON `net_sensor_reference` FOR EACH ROW
BEGIN
    IF @disable_calc_perms IS NULL THEN
        CALL update_users_affected_by_sensors(OLD.sensor_id);
    END IF;
END$$

CREATE TRIGGER `hsr_INS` AFTER INSERT ON `host_sensor_reference` FOR EACH ROW
BEGIN
    IF @disable_calc_perms IS NULL THEN
        CALL update_users_affected_by_sensors(NEW.sensor_id);
    END IF;
END$$

CREATE TRIGGER `hsr_DEL` AFTER DELETE ON `host_sensor_reference` FOR EACH ROW
BEGIN
    IF @disable_calc_perms IS NULL THEN
        CALL update_users_affected_by_sensors(OLD.sensor_id);
    END IF;
END$$

CREATE TRIGGER `ncidrs_INS` AFTER INSERT ON `net_cidrs` FOR EACH ROW
BEGIN
    IF @disable_calc_perms IS NULL THEN
        CALL update_users_affected_by_networks();
    END IF;
END$$

CREATE TRIGGER `net_DEL` AFTER DELETE ON `net` FOR EACH ROW
BEGIN
    IF @disable_calc_perms IS NULL THEN
        CALL update_users_affected_by_networks();
    END IF;
END$$

-- Update all users
DROP PROCEDURE IF EXISTS update_all_users$$
CREATE PROCEDURE update_all_users()
BEGIN
    DECLARE done  INT DEFAULT 0;
    DECLARE user  VARCHAR(64);

    DECLARE cur1 CURSOR FOR SELECT DISTINCT login FROM users WHERE enabled=1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur1;

    REPEAT
        FETCH cur1 INTO user;
        IF NOT done THEN
            CALL acl_user_permissions(user);
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur1;
END$$

-- Mr Proper
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
    IF NOT EXISTS (SELECT 1 FROM host LEFT JOIN user_host_perm ON user_host_perm.asset_id=host.id AND user_host_perm.login=user, user_ctx_perm WHERE user_ctx_perm.ctx=host.ctx AND user_host_perm.asset_id IS NULL) THEN
        DELETE FROM user_host_perm WHERE login = user;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM net LEFT JOIN user_net_perm ON user_net_perm.asset_id=net.id AND user_net_perm.login=user, user_ctx_perm WHERE user_ctx_perm.ctx=net.ctx AND user_net_perm.asset_id IS NULL) THEN
        DELETE FROM user_net_perm WHERE login = user;
    END IF;

END$$

-- modify/add columns procedure
DROP PROCEDURE IF EXISTS addcol$$
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'host_sensor_reference' AND INDEX_NAME='sensor')
  THEN
      ALTER TABLE `host_sensor_reference` ADD INDEX `sensor` (  `sensor_id` );
  END IF; 
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'net_sensor_reference' AND INDEX_NAME='sensor')
  THEN
      ALTER TABLE `net_sensor_reference` ADD INDEX `sensor` (  `sensor_id` );
  END IF;   
END$$

DELIMITER ;
CALL addcol();
DROP PROCEDURE addcol;


-- Remove duplicated servers
SELECT value FROM config WHERE conf='server_id' into @server_id;
SELECT inet6_ntoa(ip) FROM server WHERE id=UNHEX(REPLACE(@server_id,'-','')) into @server_ip;
DELETE FROM server WHERE ip=inet6_aton(@server_ip) AND id != UNHEX(REPLACE(@server_id,'-',''));

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2014-08-03');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.10.0');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA

