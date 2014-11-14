-- OSVDB relationships for cross correlation
-- plugin_id: 5003
--
-- This will be used for cross correlation
-- As this has no plugin_sids (it isn't a detector or monitor) all the data will be inserted in the plugin_reference table.

-- Please take a look at os-sim/scripts/extract_osvdb/notes.txt

DELETE FROM plugin WHERE id = "5003";
DELETE FROM plugin_sid where plugin_id = "5003";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (5003, 4, "osvdb", "Open Source Vulnerability Database");
