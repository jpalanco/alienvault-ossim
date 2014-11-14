-- sendmail
-- plugin_id: 1680;

DELETE FROM plugin WHERE id = "1680";
DELETE FROM plugin_sid where plugin_id = "1680";

INSERT INTO plugin (id, type, name, description) VALUES (1680, 1, 'sendmail', 'Sendmail');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 1000, 1, 5, 'sendmail: reply to');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 2000, 1, 5, 'sendmail: reply from');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 3000, 1, 5, 'sendmail: STARTTLS=client');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 4000, 1, 5, 'sendmail: AUTH=server');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 5000, 1, 5, 'sendmail: ruleset=check_rcpt');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 5100, 1, 5, 'sendmail: ruleset=check_mail');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 5200, 1, 5, 'sendmail: ruleset=check_relay');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 6000, 1, 5, 'sendmail: AUTH=server, relay=[SRC IP]');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7000, 1, 5, 'sendmail: did not issue MAIL/EXPN/VRFY/ETRN to MTA');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7100, 1, 5, 'sendmail: <undisclosed-recipients:>... User unknown');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7200, 1, 5, 'sendmail: lost input channel from [SRC IP] after (mail|rcpt)');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7300, 1, 5, 'sendmail: <Email>... User unknown');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7400, 1, 5, 'sendmail: DSN: Service unavailable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7500, 1, 5, 'sendmail: collect: premature EOM: unexpected close');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7510, 1, 5, 'sendmail: collect: unexpected close on connection from [SRC IP], sender=<email>');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7520, 1, 5, 'sendmail: collect: collect: I/O error on connection from [SRC IP], from=<email>');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7530, 1, 5, 'sendmail: collect: premature EOM: Connection timed out with [SRC IP]');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7535, 1, 5, 'sendmail: collect: premature EOM: Connection timed reset by [SRC IP]');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7536, 1, 5, 'sendmail: collect: premature EOM: Connection timed reset by mail');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7540, 1, 5, 'sendmail: Syntax error in mailbox address');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7550, 1, 5, 'sendmail: lost input channel from email [SRC_IP]');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7560, 1, 5, 'sendmail: collect: unexpected close on connection from email, sender=<email>');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7570, 1, 5, 'sendmail: unbalanced');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7580, 1, 5, 'sendmail: rejecting connections on daemon MTA: load average');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7585, 1, 5, 'sendmail: accepting connections again for daemon MTA');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7590, 1, 5, 'sendmail: Dropped invalid comments from header address');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7600, 1, 5, 'sendmail: discarded');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 7700, 1, 5, 'sendmail: sender notify: Warning');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1680, 9999, 1, 5, 'sendmail: Generic event');
