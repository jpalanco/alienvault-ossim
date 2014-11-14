
-- Actions
REPLACE INTO `alienvault_api`.`status_action` (`action_id`,`is_admin`,`content`,`link`) VALUES
(0,false,'An account with administrative privileges is required to fix this issue.','none'),
(1001,true,'<<Configure>> data source','AV_PATH/asset_details/enable_plugin.php?asset_id=ASSET_ID'),
(1002,true,'<<Configure>> data source plugin','AV_PATH/asset_details/enable_plugin.php?asset_id=ASSET_ID&enable=true'),
(1003,true,'<<Confirm>> AlienVault Sensor is running normally','AV_PATH/sensor/sensor.php?m_opt=configuration&sm_opt=deployment&h_opt=components&l_opt=sensors'),
(1004,true,'<<Validate>> there is network connectivity between Sensor and asset','AV_PATH/netscan/index.php?m_opt=environment&sm_opt=assets&h_opt=asset_discovery&action=custom_scan&host_id=ASSET_ID&sensor=automatic&scan_mode=fast'),
(1005,true,'<<Confirm>> the data source plugin is properly enabled','AV_PATH/assets/enable_plugin.php?asset_id=ASSET_ID'),
(1010,true,'<<Confirm>> network load does not exceed supported load for AlienVault Sensor','AV_PATH/ntop/index.php?opc=services&m_opt=environment&sm_opt=profiles&h_opt=services'),
(1011,true,'<<Verify>> system resources are not overloaded','AV_PATH/av_center/index.php?m_opt=configuration&sm_opt=deployment&h_opt=components'),
(1020,true,'Clean cache of software updates:\n1. Go to Alienvault console (Alienvault-setup)\n2. Maintenance & Troubleshooting / Mantain Disk and Logs / Clear System Update Cache',''),
(1021,true,'Purge old System logs:\n1. Go to AlienVault console (alienvault-setup)\n2. Maintenance & Troubleshooting / Mantain Disk and Logs / Purge Old System Logs',''),
(1022,true,'Adjust your <<Backup options>>','AV_PATH/conf/index.php?m_opt=configuration&sm_opt=administration&h_opt=main&open=0'),
(1030,true,'Configure an internal DNS:\n1. Go to Alienvault console (Alienvault-setup)\n2. System Preferences / Configure Network / Name Server DNS',''),
(1040,true,'Confirm if the remote system is up and reachable',''),
(1041,true,'Use the following form to configure the remote system: <<Authenticate>> the remote system','AV_PATH/av_center/data/sections/main/add_system.php?id=ASSET_ID'),
(1050,true,'<<Update>> the system','AV_PATH/av_center/index.php?m_opt=configuration&sm_opt=deployment&h_opt=components');

-- Messages
REPLACE INTO `alienvault_api`.`status_message` (`id`,`level`,`description`,`content`) VALUES
(1,'info','Enable log management','As of TIMESTAMP, no logs have been processed for this asset. Integrating logs from your assets allows for more accurate correlation and retention of these logs may be necessary for compliance. This process should only take 3-5 minutes.'),
(2,'warning','Asset logs are not being processed','The asset is sending logs to the system but they are not being processed. Ensure that the appropriate data source plugin is enabled. At TIMESTAMP'),
(3,'warning','Log management disrupted','The system has not received a log from this asset in more than 24 hours. This may be an indicator of the asset having connection difficulties with AlienVault or a disruptive configuration change on the asset. At TIMESTAMP'),
(4,'warning','Unable to analyze all network traffic','The system is receiving more packets than it can process, causing packet loss. This will result in some network traffic being excluded from analysis. Fix this issue to ensure full network visibility. At TIMESTAMP'),
(5,'warning','Unable to analyze all network traffic','The system is receiving network packets that it can not process, likely due to malformed packets or unsupported packet sizes or  network protocols. This will result in some network traffic being excluded from analysis. Fix this issue to ensure full network visibility. At TIMESTAMP'),
(6,'warning','Disk space is low','The system has less than 25% of the total disk space available. Please address this issue soon to avoid a service disruption. At TIMESTAMP'),
(7,'error','Disk space is critically low','The system has less than 10% of the total disk space available. Please address this issue immediately to avoid a service disruption. At TIMESTAMP'),
(8,'info','Configured DNS is external',"The configured Domain Name Server is external to your environment. This will cause your asset names won't be discovered. At TIMESTAMP"),
(9,'error','The remote system is not connected to the AlienVault API','The remote system is not connected to the AlienVault API. The remote system is unreachable or it has not been configured properly. At TIMESTAMP'),
(10,'notification','New Updates Available','New system updates pending. At TIMESTAMP'),
(11,'notification','Sensor connection lost','Can not connect to the sensor. At TIMESTAMP');

-- Actions for messages
REPLACE INTO `alienvault_api`.`status_message_action` (`message_id`,`action_id`) VALUES
(1,1001),
(2,1002),
(3,1003),
(3,1004),
(3,1005),
(4,1010),
(4,1011),
(5,1010),
(6,1020),
(6,1021),
(6,1022),
(7,1020),
(7,1021),
(7,1022),
(8,1030),
(9,1040),
(9,1041),
(10,1050),
(11,1003),
(11,1040),
(11,1041);
