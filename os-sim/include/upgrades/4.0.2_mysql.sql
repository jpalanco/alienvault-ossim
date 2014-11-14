USE alienvault;
SET AUTOCOMMIT=0;
BEGIN;

DELETE FROM `acl_perm` WHERE `id` = 34 AND `value` = "ConfigurationMain";

UPDATE port_group SET id=0 WHERE name='ANY';
SELECT hex(ctx) FROM port_group WHERE id=0 into @ctx;
REPLACE INTO port_group_reference (port_group_id, port_ctx, port_number, protocol_name) VALUES (0, UNHEX(@ctx), 0, 'icmp'), (0, UNHEX(@ctx), 0, 'tcp'), (0, UNHEX(@ctx), 0, 'udp');

ALTER TABLE  `wireless_aps` CHANGE  `sensor`  `sensor` VARBINARY( 16 ) NOT NULL, CHANGE  `mac`  `mac` VARCHAR( 18 ) NOT NULL;
ALTER TABLE  `wireless_networks` CHANGE  `sensor`  `sensor` VARBINARY( 16 ) NOT NULL;
ALTER TABLE  `wireless_clients` CHANGE  `sensor`  `sensor` VARBINARY( 16 ) NOT NULL, CHANGE  `client_mac`  `client_mac` VARCHAR( 18 ) NOT NULL, CHANGE  `mac`  `mac` VARCHAR( 18 ) NOT NULL;

TRUNCATE TABLE `inventory_search`;
INSERT INTO `inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES
('Alarms', 'Has Alarm', 'boolean', '', 'SELECT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm UNION SELECT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('Alarms', 'Has closed Alarms', 'boolean', '', 'SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE status=''closed'' UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE status=''closed''', 999),
('Alarms', 'Has no Alarm', 'boolean', '', 'SELECT DISTINCT HEX(ip) AS ip, HEX(ctx) AS ctx FROM (select i.ip AS ip, h.ctx AS ctx from host h, host_ip i WHERE h.id=i.host_id UNION SELECT DISTINCT ip_src as ip, ctx FROM alienvault_siem.acid_event) AS todas WHERE CONCAT(ip,'','',ctx) NOT IN (SELECT DISTINCT CONCAT(src_ip,'','',corr_engine_ctx) FROM alarm) AND CONCAT(ip,'','',ctx) NOT IN (SELECT DISTINCT CONCAT(dst_ip,'','',corr_engine_ctx) FROM alarm)', 999),
('Alarms', 'Has open Alarms', 'boolean', '', 'SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE status=''open'' UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE status=''open''', 999),
('Alarms', 'IP is Dst', 'boolean', '', 'SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('Alarms', 'IP is Src', 'boolean', '', 'SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('Asset', 'Asset is', 'fixed', 'SELECT DISTINCT asset FROM host ORDER BY asset', 'SELECT DISTINCT HEX(h.id) AS id, HEX(h.ctx) AS ctx FROM host h WHERE asset = ? UNION SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_src = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_dst = ?', 999),
('Asset', 'Asset is greater than', 'number', '', 'SELECT DISTINCT HEX(i.ip) AS ip, HEX(h.ctx) AS ctx FROM host h, host_ip i WHERE h.id = i.host_id AND h.asset > ? UNION SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_src > ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_dst > ?', 999),
('Asset', 'Asset is local', 'fixed', 'SELECT DISTINCT asset FROM host ORDER BY asset', 'SELECT DISTINCT HEX(id) AS id, HEX(ctx) AS ctx FROM host WHERE asset = ?', 999),
('Asset', 'Asset is lower than', 'number', '', 'SELECT DISTINCT HEX(i.ip) AS ip, HEX(h.ctx) AS ctx FROM host h, host_ip i WHERE h.id = i.host_id AND h.asset < ? AND h.asset > 0 UNION SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_src < ? AND ossim_asset_src > 0 UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ossim_asset_dst < ? AND ossim_asset_dst > 0', 999),
('Asset', 'Asset is remote', 'number', '', 'SELECT HEX(ip) AS ip, HEX(ctx) AS ctx FROM (SELECT DISTINCT ip_src as ip, ctx FROM alienvault_siem.acid_event WHERE ossim_asset_src = ? UNION SELECT DISTINCT ip_dst as ip, ctx FROM alienvault_siem.acid_event WHERE ossim_asset_dst = ?) remote WHERE CONCAT(ip,'','',ctx) NOT IN (SELECT DISTINCT CONCAT(i.ip,'','',h.ctx) FROM host h, host_ip i WHERE h.id=i.host_id)', 999),
('Mac', 'Has Mac', 'boolean', '', 'SELECT DISTINCT HEX(host_ip.ip) AS ip, HEX(host.ctx) AS ctx FROM host, host_ip WHERE host.id = host_ip.host_id AND host_ip.mac IS NOT NULL', 3),
('Mac', 'Has No Mac', 'boolean', '', 'SELECT DISTINCT HEX(host_ip.ip) AS ip, HEX(host.ctx) AS ctx FROM host, host_ip WHERE host.id = host_ip.host_id AND host_ip.mac IS NULL', 3),
('META', 'Date After', 'date', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE timestamp > ? UNION SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE timestamp > ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE timestamp > ? UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE timestamp > ?', 999),
('META', 'Date Before', 'date', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE timestamp < ? UNION SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE timestamp < ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE timestamp < ? UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE timestamp < ?', 999),
('META', 'Destination Port', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_dport = ? AND ip_proto = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_dport = ? AND ip_proto = ?', 999),
('META', 'Has Dst IP', 'boolean', '', 'SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('META', 'Has Src IP', 'boolean', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('META', 'Has Src or Dst IP', 'boolean', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm', 999),
('META', 'IP as Dst', 'ip', 'SELECT DISTINCT INET_NTOA(conv(HEX(ip), 16, 10)) as value FROM host_ip', 'SELECT DISTINCT HEX(ip_src) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_dst), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(alarm.src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE INET_NTOA(conv(HEX(alarm.dst_ip), 16, 10)) %op% ?', 999),
('META', 'IP as Src', 'ip', 'SELECT DISTINCT INET_NTOA(conv(HEX(ip), 16, 10)) as value FROM host_ip', 'SELECT DISTINCT HEX(ip_dst) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_src), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(alarm.dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE INET_NTOA(conv(HEX(alarm.src_ip), 16, 10)) %op% ?', 999),
('META', 'IP as Src or Dst', 'ip', 'SELECT DISTINCT INET_NTOA(conv(HEX(ip), 16, 10)) as value FROM host_ip', 'SELECT DISTINCT HEX(ip_dst) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_src), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(dst_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE INET_NTOA(conv(HEX(alarm.src_ip), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(ip_src) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_dst), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(src_ip) as ip, HEX(corr_engine_ctx) AS ctx FROM alarm WHERE INET_NTOA(conv(HEX(dst_ip), 16, 10)) %op% ?', 999),
('META', 'Port as Src or Dst', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_dport = ? AND ip_proto = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_dport = ? AND ip_proto = ?', 999),
('META', 'Source Port', 'concat', 'SELECT CONCAT(p1.port_number,"-",p2.id) as port_value,CONCAT(p1.port_number,"-",p1.protocol_name) as port_text FROM port p1, protocol p2 WHERE p1.protocol_name=p2.name', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_sport = ? AND ip_proto = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE layer4_sport = ? AND ip_proto = ?', 999),
('OS', 'Has Anomaly', 'boolean', '', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host_properties, host WHERE host_properties.host_id = host.id AND host_properties.property_ref = 3 AND host_properties.value %op% ? AND host_properties.source_id != 1 AND host_properties.source_id != 2', 1),
('OS', 'Has no Anomaly', 'boolean', '', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host_properties, host WHERE host_properties.host_id = host.id AND host_properties.property_ref = 3 AND host_properties.value %op% ? AND (host_properties.source_id = 1 OR host_properties.source_id = 2)', 1),
('OS', 'OS is', 'text', 'SELECT DISTINCT value FROM host_properties WHERE property_ref = 3 ORDER BY value', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host_properties, host WHERE host_properties.host_id = host.id AND host_properties.property_ref = 3 AND host_properties.value %op% ?', 1),
('OS', 'OS is Not', 'text', 'SELECT DISTINCT value FROM host_properties WHERE property_ref = 3 ORDER BY value', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host WHERE id NOT IN (SELECT host.id FROM host_properties, host WHERE host_properties.host_id = host.id AND host_properties.property_ref = 3 AND host_properties.value %op% ?)', 1),
('Property', 'Contains', 'fixedText', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT HEX(p.host_id) AS id, HEX(h.ctx) AS ctx FROM host h, host_properties p WHERE h.id = p.host_id AND p.property_ref = ? AND (p.value LIKE ''%$value2%'' OR p.extra LIKE ''%$value2%'')', 999),
('Property', 'Has not Property', 'fixed', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT HEX(p.host_id) AS id, HEX(h.ctx) AS ctx FROM host h, host_properties p WHERE h.id = p.host_id AND p.property_ref != ?', 999),
('Property', 'Has Property', 'fixed', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT HEX(p.host_id) AS id, HEX(h.ctx) AS ctx FROM host h, host_properties p WHERE h.id = p.host_id AND p.property_ref = ?', 999),
('Services', 'Doesnt have service', 'fixed', 'SELECT DISTINCT service as service_value, service as service_text FROM host_services', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host WHERE id NOT IN (SELECT host.id FROM host, host_services WHERE host.id = host_services.host_id AND service=?)', 2),
('Services', 'Has Anomaly', 'boolean', '', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host, host_services WHERE host.id = host_services.host_id AND service=? AND source_id != 1 AND source_id != 2', 2),
('Services', 'Has no Anomaly', 'boolean', '', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host, host_services WHERE host.id = host_services.host_id AND service=? AND (source_id = 1 OR source_id = 2)', 2),
('Services', 'Has services', 'fixed', 'SELECT DISTINCT service as service_value, service as service_text FROM host_services', 'SELECT DISTINCT HEX(host.id) as id, HEX(host.ctx) AS ctx FROM host, host_services WHERE host.id = host_services.host_id AND service=?', 2),
('SIEM Events', 'Has Different', 'number', '', 'SELECT ip, ctx FROM (SELECT count(distinct plugin_id, plugin_sid) AS total, ip, ctx FROM (select plugin_id, plugin_sid, HEX(ip_src) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION select plugin_id, plugin_sid, HEX(ip_dst) AS ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event) AS t group by ip, ctx) AS t1 WHERE t1.total >= ?', 5),
('SIEM Events', 'Has Dst IP', 'ip', 'SELECT DISTINCT INET_NTOA(conv(HEX(ip), 16, 10)) as value FROM host_ip', 'SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_dst), 16, 10)) %op% ?', 5),
('SIEM Events', 'Has Dst Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto = ? AND layer4_dport = ?', 5),
('SIEM Events', 'Has Event', 'boolean', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event', 5),
('SIEM Events', 'Has Events', 'text', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event s, plugin_sid p WHERE s.plugin_id=p.plugin_id AND s.plugin_sid=p.sid AND p.name %op% ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event s, plugin_sid p WHERE s.plugin_id=p.plugin_id AND s.plugin_sid=p.sid AND p.name %op% ?', 5),
('SIEM Events', 'Has IP', 'ip', 'SELECT DISTINCT inet_ntoa(conv(HEX(ip), 16, 10)) AS ip FROM host_ip', 'SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE inet_ntoa(conv(HEX(ip_dst), 16, 10)) %op% ? UNION SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE inet_ntoa(conv(HEX(ip_src), 16, 10)) %op% ?', 5),
('SIEM Events', 'Has no Event', 'boolean', '', 'SELECT DISTINCT HEX(host.id) AS id, HEX(host.ctx) AS ctx FROM host, host_ip WHERE host.id = host_ip.host_id AND CONCAT(host_ip.ip,'','',host.ctx) NOT IN (SELECT DISTINCT CONCAT (ip_src,'','',ctx) FROM alienvault_siem.acid_event WHERE CONCAT (ip_src,'','',ctx) != NULL UNION SELECT DISTINCT CONCAT(ip_dst,'','',ctx) FROM alienvault_siem.acid_event WHERE CONCAT(ip_dst,'','',ctx) != "NULL")', 5),
('SIEM Events', 'Has Plugin Groups', 'fixed', 'SELECT HEX(group_id) AS value, name FROM plugin_group', 'SELECT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE plugin_id in (SELECT plugin_id FROM alienvault.plugin_group WHERE group_id=UNHEX(?)) UNION SELECT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE plugin_id in (SELECT plugin_id FROM alienvault.plugin_group WHERE group_id=UNHEX(?))', 5),
('SIEM Events', 'Has Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto = ? AND layer4_sport = ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto = ? AND layer4_dport = ?', 5),
('SIEM Events', 'Has Protocol', 'fixed', 'SELECT id,alias FROM protocol', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto=? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto=? LIMIT 999', 5),
('SIEM Events', 'Has Src IP', 'ip', 'SELECT DISTINCT INET_NTOA(conv(HEX(ip), 16, 10)) as value FROM host_ip', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE INET_NTOA(conv(HEX(ip_src), 16, 10)) %op% ?', 5),
('SIEM Events', 'Has Src Port', 'concat', 'SELECT DISTINCT CONCAT(p.id,"-",h.port) as protocol_value,CONCAT(h.port,"-",p.name) as protocol_text from host_services h,protocol p where h.protocol=p.id order by h.port', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event WHERE ip_proto = ? AND layer4_sport = ?', 5),
('SIEM Events', 'Has user', 'text', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event, alienvault_siem.extra_data WHERE alienvault_siem.extra_data.event_id = alienvault_siem.acid_event.id AND alienvault_siem.extra_data.username %op% ? UNION SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event, alienvault_siem.extra_data WHERE alienvault_siem.extra_data.event_id = alienvault_siem.acid_event.id AND alienvault_siem.extra_data.username %op% ?', 5),
('SIEM Events', 'IP is Dst', 'boolean', '', 'SELECT DISTINCT HEX(ip_dst) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event LIMIT 999', 5),
('SIEM Events', 'IP is Src', 'boolean', '', 'SELECT DISTINCT HEX(ip_src) as ip, HEX(ctx) AS ctx FROM alienvault_siem.acid_event LIMIT 999', 5),
('Tickets', 'Has no Ticket', 'boolean', '', 'SELECT HEX(i.ip) as ip, HEX(h.ctx) AS ctx FROM host h, host_ip i WHERE h.id = i.host_id AND concat(INET_NTOA(conv(ip, 16, 10)),'','',ctx) NOT IN (SELECT DISTINCT concat(a.src_ips, '','', HEX(i.ctx)) FROM incident i,incident_alarm a WHERE i.id=a.incident_id)', 999),
('Tickets', 'Has Ticket Tag', 'fixed', 'SELECT id as tag_id,name as tag_name FROM incident_tag_descr', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a,incident_tag t WHERE i.id=a.incident_id AND i.id=t.incident_id AND t.tag_id=?', 999),
('Tickets', 'Has Ticket Type', 'fixed', 'SELECT id as type_value,id as type_text FROM incident_type', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.type_id=?', 999),
('Tickets', 'Has Tickets', 'boolean', '', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a WHERE i.id=a.incident_id', 999),
('Tickets', 'Is older Than Days', 'number', '', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND DATEDIFF(CURRENT_TIMESTAMP ,i.last_update) > ?', 999),
('Tickets', 'Priority is greater than', 'number', '', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.priority>?', 999),
('Tickets', 'Priority is lower than', 'number', '', 'SELECT DISTINCT conv(INET_ATON(a.src_ips), 10, 16) as ip, HEX(i.ctx) AS ctx FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.priority<?', 999),
('Vulnerabilities', 'Has CVE', 'text', '', 'SELECT DISTINCT HEX(s.host_ip) AS ip, HEX(s.ctx) AS ctx FROM vuln_nessus_plugins p,host_plugin_sid s WHERE s.plugin_id=3001 and s.plugin_sid=p.id AND p.cve_id %op% ?', 4),
('Vulnerabilities', 'Has no Vulns', 'boolean', '', 'SELECT DISTINCT HEX(host.id) AS id, HEX(host.ctx) AS ctx FROM host, host_ip WHERE host.id = host_ip.host_id AND CONCAT (host_ip.ip, '','', host.ctx) NOT IN (SELECT CONCAT(host_ip, '','', ctx) FROM host_plugin_sid WHERE plugin_id = 3001)', 4),
('Vulnerabilities', 'Has Vuln', 'fixed', 'SELECT sid as plugin_value,name as plugin_text FROM plugin_sid WHERE plugin_id =3001 LIMIT 999', 'SELECT DISTINCT HEX(host_ip) AS ip, HEX(ctx) AS ctx FROM host_plugin_sid WHERE plugin_id = 3001 AND plugin_sid = ?', 4),
('Vulnerabilities', 'Has Vuln Service', 'text', 'SELECT DISTINCT app FROM vuln_nessus_results', 'SELECT DISTINCT conv(INET_ATON(hostIP), 10, 16) as ip FROM vuln_nessus_results WHERE app %op% ?', 999),
('Vulnerabilities', 'Has Vulns', 'boolean', '', 'SELECT DISTINCT HEX(host_ip) AS ip, HEX(ctx) AS ctx FROM host_plugin_sid WHERE plugin_id = 3001', 4),
('Vulnerabilities', 'Vuln Contains', 'text', '', 'SELECT DISTINCT HEX(hp.host_ip) AS ip, HEX(hp.ctx) AS ctx FROM host_plugin_sid hp, plugin_sid p WHERE hp.plugin_id = 3001 AND p.plugin_id = 3001 AND hp.plugin_sid = p.sid AND p.name %op% ? UNION SELECT DISTINCT HEX(s.host_ip) AS ip, HEX(s.ctx) AS ctx FROM vuln_nessus_plugins p,host_plugin_sid s WHERE s.plugin_id=3001 and s.plugin_sid=p.id AND p.name %op% ?', 4),
('Vulnerabilities', 'Vuln Level is greater than', 'number', '', 'SELECT DISTINCT HEX(host.id) AS id, HEX(host.ctx) AS ctx FROM host_vulnerability, host WHERE host_vulnerability.host_id = host.id AND vulnerability > ?', 4),
('Vulnerabilities', 'Vuln Level is lower than', 'number', '', 'SELECT DISTINCT HEX(host.id) AS id, HEX(host.ctx) AS ctx FROM host_vulnerability, host WHERE host_vulnerability.host_id = host.id AND vulnerability < ?', 4),
('Vulnerabilities', 'Vuln risk is greater than', 'number', '', 'SELECT DISTINCT HEX(h.host_ip) AS ip, HEX(h.ctx) AS ctx FROM host_plugin_sid h,vuln_nessus_plugins p WHERE h.plugin_id=3001 AND h.plugin_sid=p.id AND 8-p.risk > ?', 4),
('Vulnerabilities', 'Vuln risk is lower than', 'number', '', 'SELECT DISTINCT HEX(h.host_ip) AS ip, HEX(h.ctx) AS ctx FROM host_plugin_sid h,vuln_nessus_plugins p WHERE h.plugin_id=3001 AND h.plugin_sid=p.id AND 8-p.risk < ?', 4);


DROP TRIGGER IF EXISTS alienvault.auto_incidents;
DROP PROCEDURE IF EXISTS incident_ticket_populate;

DELIMITER $$

CREATE DEFINER=`root`@`localhost` PROCEDURE `incident_ticket_populate`(incident_id INT, src_ip VARBINARY(16), dst_ip VARBINARY(16), prio INT)
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE count INT;
  DECLARE cnt_src, cnt_dst, i INT;
  DECLARE name, subname VARCHAR(255);
  DECLARE first_occ, last_occ TIMESTAMP;
  DECLARE source VARCHAR(39);
  DECLARE dest VARCHAR(39);

  DECLARE cur1 CURSOR FOR select count(*) as cnt,  inet6_ntop(event.src_ip) as src, inet6_ntop(event.dst_ip) as dst, plugin.name, plugin_sid.name, min(timestamp) as frst, max(timestamp) as last, count(distinct(event.src_ip)) as cnt_src, count(distinct(event.dst_ip)) as cnt_dst from event, plugin, plugin_sid where (event.src_ip = src_ip or event.dst_ip = src_ip or event.src_ip = dst_ip or event.dst_ip =dst_ip ) and timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY) AND plugin.id = event.plugin_id and plugin_sid.sid = event.plugin_sid and plugin_sid.plugin_id = event.plugin_id group by event.plugin_id, event.plugin_sid ORDER by cnt DESC limit 50;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  SET i = (SELECT IFNULL(MAX(id), 0) + 1 FROM incident_ticket);

OPEN cur1;

INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (i, incident_id, NOW()-1, "Open", prio, "admin", "The following tickets contain information about the top 50 event types the hosts have been generating during the last 7 days.");

SET i = i + 1;

  REPEAT
    FETCH cur1 INTO count, source, dest, name, subname, first_occ, last_occ, cnt_src, cnt_dst;
    IF NOT done THEN
        SET @desc = CONCAT( "Event Type: ",  name, "\nEvent Description: ", subname, "\nOcurrences: ",CAST(count AS CHAR), "\nFirst Ocurrence: ", CAST(first_occ AS CHAR(50)), "\nLast Ocurrence: ", CAST(last_occ AS CHAR(50)),"\nNumber of different sources: ", CAST(cnt_src AS CHAR), "\nNumber of different destinations: ", CAST(cnt_dst AS CHAR), "\nSource: ", source, "\nDest: ", dest);

        INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (i, incident_id, NOW(), "Open", prio, "admin", @desc);

        SET i = i + 1;

    END IF;

  UNTIL done END REPEAT;

  CLOSE cur1;
END$$


CREATE
DEFINER=`root`@`localhost`
TRIGGER `alienvault`.`auto_incidents`
AFTER INSERT ON `alienvault`.`alarm`
FOR EACH ROW
BEGIN

  IF EXISTS

   (SELECT value FROM config where conf = "alarms_generate_incidents" and value = "yes")

  THEN

    IF NOT EXISTS (SELECT id FROM incident_alarm WHERE backlog_id = NEW.backlog_id)

    THEN
        set @tmp_src_ip = NEW.src_ip;
        set @tmp_dst_ip = NEW.dst_ip;
        set @tmp_risk = NEW.risk;
        set @title = (SELECT TRIM(LEADING "directive_event:" FROM name) as name from plugin_sid where plugin_id = NEW.plugin_id and sid = NEW.plugin_sid);
        set @title = REPLACE(@title,"DST_IP", inet6_ntop(NEW.dst_ip));
        set @title = REPLACE(@title,"SRC_IP", inet6_ntop(NEW.src_ip));
        set @title = REPLACE(@title,"PROTOCOL", NEW.protocol);
        set @title = REPLACE(@title,"SRC_PORT", NEW.src_port);
        set @title = REPLACE(@title,"DST_PORT", NEW.dst_port);
        set @title = CONCAT(@title, " (", inet6_ntop(NEW.src_ip), ":", CAST(NEW.src_port AS CHAR), " -> ", inet6_ntop(NEW.dst_ip), ":", CAST(NEW.dst_port AS CHAR), ")");

        INSERT INTO incident(uuid,ctx,title,date,ref,type_id,priority,status,last_update,in_charge,submitter,event_start,event_end) values (UNHEX(REPLACE(UUID(),'-','')),NEW.corr_engine_ctx,@title, NEW.timestamp, "Alarm", "Generic", NEW.risk, "Open", NOW(), "admin", "admin", NEW.timestamp, NEW.timestamp);

        set @last_incident_id = (SELECT LAST_INSERT_ID() FROM incident LIMIT 1);
        INSERT INTO incident_alarm(incident_id, src_ips, dst_ips, src_ports, dst_ports, backlog_id, event_id, alarm_group_id) values (@last_incident_id, inet6_ntop(NEW.src_ip), inet6_ntop(NEW.dst_ip), NEW.src_port, NEW.dst_port, NEW.backlog_id, NEW.event_id, 0);

        CALL incident_ticket_populate(@last_incident_id, @tmp_src_ip, @tmp_dst_ip, @tmp_risk);
    END IF;
  END IF;

END$$


DELIMITER ;

REPLACE INTO config (conf, value) VALUES ('last_update', '2012-07-11');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.0.2');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
