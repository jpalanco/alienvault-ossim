-- Alteon OS
-- plugin_id: 1684

DELETE FROM plugin WHERE id = "1684";
DELETE FROM plugin_sid where plugin_id = "1684";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES
	(1684, 1, 'AlteonOS', 'Alteon OS (Nortel Switches)');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES
	(1684, 10, 'Alteon OS (Nortel): [STP] Spanning Tree event', 1, 1),
	(1684, 20, 'Alteon OS (Nortel): IP event', 1, 1),
	(1684, 30, 'Alteon OS (Nortel): [SLB] Server Load Balancing event', 1, 1),
	(1684, 40, 'Alteon OS (Nortel): [GSLB] Global Server Load Balancing event', 1, 1),
	(1684, 50, 'Alteon OS (Nortel): Console event', 1, 1),
	(1684, 60, 'Alteon OS (Nortel): Telnet event', 1, 1),
	(1684, 70, 'Alteon OS (Nortel): [VRRP] Virtual Router Redundancy event', 1, 1),
	(1684, 80, 'Alteon OS (Nortel): System event', 1, 1),
	(1684, 90, 'Alteon OS (Nortel): Web Server event', 1, 1),
	(1684, 100, 'Alteon OS (Nortel): SSH event', 1, 1),
	(1684, 110, 'Alteon OS (Nortel): [BGP] Border Gateway event', 1, 1),
	(1684, 120, 'Alteon OS (Nortel): Filter event', 1, 1),
	(1684, 130, 'Alteon OS (Nortel): DPS event', 1, 1),
	(1684, 140, 'Alteon OS (Nortel): Syn_Atk event', 1, 1),
	(1684, 150, 'Alteon OS (Nortel): Tcplim event', 1, 1),
	(1684, 160, 'Alteon OS (Nortel): Management event', 1, 1),
	(1684, 170, 'Alteon OS (Nortel): [NTP] Network Time event', 1, 1),
	(1684, 180, 'Alteon OS (Nortel): Cli event', 1, 1);

