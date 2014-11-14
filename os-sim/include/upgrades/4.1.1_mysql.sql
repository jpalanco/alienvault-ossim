USE alienvault;
SET AUTOCOMMIT=0;

-- User Sensor Perms
CREATE TABLE IF NOT EXISTS `acl_login_sensors` (
  `login` varchar(64) NOT NULL,
  `sensor_id` binary(16) NOT NULL,
  PRIMARY KEY (`login`,`sensor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `policy_forward_reference` DROP PRIMARY KEY, ADD PRIMARY KEY (`policy_id`, `child_id`, `parent_id`);
UPDATE alarm SET removable=0;

REPLACE INTO config (conf, value) VALUES ('last_update', '2012-11-26');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.1.1');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
