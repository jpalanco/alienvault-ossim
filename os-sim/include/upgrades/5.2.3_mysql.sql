BEGIN;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2016-04-07');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.2.3');


#ENG_102962 fix
DELIMITER $$

DROP PROCEDURE IF EXISTS 5_2_3_ENG_102962 $$
CREATE PROCEDURE 5_2_3_ENG_102962()
BEGIN

IF EXISTS( (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='datawarehouse' AND TABLE_NAME='tmp_user') ) THEN
    ALTER TABLE datawarehouse.tmp_user DROP PRIMARY KEY, ADD PRIMARY KEY (user,section,sid,req);
END IF;
END $$
CALL 5_2_3_ENG_102962() $$
DROP PROCEDURE IF EXISTS 5_2_3_ENG_102962 $$


DROP procedure IF EXISTS compliance_aggregate $$
CREATE PROCEDURE compliance_aggregate()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE _ref VARCHAR(512);
    DECLARE _secc VARCHAR(512);
    DECLARE _sids VARCHAR(512);
    DECLARE _a VARCHAR(512);
    DECLARE _b VARCHAR(512);
    DECLARE _c VARCHAR(512);
    DECLARE _d VARCHAR(512);
    DECLARE _e VARCHAR(512);
    DECLARE _i INT;
    DECLARE _j INT;
    DECLARE _k INT;
    DECLARE _l INT;
    DECLARE _m INT;
    DECLARE _n INT;
    DECLARE _o INT;
    DECLARE _p INT;
    DECLARE _q INT;
    DECLARE x INT DEFAULT 0;
    DECLARE y INT DEFAULT 0;
    
    
    DECLARE cur1 CURSOR FOR SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A05_Security_Policy WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A06_IS_Organization WHERE SIDSS_Ref 
>= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A07_Asset_Mgnt WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A08_Human_Resources WHERE SIDSS_Ref >= 1 UNION ALL 
SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A09_Physical_security WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A10_Com_OP_Mgnt WHERE SIDSS_Ref >= 1 UNION ALL SELECT 
`Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A11_Acces_control WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A12_IS_acquisition WHERE SIDSS_Ref >= 1 UNION ALL SELECT 
`Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A13_IS_incident_mgnt WHERE SIDSS_Ref >= 1 UNION ALL SELECT `Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A14_BCM WHERE SIDSS_Ref >= 1 UNION ALL SELECT 
`Ref`,Security_controls,SIDSS_Ref FROM ISO27001An.A15_Compliance WHERE SIDSS_Ref >= 1;
    DECLARE cur2 CURSOR FOR SELECT DISTINCT(i.destination) AS dest_ip, net.name AS service FROM net, net_cidrs, datawarehouse.incidents_ssi i WHERE net.id=net_cidrs.net_id AND inet6_aton(i.destination) >= net_cidrs.begin AND 
inet6_aton(i.destination) <= net_cidrs.end AND i.destination <> '' AND i.destination <> '0.0.0.0' GROUP BY 1;
    DECLARE cur3 CURSOR FOR SELECT incident.type_id, incident.title, incident.priority, incident_alarm.src_ips, incident_alarm.dst_ips, ifnull(incident_ticket.description,''), YEAR(incident.event_start), 
MONTH(incident.event_start), DAY(incident.event_start), HOUR(incident.event_start), MINUTE(incident.event_start), count(distinct(incident.id)) FROM incident LEFT JOIN incident_ticket ON 
incident_ticket.incident_id=incident.id,incident_alarm, incident_type WHERE incident_alarm.incident_id=incident.id and incident_type.id=incident.type_id GROUP BY 1,2,3,4,5,7,8,9,10,11;
    DECLARE cur4 CURSOR FOR SELECT a.plugin_sid, s.name, a.risk, inet6_ntoa(a.src_ip), inet6_ntoa(a.dst_ip), "no_detail", YEAR(a.timestamp), MONTH(a.timestamp), HOUR(a.timestamp), DAY(a.timestamp), MINUTE(a.timestamp), count(*) 
as volume FROM alarm a, plugin_sid s WHERE a.plugin_id=s.plugin_id AND a.plugin_sid=s.sid AND a.status="open" AND s.plugin_id=1505 GROUP BY 1,2,3,4,5,6,7,8,9,10,11;
    DECLARE cur5 CURSOR FOR SELECT inet6_ntoa(a.src_ip) AS ip, net.name AS service FROM net, alarm a, net_cidrs n WHERE n.net_id=net.id AND a.src_ip >= n.begin AND a.src_ip <= n.end GROUP BY 1 UNION SELECT inet6_ntoa(a.dst_ip) AS 
ip, net.name AS service FROM net, alarm a, net_cidrs n WHERE n.net_id=net.id AND a.dst_ip >= n.begin AND a.dst_ip <= n.end GROUP BY 1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DELETE FROM datawarehouse.iso27001sid;
    SET AUTOCOMMIT=0;
    OPEN cur1;
    REPEAT
        FETCH cur1 INTO _ref,_secc,_sids;
        IF NOT done THEN
            SET @ref = _ref;
            SET @secc = _secc;
            SELECT LENGTH(_sids) - LENGTH(REPLACE(_sids, ',', '')) INTO @nCommas;
            SET y = 1;
            SET x = @nCommas + 1;
            WHILE y <= x DO
                SELECT _split_string(_sids, ',', y) INTO @range;
                SET @query = 'INSERT INTO datawarehouse.iso27001sid VALUES (?, ?, ?)';
                PREPARE sql_query FROM @query;
                EXECUTE sql_query USING @ref, @secc, @range;
                DEALLOCATE PREPARE sql_query;
                SET y = y + 1;
            END WHILE;
        END IF;
    UNTIL done END REPEAT;
    CLOSE cur1;
    COMMIT;
    
    SET done = 0;
    
    OPEN cur2;
    REPEAT
        FETCH cur2 INTO _a,_b;
        IF NOT done THEN
            SET @query = 'REPLACE INTO datawarehouse.ip2service (`dest_ip`, `service`) VALUES (?, ?)';
            SET @a = _a;
            SET @b = _b;
            PREPARE sql_query FROM @query;
            EXECUTE sql_query USING @a, @b;
            DEALLOCATE PREPARE sql_query;
        END IF;
    UNTIL done END REPEAT;
    CLOSE cur2;
    COMMIT;
    
    
    SET done = 0;
    
    OPEN cur3;
    REPEAT
        FETCH cur3 INTO _a,_b,_j,_c,_d,_e,_k,_l,_m,_n,_o,_p;
        IF NOT done THEN
            SET @query = 'REPLACE INTO datawarehouse.incidents_ssi (`type`, `descr`, `priority`, `source`, `destination`, `details`, `year`, `month`, `day`, `hour`, `minute`, `volume`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
?)';
            SET @a = _a;
            SET @b = _b;
            SET @c = _c;
            SET @d = _d;
            SET @e = _e;
            SET @i = _i;
            SET @j = _j;
            SET @k = _k;
            SET @l = _l;
            SET @m = _m;
            SET @n = _n;
            SET @o = _o;
            SET @p = _p;
            PREPARE sql_query FROM @query;
            EXECUTE sql_query USING @a, @b, @j, @c, @d, @e, @k, @l, @m, @n, @o, @p;
            DEALLOCATE PREPARE sql_query;
        END IF;
    UNTIL done END REPEAT;
    CLOSE cur3;
    COMMIT;
    
    
    SET done = 0;
    
    OPEN cur4;
    REPEAT
        FETCH cur4 INTO _j,_a,_k,_b,_c,_d,_l,_m,_n,_o,_p,_q;
        IF NOT done THEN
            SET @query = 'REPLACE INTO datawarehouse.ssi (`sid`, `descr`, `priority`, `source`, `destination`, `details`, `year`, `month`, `hour`, `day`, `minute`, `volume`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            SET @a = _a;
            SET @b = _b;
            SET @c = _c;
            SET @d = _d;
            SET @i = _i;
            SET @j = _j;
            SET @k = _k;
            SET @l = _l;
            SET @m = _m;
            SET @n = _n;
            SET @o = _o;
            SET @p = _p;
            SET @q = _q;
            PREPARE sql_query FROM @query;
            EXECUTE sql_query USING @j, @a, @k, @b, @c, @d, @l, @m, @n, @o, @p, @q;
            DEALLOCATE PREPARE sql_query;
        END IF;
    UNTIL done END REPEAT;
    CLOSE cur4;
    COMMIT;
    
    
    SET done = 0;
    
    OPEN cur5;
    REPEAT
        FETCH cur5 INTO _a,_b;
        IF NOT done THEN
            SET @query = 'REPLACE INTO datawarehouse.ip2service (`dest_ip`, `service`) VALUES (?, ?)';
            SET @a = _a;
            SET @b = _b;
            PREPARE sql_query FROM @query;
            EXECUTE sql_query USING @a, @b;
            DEALLOCATE PREPARE sql_query;
        END IF;
    UNTIL done END REPEAT;
    CLOSE cur5;
    COMMIT;
    
END$$ 
DELIMITER ;

TRUNCATE TABLE datawarehouse.ssi;
CALL compliance_aggregate();

#ENG_102962 fix end

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
