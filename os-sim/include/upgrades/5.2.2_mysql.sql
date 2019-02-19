USE alienvault;

DELIMITER $$

DROP PROCEDURE IF EXISTS upgrade_task_inventory_to_5_2_2 $$
CREATE PROCEDURE upgrade_task_inventory_to_5_2_2()
BEGIN

-- add a column safely (only when it doesn't exist)
IF NOT EXISTS (
  (SELECT NULL FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA="alienvault"
   AND TABLE_NAME="task_inventory"
   AND COLUMN_NAME="task_last_run")
) THEN
  ALTER TABLE alienvault.task_inventory
  ADD COLUMN task_last_run INT(11) NOT NULL DEFAULT 0;
END IF;

END $$

CALL upgrade_task_inventory_to_5_2_2() $$

DELIMITER ;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2016-02-23');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.2.2');

INSERT INTO config (conf, value) VALUES ('nessus_admin_user', 'ovas-super-admin');
INSERT INTO config (conf, value) VALUES ('nessus_admin_pass', 'ovas-super-admin');

INSERT INTO device_types (id,name,class) VALUES
(110,'Active Directory Server / Domain Controller',1),
(111,'Web Application Firewall',1),
(112,'Firewall',1),
(113,'IDS/IPS',1),
(114,'DDOS Protection',1),
(115,'Anti-Virus',1),
(116,'Network Defense (Other)',1),
(117,'Time Server',1),
(118,'Monitoring Tools Server (Nagios, Tivoli, usw.)',1),
(119,'Database Server',1),
(120,'VPN Gateway',1),
(121,'Workstation',1),
(122,'Application Server (Generic)',1),
(802,'Television',8),
(803,'Set Top Box',8),
(804,'IoT Device (Other)',8),
(123,'Virtual Host',1),
(504,'Uninterrupted Power Supply (UPS)',5),
(505,'Power Distribution Unit (PDU)',5),
(506,'Environmental Monitoring',5),
(507,'Peripheral (Other)',5),
(508,'IPMI',5),
(509,'RAID',5),
(200,'Laptop',2),
(201,'Endpoint (Other)',2),
(202,'Workstation',2),
(124,'Payment Server (ACI in particular)',1),
(125,'Point of Sale Controller',1),
(126,'Server (Other)',1),
(127,'Web Server',1);


UPDATE device_types SET name = "Cell Phone" WHERE id = 301;
UPDATE device_types SET name = "Intrusion Prevention System (IPS)" WHERE id = 703;
UPDATE device_types SET name = "Intrusion Detection System (IDS)" WHERE id = 702;


COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
