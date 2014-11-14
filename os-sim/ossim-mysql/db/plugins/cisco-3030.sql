-- cisco-3030-vpn
-- plugin_id: 1657

DELETE FROM plugin WHERE id = 1657;
DELETE FROM plugin_sid WHERE plugin_id = 1657;

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1657, 1, 'cisco-3030', 'Cisco VPN concentrator');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 5, NULL, NULL, 'Cisco VPN 3030: Authentication rejected');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 22, NULL, NULL, 'Cisco VPN 3030: IPSec/LAN-to-LAN - connected');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 23, NULL, NULL, 'Cisco VPN 3030: User disconnected');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 31, NULL, NULL, 'Cisco VPN 3030: Authentication failure on http logon');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 36, NULL, NULL, 'Cisco VPN 3030: Access granted on snmp logon');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 83, NULL, NULL, 'Cisco VPN 3030: User connected');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 84, NULL, NULL, 'Cisco VPN 3030: LAN-to-LAN tunnel connected');


INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 1006, NULL, NULL, 'Cisco VPN 3030: PPP User disconnected');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 1008, NULL, NULL, 'Cisco VPN 3030: PPP User Authenticated successfully with MSCHAP-V1');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 1009, NULL, NULL, 'Cisco VPN 3030: PPP User disconnected.. failed authentication ( MSCHAP-V1 )');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 1012, NULL, NULL, 'Cisco VPN 3030: PPP User disconnected. Authentication protocol not allowed');


INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 2034, NULL, NULL, 'Cisco VPN 3030: Tunnel to peer closed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 2035, NULL, NULL, 'Cisco VPN 3030: Session closed on tunnel');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 2042, NULL, NULL, 'Cisco VPN 3030: Session started on tunnel');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 2047, NULL, NULL, 'Cisco VPN 3030: Tunnel to peer established');


INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3000, NULL, NULL, 'Cisco VPN 3030: Received unexpected event EV_ACTIVATE_NEW_SA in state MM_ACTIVE');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3034, NULL, NULL, 'Cisco VPN 3030: Received local IP Proxy Subnet data in ID Payload');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3035, NULL, NULL, 'Cisco VPN 3030: Received remote IP Proxy Subnet data in ID Payload');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3041, NULL, NULL, 'Cisco VPN 3030: IKE Initiator: New Phase 1');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3049, NULL, NULL, 'Cisco VPN 3030: Security negotiation complete for LAN-to-LAN');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3066, NULL, NULL, 'Cisco VPN 3030: IKE Remote Peer configured for SA');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3092, NULL, NULL, 'Cisco VPN 3030: Failure during phase 1 rekeying attempt due to collision');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3119, NULL, NULL, 'Cisco VPN 3030: PHASE 1 COMPLETED');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3120, NULL, NULL, 'Cisco VPN 3030: PHASE 2 COMPLETED');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3121, NULL, NULL, 'Cisco VPN 3030: Keep-alive type for this connection');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 3136, NULL, NULL, 'Cisco VPN 3030: IKE session establishment timed out');


INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 4007, NULL, NULL, 'Cisco VPN 3030: HTTP 401 Unauthorized');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 4047, NULL, NULL, 'Cisco VPN 3030: HTTP administrator login');


INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1657, 9999, NULL, NULL, 'Cisco VPN 3030: Generic event');
