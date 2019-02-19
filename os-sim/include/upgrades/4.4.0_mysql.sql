CREATE DATABASE IF NOT EXISTS alienvault_api;
USE alienvault_api;
-- -----------------------------------------------------
-- Table `deployment_status_messages`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `deployment_status_messages` (
  `component_id` BINARY(16) NOT NULL ,
  `message_id` SMALLINT UNSIGNED NOT NULL ,
  `level` TINYINT NULL ,
  `creation_time` TIMESTAMP NOT NULL COMMENT '(CURRENT_TIMESTAMP | on create CURRENT_TIMESTAMP )\n' ,
  `supressed` TINYINT NULL DEFAULT 0 COMMENT 'True or False. Indicates that this kind of message over this component id is disabled.' ,
  `supressed_time` TIMESTAMP NULL ,
  `message_content` TEXT NULL COMMENT 'Json Data with the content of the message' ,
  PRIMARY KEY (`component_id`, `message_id`) )
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `logged_actions`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `logged_actions` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `logged_user` VARCHAR(45) NULL ,
  `datetime` TIMESTAMP NULL ,
  `action_description` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `monitor_data`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `monitor_data` (
  `component_id` BINARY(16) NOT NULL ,
  `timestamp` TIMESTAMP NOT NULL ,
  `monitor_id` INT NOT NULL ,
  `data` TEXT NULL ,
  `component_type` VARCHAR(55) NULL COMMENT 'Component type. (net, host, …)' ,
  PRIMARY KEY (`component_id`, `timestamp`, `monitor_id`) )
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `celery_job`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `celery_job` (
  `id` BINARY(16) NOT NULL,
  `info` BLOB NULL,
  `last_modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

USE alienvault;
SET AUTOCOMMIT=0;
BEGIN;

-- Asset filters y types
CREATE TABLE IF NOT EXISTS `asset_filters` (
  `group_id` binary(16) NOT NULL,
  `filter_id` int(11) NOT NULL,
  `value_from` int(11) NOT NULL DEFAULT 0,
  `value_to` int(11)  NOT NULL DEFAULT 0,
  `value` varchar(128) COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`group_id`,`filter_id`,`value_from`,`value_to`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `asset_filter_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filter` varchar(128) COLLATE utf8_general_ci NOT NULL,
  `type` varchar(128) COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

TRUNCATE TABLE `asset_filter_types`;
INSERT INTO `asset_filter_types` (`id`, `filter`, `type`) VALUES
(1, 'asset_created', 'range'),
(2, 'asset_updated', 'range'),
(3, 'alarms', 'value'),
(4, 'events', 'value'),
(5, 'vulnerabilities', 'range'),
(6, 'asset_value', 'range'),
(7, 'network', 'list'),
(8, 'device_type', 'range_list'),
(9, 'software', 'list'),
(10, 'port_service', 'range_list'),
(11, 'ip', 'range_list'),
(12, 'fqdn', 'list'),
(13, 'location', 'list'),
(14, 'sensor', 'list');

CREATE TABLE IF NOT EXISTS `software_cpe_links` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `vendor` VARCHAR(255) NOT NULL,
  `model` VARCHAR(255) NOT NULL,
  `link` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `vendor` (`vendor` ASC, `model` ASC)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

REPLACE INTO `software_cpe_links` VALUES
(1,'','','DC-00101_01.pdf'),
(2,'Cisco','ASA','DC-00102_01.pdf'),
(3,'Cisco','PIX','DC-00103_01.pdf'),
(4,'Dell','SonicWALL','DC-00120_00.pdf'),
(5,'Cisco','Wireless LAN Controller','DC-00120_00.pdf'),
(6,'Citrix','NetScaler','DC-00120_00.pdf'),
(7,'McAfee','CyberGuard TSP','DC-00123_00.pdf'),
(8,'F5','FirePass','DC-00124_00.pdf'),
(9,'Fortinet','FortiGate','DC-00125_00.pdf');

CREATE TABLE IF NOT EXISTS `user_host_filter` (
  `login` VARCHAR(64) NOT NULL ,
  `asset_id` BINARY(16) NOT NULL ,
  PRIMARY KEY (`login`, `asset_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `host_group_history` (
  `host_group_id` BINARY(16) NOT NULL,
  `date` DATETIME NOT NULL,
  `login` VARCHAR(64) NOT NULL,
  `action` VARCHAR(255) NULL,
  PRIMARY KEY (`host_group_id`, `date`, `login`, `action`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- modify/add columns procedure
DROP PROCEDURE IF EXISTS addcol;
DELIMITER $$
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault_siem' AND TABLE_NAME = 'ac_acid_event' AND INDEX_NAME = 'src_host')
  THEN
      ALTER TABLE alienvault_siem.ac_acid_event ADD INDEX `src_host` (  `src_host` ), ADD INDEX `dst_host` (  `dst_host` );
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'host' AND COLUMN_NAME = 'created')
  THEN
      ALTER TABLE `host` ADD `created` DATETIME NULL DEFAULT NULL, ADD `updated` DATETIME NULL DEFAULT NULL;
      ALTER TABLE `host` ADD INDEX `created` (  `created` ), ADD INDEX `updated` (  `updated` );
      UPDATE `host` SET `created` = utc_timestamp(), `updated` = utc_timestamp();
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'host' AND INDEX_NAME = 'asset')
  THEN
      ALTER TABLE `host` ADD INDEX `asset` (  `asset` );
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'net' AND COLUMN_NAME = 'owner')
  THEN
      ALTER TABLE `net` ADD `owner` VARCHAR(128) NULL DEFAULT NULL;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'host_group' AND COLUMN_NAME = 'owner')
  THEN
      ALTER TABLE `host_group` ADD `owner` VARCHAR(128) NULL DEFAULT NULL;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'software_cpe' AND COLUMN_NAME = 'vendor')
  THEN
      ALTER TABLE `software_cpe` ADD `vendor` VARCHAR( 255 ) NOT NULL;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'software_cpe' AND COLUMN_NAME = 'plugin')
  THEN
      ALTER TABLE `software_cpe` ADD `plugin` VARCHAR( 255 ) NOT NULL;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'software_cpe' AND INDEX_NAME = 'search')
  THEN
      ALTER TABLE `software_cpe` ADD INDEX `search` (`vendor`, `name`, `version`), ADD INDEX `line` (`line`);
  END IF;
  ALTER TABLE `host_services` CHANGE `version` `version` TEXT NULL;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'host_services' AND COLUMN_NAME = 'nagios_status')
  THEN
      ALTER TABLE `host_services` ADD `nagios_status` TINYINT(4) NOT NULL DEFAULT 3 AFTER `nagios`;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'log_action' AND INDEX_NAME = 'info')
  THEN
      ALTER TABLE `log_action` ADD INDEX `info` (  `info`, `date` );
  END IF;
END$$

DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

-- Vulns index optimization
ALTER TABLE `alienvault`.`vuln_nessus_latest_results` CHANGE `risk` `risk` SMALLINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `alienvault`.`vuln_nessus_latest_results` DROP INDEX  `hostIP` , ADD INDEX `hostIP` (  `hostIP` ,  `ctx` ,  `risk` );

-- Incident title
ALTER TABLE `alienvault`.`incident` CHANGE `title` `title` VARCHAR(512) NOT NULL;

-- Add assets depends on filters
DROP FUNCTION IF EXISTS split_string;
DROP PROCEDURE IF EXISTS add_filter;

DELIMITER $$

CREATE FUNCTION split_string( stringToSplit VARCHAR(256), sign VARCHAR(12), position INT) RETURNS VARCHAR(256)
BEGIN
    RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(stringToSplit, sign, position),LENGTH(SUBSTRING_INDEX(stringToSplit, sign, position -1)) + 1), sign, '');
END$$

CREATE PROCEDURE add_filter(
    IN login VARCHAR(64), -- string like - 'admin'
    IN drop_table INT, -- boolean value - 0 or 1
    IN events_filter INT, -- boolean value - 0 or 1
    IN alarms_filter INT, -- boolean value - 0 or 1
    IN vulns_from INT, -- integer between 1 and 7
    IN vulns_to INT, -- integer between 1 and 7 >= vuln_from
    IN asset_value_from INT, -- interger between 1 and 5
    IN asset_value_to INT, -- interger between 1 and 5 >= asset_value_from
    IN last_added_from VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
    IN last_added_to VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
    IN last_updated_from VARCHAR(19), -- datetime - '2013-08-15 22:30:00'
    IN last_updated_to VARCHAR(19), -- datetime - '2013-08-15 22:30:00'
    IN fqdn VARCHAR(128), -- free string (% is allowed)
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
        IF asset_value_from > 0 AND asset_value_to > 0 AND asset_value_from <= asset_value_to
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
               SELECT split_string(fqdn, ';', y) INTO @range;
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
               SELECT split_string(ip_range, ';', y) INTO @range;
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
DELIMITER ;


--
-- Triggers
--

DROP TRIGGER IF EXISTS `host_INSERT`;
DROP TRIGGER IF EXISTS `host_UPDATE`;

DROP TRIGGER IF EXISTS `host_services_INSERT`;
DROP TRIGGER IF EXISTS `host_services_UPDATE`;
DROP TRIGGER IF EXISTS `host_services_DELETE`;

DROP TRIGGER IF EXISTS `host_software_INSERT`;
DROP TRIGGER IF EXISTS `host_software_UPDATE`;
DROP TRIGGER IF EXISTS `host_software_DELETE`;

DROP TRIGGER IF EXISTS `host_types_INSERT`;
DROP TRIGGER IF EXISTS `host_types_UPDATE`;
DROP TRIGGER IF EXISTS `host_types_DELETE`;

DROP TRIGGER IF EXISTS `host_properties_INSERT`;
DROP TRIGGER IF EXISTS `host_properties_UPDATE`;
DROP TRIGGER IF EXISTS `host_properties_DELETE`;

DROP TRIGGER IF EXISTS `host_ip_INSERT`;
DROP TRIGGER IF EXISTS `host_ip_UPDATE`;
DROP TRIGGER IF EXISTS `host_ip_DELETE`;

DROP TRIGGER IF EXISTS `host_scan_INSERT`;
DROP TRIGGER IF EXISTS `host_scan_UPDATE`;
DROP TRIGGER IF EXISTS `host_scan_DELETE`;

DROP TRIGGER IF EXISTS `host_vulnerability_INSERT`;
DROP TRIGGER IF EXISTS `host_vulnerability_UPDATE`;
DROP TRIGGER IF EXISTS `host_vulnerability_DELETE`;

DELIMITER $$
CREATE TRIGGER `host_INSERT` BEFORE INSERT ON `host`
FOR EACH ROW BEGIN
    SET NEW.created = utc_timestamp();
    SET NEW.updated = utc_timestamp();
END$$
CREATE TRIGGER `host_UPDATE` BEFORE UPDATE ON `host`
FOR EACH ROW BEGIN
    SET NEW.updated = utc_timestamp();
END$$

CREATE TRIGGER `host_services_INSERT` AFTER INSERT ON `host_services`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_services_UPDATE` AFTER UPDATE ON `host_services`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_services_DELETE` AFTER DELETE ON `host_services`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
END$$


CREATE TRIGGER `host_software_INSERT` AFTER INSERT ON `host_software`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_software_UPDATE` AFTER UPDATE ON `host_software`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_software_DELETE` AFTER DELETE ON `host_software`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
END$$


CREATE TRIGGER `host_types_INSERT` AFTER INSERT ON `host_types`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_types_UPDATE` AFTER UPDATE ON `host_types`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_types_DELETE` AFTER DELETE ON `host_types`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
END$$


CREATE TRIGGER `host_properties_INSERT` AFTER INSERT ON `host_properties`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_properties_UPDATE` AFTER UPDATE ON `host_properties`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_properties_DELETE` AFTER DELETE ON `host_properties`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
END$$


CREATE TRIGGER `host_ip_INSERT` AFTER INSERT ON `host_ip`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_ip_UPDATE` AFTER UPDATE ON `host_ip`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_ip_DELETE` AFTER DELETE ON `host_ip`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
END$$


CREATE TRIGGER `host_scan_INSERT` AFTER INSERT ON `host_scan`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_scan_UPDATE` AFTER UPDATE ON `host_scan`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_scan_DELETE` AFTER DELETE ON `host_scan`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
END$$


CREATE TRIGGER `host_vulnerability_INSERT` AFTER INSERT ON `host_vulnerability`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_vulnerability_UPDATE` AFTER UPDATE ON `host_vulnerability`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=NEW.host_id;
END$$
CREATE TRIGGER `host_vulnerability_DELETE` AFTER DELETE ON `host_vulnerability`
FOR EACH ROW BEGIN
    UPDATE host SET updated=utc_timestamp() WHERE id=OLD.host_id;
END$$
DELIMITER ;

--
-- Menu permissions
--
REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(10, 'MENU', 'environment-menu', 'PolicyHosts', 'Environment -> Assets -> Assets / Groups & Networks -> Groups', 1, 0, 1, '03.01'),
(11, 'MENU', 'environment-menu', 'PolicyNetworks', 'Environment -> Groups & Networks -> Networks & Network Groups', 0, 1, 1, '03.05'),
(13, 'MENU', 'environment-menu', 'PolicyNetworks', 'Environment -> Groups & Networks -> Groups', 0, 1, 1, '03.04'),
(42, 'MENU', 'environment-menu', 'ToolsScan', 'Environment -> Assets -> Asset Discovery', 0, 1, 1, '03.02'),
(55, 'MENU', 'configuration-menu', 'Osvdb', 'Configuration -> Threat Intelligence -> Knowledgebase', 0, 0, 1, '05.16'),
(14, 'MENU', 'configuration-menu', 'PolicyPorts', 'Configuration -> Threat Intelligence -> Ports/Port Groups', 0, 0, 1, '05.10'),
(15, 'MENU', 'configuration-menu', 'PolicyActions', 'Configuration -> Threat Intelligence -> Actions', 0, 0, 1, '05.09'),
(17, 'MENU', 'configuration-menu', 'PluginGroups', 'Configuration -> Threat Intelligence -> Data Source -> Manage Data Source Groups', 0, 0, 1, '05.15'),
(31, 'MENU', 'configuration-menu', 'CorrelationDirectives', 'Configuration -> Threat Intelligence -> Directives', 1, 1, 1, '05.11'),
(32, 'MENU', 'configuration-menu', 'CorrelationCrossCorrelation', 'Configuration -> Threat Intelligence -> Cross Correlation', 0, 0, 1, '05.13'),
(36, 'MENU', 'configuration-menu', 'ConfigurationPlugins', 'Configuration -> Threat Intelligence -> Data Source', 0, 0, 1, '05.14'),
(63, 'MENU', 'configuration-menu', 'ComplianceMapping', 'Configuration -> Threat Intelligence -> Compliance Mapping', 0, 0, 1, '05.12');
DELETE FROM `acl_perm` WHERE id in (18, 54, 64);

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2013-12-12');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.4.0');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
