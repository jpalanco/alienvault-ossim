USE alienvault;
SET AUTOCOMMIT=0;

CREATE TABLE IF NOT EXISTS `incident_tmp_email` (
  `incident_id` INT NOT NULL,
  `ticket_id` INT NOT NULL,
  PRIMARY KEY (`incident_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

DELIMITER $$
DROP PROCEDURE IF EXISTS incident_ticket_populate$$
CREATE PROCEDURE incident_ticket_populate (incident_id INT, src_ip VARBINARY(16), dst_ip VARBINARY(16), prio INT)
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE count INT;
    DECLARE cnt_src, cnt_dst, i INT;
    DECLARE name, subname VARCHAR(255);
    DECLARE first_occ, last_occ TIMESTAMP;
    DECLARE source VARCHAR(39);
    DECLARE dest VARCHAR(39);
    
    DECLARE cur1 CURSOR FOR select count(*) as cnt,  inet6_ntoa(event.src_ip) as src, inet6_ntoa(event.dst_ip) as dst, plugin.name, plugin_sid.name, min(timestamp) as frst, max(timestamp) as last, count(distinct(event.src_ip)) as cnt_src, count(distinct(event.dst_ip)) as cnt_dst from event, plugin, plugin_sid where (event.src_ip = src_ip or event.dst_ip = src_ip or event.src_ip = dst_ip or event.dst_ip =dst_ip ) and timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY) AND alienvault.plugin.id = event.plugin_id and alienvault.plugin_sid.sid = event.plugin_sid and alienvault.plugin_sid.plugin_id = event.plugin_id group by event.plugin_id, event.plugin_sid ORDER by cnt DESC limit 50;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    
    SET i = (SELECT IFNULL(MAX(id), 0) + 1 FROM incident_ticket);
    
    OPEN cur1;
    
    INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (i, incident_id, NOW()-1, "Open", prio, "admin", "The following tickets contain information about the top 50 event types the hosts have been generating during the last 7 days.");
    SET @ticket_id = i;
    SET i = i + 1;
    
    REPEAT
        FETCH cur1 INTO count, source, dest, name, subname, first_occ, last_occ, cnt_src, cnt_dst;
        IF NOT done THEN
            SET @desc = CONCAT( "Event Type: ",  name, "\nEvent Description: ", subname, "\nOcurrences: ",CAST(count AS CHAR), "\nFirst Ocurrence: ", CAST(first_occ AS CHAR(50)), "\nLast Ocurrence: ", CAST(last_occ AS CHAR(50)),"\nNumber of different sources: ", CAST(cnt_src AS CHAR), "\nNumber of different destinations: ", CAST(cnt_dst AS CHAR), "\nSource: ", source, "\nDest: ", dest);
        
            INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (i, incident_id, NOW(), "Open", prio, "admin", @desc);
        
            SET i = i + 1;
        END IF;
    UNTIL done END REPEAT;
    
    CLOSE cur1;

    -- Add to email queue if configured
    IF EXISTS (SELECT value FROM config where conf = "tickets_send_mail" and value = "yes") THEN
        REPLACE INTO alienvault.incident_tmp_email VALUES (incident_id,@ticket_id);
    END IF;
END$$

DELIMITER ;

DELETE ct FROM component_tags ct,tag t WHERE ct.id_tag=t.id AND t.id NOT IN (0xA3200000000000000000000000000000,0xA3100000000000000000000000000000) AND t.ctx=unhex('00000000000000000000000000000000') AND t.type='alarm';
DELETE FROM tag WHERE id NOT IN (0xA3200000000000000000000000000000,0xA3100000000000000000000000000000) AND ctx=unhex('00000000000000000000000000000000') AND type='alarm';

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES (360, 'Trends', 'Availability', 'Availability/Trends.php', 'Sensor:sensor_nagios:select:OSS_HEX.OSS_NULLABLE:SENSORNAGIOS:;Assumed Host Status:ahstatus:select:OSS_DIGIT.OSS_DOT.OSS_NULLABLE:ASSUMEDHOSTSTATE:5', '', 4);

ALTER TABLE `pass_history` CHANGE `pass` `pass` VARCHAR(64) NOT NULL;

UPDATE dashboard_custom_type SET help_default = 'Operating system distribution by type.' WHERE id=9001;
UPDATE dashboard_custom_type SET help_default = 'Installed software.' WHERE id=9002;

UPDATE dashboard_widget_config SET help='Operating system distribution by type.' WHERE help='Operating system distribution by type. You must have OCS up and running for this to work correctly.';
UPDATE dashboard_widget_config SET help='Installed software.' WHERE help='Installed software. OCS must be running for this to work.';

UPDATE vuln_nessus_servers SET max_scans=5 WHERE max_scans>5;

REPLACE INTO host_source_reference (`id`, `name`, `relevance`) VALUES (13,'DHCP',5);

DELETE FROM task_inventory WHERE task_type=3;

DELETE FROM config WHERE conf LIKE '%munin%';

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2015-10-06');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.2.0');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
