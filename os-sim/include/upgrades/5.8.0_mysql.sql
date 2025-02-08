SET AUTOCOMMIT=0;

--Recreating the frm information after upgrading to 5.8.0
ALTER TABLE alienvault.idm_data MODIFY `from_src` tinyint(1) DEFAULT NULL;
ALTER TABLE alienvault.otx_data MODIFY `ioc_value` varchar(2048) DEFAULT NULL;
ALTER TABLE alienvault.event MODIFY `refs` int(11) DEFAULT NULL;
ALTER TABLE alienvault.extra_data MODIFY `binary_data` blob DEFAULT NULL;
ALTER TABLE alienvault.sem_stats_events MODIFY `counter` int(11) NOT NULL;

ALTER TABLE datawarehouse.ip2country MODIFY `a3` char(3) NOT NULL DEFAULT '';
ALTER TABLE datawarehouse.report_data MODIFY `dataI3` int(11) DEFAULT NULL;
ALTER TABLE datawarehouse.ssi MODIFY `volume` int(11) DEFAULT NULL;
ALTER TABLE datawarehouse.incidents_ssi MODIFY `volume` int(11) DEFAULT NULL;

ALTER TABLE alienvault_siem.extra_data MODIFY `password` varchar(64) DEFAULT NULL;
ALTER TABLE alienvault_siem.idm_data MODIFY `from_src` tinyint(1) DEFAULT NULL;
ALTER TABLE alienvault_siem.reputation_data MODIFY `rep_act_dst` varchar(64) DEFAULT NULL;
ALTER TABLE alienvault_siem.acid_event MODIFY `dst_mac` binary(6) DEFAULT NULL;
ALTER TABLE alienvault_siem.po_acid_event MODIFY `cnt` int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE alienvault_siem.otx_data MODIFY `ioc_value` varchar(2048) DEFAULT NULL;


--The size of the leaf is miss calculated after migration and the scheema should change to be well calculated
ALTER TABLE alienvault.vuln_job_schedule MODIFY `exclude_ports` text DEFAULT NULL;
ALTER TABLE alienvault.vuln_jobs MODIFY `exclude_ports` text DEFAULT NULL;
--We recover the previous value
ALTER TABLE alienvault.vuln_job_schedule MODIFY `exclude_ports` text NOT NULL;
ALTER TABLE alienvault.vuln_jobs MODIFY `exclude_ports` text NOT NULL;


USE alienvault;


DELIMITER $$
DROP PROCEDURE IF EXISTS _update_vuln_assets$$
CREATE PROCEDURE _update_vuln_assets ( IN _job_id INT )
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE _jid INT DEFAULT 0;
    DECLARE _targets TEXT;
    DECLARE x INT DEFAULT 0;
    DECLARE y INT DEFAULT 0;
    DECLARE cur1 CURSOR FOR SELECT id,targets FROM _tmp_jobs;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    CREATE TEMPORARY TABLE IF NOT EXISTS _tmp_jobs (id int(11) NOT NULL, targets TEXT) ENGINE=InnoDB;
    CREATE TEMPORARY TABLE IF NOT EXISTS _tmp_net (PRIMARY KEY(begin,end)) AS SELECT begin,end,net_id from net_cidrs LIMIT 0;
    INSERT IGNORE INTO _tmp_net SELECT begin,end,net_id from net_cidrs;

    IF _job_id = 0 THEN
        SET @jtype = 0;
        INSERT IGNORE INTO _tmp_jobs SELECT id,meth_TARGET FROM vuln_job_schedule;
    ELSE
        SET @jtype = 1;
        INSERT IGNORE INTO _tmp_jobs SELECT id,meth_TARGET FROM vuln_jobs WHERE id=_job_id;
    END IF;

    OPEN cur1;

    REPEAT
        FETCH cur1 INTO _jid,_targets;
        IF NOT done THEN
            DELETE FROM vuln_job_assets WHERE job_id=_jid AND job_type=@jtype;
            -- Line by line iterator
            SELECT LENGTH(_targets) - LENGTH(REPLACE(_targets, '\n', '')) INTO @nCommas;
            SET y = 1;
            SET x = @nCommas + 1;
            SET @query = '';
            WHILE y <= x DO
                SELECT _split_string(_targets, '\n', y) INTO @target;
                IF @target REGEXP '.*#.*' THEN
                    SELECT _split_string(@target, '#', 1) INTO @uuid;
                    SELECT _split_string(@target, '#', 2) INTO @asset_type;
                    -- asset
                    INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) VALUES (_jid, @jtype, UNHEX(@uuid));
                    INSERT IGNORE INTO vuln_job_assets SELECT _jid, @jtype, n1.net_id FROM _tmp_net n1, net_cidrs n WHERE n1.begin >= n.begin AND n1.end <= n.end AND n.net_id=UNHEX(@uuid);
                    -- host groups
                    IF @asset_type = 'hostgroup' THEN
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT _jid, @jtype, host_id FROM host_group_reference WHERE host_group_id=UNHEX(@uuid);
                    -- network groups
                    ELSEIF @asset_type = 'netgroup' THEN
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT _jid, @jtype, net_id FROM net_group_reference WHERE net_group_id=UNHEX(@uuid);
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT DISTINCT _jid, @jtype, host_id FROM host_net_reference, net_group_reference WHERE host_net_reference.net_id = net_group_reference.net_id AND net_group_reference.net_group_id=UNHEX(@uuid);
                    -- network cidrs
                    ELSEIF @asset_type REGEXP '[[.slash.]][[:digit:]]' THEN
                        INSERT IGNORE INTO vuln_job_assets (job_id, job_type, asset_id) SELECT _jid, @jtype, host_id FROM host_net_reference WHERE net_id=UNHEX(@uuid);
                    END IF;
                END IF;
                SET  y = y + 1;
            END WHILE;
        END IF;
    UNTIL done END REPEAT;

    CLOSE cur1;

    DROP TABLE IF EXISTS _tmp_jobs;
    DROP TABLE IF EXISTS _tmp_net;
END$$

DELIMITER ;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2020-02-18');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.8.0');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
