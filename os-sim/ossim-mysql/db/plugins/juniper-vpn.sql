DELETE FROM plugin WHERE id = "1609";
DELETE FROM plugin_sid where plugin_id = "1609";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1609, 1, 'Juniper-VPN', 'Juniper VPN SSL');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 1, NULL, NULL, 'Juniper-VPN: WebRequest ok' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 2, NULL, NULL, 'Juniper-VPN: WebRequest completed' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 3, NULL, NULL, 'Juniper-VPN: Login Succeeded' ,2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 4, NULL, NULL, 'Juniper-VPN: Policy Check Passed' ,1, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 5, NULL, NULL, 'Juniper-VPN: Policy Check Failed' ,2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 6, NULL, NULL, 'Juniper-VPN: Session Logout' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 7, NULL, NULL, 'Juniper-VPN: Downloaded File' ,2, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 8, NULL, NULL, 'Juniper-VPN: Access denied to Windows directory' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 9, NULL, NULL, 'Juniper-VPN: Login Failed' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 10, NULL, NULL, 'Juniper-VPN: Authentication successful' ,2, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 11, NULL, NULL, 'Juniper-VPN: Session switch' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 12, NULL, NULL, 'Juniper-VPN: RDP Session opened' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 13, NULL, NULL, 'Juniper-VPN: RDP Session closed' ,1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 14, NULL, NULL, 'Juniper-VPN: Authentication failed' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 15, NULL, NULL, 'Juniper-VPN: Account Lockout' ,4, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 16, NULL, NULL, 'Juniper-VPN: Write Error' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 17, NULL, NULL, 'Juniper-VPN: Read Error' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 18, NULL, NULL, 'Juniper-VPN: Password Real Restriction Failed' ,3, 3);

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 19, NULL, NULL, 'Juniper-VPN: Agent Login Succeeded' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 20, NULL, NULL, 'Juniper-VPN: RADIUS Authentication Accepted' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 21, NULL, NULL, 'Juniper-VPN: RADIUS Invalid Message' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 22, NULL, NULL, 'Juniper-VPN: RADIUS User Assigned Attributes' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 23, NULL, NULL, 'Juniper-VPN: Certificate Restrictions Successfully Passed' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 24, NULL, NULL, 'Juniper-VPN: RADIUS Authentication Rejected' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 25, NULL, NULL, 'Juniper-VPN: Primary Authentication Successful' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 26, NULL, NULL, 'Juniper-VPN: Login Failed' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 27, NULL, NULL, 'Juniper-VPN: Session Timeout' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 28, NULL, NULL, 'Juniper-VPN: RADIUS Statistics' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 29, NULL, NULL, 'Juniper-VPN: RADIUS Accounting' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 30, NULL, NULL, 'Juniper-VPN: MAC Login Succeeded' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 31, NULL, NULL, 'Juniper-VPN: RADIUS Authentication Accepted' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 32, NULL, NULL, 'Juniper-VPN: Max Session Timeout' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 33, NULL, NULL, 'Juniper-VPN: Internal Error' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 34, NULL, NULL, 'Juniper-VPN: Authentication Failure' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 35, NULL, NULL, 'Juniper-VPN: Session Extended' ,3, 3);

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 101, NULL, NULL, 'Juniper-VPN: SSL Negotiation Failed at source SRC_IP to DST_IP' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 102, NULL, NULL, 'Juniper-VPN: User USERDATA3 added to authentication server USERDATA4' ,3, 4);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 103, NULL, NULL, 'Juniper-VPN: User USERDATA3 removed to authentication server USERDATA4' ,3, 4);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 104, NULL, NULL, 'Juniper-VPN: Connection to USERDATA3 not authenticated yet' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 105, NULL, NULL, 'Juniper-VPN: Statistics Archive' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 106, NULL, NULL, 'Juniper-VPN: Counting event' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 107, NULL, NULL, 'Juniper-VPN: Clearing log event' ,3, 4);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 108, NULL, NULL, 'Juniper-VPN: External Auth Server Unreachable' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 109, NULL, NULL, 'Juniper-VPN: Downloaded identical CRL' ,2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 110, NULL, NULL, 'Juniper-VPN: Downloaded new  CRL' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 111, NULL, NULL, 'Juniper-VPN: NTP event' ,3, 3);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 112, NULL, NULL, 'Juniper-VPN: Archive USERDATA4 logs in USERDATA3' ,2, 2);

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 999, NULL, NULL, 'Juniper-VPN: Generic Message' ,1, 1);
