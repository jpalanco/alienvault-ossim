SET AUTOCOMMIT=0;

USE alienvault_siem;

DROP PROCEDURE IF EXISTS reIndexing;
DROP PROCEDURE IF EXISTS delete_events;
DELIMITER $$

CREATE PROCEDURE reIndexing()
  BEGIN
    IF NOT EXISTS(
                SELECT * FROM information_schema.statistics
                WHERE TABLE_SCHEMA = 'alienvault_siem'
                AND `table_name`='po_acid_event'
                AND `index_name` = 'plugin_id'
            )
    THEN
      ALTER TABLE alienvault_siem.po_acid_event ADD INDEX (plugin_id, plugin_sid);
    END IF;

    IF NOT EXISTS(
                SELECT * FROM information_schema.statistics
                WHERE TABLE_SCHEMA = 'alienvault_siem'
                AND `table_name`='po_acid_event'
                AND `index_name` = 'day'
            )
    THEN
      ALTER TABLE alienvault_siem.po_acid_event ADD INDEX `day` (`timestamp`);
    END IF;
    IF EXISTS(
                SELECT * FROM information_schema.statistics
                WHERE TABLE_SCHEMA = 'alienvault_siem'
                AND `table_name`='po_acid_event'
                AND `index_name` = 'timestamp'
            )
    THEN
      ALTER TABLE alienvault_siem.po_acid_event DROP INDEX `timestamp`;
    END IF;
END$$

CREATE PROCEDURE delete_events( tmp_table VARCHAR(64) )
  BEGIN
    SET @query = CONCAT('DELETE aux FROM alienvault_siem.reputation_data aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.event_id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;

    SET @query = CONCAT('DELETE aux FROM alienvault_siem.otx_data aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.event_id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;

    SET @query = CONCAT('DELETE aux FROM alienvault_siem.idm_data aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.event_id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;

    SET @query = CONCAT('DELETE aux FROM alienvault_siem.extra_data aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.event_id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;

    SET @query = CONCAT('DELETE aux FROM alienvault_siem.acid_event aux LEFT JOIN ',tmp_table,' tmp ON tmp.id=aux.id WHERE tmp.id IS NOT NULL');
    PREPARE sql_query from @query;
    EXECUTE sql_query;
    DEALLOCATE PREPARE sql_query;
END$$
DELIMITER ;

CALL reIndexing();
DROP PROCEDURE reIndexing;

USE alienvault;


REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2019-03-12');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.7.2');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
