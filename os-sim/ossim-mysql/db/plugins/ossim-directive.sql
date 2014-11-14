-- ossim directives
-- plugin_id: 1505
--
-- This is a special plugin. The plugin_sid's will be inserted in the DB by the ossim server. When the ossim server starts, it reads the /etc/ossim/server/directives.xml and insert one plugin_sid for each directive. So the plugin_sids are the directive identificators.
--

-- DELETE FROM plugin WHERE id = "1505";
-- DELETE FROM plugin_sid where plugin_id = "1505"; Not needed here, the ossim server will do this each restart

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1505, 1, 'directive_alert', 'OSSIM Directives Alerts');



