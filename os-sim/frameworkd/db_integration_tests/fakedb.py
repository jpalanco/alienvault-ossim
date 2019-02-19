def create_database():
    return 'CREATE DATABASE IF NOT EXISTS alienvault_siem'


def drop_database():
    return 'DROP DATABASE IF EXISTS alienvault_siem'


def use_database():
    return 'USE alienvault_siem'


def create_fill_tables_proc():
    statement = """
    CREATE PROCEDURE alienvault_siem.fill_tables (IN date_from VARCHAR(19), IN date_to VARCHAR(19))
    BEGIN
        SELECT 10;
    END;
    """
    return statement


def drop_fill_tables_proc():
    return 'DROP PROCEDURE alienvault_siem.fill_tables;'


def create_table_acid_event():
    statement = """
        CREATE TABLE `acid_event` (
      `id` binary(16) NOT NULL,
      `device_id` int(10) unsigned NOT NULL,
      `ctx` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
      `timestamp` datetime NOT NULL,
      `ip_src` varbinary(16) DEFAULT NULL,
      `ip_dst` varbinary(16) DEFAULT NULL,
      `ip_proto` int(11) DEFAULT NULL,
      `layer4_sport` smallint(5) unsigned DEFAULT NULL,
      `layer4_dport` smallint(5) unsigned DEFAULT NULL,
      `ossim_priority` tinyint(4) DEFAULT '1',
      `ossim_reliability` tinyint(4) DEFAULT '1',
      `ossim_asset_src` tinyint(4) DEFAULT '1',
      `ossim_asset_dst` tinyint(4) DEFAULT '1',
      `ossim_risk_c` tinyint(4) DEFAULT '1',
      `ossim_risk_a` tinyint(4) DEFAULT '1',
      `plugin_id` int(10) unsigned DEFAULT NULL,
      `plugin_sid` int(10) unsigned DEFAULT NULL,
      `tzone` float NOT NULL DEFAULT '0',
      `ossim_correlation` tinyint(4) DEFAULT '0',
      `src_hostname` varchar(64) DEFAULT NULL,
      `dst_hostname` varchar(64) DEFAULT NULL,
      `src_mac` binary(6) DEFAULT NULL,
      `dst_mac` binary(6) DEFAULT NULL,
      `src_host` binary(16) DEFAULT NULL,
      `dst_host` binary(16) DEFAULT NULL,
      `src_net` binary(16) DEFAULT NULL,
      `dst_net` binary(16) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `timestamp` (`timestamp`,`plugin_id`,`plugin_sid`),
      KEY `layer4_dport` (`layer4_dport`),
      KEY `ip_src` (`ip_src`),
      KEY `ip_dst` (`ip_dst`),
      KEY `acid_event_ossim_risk_a` (`ossim_risk_a`),
      KEY `plugin` (`plugin_id`,`plugin_sid`),
      KEY `src_host` (`src_host`),
      KEY `dst_host` (`dst_host`)
    )
        """
    return statement


def create_table_acid_event2():
    statement = """
    CREATE TABLE `acid_event` (
  `id` binary(16) NOT NULL,
  `device_id` int(10) unsigned NOT NULL,
  `ctx` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `timestamp` datetime NOT NULL,
  `ip_src` varbinary(16) DEFAULT NULL,
  `ip_dst` varbinary(16) DEFAULT NULL,
  `ip_proto` int(11) DEFAULT NULL,
  `layer4_sport` smallint(5) unsigned DEFAULT NULL,
  `layer4_dport` smallint(5) unsigned DEFAULT NULL,
  `ossim_priority` tinyint(4) DEFAULT '1',
  `ossim_reliability` tinyint(4) DEFAULT '1',
  `ossim_asset_src` tinyint(4) DEFAULT '1',
  `ossim_asset_dst` tinyint(4) DEFAULT '1',
  `ossim_risk_c` tinyint(4) DEFAULT '1',
  `ossim_risk_a` tinyint(4) DEFAULT '1',
  `plugin_id` int(10) unsigned DEFAULT NULL,
  `plugin_sid` int(10) unsigned DEFAULT NULL,
  `tzone` float NOT NULL DEFAULT '0',
  `ossim_correlation` tinyint(4) DEFAULT '0',
  `src_hostname` varchar(64) DEFAULT NULL,
  `dst_hostname` varchar(64) DEFAULT NULL,
  `src_mac` binary(6) DEFAULT NULL,
  `dst_mac` binary(6) DEFAULT NULL,
  `src_host` binary(16) DEFAULT NULL,
  `dst_host` binary(16) DEFAULT NULL,
  `src_net` binary(16) DEFAULT NULL,
  `dst_net` binary(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`,`plugin_id`,`plugin_sid`),
  KEY `layer4_dport` (`layer4_dport`),
  KEY `ip_src` (`ip_src`),
  KEY `ip_dst` (`ip_dst`),
  KEY `acid_event_ossim_risk_a` (`ossim_risk_a`),
  KEY `plugin` (`plugin_id`,`plugin_sid`),
  KEY `src_host` (`src_host`),
  KEY `dst_host` (`dst_host`)
)
    """
    return statement


def drop_table_acid_event():
    return 'DROP TABLE alienvault_siem.acid_event'


def create_delete_events_proc():
    statement = """
        CREATE PROCEDURE alienvault_siem.delete_events (tmp_table VARCHAR(64))
        BEGIN
            SELECT 10;
        END;
        """
    return statement


def drop_delete_events_proc():
    return 'DROP PROCEDURE alienvault_siem.delete_events;'


def create_table_ac_acid_event():
    statement = """
    CREATE TABLE `ac_acid_event` (
  `ctx` binary(16) NOT NULL,
  `device_id` int(10) unsigned NOT NULL,
  `plugin_id` int(10) unsigned NOT NULL,
  `plugin_sid` int(10) unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  `src_host` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `dst_host` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `src_net` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `dst_net` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `cnt` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ctx`,`device_id`,`plugin_id`,`plugin_sid`,`timestamp`,`src_host`,`dst_host`,`src_net`,`dst_net`),
  KEY `day` (`timestamp`),
  KEY `src_host` (`src_host`),
  KEY `dst_host` (`dst_host`),
  KEY `plugin_id` (`plugin_id`,`plugin_sid`),
  KEY `src_net` (`src_net`),
  KEY `dst_net` (`dst_net`),
  KEY `device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
    """
    return statement


def drop_table_ac_acid_event():
    return 'DROP TABLE IF EXISTS alienvault_siem.ac_acid_event'


def create_table_po_acid_event():
    statement = """
    CREATE TABLE `po_acid_event` (
  `ctx` binary(16) NOT NULL,
  `device_id` int(10) unsigned NOT NULL,
  `plugin_id` int(10) unsigned NOT NULL,
  `plugin_sid` int(10) unsigned NOT NULL,
  `ip_src` varbinary(16) NOT NULL,
  `ip_dst` varbinary(16) NOT NULL,
  `timestamp` datetime NOT NULL,
  `src_host` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `dst_host` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `src_net` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `dst_net` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `cnt` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ctx`,`device_id`,`plugin_id`,`plugin_sid`,`ip_src`,`ip_dst`,`timestamp`,`src_host`,`dst_host`,
  `src_net`,`dst_net`),
  KEY `day` (`timestamp`),
  KEY `plugin_id` (`plugin_id`,`plugin_sid`),
  KEY `src_host` (`src_host`),
  KEY `dst_host` (`dst_host`),
  KEY `src_net` (`src_net`),
  KEY `dst_net` (`dst_net`),
  KEY `ip_src` (`ip_src`),
  KEY `ip_dst` (`ip_dst`),
  KEY `device_id` (`device_id`)
)
    """
    return statement


def drop_table_po_acid_event():
    return 'DROP TABLE IF EXISTS alienvault_siem.po_acid_event'
