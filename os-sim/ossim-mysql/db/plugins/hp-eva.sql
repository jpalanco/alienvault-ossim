-- hp-eva
-- HP StorageWorks Command View EVA
-- plugin_id: 1579

DELETE FROM plugin WHERE id=1579;
DELETE FROM plugin_sid WHERE plugin_id=1579;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1579, 1, 'hp-eva', 'HP Command View EVA');

-- A machine check occurred while a termination event was being processed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 1, NULL, NULL, 'HP Command View EVA (ErrorCode: 01) - Fault Manager Termination Processing Recursive Entry Event', 2, 2);

-- An unexpected event occurred while a termination event was being processed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 2, NULL, NULL, 'HP Command View EVA (ErrorCode: 02) - Fault Manager Termination Processing Unexpected', 2, 2);

-- An event that affects Fault Manager operation occurred
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 3, NULL, NULL, 'HP Command View EVA (ErrorCode: 03) - Fault Manager Management Event', 2, 2);

-- An error was encountered while accessing a physical disk drive
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 4, NULL, NULL, 'HP Command View EVA (ErrorCode: 04) - Fibre Channel Services Physical Disk Drive Error', 3, 2);

-- The state of a Storage System Management Interface entity was changed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 5, NULL, NULL, 'HP Command View EVA (ErrorCode: 05) - Storage System Management Interface Entity State Change', 2, 2);

--
-- TODO: ErrorCode: 06
--

-- Excessive link errors were detected on a Fibre Channel port
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 7, NULL, NULL, 'HP Command View EVA (ErrorCode: 07) - Fibre Channel Services Fibre Channel Port Link Error', 2, 2);

-- A Fibre Channel port link has failed or a Drive Enclosure Environmental Monitoring Unit task has failed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 8, NULL, NULL, 'HP Command View EVA (ErrorCode: 08) - Fibre Channel Services Fibre Channel Port Link', 2, 2);

-- An error was encountered while attempting to access a physical disk drive or the mirror port
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 9, NULL, NULL, 'HP Command View EVA (ErrorCode: 09) - Fibre Channel Services Physical Disk Drive/Mirror Port Error', 2, 2);

-- A storage System state change occurred
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 10, NULL, NULL, 'HP Command View EVA (ErrorCode: 0A) - Storage System State Services State Change', 2, 2);

-- A physical disk drive state change occurred
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 11, NULL, NULL, 'HP Command View EVA (ErrorCode: 0B) - Storage System State Services Physical Disk Drive State Change', 2, 2);

-- A Data Replication Manager state change occurred
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 12, NULL, NULL, 'HP Command View EVA (ErrorCode: 0C) - Data Replication Manager State Change', 2, 2);

-- A change in system time occurred
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 13, NULL, NULL, 'HP Command View EVA (ErrorCode: 0D) - Executive Services System Time Change', 2, 2);

-- A Storage System Management Interface entity was created or deleted
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 14, NULL, NULL, 'HP Command View EVA (ErrorCode: 0E) - Storage System Management Interface Entity Creation or Deletion', 2, 2);

-- An attribute of a Storage System Management Interface entity has changed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 15, NULL, NULL, 'HP Command View EVA (ErrorCode: 0F) - Storage System Management Interface Entity Attribute Change', 2, 2);

-- A controller state change occurred
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 16, NULL, NULL, 'HP Command View EVA (ErrorCode: 10) - System Services HSV210 Controller State Change', 2, 2);

-- Status of a disk enclosure element has changed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 17, NULL, NULL, 'HP Command View EVA (ErrorCode: 11) - Disk Enclosure Environment Monitoring Unit Services Status Change', 2, 2);

-- Unexpected work was received from a physical disk drive or the mirror port 
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 18, NULL, NULL, 'HP Command View EVA (ErrorCode: 12) - Fibre Channel Services Physical Disk Drive/Mirror Port Unexpected Work Encounteres', 2, 2);

-- Summary of errors encountered while attempting to access a physical disk drive, the mirror port, or a Drive Enclosure Environmental Monitoring Unit
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 19, NULL, NULL, 'HP Command View EVA (ErrorCode: 13) - Fibre Channel Services Physical Disk Drive/Mirror Port/drive Enclosure Environmental Monitoring Unit Error Summary', 3, 2);

-- A failure was detected during the execution of a diagnostic
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 20, NULL, NULL, 'HP Command View EVA (ErrorCode: 14) - Diagnostic Operations Generator Detected Failure', 3, 2);

-- An operation on a Disk Group has started or completed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 21, NULL, NULL, 'HP Command View EVA (ErrorCode: 15) - Container Services Management Operation has started or completed', 2, 2);

-- An HSV210 controller has received a time report message
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 22, NULL, NULL, 'HP Command View EVA (ErrorCode: 16) - Data Replication Manager Time Report', 2, 2);

-- A new device map has been generated on a Fibre Channel port
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 23, NULL, NULL, 'HP Command View EVA (ErrorCode: 17) - Fibre Channel Services Fibre Channel Port Loop Config', 2, 2);

-- A Redundant Storage Set state change occurred
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 24, NULL, NULL, 'HP Command View EVA (ErrorCode: 18) - Storage System State Services Redundant Storage Set State Change', 2, 2);

-- Status of a System Data Center element has changed
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 25, NULL, NULL, 'HP Command View EVA (ErrorCode: 19) - System Data Center Services Status Change', 2, 2);

-- A code load operation has occurred
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 26, NULL, NULL, 'HP Command View EVA (ErrorCode: 1A) - System Services Code Load Operation Update', 2, 2);

--
-- TODO: ErrorCode 1B
--

-- HSV210 controller operation was terminated due to an unrecoverable event detected by either software or hardware or due to an action initiated via the Storage System Management Interface
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 28, NULL, NULL, 'HP Command View EVA (ErrorCode: 1C) - Fault Manager Termination Event', 3, 2);

-- HSV210 controller operation was terminated due to an unrecoverable event detected by either software or hardware or due to an action initiated via the Storage System Manager Interface
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 29, NULL, NULL, 'HP Command View EVA (ErrorCode: 1D) - Fault Manager Termination Event (old Termination Event Information Header)', 3, 1);

-- General Storage System state information to be reported
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1579, 30, NULL, NULL, 'HP Command View EVA (ErrorCode: 1E) - General Storage System State Services State Information Event', 2, 2);

