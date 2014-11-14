-- motorola firewall
-- plugin_id: 1633

DELETE FROM plugin WHERE id = "1633";
DELETE FROM plugin_sid where plugin_id = "1633";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1633, 1, 'motorola firewall', 'Motorola RFS Series Firewall');

INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1633, 1008, 'motorola-firewall: IpSpoofing detected, dropping packet', 1, 1);
INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1633, 1013, 'motorola-firewall: TCP connection request received is invalid, dropping packet', 1, 1);
INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1633, 1030, 'motorola-firewall: Dropping ICMP packet', 1, 1);
INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1633, 1042, 'motorola-firewall: Packet recieved after RST from the same direction', 1, 1);
INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1633, 1062, 'motorola-firewall: Packet with acknowledgement number out of range detected,dropping packet', 1, 1);
INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1633, 1078, 'motorola-firewall: ICMP error message replay attack detected, dropping packet', 1, 1);
INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1633, 1079, 'motorola-firewall: Invalid TCP Packet Recieved before 3-way Handshake is complete', 1, 1);
INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1633, 1081, 'motorola-firewall: Icmp error message received for uninitiated connection', 1, 1);
INSERT IGNORE INTO plugin_sid (`plugin_id`, `sid`, `name`, `priority`, `reliability`) VALUES (1633, 1084, 'motorola-firewall: Connection closed by RST before 3-way Handshake is complete', 1, 1);
