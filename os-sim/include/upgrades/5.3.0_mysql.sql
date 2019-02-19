USE alienvault;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2016-06-17');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.3.0');

INSERT INTO `plugin_sid` VALUES ('\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',7006,700001,NULL,5,1,'ossec: Windows Console Logon',0.0000,24,2),
('\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',7006,700002,NULL,5,1,'ossec: Windows Console Logon',0.0000,24,2),
('\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',7006,700003,NULL,5,1,'ossec: Windows Network Logon',0.0000,24,2),
('\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',7006,700004,NULL,5,1,'ossec: Windows Workstation Lock',0.0000,24,2),
('\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',7006,700005,NULL,5,1,'ossec: Windows RDP-TS Logon',0.0000,24,2);

INSERT into acl_perm VALUES (NULL,'MENU','message_center-menu','MessageCenterDelete','MessageCenter',0,0,1,'13.01');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA