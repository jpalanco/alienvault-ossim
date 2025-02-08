SET AUTOCOMMIT=0;
USE alienvault;

-- -----------------------------------------------------
-- Table `vuln_nessus_settings_sensor`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vuln_nessus_settings_sensor` (
  `sensor_id` BINARY(16) NOT NULL COMMENT 'a remote sensor',
  `vns_id` VARCHAR(32) NOT NULL  COMMENT 'ref to vuln_nessus_settings id',
  `sensor_gvm_config_id` VARCHAR(64) NOT NULL COMMENT 'gvm id of the vuln_nessus_settings created',
  PRIMARY KEY (`sensor_id`, `vns_id`, `sensor_gvm_config_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

UPDATE sensor_properties SET has_vuln_scanner = 0 where version = '';

ALTER TABLE `vuln_jobs` CHANGE COLUMN IF EXISTS `status` `status` CHAR(1) NOT NULL DEFAULT 'S' COMMENT 'values: C => Completed, D => Delayed (No available Scan Slots), F => Failed, S => Scheduled, H => Invalid Scan Config, R => Running, K => Kill, T => Timeout expired, I => Incomplete';


ALTER TABLE `vuln_jobs` CHANGE COLUMN IF EXISTS `meth_CPLUGINS` `task_id` TEXT;
ALTER TABLE `vuln_job_schedule` CHANGE COLUMN IF EXISTS `meth_CPLUGINS` `task_id` TEXT;

ALTER TABLE `vuln_jobs` CHANGE COLUMN IF EXISTS `meth_CRED` `only_alive_hosts` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `vuln_job_schedule` CHANGE COLUMN IF EXISTS `meth_CRED` `only_alive_hosts` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE `vuln_jobs` CHANGE COLUMN IF EXISTS `meth_Wfile` `send_email` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `vuln_job_schedule` CHANGE COLUMN IF EXISTS `meth_Wfile` `send_email` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE `vuln_jobs` CHANGE COLUMN IF EXISTS `meth_Ucheck` `scan_locally` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `vuln_job_schedule` CHANGE COLUMN IF EXISTS `meth_Ucheck` `scan_locally` TINYINT(1) NOT NULL DEFAULT '0';

-- UPGRADING  vuln_job_schedule
ALTER TABLE `vuln_job_schedule` CHANGE COLUMN IF EXISTS `meth_VSET` `set_id` INT(11);
ALTER TABLE `vuln_job_schedule` ADD COLUMN IF NOT EXISTS `profile_id` VARCHAR (32) NOT NULL;

UPDATE vuln_job_schedule vjs JOIN vuln_nessus_settings vns ON vjs.set_id = vns.id SET profile_id = md5(vns.name);

-- Move deprecated profiles (Default, Deep and Ultimate) to new ones (Full and Fast, Full and very deep and Full and very deep ultimate respectively)
UPDATE vuln_job_schedule SET profile_id = '708f25c4748911df8094002264764cea' where profile_id = '21d01af167e6874b88ed5f6dc7c4b1e4';
UPDATE vuln_job_schedule SET profile_id = 'daba56c873ec11dfa475002264764cea' where profile_id = '7a1920d61156abc05a60135aefe8bc67';
UPDATE vuln_job_schedule SET profile_id = '698f691e748911df9d8c002264764cea' where profile_id = 'e920866f008a763ab62f28a7d6a65aa8';

ALTER TABLE `vuln_job_schedule` DROP COLUMN IF EXISTS `set_id` ;

-- END OF UPGRADING vuln_job_schedule

-- UPGRADING vuln_jobs

ALTER TABLE `vuln_jobs` CHANGE COLUMN IF EXISTS `meth_VSET` `set_id` INT(11);
ALTER TABLE `vuln_jobs` ADD COLUMN IF NOT EXISTS `profile_id` VARCHAR (32) NOT NULL;

UPDATE vuln_jobs vj JOIN vuln_nessus_settings vns ON vj.set_id = vns.id SET profile_id = md5(vns.name);

ALTER TABLE `vuln_jobs` DROP COLUMN IF EXISTS `set_id` ;

-- END OF UPGRADING vuln_jobs

--  UPGRADING vuln_nessus_plugins

ALTER TABLE `vuln_nessus_plugins` CHANGE COLUMN IF EXISTS `bugtraq_id` `bugtraq_id` TEXT;
ALTER TABLE `vuln_nessus_plugins` CHANGE COLUMN IF EXISTS `cve_id` `cve_id` TEXT;
ALTER TABLE `vuln_nessus_plugins` CHANGE COLUMN IF EXISTS `summary` `summary` TEXT;
ALTER TABLE `vuln_nessus_plugins` CHANGE COLUMN IF EXISTS `created` `created` VARCHAR (32);
ALTER TABLE `vuln_nessus_plugins` CHANGE COLUMN IF EXISTS `modified` `modified` VARCHAR (32);
ALTER TABLE `vuln_nessus_plugins` CHANGE COLUMN IF EXISTS `category` `cat_id` INT(11);
ALTER TABLE `vuln_nessus_plugins` CHANGE COLUMN IF EXISTS `family` `fam_id` INT(11);
ALTER TABLE `vuln_nessus_plugins` ADD COLUMN IF NOT EXISTS `category` VARCHAR (32) NOT NULL;
ALTER TABLE `vuln_nessus_plugins` ADD COLUMN IF NOT EXISTS `family` VARCHAR (32) NOT NULL;
ALTER TABLE `vuln_nessus_plugins` ADD COLUMN IF NOT EXISTS `cvss_base_score` DECIMAL(3,1) NOT NULL;
ALTER TABLE `vuln_nessus_plugins` DROP COLUMN IF EXISTS `deleted` ;
ALTER TABLE `vuln_nessus_plugins` DROP COLUMN IF EXISTS `version` ;
ALTER TABLE `vuln_nessus_plugins` DROP COLUMN IF EXISTS `copyright` ;
ALTER TABLE `vuln_nessus_plugins` DROP COLUMN IF EXISTS `custom_risk` ;
ALTER TABLE `vuln_nessus_plugins` DROP COLUMN IF EXISTS `description` ;

UPDATE vuln_nessus_plugins vnp JOIN vuln_nessus_category vnc ON vnp.cat_id = vnc.id SET category = md5(vnc.name);
UPDATE vuln_nessus_plugins vnp JOIN vuln_nessus_family vnf ON vnp.fam_id = vnf.id SET family = md5(vnf.name);

ALTER TABLE `vuln_nessus_plugins` DROP COLUMN IF EXISTS `cat_id` ;
ALTER TABLE `vuln_nessus_plugins` DROP COLUMN IF EXISTS `fam_id` ;


-- END OF UPGRADING vuln_nessus_plugins

--  UPGRADING vuln_nessus_settings_category
DROP TABLE IF EXISTS vuln_nessus_settings_category;
-- END OF UPGRADING vuln_nessus_settings_category

-- UPGRADING vuln_nessus_latest_results
ALTER TABLE `vuln_nessus_latest_results` DROP INDEX `report_id`;
ALTER TABLE `vuln_nessus_latest_results` CHANGE COLUMN IF EXISTS `sid` `set_id` INT(11);
ALTER TABLE `vuln_nessus_latest_results` ADD COLUMN IF NOT EXISTS `sid` VARCHAR (32) NOT NULL;

UPDATE vuln_nessus_latest_results vnlr left join vuln_nessus_settings vns ON vnlr.set_id = vns.id   SET sid = md5(ifnull(vns.name, vnlr.set_id));
ALTER TABLE `vuln_nessus_latest_results` ADD KEY `report_id` (`username`, `sid`);

ALTER TABLE `vuln_nessus_latest_results` DROP COLUMN IF EXISTS `set_id` ;

-- END OF UPGRADING vuln_nessus_latest_results

-- UPGRADING vuln_nessus_reports
ALTER TABLE `vuln_nessus_reports` CHANGE COLUMN IF EXISTS `sid` `set_id` INT(11);
ALTER TABLE `vuln_nessus_reports` ADD COLUMN IF NOT EXISTS `sid` VARCHAR (32) NOT NULL;

UPDATE vuln_nessus_reports vnr left join vuln_nessus_settings vns ON vnr.set_id = vns.id   SET sid = md5(ifnull(vns.name, vnr.set_id));

ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `set_id` ;

-- END OF UPGRADING vuln_nessus_reports

--  UPGRADING vuln_nessus_latest_reports
ALTER TABLE `vuln_nessus_latest_reports` DROP PRIMARY KEY;
ALTER TABLE `vuln_nessus_latest_reports` CHANGE COLUMN IF EXISTS `sid` `set_id` INT(11);
ALTER TABLE `vuln_nessus_latest_reports` ADD COLUMN IF NOT EXISTS `sid` VARCHAR (32) NOT NULL;
UPDATE vuln_nessus_latest_reports vnr left join vuln_nessus_settings vns ON vnr.set_id = vns.id   SET sid = md5(ifnull(vns.name, vnr.set_id));
ALTER TABLE `vuln_nessus_latest_reports` ADD PRIMARY KEY (`hostIP`,`sid`,`username`,`ctx`);

ALTER TABLE `vuln_nessus_latest_reports` DROP COLUMN IF EXISTS `set_id` ;

-- END OF UPGRADING vuln_nessus_latest_reports

--  UPGRADING vuln_nessus_settings_plugins

ALTER TABLE `vuln_nessus_settings_plugins` CHANGE COLUMN IF EXISTS `family` `fam_id` INT(11);
ALTER TABLE `vuln_nessus_settings_plugins` CHANGE COLUMN IF EXISTS `category` `cat_id` INT(11);
ALTER TABLE `vuln_nessus_settings_plugins` CHANGE COLUMN IF EXISTS `sid` `set_id` INT(11);
ALTER TABLE `vuln_nessus_settings_plugins` ADD COLUMN IF NOT EXISTS `family` VARCHAR (32) NOT NULL;
ALTER TABLE `vuln_nessus_settings_plugins` ADD COLUMN IF NOT EXISTS `category` VARCHAR (32) NOT NULL;
ALTER TABLE `vuln_nessus_settings_plugins` ADD COLUMN IF NOT EXISTS `sid` VARCHAR (32) NOT NULL;

UPDATE vuln_nessus_settings_plugins vnsp left join vuln_nessus_family vnf ON vnsp.fam_id = vnf.id   SET family = md5(IFNULL(vnf.name, vnsp.fam_id));
UPDATE vuln_nessus_settings_plugins vnsp left join vuln_nessus_category vnc ON vnsp.cat_id = vnc.id   SET category = md5(IFNULL(vnc.name, vnsp.cat_id));
UPDATE vuln_nessus_settings_plugins vnsp left join vuln_nessus_settings vns ON vnsp.set_id = vns.id   SET sid = md5(IFNULL(vns.name, vnsp.set_id));

ALTER TABLE `vuln_nessus_settings_plugins` ADD key (sid);
ALTER TABLE `vuln_nessus_settings_plugins` ADD key (family, category);

ALTER TABLE `vuln_nessus_settings_plugins` DROP COLUMN IF EXISTS `fam_id` ;
ALTER TABLE `vuln_nessus_settings_plugins` DROP COLUMN IF EXISTS `cat_id` ;
ALTER TABLE `vuln_nessus_settings_plugins` DROP COLUMN IF EXISTS `set_id` ;

-- END OF UPGRADING vuln_nessus_settings_plugins

--  UPGRADING vuln_nessus_settings_family
ALTER TABLE `vuln_nessus_settings_family` DROP PRIMARY KEY;
ALTER TABLE `vuln_nessus_settings_family` CHANGE COLUMN IF EXISTS `fid` `fam_id` INT(11);
ALTER TABLE `vuln_nessus_settings_family` CHANGE COLUMN IF EXISTS `sid` `set_id` INT(11);
ALTER TABLE `vuln_nessus_settings_family` ADD COLUMN IF NOT EXISTS `fid` VARCHAR (32) NOT NULL;
ALTER TABLE `vuln_nessus_settings_family` ADD COLUMN IF NOT EXISTS `sid` VARCHAR (32) NOT NULL;

UPDATE vuln_nessus_settings_family vnsf left join vuln_nessus_family vnf ON vnsf.fam_id = vnf.id   SET fid = md5(IFNULL(vnf.name, vnsf.fam_id ));
UPDATE vuln_nessus_settings_family vnsf left join vuln_nessus_settings vns ON vnsf.set_id = vns.id   SET sid = md5(IFNULL(vns.name, vnsf.set_id ));
ALTER TABLE `vuln_nessus_settings_family` ADD PRIMARY key (sid, fid);

ALTER TABLE `vuln_nessus_settings_family` DROP COLUMN IF EXISTS `fam_id` ;
ALTER TABLE `vuln_nessus_settings_family` DROP COLUMN IF EXISTS `set_id` ;

UPDATE `vuln_nessus_settings_family` SET `status` = 2 WHERE `status` = 5;
UPDATE `vuln_nessus_settings_family` SET `status` = 3 WHERE `status` = 4;


-- END OF UPGRADING vuln_nessus_settings_family

--  UPGRADING vuln_nessus_settings_preferences

ALTER TABLE `vuln_nessus_settings_preferences` CHANGE COLUMN IF EXISTS `sid` `set_id` INT(11);
ALTER TABLE `vuln_nessus_settings_preferences` ADD COLUMN IF NOT EXISTS `sid` VARCHAR (32) NOT NULL;

UPDATE vuln_nessus_settings_preferences vnsp left join vuln_nessus_settings vns ON vnsp.set_id = vns.id   SET sid = md5(IFNULL(vns.name, vnsp.set_id));

ALTER TABLE `vuln_nessus_settings_preferences` DROP COLUMN IF EXISTS `set_id` ;

CREATE TABLE IF NOT EXISTS `vuln_nessus_settings_preferences_tmp` (
  `sid` VARCHAR (32) NOT NULL,
  `id` VARCHAR(255) NULL DEFAULT NULL,
  `nessus_id` VARCHAR(255) NOT NULL DEFAULT '',
  `value` TEXT CHARACTER SET 'latin1' COLLATE 'latin1_general_ci' NULL DEFAULT NULL,
  `category` VARCHAR(255) NULL DEFAULT NULL,
  `type` CHAR(1) NOT NULL DEFAULT '',
  PRIMARY KEY (`nessus_id`, `sid`))
ENGINE=InnoDB
DEFAULT CHARACTER SET = utf8;
TRUNCATE TABLE `vuln_nessus_settings_preferences_tmp`;

REPLACE INTO `vuln_nessus_settings_preferences_tmp` (`sid`, `id`, `nessus_id`,`value`, `category`, `type`)
  (select `sid`, `id`, `nessus_id`,`value`, `category`, `type` from vuln_nessus_settings_preferences group by sid, nessus_id);

TRUNCATE TABLE `vuln_nessus_settings_preferences`;

ALTER TABLE `vuln_nessus_settings_preferences` ADD PRIMARY KEY (`nessus_id`, `sid`);

REPLACE INTO `vuln_nessus_settings_preferences` (`sid`, `id`, `nessus_id`,`value`, `category`, `type`)  select `sid`, `id`, `nessus_id`,`value`, `category`, `type` from vuln_nessus_settings_preferences_tmp;
DROP TABLE `vuln_nessus_settings_preferences_tmp`;

-- END OF UPGRADING vuln_nessus_settings_preferences

-- UPGRADING vuln_nessus_settings
ALTER TABLE `vuln_nessus_settings` CHANGE COLUMN IF EXISTS `id` `sid` INT(11);
ALTER TABLE `vuln_nessus_settings` DROP PRIMARY KEY;
ALTER TABLE `vuln_nessus_settings` ADD COLUMN IF NOT EXISTS `id`  VARCHAR (32) NOT NULL;
ALTER TABLE `vuln_nessus_settings` ADD COLUMN IF NOT EXISTS `default` TINYINT(1) NOT NULL DEFAULT '0';

UPDATE vuln_nessus_settings SET id = md5(`name`), `default`= IF(sid <= '3', 1, 0);
ALTER TABLE `vuln_nessus_settings` ADD PRIMARY key (id);

ALTER TABLE `vuln_nessus_settings` DROP COLUMN IF EXISTS `type` ;
ALTER TABLE `vuln_nessus_settings` DROP COLUMN IF EXISTS `update_host_tracker` ;
ALTER TABLE `vuln_nessus_settings` DROP COLUMN IF EXISTS `sid` ;
ALTER TABLE `vuln_nessus_settings` DROP COLUMN IF EXISTS `auto_cat_status` ;
ALTER TABLE `vuln_nessus_settings` DROP COLUMN IF EXISTS `auto_fam_status` ;
ALTER TABLE `vuln_nessus_settings` DROP COLUMN IF EXISTS `autoenable` ;

-- END OF UPGRADING vuln_nessus_settings

--  UPGRADING vuln_nessus_category
ALTER TABLE `vuln_nessus_category` CHANGE COLUMN IF EXISTS `id` `cat_id` INT(11);
ALTER TABLE `vuln_nessus_category` DROP PRIMARY KEY;
ALTER TABLE `vuln_nessus_category` ADD COLUMN IF NOT EXISTS `id` VARCHAR (32) NOT NULL;
ALTER TABLE `vuln_nessus_category` ADD COLUMN IF NOT EXISTS `description` VARCHAR (512) NULL DEFAULT '';

UPDATE vuln_nessus_category  SET id = md5(`name`);
ALTER TABLE `vuln_nessus_category` ADD PRIMARY key (id);

ALTER TABLE `vuln_nessus_category` DROP COLUMN IF EXISTS `cat_id` ;

-- END OF UPGRADING vuln_nessus_category

--  UPGRADING vuln_nessus_family
ALTER TABLE `vuln_nessus_family` CHANGE COLUMN IF EXISTS `id` `fam_id` INT(11);
ALTER TABLE `vuln_nessus_family` DROP PRIMARY KEY;
ALTER TABLE `vuln_nessus_family` ADD COLUMN IF NOT EXISTS `id` VARCHAR (32) NOT NULL;

UPDATE vuln_nessus_family  SET id = md5(`name`);
ALTER TABLE `vuln_nessus_family` ADD PRIMARY key (id);

ALTER TABLE `vuln_nessus_family` DROP COLUMN IF EXISTS `fam_id` ;

-- END OF UPGRADING vuln_nessus_family

--  UPGRADING vuln_nessus_reports
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `cred_used`;
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `failed`;
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `note`;
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `report_path`;
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `results_sent`;
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `deleted`;
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `domain`;
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `server_ip`;
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `server_nversion`;
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `server_feedtype`;
ALTER TABLE `vuln_nessus_reports` DROP COLUMN IF EXISTS `server_feedversion`;

-- END OF UPGRADING vuln_nessus_reports

--  UPGRADING vuln_nessus_latest_reports
ALTER TABLE `vuln_nessus_latest_reports` DROP COLUMN IF EXISTS `server_ip`;
ALTER TABLE `vuln_nessus_latest_reports` DROP COLUMN IF EXISTS `server_nversion`;
ALTER TABLE `vuln_nessus_latest_reports` DROP COLUMN IF EXISTS `server_feedtype`;
ALTER TABLE `vuln_nessus_latest_reports` DROP COLUMN IF EXISTS `server_feedversion`;
ALTER TABLE `vuln_nessus_latest_reports` DROP INDEX IF EXISTS `deleted`;
ALTER TABLE `vuln_nessus_latest_reports` DROP COLUMN IF EXISTS `deleted`;
ALTER TABLE `vuln_nessus_latest_reports` ADD INDEX `result_sent` (`results_sent` ASC);

ALTER TABLE `vuln_nessus_latest_reports` DROP COLUMN IF EXISTS `cred_used`;
ALTER TABLE `vuln_nessus_latest_reports` DROP COLUMN IF EXISTS `domain`;
ALTER TABLE `vuln_nessus_latest_reports` DROP COLUMN IF EXISTS `report_path`;

-- END OF UPGRADING vuln_nessus_latest_reports


--  UPGRADING vuln_nessus_servers

ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `site_code`;
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `server_nversion`;
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `server_feedtype`;
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `server_feedversion`;
ALTER TABLE `vuln_nessus_servers` DROP COLUMN IF EXISTS `checkin_time`;

UPDATE vuln_nessus_servers SET max_scans = 3 WHERE max_scans >= 3;

-- END OF UPGRADING vuln_nessus_servers

--  UPGRADING vuln_nessus_preferences_defaults
ALTER TABLE `vuln_nessus_preferences_defaults` CHANGE COLUMN IF EXISTS `value` `value` TEXT;
ALTER TABLE `vuln_nessus_preferences_defaults` ADD COLUMN IF NOT EXISTS `id` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `vuln_nessus_preferences_defaults` ADD COLUMN IF NOT EXISTS `gvm_id` INT(11) DEFAULT NULL;

-- END OF UPGRADING vuln_nessus_preferences_defaults

--  UPGRADING vuln_nessus_preferences
ALTER TABLE `vuln_nessus_preferences` CHANGE COLUMN IF EXISTS `value` `value` TEXT;
-- END OF UPGRADING vuln_nessus_preferences


-- Removed deprecated Nessus tables
DROP TABLE IF EXISTS vuln_nessus_category_feed, vuln_nessus_family_feed, vuln_nessus_plugins_feed;


UPDATE host_source_reference set name = 'GVM' where id=7;

UPDATE config SET conf = 'gvm_host' where conf = 'nessus_host';
UPDATE config SET conf = 'gvm_pre_scan_locally' where conf = 'nessus_pre_scan_locally';
UPDATE config SET conf = 'gvm_rpt_path' where conf = 'nessus_rpt_path';
UPDATE config SET conf = 'gvm_path', value='/usr/bin/gvm-cli' where conf = 'nessus_path';

DELETE FROM config where conf IN ('nessusrc_path',
                                  'nessus_admin_pass',
                                  'nessus_admin_user',
                                  'nessus_distributed',
                                  'nessus_pass',
                                  'nessus_port',
                                  'nessus_updater_path',
                                  'nessus_user');

DELIMITER $$

DROP TRIGGER IF EXISTS host_services_RENAME $$
CREATE TRIGGER `host_services_RENAME` BEFORE INSERT ON `host_services` FOR EACH ROW
BEGIN
  IF NEW.port=40001 AND NEW.service='unknown' THEN
    SET NEW.service = IF(is_pro(),'usm server','ossim server');
  ELSEIF NEW.port=1241 AND NEW.service='unknown' THEN
    SET NEW.service = 'nessus';
  ELSEIF NEW.port=9390 AND (NEW.service='otp' OR NEW.service='unknown' OR NEW.service='OpenVAS' OR NEW.service='GVM' OR NEW.service='unknown-ssl') THEN
    SET NEW.service = 'gvmd';
  END IF;

  IF NEW.service='ossim server' THEN
    SET NEW.version = 'Open Source Security Information Management server';
  ELSEIF NEW.service='usm server' THEN
    SET NEW.version = 'Unified Security Management server';
  ELSEIF NEW.service='gvmd' THEN
    SET NEW.version = 'GVM 11';
  END IF;
END
$$

DROP PROCEDURE IF EXISTS sensor_update$$
CREATE PROCEDURE sensor_update(
  user VARCHAR(64),
  uuid VARCHAR(64),
  ip VARCHAR(15),
  name VARCHAR(64),
  prio INT,
  port INT,
  tzone FLOAT,
  descr VARCHAR(255),
  ctxs TEXT,
  version VARCHAR(64),
  ntop INT,
  vuln INT,
  ids INT,
  passive_inventory INT,
  netflows INT
)
SENSOR:BEGIN
  DECLARE x INT;
  DECLARE y INT;

  -- needed params
  IF INET6_ATON(ip) IS NOT NULL AND NOT user = '' THEN

    -- check params
    SELECT IF (uuid='',UPPER(REPLACE(UUID(), '-', '')),UPPER(uuid)) into @uuid;
    SET @ip = HEX(INET6_ATON(ip));
    SELECT IF (name='','(null)',name) into @name;
    SELECT IF (prio<1 OR prio>10,5,prio) into @prio;
    SELECT IF (port<1 OR port>65535,40001,port) into @port;
    SET @tzone = tzone;
    SET @descr = descr;
    SET @ctxs = ctxs;
    SET @version = version;
    SELECT IF (ntop<0 OR ntop>1,1,ntop) into @ntop;
    SELECT IF (vuln<0 OR vuln>1,1,vuln) into @vuln;
    SELECT IF (ids<0 OR ids>1,0,ids) into @ids;
    SELECT IF (passive_inventory<0 OR passive_inventory>1,0,passive_inventory) into @passive_inventory;
    SELECT IF (netflows<0 OR netflows>1,0,netflows) into @netflows;
    SELECT EXISTS (SELECT 1 from user_ctx_perm where login = user) INTO @get_ctx_where;
    SELECT `value` FROM config WHERE conf='encryption_key' into @system_uuid;
    SELECT `value` FROM config WHERE conf='gvm_host' into @gvm_host;

    -- check if exists with permissions
    IF @get_ctx_where THEN
      SELECT HEX(sensor.id),sensor.name FROM sensor, acl_sensors WHERE sensor.id=acl_sensors.sensor_id AND acl_sensors.entity_id in (SELECT ctx from user_ctx_perm where login = user) AND sensor.ip = UNHEX(@ip) INTO @sensor_id, @sensor_name;
    ELSE
      SELECT HEX(sensor.id),sensor.name FROM sensor WHERE sensor.ip = UNHEX(@ip) INTO @sensor_id, @sensor_name;
    END IF;

    -- already exists
    IF NOT @sensor_id = '' THEN
      IF NOT UPPER(@uuid) = UPPER(@sensor_id) THEN
        IF NOT @sensor_name = '(null)' THEN
          SELECT CONCAT('Sensor ',ip,' already exists with different uuid') as status, NULL as sensor_id;
          LEAVE SENSOR;
        END IF;
      ELSE
        -- Update existing
        SET @uuid = @sensor_id;
      END IF;
    END IF;

    -- insert
    SET @query = 'REPLACE INTO sensor (id, name, ip, priority, port, tzone, connect, descr) VALUES (UNHEX(?), ?, UNHEX(?), ?, ?, ?, 0, ?)';
    PREPARE stmt1 FROM @query;
    EXECUTE stmt1 USING @uuid, @name, @ip, @prio, @port, @tzone, @descr;
    DEALLOCATE PREPARE stmt1;

    SET @query = 'INSERT IGNORE INTO sensor_stats (sensor_id) VALUES (UNHEX(?))';
    PREPARE stmt1 FROM @query;
    EXECUTE stmt1 USING @uuid;
    DEALLOCATE PREPARE stmt1;

    SET @query = 'REPLACE INTO sensor_properties (sensor_id,version,has_ntop,has_vuln_scanner,ids,passive_inventory,netflows) VALUES (UNHEX(?), ?, ?, ?, ?, ?, ?)';

    PREPARE stmt1 FROM @query;
    EXECUTE stmt1 USING @uuid, @version, @ntop, @vuln, @ids, @passive_inventory, @netflows;
    DEALLOCATE PREPARE stmt1;

    -- Contexts
    IF @ctxs = '' THEN
      -- get default context
      SELECT UPPER(REPLACE(`value`,'-','')) FROM config WHERE conf='default_context_id' into @ctxs;
    END IF;
    DELETE FROM acl_sensors where sensor_id = UNHEX(@uuid) and entity_id IN (SELECT id FROM acl_entities WHERE entity_type='context');
    SELECT LENGTH(@ctxs) - LENGTH(REPLACE(@ctxs, ',', '')) INTO @nCommas;
    SET y = 1;
    SET x = @nCommas + 1;
    WHILE y <= x DO
    SELECT _split_string(@ctxs, ',', y) INTO @range;
    SET @query = 'REPLACE INTO acl_sensors (entity_id, sensor_id) VALUES (UNHEX(?), UNHEX(?))';
    PREPARE stmt1 FROM @query;
    EXECUTE stmt1 USING @range, @uuid;
    DEALLOCATE PREPARE stmt1;
    SET  y = y + 1;
    END WHILE;

    -- has_vuln_scanner?
    IF @vuln AND NOT EXISTS(SELECT 1 FROM vuln_nessus_servers WHERE hostname=@uuid) THEN
      SET @query = 'REPLACE INTO vuln_nessus_servers (name , description, hostname, port, user, PASSWORD, max_scans, current_scans, TYPE, owner, status, enabled) VALUES (?, "RemoteHost", ?, 9390, "ossim", AES_ENCRYPT("ossim",?), 5, 0, "R", "admin", "A", 1)';
      PREPARE stmt1 FROM @query;
      EXECUTE stmt1 USING @name, @uuid, @system_uuid;
      DEALLOCATE PREPARE stmt1;
    END IF;

    -- Unique sensor
    IF NOT EXISTS(SELECT 1 FROM sensor WHERE id != UNHEX(@uuid) AND name != '(null)') THEN

      CALL _orphans_of_sensor(@uuid);

    END IF;

    -- Default nessus host
    IF @gvm_host = '' THEN
      REPLACE INTO config VALUES ('gvm_host', INET6_NTOA(UNHEX(@ip)));
    END IF;

    -- Check if a asset exists
    IF NOT EXISTS(SELECT 1 FROM host h, host_ip hi WHERE h.id=hi.host_id AND hi.ip=UNHEX(@ip)) THEN
      SELECT REPLACE(UUID(), '-', '') into @asset_id;
      INSERT IGNORE INTO host (id, ctx, hostname, asset, threshold_c, threshold_a, alert, persistence, nat, rrd_profile, descr, lat, lon, av_component) VALUES (UNHEX(@asset_id), UNHEX(@range), @name, '2', '30', '30', '0', '0', '', '', '', '0', '0', '1');
      INSERT IGNORE INTO host_ip (host_id,ip) VALUES (UNHEX(@asset_id), UNHEX(@ip));
      INSERT IGNORE INTO host_sensor_reference (host_id,sensor_id) VALUES (UNHEX(@asset_id), UNHEX(@uuid));
    ELSE
      INSERT IGNORE INTO host_sensor_reference (host_id,sensor_id) VALUES ((SELECT h.id FROM host h, host_ip hi WHERE h.id=hi.host_id AND hi.ip=UNHEX(@ip)), UNHEX(@uuid));
      UPDATE host h, host_ip hi SET hostname=@name WHERE h.id=hi.host_id AND hi.ip=UNHEX(@ip);
    END IF;

    -- Clean Orphans
    DELETE aux FROM sensor_stats aux LEFT JOIN sensor s ON s.id=aux.sensor_id WHERE s.id IS NULL;
    DELETE aux FROM sensor_properties aux LEFT JOIN sensor s ON s.id=aux.sensor_id WHERE s.id IS NULL;
    DELETE aux FROM acl_sensors aux LEFT JOIN sensor s ON s.id=aux.sensor_id WHERE s.id IS NULL;
    DELETE aux FROM vuln_nessus_servers aux LEFT JOIN sensor s ON s.id=UNHEX(aux.hostname) WHERE s.id IS NULL;

    CALL _host_default_os();

    SELECT CONCAT('Sensor successfully updated') as status, @uuid as sensor_id;

  ELSE

    SELECT CONCAT('Invalid IP or User values') as status, NULL as sensor_id;

  END IF;

END$$

DELIMITER ;


REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2021-04-26');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.8.7');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
