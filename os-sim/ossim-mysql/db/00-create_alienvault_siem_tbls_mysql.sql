SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_ALL_TABLES,ALLOW_INVALID_DATES';


-- -----------------------------------------------------
-- Table `device`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `device` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_ip` VARBINARY(16) NULL DEFAULT NULL,
  `interface` VARCHAR(32) NULL DEFAULT NULL,
  `sensor_id` BINARY(16) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `sensor_ip` (`sensor_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `extra_data`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `extra_data` (
  `event_id` BINARY(16) NOT NULL,
  `filename` VARCHAR(256) NULL DEFAULT NULL,
  `username` VARCHAR(64) NULL DEFAULT NULL,
  `password` VARCHAR(64) NULL DEFAULT NULL,
  `userdata1` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata2` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata3` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata4` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata5` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata6` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata7` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata8` VARCHAR(1024) NULL DEFAULT NULL,
  `userdata9` VARCHAR(1024) NULL DEFAULT NULL,
  `data_payload` TEXT NULL DEFAULT NULL,
  `binary_data` BLOB NULL DEFAULT NULL,
  PRIMARY KEY (`event_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `ac_acid_event`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ac_acid_event` (
  `ctx` BINARY(16) NOT NULL,
  `device_id` INT UNSIGNED NOT NULL,
  `plugin_id` INT UNSIGNED NOT NULL,
  `plugin_sid` INT UNSIGNED NOT NULL,
  `day` DATE NOT NULL,
  `src_host` BINARY(16) NOT NULL DEFAULT 0x0,
  `dst_host` BINARY(16) NOT NULL DEFAULT 0x0,
  `src_net` BINARY(16) NOT NULL DEFAULT 0x0,
  `dst_net` BINARY(16) NOT NULL DEFAULT 0x0,
  `cnt` INT UNSIGNED NOT NULL DEFAULT 0,
  INDEX `day` (`day` ASC),
  INDEX `plugin_id` (`plugin_id` ASC),
  PRIMARY KEY (`ctx`, `device_id`, `plugin_id`, `plugin_sid`, `day`, `src_host`, `dst_net`, `dst_host`, `src_net`),
  INDEX `src_host` (`src_host` ASC),
  INDEX `dst_host` (`dst_host` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `acid_event`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `acid_event` (
  `id` BINARY(16) NOT NULL,
  `device_id` INT UNSIGNED NOT NULL,
  `ctx` BINARY(16) NOT NULL DEFAULT 0x0,
  `timestamp` DATETIME NOT NULL,
  `ip_src` VARBINARY(16) NULL DEFAULT NULL,
  `ip_dst` VARBINARY(16) NULL DEFAULT NULL,
  `ip_proto` INT NULL DEFAULT NULL,
  `layer4_sport` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `layer4_dport` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `ossim_priority` TINYINT NULL DEFAULT '1',
  `ossim_reliability` TINYINT NULL DEFAULT '1',
  `ossim_asset_src` TINYINT NULL DEFAULT '1',
  `ossim_asset_dst` TINYINT NULL DEFAULT '1',
  `ossim_risk_c` TINYINT NULL DEFAULT '1',
  `ossim_risk_a` TINYINT NULL DEFAULT '1',
  `plugin_id` INT UNSIGNED NULL DEFAULT NULL,
  `plugin_sid` INT UNSIGNED NULL DEFAULT NULL,
  `tzone` FLOAT NOT NULL DEFAULT '0',
  `ossim_correlation` TINYINT NULL DEFAULT '0',
  `src_hostname` VARCHAR(64) NULL DEFAULT NULL,
  `dst_hostname` VARCHAR(64) NULL DEFAULT NULL,
  `src_mac` BINARY(6) NULL DEFAULT NULL,
  `dst_mac` BINARY(6) NULL DEFAULT NULL,
  `src_host` BINARY(16) NULL DEFAULT NULL,
  `dst_host` BINARY(16) NULL DEFAULT NULL,
  `src_net` BINARY(16) NULL DEFAULT NULL,
  `dst_net` BINARY(16) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `timestamp` (`timestamp` ASC),
  INDEX `layer4_sport` (`layer4_sport` ASC),
  INDEX `layer4_dport` (`layer4_dport` ASC),
  INDEX `ip_src` (`ip_src` ASC),
  INDEX `ip_dst` (`ip_dst` ASC),
  INDEX `acid_event_ossim_priority` (`ossim_priority` ASC),
  INDEX `acid_event_ossim_risk_a` (`ossim_risk_a` ASC),
  INDEX `acid_event_ossim_reliability` (`ossim_reliability` ASC),
  INDEX `acid_event_ossim_risk_c` (`ossim_risk_c` ASC),
  INDEX `plugin` (`plugin_id` ASC, `plugin_sid` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `reputation_data`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `reputation_data` (
  `event_id` BINARY(16) NOT NULL,
  `rep_ip_src` VARBINARY(16) NULL DEFAULT NULL,
  `rep_ip_dst` VARBINARY(16) NULL DEFAULT NULL,
  `rep_prio_src` TINYINT UNSIGNED NULL DEFAULT NULL,
  `rep_prio_dst` TINYINT UNSIGNED NULL DEFAULT NULL,
  `rep_rel_src` TINYINT UNSIGNED NULL DEFAULT NULL,
  `rep_rel_dst` TINYINT UNSIGNED NULL DEFAULT NULL,
  `rep_act_src` VARCHAR(64) NULL DEFAULT NULL,
  `rep_act_dst` VARCHAR(64) NULL DEFAULT NULL,
  PRIMARY KEY (`event_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `last_update`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `last_update` (
  `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `reference_system`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `reference_system` (
  `ref_system_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_system_name` VARCHAR(20) NULL DEFAULT NULL,
  `icon` MEDIUMBLOB NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`ref_system_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `reference`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `reference` (
  `ref_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_system_id` INT(10) UNSIGNED NOT NULL,
  `ref_tag` TEXT NOT NULL,
  PRIMARY KEY (`ref_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `schema`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `schema` (
  `vseq` INT(10) UNSIGNED NOT NULL,
  `ctime` DATETIME NOT NULL,
  PRIMARY KEY (`vseq`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sig_reference`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sig_reference` (
  `plugin_id` INT(11) NOT NULL,
  `plugin_sid` INT(11) NOT NULL,
  `ref_id` INT(10) UNSIGNED NOT NULL,
  `ctx` BINARY(16) NOT NULL,
  PRIMARY KEY (`plugin_id`, `plugin_sid`, `ref_id`, `ctx`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `idm_data`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `idm_data` (
  `event_id` BINARY(16) NOT NULL,
  `username` VARCHAR(64) NULL DEFAULT NULL,
  `domain` VARCHAR(64) NULL DEFAULT NULL,
  `from_src` TINYINT(1) NULL DEFAULT NULL,
  INDEX `event_id` (`event_id` ASC),
  INDEX `usrdmn` (`username` ASC, `domain` ASC),
  INDEX `domain` (`domain` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


DELIMITER $$


CREATE TRIGGER `count_acid_event` AFTER INSERT ON `acid_event` FOR EACH ROW
-- Edit trigger body code below this line. Do not edit lines above this one
BEGIN

  INSERT IGNORE INTO ac_acid_event (ctx, device_id, plugin_id, plugin_sid, day, src_host, dst_host, src_net, dst_net, cnt)

    VALUES (NEW.ctx, NEW.device_id, NEW.plugin_id, NEW.plugin_sid, DATE(NEW.timestamp), NEW.src_host, NEW.dst_host, NEW.src_net, NEW.dst_net, 1)

  ON DUPLICATE KEY UPDATE cnt = cnt + 1;

END
$$



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
