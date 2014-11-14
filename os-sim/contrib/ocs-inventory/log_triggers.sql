-- OCSWEB Tiggers 
-- Log into table hardware changes on follow tables:
-- 
-- bios
-- controllers
-- drives
-- inputs
-- locks
-- memories
-- modems
-- monitors
-- networks
-- ports
-- printers
-- registry
-- slots
-- softwares
-- sounds
-- storages
-- videos
CREATE TABLE IF NOT EXISTS Log (
  action ENUM('create','update','delete'),
  from_table  varchar(64),
  id  INT(11) NOT NULL,
  hardware_id  INT(11) NOT NULL,
  date TIMESTAMP,
  data TEXT,
  PRIMARY KEY (action,from_table,id,hardware_id)
);
--
DELIMITER $$
--
-- bios triggers
--
DROP TRIGGER IF EXISTS i_bios$$
CREATE TRIGGER i_bios AFTER INSERT ON bios
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','bios',NEW.HARDWARE_ID,NEW.HARDWARE_ID,NOW(),CONCAT('SMANUFACTURER: ',NEW.SMANUFACTURER, '\nSMODEL: ', NEW.SMODEL, '\nSSN: ', NEW.SSN, '\nBVERSION: ', NEW.BVERSION, '\nTYPE: ', NEW.TYPE, '\nBMANUFACTURER: ', NEW.BMANUFACTURER , '\nBVERSION: ', NEW.BVERSION, '\nBDATE: ', NEW.BDATE));
END$$

DROP TRIGGER IF EXISTS u_bios$$
CREATE TRIGGER u_bios AFTER UPDATE ON bios
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','bios',NEW.HARDWARE_ID,NEW.HARDWARE_ID,NOW(),CONCAT('SMANUFACTURER: ',NEW.SMANUFACTURER, '\nSMODEL: ', NEW.SMODEL, '\nSSN: ', NEW.SSN, '\nBVERSION: ', NEW.BVERSION, '\nTYPE: ', NEW.TYPE, '\nBMANUFACTURER: ', NEW.BMANUFACTURER , '\nBVERSION: ', NEW.BVERSION, '\nBDATE: ', NEW.BDATE));
END$$

DROP TRIGGER IF EXISTS d_bios$$
CREATE TRIGGER d_bios AFTER DELETE ON bios
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','bios',OLD.HARDWARE_ID,OLD.HARDWARE_ID,NOW(),CONCAT('SMANUFACTURER: ',OLD.SMANUFACTURER, '\nSMODEL: ', OLD.SMODEL, '\nSSN: ', OLD.SSN, '\nBVERSION: ', OLD.BVERSION, '\nTYPE: ', OLD.TYPE, '\nBMANUFACTURER: ', OLD.BMANUFACTURER , '\nBVERSION: ', OLD.BVERSION, '\nBDATE: ', OLD.BDATE));
END$$
--
-- controllers triggers
--
DROP TRIGGER IF EXISTS i_controllers$$
CREATE TRIGGER i_controllers AFTER INSERT ON controllers
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','controllers',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',NEW.MANUFACTURER, '\nNAME: ', NEW.NAME, '\nCAPTION: ', NEW.CAPTION, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nTYPE: ', NEW.TYPE, '\nVERSION: ', NEW.VERSION));
END$$

DROP TRIGGER IF EXISTS u_controllers$$
CREATE TRIGGER u_controllers AFTER UPDATE ON controllers
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','controllers',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',NEW.MANUFACTURER, '\nNAME: ', NEW.NAME, '\nCAPTION: ', NEW.CAPTION, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nTYPE: ', NEW.TYPE, '\nVERSION: ', NEW.VERSION));
END$$

DROP TRIGGER IF EXISTS d_controllers$$
CREATE TRIGGER d_controllers AFTER DELETE ON controllers
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','controllers',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',OLD.MANUFACTURER, '\nNAME: ', OLD.NAME, '\nCAPTION: ', OLD.CAPTION, '\nDESCRIPTION: ', OLD.DESCRIPTION, '\nTYPE: ', OLD.TYPE, '\nVERSION: ', OLD.VERSION));
END$$
--
-- drives triggers
--
DROP TRIGGER IF EXISTS i_drives$$
CREATE TRIGGER i_drives AFTER INSERT ON drives
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','drives',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('LETTER: ',NEW.LETTER,'\nTYPE: ', NEW.TYPE, '\nFILESYSTEM: ', NEW.FILESYSTEM, '\nTOTAL: ', NEW.TOTAL, '\nFREE: ', NEW.FREE, '\nNUMFILES: ', NEW.NUMFILES, '\nVOLUMN: ', NEW.VOLUMN));
END$$

DROP TRIGGER IF EXISTS u_drives$$
CREATE TRIGGER u_drives AFTER UPDATE ON drives
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','drives',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('LETTER: ',NEW.LETTER, '\nTYPE: ', NEW.TYPE, '\nFILESYSTEM: ', NEW.FILESYSTEM, '\nTOTAL: ', NEW.TOTAL, '\nFREE: ', NEW.FREE, '\nNUMFILES: ', NEW.NUMFILES, '\nVOLUMN: ', NEW.VOLUMN));
END$$

DROP TRIGGER IF EXISTS d_drives$$
CREATE TRIGGER d_drives AFTER DELETE ON drives
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','drives',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('LETTER: ',OLD.LETTER, '\nTYPE: ', OLD.TYPE, '\nFILESYSTEM: ', OLD.FILESYSTEM, '\nTOTAL: ', OLD.TOTAL, '\nFREE: ', OLD.FREE, '\nNUMFILES: ', OLD.NUMFILES, '\nVOLUMN: ', OLD.VOLUMN));
END$$
--
-- inputs triggers
--
DROP TRIGGER IF EXISTS i_inputs$$
CREATE TRIGGER i_inputs AFTER INSERT ON inputs
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','inputs',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',NEW.MANUFACTURER,'\nTYPE: ', '\nCAPTION: ', NEW.CAPTION, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nINTERFACE: ', NEW.INTERFACE, '\nPOINTTYPE: ', NEW.POINTTYPE));
END$$

DROP TRIGGER IF EXISTS u_inputs$$
CREATE TRIGGER u_inputs AFTER UPDATE ON inputs
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','inputs',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',NEW.MANUFACTURER, '\nTYPE: ', NEW.TYPE, '\nCAPTION: ', NEW.CAPTION, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nINTERFACE: ', NEW.INTERFACE, '\nPOINTTYPE: ', NEW.POINTTYPE));
END$$

DROP TRIGGER IF EXISTS d_inputs$$
CREATE TRIGGER d_inputs AFTER DELETE ON inputs
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','inputs',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',OLD.MANUFACTURER, '\nTYPE: ', OLD.TYPE, '\nCAPTION: ', OLD.CAPTION, '\nDESCRIPTION: ', OLD.DESCRIPTION, '\nINTERFACE: ', OLD.INTERFACE, '\nPOINTTYPE: ', OLD.POINTTYPE));
END$$
--
-- locks triggers
--
DROP TRIGGER IF EXISTS i_locks$$
CREATE TRIGGER i_locks AFTER INSERT ON locks
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','locks',NEW.HARDWARE_ID,NEW.HARDWARE_ID,NOW(),CONCAT('ID: ', NEW.ID, '\nSINCE: ', NEW.SINCE));
END$$

DROP TRIGGER IF EXISTS u_locks$$
CREATE TRIGGER u_locks AFTER UPDATE ON locks
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','locks',NEW.HARDWARE_ID,NEW.HARDWARE_ID,NOW(),CONCAT('ID: ', NEW.ID, '\nSINCE: ', NEW.SINCE));
END$$

DROP TRIGGER IF EXISTS d_locks$$
CREATE TRIGGER d_locks AFTER DELETE ON locks
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','locks',OLD.HARDWARE_ID,OLD.HARDWARE_ID,NOW(),CONCAT('ID: ', OLD.ID, '\nSINCE: ', OLD.SINCE));
END$$
--
-- memories triggers
--
DROP TRIGGER IF EXISTS i_memories$$
CREATE TRIGGER i_memories AFTER INSERT ON memories
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','memories',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('CAPTION: ',NEW.CAPTION, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nCAPACITY: ', NEW.CAPACITY, '\nPURPOSE: ', NEW.PURPOSE, '\nTYPE: ', NEW.TYPE, '\nSPEED: ', NEW.SPEED, '\nNUMSLOTS: ', NEW.NUMSLOTS));
END$$

DROP TRIGGER IF EXISTS u_memories$$
CREATE TRIGGER u_memories AFTER UPDATE ON memories
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','memories',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('CAPTION: ',NEW.CAPTION, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nCAPACITY: ', NEW.CAPACITY, '\nPURPOSE: ', NEW.PURPOSE, '\nTYPE: ', NEW.TYPE, '\nSPEED: ', NEW.SPEED, '\nNUMSLOTS: ', NEW.NUMSLOTS));
END$$

DROP TRIGGER IF EXISTS d_memories$$
CREATE TRIGGER d_memories AFTER DELETE ON memories
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','memories',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('CAPTION: ',OLD.CAPTION, '\nDESCRIPTION: ', OLD.DESCRIPTION, '\nCAPACITY: ', OLD.CAPACITY, '\nPURPOSE: ', OLD.PURPOSE, '\nTYPE: ', OLD.TYPE, '\nSPEED: ', OLD.SPEED, '\nNUMSLOTS: ', OLD.NUMSLOTS));
END$$
--
-- modems triggers
--
DROP TRIGGER IF EXISTS i_modems$$
CREATE TRIGGER i_modems AFTER INSERT ON modems
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','modems',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('NAME: ',NEW.NAME, '\nMODEL: ', NEW.MODEL, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nTYPE: ', NEW.TYPE));
END$$

DROP TRIGGER IF EXISTS u_modems$$
CREATE TRIGGER u_modems AFTER UPDATE ON modems
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','modems',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('NAME: ',NEW.NAME, '\nMODEL: ', NEW.MODEL, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nTYPE: ', NEW.TYPE));
END$$

DROP TRIGGER IF EXISTS d_modems$$
CREATE TRIGGER d_modems AFTER DELETE ON modems
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','modems',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('NAME: ',OLD.NAME, '\nMODEL: ', OLD.MODEL, '\nDESCRIPTION: ', OLD.DESCRIPTION, '\nTYPE: ', OLD.TYPE));
END$$
--
-- monitors triggers
--
DROP TRIGGER IF EXISTS i_monitors$$
CREATE TRIGGER i_monitors AFTER INSERT ON monitors
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','monitors',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',NEW.MANUFACTURER, '\nCAPTION: ', NEW.CAPTION, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nTYPE: ', NEW.TYPE, '\nSERIAL: ', NEW.SERIAL));
END$$

DROP TRIGGER IF EXISTS u_monitors$$
CREATE TRIGGER u_monitors AFTER UPDATE ON monitors
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','monitors',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',NEW.MANUFACTURER, '\nCAPTION: ', NEW.CAPTION, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nTYPE: ', NEW.TYPE, '\nSERIAL: ', NEW.SERIAL));
END$$

DROP TRIGGER IF EXISTS d_monitors$$
CREATE TRIGGER d_monitors AFTER DELETE ON monitors
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','monitors',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',OLD.MANUFACTURER, '\nCAPTION: ', OLD.CAPTION, '\nDESCRIPTION: ', OLD.DESCRIPTION, '\nTYPE: ', OLD.TYPE, '\nSERIAL: ', OLD.SERIAL));
END$$
--
-- networks triggers
--
DROP TRIGGER IF EXISTS i_networks$$
CREATE TRIGGER i_networks AFTER INSERT ON networks
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','networks',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('DESCRIPTION: ',NEW.DESCRIPTION, '\nTYPE: ', NEW.TYPE, '\nTYPEMIB: ', NEW.TYPEMIB, '\nSPEED: ', NEW.SPEED, '\nMACADDR: ', NEW.MACADDR, '\nSTATUS: ', NEW.STATUS, '\nIPADDRESS: ', NEW.IPADDRESS, '\nIPMASK: ', NEW.IPMASK, '\nIPGATEWAY: ', NEW.IPGATEWAY, '\nIPSUBNET: ', NEW.IPSUBNET, '\nIPDHCP: ', NEW.IPDHCP));
END$$

DROP TRIGGER IF EXISTS u_networks$$
CREATE TRIGGER u_networks AFTER UPDATE ON networks
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','networks',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('DESCRIPTION: ',NEW.DESCRIPTION, '\nTYPE: ', NEW.TYPE, '\nTYPEMIB: ', NEW.TYPEMIB, '\nSPEED: ', NEW.SPEED, '\nMACADDR: ', NEW.MACADDR, '\nSTATUS: ', NEW.STATUS, '\nIPADDRESS: ', NEW.IPADDRESS, '\nIPMASK: ', NEW.IPMASK, '\nIPGATEWAY: ', NEW.IPGATEWAY, '\nIPSUBNET: ', NEW.IPSUBNET, '\nIPDHCP: ', NEW.IPDHCP));
END$$

DROP TRIGGER IF EXISTS d_networks$$
CREATE TRIGGER d_networks AFTER DELETE ON networks
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','networks',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('DESCRIPTION: ',OLD.DESCRIPTION, '\nTYPE: ', OLD.TYPE, '\nTYPEMIB: ', OLD.TYPEMIB, '\nSPEED: ', OLD.SPEED, '\nMACADDR: ', OLD.MACADDR, '\nSTATUS: ', OLD.STATUS, '\nIPADDRESS: ', OLD.IPADDRESS, '\nIPMASK: ', OLD.IPMASK, '\nIPGATEWAY: ', OLD.IPGATEWAY, '\nIPSUBNET: ', OLD.IPSUBNET, '\nIPDHCP: ', OLD.IPDHCP));
END$$
--
-- ports triggers
--
DROP TRIGGER IF EXISTS i_ports$$
CREATE TRIGGER i_ports AFTER INSERT ON ports
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','ports',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('TYPE: ', NEW.TYPE, '\nNAME: ', NEW.NAME, '\nCAPTION: ', NEW.CAPTION, '\nDESCRIPTION: ',NEW.DESCRIPTION));
END$$

DROP TRIGGER IF EXISTS u_ports$$
CREATE TRIGGER u_ports AFTER UPDATE ON ports
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','ports',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('TYPE: ', NEW.TYPE, '\nNAME: ', NEW.NAME, '\nCAPTION: ', NEW.CAPTION, '\nDESCRIPTION: ',NEW.DESCRIPTION));
END$$

DROP TRIGGER IF EXISTS d_ports$$
CREATE TRIGGER d_ports AFTER DELETE ON ports
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','ports',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('TYPE: ', OLD.TYPE, '\nNAME: ', OLD.NAME, '\nCAPTION: ', OLD.CAPTION, '\nDESCRIPTION: ',OLD.DESCRIPTION));
END$$
--
-- printers triggers
--
DROP TRIGGER IF EXISTS i_printers$$
CREATE TRIGGER i_printers AFTER INSERT ON printers
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','printers',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('NAME: ', NEW.NAME, '\nDRIVER: ', NEW.DRIVER, '\nPORT: ',NEW.PORT));
END$$

DROP TRIGGER IF EXISTS u_printers$$
CREATE TRIGGER u_printers AFTER UPDATE ON printers
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','printers',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('NAME: ', NEW.NAME, '\nDRIVER: ', NEW.DRIVER, '\nPORT: ',NEW.PORT));
END$$

DROP TRIGGER IF EXISTS d_printers$$
CREATE TRIGGER d_printers AFTER DELETE ON printers
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','printers',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('NAME: ', OLD.NAME, '\nDRIVER: ', OLD.DRIVER, '\nPORT: ',OLD.PORT));
END$$
--
-- registry triggers
--
DROP TRIGGER IF EXISTS i_registry$$
CREATE TRIGGER i_registry AFTER INSERT ON registry
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','registry',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('NAME: ', NEW.NAME, '\nREGVALUE: ',NEW.REGVALUE));
END$$

DROP TRIGGER IF EXISTS u_registry$$
CREATE TRIGGER u_registry AFTER UPDATE ON registry
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','registry',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('NAME: ', NEW.NAME, '\nREGVALUE: ',NEW.REGVALUE));
END$$

DROP TRIGGER IF EXISTS d_registry$$
CREATE TRIGGER d_registry AFTER DELETE ON registry
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','registry',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('NAME: ', OLD.NAME, '\nREGVALUE: ',OLD.REGVALUE));
END$$
--
-- slots triggers
--
DROP TRIGGER IF EXISTS i_slots$$
CREATE TRIGGER i_slots AFTER INSERT ON slots
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','slots',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('NAME: ', NEW.NAME, '\nDESCRIPTION: ',NEW.DESCRIPTION, '\nDESIGNATION: ', NEW.DESIGNATION,'\nPURPOSE: ', NEW.PURPOSE, '\nSTATUS: ',NEW.STATUS, '\nPSHARE: ', NEW.PSHARE));
END$$

DROP TRIGGER IF EXISTS u_slots$$
CREATE TRIGGER u_slots AFTER UPDATE ON slots
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','slots',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('NAME: ', NEW.NAME, '\nDESCRIPTION: ',NEW.DESCRIPTION, '\nDESIGNATION: ', NEW.DESIGNATION,'\nPURPOSE: ', NEW.PURPOSE, '\nSTATUS: ',NEW.STATUS, '\nPSHARE: ', NEW.PSHARE));
END$$

DROP TRIGGER IF EXISTS d_slots$$
CREATE TRIGGER d_slots AFTER DELETE ON slots
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','slots',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('NAME: ', OLD.NAME, '\nDESCRIPTION: ',OLD.DESCRIPTION, '\nDESIGNATION: ', OLD.DESIGNATION,'\nPURPOSE: ', OLD.PURPOSE, '\nSTATUS: ',OLD.STATUS, '\nPSHARE: ', OLD.PSHARE));
END$$
--
-- softwares triggers
--
DROP TRIGGER IF EXISTS i_softwares$$
CREATE TRIGGER i_softwares AFTER INSERT ON softwares
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','softwares',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('PUBLISHER: ',NEW.PUBLISHER, '\nNAME: ', NEW.NAME, '\nVERSION: ', NEW.VERSION, '\nFOLDER: ', NEW.FOLDER, '\nCOMMENTS: ',NEW.COMMENTS, '\nFILENAME: ', NEW.FILENAME, '\nFILESIZE: ',NEW.FILESIZE, '\nSOURCE: ', NEW.SOURCE));
END$$

DROP TRIGGER IF EXISTS u_softwares$$
CREATE TRIGGER u_softwares AFTER UPDATE ON softwares
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','softwares',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('PUBLISHER: ',NEW.PUBLISHER, '\nNAME: ', NEW.NAME, '\nVERSION: ', NEW.VERSION, '\nFOLDER: ', NEW.FOLDER, '\nCOMMENTS: ',NEW.COMMENTS, '\nFILENAME: ', NEW.FILENAME, '\nFILESIZE: ',NEW.FILESIZE, '\nSOURCE: ', NEW.SOURCE));
END$$

DROP TRIGGER IF EXISTS d_softwares$$
CREATE TRIGGER d_softwares AFTER DELETE ON softwares
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','softwares',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('PUBLISHER: ',OLD.PUBLISHER, '\nNAME: ', OLD.NAME, '\nVERSION: ', OLD.VERSION, '\nFOLDER: ', OLD.FOLDER, '\nCOMMENTS: ',OLD.COMMENTS, '\nFILENAME: ', OLD.FILENAME, '\nFILESIZE: ',OLD.FILESIZE, '\nSOURCE: ', OLD.SOURCE));
END$$
--
-- sounds triggers
--
DROP TRIGGER IF EXISTS i_sounds$$
CREATE TRIGGER i_sounds AFTER INSERT ON sounds
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','sounds',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',NEW.MANUFACTURER, '\nNAME: ', NEW.NAME, '\nDESCRIPTION: ', NEW.DESCRIPTION));
END$$

DROP TRIGGER IF EXISTS u_sounds$$
CREATE TRIGGER u_sounds AFTER UPDATE ON sounds
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','sounds',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',NEW.MANUFACTURER, '\nNAME: ', NEW.NAME, '\nDESCRIPTION: ', NEW.DESCRIPTION));
END$$

DROP TRIGGER IF EXISTS d_sounds$$
CREATE TRIGGER d_sounds AFTER DELETE ON sounds
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','sounds',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',OLD.MANUFACTURER, '\nNAME: ', OLD.NAME, '\nDESCRIPTION: ', OLD.DESCRIPTION));
END$$
--
-- storages triggers
--
DROP TRIGGER IF EXISTS i_storages$$
CREATE TRIGGER i_storages AFTER INSERT ON storages
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','storages',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',NEW.MANUFACTURER, '\nNAME: ', NEW.NAME, '\nMODEL: ', NEW.MODEL, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nTYPE: ', NEW.TYPE, '\nDISKSIZE: ', NEW.DISKSIZE));
END$$

DROP TRIGGER IF EXISTS u_storages$$
CREATE TRIGGER u_storages AFTER UPDATE ON storages
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','storages',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',NEW.MANUFACTURER, '\nNAME: ', NEW.NAME, '\nMODEL: ', NEW.MODEL, '\nDESCRIPTION: ', NEW.DESCRIPTION, '\nTYPE: ', NEW.TYPE, '\nDISKSIZE: ', NEW.DISKSIZE));
END$$

DROP TRIGGER IF EXISTS d_storages$$
CREATE TRIGGER d_storages AFTER DELETE ON storages
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','storages',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('MANUFACTURER: ',OLD.MANUFACTURER, '\nNAME: ', OLD.NAME, '\nMODEL: ', OLD.MODEL, '\nDESCRIPTION: ', OLD.DESCRIPTION, '\nTYPE: ', OLD.TYPE, '\nDISKSIZE: ', OLD.DISKSIZE));
END$$
--
-- videos triggers
--
DROP TRIGGER IF EXISTS i_videos$$
CREATE TRIGGER i_videos AFTER INSERT ON videos
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('create','videos',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('NAME: ', NEW.NAME, '\nCHIPSET: ', NEW.CHIPSET, '\nMEMORY: ', NEW.MEMORY, '\nRESOLUTION: ', NEW.RESOLUTION));
END$$

DROP TRIGGER IF EXISTS u_videos$$
CREATE TRIGGER u_videos AFTER UPDATE ON videos
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('update','videos',NEW.ID,NEW.HARDWARE_ID,NOW(),CONCAT('NAME: ', NEW.NAME, '\nCHIPSET: ', NEW.CHIPSET, '\nMEMORY: ', NEW.MEMORY, '\nRESOLUTION: ', NEW.RESOLUTION));
END$$

DROP TRIGGER IF EXISTS d_videos$$
CREATE TRIGGER d_videos AFTER DELETE ON videos
FOR EACH ROW
BEGIN
  INSERT INTO Log (action,from_table,id,hardware_id,date,data)
  VALUES('delete','videos',OLD.ID,OLD.HARDWARE_ID,NOW(),CONCAT('NAME: ', OLD.NAME, '\nCHIPSET: ', OLD.CHIPSET, '\nMEMORY: ', OLD.MEMORY, '\nRESOLUTION: ', OLD.RESOLUTION));
END$$
--
--
--
DELIMITER ;


