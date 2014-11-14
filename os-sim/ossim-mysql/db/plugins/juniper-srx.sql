-- juniper-srx
-- plugin_id: 1626

DELETE FROM plugin WHERE id = "1626";
DELETE FROM plugin_sid where plugin_id = "1626";

INSERT IGNORE INTO `plugin` (`id` , `type` , `name` , `description` , `vendor`) VALUES (
'1626', '1', 'Juniper-SRX', 'Juniper-SRX Router/Firewall/IDS/IPS', 'Juniper'
);

INSERT IGNORE INTO `plugin_sid` (`plugin_id`, `sid`, `reliability`, `priority`, `name`) VALUES
(1626, 10, 1, 2, 'Juniper-SRX: RT_FLOW: SESSION DENY'),
(1626, 20, 1, 1, 'Juniper-SRX: KMD: PM P2 POLICY LOOKUP FAILURE'),
(1626, 30, 1, 1, 'Juniper-SRX: KERNEL: MAC ADDRESS CHANGE'),
(1626, 40, 1, 3, 'Juniper-SRX: RT_IDS: RT SCREEN TCP'),
(1626, 51, 1, 1, 'Juniper-SRX: MGD: UI AUTH EVENT'),
(1626, 52, 1, 1, 'Juniper-SRX: MGD: UI LOGIN EVENT'),
(1626, 53, 1, 1, 'Juniper-SRX: MGD: UI CMDLINE READ LINE'),
(1626, 60, 1, 1, 'Juniper-SRX: RT_IPSEC: BAD SPI');
