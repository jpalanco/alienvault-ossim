-- plugin_id:  1691
-- type: ;; type: detector
-- description: 

DELETE FROM plugin where id = 1691;
DELETE FROM plugin_sid where plugin_id = 1691;

INSERT IGNORE INTO plugin(id, type, name, description) VALUES
  (1691, 1, 'Watchguard', 'Watchguard Firebox');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability)
  VALUES
    (1691, 1, 'Watchguard: Allowed packet', 2,2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability)
  VALUES
    (1691, 2, 'Watchguard: Denied packet', 2,2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability)
  VALUES
    (1691, 10, 'Watchguard: dvcpcd message', 2,2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability)
  VALUES
    (1691, 20, 'Watchguard: Signatures up to date', 2,2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, name, priority, reliability)
  VALUES
    (1691, 9999999, 'Watchguard: Generic message', 2,2);
