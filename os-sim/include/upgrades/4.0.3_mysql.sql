USE alienvault;
SET AUTOCOMMIT=0;
BEGIN;

USE PCI;
REPLACE INTO `R06_System_app` (`x1`, `x2`, `x3`, `x4`, `Security_control`, `operational`, `not_operational`, `comments`, `SIDSS_ref`, `testing_procedures`) VALUES ('R', '6', '5', '10', 'Failure to restrict URL access', '1', NULL, NULL, NULL, '<b>6.5.10</b> Failure to restrict URL access');
USE alienvault;

ALTER TABLE  `user_config` CHANGE  `name`  `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

REPLACE INTO config (conf, value) VALUES ('last_update', '2012-08-27');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.0.3');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
