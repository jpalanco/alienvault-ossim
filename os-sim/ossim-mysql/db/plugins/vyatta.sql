-- vyatta
-- plugin_id: 1610
-- vyatta.cfg, v 0.14 2011/02/22 hnoguera@openredes.com - http://www.openredes.com

DELETE FROM plugin WHERE id = "1610";
DELETE FROM plugin_sid where plugin_id = "1610";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES(1610, 1, 'vyatta', 'Vyatta events');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 10, null, null, 1, 1, "vyatta: firewall: Accept");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 20, null, null, 1, 1, "vyatta: firewall: Drop");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 30, null, null, 1, 1, "vyatta: firewall: Reject");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 40, null, null, 1, 1, "vyatta: openvpn: sts connection ok");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 50, null, null, 1, 1, "vyatta: openvpn: ra connection ok");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 60, null, null, 1, 1, "vyatta: openvpn: TLS key expired");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 70, null, null, 1, 1, "vyatta: openvpn: Inactivity timeout (--ping-restart)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 80, null, null, 1, 1, "vyatta: openvpn: [EHOSTUNREACH]: No route to host");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 90, null, null, 1, 1, "vyatta: openvpn: RA client disconnected");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 100, null, null, 1, 1, "vyatta: ospfd: Adjacency change (HelloReceived)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 110, null, null, 1, 1, "vyatta: ospfd: Adjacency change (Start)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 120, null, null, 1, 1, "vyatta: ospfd: Adjacency change (2-WayReceived)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 130, null, null, 1, 1, "vyatta: ospfd: Adjacency change (1-WayReceived)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 140, null, null, 1, 1, "vyatta: ospfd: Adjacency change (NegotiationDone)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 150, null, null, 1, 1, "vyatta: ospfd: Adjacency change (ExchangeDone)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 160, null, null, 1, 1, "vyatta: ospfd: Adjacency change (BadLSReq)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 170, null, null, 1, 1, "vyatta: ospfd: Adjacency change (Loading Done)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 180, null, null, 1, 1, "vyatta: ospfd: Adjacency change (AdjOK?)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 190, null, null, 1, 1, "vyatta: ospfd: Adjacency change (SeqNumberMismatch)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 200, null, null, 1, 1, "vyatta: ospfd: Adjacency change (1-Way)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 210, null, null, 1, 1, "vyatta: ospfd: Adjacency change (KillNbr)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 220, null, null, 1, 1, "vyatta: ospfd: Adjacency change (InactivityTimer)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 230, null, null, 1, 1, "vyatta: ospfd: Adjacency change (LLDown)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 240, null, null, 1, 1, "vyatta: ospfd: NSM change (now Deleted)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 250, null, null, 1, 1, "vyatta: ospfd: NSM change (now Init)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 260, null, null, 1, 1, "vyatta: ospfd: NSM change (now ExStart)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 270, null, null, 1, 1, "vyatta: ospfd: NSM change (now 2-Way)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 280, null, null, 1, 1, "vyatta: ospfd: NSM change (now Exchange)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 290, null, null, 1, 1, "vyatta: ospfd: NSM change (now Loading)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 300, null, null, 1, 1, "vyatta: ospfd: NSM change (now Full)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 310, null, null, 1, 1, "vyatta: zebra: interface changes");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 320, null, null, 1, 1, "vyatta: zebra: interface deleted");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 330, null, null, 1, 1, "vyatta: zebra: interface added");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 340, null, null, 1, 1, "vyatta: system: new config loaded");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 350, null, null, 1, 1, "vyatta: system: shutdown system");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 360, null, null, 1, 1, "vyatta: wan_lb: interface state change (now FAILED)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 370, null, null, 1, 1, "vyatta: wan_lb: interface state change (now ACTIVE)");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 380, null, null, 1, 1, "vyatta: pmacctd: memory resources warning");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 390, null, null, 1, 1, "vyatta: pam_unix: auth failure");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 400, null, null, 1, 1, "vyatta: pam_unix: more than # auth failures");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 410, null, null, 1, 1, "vyatta: pam_unix: max retries");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 420, null, null, 1, 1, "vyatta: pam_unix: unknown user");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 430, null, null, 1, 1, "vyatta: pam_unix: auth failure");
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1610, 440, null, null, 1, 1, "vyatta: pam_unix: too many login tries");
