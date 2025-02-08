SET AUTOCOMMIT=0;

USE alienvault;

DELIMITER $$

DROP PROCEDURE IF EXISTS _delete_orphan_backlogs$$

CREATE PROCEDURE _delete_orphan_backlogs(
cleanup BOOLEAN
)
BEGIN
    DECLARE num_events INT;

    CREATE TEMPORARY TABLE IF NOT EXISTS tmpbckdel (backlog_id BINARY(16) NOT NULL, PRIMARY KEY ( backlog_id )) ENGINE=INNODB;
    CREATE TEMPORARY TABLE IF NOT EXISTS tmpevndel (event_id BINARY(16) NOT NULL, PRIMARY KEY ( event_id )) ENGINE=INNODB;

    -- ENG-110364: If the name "server_cleanbcktmp" is changed, change it in the cleanup process as well
    IF (cleanup = 1) THEN
      INSERT IGNORE INTO tmpbckdel SELECT id FROM backlog b WHERE b.timestamp = '1970-01-01 00:00:00' AND id NOT IN (SELECT backlog_id FROM server_cleanbcktmp);
    ELSE
      INSERT IGNORE INTO tmpbckdel SELECT id FROM backlog WHERE timestamp = '1970-01-01 00:00:00';
    END IF;

    IF EXISTS (SELECT 1 FROM tmpbckdel LIMIT 1) THEN

        INSERT IGNORE INTO tmpevndel SELECT be.event_id FROM backlog_event be, tmpbckdel tmp WHERE be.backlog_id = tmp.backlog_id;

        CREATE TEMPORARY TABLE IF NOT EXISTS tmpexclude (event_id BINARY(16) NOT NULL, PRIMARY KEY ( event_id )) ENGINE=MEMORY;
        INSERT IGNORE INTO tmpexclude SELECT t.event_id FROM tmpevndel t, backlog_event be LEFT JOIN tmpbckdel b ON be.backlog_id = b.backlog_id WHERE be.event_id = t.event_id AND b.backlog_id IS NULL;
        DELETE t FROM tmpevndel t, tmpexclude ex WHERE t.event_id = ex.event_id;
        DROP TABLE tmpexclude;

        -- Delete events
        CREATE TEMPORARY TABLE _ttmp (id binary(16) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MEMORY;
        SELECT COUNT(event_id) FROM tmpevndel INTO @num_events;

        WHILE @num_events > 0 DO
            INSERT INTO _ttmp SELECT event_id FROM tmpevndel LIMIT 10000;
            DELETE e FROM event e, _ttmp t WHERE e.id = t.id;
            DELETE i FROM idm_data i, _ttmp t WHERE i.event_id = t.id;
            DELETE o FROM otx_data o, _ttmp t WHERE o.event_id = t.id;
            DELETE x FROM extra_data x, _ttmp t WHERE x.event_id= t.id;
            DELETE te FROM tmpevndel te, _ttmp t WHERE te.event_id=t.id;
            TRUNCATE TABLE _ttmp;
            SET @num_events = @num_events - 10000;
        END WHILE;

        -- Delete backlogs
        TRUNCATE TABLE _ttmp;
        SELECT COUNT(backlog_id) FROM tmpbckdel INTO @num_events;

        WHILE @num_events > 0 DO
            INSERT INTO _ttmp SELECT backlog_id FROM tmpbckdel LIMIT 10000;
            DELETE be FROM backlog_event be, _ttmp t WHERE be.backlog_id=t.id;
            DELETE b, ta, c, h, n, a FROM backlog b INNER JOIN _ttmp t ON t.id=b.id LEFT JOIN component_tags ta ON ta.id_component = b.id LEFT JOIN alarm_ctxs c ON c.id_alarm = b.id LEFT JOIN alarm_nets n ON n.id_alarm = b.id LEFT JOIN alarm_hosts h ON h.id_alarm = b.id LEFT JOIN alarm a ON a.backlog_id = b.id;
            DELETE te FROM tmpbckdel te, _ttmp t WHERE te.backlog_id=t.id;
            TRUNCATE TABLE _ttmp;
            SET @num_events = @num_events - 10000;
        END WHILE;

        DROP TABLE _ttmp;

    END IF;
    DROP TABLE tmpevndel;
    DROP TABLE tmpbckdel;
END$$

DELIMITER ;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2019-11-19');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.7.6');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
