-- proxim-orinoco
-- plugin_id: 1682

DELETE FROM plugin WHERE id = "1682";
DELETE FROM plugin_sid where plugin_id = "1682";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1682, 1, 'proxim-orinoco', 'Proxim ORiNOCO');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 1, 'proxim-orinoco: allowed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 2, 'proxim-orinoco: denied');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 3, 'proxim-orinoco: invalid auth');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 4, 'proxim-orinoco: STA not authenticated');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 5, 'proxim-orinoco: class2 frame from non-auth STA');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 6, 'proxim-orinoco: class3 frame from non-associated STA');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 7, 'proxim-orinoco: WPA module de-auth');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 101, 'proxim-orinoco: SibEntryAge');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 102, 'proxim-orinoco: deleting STA');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 103, 'proxim-orinoco: STA not authenticated');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 104, 'proxim-orinoco: frame from non expected STA');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 105, 'proxim-orinoco: WPA EAPOL-Key tries exhaustedsending disassoc');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 105, 'proxim-orinoco: WPA sending disassoc');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 106, 'proxim-orinoco: WPA KEY ERROR transitionsending disassoc');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 108, 'proxim-orinoco: received data frame');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name) VALUES (1682, 109, 'proxim-orinoco: STA already associated');
