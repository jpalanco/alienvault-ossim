USE alienvault;

alter table incident_tag_descr add column class text not null;
INSERT INTO acl_perm VALUES (89, 'MENU','analysis-menu','ControlPanelAlarmsClose','Analysis -> Alarms -> Close Alarms',1,1,1,'02.15');
alter table incident_tmp_email add column type TEXT;

DROP PROCEDURE IF EXISTS `incident_ticket_populate`;
DELIMITER $$
CREATE DEFINER=`root`@`127.0.0.1` PROCEDURE `incident_ticket_populate`(p_incident_id INT, src_ip VARBINARY(16), dst_ip VARBINARY(16), prio INT) BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE count INT;
    DECLARE cnt_src, cnt_dst INT;
    DECLARE name, subname VARCHAR(255);
    DECLARE first_occ, last_occ TIMESTAMP;
    DECLARE source VARCHAR(39);
    DECLARE dest VARCHAR(39);

    DECLARE cur1 CURSOR FOR select count(*) as cnt, inet6_ntoa(event.src_ip) as src, inet6_ntoa(event.dst_ip) as dst, plugin.name, plugin_sid.name, min(timestamp) as frst, max(timestamp) as last, count(distinct(event.src_ip)) as
cnt_src, count(distinct(event.dst_ip)) as cnt_dst from event, plugin, plugin_sid where (event.src_ip = src_ip or event.dst_ip = src_ip or event.src_ip = dst_ip or event.dst_ip =dst_ip ) and timestamp > DATE_SUB(NOW(), INTERVAL 7
DAY) AND plugin.id = event.plugin_id and plugin_sid.sid = event.plugin_sid and plugin_sid.plugin_id = event.plugin_id group by event.plugin_id, event.plugin_sid ORDER by cnt DESC limit 50;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    SET @alarm_id = NULL;
    SELECT hex(backlog_id) INTO @alarm_id FROM incident_alarm where `incident_id` = p_incident_id;

    IF (@alarm_id IS NOT NULL) THEN
                INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES
                        (NULL, p_incident_id, NOW()-1, "Open", prio, "admin", CONCAT("<a target=\"_blank\" href=\"/ossim/#analysis/alarms/alarms-",@alarm_id,"\">Link to Alarm</a>"));
        END IF;
        INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES
        (NULL, p_incident_id, NOW()-1, "Open", prio, "admin", "The following tickets contain information about the top 50 event types the hosts have been generating during the last 7 days.");
    SET @ticket_id = LAST_INSERT_ID();

    OPEN cur1;
    REPEAT
        FETCH cur1 INTO count, source, dest, name, subname, first_occ, last_occ, cnt_src, cnt_dst;
        IF NOT done THEN
            SET @desc = CONCAT( "Event Type: ", name, "\nEvent Description: ", subname, "\nOcurrences: ",CAST(count AS CHAR), "\nFirst Ocurrence: ", CAST(first_occ AS CHAR(50)), "\nLast Ocurrence: ", CAST(last_occ AS
CHAR(50)),"\nNumber of different sources: ", CAST(cnt_src AS CHAR), "\nNumber of different destinations: ", CAST(cnt_dst AS CHAR), "\nSource: ", source, "\nDest: ", dest);
            INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (NULL, p_incident_id, NOW(), "Open", prio, "admin", @desc);
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur1;
    IF EXISTS (SELECT value FROM config where conf = "tickets_send_mail" and value = "yes") THEN
        REPLACE INTO incident_tmp_email VALUES (p_incident_id,@ticket_id,"CREATE_INCIDENT");
    END IF; END$$
DELIMITER ;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2017-01-24');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.3.5');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
