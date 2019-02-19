USE alienvault_api;

-- Warning & Errors: Actions
REPLACE INTO `alienvault_api`.`status_action` (`action_id`,`is_admin`,`content`,`link`) VALUES
(1060, true, '<<Update>> the plugins feed','AV_PATH/av_center/index.php?m_opt=configuration&sm_opt=deployment&h_opt=components'),
(1070, true, 'Please contact AlienVault Support if you did not make this change', ''),
(1071, true, 'Please contact AlienVault Support if you did not make this change', ''),
(1072, true, 'Please contact AlienVault Support if you did not make this change', ''),
(1073, true, 'Please contact AlienVault Support if you did not make this change', '');


-- Warning & Errors: Messages
REPLACE INTO `alienvault_api`.`status_message` (`id`,`level`,`description`,`content`) VALUES
(12, 'info',    'Plugins version out of date', 'There is a new version of the plugins feed. At TIMESTAMP'),
(13, 'warning', 'A change has been detected in the configuration files of one or more plugins', 'The AlienVault Sensor detected that the configuration files of one or more plugins have been modified. This could cause a system malfunction when collecting data from your environment.\n\nAny local changes will be overwritten in the next product update.'),
(14, 'warning', 'One or more plugin configuration files have been deleted', 'The AlienVault Sensor detected that the configuration files of one or more plugins have been manually deleted. This could cause a system malfunction when collecting data from your environment.\n\nAny local changes will be overwritten in the next product update.'),
(15, 'warning', 'One or more changes have been detected in the syslog processor configuration files', 'The syslog processor configuration files have been manually changed. This could cause a system malfunction when collecting data from your environment.\n\nAny local changes will be overwritten in the next product update.'),
(16, 'warning', 'Syslog processor configuration files have been deleted', 'The syslog processor configuration files have been manually deleted. This could cause a system malfunction when collecting data from your environment.\n\nAny local changes will be overwritten in the next product update.');

-- Warning & Errors: Actions for messages
REPLACE INTO `alienvault_api`.`status_message_action` (`message_id`,`action_id`) VALUES
(12, 1060),
(13, 1070),
(14, 1071),
(15, 1072),
(16, 1073);


USE alienvault;
SET AUTOCOMMIT=0;

DELIMITER $$

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
        DELETE aux FROM bp_member_status aux LEFT JOIN tmpdel h ON h.id=aux.member_id WHERE h.id IS NOT NULL;
        DELETE aux FROM bp_asset_member aux LEFT JOIN tmpdel h ON h.id=aux.member WHERE aux.type='host' AND h.id IS NOT NULL;
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
        DELETE aux FROM bp_member_status aux LEFT JOIN tmpdel n ON n.id=aux.member_id WHERE n.id IS NOT NULL;
        DELETE aux FROM bp_asset_member aux LEFT JOIN tmpdel n ON n.id=aux.member WHERE aux.type='net' AND n.id IS NOT NULL;
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

DELIMITER ;

-- Risk Maps: Romania flag changed, Columbus server moved, England changed to UK.
UPDATE risk_indicators SET icon = 'pixmaps/uploaded/logis111.jpg' WHERE id = 59 AND name = 'Romania';
UPDATE risk_indicators SET x = 161, y = 231 WHERE id = 121 AND name = 'Columbus';
UPDATE risk_indicators SET name = 'United Kingdom' WHERE id = 52 AND name = 'England';

ALTER TABLE users CHANGE `email` `email` VARCHAR(255) NOT NULL;
ALTER TABLE dashboard_tab_config CHANGE `user` `user` VARCHAR(64) NOT NULL;
ALTER TABLE dashboard_widget_config CHANGE `user` `user` VARCHAR(64) NOT NULL;
ALTER TABLE dashboard_tab_options CHANGE `user` `user` VARCHAR(64) NOT NULL;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2015-02-03');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.15.1');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
