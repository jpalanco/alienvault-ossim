USE alienvault;
SET AUTOCOMMIT=0;
BEGIN;

-- Alienvault webservice
CREATE TABLE IF NOT EXISTS `alienvault`.`webservice` (
    `id`     binary(16) NOT NULL,
    `ctx`    binary(16) NOT NULL,
    `name`   varchar(64) NOT NULL,
    `descr`  varchar(256) NOT NULL,
    `type`   varchar(32) NOT NULL,
    `source` ENUM('ticket') NOT NULL,
    `url`    varchar(256) NOT NULL,
    `namespace` varchar(64) NOT NULL,
    `user`   varchar(64) NOT NULL,
    `pass`   varchar(64) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = INNODB DEFAULT CHARSET=utf8;

-- Alienvault webservice_operation
CREATE TABLE IF NOT EXISTS `alienvault`.`webservice_operation` (
    `ws_id`  BINARY(16) NOT NULL ,
    `op`     varchar(64) NOT NULL,
    `attrs`  varchar(512) NOT NULL,
    `type`   ENUM('insert','query','update','delete','auth') NOT NULL,
    PRIMARY KEY (`ws_id`,`op`)
) ENGINE = INNODB DEFAULT CHARSET=utf8;

-- Alienvault webservice_default
CREATE TABLE IF NOT EXISTS `webservice_default` (
  `ws_id` BINARY(16) NOT NULL ,
  `field` VARCHAR(64) NOT NULL ,
  `value` VARCHAR(512) NOT NULL ,
  PRIMARY KEY (`ws_id`, `field`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- Inventory task_targets
DROP PROCEDURE IF EXISTS inventory_targets_update; 
DELIMITER $$
CREATE PROCEDURE inventory_targets_update()
BEGIN
  DECLARE c_end INTEGER DEFAULT 0;
  DECLARE c_tak_id INT;
  DECLARE c_net_id VARCHAR(32);

  DECLARE inventory_target CURSOR FOR SELECT DISTINCT t.task_id, HEX(nc.net_id) FROM task_inventory t, net_cidrs nc, net_sensor_reference ns WHERE nc.net_id=ns.net_id AND ns.sensor_id=t.task_sensor AND t.task_params LIKE CONCAT('%', nc.cidr ,'%');

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET c_end=1;

  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'task_inventory' AND COLUMN_NAME = 'task_targets')
  THEN
      ALTER TABLE  `task_inventory` ADD  `task_targets` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';
  END IF;
  
  OPEN inventory_target;

  UPDATE task_inventory SET task_targets ='';

  C_LOOP:REPEAT

    IF c_end THEN

      LEAVE C_LOOP;
    
    ELSE

      SET c_net_id = '';
      SET c_tak_id = 0;

      FETCH inventory_target INTO c_tak_id, c_net_id;

      UPDATE task_inventory SET task_targets = CONCAT(task_targets, c_net_id, ' ')  WHERE  task_id=c_tak_id;

    END IF;

  UNTIL c_end END REPEAT C_LOOP;

  CLOSE inventory_target;

END$$

DELIMITER ;
CALL inventory_targets_update();
DROP PROCEDURE IF EXISTS inventory_targets_update;


-- Removing source and dest in policies for 1505
DROP PROCEDURE IF EXISTS update_policy_targets; 
DELIMITER $$
CREATE PROCEDURE update_policy_targets()
BEGIN
    DECLARE policies_to_update INT DEFAULT 0;

    DROP TABLE IF EXISTS policy_to_delete;

    CREATE TEMPORARY TABLE policy_to_delete (
        id varbinary(16) NOT NULL PRIMARY KEY
    );

    INSERT INTO policy_to_delete (SELECT DISTINCT p.id FROM policy p, acl_entities e WHERE p.ctx = e.id AND entity_type='engine');

    SELECT COUNT(*) INTO @policies_to_update FROM policy_to_delete;

    IF @policies_to_update > 0 
    THEN 
        #DELETING ALL OLD REFERENCES
        DELETE FROM policy_host_reference WHERE policy_id IN (SELECT id FROM policy_to_delete);
        DELETE FROM policy_host_group_reference WHERE policy_id IN (SELECT id FROM policy_to_delete);
        DELETE FROM policy_net_reference WHERE policy_id IN (SELECT id FROM policy_to_delete);
        DELETE FROM policy_net_group_reference WHERE policy_id IN (SELECT id FROM policy_to_delete);
        DELETE FROM policy_port_reference WHERE policy_id IN (SELECT id FROM policy_to_delete);
        DELETE FROM policy_sensor_reference WHERE policy_id IN (SELECT id FROM policy_to_delete);

        #ADDING DEFAULT REFERENCES

        #Hosts
        REPLACE INTO policy_host_reference SELECT id, 0x0, 'source' from policy_to_delete;
        REPLACE INTO policy_host_reference SELECT id, 0x0, 'dest' from policy_to_delete;

        #Ports
        REPLACE INTO policy_port_reference SELECT id, 0x0, 'source' from policy_to_delete;
        REPLACE INTO policy_port_reference SELECT id, 0x0, 'dest' from policy_to_delete;

        #Sensors
        REPLACE INTO policy_sensor_reference SELECT id, 0x0 from policy_to_delete;

    END IF;

    DROP TABLE policy_to_delete;

END$$

DELIMITER ;
CALL update_policy_targets();
DROP PROCEDURE IF EXISTS update_policy_targets;


-- Modifying media field to mediumblob to avoid images truncated.
ALTER TABLE  `dashboard_widget_config` CHANGE  `media`  `media` MEDIUMBLOB NULL DEFAULT NULL;

-- New table for plugins restore
CREATE TABLE IF NOT EXISTS `plugin_sid_orig` (
  `plugin_ctx` BINARY(16) NOT NULL ,
  `plugin_id` INT NOT NULL ,
  `sid` INT NOT NULL ,
  `class_id` INT(11) NULL DEFAULT NULL ,
  `reliability` INT(11) NULL DEFAULT '1' ,
  `priority` INT(11) NULL DEFAULT '1' ,
  `name` VARCHAR(255) NOT NULL ,
  `aro` DECIMAL(11,4) NOT NULL DEFAULT '0.0000' ,
  `subcategory_id` INT(11) NULL DEFAULT NULL ,
  `category_id` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`plugin_ctx`, `plugin_id`, `sid`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

REPLACE INTO config (conf, value) VALUES ('last_update', '2013-05-07');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.2.1');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
