USE alienvault;


-- -----------------------------------------------------
-- Procedure host_filter
-- -----------------------------------------------------

DELIMITER $$
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
  IN asset_value_from CHAR, -- integer between 0 and 5
  IN asset_value_to CHAR, -- integer between 0 and 5 >= asset_value_from
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
  DECLARE host_filters_applied TINYINT DEFAULT 0;

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

  -- Hosts with events
  IF events_filter = 1
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    REPLACE INTO filters_add SELECT src_host as id FROM alienvault_siem.po_acid_event UNION DISTINCT SELECT DISTINCT dst_host as id FROM alienvault_siem.po_acid_event;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts with alarms
  IF alarms_filter = 1
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    REPLACE INTO filters_add SELECT ah.id_host as id FROM alarm_hosts ah, alarm a WHERE a.backlog_id=ah.id_alarm AND a.status = 'open';
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts with vulnerabilities in range
  IF vulns_from > 0 AND vulns_to > 0 AND vulns_from <= vulns_to
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    REPLACE INTO filters_add SELECT h.id FROM host h, host_ip hi, vuln_nessus_latest_results lr WHERE hi.host_id=h.id AND hi.ip=inet6_aton(lr.hostIP) AND h.ctx=lr.ctx AND lr.risk BETWEEN vulns_from AND vulns_to;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts with availability enabled
  IF nagios <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    IF nagios = '0' THEN
      REPLACE INTO filters_add SELECT h.id FROM host h LEFT JOIN host_scan ha ON ha.host_id=h.id AND ha.plugin_id=2007 and ha.plugin_sid=0 WHERE ha.host_id IS NULL;
    ELSEIF nagios = '1' THEN
      REPLACE INTO filters_add SELECT h.id FROM host h, host_scan ha WHERE ha.host_id=h.id AND ha.plugin_id=2007 and ha.plugin_sid=0 AND ha.status=1;
    ELSEIF nagios = '2' THEN
      REPLACE INTO filters_add SELECT h.id FROM host h, host_scan ha WHERE ha.host_id=h.id AND ha.plugin_id=2007 and ha.plugin_sid=0 AND ha.status=2;
    END IF;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts with asset value in range
  IF asset_value_from <> '' AND asset_value_to <> '' AND asset_value_from <= asset_value_to AND ftype <> 'network'
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    REPLACE INTO filters_add SELECT h.id FROM host h WHERE h.asset BETWEEN asset_value_from AND asset_value_to;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts with asset created date greater or equals than a given date
  IF last_added_from <> '' AND last_added_to <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    REPLACE INTO filters_add SELECT h.id FROM host h WHERE h.created BETWEEN last_added_from AND last_added_to;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts with asset updated date greater or equals than a given date
  IF last_updated_from <> '' AND last_updated_to <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    REPLACE INTO filters_add SELECT h.id FROM host h WHERE h.updated BETWEEN last_updated_from AND last_updated_to;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts with hostname or FQDN
  IF fqdn <> ''
  THEN
    SET host_filters_applied = 1;
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

  -- Hosts with IP in range
  IF ip_range <> ''
  THEN
    SET host_filters_applied = 1;
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

  -- Hosts in a list of networks
  IF networks <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_net_reference r WHERE r.host_id=h.id AND r.net_id in (',networks,');');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts in a list of asset groups
  IF agroups <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_group_reference r WHERE r.host_id=h.id AND r.host_group_id in (',agroups,');');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts within a list of labels/tags
  IF labels <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.component_tags t WHERE t.id_component=h.id AND t.id_tag in (',labels,');');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts within a list of operating systems
  IF os <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    SET @str = REPLACE(os,',','","');
    SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_properties hp WHERE hp.host_id=h.id AND hp.property_ref=3 AND hp.value in ("',@str,'");');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
    IF os = 'unknown' THEN
      REPLACE INTO filters_add SELECT DISTINCT h.id FROM host h LEFT JOIN host_properties hp ON hp.host_id=h.id AND hp.property_ref=3 WHERE hp.host_id IS NULL;
    END IF;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts within a list of models
  IF model <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    SET @str = REPLACE(model,',','","');
    SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_properties hp WHERE hp.host_id=h.id AND hp.property_ref=14 AND hp.value in ("',@str,'");');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts that contains a software with CPE
  IF cpe <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    SET @str = REPLACE(cpe,',','","');
    SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_software s WHERE s.host_id=h.id AND s.cpe in ("',@str,'");');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts from a device type or subtype
  IF device_types <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    SET @str = REPLACE(device_types,';','),(');
    SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_types ht WHERE ht.host_id=h.id AND (ht.type, ht.subtype) in ((',@str,'));');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts with services (port, protocol, service)
  IF services <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    SET @str = REPLACE(services,';','),(');
    SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_services hs WHERE hs.host_id=h.id AND (hs.port, hs.protocol, hs.service) in ((',@str,'));');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts associated with sensors
  IF sensors <> '' AND ftype <> 'network'
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_sensor_reference r WHERE r.host_id=h.id AND r.sensor_id in (',sensors,');');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts with locations
  IF locations <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_sensor_reference r, location_sensor_reference l WHERE h.id=r.host_id AND r.sensor_id=l.sensor_id AND l.location_id in (',locations,');');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts related with a plugin list
  IF plugins <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    IF plugins = '0' THEN
      REPLACE INTO filters_add SELECT h.id FROM host h LEFT JOIN host_scan ha ON ha.host_id=h.id AND ha.plugin_id!=2007 WHERE ha.host_id IS NULL;
    ELSE
      SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_scan s WHERE s.host_id=h.id AND s.plugin_id in (',plugins,') AND s.plugin_sid=0;');
      PREPARE sql_query from @query;
      EXECUTE sql_query;
      DEALLOCATE PREPARE sql_query;
    END IF;
    DELETE ft FROM filters_tmp ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
  END IF;

  -- Hosts with HIDS
  IF hids_filter <> ''
  THEN
    SET host_filters_applied = 1;
    TRUNCATE filters_add;
    IF hids_filter = '0' THEN
      REPLACE INTO filters_add SELECT h.id FROM host h LEFT JOIN hids_agents ha ON ha.host_id=h.id WHERE ha.host_id IS NULL;
    ELSEIF hids_filter = '1' THEN
      REPLACE INTO filters_add SELECT h.id FROM host h, hids_agents ha WHERE ha.host_id=h.id AND ha.agent_status in (0,1,2);
    ELSEIF hids_filter = '2' THEN
      REPLACE INTO filters_add SELECT h.id FROM host h, hids_agents ha WHERE ha.host_id=h.id AND ha.agent_status in (3,4);
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

    IF host_filters_applied = 1
    THEN
      -- Asset groups which match the search criteria
      INSERT IGNORE INTO filters_tmpg SELECT host_group_id FROM host_group_reference, filters_tmp WHERE id=host_id;
    ELSE
      -- No host filters applied, so all asset groups are added initially
      INSERT IGNORE INTO filters_tmpg SELECT id FROM host_group;
    END IF;

    -- It doesn't make sense to apply net filters if there are no asset groups which match the search criteria
    SELECT COUNT(id) INTO @asset_groups_filtered FROM filters_tmpg LIMIT 1;

    IF group_name <> '' AND @asset_groups_filtered > 0
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
      SET @query = CONCAT('INSERT IGNORE INTO filters_add SELECT hg.id FROM host_group hg WHERE ',substring(@query,1,length(@query)-4));
      PREPARE sql_query from @query;
      EXECUTE sql_query;
      DEALLOCATE PREPARE sql_query;
      DELETE ft FROM filters_tmpg ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
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

    IF host_filters_applied = 1
    THEN
      -- Networks which match the search criteria
      INSERT IGNORE INTO filters_tmpg SELECT net_id FROM host_net_reference, filters_tmp WHERE id=host_id;
    ELSE
      -- No host filters applied, so all networks are added initially
      INSERT IGNORE INTO filters_tmpg SELECT id FROM net;
    END IF;

    -- It doesn't make sense to apply net filters if there are no networks which match the search criteria
    SELECT COUNT(id) INTO @nets_filtered FROM filters_tmpg LIMIT 1;

    IF @nets_filtered > 0
    THEN
      -- Filter by network name
      IF net_name <> ''
      THEN
        SELECT LENGTH(net_name) - LENGTH(REPLACE(net_name, ';', '')) INTO @nCommas;
        SET y = 1;
        SET x = @nCommas + 1;
        SET @query = '';
        WHILE y <= x DO
        SELECT _split_string(net_name, ';', y) INTO @range;
        SET @query = CONCAT(@query,'n.name like "%',@range,'%" OR ');
        SET  y = y + 1;
        END WHILE;

        TRUNCATE filters_add;
        SET @query = CONCAT('INSERT IGNORE INTO filters_add SELECT n.id FROM net n WHERE ',substring(@query,1,length(@query)-4));
        PREPARE sql_query from @query;
        EXECUTE sql_query;
        DEALLOCATE PREPARE sql_query;
        DELETE ft FROM filters_tmpg ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
      END IF;

      -- Network CIDR
      IF net_cidr <> ''
      THEN
        SELECT LENGTH(net_cidr) - LENGTH(REPLACE(net_cidr, ';', '')) INTO @nCommas;
        SET y = 1;
        SET x = @nCommas + 1;
        SET @query = '';
        WHILE y <= x DO
        SELECT _split_string(net_cidr, ';', y) INTO @range;
        SET @query = CONCAT(@query,'n.ips like "%',@range,'%" OR ');
        SET  y = y + 1;
        END WHILE;

        TRUNCATE filters_add;
        SET @query = CONCAT('INSERT IGNORE INTO filters_add SELECT n.id FROM net n WHERE ',substring(@query,1,length(@query)-4));
        PREPARE sql_query from @query;
        EXECUTE sql_query;
        DEALLOCATE PREPARE sql_query;
        DELETE ft FROM filters_tmpg ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
      END IF;

      -- Asset value
      IF asset_value_from <> '' AND asset_value_to <> '' AND asset_value_from <= asset_value_to
      THEN
        TRUNCATE filters_add;
        INSERT IGNORE INTO filters_add SELECT n.id FROM net n WHERE n.asset BETWEEN asset_value_from AND asset_value_to;
        DELETE ft FROM filters_tmpg ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
      END IF;

      -- Sensors
      IF sensors <> ''
      THEN
        TRUNCATE filters_add;
        SET @query = CONCAT('INSERT IGNORE INTO filters_add SELECT DISTINCT net_id FROM alienvault.net_sensor_reference WHERE sensor_id IN (',sensors,');');
        PREPARE sql_query from @query;
        EXECUTE sql_query;
        DEALLOCATE PREPARE sql_query;
        DELETE ft FROM filters_tmpg ft LEFT JOIN filters_add fa ON fa.id=ft.id WHERE fa.id IS NULL;
      END IF;
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

DELIMITER ;


-- DELETE old acl_templates_perms
DELETE FROM acl_templates_perms WHERE ac_perm_id IN (18, 27, 30, 33, 34, 46, 47, 54, 62, 64, 68, 78, 81 );


REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2020-09-29');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.8.5');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
