DELETE FROM plugin where id = '7113';
DELETE FROM plugin_sid where plugin_id = '7113';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7113, 1, "ossec-wordpress", "wordpress");
DELETE FROM plugin where id = '7112';
DELETE FROM plugin_sid where plugin_id = '7112';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7112, 1, "ossec-yum", "yum");
DELETE FROM plugin where id = '7111';
DELETE FROM plugin_sid where plugin_id = '7111';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7111, 1, "ossec-mcafee", "mcafee");
DELETE FROM plugin where id = '7110';
DELETE FROM plugin_sid where plugin_id = '7110';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7110, 1, "ossec-dhcp_ipv6", "dhcp_ipv6");
DELETE FROM plugin where id = '7117';
DELETE FROM plugin_sid where plugin_id = '7117';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7117, 1, "ossec-roundcube", "roundcube");
DELETE FROM plugin where id = '7116';
DELETE FROM plugin_sid where plugin_id = '7116';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7116, 1, "ossec-dpkg", "dpkg");
DELETE FROM plugin where id = '7115';
DELETE FROM plugin_sid where plugin_id = '7115';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7115, 1, "ossec-login_time", "login_time");
DELETE FROM plugin where id = '7114';
DELETE FROM plugin_sid where plugin_id = '7114';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7114, 1, "ossec-mse", "mse");
DELETE FROM plugin where id = '7119';
DELETE FROM plugin_sid where plugin_id = '7119';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7119, 1, "ossec-solaris_bsm", "solaris_bsm");
DELETE FROM plugin where id = '7118';
DELETE FROM plugin_sid where plugin_id = '7118';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7118, 1, "ossec-vm-pop3d", "vm-pop3d");
DELETE FROM plugin where id = '7058';
DELETE FROM plugin_sid where plugin_id = '7058';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7058, 1, "ossec-accesslog", "accesslog");
DELETE FROM plugin where id = '7059';
DELETE FROM plugin_sid where plugin_id = '7059';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7059, 1, "ossec-attack", "attack");
DELETE FROM plugin where id = '7052';
DELETE FROM plugin_sid where plugin_id = '7052';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7052, 1, "ossec-proftpd", "proftpd");
DELETE FROM plugin where id = '7053';
DELETE FROM plugin_sid where plugin_id = '7053';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7053, 1, "ossec-msftp", "msftp");
DELETE FROM plugin where id = '7050';
DELETE FROM plugin_sid where plugin_id = '7050';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7050, 1, "ossec-connection_attempt", "connection_attempt");
DELETE FROM plugin where id = '7051';
DELETE FROM plugin_sid where plugin_id = '7051';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7051, 1, "ossec-pure-ftpd", "pure-ftpd");
DELETE FROM plugin where id = '7056';
DELETE FROM plugin_sid where plugin_id = '7056';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7056, 1, "ossec-courier", "courier");
DELETE FROM plugin where id = '7057';
DELETE FROM plugin_sid where plugin_id = '7057';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7057, 1, "ossec-web", "web");
DELETE FROM plugin where id = '7054';
DELETE FROM plugin_sid where plugin_id = '7054';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7054, 1, "ossec-hordeimp", "hordeimp");
DELETE FROM plugin where id = '7055';
DELETE FROM plugin_sid where plugin_id = '7055';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7055, 1, "ossec-vpopmail", "vpopmail");
DELETE FROM plugin where id = '7045';
DELETE FROM plugin_sid where plugin_id = '7045';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7045, 1, "ossec-named", "named");
DELETE FROM plugin where id = '7044';
DELETE FROM plugin_sid where plugin_id = '7044';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7044, 1, "ossec-system_error", "system_error");
DELETE FROM plugin where id = '7047';
DELETE FROM plugin_sid where plugin_id = '7047';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7047, 1, "ossec-client_misconfig", "client_misconfig");
DELETE FROM plugin where id = '7046';
DELETE FROM plugin_sid where plugin_id = '7046';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7046, 1, "ossec-invalid_access", "invalid_access");
DELETE FROM plugin where id = '7041';
DELETE FROM plugin_sid where plugin_id = '7041';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7041, 1, "ossec-pix", "pix");
DELETE FROM plugin where id = '7040';
DELETE FROM plugin_sid where plugin_id = '7040';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7040, 1, "ossec-virus", "virus");
DELETE FROM plugin where id = '7043';
DELETE FROM plugin_sid where plugin_id = '7043';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7043, 1, "ossec-account_changed", "account_changed");
DELETE FROM plugin where id = '7042';
DELETE FROM plugin_sid where plugin_id = '7042';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7042, 1, "ossec-config_changed", "config_changed");
DELETE FROM plugin where id = '7124';
DELETE FROM plugin_sid where plugin_id = '7124';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7124, 1, "ossec-dhcp_lease_action", "dhcp_lease_action");
DELETE FROM plugin where id = '7125';
DELETE FROM plugin_sid where plugin_id = '7125';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7125, 1, "ossec-win_group_deleted", "win_group_deleted");
DELETE FROM plugin where id = '7049';
DELETE FROM plugin_sid where plugin_id = '7049';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7049, 1, "ossec-vsftpd", "vsftpd");
DELETE FROM plugin where id = '7048';
DELETE FROM plugin_sid where plugin_id = '7048';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7048, 1, "ossec-smbd", "smbd");
DELETE FROM plugin where id = '7120';
DELETE FROM plugin_sid where plugin_id = '7120';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7120, 1, "ossec-vmware", "vmware");
DELETE FROM plugin where id = '7121';
DELETE FROM plugin_sid where plugin_id = '7121';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7121, 1, "ossec-asterisk", "asterisk");
DELETE FROM plugin where id = '7030';
DELETE FROM plugin_sid where plugin_id = '7030';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7030, 1, "ossec-su", "su");
DELETE FROM plugin where id = '7031';
DELETE FROM plugin_sid where plugin_id = '7031';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7031, 1, "ossec-tripwire", "tripwire");
DELETE FROM plugin where id = '7032';
DELETE FROM plugin_sid where plugin_id = '7032';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7032, 1, "ossec-adduser", "adduser");
DELETE FROM plugin where id = '7033';
DELETE FROM plugin_sid where plugin_id = '7033';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7033, 1, "ossec-sudo", "sudo");
DELETE FROM plugin where id = '7034';
DELETE FROM plugin_sid where plugin_id = '7034';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7034, 1, "ossec-pptp", "pptp");
DELETE FROM plugin where id = '7035';
DELETE FROM plugin_sid where plugin_id = '7035';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7035, 1, "ossec-fts", "fts");
DELETE FROM plugin where id = '7036';
DELETE FROM plugin_sid where plugin_id = '7036';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7036, 1, "ossec-arpwatch", "arpwatch");
DELETE FROM plugin where id = '7037';
DELETE FROM plugin_sid where plugin_id = '7037';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7037, 1, "ossec-new_host", "new_host");
DELETE FROM plugin where id = '7038';
DELETE FROM plugin_sid where plugin_id = '7038';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7038, 1, "ossec-ip_spoof", "ip_spoof");
DELETE FROM plugin where id = '7039';
DELETE FROM plugin_sid where plugin_id = '7039';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7039, 1, "ossec-symantec", "symantec");
DELETE FROM plugin where id = '7023';
DELETE FROM plugin_sid where plugin_id = '7023';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7023, 1, "ossec-mail", "mail");
DELETE FROM plugin where id = '7022';
DELETE FROM plugin_sid where plugin_id = '7022';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7022, 1, "ossec-access_denied", "access_denied");
DELETE FROM plugin where id = '7021';
DELETE FROM plugin_sid where plugin_id = '7021';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7021, 1, "ossec-access_control", "access_control");
DELETE FROM plugin where id = '7020';
DELETE FROM plugin_sid where plugin_id = '7020';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7020, 1, "ossec-xinetd", "xinetd");
DELETE FROM plugin where id = '7027';
DELETE FROM plugin_sid where plugin_id = '7027';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7027, 1, "ossec-service_availability", "service_availability");
DELETE FROM plugin where id = '7026';
DELETE FROM plugin_sid where plugin_id = '7026';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7026, 1, "ossec-promisc", "promisc");
DELETE FROM plugin where id = '7025';
DELETE FROM plugin_sid where plugin_id = '7025';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7025, 1, "ossec-linuxkernel", "linuxkernel");
DELETE FROM plugin where id = '7024';
DELETE FROM plugin_sid where plugin_id = '7024';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7024, 1, "ossec-smartd", "smartd");
DELETE FROM plugin where id = '7029';
DELETE FROM plugin_sid where plugin_id = '7029';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7029, 1, "ossec-cron", "cron");
DELETE FROM plugin where id = '7028';
DELETE FROM plugin_sid where plugin_id = '7028';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7028, 1, "ossec-system_shutdown", "system_shutdown");
DELETE FROM plugin where id = '7018';
DELETE FROM plugin_sid where plugin_id = '7018';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7018, 1, "ossec-low_diskspace", "low_diskspace");
DELETE FROM plugin where id = '7019';
DELETE FROM plugin_sid where plugin_id = '7019';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7019, 1, "ossec-nfs", "nfs");
DELETE FROM plugin where id = '7016';
DELETE FROM plugin_sid where plugin_id = '7016';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7016, 1, "ossec-telnetd", "telnetd");
DELETE FROM plugin where id = '7017';
DELETE FROM plugin_sid where plugin_id = '7017';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7017, 1, "ossec-errors", "errors");
DELETE FROM plugin where id = '7014';
DELETE FROM plugin_sid where plugin_id = '7014';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7014, 1, "ossec-recon", "recon");
DELETE FROM plugin where id = '7015';
DELETE FROM plugin_sid where plugin_id = '7015';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7015, 1, "ossec-exploit_attempt", "exploit_attempt");
DELETE FROM plugin where id = '7012';
DELETE FROM plugin_sid where plugin_id = '7012';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7012, 1, "ossec-authentication_failures", "authentication_failures");
DELETE FROM plugin where id = '7013';
DELETE FROM plugin_sid where plugin_id = '7013';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7013, 1, "ossec-sshd", "sshd");
DELETE FROM plugin where id = '7010';
DELETE FROM plugin_sid where plugin_id = '7010';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7010, 1, "ossec-authentication_failed", "authentication_failed");
DELETE FROM plugin where id = '7011';
DELETE FROM plugin_sid where plugin_id = '7011';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7011, 1, "ossec-invalid_login", "invalid_login");
DELETE FROM plugin where id = '7096';
DELETE FROM plugin_sid where plugin_id = '7096';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7096, 1, "ossec-local", "local");
DELETE FROM plugin where id = '7097';
DELETE FROM plugin_sid where plugin_id = '7097';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7097, 1, "ossec-", "");
DELETE FROM plugin where id = '7094';
DELETE FROM plugin_sid where plugin_id = '7094';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7094, 1, "ossec-syscheck", "syscheck");
DELETE FROM plugin where id = '7095';
DELETE FROM plugin_sid where plugin_id = '7095';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7095, 1, "ossec-hostinfo", "hostinfo");
DELETE FROM plugin where id = '7092';
DELETE FROM plugin_sid where plugin_id = '7092';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7092, 1, "ossec-zeus", "zeus");
DELETE FROM plugin where id = '7093';
DELETE FROM plugin_sid where plugin_id = '7093';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7093, 1, "ossec-rootcheck", "rootcheck");
DELETE FROM plugin where id = '7090';
DELETE FROM plugin_sid where plugin_id = '7090';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7090, 1, "ossec-attacks", "attacks");
DELETE FROM plugin where id = '7091';
DELETE FROM plugin_sid where plugin_id = '7091';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7091, 1, "ossec-elevation_of_privilege", "elevation_of_privilege");
DELETE FROM plugin where id = '7098';
DELETE FROM plugin_sid where plugin_id = '7098';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7098, 1, "ossec-ftpd", "ftpd");
DELETE FROM plugin where id = '7099';
DELETE FROM plugin_sid where plugin_id = '7099';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7099, 1, "ossec-win_group_created", "win_group_created");
DELETE FROM plugin where id = '8000';
DELETE FROM plugin_sid where plugin_id = '8000';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(8000, 1, "ossec-dropbear", "dropbear");
DELETE FROM plugin where id = '8001';
DELETE FROM plugin_sid where plugin_id = '8001';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(8001, 1, "ossec-active_response", "active_response");
DELETE FROM plugin where id = '8002';
DELETE FROM plugin_sid where plugin_id = '8002';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(8002, 1, "ossec-bro", "bro");
DELETE FROM plugin where id = '8003';
DELETE FROM plugin_sid where plugin_id = '8003';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(8003, 1, "ossec-freshclam", "freshclam");
DELETE FROM plugin where id = '8004';
DELETE FROM plugin_sid where plugin_id = '8004';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(8004, 1, "ossec-groupdel", "groupdel");
DELETE FROM plugin where id = '8005';
DELETE FROM plugin_sid where plugin_id = '8005';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(8005, 1, "ossec-openbsd", "openbsd");
DELETE FROM plugin where id = '7009';
DELETE FROM plugin_sid where plugin_id = '7009';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7009, 1, "ossec-authentication_success", "authentication_success");
DELETE FROM plugin where id = '7001';
DELETE FROM plugin_sid where plugin_id = '7001';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7001, 1, "ossec-syslog", "syslog");
DELETE FROM plugin where id = '7003';
DELETE FROM plugin_sid where plugin_id = '7003';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7003, 1, "ossec-ids", "ids");
DELETE FROM plugin where id = '7002';
DELETE FROM plugin_sid where plugin_id = '7002';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7002, 1, "ossec-firewall", "firewall");
DELETE FROM plugin where id = '7005';
DELETE FROM plugin_sid where plugin_id = '7005';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7005, 1, "ossec-squid", "squid");
DELETE FROM plugin where id = '7004';
DELETE FROM plugin_sid where plugin_id = '7004';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7004, 1, "ossec-web-log", "web-log");
DELETE FROM plugin where id = '7007';
DELETE FROM plugin_sid where plugin_id = '7007';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7007, 1, "ossec-ossec", "ossec");
DELETE FROM plugin where id = '7006';
DELETE FROM plugin_sid where plugin_id = '7006';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7006, 1, "ossec-windows", "windows");
DELETE FROM plugin where id = '7081';
DELETE FROM plugin_sid where plugin_id = '7081';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7081, 1, "ossec-exchange", "exchange");
DELETE FROM plugin where id = '7080';
DELETE FROM plugin_sid where plugin_id = '7080';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7080, 1, "ossec-ms", "ms");
DELETE FROM plugin where id = '7083';
DELETE FROM plugin_sid where plugin_id = '7083';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7083, 1, "ossec-cisco_vpn", "cisco_vpn");
DELETE FROM plugin where id = '7082';
DELETE FROM plugin_sid where plugin_id = '7082';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7082, 1, "ossec-racoon", "racoon");
DELETE FROM plugin where id = '7085';
DELETE FROM plugin_sid where plugin_id = '7085';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7085, 1, "ossec-win_authentication_failed", "win_authentication_failed");
DELETE FROM plugin where id = '7084';
DELETE FROM plugin_sid where plugin_id = '7084';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7084, 1, "ossec-spamd", "spamd");
DELETE FROM plugin where id = '7087';
DELETE FROM plugin_sid where plugin_id = '7087';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7087, 1, "ossec-logs_cleared", "logs_cleared");
DELETE FROM plugin where id = '7086';
DELETE FROM plugin_sid where plugin_id = '7086';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7086, 1, "ossec-policy_changed", "policy_changed");
DELETE FROM plugin where id = '7089';
DELETE FROM plugin_sid where plugin_id = '7089';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7089, 1, "ossec-time_changed", "time_changed");
DELETE FROM plugin where id = '7088';
DELETE FROM plugin_sid where plugin_id = '7088';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7088, 1, "ossec-login_denied", "login_denied");
DELETE FROM plugin where id = '7072';
DELETE FROM plugin_sid where plugin_id = '7072';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7072, 1, "ossec-sonicwall", "sonicwall");
DELETE FROM plugin where id = '7999';
DELETE FROM plugin_sid where plugin_id = '7999';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7999, 1, "ossec-preprocessor", "preprocessor");
DELETE FROM plugin where id = '7122';
DELETE FROM plugin_sid where plugin_id = '7122';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7122, 1, "ossec-dhcp_rogue_server", "dhcp_rogue_server");
DELETE FROM plugin where id = '7078';
DELETE FROM plugin_sid where plugin_id = '7078';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7078, 1, "ossec-imapd", "imapd");
DELETE FROM plugin where id = '7079';
DELETE FROM plugin_sid where plugin_id = '7079';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7079, 1, "ossec-mailscanner", "mailscanner");
DELETE FROM plugin where id = '7074';
DELETE FROM plugin_sid where plugin_id = '7074';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7074, 1, "ossec-spam", "spam");
DELETE FROM plugin where id = '7075';
DELETE FROM plugin_sid where plugin_id = '7075';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7075, 1, "ossec-multiple_spam", "multiple_spam");
DELETE FROM plugin where id = '7076';
DELETE FROM plugin_sid where plugin_id = '7076';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7076, 1, "ossec-sendmail", "sendmail");
DELETE FROM plugin where id = '7077';
DELETE FROM plugin_sid where plugin_id = '7077';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7077, 1, "ossec-smf-sav", "smf-sav");
DELETE FROM plugin where id = '7070';
DELETE FROM plugin_sid where plugin_id = '7070';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7070, 1, "ossec-cisco_ios", "cisco_ios");
DELETE FROM plugin where id = '7071';
DELETE FROM plugin_sid where plugin_id = '7071';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7071, 1, "ossec-netscreenfw", "netscreenfw");
DELETE FROM plugin where id = '7123';
DELETE FROM plugin_sid where plugin_id = '7123';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7123, 1, "ossec-dhcp_dns_maintenance", "dhcp_dns_maintenance");
DELETE FROM plugin where id = '7073';
DELETE FROM plugin_sid where plugin_id = '7073';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7073, 1, "ossec-postfix", "postfix");
DELETE FROM plugin where id = '7100';
DELETE FROM plugin_sid where plugin_id = '7100';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7100, 1, "ossec-dovecot", "dovecot");
DELETE FROM plugin where id = '7101';
DELETE FROM plugin_sid where plugin_id = '7101';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7101, 1, "ossec-cimserver", "cimserver");
DELETE FROM plugin where id = '7102';
DELETE FROM plugin_sid where plugin_id = '7102';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7102, 1, "ossec-agentless", "agentless");
DELETE FROM plugin where id = '7103';
DELETE FROM plugin_sid where plugin_id = '7103';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7103, 1, "ossec-service_start", "service_start");
DELETE FROM plugin where id = '7104';
DELETE FROM plugin_sid where plugin_id = '7104';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7104, 1, "ossec-process_monitor", "process_monitor");
DELETE FROM plugin where id = '7105';
DELETE FROM plugin_sid where plugin_id = '7105';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7105, 1, "ossec-ocse", "ocse");
DELETE FROM plugin where id = '7106';
DELETE FROM plugin_sid where plugin_id = '7106';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7106, 1, "ossec-dhcp_maintenance", "dhcp_maintenance");
DELETE FROM plugin where id = '7107';
DELETE FROM plugin_sid where plugin_id = '7107';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7107, 1, "ossec-win_group_changed", "win_group_changed");
DELETE FROM plugin where id = '7108';
DELETE FROM plugin_sid where plugin_id = '7108';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7108, 1, "ossec-login_day", "login_day");
DELETE FROM plugin where id = '7109';
DELETE FROM plugin_sid where plugin_id = '7109';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7109, 1, "ossec-dhcp", "dhcp");
DELETE FROM plugin where id = '7069';
DELETE FROM plugin_sid where plugin_id = '7069';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7069, 1, "ossec-multiple_drops", "multiple_drops");
DELETE FROM plugin where id = '7068';
DELETE FROM plugin_sid where plugin_id = '7068';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7068, 1, "ossec-firewall_drop", "firewall_drop");
DELETE FROM plugin where id = '7067';
DELETE FROM plugin_sid where plugin_id = '7067';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7067, 1, "ossec-postgresql_log", "postgresql_log");
DELETE FROM plugin where id = '7066';
DELETE FROM plugin_sid where plugin_id = '7066';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7066, 1, "ossec-mysql_log", "mysql_log");
DELETE FROM plugin where id = '7065';
DELETE FROM plugin_sid where plugin_id = '7065';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7065, 1, "ossec-invalid_request", "invalid_request");
DELETE FROM plugin where id = '7064';
DELETE FROM plugin_sid where plugin_id = '7064';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7064, 1, "ossec-unknown_resource", "unknown_resource");
DELETE FROM plugin where id = '7063';
DELETE FROM plugin_sid where plugin_id = '7063';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7063, 1, "ossec-automatic_attack", "automatic_attack");
DELETE FROM plugin where id = '7062';
DELETE FROM plugin_sid where plugin_id = '7062';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7062, 1, "ossec-apache", "apache");
DELETE FROM plugin where id = '7061';
DELETE FROM plugin_sid where plugin_id = '7061';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7061, 1, "ossec-web_scan", "web_scan");
DELETE FROM plugin where id = '7060';
DELETE FROM plugin_sid where plugin_id = '7060';
INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7060, 1, "ossec-sql_injection", "sql_injection");
INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES
(7001, 01, NULL, NULL, 1,1, "ossec: Generic template for all syslog rules."),
(7001, 100001, NULL, NULL, 1,1, "ossec: Example of rule that will ignore sshd failed logins from IP 1.1.1.1."),
(7001, 5500, NULL, NULL, 1,1, "ossec: Grouping of the pam_unix rules."),
(7001, 5502, NULL, NULL, 1,1, "ossec: Login session closed."),
(7001, 5521, NULL, NULL, 1,1, "ossec: Ignoring Annoying Ubuntu/debian cron login events."),
(7001, 5522, NULL, NULL, 1,1, "ossec: Ignoring Annoying Ubuntu/debian cron login events."),
(7001, 5523, NULL, NULL, 1,1, "ossec: Ignoring events with a user or a password."),
(7001, 5552, NULL, NULL, 1,1, "ossec: PAM and gdm are not playing nicely."),
(7001, 5553, NULL, NULL, 1,1, "ossec: PAM misconfiguration."),
(7001, 5554, NULL, NULL, 1,1, "ossec: PAM misconfiguration."),
(7001, 5555, NULL, NULL, 1,1, "ossec: User changed password."),
(7002, 02, NULL, NULL, 1,1, "ossec: Generic template for all firewall rules."),
(7002, 4100, NULL, NULL, 1,1, "ossec: Firewall rules grouped."),
(7003, 03, NULL, NULL, 1,1, "ossec: Generic template for all ids rules."),
(7003, 20101, NULL, NULL, 1,1, "ossec: IDS event."),
(7003, 20102, NULL, NULL, 1,1, "ossec: Ignored snort ids."),
(7003, 20103, NULL, NULL, 1,1, "ossec: Ignored snort ids."),
(7003, 20151, NULL, NULL, 1,1, "ossec: Multiple IDS events from same source ip."),
(7003, 20152, NULL, NULL, 1,1, "ossec: Multiple IDS alerts for same id."),
(7003, 20161, NULL, NULL, 1,1, "ossec: Multiple IDS events from same source ip (ignoring now this srcip and id)."),
(7003, 20162, NULL, NULL, 1,1, "ossec: Multiple IDS alerts for same id (ignoring now this id)."),
(7003, 4333, NULL, NULL, 1,1, "ossec: Attack in progress detected by the PIX."),
(7004, 04, NULL, NULL, 1,1, "ossec: Generic template for all web rules."),
(7005, 05, NULL, NULL, 1,1, "ossec: Generic template for all web proxy rules."),
(7005, 35000, NULL, NULL, 1,1, "ossec: Squid messages grouped."),
(7005, 35002, NULL, NULL, 1,1, "ossec: Squid generic error codes."),
(7005, 35003, NULL, NULL, 1,1, "ossec: Bad request/Invalid syntax."),
(7005, 35004, NULL, NULL, 1,1, "ossec: Unauthorized: Failed attempt to access authorization-required file or directory."),
(7005, 35005, NULL, NULL, 1,1, "ossec: Forbidden: Attempt to access forbidden file or directory."),
(7005, 35006, NULL, NULL, 1,1, "ossec: Not Found: Attempt to access non-existent file or directory."),
(7005, 35007, NULL, NULL, 1,1, "ossec: Proxy Authentication Required: User is not authorized to use proxy."),
(7005, 35008, NULL, NULL, 1,1, "ossec: Squid 400 error code (request failed)."),
(7005, 35009, NULL, NULL, 1,1, "ossec: Squid 500/600 error code (server error)."),
(7005, 35010, NULL, NULL, 1,1, "ossec: Squid 503 error code (server unavailable)."),
(7005, 35023, NULL, NULL, 1,1, "ossec: Ignored files on a 40x error."),
(7005, 35051, NULL, NULL, 1,1, "ossec: Multiple attempts to access forbidden file or directory from same source ip."),
(7005, 35052, NULL, NULL, 1,1, "ossec: Multiple unauthorized attempts to use proxy."),
(7005, 35053, NULL, NULL, 1,1, "ossec: Multiple Bad requests/Invalid syntax."),
(7005, 35054, NULL, NULL, 1,1, "ossec: Infected machine with W32.Beagle.DP."),
(7005, 35055, NULL, NULL, 1,1, "ossec: Multiple attempts to access a non-existent file."),
(7005, 35056, NULL, NULL, 1,1, "ossec: Multiple attempts to access a worm/trojan/virus related web site. System probably infected."),
(7005, 35057, NULL, NULL, 1,1, "ossec: Multiple 400 error codes (requests failed)."),
(7005, 35058, NULL, NULL, 1,1, "ossec: Multiple 500/600 error codes (server error)."),
(7005, 35095, NULL, NULL, 1,1, "ossec: Ignoring multiple attempts from same source ip (alert only once)."),
(7005, 9200, NULL, NULL, 1,1, "ossec: Squid syslog messages grouped"),
(7005, 9201, NULL, NULL, 1,1, "ossec: Squid debug message"),
(7006, 06, NULL, NULL, 1,1, "ossec: Generic template for all windows rules."),
(7006, 18100, NULL, NULL, 1,1, "ossec: Group of windows rules."),
(7006, 18101, NULL, NULL, 1,1, "ossec: Windows informational event."),
(7006, 18102, NULL, NULL, 1,1, "ossec: Windows warning event."),
(7006, 18104, NULL, NULL, 1,1, "ossec: Windows audit success event."),
(7006, 18105, NULL, NULL, 1,1, "ossec: Windows audit failure event."),
(7006, 18108, NULL, NULL, 1,1, "ossec: Failed attempt to perform a privileged operation."),
(7006, 18109, NULL, NULL, 1,1, "ossec: Session reconnected/disconnected to winstation."),
(7006, 18120, NULL, NULL, 1,1, "ossec: Windows login attempt (ignored). Duplicated."),
(7006, 18121, NULL, NULL, 1,1, "ossec: Windows Logon Success (ignored)."),
(7006, 18146, NULL, NULL, 1,1, "ossec: Application Uninstalled."),
(7006, 18147, NULL, NULL, 1,1, "ossec: Application Installed."),
(7006, 18148, NULL, NULL, 1,1, "ossec: Windows is starting up."),
(7006, 18149, NULL, NULL, 1,1, "ossec: Windows User Logoff."),
(7006, 18150, NULL, NULL, 1,1, "ossec: Windows Event Log cleared."),
(7006, 18151, NULL, NULL, 1,1, "ossec: Multiple failed attempts to perform a privileged operation by the same user."),
(7006, 18153, NULL, NULL, 1,1, "ossec: Multiple Windows audit failure events."),
(7006, 18154, NULL, NULL, 1,1, "ossec: Multiple Windows error events."),
(7006, 18155, NULL, NULL, 1,1, "ossec: Multiple Windows warning events."),
(7006, 18224, NULL, NULL, 1,1, "ossec: Local User Group NONE"),
(7007, 07, NULL, NULL, 1,1, "ossec: Generic template for all ossec rules."),
(7007, 500, NULL, NULL, 1,1, "ossec: Grouping of ossec rules."),
(7007, 501, NULL, NULL, 1,1, "ossec: New ossec agent connected."),
(7007, 502, NULL, NULL, 1,1, "ossec: Ossec server started."),
(7007, 503, NULL, NULL, 1,1, "ossec: Ossec agent started."),
(7007, 504, NULL, NULL, 1,1, "ossec: Ossec agent disconnected."),
(7007, 532, NULL, NULL, 1,1, "ossec: Ignoring external medias."),
(7007, 533, NULL, NULL, 1,1, "ossec: Listened ports status (netstat) changed (new port opened or closed)."),
(7007, 534, NULL, NULL, 1,1, "ossec: List of logged in users. It will not be alerted by default."),
(7007, 535, NULL, NULL, 1,1, "ossec: List of the last logged in users."),
(7007, 591, NULL, NULL, 1,1, "ossec: Log file rotated."),
(7009, 10100, NULL, NULL, 1,1, "ossec: First time user logged in."),
(7009, 11205, NULL, NULL, 1,1, "ossec: FTP Authentication success."),
(7009, 11309, NULL, NULL, 1,1, "ossec: FTP Authentication success."),
(7009, 11402, NULL, NULL, 1,1, "ossec: FTP Authentication success."),
(7009, 11503, NULL, NULL, 1,1, "ossec: FTP Authentication success."),
(7009, 14120, NULL, NULL, 1,1, "ossec: VPN established."),
(7009, 14201, NULL, NULL, 1,1, "ossec: VPN authentication successful."),
(7009, 14203, NULL, NULL, 1,1, "ossec: VPN Admin authentication successful."),
(7009, 18107, NULL, NULL, 1,1, "ossec: Windows Logon Success."),
(7009, 18119, NULL, NULL, 1,1, "ossec: First time this user logged in this system."),
(7009, 18126, NULL, NULL, 1,1, "ossec: Remote access login success."),
(7009, 18181, NULL, NULL, 1,1, "ossec: MS SQL Server Logon Success."),
(7009, 19110, NULL, NULL, 1,1, "ossec: VMWare ESX authentication success."),
(7009, 19112, NULL, NULL, 1,1, "ossec: VMWare ESX user login."),
(7009, 3602, NULL, NULL, 1,1, "ossec: Imapd user login."),
(7009, 3904, NULL, NULL, 1,1, "ossec: Courier (imap/pop3) authentication success."),
(7009, 4323, NULL, NULL, 1,1, "ossec: Successful login to the PIX firewall."),
(7009, 4335, NULL, NULL, 1,1, "ossec: AAA (VPN) authentication successful."),
(7009, 4506, NULL, NULL, 1,1, "ossec: Successfull admin login to the Netscreen firewall"),
(7009, 4507, NULL, NULL, 1,1, "ossec: Successfull admin login to the Netscreen firewall"),
(7009, 4722, NULL, NULL, 1,1, "ossec: Successful login to the router."),
(7009, 4810, NULL, NULL, 1,1, "ossec: Firewall administrator login."),
(7009, 50105, NULL, NULL, 1,1, "ossec: Database authentication success."),
(7009, 50511, NULL, NULL, 1,1, "ossec: Database authentication success."),
(7009, 51009, NULL, NULL, 1,1, "ossec: User successfully logged in using a password."),
(7009, 5303, NULL, NULL, 1,1, "ossec: User successfully changed UID to root."),
(7009, 5304, NULL, NULL, 1,1, "ossec: User successfully changed UID."),
(7009, 5501, NULL, NULL, 1,1, "ossec: Login session opened."),
(7009, 5715, NULL, NULL, 1,1, "ossec: SSHD authentication success."),
(7009, 6103, NULL, NULL, 1,1, "ossec: Login session succeeded."),
(7009, 6105, NULL, NULL, 1,1, "ossec: User successfully changed UID."),
(7009, 7415, NULL, NULL, 1,1, "ossec: Login success accessing the web proxy."),
(7009, 7420, NULL, NULL, 1,1, "ossec: Admin Login success to the web proxy."),
(7009, 9305, NULL, NULL, 1,1, "ossec: Horde IMP successful login."),
(7009, 9402, NULL, NULL, 1,1, "ossec: Roundcube authentication succeeded."),
(7009, 9502, NULL, NULL, 1,1, "ossec: Wordpress authentication succeeded."),
(7009, 9701, NULL, NULL, 1,1, "ossec: Dovecot Authentication Success."),
(7009, 9904, NULL, NULL, 1,1, "ossec: Vpopmail successful login."),
(7010, 11111, NULL, NULL, 1,1, "ossec: Attempt to login with disabled account."),
(7010, 11112, NULL, NULL, 1,1, "ossec: FTP authentication failure."),
(7010, 11113, NULL, NULL, 1,1, "ossec: FTP authentication failure."),
(7010, 11204, NULL, NULL, 1,1, "ossec: Login failed accessing the FTP server"),
(7010, 11302, NULL, NULL, 1,1, "ossec: FTP Authentication failed."),
(7010, 11403, NULL, NULL, 1,1, "ossec: Login failed accessing the FTP server."),
(7010, 11502, NULL, NULL, 1,1, "ossec: FTP Authentication failed."),
(7010, 14101, NULL, NULL, 1,1, "ossec: VPN authentication failed."),
(7010, 14202, NULL, NULL, 1,1, "ossec: VPN authentication failed."),
(7010, 18125, NULL, NULL, 1,1, "ossec: Remote access login failure."),
(7010, 19111, NULL, NULL, 1,1, "ossec: VMWare ESX authentication failure."),
(7010, 19113, NULL, NULL, 1,1, "ossec: VMWare ESX user authentication failure."),
(7010, 2501, NULL, NULL, 1,1, "ossec: User authentication failure."),
(7010, 2502, NULL, NULL, 1,1, "ossec: User missed the password more than one time"),
(7010, 30108, NULL, NULL, 1,1, "ossec: User authentication failed."),
(7010, 30110, NULL, NULL, 1,1, "ossec: User authentication failed."),
(7010, 31205, NULL, NULL, 1,1, "ossec: Admin authentication failed."),
(7010, 31315, NULL, NULL, 1,1, "ossec: Web authentication failed."),
(7010, 3332, NULL, NULL, 1,1, "ossec: Postfix SASL authentication failure."),
(7010, 3601, NULL, NULL, 1,1, "ossec: Imapd user login failed."),
(7010, 3902, NULL, NULL, 1,1, "ossec: Courier (imap/pop3) authentication failed."),
(7010, 4321, NULL, NULL, 1,1, "ossec: Failed login attempt at the PIX firewall."),
(7010, 4324, NULL, NULL, 1,1, "ossec: Password mismatch while running 'enable' on the PIX."),
(7010, 4334, NULL, NULL, 1,1, "ossec: AAA (VPN) authentication failed."),
(7010, 4336, NULL, NULL, 1,1, "ossec: AAA (VPN) user locked out."),
(7010, 4724, NULL, NULL, 1,1, "ossec: Failed login to the router."),
(7010, 4811, NULL, NULL, 1,1, "ossec: Firewall authentication failure."),
(7010, 50106, NULL, NULL, 1,1, "ossec: Database authentication failure."),
(7010, 50512, NULL, NULL, 1,1, "ossec: Database authentication failure."),
(7010, 51003, NULL, NULL, 1,1, "ossec: Bad password attempt."),
(7010, 5301, NULL, NULL, 1,1, "ossec: User missed the password to change UID (user id)."),
(7010, 5302, NULL, NULL, 1,1, "ossec: User missed the password to change UID to root."),
(7010, 5503, NULL, NULL, 1,1, "ossec: User login failed."),
(7010, 5710, NULL, NULL, 1,1, "ossec: Attempt to login using a non-existent user"),
(7010, 5716, NULL, NULL, 1,1, "ossec: SSHD authentication failed."),
(7010, 5728, NULL, NULL, 1,1, "ossec: Authentication services were not able to retrieve user credentials."),
(7010, 5738, NULL, NULL, 1,1, "ossec: pam_loginuid could not open loginuid."),
(7010, 6104, NULL, NULL, 1,1, "ossec: Login session failed."),
(7010, 6106, NULL, NULL, 1,1, "ossec: User failed to change UID (user id)."),
(7010, 6210, NULL, NULL, 1,1, "ossec: Login session failed."),
(7010, 7410, NULL, NULL, 1,1, "ossec: Login failed accessing the web proxy."),
(7010, 9306, NULL, NULL, 1,1, "ossec: Horde IMP Failed login."),
(7010, 9401, NULL, NULL, 1,1, "ossec: Roundcube authentication failed."),
(7010, 9501, NULL, NULL, 1,1, "ossec: Wordpress authentication failed."),
(7010, 9610, NULL, NULL, 1,1, "ossec: Compaq Insight Manager authentication failure."),
(7010, 9702, NULL, NULL, 1,1, "ossec: Dovecot Authentication Failed."),
(7010, 9705, NULL, NULL, 1,1, "ossec: Dovecot Invalid User Login Attempt."),
(7010, 9801, NULL, NULL, 1,1, "ossec: Login failed accessing the pop3 server."),
(7010, 9901, NULL, NULL, 1,1, "ossec: Login failed for vpopmail."),
(7010, 9903, NULL, NULL, 1,1, "ossec: Attempt to login to vpopmail with empty password."),
(7011, 11203, NULL, NULL, 1,1, "ossec: Attempt to login using a non-existent user."),
(7011, 2504, NULL, NULL, 1,1, "ossec: Illegal root login. "),
(7011, 30109, NULL, NULL, 1,1, "ossec: Attempt to login using a non-existent user."),
(7011, 40101, NULL, NULL, 1,1, "ossec: System user successfully logged to the system."),
(7011, 5504, NULL, NULL, 1,1, "ossec: Attempt to login with an invalid user."),
(7011, 5718, NULL, NULL, 1,1, "ossec: Attempt to login using a denied user."),
(7011, 5719, NULL, NULL, 1,1, "ossec: Multiple access attempts using a denied user."),
(7011, 6211, NULL, NULL, 1,1, "ossec: Login session failed (invalid user)."),
(7011, 6212, NULL, NULL, 1,1, "ossec: Login session failed (invalid extension)."),
(7011, 6253, NULL, NULL, 1,1, "ossec: Login session failed (invalid iax user)."),
(7011, 6255, NULL, NULL, 1,1, "ossec: Possible Registration Hijacking."),
(7011, 6256, NULL, NULL, 1,1, "ossec: IAX peer Wrong Password."),
(7011, 9707, NULL, NULL, 1,1, "ossec: Dovecot Aborted Login."),
(7011, 9902, NULL, NULL, 1,1, "ossec: Attempt to login to vpopmail with invalid username."),
(7012, 11109, NULL, NULL, 1,1, "ossec: Multiple FTP failed login attempts."),
(7012, 11210, NULL, NULL, 1,1, "ossec: Multiple failed login attempts."),
(7012, 11251, NULL, NULL, 1,1, "ossec: FTP brute force (multiple failed logins)."),
(7012, 11306, NULL, NULL, 1,1, "ossec: FTP brute force (multiple failed logins)."),
(7012, 11451, NULL, NULL, 1,1, "ossec: FTP brute force (multiple failed logins)."),
(7012, 11510, NULL, NULL, 1,1, "ossec: FTP brute force (multiple failed logins)."),
(7012, 14251, NULL, NULL, 1,1, "ossec: Multiple VPN authentication failures."),
(7012, 18116, NULL, NULL, 1,1, "ossec: User account locked out (multiple login errors)."),
(7012, 18152, NULL, NULL, 1,1, "ossec: Multiple Windows Logon Failures."),
(7012, 18156, NULL, NULL, 1,1, "ossec: Multiple remote access login failures."),
(7012, 19152, NULL, NULL, 1,1, "ossec: Multiple VMWare ESX authentication failures."),
(7012, 19153, NULL, NULL, 1,1, "ossec: Multiple VMWare ESX user authentication failures."),
(7012, 31316, NULL, NULL, 1,1, "ossec: Multiple web authentication failures."),
(7012, 3357, NULL, NULL, 1,1, "ossec: Multiple SASL authentication failures."),
(7012, 3651, NULL, NULL, 1,1, "ossec: Multiple failed logins from same source ip."),
(7012, 3910, NULL, NULL, 1,1, "ossec: Courier brute force (multiple failed logins)."),
(7012, 40111, NULL, NULL, 1,1, "ossec: Multiple authentication failures."),
(7012, 4386, NULL, NULL, 1,1, "ossec: Multiple AAA (VPN) authentication failures."),
(7012, 51004, NULL, NULL, 1,1, "ossec: dropbear brute force attempt."),
(7012, 51007, NULL, NULL, 1,1, "ossec: dropbear brute force attempt."),
(7012, 5551, NULL, NULL, 1,1, "ossec: Multiple failed logins in a small period of time."),
(7012, 5712, NULL, NULL, 1,1, "ossec: SSHD brute force trying to get access to the system."),
(7012, 5720, NULL, NULL, 1,1, "ossec: Multiple SSHD authentication failures."),
(7012, 5733, NULL, NULL, 1,1, "ossec: User entered incorrect password."),
(7012, 9351, NULL, NULL, 1,1, "ossec: Horde brute force (multiple failed logins)."),
(7012, 9551, NULL, NULL, 1,1, "ossec: Multiple wordpress authentication failures."),
(7012, 9750, NULL, NULL, 1,1, "ossec: Dovecot Multiple Authentication Failures."),
(7012, 9751, NULL, NULL, 1,1, "ossec: Dovecot brute force attack (multiple auth failures)."),
(7012, 9820, NULL, NULL, 1,1, "ossec: POP3 brute force (multiple failed logins)."),
(7012, 9951, NULL, NULL, 1,1, "ossec: Vpopmail brute force (multiple failed logins)."),
(7012, 9952, NULL, NULL, 1,1, "ossec: Vpopmail brute force (email harvesting)."),
(7012, 9953, NULL, NULL, 1,1, "ossec: VPOPMAIL brute force (empty password)."),
(7013, 5700, NULL, NULL, 1,1, "ossec: SSHD messages grouped."),
(7013, 5701, NULL, NULL, 1,1, "ossec: Possible attack on the ssh server (or version gathering)."),
(7013, 5702, NULL, NULL, 1,1, "ossec: Reverse lookup error (bad ISP or attack)."),
(7013, 5703, NULL, NULL, 1,1, "ossec: Possible breakin attempt (high number of reverse lookup errors)."),
(7013, 5704, NULL, NULL, 1,1, "ossec: Timeout while logging in (sshd)."),
(7013, 5705, NULL, NULL, 1,1, "ossec: Possible scan or breakin attempt (high number of login timeouts)."),
(7013, 5709, NULL, NULL, 1,1, "ossec: Useless SSHD message without an user/ip and context."),
(7013, 5711, NULL, NULL, 1,1, "ossec: Useless/Duplicated SSHD message without a user/ip."),
(7013, 5713, NULL, NULL, 1,1, "ossec: Corrupted bytes on SSHD."),
(7013, 5717, NULL, NULL, 1,1, "ossec: SSHD configuration error (moduli)."),
(7013, 5721, NULL, NULL, 1,1, "ossec: System disconnected from sshd."),
(7013, 5722, NULL, NULL, 1,1, "ossec: ssh connection closed."),
(7013, 5723, NULL, NULL, 1,1, "ossec: SSHD key error."),
(7013, 5724, NULL, NULL, 1,1, "ossec: SSHD key error."),
(7013, 5725, NULL, NULL, 1,1, "ossec: Host ungracefully disconnected."),
(7013, 5726, NULL, NULL, 1,1, "ossec: Unknown PAM module, PAM misconfiguration."),
(7013, 5727, NULL, NULL, 1,1, "ossec: Attempt to start sshd when something already bound to the port."),
(7013, 5729, NULL, NULL, 1,1, "ossec: Debug message."),
(7013, 5730, NULL, NULL, 1,1, "ossec: SSHD is not accepting connections."),
(7013, 5732, NULL, NULL, 1,1, "ossec: Possible port forwarding failure."),
(7013, 5734, NULL, NULL, 1,1, "ossec: sshd could not load one or more host keys."),
(7013, 5735, NULL, NULL, 1,1, "ossec: Failed write due to one host disappearing."),
(7013, 5736, NULL, NULL, 1,1, "ossec: Connection reset or aborted."),
(7013, 5737, NULL, NULL, 1,1, "ossec: sshd cannot bind to configured address."),
(7014, 11252, NULL, NULL, 1,1, "ossec: Multiple connection attempts from same source."),
(7014, 11307, NULL, NULL, 1,1, "ossec: Multiple connection attempts from same source."),
(7014, 11452, NULL, NULL, 1,1, "ossec: Multiple FTP connection attempts from same source IP."),
(7014, 11511, NULL, NULL, 1,1, "ossec: Multiple connection attempts from same source."),
(7014, 31151, NULL, NULL, 1,1, "ossec: Multiple web server 400 error codes from same source ip."),
(7014, 31161, NULL, NULL, 1,1, "ossec: Multiple web server 501 error code (Not Implemented)."),
(7014, 31163, NULL, NULL, 1,1, "ossec: Multiple web server 503 error code (Service unavailable)."),
(7014, 3911, NULL, NULL, 1,1, "ossec: Multiple connection attempts from same source."),
(7014, 40601, NULL, NULL, 1,1, "ossec: Network scan from same source ip."),
(7014, 51006, NULL, NULL, 1,1, "ossec: Client exited before authentication."),
(7014, 51008, NULL, NULL, 1,1, "ossec: Incompatible remote version."),
(7014, 5706, NULL, NULL, 1,1, "ossec: SSH insecure connection attempt (scan)."),
(7014, 5731, NULL, NULL, 1,1, "ossec: SSH Scanning."),
(7015, 40102, NULL, NULL, 1,1, "ossec: Buffer overflow attack on rpc.statd"),
(7015, 40103, NULL, NULL, 1,1, "ossec: Buffer overflow on WU-FTPD versions prior to 2.6"),
(7015, 40104, NULL, NULL, 1,1, "ossec: Possible buffer overflow attempt."),
(7015, 40105, NULL, NULL, 1,1, "ossec: \"Null\" user changed some information."),
(7015, 40106, NULL, NULL, 1,1, "ossec: Buffer overflow attempt (probably on yppasswd)."),
(7015, 40107, NULL, NULL, 1,1, "ossec: Heap overflow in the Solaris cachefsd service."),
(7015, 40109, NULL, NULL, 1,1, "ossec: Stack overflow attempt or program exiting with SEGV (Solaris)."),
(7015, 5707, NULL, NULL, 1,1, "ossec: OpenSSH challenge-response exploit."),
(7015, 5714, NULL, NULL, 1,1, "ossec: SSH CRC-32 Compensation attack"),
(7016, 5600, NULL, NULL, 1,1, "ossec: Grouping for the telnetd rules"),
(7016, 5601, NULL, NULL, 1,1, "ossec: Connection refused by TCP Wrappers."),
(7016, 5602, NULL, NULL, 1,1, "ossec: Remote host established a telnet connection."),
(7016, 5603, NULL, NULL, 1,1, "ossec: Remote host invalid connection."),
(7016, 5604, NULL, NULL, 1,1, "ossec: Reverse lookup error (bad hostname config)."),
(7016, 5631, NULL, NULL, 1,1, "ossec: Multiple connection attempts from same source (possible scan)."),
(7017, 1001, NULL, NULL, 1,1, "ossec: File missing. Root access unrestricted."),
(7017, 1002, NULL, NULL, 1,1, "ossec: Unknown problem somewhere in the system."),
(7017, 1003, NULL, NULL, 1,1, "ossec: Non standard syslog message (size too large)."),
(7017, 1004, NULL, NULL, 1,1, "ossec: Syslogd exiting (logging stopped)."),
(7017, 1005, NULL, NULL, 1,1, "ossec: Syslogd restarted."),
(7017, 1006, NULL, NULL, 1,1, "ossec: Syslogd restarted."),
(7017, 1009, NULL, NULL, 1,1, "ossec: Ignoring known false positives on rule 1002.."),
(7018, 1007, NULL, NULL, 1,1, "ossec: File system full."),
(7018, 18129, NULL, NULL, 1,1, "ossec: Windows file system full."),
(7018, 31413, NULL, NULL, 1,1, "ossec: PHP internal error (server out of space)."),
(7018, 531, NULL, NULL, 1,1, "ossec: Partition usage reached 100% (disk space monitor)."),
(7019, 2100, NULL, NULL, 1,1, "ossec: NFS rules grouped."),
(7019, 2101, NULL, NULL, 1,1, "ossec: Unable to mount the NFS share."),
(7019, 2102, NULL, NULL, 1,1, "ossec: Unable to mount the NFS directory."),
(7019, 2103, NULL, NULL, 1,1, "ossec: Unable to mount the NFS directory."),
(7019, 2104, NULL, NULL, 1,1, "ossec: Automount informative message"),
(7020, 2301, NULL, NULL, 1,1, "ossec: Excessive number connections to a service."),
(7021, 2505, NULL, NULL, 1,1, "ossec: Physical root login."),
(7021, 2506, NULL, NULL, 1,1, "ossec: Pop3 Authentication passed."),
(7021, 2507, NULL, NULL, 1,1, "ossec: OpenLDAP group."),
(7021, 2508, NULL, NULL, 1,1, "ossec: OpenLDAP connection open."),
(7021, 2509, NULL, NULL, 1,1, "ossec: OpenLDAP authentication failed."),
(7021, 2550, NULL, NULL, 1,1, "ossec: rshd messages grouped."),
(7022, 11101, NULL, NULL, 1,1, "ossec: FTP connection refused."),
(7022, 11107, NULL, NULL, 1,1, "ossec: Connection blocked by Tcp Wrappers."),
(7022, 11206, NULL, NULL, 1,1, "ossec: Connection denied by ProFTPD configuration."),
(7022, 11207, NULL, NULL, 1,1, "ossec: Connection refused by TCP Wrappers."),
(7022, 12102, NULL, NULL, 1,1, "ossec: Failed attempt to perform a zone transfer."),
(7022, 13102, NULL, NULL, 1,1, "ossec: Samba connection denied."),
(7022, 13104, NULL, NULL, 1,1, "ossec: User action denied by configuration."),
(7022, 2503, NULL, NULL, 1,1, "ossec: Connection blocked by Tcp Wrappers."),
(7022, 30105, NULL, NULL, 1,1, "ossec: Attempt to access forbidden file or directory."),
(7022, 30106, NULL, NULL, 1,1, "ossec: Attempt to access forbidden directory index."),
(7022, 30118, NULL, NULL, 1,1, "ossec: Access attempt blocked by Mod Security."),
(7022, 30119, NULL, NULL, 1,1, "ossec: Multiple attempts blocked by Mod Security."),
(7022, 30201, NULL, NULL, 1,1, "ossec: Modsecurity access denied."),
(7022, 30202, NULL, NULL, 1,1, "ossec: Multiple attempts blocked by Mod Security."),
(7022, 4326, NULL, NULL, 1,1, "ossec: Attempt to connect from a blocked (shunned) IP."),
(7023, 2701, NULL, NULL, 1,1, "ossec: Ignoring procmail messages."),
(7024, 2800, NULL, NULL, 1,1, "ossec: Pre-match rule for smartd."),
(7024, 2801, NULL, NULL, 1,1, "ossec: Smartd Started but not configured"),
(7024, 2802, NULL, NULL, 1,1, "ossec: Smartd configuration problem"),
(7024, 2803, NULL, NULL, 1,1, "ossec: Device configured but not available to Smartd"),
(7025, 5100, NULL, NULL, 1,1, "ossec: Pre-match rule for kernel messages"),
(7025, 5101, NULL, NULL, 1,1, "ossec: Informative message from the kernel."),
(7025, 5102, NULL, NULL, 1,1, "ossec: Informative message from the kernel"),
(7025, 5103, NULL, NULL, 1,1, "ossec: Error message from the kernel. Ping of death attack."),
(7025, 5105, NULL, NULL, 1,1, "ossec: Invalid request to /dev/fd0 (bug on the kernel)."),
(7025, 5106, NULL, NULL, 1,1, "ossec: NFS incompability between Linux and Solaris."),
(7025, 5107, NULL, NULL, 1,1, "ossec: NFS incompability between Linux and Solaris."),
(7025, 5109, NULL, NULL, 1,1, "ossec: Kernel Input/Output error"),
(7025, 5110, NULL, NULL, 1,1, "ossec: IRC misconfiguration"),
(7025, 5111, NULL, NULL, 1,1, "ossec: Kernel device error."),
(7025, 5112, NULL, NULL, 1,1, "ossec: Kernel usbhid probe error (ignored)."),
(7025, 5130, NULL, NULL, 1,1, "ossec: Monitor ADSL line is down."),
(7025, 5131, NULL, NULL, 1,1, "ossec: Monitor ADSL line is up."),
(7025, 5200, NULL, NULL, 1,1, "ossec: Ignoring hpiod for producing useless logs."),
(7026, 5104, NULL, NULL, 1,1, "ossec: Interface entered in promiscuous(sniffing) mode."),
(7027, 1008, NULL, NULL, 1,1, "ossec: Process exiting (killed)."),
(7027, 11218, NULL, NULL, 1,1, "ossec: FTP process crashed."),
(7027, 12109, NULL, NULL, 1,1, "ossec: Named fatal error. DNS service going down."),
(7027, 19120, NULL, NULL, 1,1, "ossec: Virtual machine state changed to OFF."),
(7027, 19150, NULL, NULL, 1,1, "ossec: Multiple VMWare ESX warning messages."),
(7027, 19151, NULL, NULL, 1,1, "ossec: Multiple VMWare ESX error messages."),
(7027, 30104, NULL, NULL, 1,1, "ossec: Apache segmentation fault."),
(7027, 30120, NULL, NULL, 1,1, "ossec: Apache without resources to run."),
(7027, 3330, NULL, NULL, 1,1, "ossec: Postfix process error."),
(7027, 3331, NULL, NULL, 1,1, "ossec: Postfix insufficient disk space error."),
(7027, 3333, NULL, NULL, 1,1, "ossec: Postfix stopped."),
(7027, 4337, NULL, NULL, 1,1, "ossec: The PIX is disallowing new connections."),
(7027, 4338, NULL, NULL, 1,1, "ossec: Firewall failover pair communication problem."),
(7027, 4505, NULL, NULL, 1,1, "ossec: Netscreen Erase sequence started."),
(7027, 4850, NULL, NULL, 1,1, "ossec: Multiple firewall warning messages."),
(7027, 4851, NULL, NULL, 1,1, "ossec: Multiple firewall error messages."),
(7027, 50120, NULL, NULL, 1,1, "ossec: Database shutdown messge."),
(7027, 50121, NULL, NULL, 1,1, "ossec: Database startup message."),
(7027, 50126, NULL, NULL, 1,1, "ossec: Database fatal error."),
(7027, 50180, NULL, NULL, 1,1, "ossec: Multiple database errors."),
(7027, 50520, NULL, NULL, 1,1, "ossec: Database shutdown messge."),
(7027, 50521, NULL, NULL, 1,1, "ossec: Database shutdown messge."),
(7027, 50580, NULL, NULL, 1,1, "ossec: Multiple database errors."),
(7027, 50581, NULL, NULL, 1,1, "ossec: Multiple database errors."),
(7027, 5108, NULL, NULL, 1,1, "ossec: System running out of memory. Availability of the system is in risk."),
(7027, 6302, NULL, NULL, 1,1, "ossec: The log was stopped."),
(7027, 6362, NULL, NULL, 1,1, "ossec: Stopped."),
(7027, 6363, NULL, NULL, 1,1, "ossec: Audit log paused."),
(7027, 7203, NULL, NULL, 1,1, "ossec: Arpwatch exiting."),
(7027, 9304, NULL, NULL, 1,1, "ossec: Horde IMP emergency message."),
(7027, 9352, NULL, NULL, 1,1, "ossec: Multiple Horde emergency messages."),
(7027, 9611, NULL, NULL, 1,1, "ossec: Compaq Insight Manager stopped."),
(7028, 18117, NULL, NULL, 1,1, "ossec: Windows is shutting down."),
(7028, 18141, NULL, NULL, 1,1, "ossec: Unexpected Windows shutdown."),
(7028, 5113, NULL, NULL, 1,1, "ossec: System is shutting down."),
(7029, 2830, NULL, NULL, 1,1, "ossec: Crontab rule group."),
(7029, 2831, NULL, NULL, 1,1, "ossec: Wrong crond configuration"),
(7029, 2832, NULL, NULL, 1,1, "ossec: Crontab entry changed."),
(7029, 2833, NULL, NULL, 1,1, "ossec: Root's crontab entry changed."),
(7029, 2834, NULL, NULL, 1,1, "ossec: Crontab opened for editing."),
(7030, 5300, NULL, NULL, 1,1, "ossec: Initial grouping for su messages."),
(7030, 5305, NULL, NULL, 1,1, "ossec: First time (su) is executed by user."),
(7030, 5306, NULL, NULL, 1,1, "ossec: A user has attempted to su to an unknown class."),
(7031, 7101, NULL, NULL, 1,1, "ossec: Problems with the tripwire checking"),
(7032, 5901, NULL, NULL, 1,1, "ossec: New group added to the system"),
(7032, 5902, NULL, NULL, 1,1, "ossec: New user added to the system"),
(7032, 5903, NULL, NULL, 1,1, "ossec: Group (or user) deleted from the system"),
(7032, 5904, NULL, NULL, 1,1, "ossec: Information from the user was changed"),
(7033, 5400, NULL, NULL, 1,1, "ossec: Initial group for sudo messages"),
(7033, 5401, NULL, NULL, 1,1, "ossec: Three failed attempts to run sudo"),
(7033, 5402, NULL, NULL, 1,1, "ossec: Successful sudo to ROOT executed"),
(7033, 5403, NULL, NULL, 1,1, "ossec: First time user executed sudo."),
(7034, 9100, NULL, NULL, 1,1, "ossec: PPTPD messages grouped"),
(7034, 9101, NULL, NULL, 1,1, "ossec: PPTPD failed message (communication error)"),
(7034, 9102, NULL, NULL, 1,1, "ossec: PPTPD communication error"),
(7035, 20100, NULL, NULL, 1,1, "ossec: First time this IDS alert is generated."),
(7036, 7200, NULL, NULL, 1,1, "ossec: Grouping of the arpwatch rules."),
(7036, 7205, NULL, NULL, 1,1, "ossec: Arpwatch startup/exiting messages."),
(7036, 7206, NULL, NULL, 1,1, "ossec: Arpwatch detected bad address len (ignored)."),
(7036, 7207, NULL, NULL, 1,1, "ossec: arpwatch probably run with wrong permissions"),
(7036, 7208, NULL, NULL, 1,1, "ossec: An IP has reverted to an old ethernet address."),
(7037, 7201, NULL, NULL, 1,1, "ossec: Arpwatch new host detected."),
(7038, 7202, NULL, NULL, 1,1, "ossec: Arpwatch \"flip flop\" message. IP address/MAC relation changing too often."),
(7038, 7204, NULL, NULL, 1,1, "ossec: Changed network interface for ip address."),
(7038, 7209, NULL, NULL, 1,1, "ossec: Possible arpspoofing attempt."),
(7039, 7300, NULL, NULL, 1,1, "ossec: Grouping of Symantec AV rules."),
(7039, 7301, NULL, NULL, 1,1, "ossec: Grouping of Symantec AV rules from eventlog."),
(7039, 7320, NULL, NULL, 1,1, "ossec: Virus scan updated,started or stopped."),
(7039, 7400, NULL, NULL, 1,1, "ossec: Grouping of Symantec Web Security rules."),
(7040, 40113, NULL, NULL, 1,1, "ossec: Multiple viruses detected - Possible outbreak."),
(7040, 52502, NULL, NULL, 1,1, "ossec: Virus detected"),
(7040, 52503, NULL, NULL, 1,1, "ossec: Clamd error"),
(7040, 52504, NULL, NULL, 1,1, "ossec: Clamd warning"),
(7040, 52505, NULL, NULL, 1,1, "ossec: Clamd restarted"),
(7040, 52506, NULL, NULL, 1,1, "ossec: Clamd database updated"),
(7040, 52507, NULL, NULL, 1,1, "ossec: ClamAV database update"),
(7040, 52508, NULL, NULL, 1,1, "ossec: ClamAV database updated"),
(7040, 7310, NULL, NULL, 1,1, "ossec: Virus detected."),
(7040, 7504, NULL, NULL, 1,1, "ossec: McAfee Windows AV - Virus detected and not removed."),
(7040, 7505, NULL, NULL, 1,1, "ossec: McAfee Windows AV - Virus detected and properly removed."),
(7040, 7506, NULL, NULL, 1,1, "ossec: McAfee Windows AV - Virus detected and file will be deleted."),
(7040, 7610, NULL, NULL, 1,1, "ossec: Virus detected and cleaned/quarantined/remved"),
(7040, 7611, NULL, NULL, 1,1, "ossec: Virus detected and unable to clean up."),
(7040, 7710, NULL, NULL, 1,1, "ossec: Microsoft Security Essentials - Virus detected, but unable to remove."),
(7040, 7711, NULL, NULL, 1,1, "ossec: Microsoft Security Essentials - Virus detected and properly removed."),
(7040, 7712, NULL, NULL, 1,1, "ossec: Microsoft Security Essentials - Virus detected."),
(7041, 4300, NULL, NULL, 1,1, "ossec: Grouping of PIX rules"),
(7041, 4310, NULL, NULL, 1,1, "ossec: PIX alert message."),
(7041, 4311, NULL, NULL, 1,1, "ossec: PIX critical message."),
(7041, 4312, NULL, NULL, 1,1, "ossec: PIX error message."),
(7041, 4313, NULL, NULL, 1,1, "ossec: PIX warning message."),
(7041, 4314, NULL, NULL, 1,1, "ossec: PIX notification/informational message."),
(7041, 4315, NULL, NULL, 1,1, "ossec: PIX debug message."),
(7041, 4322, NULL, NULL, 1,1, "ossec: Privilege changed in the PIX firewall."),
(7041, 4325, NULL, NULL, 1,1, "ossec: ARP collision detected by the PIX."),
(7041, 4327, NULL, NULL, 1,1, "ossec: Connection limit exceeded."),
(7041, 4330, NULL, NULL, 1,1, "ossec: Attack in progress detected by the PIX."),
(7041, 4331, NULL, NULL, 1,1, "ossec: Attack in progress detected by the PIX."),
(7041, 4332, NULL, NULL, 1,1, "ossec: Attack in progress detected by the PIX."),
(7041, 4341, NULL, NULL, 1,1, "ossec: Firewall command executed (for accounting only)."),
(7041, 4380, NULL, NULL, 1,1, "ossec: Multiple PIX alert messages."),
(7041, 4381, NULL, NULL, 1,1, "ossec: Multiple PIX critical messages."),
(7041, 4383, NULL, NULL, 1,1, "ossec: Multiple PIX warning messages."),
(7041, 4385, NULL, NULL, 1,1, "ossec: Multiple attack in progress messages."),
(7042, 19123, NULL, NULL, 1,1, "ossec: Virtual machine being reconfigured."),
(7042, 2902, NULL, NULL, 1,1, "ossec: New dpkg (Debian Package) installed."),
(7042, 2903, NULL, NULL, 1,1, "ossec: Dpkg (Debian Package) removed."),
(7042, 2932, NULL, NULL, 1,1, "ossec: New Yum package installed."),
(7042, 2933, NULL, NULL, 1,1, "ossec: Yum package updated."),
(7042, 2934, NULL, NULL, 1,1, "ossec: Yum package deleted."),
(7042, 4339, NULL, NULL, 1,1, "ossec: Firewall configuration deleted."),
(7042, 4340, NULL, NULL, 1,1, "ossec: Firewall configuration changed."),
(7042, 4508, NULL, NULL, 1,1, "ossec: Firewall policy changed."),
(7042, 4509, NULL, NULL, 1,1, "ossec: Firewall configuration changed."),
(7042, 4721, NULL, NULL, 1,1, "ossec: Cisco IOS router configuration changed."),
(7043, 18110, NULL, NULL, 1,1, "ossec: User account enabled or created."),
(7043, 18111, NULL, NULL, 1,1, "ossec: User account changed."),
(7043, 18112, NULL, NULL, 1,1, "ossec: User account disabled or deleted."),
(7043, 18115, NULL, NULL, 1,1, "ossec: General account database changed."),
(7043, 18127, NULL, NULL, 1,1, "ossec: Computer account changed/deleted."),
(7043, 18128, NULL, NULL, 1,1, "ossec: Group account added/changed/deleted."),
(7043, 18142, NULL, NULL, 1,1, "ossec: User account unlocked."),
(7043, 18143, NULL, NULL, 1,1, "ossec: Security enabled group created."),
(7043, 18144, NULL, NULL, 1,1, "ossec: Security enabled group deleted."),
(7043, 4342, NULL, NULL, 1,1, "ossec: User created or modified on the Firewall."),
(7044, 12104, NULL, NULL, 1,1, "ossec: Log permission misconfiguration in Named."),
(7044, 12110, NULL, NULL, 1,1, "ossec: Serial number from master is lower than stored."),
(7044, 12111, NULL, NULL, 1,1, "ossec: Unable to perform zone transfer."),
(7044, 18103, NULL, NULL, 1,1, "ossec: Windows error event."),
(7044, 3109, NULL, NULL, 1,1, "ossec: Sendmail save mail panic."),
(7044, 31122, NULL, NULL, 1,1, "ossec: Web server 500 error code (Internal Error)."),
(7044, 31162, NULL, NULL, 1,1, "ossec: Multiple web server 500 error code (Internal Error)."),
(7044, 4382, NULL, NULL, 1,1, "ossec: Multiple PIX error messages."),
(7044, 6303, NULL, NULL, 1,1, "ossec: The log was temporarily paused due to low disk space."),
(7044, 6364, NULL, NULL, 1,1, "ossec: DHCP Log File."),
(7045, 12100, NULL, NULL, 1,1, "ossec: Grouping of the named rules"),
(7045, 12105, NULL, NULL, 1,1, "ossec: Unexpected error while resolving domain."),
(7045, 12106, NULL, NULL, 1,1, "ossec: DNS configuration error."),
(7045, 12107, NULL, NULL, 1,1, "ossec: DNS update using RFC2136 Dynamic protocol."),
(7045, 12108, NULL, NULL, 1,1, "ossec: Query cache denied (probably config error)."),
(7045, 12112, NULL, NULL, 1,1, "ossec: Zone transfer error."),
(7045, 12113, NULL, NULL, 1,1, "ossec: Zone transfer deferred."),
(7045, 12114, NULL, NULL, 1,1, "ossec: Hostname contains characters that check-names does not like."),
(7045, 12115, NULL, NULL, 1,1, "ossec: Zone transfer."),
(7045, 12116, NULL, NULL, 1,1, "ossec: Syntax error in a named configuration file."),
(7045, 12117, NULL, NULL, 1,1, "ossec: Zone transfer rety limit exceeded"),
(7045, 12118, NULL, NULL, 1,1, "ossec: Zone has been duplicated."),
(7045, 12119, NULL, NULL, 1,1, "ossec: BIND has been started"),
(7045, 12120, NULL, NULL, 1,1, "ossec: Missing A or AAAA record"),
(7045, 12121, NULL, NULL, 1,1, "ossec: Zone has been removed from a master server"),
(7045, 12122, NULL, NULL, 1,1, "ossec: Origin of zone and owner name of SOA do not match."),
(7045, 12123, NULL, NULL, 1,1, "ossec: Zone has been duplicated"),
(7045, 12125, NULL, NULL, 1,1, "ossec: BIND Configuration error."),
(7045, 12126, NULL, NULL, 1,1, "ossec: Zone has been removed from a master server"),
(7045, 12127, NULL, NULL, 1,1, "ossec: Origin of zone and owner name of SOA do not match."),
(7045, 12128, NULL, NULL, 1,1, "ossec: Zone transfer."),
(7045, 12129, NULL, NULL, 1,1, "ossec: Zone transfer failed, unable to connect to master."),
(7045, 12130, NULL, NULL, 1,1, "ossec: Could not listen on IPv6 interface."),
(7045, 12131, NULL, NULL, 1,1, "ossec: Could not bind to an interface."),
(7045, 12132, NULL, NULL, 1,1, "ossec: Master is not authoritative for zone."),
(7045, 12133, NULL, NULL, 1,1, "ossec: Could not open configuration file, permission denied."),
(7045, 12134, NULL, NULL, 1,1, "ossec: Could not open configuration file, permission denied."),
(7045, 12135, NULL, NULL, 1,1, "ossec: Domain in SOA -E."),
(7045, 12136, NULL, NULL, 1,1, "ossec: Master appears to be down."),
(7045, 12137, NULL, NULL, 1,1, "ossec: Domain is queried for a zone transferred."),
(7045, 12138, NULL, NULL, 1,1, "ossec: Domain A record found."),
(7045, 12139, NULL, NULL, 1,1, "ossec: Bad zone transfer request."),
(7045, 12140, NULL, NULL, 1,1, "ossec: Cannot refresh a domain from the master server."),
(7045, 12141, NULL, NULL, 1,1, "ossec: Origin of zone and owner name of SOA do not match."),
(7045, 12142, NULL, NULL, 1,1, "ossec: named command channel is listening."),
(7045, 12143, NULL, NULL, 1,1, "ossec: named has created an automatic empty zone."),
(7045, 12144, NULL, NULL, 1,1, "ossec: Server does not have enough memory to reload the configuration."),
(7045, 12145, NULL, NULL, 1,1, "ossec: zone transfer denied"),
(7045, 12146, NULL, NULL, 1,1, "ossec: Cannot send a DNS response."),
(7045, 12147, NULL, NULL, 1,1, "ossec: Cannot update forwarding domain."),
(7045, 12148, NULL, NULL, 1,1, "ossec: Parsing of a configuration file has failed."),
(7046, 12101, NULL, NULL, 1,1, "ossec: Invalid DNS packet. Possibility of attack."),
(7046, 31115, NULL, NULL, 1,1, "ossec: URL too long. Higher than allowed on most browsers. Possible attack."),
(7047, 11108, NULL, NULL, 1,1, "ossec: Reverse lookup error (bad ISP config)."),
(7047, 12103, NULL, NULL, 1,1, "ossec: DNS update denied. Generally mis-configuration."),
(7048, 13100, NULL, NULL, 1,1, "ossec: Grouping for the smbd rules."),
(7048, 13101, NULL, NULL, 1,1, "ossec: Samba network problems."),
(7048, 13103, NULL, NULL, 1,1, "ossec: Samba network problems."),
(7048, 13105, NULL, NULL, 1,1, "ossec: Samba network problems (unable to connect)."),
(7048, 13106, NULL, NULL, 1,1, "ossec: "),
(7048, 13108, NULL, NULL, 1,1, "ossec: An attempt has been made to start smbd but the process is already running."),
(7048, 13109, NULL, NULL, 1,1, "ossec: An attempt has been made to start nmbd but the process is already running."),
(7048, 13110, NULL, NULL, 1,1, "ossec: Connection was denied."),
(7048, 13111, NULL, NULL, 1,1, "ossec: Socket is not connected, write failed."),
(7048, 13112, NULL, NULL, 1,1, "ossec: Segfault in gvfs-smb."),
(7049, 11400, NULL, NULL, 1,1, "ossec: Grouping for the vsftpd rules."),
(7049, 11404, NULL, NULL, 1,1, "ossec: FTP server file upload."),
(7050, 11106, NULL, NULL, 1,1, "ossec: Remote host connected to FTP server."),
(7050, 11201, NULL, NULL, 1,1, "ossec: FTP session opened."),
(7050, 11213, NULL, NULL, 1,1, "ossec: Remote host connected to FTP server."),
(7050, 11301, NULL, NULL, 1,1, "ossec: New FTP connection."),
(7050, 11401, NULL, NULL, 1,1, "ossec: FTP session opened."),
(7050, 11501, NULL, NULL, 1,1, "ossec: New FTP connection."),
(7050, 2551, NULL, NULL, 1,1, "ossec: Connection to rshd from unprivileged port. Possible network scan."),
(7050, 3901, NULL, NULL, 1,1, "ossec: New courier (imap/pop3) connection."),
(7051, 11300, NULL, NULL, 1,1, "ossec: Grouping for the pure-ftpd rules."),
(7051, 11303, NULL, NULL, 1,1, "ossec: FTP user logout/timeout"),
(7051, 11304, NULL, NULL, 1,1, "ossec: FTP notice messages"),
(7051, 11305, NULL, NULL, 1,1, "ossec: Attempt to access invalid directory"),
(7052, 11200, NULL, NULL, 1,1, "ossec: Grouping for the proftpd rules."),
(7052, 11202, NULL, NULL, 1,1, "ossec: FTP session closed."),
(7052, 11208, NULL, NULL, 1,1, "ossec: Small PassivePorts range in config file. Server misconfiguration."),
(7052, 11209, NULL, NULL, 1,1, "ossec: Attempt to bypass firewall that can't adequately keep state of FTP traffic."),
(7052, 11211, NULL, NULL, 1,1, "ossec: Mismatch in server's hostname."),
(7052, 11212, NULL, NULL, 1,1, "ossec: Reverse lookup error (bad ISP config)."),
(7052, 11214, NULL, NULL, 1,1, "ossec: Remote host disconnected due to inactivity."),
(7052, 11215, NULL, NULL, 1,1, "ossec: Remote host disconnected due to login time out."),
(7052, 11216, NULL, NULL, 1,1, "ossec: Remote host disconnected due to time out."),
(7052, 11217, NULL, NULL, 1,1, "ossec: Data transfer stalled."),
(7052, 11219, NULL, NULL, 1,1, "ossec: FTP server Buffer overflow attempt."),
(7052, 11220, NULL, NULL, 1,1, "ossec: Unable to bind to adress."),
(7052, 11221, NULL, NULL, 1,1, "ossec: IPv6 error and mod-delay info (ignored)."),
(7052, 11253, NULL, NULL, 1,1, "ossec: Multiple timed out logins from same source."),
(7053, 11500, NULL, NULL, 1,1, "ossec: Grouping for the Microsoft ftp rules."),
(7053, 11504, NULL, NULL, 1,1, "ossec: FTP client request failed."),
(7053, 11512, NULL, NULL, 1,1, "ossec: Multiple FTP errors from same source."),
(7054, 9300, NULL, NULL, 1,1, "ossec: Grouping for the Horde imp rules."),
(7054, 9301, NULL, NULL, 1,1, "ossec: Horde IMP informational message."),
(7054, 9302, NULL, NULL, 1,1, "ossec: Horde IMP notice message."),
(7054, 9303, NULL, NULL, 1,1, "ossec: Horde IMP error message."),
(7055, 9900, NULL, NULL, 1,1, "ossec: Grouping for the vpopmail rules."),
(7056, 3900, NULL, NULL, 1,1, "ossec: Grouping for the courier rules."),
(7056, 3903, NULL, NULL, 1,1, "ossec: Courier logout/timeout."),
(7058, 31100, NULL, NULL, 1,1, "ossec: Access log messages grouped."),
(7058, 31101, NULL, NULL, 1,1, "ossec: Web server 400 error code."),
(7058, 31102, NULL, NULL, 1,1, "ossec: Ignored extensions on 400 error codes."),
(7058, 31107, NULL, NULL, 1,1, "ossec: Ignored URLs for the web attacks"),
(7058, 31108, NULL, NULL, 1,1, "ossec: Ignored URLs (simple queries)."),
(7058, 31120, NULL, NULL, 1,1, "ossec: Web server 500 error code (server error)."),
(7058, 31121, NULL, NULL, 1,1, "ossec: Web server 501 error code (Not Implemented)."),
(7058, 31123, NULL, NULL, 1,1, "ossec: Web server 503 error code (Service unavailable)."),
(7058, 31140, NULL, NULL, 1,1, "ossec: Ignoring google/msn/yahoo bots."),
(7059, 31104, NULL, NULL, 1,1, "ossec: Common web attack."),
(7059, 31105, NULL, NULL, 1,1, "ossec: XSS (Cross Site Scripting) attempt."),
(7059, 31106, NULL, NULL, 1,1, "ossec: A web attack returned code 200 (success)."),
(7059, 31109, NULL, NULL, 1,1, "ossec: MSSQL Injection attempt (/ur.php, urchin.js)"),
(7059, 31110, NULL, NULL, 1,1, "ossec: PHP CGI-bin vulnerability attempt."),
(7059, 31153, NULL, NULL, 1,1, "ossec: Multiple common web attacks from same souce ip."),
(7059, 31154, NULL, NULL, 1,1, "ossec: Multiple XSS (Cross Site Scripting) attempts from same souce ip."),
(7059, 31411, NULL, NULL, 1,1, "ossec: PHP web attack."),
(7059, 31501, NULL, NULL, 1,1, "ossec: WordPress Comment Spam (coming from a fake search engine UA)."),
(7059, 31502, NULL, NULL, 1,1, "ossec: TimThumb vulnerability exploit attempt."),
(7059, 31503, NULL, NULL, 1,1, "ossec: osCommerce login.php bypass attempt."),
(7059, 31504, NULL, NULL, 1,1, "ossec: osCommerce file manager login.php bypass attempt."),
(7059, 31505, NULL, NULL, 1,1, "ossec: TimThumb backdoor access attempt."),
(7059, 31506, NULL, NULL, 1,1, "ossec: Cart.php directory transversal attempt."),
(7059, 31507, NULL, NULL, 1,1, "ossec: MSSQL Injection attempt (ur.php, urchin.js)."),
(7059, 31508, NULL, NULL, 1,1, "ossec: Blacklisted user agent (known malicious user agent)."),
(7059, 31509, NULL, NULL, 1,1, "ossec: WordPress login attempt."),
(7059, 31510, NULL, NULL, 1,1, "ossec: WordPress wp-login.php brute force attempt."),
(7059, 31511, NULL, NULL, 1,1, "ossec: Blacklisted user agent (wget)."),
(7059, 31512, NULL, NULL, 1,1, "ossec: TimThumb vulnerability exploit attempt."),
(7059, 31513, NULL, NULL, 1,1, "ossec: BBS delete.php exploit attempt."),
(7059, 31550, NULL, NULL, 1,1, "ossec: Anomaly URL query (attempting to pass null termination)."),
(7060, 31103, NULL, NULL, 1,1, "ossec: SQL injection attempt."),
(7060, 31152, NULL, NULL, 1,1, "ossec: Multiple SQL injection attempts from same souce ip."),
(7062, 30100, NULL, NULL, 1,1, "ossec: Apache messages grouped."),
(7062, 30101, NULL, NULL, 1,1, "ossec: Apache error messages grouped."),
(7062, 30102, NULL, NULL, 1,1, "ossec: Apache warn messages grouped."),
(7062, 30103, NULL, NULL, 1,1, "ossec: Apache notice messages grouped."),
(7062, 30200, NULL, NULL, 1,1, "ossec: Modsecurity alert."),
(7062, 31300, NULL, NULL, 1,1, "ossec: Nginx messages grouped."),
(7062, 31301, NULL, NULL, 1,1, "ossec: Nginx error message."),
(7062, 31302, NULL, NULL, 1,1, "ossec: Nginx warning message."),
(7062, 31303, NULL, NULL, 1,1, "ossec: Nginx critical message."),
(7062, 31310, NULL, NULL, 1,1, "ossec: Server returned 404 (reported in the access.log)."),
(7062, 31311, NULL, NULL, 1,1, "ossec: Incomplete client request."),
(7062, 31312, NULL, NULL, 1,1, "ossec: Initial 401 authentication request."),
(7062, 31401, NULL, NULL, 1,1, "ossec: PHP Warning message."),
(7062, 31402, NULL, NULL, 1,1, "ossec: PHP Fatal error."),
(7062, 31403, NULL, NULL, 1,1, "ossec: PHP Parse error."),
(7062, 31404, NULL, NULL, 1,1, "ossec: PHP Warning message."),
(7062, 31405, NULL, NULL, 1,1, "ossec: PHP Fatal error."),
(7062, 31406, NULL, NULL, 1,1, "ossec: PHP Parse error."),
(7062, 31410, NULL, NULL, 1,1, "ossec: PHP Warning message."),
(7062, 31412, NULL, NULL, 1,1, "ossec: PHP internal error (missing file)."),
(7062, 31420, NULL, NULL, 1,1, "ossec: PHP Fatal error."),
(7062, 31421, NULL, NULL, 1,1, "ossec: PHP internal error (missing file or function)."),
(7062, 31430, NULL, NULL, 1,1, "ossec: PHP Parse error."),
(7063, 30107, NULL, NULL, 1,1, "ossec: Code Red attack."),
(7063, 35021, NULL, NULL, 1,1, "ossec: Attempt to access a Beagle worm (or variant) file."),
(7063, 35022, NULL, NULL, 1,1, "ossec: Attempt to access a worm/trojan related site."),
(7064, 30112, NULL, NULL, 1,1, "ossec: Attempt to access an non-existent file (those are reported on the access.log)."),
(7065, 30115, NULL, NULL, 1,1, "ossec: Invalid URI (bad client request)."),
(7065, 30116, NULL, NULL, 1,1, "ossec: Multiple Invalid URI requests from same source."),
(7065, 30117, NULL, NULL, 1,1, "ossec: Invalid URI, file name too long."),
(7065, 31320, NULL, NULL, 1,1, "ossec: Invalid URI, file name too long."),
(7066, 50100, NULL, NULL, 1,1, "ossec: MySQL messages grouped."),
(7066, 50107, NULL, NULL, 1,1, "ossec: Database query."),
(7066, 50108, NULL, NULL, 1,1, "ossec: User disconnected from database."),
(7066, 50125, NULL, NULL, 1,1, "ossec: Database error."),
(7067, 50500, NULL, NULL, 1,1, "ossec: PostgreSQL messages grouped."),
(7067, 50501, NULL, NULL, 1,1, "ossec: PostgreSQL log message."),
(7067, 50502, NULL, NULL, 1,1, "ossec: PostgreSQL informational message."),
(7067, 50503, NULL, NULL, 1,1, "ossec: PostgreSQL error message."),
(7067, 50504, NULL, NULL, 1,1, "ossec: PostgreSQL error message."),
(7067, 50505, NULL, NULL, 1,1, "ossec: PostgreSQL debug message."),
(7067, 50510, NULL, NULL, 1,1, "ossec: Database query."),
(7068, 4101, NULL, NULL, 1,1, "ossec: Firewall drop event."),
(7069, 4151, NULL, NULL, 1,1, "ossec: Multiple Firewall drop events from same source."),
(7070, 4700, NULL, NULL, 1,1, "ossec: Grouping of Cisco IOS rules."),
(7070, 4710, NULL, NULL, 1,1, "ossec: Cisco IOS emergency message."),
(7070, 4711, NULL, NULL, 1,1, "ossec: Cisco IOS alert message."),
(7070, 4712, NULL, NULL, 1,1, "ossec: Cisco IOS critical message."),
(7070, 4713, NULL, NULL, 1,1, "ossec: Cisco IOS error message."),
(7070, 4714, NULL, NULL, 1,1, "ossec: Cisco IOS warning message."),
(7070, 4715, NULL, NULL, 1,1, "ossec: Cisco IOS notification message."),
(7070, 4716, NULL, NULL, 1,1, "ossec: Cisco IOS informational message."),
(7070, 4717, NULL, NULL, 1,1, "ossec: Cisco IOS debug message."),
(7071, 4500, NULL, NULL, 1,1, "ossec: Grouping for the Netscreen Firewall rules"),
(7071, 4501, NULL, NULL, 1,1, "ossec: Netscreen notification message."),
(7071, 4502, NULL, NULL, 1,1, "ossec: Netscreen warning message."),
(7071, 4503, NULL, NULL, 1,1, "ossec: Netscreen critical/alert message."),
(7071, 4504, NULL, NULL, 1,1, "ossec: Netscreen informational message."),
(7071, 4513, NULL, NULL, 1,1, "ossec: Netscreen critical/alert message."),
(7071, 4550, NULL, NULL, 1,1, "ossec: Multiple Netscreen critical messages from same source IP."),
(7071, 4551, NULL, NULL, 1,1, "ossec: Multiple Netscreen critical messages."),
(7071, 4552, NULL, NULL, 1,1, "ossec: Multiple Netscreen alert messages from same source IP."),
(7071, 4553, NULL, NULL, 1,1, "ossec: Multiple Netscreen alert messages."),
(7072, 4800, NULL, NULL, 1,1, "ossec: SonicWall messages grouped."),
(7072, 4801, NULL, NULL, 1,1, "ossec: SonicWall critical message."),
(7072, 4802, NULL, NULL, 1,1, "ossec: SonicWall critical message."),
(7072, 4803, NULL, NULL, 1,1, "ossec: SonicWall error message."),
(7072, 4804, NULL, NULL, 1,1, "ossec: SonicWall warning message."),
(7072, 4805, NULL, NULL, 1,1, "ossec: SonicWall notice message."),
(7072, 4806, NULL, NULL, 1,1, "ossec: SonicWall informational message."),
(7072, 4807, NULL, NULL, 1,1, "ossec: SonicWall debug message."),
(7073, 3300, NULL, NULL, 1,1, "ossec: Grouping of the postfix reject rules."),
(7073, 3320, NULL, NULL, 1,1, "ossec: Grouping of the postfix rules."),
(7073, 3334, NULL, NULL, 1,1, "ossec: Postfix started."),
(7073, 3390, NULL, NULL, 1,1, "ossec: Grouping of the clamsmtpd rules."),
(7074, 3102, NULL, NULL, 1,1, "ossec: Sender domain does not have any valid MX record (Requested action aborted)."),
(7074, 3103, NULL, NULL, 1,1, "ossec: Rejected by access list (55x: Requested action not taken)."),
(7074, 3104, NULL, NULL, 1,1, "ossec: Attepmt to use mail server as relay (550: Requested action not taken)."),
(7074, 3105, NULL, NULL, 1,1, "ossec: Sender domain is not found  (553: Requested action not taken)."),
(7074, 3106, NULL, NULL, 1,1, "ossec: Sender address does not have domain (553: Requested action not taken)."),
(7074, 3108, NULL, NULL, 1,1, "ossec: Sendmail rejected due to pre-greeting."),
(7074, 3191, NULL, NULL, 1,1, "ossec: SMF-SAV sendmail milter unable to verify address (REJECTED)."),
(7074, 3301, NULL, NULL, 1,1, "ossec: Attempt to use mail server as relay (client host rejected)."),
(7074, 3302, NULL, NULL, 1,1, "ossec: Rejected by access list (Requested action not taken)."),
(7074, 3303, NULL, NULL, 1,1, "ossec: Sender domain is not found (450: Requested mail action not taken)."),
(7074, 3304, NULL, NULL, 1,1, "ossec: Improper use of SMTP command pipelining (503: Bad sequence of commands)."),
(7074, 3305, NULL, NULL, 1,1, "ossec: Receipent address must contain FQDN (504: Command parameter not implemented)."),
(7074, 3306, NULL, NULL, 1,1, "ossec: IP Address black-listed by anti-spam (blocked)."),
(7074, 3702, NULL, NULL, 1,1, "ossec: Mail Scanner spam detected."),
(7074, 3801, NULL, NULL, 1,1, "ossec: E-mail rcpt is not valid (invalid account)."),
(7074, 3802, NULL, NULL, 1,1, "ossec: E-mail 500 error code."),
(7075, 3151, NULL, NULL, 1,1, "ossec: Sender domain has bogus MX record. It should not be sending e-mail."),
(7075, 3152, NULL, NULL, 1,1, "ossec: Multiple attempts to send e-mail from a previously rejected sender (access)."),
(7075, 3153, NULL, NULL, 1,1, "ossec: Multiple relaying attempts of spam."),
(7075, 3154, NULL, NULL, 1,1, "ossec: Multiple attempts to send e-mail from invalid/unknown sender domain."),
(7075, 3155, NULL, NULL, 1,1, "ossec: Multiple attempts to send e-mail from invalid/unknown sender."),
(7075, 3156, NULL, NULL, 1,1, "ossec: Multiple rejected e-mails from same source ip."),
(7075, 3158, NULL, NULL, 1,1, "ossec: Multiple pre-greetings rejects."),
(7075, 3351, NULL, NULL, 1,1, "ossec: Multiple relaying attempts of spam."),
(7075, 3352, NULL, NULL, 1,1, "ossec: Multiple attempts to send e-mail from a rejected sender IP (access)."),
(7075, 3353, NULL, NULL, 1,1, "ossec: Multiple attempts to send e-mail from invalid/unknown sender domain."),
(7075, 3354, NULL, NULL, 1,1, "ossec: Multiple misuse of SMTP service (bad sequence of commands)."),
(7075, 3355, NULL, NULL, 1,1, "ossec: Multiple attempts to send e-mail to invalid recipient or from unknown sender domain."),
(7075, 3356, NULL, NULL, 1,1, "ossec: Multiple attempts to send e-mail from black-listed IP address (blocked)."),
(7075, 3751, NULL, NULL, 1,1, "ossec: Multiple attempts of spam."),
(7075, 3851, NULL, NULL, 1,1, "ossec: Multiple e-mail attempts to an invalid account."),
(7075, 3852, NULL, NULL, 1,1, "ossec: Multiple e-mail 500 error code (spam)."),
(7076, 3100, NULL, NULL, 1,1, "ossec: Grouping of the sendmail rules."),
(7076, 3101, NULL, NULL, 1,1, "ossec: Grouping of the sendmail reject rules."),
(7076, 3107, NULL, NULL, 1,1, "ossec: Sendmail rejected message."),
(7077, 3190, NULL, NULL, 1,1, "ossec: Grouping of the smf-sav sendmail milter rules."),
(7078, 3600, NULL, NULL, 1,1, "ossec: Grouping of the imapd rules."),
(7078, 3603, NULL, NULL, 1,1, "ossec: Imapd user logout."),
(7079, 3700, NULL, NULL, 1,1, "ossec: Grouping of mailscanner rules."),
(7079, 3701, NULL, NULL, 1,1, "ossec: Non spam message. Ignored."),
(7081, 3800, NULL, NULL, 1,1, "ossec: Grouping of Exchange rules."),
(7082, 14100, NULL, NULL, 1,1, "ossec: Grouping of racoon rules."),
(7082, 14110, NULL, NULL, 1,1, "ossec: Racoon informational message."),
(7082, 14111, NULL, NULL, 1,1, "ossec: Racoon error message."),
(7082, 14112, NULL, NULL, 1,1, "ossec: Racoon warning message."),
(7082, 14121, NULL, NULL, 1,1, "ossec: Roadwarrior configuration (ignored error)."),
(7082, 14122, NULL, NULL, 1,1, "ossec: Roadwarrior configuration (ignored warning)."),
(7082, 14123, NULL, NULL, 1,1, "ossec: Invalid configuration settings (ignored error)."),
(7082, 14151, NULL, NULL, 1,1, "ossec: Multiple failed VPN logins."),
(7083, 14200, NULL, NULL, 1,1, "ossec: Grouping of Cisco VPN concentrator rules"),
(7084, 3500, NULL, NULL, 1,1, "ossec: Grouping for the spamd rules"),
(7084, 3501, NULL, NULL, 1,1, "ossec: SPAMD result message (not very usefull here)."),
(7084, 3502, NULL, NULL, 1,1, "ossec: Spamd debug event (reading message)."),
(7085, 18106, NULL, NULL, 1,1, "ossec: Windows Logon Failure."),
(7085, 18130, NULL, NULL, 1,1, "ossec: Logon Failure - Unknown user or bad password."),
(7085, 18135, NULL, NULL, 1,1, "ossec: Logon Failure - User not granted logon type."),
(7085, 18136, NULL, NULL, 1,1, "ossec: Logon Failure - Account's password expired."),
(7085, 18137, NULL, NULL, 1,1, "ossec: Logon Failure - Internal error."),
(7085, 18138, NULL, NULL, 1,1, "ossec: Logon Failure - Account locked out."),
(7085, 18139, NULL, NULL, 1,1, "ossec: Windows DC Logon Failure."),
(7085, 18180, NULL, NULL, 1,1, "ossec: MS SQL Server Logon Failure."),
(7086, 18113, NULL, NULL, 1,1, "ossec: Windows Audit Policy changed."),
(7086, 18145, NULL, NULL, 1,1, "ossec: Service startup type was changed."),
(7086, 7720, NULL, NULL, 1,1, "ossec: Microsoft Security Essentials - Configuration changed."),
(7087, 18118, NULL, NULL, 1,1, "ossec: Windows audit log was cleared."),
(7087, 593, NULL, NULL, 1,1, "ossec: Microsoft Event log cleared."),
(7088, 18131, NULL, NULL, 1,1, "ossec: Logon Failure - Account logon time restriction violation."),
(7088, 18132, NULL, NULL, 1,1, "ossec: Logon Failure - Account currently disabled."),
(7088, 18133, NULL, NULL, 1,1, "ossec: Logon Failure - Specified account expired."),
(7088, 18134, NULL, NULL, 1,1, "ossec: Logon Failure - User not allowed to login at this computer."),
(7089, 18140, NULL, NULL, 1,1, "ossec: System time changed."),
(7090, 18170, NULL, NULL, 1,1, "ossec: Windows DC integrity check on decrypted field failed."),
(7090, 18171, NULL, NULL, 1,1, "ossec: Windows DC - Possible replay attack."),
(7090, 18172, NULL, NULL, 1,1, "ossec: Windows DC - Clock skew too great."),
(7090, 40112, NULL, NULL, 1,1, "ossec: Multiple authentication failures followed by a success."),
(7090, 592, NULL, NULL, 1,1, "ossec: Log file size reduced."),
(7091, 40501, NULL, NULL, 1,1, "ossec: Attacks followed by the addition of an user."),
(7092, 31200, NULL, NULL, 1,1, "ossec: Grouping of Zeus rules."),
(7092, 31201, NULL, NULL, 1,1, "ossec: Grouping of Zeus informational logs."),
(7092, 31202, NULL, NULL, 1,1, "ossec: Zeus warning log."),
(7092, 31203, NULL, NULL, 1,1, "ossec: Zeus serious log."),
(7092, 31204, NULL, NULL, 1,1, "ossec: Zeus fatal log."),
(7092, 31206, NULL, NULL, 1,1, "ossec: Configuration warning (ignored)."),
(7092, 31251, NULL, NULL, 1,1, "ossec: Multiple Zeus warnings."),
(7093, 509, NULL, NULL, 1,1, "ossec: Rootcheck event."),
(7093, 510, NULL, NULL, 1,1, "ossec: Host-based anomaly detection event (rootcheck)."),
(7093, 511, NULL, NULL, 1,1, "ossec: Ignored common NTFS ADS entries."),
(7093, 512, NULL, NULL, 1,1, "ossec: Windows Audit event."),
(7093, 513, NULL, NULL, 1,1, "ossec: Windows malware detected."),
(7093, 514, NULL, NULL, 1,1, "ossec: Windows application monitor event."),
(7093, 516, NULL, NULL, 1,1, "ossec: System Audit event."),
(7093, 518, NULL, NULL, 1,1, "ossec: Windows Adware/Spyware application found."),
(7093, 519, NULL, NULL, 1,1, "ossec: System Audit: Vulnerable web application found."),
(7094, 515, NULL, NULL, 1,1, "ossec: Ignoring rootcheck/syscheck scan messages."),
(7094, 550, NULL, NULL, 1,1, "ossec: Integrity checksum changed."),
(7094, 551, NULL, NULL, 1,1, "ossec: Integrity checksum changed again (2nd time)."),
(7094, 552, NULL, NULL, 1,1, "ossec: Integrity checksum changed again (3rd time)."),
(7094, 553, NULL, NULL, 1,1, "ossec: File deleted. Unable to retrieve checksum."),
(7094, 554, NULL, NULL, 1,1, "ossec: File added to the system."),
(7094, 594, NULL, NULL, 1,1, "ossec: Registry Integrity Checksum Changed"),
(7094, 595, NULL, NULL, 1,1, "ossec: Registry Integrity Checksum Changed Again (2nd time)"),
(7094, 596, NULL, NULL, 1,1, "ossec: Registry Integrity Checksum Changed Again (3rd time)"),
(7094, 597, NULL, NULL, 1,1, "ossec: Registry Entry Deleted. Unable to Retrieve Checksum"),
(7094, 598, NULL, NULL, 1,1, "ossec: Registry Entry Added to the System"),
(7095, 580, NULL, NULL, 1,1, "ossec: Host information changed."),
(7095, 581, NULL, NULL, 1,1, "ossec: Host information added."),
(7097, 1, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 10, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 11, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 12, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 13, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 14, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 140125, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 15, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 16, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 17, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 18, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 18560, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 19, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 2, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 20, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 21, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 22, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 23, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 24, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 25, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 26, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 27, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 28, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 29, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 3, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 30, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 31, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 32, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 33, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 34, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 35, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 36, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 37, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 38, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 39, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 4, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 40, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 41, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 42, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 43, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 44, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 45, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 46, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 47, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 48, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 49, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 5, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 50, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 51, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 52, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 53, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 54, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 55, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 56, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 57, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 58, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 59, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 6, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 60, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 61, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 62, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 63, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 64, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 65, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 66, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 67, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 68, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 69, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 7, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 70, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 71, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 72, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 73, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 74, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 75, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 76, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 77, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 78, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 79, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 8, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 80, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 81, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 82, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 83, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 84, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 85, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 86, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 87, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 88, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 89, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 9, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 90, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 91, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 92, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 93, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 94, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 95, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 96, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 97, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 98, NULL, NULL, 1,1, "ossec: Not founded"),
(7097, 99, NULL, NULL, 1,1, "ossec: Not founded"),
(7098, 11100, NULL, NULL, 1,1, "ossec: Grouping for the ftpd rules."),
(7098, 11102, NULL, NULL, 1,1, "ossec: File created via FTP"),
(7098, 11103, NULL, NULL, 1,1, "ossec: File deleted via FTP"),
(7098, 11104, NULL, NULL, 1,1, "ossec: User uploaded a file to server."),
(7098, 11105, NULL, NULL, 1,1, "ossec: User downloaded a file to server."),
(7098, 11110, NULL, NULL, 1,1, "ossec: User disconnected due to time out."),
(7099, 18200, NULL, NULL, 1,1, "ossec: Group Account Created"),
(7099, 18202, NULL, NULL, 1,1, "ossec: Security Enabled Global Group Created"),
(7099, 18206, NULL, NULL, 1,1, "ossec: Security Enabled Local Group Created"),
(7099, 18212, NULL, NULL, 1,1, "ossec: Security Enabled Universal Group Created"),
(7100, 9700, NULL, NULL, 1,1, "ossec: Dovecot Messages Grouped."),
(7100, 9703, NULL, NULL, 1,1, "ossec: Dovecot is Starting Up."),
(7100, 9704, NULL, NULL, 1,1, "ossec: Dovecot Fatal Failure."),
(7100, 9706, NULL, NULL, 1,1, "ossec: Dovecot Session Disconnected."),
(7101, 9600, NULL, NULL, 1,1, "ossec: cimserver messages grouped."),
(7102, 555, NULL, NULL, 1,1, "ossec: Integrity checksum for agentless device changed."),
(7103, 6301, NULL, NULL, 1,1, "ossec: The log was started."),
(7103, 6361, NULL, NULL, 1,1, "ossec: Started."),
(7104, 530, NULL, NULL, 1,1, "ossec: OSSEC process monitoring rules."),
(7105, 7600, NULL, NULL, 1,1, "ossec: Grouping of Trend OSCE rules."),
(7105, 7612, NULL, NULL, 1,1, "ossec: Virus scan completed with no errors detected."),
(7105, 7613, NULL, NULL, 1,1, "ossec: Virus scan passed by found potential security risk."),
(7106, 6316, NULL, NULL, 1,1, "ossec: IP address cleanup operation has began."),
(7106, 6317, NULL, NULL, 1,1, "ossec: IP address cleanup statistics."),
(7107, 18114, NULL, NULL, 1,1, "ossec: Group Account Changed"),
(7107, 18203, NULL, NULL, 1,1, "ossec: Security Enabled Global Group Member Added"),
(7107, 18204, NULL, NULL, 1,1, "ossec: Security Enabled Global Group Member Removed"),
(7107, 18207, NULL, NULL, 1,1, "ossec: Security Enabled Local Group Member Added"),
(7107, 18208, NULL, NULL, 1,1, "ossec: Security Enabled Local Group Member Removed"),
(7107, 18210, NULL, NULL, 1,1, "ossec: Security Enabled Local Group Changed"),
(7107, 18211, NULL, NULL, 1,1, "ossec: Security Enabled Global Group Changed"),
(7107, 18213, NULL, NULL, 1,1, "ossec: Security Enabled Universal Group Changed"),
(7107, 18214, NULL, NULL, 1,1, "ossec: Security Enabled Universal Group Member Added"),
(7107, 18215, NULL, NULL, 1,1, "ossec: Security Enabled Universal Group Member Removed"),
(7107, 18217, NULL, NULL, 1,1, "ossec: Administrators Group Changed"),
(7107, 18218, NULL, NULL, 1,1, "ossec: Everyone Group Changed"),
(7107, 18219, NULL, NULL, 1,1, "ossec: Enterprise Domain Controllers Group Changed"),
(7107, 18220, NULL, NULL, 1,1, "ossec: Authenticated Users Group Changed"),
(7107, 18221, NULL, NULL, 1,1, "ossec: Terminal Server Users Group Changed"),
(7107, 18222, NULL, NULL, 1,1, "ossec: Domain Admins Group Changed"),
(7107, 18223, NULL, NULL, 1,1, "ossec: Domain Users Group Changed"),
(7107, 18225, NULL, NULL, 1,1, "ossec: Domain Guests Group Changed"),
(7107, 18226, NULL, NULL, 1,1, "ossec: Domain Computers Group Changed"),
(7107, 18227, NULL, NULL, 1,1, "ossec: Domain Controllers Group Changed"),
(7107, 18228, NULL, NULL, 1,1, "ossec: Cert Publishers Group Changed"),
(7107, 18229, NULL, NULL, 1,1, "ossec: Schema Admins Group Changed"),
(7107, 18230, NULL, NULL, 1,1, "ossec: Enterprise Admins Group Changed"),
(7107, 18231, NULL, NULL, 1,1, "ossec: Group Policy Creator Owners Group Changed"),
(7107, 18232, NULL, NULL, 1,1, "ossec: RAS and IAS Servers Group Changed"),
(7107, 18233, NULL, NULL, 1,1, "ossec: Users Group Changed"),
(7107, 18234, NULL, NULL, 1,1, "ossec: Guests Group Changed"),
(7107, 18235, NULL, NULL, 1,1, "ossec: Power Users Group Changed"),
(7107, 18236, NULL, NULL, 1,1, "ossec: Account Operators Group Changed"),
(7107, 18237, NULL, NULL, 1,1, "ossec: Server Operators Group Changed"),
(7107, 18238, NULL, NULL, 1,1, "ossec: Print Operators Group Changed"),
(7107, 18239, NULL, NULL, 1,1, "ossec: Backup Operators Group Changed"),
(7107, 18240, NULL, NULL, 1,1, "ossec: Replicators Group Changed"),
(7107, 18241, NULL, NULL, 1,1, "ossec: Pre-Windows 2000 Compatible Access Group Changed"),
(7107, 18242, NULL, NULL, 1,1, "ossec: Remote Desktop Users Group Changed"),
(7107, 18243, NULL, NULL, 1,1, "ossec: Network Configuration Operators Group Changed"),
(7107, 18244, NULL, NULL, 1,1, "ossec: Incoming Forest Trust Builders Group Changed"),
(7107, 18245, NULL, NULL, 1,1, "ossec: Performance Monitor Users Group Changed"),
(7107, 18246, NULL, NULL, 1,1, "ossec: Performance Log Users Group Changed"),
(7107, 18247, NULL, NULL, 1,1, "ossec: Windows Authorization Access Group Changed"),
(7107, 18248, NULL, NULL, 1,1, "ossec: Terminal Server License Servers Group Changed"),
(7107, 18249, NULL, NULL, 1,1, "ossec: Distributed COM Users Group Changed"),
(7107, 18250, NULL, NULL, 1,1, "ossec: Enterprise Read-only Domain Controllers Group Changed"),
(7107, 18251, NULL, NULL, 1,1, "ossec: Read-only Domain Controllers Group Changed"),
(7107, 18252, NULL, NULL, 1,1, "ossec: Cryptographic Operators Group Changed"),
(7107, 18253, NULL, NULL, 1,1, "ossec: Allowed RODC Password Replication Group Changed"),
(7107, 18254, NULL, NULL, 1,1, "ossec: Denied RODC Password Replication Group Changed"),
(7107, 18255, NULL, NULL, 1,1, "ossec: Event Log Readers Group Changed"),
(7107, 18256, NULL, NULL, 1,1, "ossec: Certificate Service DCOM Access Group Changed"),
(7108, 17102, NULL, NULL, 1,1, "ossec: Successful login during weekend."),
(7109, 6300, NULL, NULL, 1,1, "ossec: Grouping for the MS-DHCP rules."),
(7109, 6350, NULL, NULL, 1,1, "ossec: Grouping for the MS-DHCP rules."),
(7110, 6351, NULL, NULL, 1,1, "ossec: Solicit."),
(7110, 6352, NULL, NULL, 1,1, "ossec: Advertise."),
(7110, 6354, NULL, NULL, 1,1, "ossec: Confirm."),
(7110, 6355, NULL, NULL, 1,1, "ossec: Renew."),
(7110, 6356, NULL, NULL, 1,1, "ossec: Rebind."),
(7110, 6357, NULL, NULL, 1,1, "ossec: DHCP Decline."),
(7110, 6358, NULL, NULL, 1,1, "ossec: Release."),
(7110, 6359, NULL, NULL, 1,1, "ossec: Information Request."),
(7110, 6360, NULL, NULL, 1,1, "ossec: Scope Full."),
(7110, 6365, NULL, NULL, 1,1, "ossec: Bad Address."),
(7110, 6366, NULL, NULL, 1,1, "ossec: Address is already in use."),
(7110, 6367, NULL, NULL, 1,1, "ossec: Client deleted."),
(7110, 6368, NULL, NULL, 1,1, "ossec: DNS record not deleted."),
(7110, 6369, NULL, NULL, 1,1, "ossec: Expired."),
(7110, 6370, NULL, NULL, 1,1, "ossec: Expired and Deleted count."),
(7110, 6371, NULL, NULL, 1,1, "ossec: Database cleanup begin."),
(7110, 6372, NULL, NULL, 1,1, "ossec: Database cleanup end."),
(7110, 6373, NULL, NULL, 1,1, "ossec: Service not authorized in AD."),
(7110, 6374, NULL, NULL, 1,1, "ossec: Service authorized in AD."),
(7110, 6376, NULL, NULL, 1,1, "ossec: Service has not determined if it is authorized in AD."),
(7111, 07512, NULL, NULL, 1,1, "ossec: McAfee Windows AV - Virus program or DAT update failed."),
(7111, 7500, NULL, NULL, 1,1, "ossec: Grouping of McAfee Windows AV rules."),
(7111, 7501, NULL, NULL, 1,1, "ossec: McAfee Windows AV informational event."),
(7111, 7502, NULL, NULL, 1,1, "ossec: McAfee Windows AV warning event."),
(7111, 7503, NULL, NULL, 1,1, "ossec: McAfee Windows AV error event."),
(7111, 7507, NULL, NULL, 1,1, "ossec: McAfee Windows AV - Scan started or stopped."),
(7111, 7508, NULL, NULL, 1,1, "ossec: McAfee Windows AV - Scan completed with no viruses found."),
(7111, 7509, NULL, NULL, 1,1, "ossec: McAfee Windows AV - Virus scan cancelled."),
(7111, 7510, NULL, NULL, 1,1, "ossec: McAfee Windows AV - Virus scan cancelled due to shutdown."),
(7111, 7511, NULL, NULL, 1,1, "ossec: McAfee Windows AV - Virus program or DAT update succeeded."),
(7111, 7513, NULL, NULL, 1,1, "ossec: McAfee Windows AV - Virus program or DAT update cancelled."),
(7111, 7514, NULL, NULL, 1,1, "ossec: McAfee Windows AV - EICAR test file detected."),
(7111, 7550, NULL, NULL, 1,1, "ossec: Multiple McAfee AV warning events."),
(7112, 2930, NULL, NULL, 1,1, "ossec: Yum logs."),
(7112, 2931, NULL, NULL, 1,1, "ossec: Yum logs."),
(7113, 9500, NULL, NULL, 1,1, "ossec: Wordpress messages grouped."),
(7113, 9503, NULL, NULL, 1,1, "ossec: WPsyslog was successfully initialized."),
(7113, 9504, NULL, NULL, 1,1, "ossec: Wordpress plugin deactivated."),
(7113, 9505, NULL, NULL, 1,1, "ossec: Wordpress Comment Flood Attempt."),
(7113, 9510, NULL, NULL, 1,1, "ossec: Attack against Wordpress detected."),
(7114, 7701, NULL, NULL, 1,1, "ossec: Grouping of Microsoft Security Essentials rules."),
(7114, 7731, NULL, NULL, 1,1, "ossec: Microsoft Security Essentials - EICAR test file detected."),
(7114, 7750, NULL, NULL, 1,1, "ossec: Multiple Microsoft Security Essentials AV warnings detected."),
(7114, 7751, NULL, NULL, 1,1, "ossec: Multiple Microsoft Security Essentials AV warnings detected."),
(7115, 17101, NULL, NULL, 1,1, "ossec: Successful login during non-business hours."),
(7116, 2900, NULL, NULL, 1,1, "ossec: Dpkg (Debian Package) log."),
(7116, 2901, NULL, NULL, 1,1, "ossec: New dpkg (Debian Package) requested to install."),
(7117, 9400, NULL, NULL, 1,1, "ossec: Roundcube messages groupe.d"),
(7118, 9800, NULL, NULL, 1,1, "ossec: Grouping for the vm-pop3d rules."),
(7119, 6100, NULL, NULL, 1,1, "ossec: Solaris BSM Auditing messages grouped."),
(7119, 6101, NULL, NULL, 1,1, "ossec: Auditing session failed."),
(7119, 6102, NULL, NULL, 1,1, "ossec: Auditing session succeeded."),
(7120, 19100, NULL, NULL, 1,1, "ossec: VMWare messages grouped."),
(7120, 19101, NULL, NULL, 1,1, "ossec: VMWare ESX syslog messages grouped."),
(7120, 19102, NULL, NULL, 1,1, "ossec: VMware ESX critical message."),
(7120, 19103, NULL, NULL, 1,1, "ossec: VMware ESX error message."),
(7120, 19104, NULL, NULL, 1,1, "ossec: VMware ESX warning message."),
(7120, 19105, NULL, NULL, 1,1, "ossec: VMware ESX notice message."),
(7120, 19106, NULL, NULL, 1,1, "ossec: VMware ESX informational message."),
(7120, 19107, NULL, NULL, 1,1, "ossec: VMware ESX verbose message."),
(7120, 19121, NULL, NULL, 1,1, "ossec: Virtual machine being turned ON."),
(7120, 19122, NULL, NULL, 1,1, "ossec: Virtual machine state changed to ON."),
(7121, 6200, NULL, NULL, 1,1, "ossec: Asterisk messages grouped."),
(7121, 6201, NULL, NULL, 1,1, "ossec: Asterisk notice messages grouped."),
(7121, 6202, NULL, NULL, 1,1, "ossec: Asterisk warning message."),
(7121, 6203, NULL, NULL, 1,1, "ossec: Asterisk error message."),
(7121, 6250, NULL, NULL, 1,1, "ossec: Multiple failed logins (user enumeration in process)."),
(7121, 6251, NULL, NULL, 1,1, "ossec: Multiple failed logins."),
(7121, 6252, NULL, NULL, 1,1, "ossec: Extension enumeration."),
(7121, 6254, NULL, NULL, 1,1, "ossec: Extension IAX Enumeration."),
(7121, 6257, NULL, NULL, 1,1, "ossec: Multiple failed logins."),
(7122, 6321, NULL, NULL, 1,1, "ossec: Codes above 50 are used for Rogue Server Detection information."),
(7123, 6318, NULL, NULL, 1,1, "ossec: DNS update request to the named DNS server."),
(7123, 6319, NULL, NULL, 1,1, "ossec: DNS update failed."),
(7123, 6320, NULL, NULL, 1,1, "ossec: DNS update successful."),
(7123, 6322, NULL, NULL, 1,1, "ossec: A lease was expired and DNS records were deleted."),
(7124, 6304, NULL, NULL, 1,1, "ossec: A new IP address was leased to a client."),
(7124, 6305, NULL, NULL, 1,1, "ossec: A lease was renewed by a client."),
(7124, 6306, NULL, NULL, 1,1, "ossec: A lease was released by a client."),
(7124, 6307, NULL, NULL, 1,1, "ossec: An IP address was found to be in use on the network."),
(7124, 6308, NULL, NULL, 1,1, "ossec: A lease request could not be satisfied because the scope's address pool was exhausted."),
(7124, 6309, NULL, NULL, 1,1, "ossec: A lease was denied."),
(7124, 6310, NULL, NULL, 1,1, "ossec: A lease was deleted."),
(7124, 6311, NULL, NULL, 1,1, "ossec: A lease was expired and DNS records for an expired leases have not been deleted."),
(7124, 6312, NULL, NULL, 1,1, "ossec: A BOOTP address was leased to a client."),
(7124, 6313, NULL, NULL, 1,1, "ossec: A dynamic BOOTP address was leased to a client."),
(7124, 6314, NULL, NULL, 1,1, "ossec: A BOOTP request could not be satisfied because the scope's  address pool for BOOTP was exhausted."),
(7124, 6315, NULL, NULL, 1,1, "ossec: A BOOTP IP address was deleted after checking to see it was not in use."),
(7124, 6323, NULL, NULL, 1,1, "ossec: Packet dropped due to NAP policy."),
(7125, 18201, NULL, NULL, 1,1, "ossec: Group Account Deleted"),
(7125, 18205, NULL, NULL, 1,1, "ossec: Security Enabled Global Group Deleted"),
(7125, 18209, NULL, NULL, 1,1, "ossec: Security Enabled Local Group Deleted"),
(7125, 18216, NULL, NULL, 1,1, "ossec: Security Enabled Universal Group Deleted"),
(8000, 51000, NULL, NULL, 1,1, "ossec: Grouping for dropbear rules."),
(8000, 51001, NULL, NULL, 1,1, "ossec: Failed to get key exchange value"),
(8000, 51002, NULL, NULL, 1,1, "ossec: Premature kexdh_init message"),
(8000, 51005, NULL, NULL, 1,1, "ossec: User disconnected."),
(8001, 600, NULL, NULL, 1,1, "ossec: Active Response Messages Grouped"),
(8001, 601, NULL, NULL, 1,1, "ossec: Host Blocked by firewall-drop.sh Active Response"),
(8001, 602, NULL, NULL, 1,1, "ossec: Host Unblocked by firewall-drop.sh Active Response"),
(8001, 603, NULL, NULL, 1,1, "ossec: Host Blocked by host-deny.sh Active Response"),
(8001, 604, NULL, NULL, 1,1, "ossec: Host Unblocked by host-deny.sh Active Response"),
(8001, 605, NULL, NULL, 1,1, "ossec: Host Blocked by route-null.sh Active Response"),
(8001, 606, NULL, NULL, 1,1, "ossec: Host Unblocked by route-null.sh Active Response"),
(8002, 52000, NULL, NULL, 1,1, "ossec: Grouping for all bro-ids events."),
(8002, 52001, NULL, NULL, 1,1, "ossec: Bro-ids has been started."),
(8002, 52002, NULL, NULL, 1,1, "ossec: Bro-ids has been stopped."),
(8002, 52003, NULL, NULL, 1,1, "ossec: XXX Ack Above Hole"),
(8002, 52004, NULL, NULL, 1,1, "ossec: XXX Content Gap"),
(8002, 52005, NULL, NULL, 1,1, "ossec: Bro-ids resource summary."),
(8002, 52006, NULL, NULL, 1,1, "ossec: Bro-ids port scan summary."),
(8002, 52007, NULL, NULL, 1,1, "ossec: Bro-ids Zone Transfer alert."),
(8002, 52008, NULL, NULL, 1,1, "ossec: Bro-ids detected acces to the portmapper port."),
(8002, 52009, NULL, NULL, 1,1, "ossec: Bro-ids detected a portscan."),
(8003, 52500, NULL, NULL, 1,1, "ossec: Grouping of the clamd rules."),
(8003, 52501, NULL, NULL, 1,1, "ossec: ClamAV database update"),
(8003, 52509, NULL, NULL, 1,1, "ossec: Could not download the incremental virus definition updates."),
(8004, 51521, NULL, NULL, 1,1, "ossec: Grouping for groupdel rules."),
(8004, 51522, NULL, NULL, 1,1, "ossec: Group deleted."),
(8005, 51500, NULL, NULL, 1,1, "ossec: Grouping of bsd_kernel alerts"),
(8005, 51501, NULL, NULL, 1,1, "ossec: A timeout occurred waiting for a transfer."),
(8005, 51502, NULL, NULL, 1,1, "ossec: Check media in optical drive."),
(8005, 51503, NULL, NULL, 1,1, "ossec: A disk has timed out."),
(8005, 51504, NULL, NULL, 1,1, "ossec: arp info has been overwritten for a host"),
(8005, 51505, NULL, NULL, 1,1, "ossec: A filesystem was not properly unmounted, likely system crash"),
(8005, 51506, NULL, NULL, 1,1, "ossec: UKC was used, possibly modifying a kernel at boot time."),
(8005, 51507, NULL, NULL, 1,1, "ossec: Michael MIC failure: Checksum failure in the tkip protocol."),
(8005, 51508, NULL, NULL, 1,1, "ossec: A soft error has been corrected on a hard drive, this is a possible early sign of failure."),
(8005, 51509, NULL, NULL, 1,1, "ossec: Unknown acpithinkpad event"),
(8005, 51510, NULL, NULL, 1,1, "ossec: System shutdown due to temperature"),
(8005, 51511, NULL, NULL, 1,1, "ossec: Unknown ACPI event (bug 6299 in OpenBSD bug tracking system)."),
(8005, 51512, NULL, NULL, 1,1, "ossec: USB diagnostic message."),
(8005, 51513, NULL, NULL, 1,1, "ossec: Possible APM or ACPI event."),
(8005, 51514, NULL, NULL, 1,1, "ossec: Unclean filesystem, run fsck."),
(8005, 51515, NULL, NULL, 1,1, "ossec: Timeout in atascsi_passthru_done."),
(8005, 51516, NULL, NULL, 1,1, "ossec: Clock battery error 80"),
(8005, 51518, NULL, NULL, 1,1, "ossec: I/O error on a storage device"),
(8005, 51519, NULL, NULL, 1,1, "ossec: kbc error."),
(8005, 51520, NULL, NULL, 1,1, "ossec: USB reset failed, IOERROR."),
(8005, 51523, NULL, NULL, 1,1, "ossec: No core dumps."),
(8005, 51524, NULL, NULL, 1,1, "ossec: System was rebooted."),
(8005, 51525, NULL, NULL, 1,1, "ossec: ftp-proxy cannot connect to a server."),
(8005, 51526, NULL, NULL, 1,1, "ossec: Hard drive is dying."),
(8005, 51527, NULL, NULL, 1,1, "ossec: CARP master to backup."),
(8005, 51528, NULL, NULL, 1,1, "ossec: Duplicate IPv6 address."),
(8005, 51529, NULL, NULL, 1,1, "ossec: Could not load a firmware."),
(8005, 51530, NULL, NULL, 1,1, "ossec: hotplugd could not open a file."),
(7999, 1, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 2, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 3, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 4, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 5, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 6, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 7, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 8, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 9, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 10, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 11, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 12, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 13, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 14, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 15, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 16, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 17, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 18, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 19, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 20, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 21, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 22, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 23, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 24, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 25, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 26, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 27, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 28, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 29, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 30, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 31, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 32, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 33, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 34, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 35, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 36, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 37, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 38, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 39, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 40, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 41, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 42, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 43, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 44, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 45, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 46, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 47, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 48, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 49, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 50, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 51, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 52, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 53, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 54, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 55, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 56, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 57, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 58, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 59, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 60, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 61, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 62, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 63, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 64, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 65, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 66, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 67, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 68, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 69, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 70, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 71, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 72, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 73, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 74, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 75, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 76, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 77, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 78, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 79, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 80, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 81, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 82, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 83, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 84, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 85, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 86, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 87, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 88, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 89, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 90, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 91, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 92, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 93, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 94, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 95, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 96, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 97, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 98, NULL, NULL, 1, 2, "ossec: preprocessor"),
(7999, 99, NULL, NULL, 1, 2, "ossec: preprocessor");

