USE alienvault;
SET AUTOCOMMIT=0;

ALTER TABLE users CHANGE pass pass VARCHAR(128) NOT NULL;

UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=236)" WHERE name = "Database/Login" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=237)" WHERE name = "Database/Login Failed" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=238)" WHERE name = "Database/Query" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=239)" WHERE name = "Database/Logout" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=240)" WHERE name = "Database/Stop" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=241)" WHERE name = "Database/Start" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=242)" WHERE name = "Database/Error" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=243)" WHERE name = "Database/Misc" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=18 WHERE plugin_sid.subcategory_id=202)" WHERE name = "Inventory/Service Detected" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=18 WHERE plugin_sid.subcategory_id=203)" WHERE name = "Inventory/Service Change" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=18 WHERE plugin_sid.subcategory_id=204)" WHERE name = "Inventory/Service Misc" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=18 WHERE plugin_sid.subcategory_id=205)" WHERE name = "Inventory/Operating System Detected" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=18 WHERE plugin_sid.subcategory_id=206)" WHERE name = "Inventory/Operating System Change" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=18 WHERE plugin_sid.subcategory_id=207)" WHERE name = "Inventory/Operating System Misc" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=18 WHERE plugin_sid.subcategory_id=208)" WHERE name = "Inventory/Mac Detected" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=18 WHERE plugin_sid.subcategory_id=209)" WHERE name = "Inventory/Mac Change" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=18 WHERE plugin_sid.subcategory_id=210)" WHERE name = "Inventory/Mac Misc" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=5 WHERE plugin_sid.subcategory_id=211)" WHERE name = "Policy/Check Failed" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=5 WHERE plugin_sid.subcategory_id=212)" WHERE name = "Policy/Check Passed" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=8 WHERE plugin_sid.subcategory_id=213)" WHERE name = "Network/High Load" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=2 WHERE plugin_sid.subcategory_id=214)" WHERE name = "Authentication/Error" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=215)" WHERE name = "Application/Web Modified" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=2 WHERE plugin_sid.subcategory_id=216)" WHERE name = "Authentication/Misc" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=217)" WHERE name = "Application/DHCP Release" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=218)" WHERE name = "Application/DHCP Misc" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=219)" WHERE name = "Application/DHCP Request" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=220)" WHERE name = "Application/DHCP Lease" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=221)" WHERE name = "Application/DHCP Pool Exhausted" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=222)" WHERE name = "Application/DHCP Error" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=11 WHERE plugin_sid.subcategory_id=223)" WHERE name = "System/Software Installed" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=19 WHERE plugin_sid.subcategory_id=224)" WHERE name = "Honeypot/Connection Opened" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=19 WHERE plugin_sid.subcategory_id=225)" WHERE name = "Honeypot/Attack Detected" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=19 WHERE plugin_sid.subcategory_id=226)" WHERE name = "Honeypot/Connection Closed" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=19 WHERE plugin_sid.subcategory_id=227)" WHERE name = "Honeypot/Misc" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=228)" WHERE name = "Application/DNS Succesful Zone Tranfer" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=229)" WHERE name = "Application/DNS Zone Transfer Failed" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=230)" WHERE name = "Application/DNS Misc" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=231)" WHERE name = "Application/FTP Command Executed" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=232)" WHERE name = "Application/FTP Error" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=233)" WHERE name = "Application/FTP Connection Opened" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=234)" WHERE name = "Application/FTP Connection Closed" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=13 WHERE plugin_sid.subcategory_id=235)" WHERE name = "Application/FTP Misc" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=237)" WHERE name = "Database/Login Failed" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=238)" WHERE name = "Database/Query" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=239)" WHERE name = "Database/Logout" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=240)" WHERE name = "Database/Stop" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=241)" WHERE name = "Database/Start" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=242)" WHERE name = "Database/Error" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=243)" WHERE name = "Database/Misc" AND type = "Security";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=5 WHERE plugin_sid.subcategory_id=212)" WHERE name = "Information Security Policy Compliance Checks" AND type = "PCI - Maintain Information Security Policy";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=5 WHERE plugin_sid.subcategory_id=211)" WHERE name = "Information Security Policy Compliance Failed" AND type = "PCI - Maintain Information Security Policy";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=237)" WHERE name = "Database Failed Logins" AND type = "PCI - Protect Stored Data";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=242)" WHERE name = "Database Errors" AND type = "PCI - Protect Stored Data";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=20 WHERE plugin_sid.subcategory_id=236)" WHERE name = "Database Succesful Logins" AND type = "PCI - Protect Stored Data";
UPDATE custom_report_types SET custom_report_types.sql = "AND ((plugin_sid.category_id=2 AND plugin_sid.subcategory_id in (25,83)) OR (plugin_sid.category_id=20 AND plugin_sid.subcategory_id in (237)))" WHERE name = "Failed Logins" AND type = "PCI - Restrict Access to Data";
UPDATE custom_report_types SET custom_report_types.sql = "AND (plugin_sid.category_id=2 AND plugin_sid.subcategory_id in (214,94,22,23,28,25,83))" WHERE name = "A.10.10.5 Fault logging" AND type = "ISO 27001";

-- Messages
REPLACE INTO `alienvault_api`.`status_message` (`id`,`level`,`description`,`content`) VALUES
(3,'warning','Log management disrupted','The system has not received a log from this asset in more than 24 hours. This may be an indicator of the asset having connection difficulties with AlienVault or a disruptive configuration change on the asset. At TIMESTAMP');

DELIMITER $$

-- Delete backlog
DROP PROCEDURE IF EXISTS _delete_orphan_backlogs$$
CREATE PROCEDURE _delete_orphan_backlogs()
BEGIN
    START TRANSACTION;
        CREATE TEMPORARY TABLE IF NOT EXISTS tmpbckdel (event_id binary(16) NOT NULL, PRIMARY KEY ( event_id ));
        REPLACE INTO tmpbckdel SELECT be.event_id FROM backlog_event be, backlog b WHERE be.backlog_id = b.id AND b.timestamp = '1970-01-01 00:00:00';
        DELETE t FROM tmpbckdel t, backlog_event be, backlog b WHERE be.backlog_id = b.id AND be.event_id = t.event_id AND b.timestamp <> '1970-01-01 00:00:00';
        DELETE e, i, x FROM event e LEFT JOIN tmpbckdel t ON e.id = t.event_id LEFT JOIN idm_data i ON i.event_id = e.id LEFT JOIN extra_data x ON x.event_id = e.id WHERE t.event_id IS NOT NULL;
        DELETE be FROM backlog_event be, backlog b WHERE be.backlog_id=b.id AND b.timestamp = '1970-01-01 00:00:00';
        DELETE b, t, c, h, n, a FROM backlog b LEFT JOIN alarm_tags t ON t.id_alarm = b.id LEFT JOIN alarm_ctxs c ON c.id_alarm = b.id LEFT JOIN alarm_nets n ON n.id_alarm = b.id LEFT JOIN alarm_hosts h ON h.id_alarm = b.id LEFT JOIN alarm a ON a.backlog_id = b.id WHERE b.timestamp = '1970-01-01 00:00:00';
        DROP TABLE tmpbckdel;
    COMMIT;
END$$

-- modify/add columns procedure
DROP PROCEDURE IF EXISTS addcol$$
CREATE PROCEDURE addcol() BEGIN
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_nessus_latest_results' AND INDEX_NAME = 'port')
    THEN
        ALTER TABLE `alienvault`.`vuln_nessus_latest_results` DROP INDEX `port`;
    END IF;
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_nessus_latest_results' AND INDEX_NAME = 'hosts')
    THEN
        ALTER TABLE `alienvault`.`vuln_nessus_latest_results` DROP INDEX `hosts`;
    END IF;
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_nessus_latest_results' AND INDEX_NAME = 'falsepositive')
    THEN
        ALTER TABLE `alienvault`.`vuln_nessus_latest_results` DROP INDEX `falsepositive`;
    END IF;
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_nessus_results' AND INDEX_NAME = 'scriptid')
    THEN
        ALTER TABLE `alienvault`.`vuln_nessus_results` DROP INDEX `scriptid`;
    END IF;
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_nessus_settings_plugins' AND INDEX_NAME = 'id')
    THEN
        ALTER TABLE `alienvault`.`vuln_nessus_settings_plugins` DROP INDEX `id`;
    END IF;
    ALTER TABLE `alienvault`.`vuln_nessus_latest_results` ADD INDEX  `port` (`falsepositive`,`port`,`protocol`,`app`);
    ALTER TABLE `alienvault`.`vuln_nessus_latest_results` ADD INDEX  `hosts` (`falsepositive`, `hostIP`);
    ALTER TABLE `alienvault`.`vuln_nessus_latest_results` ADD INDEX  `falsepositive` (`falsepositive`,`risk`,`hostIP`,`ctx`);
    ALTER TABLE `alienvault`.`vuln_nessus_results` ADD INDEX  `scriptid` (`scriptid`,`falsepositive`);
    ALTER TABLE `alienvault`.`vuln_nessus_settings_plugins` ADD INDEX  `id` (`id`);
END$$

DROP PROCEDURE IF EXISTS host_filter$$
CREATE PROCEDURE host_filter(
    IN login VARCHAR(64), -- string like - 'admin'
    IN drop_table INT, -- boolean value - 0 or 1
    IN events_filter INT, -- boolean value - 0 or 1
    IN alarms_filter INT, -- boolean value - 0 or 1
    IN vulns_from INT, -- integer between 1 and 7
    IN vulns_to INT, -- integer between 1 and 7 >= vuln_from
    IN asset_value_from CHAR, -- interger between 1 and 5
    IN asset_value_to CHAR, -- interger between 1 and 5 >= asset_value_from
    IN last_added_from VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
    IN last_added_to VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
    IN last_updated_from VARCHAR(19), -- datetime - '2013-08-15 22:30:00'
    IN last_updated_to VARCHAR(19), -- datetime - '2013-08-15 22:30:00'
    IN fqdn TEXT, -- free string (% is allowed)
    IN ip_range TEXT, -- ip ranges 192.168.1.1,192.168.1.255;192.168.1.2,192.168.1.2
    IN networks TEXT, -- network hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
    IN cpe TEXT, -- unquoted string - cpe:/o:yamaha:srt100:10.00.46,cpe:/o:microsoft:virtual_machine_manager:2007
    IN device_types TEXT, -- unquoted string typeid,subtypeid - 1,0;4,404
    IN services TEXT, -- quoted string port,protocol,'service' - 80,6,'http';0,1,'PING'
    IN sensors TEXT, -- sensor hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
    IN locations TEXT -- location hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
)
BEGIN

    DECLARE x INT DEFAULT 0;
    DECLARE y INT DEFAULT 0;

    CREATE TABLE IF NOT EXISTS user_host_filter (
        login varchar(64) NOT NULL,
        asset_id varbinary(16) NOT NULL,
        PRIMARY KEY (`asset_id`,`login`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    DROP TEMPORARY TABLE IF EXISTS filters_tmp;
    DROP TEMPORARY TABLE IF EXISTS filters_add;
    CREATE TEMPORARY TABLE filters_tmp (id binary(16) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MEMORY;
    CREATE TEMPORARY TABLE filters_add (id binary(16) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MEMORY;
    REPLACE INTO filters_tmp SELECT id FROM host;

    START TRANSACTION;

        -- Host with events
        IF events_filter = 1
        THEN
            TRUNCATE filters_add;
            REPLACE INTO filters_add SELECT src_host as id FROM alienvault_siem.ac_acid_event WHERE cnt>0 UNION DISTINCT SELECT DISTINCT dst_host as id FROM alienvault_siem.ac_acid_event WHERE cnt>0;
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

        -- Host with asset value in range        
        IF asset_value_from <> '' AND asset_value_to <> '' AND asset_value_from <= asset_value_to
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

        -- Host in a network list
        IF networks <> ''
        THEN
            TRUNCATE filters_add;
            SET @query = CONCAT('REPLACE INTO filters_add SELECT DISTINCT h.id FROM alienvault.host h, alienvault.host_net_reference r WHERE r.host_id=h.id AND r.net_id in (',networks,');');
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

        -- Host width services (port, protocol, service)
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

    	IF drop_table = 1
    	THEN
    	    DELETE FROM user_host_filter WHERE `login`=login;
    	    INSERT INTO user_host_filter SELECT login,id from filters_tmp;
    	ELSE
    	    DELETE h FROM user_host_filter h LEFT JOIN filters_tmp t ON h.asset_id=t.id WHERE h.`login`=login AND t.id IS NULL;
    	END IF;

    COMMIT;

    SELECT COUNT(asset_id) as assets FROM user_host_filter WHERE `login`=login;
END$$

DELIMITER ';'

CALL addcol();
DROP PROCEDURE addcol;





REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2014-09-02');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.11.0');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA

