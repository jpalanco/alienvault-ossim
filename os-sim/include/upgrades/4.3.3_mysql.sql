USE alienvault;
SET AUTOCOMMIT=0;
BEGIN;

DELETE FROM user_config where name like 'servers_layout';

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'alarm_taxonomy' AND COLUMN_NAME = 'engine_id')
  THEN
        ALTER TABLE `alarm_taxonomy` ADD `engine_id` binary(16) NOT NULL DEFAULT 0x0 AFTER `sid`;
        ALTER TABLE `alarm_taxonomy` DROP PRIMARY KEY , ADD PRIMARY KEY ( `sid`, `engine_id`);
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'host_agentless' AND COLUMN_NAME = 'use_su')
  THEN
        ALTER TABLE `host_agentless` ADD `use_su` TINYINT(1) NOT NULL DEFAULT '0' AFTER `ppass`;
        UPDATE `host_agentless` SET `use_su`=1 WHERE `ppass` IS NOT NULL;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

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

REPLACE INTO config (conf, value) VALUES ('last_update', '2013-09-24');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.3.3');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
