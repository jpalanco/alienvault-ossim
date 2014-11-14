-- ossim services listing
-- plugin_id: 5002
--
-- This will be used for cross correlation
--
-- To actualice it, take a lok at: os-sim/scripts/correlation/notes
-- If you want to insert the pluigin_sid data, keep in mind that your ossim-server may start some minutes later, while it loads the data.
-- To insert it:
-- 1.- bunzip2 os-sim/db/service_tables.sql.bz2
-- 2.- mysql -p ossim < service_tables.sql

DELETE FROM plugin WHERE id = "5002";
DELETE FROM plugin_sid where plugin_id = "5002";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (5002, 4, "services", "Services / Ports");
