-- xtera-ascenlink
-- Xtera's WAN Load Balancer
-- plugin_id: 1660,1661

DELETE FROM plugin WHERE id=1660 OR id=1661;
DELETE FROM plugin_sid WHERE plugin_id=1660 OR plugin_id=1661;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1660, 1, 'ascenlink-network', 'Xtera AscenLink WAN Load Balancer - Network');
INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1661, 1, 'ascenlink-system', 'Xtera AscenLink WAN Load Balancer - System');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 1, 'xtera-ascenlink: ICMP echo reply');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 3, 'xtera-ascenlink: ICMP dest unreachable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 4, 'xtera-ascenlink: ICMP source quench');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 5, 'xtera-ascenlink: ICMP redirect');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 8, 'xtera-ascenlink: ICMP echo');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 9, 'xtera-ascenlink: ICMP advertisement');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 10, 'xtera-ascenlink: ICMP solicitation');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 11, 'xtera-ascenlink: ICMP time exceeded');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 12, 'xtera-ascenlink: ICMP ip header bad');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 13, 'xtera-ascenlink: ICMP timestamp request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 14, 'xtera-ascenlink: ICMP timestamp reply');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 15, 'xtera-ascenlink: ICMP information request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 16, 'xtera-ascenlink: ICMP information reply');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 17, 'xtera-ascenlink: ICMP address mask request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 18, 'xtera-ascenlink: ICMP address mask reply');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES
 (1660, 20, 'xtera-ascenlink: ICMP protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 21, 'xtera-ascenlink: GRE (Generic Route Encapsulation) protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES
 (1660, 25, 'xtera-ascenlink: UDP protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 30, 'xtera-ascenlink: TCP protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES
 (1660, 41, 'xtera-ascenlink: DNS protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 42, 'xtera-ascenlink: FTP protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 43, 'xtera-ascenlink: H323 protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 44, 'xtera-ascenlink: HTTP protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 45, 'xtera-ascenlink: HTTPS protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 46, 'xtera-ascenlink: IMAP protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 47, 'xtera-ascenlink: NTP protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 48, 'xtera-ascenlink: POP3 protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 49, 'xtera-ascenlink: RDP protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 50, 'xtera-ascenlink: SMTP protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 51, 'xtera-ascenlink: SNMP protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 52, 'xtera-ascenlink: SSH protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 53, 'xtera-ascenlink: TELNET protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 80, 'xtera-ascenlink: pcAnywhere-D protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1660, 81, 'xtera-ascenlink: pcAnywhere-S protocol');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1661, 101, 'xtera-ascenlink: User logged in');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1661, 102, 'xtera-ascenlink: User logged out');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1661, 103, 'xtera-ascenlink: User password');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1661, 104, 'xtera-ascenlink: Failed to browse');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1661, 105, 'xtera-ascenlink: Failed to push');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1661, 106, 'xtera-ascenlink: WLAN link failure');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1661, 107, 'xtera-ascenlink: WLAN link recovery');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1661, 108, 'xtera-ascenlink: Settings applied for page');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1661, 109, 'xtera-ascenlink: System reboot');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1661, 110, 'xtera-ascenlink: Pushing log is finished');
