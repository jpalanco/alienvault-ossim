-- -----------------------------------------------------
-- Table `suggestions`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `suggestions` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `suggestion_group_id` BINARY(16) NOT NULL ,
  `filename` VARCHAR(255) NOT NULL ,
  `location` VARCHAR(255) NOT NULL ,
  `datetime` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `alarm_coincidence`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `alarm_coincidence` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `data` TEXT NOT NULL ,
  `sample_log` TEXT NOT NULL ,
  `sensor_id` BINARY(16) NOT NULL ,
  `datetime` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Table to store the asec alarms.' ;


-- -----------------------------------------------------
-- Table `notification`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `notification` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `plugin_id` INT(11)  NOT NULL ,
  `rule_name` VARCHAR(45) NOT NULL ,
  `log_file` VARCHAR(45) NOT NULL ,
  `ignore` TINYINT(4)  NULL DEFAULT 0 ,
  `ignore_timestamp` DATETIME NULL ,
  `sensor_id` BINARY(16) NOT NULL ,
  `datetime` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `event_fields`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `event_fields` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `tag` TEXT NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `suggestion_pattern`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `suggestion_pattern` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `suggestion_group_id` BINARY(16) NOT NULL ,
  `pattern_json` TEXT NOT NULL ,
  `status` TINYINT(1) NOT NULL DEFAULT '0' ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `data_sources`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `data_sources` (
  `id` INT(11) NOT NULL,
  `name` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;
