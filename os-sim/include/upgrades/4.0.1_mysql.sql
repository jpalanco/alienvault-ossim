USE alienvault;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE `incident` CHANGE `ref` `ref` ENUM( 'Alarm', 'Alert', 'Event', 'Metric', 'Anomaly', 'Vulnerability', 'Custom' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'Alarm';

ALTER TABLE `alienvault`.`incident_custom_types` DROP PRIMARY KEY, ADD PRIMARY KEY ( `id` , `name` );

INSERT IGNORE INTO `tags_alarm` VALUES (1,'\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','Analysis in Progress','ffe3e3','cc0000',0,0),(2,'\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','False Positive','dee5f2','5a6986',0,0);

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'backlog' AND COLUMN_NAME = 'last')
  THEN
      ALTER TABLE `backlog` CHANGE `timestamp` `timestamp` DATETIME NULL DEFAULT NULL;      
      ALTER TABLE `backlog` ADD `last` DATETIME NULL DEFAULT NULL AFTER `timestamp`;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

REPLACE INTO config (conf, value) VALUES ('last_update', '2012-07-06');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.0.1');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
