-- SSHd
-- plugin_id: 4003

DELETE FROM plugin WHERE id = "4003";
DELETE FROM plugin_sid where plugin_id = "4003";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (4003, 1, 'sshd', 'SSHd: Secure Shell daemon');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 1, NULL, NULL, 'SSHd: Failed password', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 2, NULL, NULL, 'SSHd: Failed publickey', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 3, NULL, NULL, 'SSHd: Invalid user', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 4, NULL, NULL, 'SSHd: Illegal user', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 5, NULL, NULL, 'SSHd: Root login refused', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 6, NULL, NULL, 'SSHd: User not allowed because listed in DenyUsers', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 7, NULL, NULL, 'SSHd: Login sucessful, Accepted password', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 8, NULL, NULL, 'SSHd: Login sucessful, Accepted publickey', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 9, NULL, NULL, 'SSHd: Bad protocol version identification', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 10, NULL, NULL, 'SSHd: Did not receive identification string', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 11, NULL, NULL, 'SSHd: Received disconnect', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 12, NULL, NULL, 'SSHd: Authentication refused: bad ownership or modes', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 13, NULL, NULL, 'SSHd: User not allowed becase account is locked', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 14, NULL, NULL, 'SSHd: PAM X more authentication failures', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 15, NULL, NULL, 'SSHd: Reverse mapped failed', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 16, NULL, NULL, 'SSHd: Address not mapped', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 17, NULL, NULL, 'SSHd: Server listening', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 18, NULL, NULL, 'SSHd: Server terminated', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 19, NULL, NULL, 'SSHd: Refused connect', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 20, NULL, NULL, 'SSHd: Denied connection', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 21, NULL, NULL, 'SSHd: Could not get shadow information', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 22, NULL, NULL, 'SSHd: HPUX Recieved connection - Version', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 23, NULL, NULL, 'SSHd: HPUX Recieved connection - Throughput', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 24, NULL, NULL, 'SSHd: PAM: authentication failure', 3, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 25, NULL, NULL, 'SSHd: PAM: Session Opened', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 26, NULL, NULL, 'SSHd: PAM: Session Closed', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 27, NULL, NULL, 'SSHd: Connection closed', 1, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 96, NULL, NULL, 'SSHd: Input userauth request invalid user', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 97, NULL, NULL, 'SSHd: Error retrieving info', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 98, NULL, NULL, 'SSHd: Generic PAM SSH Event', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 99, NULL, NULL, 'SSHd: Generic SSH Event', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 100, NULL, NULL, 'SSHd: Protocol major versions differ', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003,101, NULL, NULL, 'SSHd: AIX Could not wait for child', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003,102, NULL, NULL, 'SSHd: AIX subsystem request for sftp', 1 , 1);
