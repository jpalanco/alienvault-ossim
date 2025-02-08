SET AUTOCOMMIT=0;
USE alienvault;

ALTER TABLE `vuln_nessus_plugins` CHANGE `cve_id` `cve_id` VARCHAR(512) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `vuln_nessus_plugins_feed` CHANGE `cve_id` `cve_id` VARCHAR(512) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

UPDATE alienvault_siem.reference_system SET url = 'http://otx.alienvault.com/indicator/cve/%value%' WHERE `ref_system_id` IN ('5', '7');

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(200, 'Details', 'Vulnerabilities', 'Vulnerabilities/Vulnerabilities.php', 'Critical:critical:checkbox:OSS_NULLABLE.OSS_DIGIT:1;High:high:checkbox:OSS_NULLABLE.OSS_DIGIT:1;Medium:medium:checkbox:OSS_NULLABLE.OSS_DIGIT:1;Low:low:checkbox:OSS_NULLABLE.OSS_DIGIT:1;Info:info:checkbox:OSS_NULLABLE.OSS_DIGIT:1', '', 1),
(201, 'Summary', 'Vulnerabilities', 'Vulnerabilities/Vulnerabilities.php', 'Critical:critical:checkbox:OSS_NULLABLE.OSS_DIGIT:1;High:high:checkbox:OSS_NULLABLE.OSS_DIGIT:1;Medium:medium:checkbox:OSS_NULLABLE.OSS_DIGIT:1;Low:low:checkbox:OSS_NULLABLE.OSS_DIGIT:1;Info:info:checkbox:OSS_NULLABLE.OSS_DIGIT:1', '', 1),
(202, 'Threats Database', 'Vulnerabilities', 'Vulnerabilities/TheatsDatabase.php', 'Keywords:keywords:text:OSS_NULLABLE::20;CVE:cve:text:OSS_NULLABLE::20;Risk Factor:riskFactor:select:OSS_ALPHA:ALL,Info,Low,Medium,High,Critical:;Detail:detail:checkbox:OSS_NULLABLE.OSS_DIGIT:1', '', 1);


DELIMITER $$

DROP PROCEDURE IF EXISTS change_vuln_nessus_report_stats_column$$
CREATE PROCEDURE change_vuln_nessus_report_stats_column() BEGIN
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_nessus_report_stats' AND COLUMN_NAME = 'vSerious')
    THEN
        ALTER TABLE `vuln_nessus_report_stats` CHANGE `vSerious` `vCritical` INT(6) NOT NULL DEFAULT '0';
    END IF;
END$$

DELIMITER ;

CALL change_vuln_nessus_report_stats_column();

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2019-09-10');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.7.5');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
