USE alienvault;
SET AUTOCOMMIT=0;
BEGIN;

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_jobs' AND COLUMN_NAME = 'credentials')
  THEN
      ALTER TABLE `vuln_jobs` ADD `credentials` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_job_schedule' AND COLUMN_NAME = 'credentials')
  THEN
      ALTER TABLE `vuln_job_schedule` ADD `credentials` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
  END IF;  
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;


REPLACE INTO config (conf, value) VALUES ('last_update', '2012-09-21');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.0.4');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
