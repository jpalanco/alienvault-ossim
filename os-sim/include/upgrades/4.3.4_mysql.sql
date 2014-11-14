SET AUTOCOMMIT=0;
BEGIN;

USE alienvault_siem;

-- Insert device id=0 for directive events
DELETE FROM device WHERE id = 0;
REPLACE INTO device (id, device_ip, interface, sensor_id) VALUES (999999, 0x0, '', 0x0);
UPDATE device SET id = 0 WHERE id = 999999;

DROP TRIGGER IF EXISTS `del_count_acid_event`;

DELIMITER $$

CREATE TRIGGER `del_count_acid_event` AFTER DELETE ON `acid_event` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN

  UPDATE ac_acid_event SET cnt = cnt - 1

   WHERE ctx = OLD.ctx AND device_id = OLD.device_id AND plugin_id = OLD.plugin_id AND plugin_sid = OLD.plugin_sid
     AND day = DATE(OLD.timestamp)
     AND src_host = IFNULL(OLD.src_host, 0x00000000000000000000000000000000)
     AND dst_host = IFNULL(OLD.dst_host, 0x00000000000000000000000000000000)
     AND src_net = IFNULL(OLD.src_net, 0x00000000000000000000000000000000)
     AND dst_net = IFNULL(OLD.dst_net, 0x00000000000000000000000000000000) AND cnt > 0;

END
$$

DELIMITER ;

USE alienvault;

-- Taxonomy for new directives
REPLACE INTO alarm_taxonomy (sid, kingdom, category, subcategory) VALUES 
(41070,5,41,'Kelihos'),(41071,5,41,'Kelihos'),
(45004,3,51,'Exploit kit'),(45005,3,51,'Exploit kit'),
(29011,5,51,'Exploit kit'),(29012,5,51,'Exploit kit');

-- Clone Taxonomy for all enginges
DROP PROCEDURE IF EXISTS clone_taxonomy;
DELIMITER $$
CREATE PROCEDURE clone_taxonomy()
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
END;
$$
DELIMITER ;
CALL clone_taxonomy();
DROP PROCEDURE clone_taxonomy;

DELETE FROM `acl_perm` WHERE id=54;

-- Vuln Nessus Plugins feed tables
CREATE TABLE IF NOT EXISTS `vuln_nessus_plugins_feed` ( 
  `id` INT(11) NOT NULL DEFAULT '0',
  `oid` VARCHAR(50) NOT NULL DEFAULT '',
  `name` VARCHAR(255) NULL DEFAULT NULL,
  `copyright` VARCHAR(255) NULL DEFAULT NULL,
  `summary` VARCHAR(255) NULL DEFAULT NULL,  
  `description` BLOB NULL DEFAULT NULL,
  `cve_id` VARCHAR(255) NULL DEFAULT NULL,
  `bugtraq_id` VARCHAR(255) NULL DEFAULT NULL,
  `xref` BLOB NULL DEFAULT NULL,
  `enabled` CHAR(1) NOT NULL DEFAULT '',
  `version` VARCHAR(255) NULL DEFAULT NULL,
  `created` VARCHAR(14) NULL DEFAULT NULL, 
  `modified` VARCHAR(14) NULL DEFAULT NULL,
  `deleted` VARCHAR(14) NULL DEFAULT NULL, 
  `category` INT(11) NOT NULL DEFAULT '0', 
  `family` INT(11) NOT NULL DEFAULT '0',   
  `risk` INT(11) NOT NULL DEFAULT '0',     
  `custom_risk` INT(1) NULL DEFAULT NULL,  
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `vuln_nessus_family_feed` (  
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `nname` (`name` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `vuln_nessus_category_feed` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `nname` (`name` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

REPLACE INTO config (conf, value) VALUES ('last_update', '2013-10-28');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.3.4');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
