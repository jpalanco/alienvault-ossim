START TRANSACTION;
LOCK TABLES acid_event WRITE, ac_acid_event WRITE, device WRITE, extra_data WRITE, reputation_data WRITE, idm_data WRITE;
TRUNCATE acid_event;
TRUNCATE ac_acid_event;
TRUNCATE extra_data;
TRUNCATE reputation_data;
TRUNCATE idm_data;
UNLOCK TABLES;
COMMIT;
