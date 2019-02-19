USE alienvault;
SET AUTOCOMMIT=0;

REPLACE INTO `software_cpe` (`cpe`, `name`, `version`, `line`, `vendor`, `plugin`) VALUES ('cpe:/a:snort:snort','Snort','','Snort Snort','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.6','Snort','1.6','Snort Snort 1.6','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.8.0','Snort','1.8.0','Snort Snort 1.8.0','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.8.1','Snort','1.8.1','Snort Snort 1.8.1','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.8.2','Snort','1.8.2','Snort Snort 1.8.2','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.8.3','Snort','1.8.3','Snort Snort 1.8.3','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.8.4','Snort','1.8.4','Snort Snort 1.8.4','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.8.5','Snort','1.8.5','Snort Snort 1.8.5','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.8.6','Snort','1.8.6','Snort Snort 1.8.6','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.8.7','Snort','1.8.7','Snort Snort 1.8.7','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.9.0','Snort','1.9.0','Snort Snort 1.9.0','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:1.9.1','Snort','1.9.1','Snort Snort 1.9.1','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:2.0:beta','Snort  Beta','2.0','Snort Snort 2.0 Beta','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:2.0:rc1','Snort  RC1','2.0','Snort Snort 2.0 RC1','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:2.6.1','Snort','2.6.1','Snort Snort 2.6.1','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:2.6.1.1','Snort','2.6.1.1','Snort Snort 2.6.1.1','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:2.6.1.2','Snort','2.6.1.2','Snort Snort 2.6.1.2','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:2.6.2','Snort','2.6.2','Snort Snort 2.6.2','Snort','snort_syslog:1001'),('cpe:/a:snort:snort:2.7_beta1','Snort 2.7 Beta1','2.7_beta1','Snort Snort 2.7 Beta1','Snort','snort_syslog:1001');

-- Risk maps USA fixes
UPDATE risk_indicators SET name = 'Minnesota' WHERE name = 'Minesota' AND id = 27;
UPDATE risk_indicators SET name = 'Massachusetts' WHERE name = 'Massachusets' AND id = 48;
UPDATE risk_indicators SET name = 'Tennessee' WHERE name = 'Tennesse' AND id = 37;
UPDATE risk_indicators SET x = 683, y = 362 WHERE name = 'Tennessee' AND id = 37;
UPDATE risk_indicators SET x = 699, y = 426 WHERE name = 'Alabama' AND id = 34;
UPDATE risk_indicators SET x = 726, y = 309 WHERE name = 'Kentucky' AND id = 40;
UPDATE risk_indicators SET x = 735, y = 229 WHERE name = 'Ohio' AND id = 38;
UPDATE risk_indicators SET x = 698, y = 126 WHERE name = 'Michigan' AND id = 39;
UPDATE risk_indicators SET x = 821, y = 264 WHERE name = 'Virginia' AND id = 43;
UPDATE risk_indicators SET x = 635, y = 431 WHERE name = 'Mississippi' AND id = 35;
-- New indicator for State of Indiana
INSERT INTO risk_indicators (name, map, url, type, type_name, icon, x, y, w, h, size) VALUES ('Indiana', 0x00000000000000000000000000000002, '', 'sensor', 'ossim', 'pixmaps/uploaded/logis16.jpg', 681, 238, 90, 60, 0);
-- Set the correct sensor ID in type_name field
UPDATE risk_indicators r1, risk_indicators r2 SET r1.type_name = r2.type_name WHERE r1.name = 'Indiana' AND r2.name = 'Kentucky';

DELIMITER $$

DROP PROCEDURE IF EXISTS _acl_fill_subnets$$
CREATE PROCEDURE _acl_fill_subnets( user VARCHAR(64) )
BEGIN
    IF NOT EXISTS (SELECT 1 FROM net LEFT JOIN user_net_perm ON user_net_perm.asset_id=net.id AND user_net_perm.login=user, user_ctx_perm, net_sensor_reference, sensor WHERE net.id=net_sensor_reference.net_id AND net_sensor_reference.sensor_id=sensor.id AND user_ctx_perm.ctx=net.ctx AND user_ctx_perm.login=user AND user_net_perm.asset_id IS NULL) THEN
        DELETE FROM user_net_perm WHERE login = user;
    END IF;

    CREATE TEMPORARY TABLE IF NOT EXISTS tmpnet (PRIMARY KEY(asset_id)) AS SELECT asset_id from user_net_perm where login = user;
    CREATE TEMPORARY TABLE IF NOT EXISTS tmpnet1 (PRIMARY KEY(asset_id)) AS SELECT asset_id from user_net_perm where login = user;

    REPLACE INTO user_net_perm SELECT DISTINCT user, n1.net_id FROM net ne LEFT JOIN tmpnet t ON t.asset_id=ne.id, net_cidrs n, net ne1 LEFT JOIN tmpnet1 t1 ON t1.asset_id=ne1.id, net_cidrs n1 WHERE ne.id = n.net_id AND ne1.id = n1.net_id AND n.begin <= n1.begin AND n1.end <= n.end AND ne.ctx = ne1.ctx AND t.asset_id IS NOT NULL AND t1.asset_id IS NULL;

    DROP TEMPORARY TABLE IF EXISTS tmpnet;
    DROP TEMPORARY TABLE IF EXISTS tmpnet1;
END$$

DROP PROCEDURE IF EXISTS _acl_fill_context_assets$$
CREATE PROCEDURE _acl_fill_context_assets( user VARCHAR(64) )
BEGIN

    REPLACE INTO user_host_perm SELECT DISTINCT user, asset_id FROM acl_entities_assets, acl_entities_users, acl_entities, host WHERE host.id=acl_entities_assets.asset_id AND acl_entities.id = acl_entities_assets.entity_id AND acl_entities.id=acl_entities_users.entity_id AND acl_entities.entity_type='logical' AND acl_entities_users.login = user;

    REPLACE INTO user_net_perm SELECT DISTINCT user, asset_id FROM acl_entities_assets, acl_entities_users, acl_entities, net WHERE net.id=acl_entities_assets.asset_id AND acl_entities.id = acl_entities_assets.entity_id AND acl_entities.id=acl_entities_users.entity_id AND acl_entities.entity_type='logical' AND acl_entities_users.login = user;

END$$

DROP PROCEDURE IF EXISTS _acl_fill_context_sensors$$
CREATE PROCEDURE _acl_fill_context_sensors( user VARCHAR(64) )
BEGIN

    REPLACE INTO user_sensor_perm SELECT DISTINCT user, sensor_id FROM acl_entities, acl_sensors, acl_entities_users, sensor WHERE acl_sensors.entity_id = acl_entities.id AND sensor.id = acl_sensors.sensor_id AND acl_entities.id=acl_entities_users.entity_id AND acl_entities.entity_type!='engine' AND acl_entities_users.login = user;

    SELECT EXISTS (SELECT 1 from user_host_perm where login = user) INTO @hosts;
    SELECT EXISTS (SELECT 1 from user_net_perm where login = user) INTO @networks;

    IF @hosts OR @networks THEN
        REPLACE INTO user_host_perm SELECT DISTINCT user, host_id FROM acl_entities, acl_sensors, acl_entities_users, sensor, host, host_sensor_reference WHERE host_sensor_reference.host_id=host.id AND acl_sensors.entity_id = acl_entities.id AND sensor.id = acl_sensors.sensor_id AND host_sensor_reference.sensor_id=acl_sensors.sensor_id AND acl_entities.id=acl_entities_users.entity_id AND acl_entities.entity_type!='engine' AND acl_entities_users.login = user;
        REPLACE INTO user_net_perm SELECT DISTINCT user, net_id FROM acl_entities, acl_sensors, acl_entities_users, sensor, net, net_sensor_reference WHERE net_sensor_reference.net_id=net.id AND acl_sensors.entity_id = acl_entities.id AND sensor.id = acl_sensors.sensor_id AND net_sensor_reference.sensor_id=acl_sensors.sensor_id AND acl_entities.id=acl_entities_users.entity_id AND acl_entities.entity_type!='engine' AND acl_entities_users.login = user;
    END IF;

END$$

DROP PROCEDURE IF EXISTS _acl_fill_assets$$
CREATE PROCEDURE _acl_fill_assets( user VARCHAR(64) )
BEGIN

    REPLACE INTO user_host_perm SELECT DISTINCT user, asset_id FROM acl_assets, host WHERE id = asset_id AND login = user;

    REPLACE INTO user_net_perm SELECT DISTINCT user, asset_id FROM acl_assets, net WHERE id = asset_id AND login = user;

END$$

DROP PROCEDURE IF EXISTS _acl_fill_sensors$$
CREATE PROCEDURE _acl_fill_sensors( user VARCHAR(64) )
BEGIN

    REPLACE INTO user_sensor_perm SELECT DISTINCT user, sensor.id FROM sensor, acl_login_sensors WHERE sensor.id = acl_login_sensors.sensor_id AND acl_login_sensors.login = user;

    REPLACE INTO user_host_perm SELECT DISTINCT user, host_sensor_reference.host_id FROM sensor, host_sensor_reference, acl_login_sensors WHERE sensor.id = acl_login_sensors.sensor_id AND sensor.id = host_sensor_reference.sensor_id AND acl_login_sensors.login = user;

    REPLACE INTO user_net_perm SELECT DISTINCT user, net_sensor_reference.net_id FROM sensor, net_sensor_reference, acl_login_sensors WHERE sensor.id = acl_login_sensors.sensor_id AND sensor.id = net_sensor_reference.sensor_id AND acl_login_sensors.login = user;

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
    IF NOT EXISTS (SELECT 1 FROM host LEFT JOIN user_host_perm ON user_host_perm.asset_id=host.id AND user_host_perm.login=user, user_ctx_perm, host_sensor_reference, sensor WHERE host.id=host_sensor_reference.host_id AND host_sensor_reference.sensor_id=sensor.id AND user_ctx_perm.ctx=host.ctx AND user_ctx_perm.login=user AND user_host_perm.asset_id IS NULL) THEN
        DELETE FROM user_host_perm WHERE login = user;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM net LEFT JOIN user_net_perm ON user_net_perm.asset_id=net.id AND user_net_perm.login=user, user_ctx_perm, net_sensor_reference, sensor WHERE net.id=net_sensor_reference.net_id AND net_sensor_reference.sensor_id=sensor.id AND user_ctx_perm.ctx=net.ctx AND user_ctx_perm.login=user AND user_net_perm.asset_id IS NULL) THEN
        DELETE FROM user_net_perm WHERE login = user;
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
        IF NOT EXISTS(SELECT 1 FROM host h, host_ip hi, host_sensor_reference r, sensor s WHERE h.id=r.host_id AND hi.host_id=h.id AND hi.ip=s.ip AND r.sensor_id=s.id AND s.id=UNHEX(@uuid)) THEN
            SELECT REPLACE(UUID(), '-', '') into @asset_id;
            INSERT IGNORE INTO alienvault.host (id, ctx, hostname, asset, threshold_c, threshold_a, alert, persistence, nat, rrd_profile, descr, lat, lon, av_component) VALUES (UNHEX(@asset_id), UNHEX(@range), @name, '2', '30', '30', '0', '0', '', '', '', '0', '0', '1');
            INSERT IGNORE INTO alienvault.host_ip (host_id,ip) VALUES (UNHEX(@asset_id), UNHEX(@ip));
            INSERT IGNORE INTO alienvault.host_sensor_reference (host_id,sensor_id) VALUES (UNHEX(@asset_id), UNHEX(@uuid));
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
    -- Fix Host net references
    REPLACE INTO alienvault.host_net_reference SELECT host.id,net_id FROM alienvault.host, alienvault.host_ip, alienvault.net_cidrs WHERE host.id = host_ip.host_id AND host_ip.ip >= net_cidrs.begin AND host_ip.ip <= net_cidrs.end;
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

DROP TRIGGER IF EXISTS `auto_incidents`$$
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

DROP PROCEDURE IF EXISTS `incident_ticket_populate`$$
CREATE PROCEDURE `incident_ticket_populate` (incident_id INT, src_ip VARBINARY(16), dst_ip VARBINARY(16), prio INT)
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

DROP PROCEDURE IF EXISTS addcol$$
CREATE PROCEDURE addcol() BEGIN
    IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_job_schedule' AND COLUMN_NAME = 'begin')
    THEN
      ALTER TABLE `vuln_job_schedule` ADD `begin` VARCHAR(8) NOT NULL DEFAULT '';
    END IF;
    
    -- Fix default policy
    UPDATE policy_group SET name='AV default policies' WHERE name='AV Default policies';
    UPDATE policy_group SET name='Default policy group',descr='Default group policy objects' WHERE name='Default Policy Group';
    
    UPDATE policy_host_reference SET host_id = 0x00000000000000000000000000000000 WHERE host_id = 0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF;
    SELECT UNHEX(REPLACE(value, '-', '')) FROM config WHERE conf = 'server_id' into @server_id;

    SELECT id FROM policy WHERE descr like 'AVAPI filter' into @dpid;
    IF NOT (@dpid IS NULL OR @dpid = '') THEN
        IF NOT EXISTS (SELECT 1 FROM policy_host_reference WHERE policy_id=@dpid) THEN
            INSERT IGNORE INTO policy_host_reference (`policy_id`, `host_id`, `direction`) VALUES (@dpid, 0x00000000000000000000000000000000,'source'),(@dpid, 0x00000000000000000000000000000000,'dest');
        ELSE
            IF EXISTS (SELECT count(*) as total FROM policy_host_reference WHERE policy_id=@dpid HAVING total<=2) THEN
                UPDATE policy_host_reference SET host_id = 0x00000000000000000000000000000000 WHERE policy_id = @dpid;
            END IF;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM policy_target_reference WHERE policy_id=@dpid) THEN
            INSERT IGNORE INTO policy_target_reference (policy_id, target_id) VALUES (@dpid, IF(@server_id IS NULL or @server_id = '', 0x00000000000000000000000000000000, @server_id));
        ELSE
            UPDATE policy_target_reference SET target_id = IF(@server_id IS NULL or @server_id = '', 0x00000000000000000000000000000000, @server_id) WHERE policy_id = @dpid;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM policy_time_reference WHERE policy_id=@dpid) THEN
            INSERT IGNORE INTO policy_time_reference (`policy_id`, `minute_start`, `minute_end`, `hour_start`, `hour_end`, `week_day_start`, `week_day_end`, `month_day_start`, `month_day_end`, `month_start`, `month_end`, `timezone`) VALUES (@dpid,0,59,0,23,0,0,0,0,0,0,'US/Eastern');
        END IF;
    END IF;
    
END$$

DELIMITER ;

CALL addcol();
DROP PROCEDURE addcol;

ALTER TABLE alarm CHANGE `stats` `stats` MEDIUMTEXT NOT NULL;

ALTER TABLE datawarehouse.report_data CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE datawarehouse.report_data CHANGE `user` `user` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
CHANGE `dataV1` `dataV1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `dataV2` `dataV2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `dataV3` `dataV3` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `dataV4` `dataV4` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `dataV5` `dataV5` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `dataV6` `dataV6` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `dataV7` `dataV7` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `dataV8` `dataV8` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `dataV9` `dataV9` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `dataV10` `dataV10` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
CHANGE `dataV11` `dataV11` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `custom_report_scheduler` CHANGE `id_report` `id_report` VARCHAR( 512 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `custom_report_scheduler` CHANGE `name_report` `name_report` VARCHAR( 512 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

REPLACE INTO `alienvault`.`user_config` (`login`, `category`, `name`, `value`) VALUES 
('admin','custom_report','PCI DSS 3.0: Access Control Device Denied','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2027;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:41:\"PCI DSS 3.0: Access Control Device Denied\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 14:15:10\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Admin Access to Systems','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2018;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:36:\"PCI DSS 3.0: Admin Access to Systems\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:23:23\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: All Antivirus Security Risk Events','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 5\";s:8:\"comments\";s:464:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 5: Use and regularly update &lt;span id=&quot;be18aa3d-5538-4f9e-882a-0633b3061f23&quot; ginger_software_uiphraseguid=&quot;b0a6b6fc-6ae7-4c14-bf97-f10dc6b0474d&quot; class=&quot;GINGER_SOFTWARE_mark&quot;&gt;Anti-Virus&lt;/span&gt;&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 5&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2000;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:47:\"PCI DSS 3.0: All Antivirus Security Risk Events\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:50:43\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: All Virus Events','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 5\";s:8:\"comments\";s:464:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 5: Use and regularly update &lt;span id=&quot;be18aa3d-5538-4f9e-882a-0633b3061f23&quot; ginger_software_uiphraseguid=&quot;b0a6b6fc-6ae7-4c14-bf97-f10dc6b0474d&quot; class=&quot;GINGER_SOFTWARE_mark&quot;&gt;Anti-Virus&lt;/span&gt;&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 5&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2001;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:29:\"PCI DSS 3.0: All Virus Events\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:54:14\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Antivirus Definition Updates','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 5\";s:8:\"comments\";s:464:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 5: Use and regularly update &lt;span id=&quot;be18aa3d-5538-4f9e-882a-0633b3061f23&quot; ginger_software_uiphraseguid=&quot;b0a6b6fc-6ae7-4c14-bf97-f10dc6b0474d&quot; class=&quot;GINGER_SOFTWARE_mark&quot;&gt;Anti-Virus&lt;/span&gt;&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 5&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2003;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:41:\"PCI DSS 3.0: Antivirus Definition Updates\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:51:46\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Antivirus Disabled','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 5\";s:8:\"comments\";s:464:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 5: Use and regularly update &lt;span id=&quot;be18aa3d-5538-4f9e-882a-0633b3061f23&quot; ginger_software_uiphraseguid=&quot;b0a6b6fc-6ae7-4c14-bf97-f10dc6b0474d&quot; class=&quot;GINGER_SOFTWARE_mark&quot;&gt;Anti-Virus&lt;/span&gt;&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 5&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2002;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:31:\"PCI DSS 3.0: Antivirus Disabled\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:54:56\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Authentications with Default Credentials','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 2\";s:8:\"comments\";s:297:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 2: Do not use vendor-supplied defaults&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the&nbsp; PCI DSS requirement 2&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:1076;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:53:\"PCI DSS 3.0: Authentications with Default Credentials\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:20:58\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Cloaked Wireless Networks with Uncloaked APs','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:461;a:2:{s:8:\"location\";s:1:\"0\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:57:\"PCI DSS 3.0: Cloaked Wireless Networks with Uncloaked APs\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 17:04:27\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Database Errors','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2021;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:28:\"PCI DSS 3.0: Database Errors\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:24:08\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Database Failed Logins','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2020;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:35:\"PCI DSS 3.0: Database Failed Logins\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:25:10\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Database Successful Logins','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2022;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:39:\"PCI DSS 3.0: Database Successful Logins\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:25:29\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Dropped or Denied Connections','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 1\";s:8:\"comments\";s:307:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 1: Install and Maintain a Firewall Configuration&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review as stated in PCI DSS requirement 1&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2008;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:42:\"PCI DSS 3.0: Dropped or Denied Connections\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 11:21:05\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Encrypted HTTPS Connections','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 4\";s:8:\"comments\";s:284:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 4: Encrypt Transmission of Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 4&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2005;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:40:\"PCI DSS 3.0: Encrypted HTTPS Connections\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:46:19\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Encrypted Networks with Unencrypted APs','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 4\";s:8:\"comments\";s:284:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 4: Encrypt Transmission of Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 4&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:462;a:2:{s:8:\"location\";s:1:\"0\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:54:\"PCI DSS 3.0: Encrypted Networks with Unencrypted APs\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:49:26\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Encrypted VPN Client Connections Accepted','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 4\";s:8:\"comments\";s:284:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 4: Encrypt Transmission of Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 4&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2006;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:54:\"PCI DSS 3.0: Encrypted VPN Client Connections Accepted\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:45:55\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Encrypted VPN Client Connections Failed','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 4\";s:8:\"comments\";s:284:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 4: Encrypt Transmission of Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 4&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2007;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:52:\"PCI DSS 3.0: Encrypted VPN Client Connections Failed\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:47:37\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Failed Logins','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2025;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:26:\"PCI DSS 3.0: Failed Logins\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:30:52\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Firewall Configuration Changes','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 1\";s:8:\"comments\";s:307:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 1: Install and Maintain a Firewall Configuration&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review as stated in PCI DSS requirement 1&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2010;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:43:\"PCI DSS 3.0: Firewall Configuration Changes\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 11:21:30\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Firewall Failed Authentication','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 1\";s:8:\"comments\";s:307:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 1: Install and Maintain a Firewall Configuration&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review as stated in PCI DSS requirement 1&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2011;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:43:\"PCI DSS 3.0: Firewall Failed Authentication\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 11:21:50\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Firewall Intrusion Detection','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 1\";s:8:\"comments\";s:307:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 1: Install and Maintain a Firewall Configuration&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review as stated in PCI DSS requirement 1&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2013;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:41:\"PCI DSS 3.0: Firewall Intrusion Detection\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 11:11:26\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Firewall Successful Authentication','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 1\";s:8:\"comments\";s:307:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 1: Install and Maintain a Firewall Configuration&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review as stated in PCI DSS requirement 1&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2014;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:47:\"PCI DSS 3.0: Firewall Successful Authentication\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 11:22:19\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Infected Computers','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 5\";s:8:\"comments\";s:464:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 5: Use and regularly update &lt;span id=&quot;be18aa3d-5538-4f9e-882a-0633b3061f23&quot; ginger_software_uiphraseguid=&quot;b0a6b6fc-6ae7-4c14-bf97-f10dc6b0474d&quot; class=&quot;GINGER_SOFTWARE_mark&quot;&gt;Anti-Virus&lt;/span&gt;&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 5&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2004;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:31:\"PCI DSS 3.0: Infected Computers\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:53:41\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Information Security Policy Compliance Checks','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:15:\"Requirements 12\";s:8:\"comments\";s:333:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 12: Maintain an Information Security Policy&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;&lt;span style=&quot;font-weight: normal;&quot;&gt;The following report provides support for the systematic review PCI DSS requirement 12&lt;/span&gt;&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2015;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:58:\"PCI DSS 3.0: Information Security Policy Compliance Checks\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:57:36\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Information Security Policy Compliance Failed','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:15:\"Requirements 12\";s:8:\"comments\";s:333:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 12: Maintain an Information Security Policy&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;&lt;span style=&quot;font-weight: normal;&quot;&gt;The following report provides support for the systematic review PCI DSS requirement 12&lt;/span&gt;&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2016;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:58:\"PCI DSS 3.0: Information Security Policy Compliance Failed\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:58:19\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Security Device Policy Modifications','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:15:\"Requirements 12\";s:8:\"comments\";s:333:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 12: Maintain an Information Security Policy&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;&lt;span style=&quot;font-weight: normal;&quot;&gt;The following report provides support for the systematic review PCI DSS requirement 12&lt;/span&gt;&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2017;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:49:\"PCI DSS 3.0: Security Device Policy Modifications\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:59:16\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Successful Logins','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2029;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:30:\"PCI DSS 3.0: Successful Logins\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:32:28\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Suspicious Clients on Wireless Networks','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:464;a:2:{s:8:\"location\";s:1:\"0\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:52:\"PCI DSS 3.0: Suspicious Clients on Wireless Networks\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:35:27\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Suspicious Database Events','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:2026;a:5:{s:3:\"top\";s:2:\"25\";s:13:\"plugin_groups\";s:0:\"\";s:6:\"source\";s:4:\"siem\";s:7:\"groupby\";s:0:\"\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:39:\"PCI DSS 3.0: Suspicious Database Events\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:29:52\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Vulnerability Details','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:14:\"Requirement 11\";s:8:\"comments\";s:268:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 11: Regularly Test Security Systems&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 11&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:200;a:6:{s:7:\"serious\";s:1:\"1\";s:4:\"high\";s:1:\"1\";s:6:\"medium\";s:1:\"1\";s:3:\"low\";s:1:\"1\";s:4:\"info\";s:1:\"1\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:34:\"PCI DSS 3.0: Vulnerability Details\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:56:28\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Wireless Networks','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:24:\"Requirements 3, 7 and 10\";s:8:\"comments\";s:306:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirements 3, 7 and 10: Protect, Restrict and Monitor Access to Stored Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 3,7 and 10&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:460;a:2:{s:8:\"location\";s:1:\"0\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:30:\"PCI DSS 3.0: Wireless Networks\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:34:31\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}'),
('admin','custom_report','PCI DSS 3.0: Wireless Networks Using Weak Encryption','a:12:{s:2:\"ds\";a:5:{i:440;a:7:{s:4:\"logo\";N;s:9:\"maintitle\";s:18:\"PCI DSS 3.0 Report\";s:11:\"it_security\";s:10:\"AlienVault\";s:7:\"address\";s:31:\"1875 S. Grant Street, Suite 200\";s:4:\"tlfn\";s:17:\"+1 (650) 713-3333\";s:4:\"date\";s:5:\"#DATE\";s:5:\"notes\";s:0:\"\";}i:385;a:3:{s:5:\"title\";s:20:\"PCI Technical Report\";s:8:\"comments\";s:1237:\"&lt;br&gt;The Processing Card Industry Security Standard defines the following twelve requirements:&lt;br&gt;&lt;br&gt;1. Install and maintain a firewall configuration to protect cardholder data &lt;br&gt;2. Do not use vendor supplied defaults for system password and other security parameters.&lt;br&gt;3. Protect stored cardholder data &lt;br&gt;4. Encrypt the transmission of cardholder data across open, public networks.&lt;br&gt;5. Use and regularly update antivirus software&lt;br&gt;6. Develop and maintain secure systems and applications&lt;br&gt;7. Restrict access to cardholder data by businessneed-to-know&lt;br&gt;8. Assign a unique ID to each person with computer access&lt;br&gt;9. Restrict physical access to cardholder data&lt;br&gt;10. Track and monitor all access to network resources and cardholder data&lt;br&gt;11. Regularly test security systems and processes&lt;br&gt;12. Maintain a policy that addresses information security&lt;br&gt;&lt;br&gt;This report provides support for a systematic review of these requirements.&lt;br&gt;&lt;br&gt;Note: The asset configuration of the report should limited to cardholder data servers and security and network devices involved in the cardholder data transmission.&lt;br&gt;\";s:5:\"notes\";s:0:\"\";}i:404;a:1:{s:5:\"notes\";N;}i:380;a:3:{s:5:\"title\";s:13:\"Requirement 4\";s:8:\"comments\";s:284:\"&lt;h3 style=&quot;text-align: center;&quot;&gt;Requirement 4: Encrypt Transmission of Data&lt;br&gt;&lt;/h3&gt;&lt;h3 style=&quot;text-align: center; font-weight: normal;&quot;&gt;The following report provides support for the systematic review of the PCI DSS requirement 4&lt;/h3&gt;\";s:5:\"notes\";s:0:\"\";}i:463;a:2:{s:8:\"location\";s:1:\"0\";s:5:\"notes\";s:0:\"\";}}s:5:\"rname\";s:52:\"PCI DSS 3.0: Wireless Networks Using Weak Encryption\";s:9:\"date_from\";N;s:7:\"date_to\";N;s:10:\"date_range\";s:6:\"last30\";s:7:\"profile\";s:7:\"Default\";s:5:\"cdate\";s:19:\"2010-07-01 17:06:31\";s:5:\"mdate\";s:19:\"2014-11-13 12:38:41\";s:6:\"assets\";s:10:\"ALL_ASSETS\";s:10:\"asset_type\";s:0:\"\";s:4:\"user\";s:1:\"0\";s:6:\"entity\";s:2:\"-1\";}');

-- Fill begin field
UPDATE vuln_job_schedule SET begin = SUBSTRING(next_CHECK,1,8) WHERE begin='';

REPLACE INTO config (conf, value) VALUES ('incidents_incharge_default', 'admin');

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2014-12-02');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.14.0');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
