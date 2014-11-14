
-- New table structure

drop table if exists alienvault.post_correlation;

create table if not exists alienvault.post_correlation (
  `id` int(11) NOT NULL auto_increment,
  timestamp TIMESTAMP,
  src_ip          INTEGER UNSIGNED DEFAULT 0,
  dst_ip          INTEGER UNSIGNED DEFAULT 0,
  ocurrences 	  INTEGER DEFAULT 0,
  src_port        INTEGER DEFAULT 0,
  dst_port        INTEGER DEFAULT 0,
  plugin_id       INTEGER NOT NULL,
  plugin_sid      INTEGER NOT NULL DEFAULT 9999,
  PRIMARY KEY  (`id`)
);

-- Functions & Events

DROP PROCEDURE IF EXISTS post_correlation_check_new_ips1;

DELIMITER '|'

CREATE PROCEDURE post_correlation_check_new_ips1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select *, 12001, 2 from (select ip_src,count(ip_src) as cnt from alienvault_siem.acid_event where timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by ip_src) as today where ip_src not in (select distinct ip_src from alienvault_siem.acid_event where timestamp BETWEEN DATE_SUB(CURRENT_TIMESTAMP(),INTERVAL 7 DAY) AND DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) and cnt>=5;

END
|

DELIMITER ';'

--

DROP PROCEDURE IF EXISTS select_post_correlation_index;

DELIMITER '|'

CREATE PROCEDURE select_post_correlation_index()
BEGIN
IF EXISTS
        (SELECT id FROM alienvault.post_correlation)
THEN
	SELECT id FROM alienvault.post_correlation order by id desc limit 1;
ELSE
	SELECT 1 as id;
        END IF;
END
|

DELIMITER ';'


DROP EVENT IF EXISTS check_new_ips1_schedule;

CREATE EVENT check_new_ips1_schedule ON SCHEDULE 
EVERY 1 DAY
STARTS TIMESTAMP(NOW())
COMMENT 'Check for new ips that have not appeared on the past 7 days'
DO CALL alienvault.post_correlation_check_new_ips1();


-- Automatically generated functions and events for taxonomy

DROP PROCEDURE IF EXISTS post_correlation_check_too_many_exploit_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_exploit_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 51 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 1 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_exploit_events1_schedule;

CREATE EVENT check_too_many_exploit_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '21' HOUR
COMMENT 'Check for IPS with more than 3 different Exploit events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_exploit_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_exploit_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_exploit_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 501 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 1 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_exploit_events1_dstnct_sched;

CREATE EVENT check_too_many_exploit_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '19' HOUR
COMMENT 'Check for IPS with more than 3 different Exploit events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_exploit_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_authentication_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_authentication_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 52 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 2 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_authentication_events1_schedule;

CREATE EVENT check_too_many_authentication_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '8' HOUR
COMMENT 'Check for IPS with more than 3 different Authentication events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_authentication_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_authentication_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_authentication_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 502 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 2 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_authentication_events1_dstnct_sched;

CREATE EVENT check_too_many_authentication_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '0' HOUR
COMMENT 'Check for IPS with more than 3 different Authentication events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_authentication_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_access_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_access_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 53 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 3 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_access_events1_schedule;

CREATE EVENT check_too_many_access_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '21' HOUR
COMMENT 'Check for IPS with more than 3 different Access events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_access_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_access_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_access_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 503 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 3 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_access_events1_dstnct_sched;

CREATE EVENT check_too_many_access_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '10' HOUR
COMMENT 'Check for IPS with more than 3 different Access events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_access_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_malware_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_malware_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 54 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 4 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_malware_events1_schedule;

CREATE EVENT check_too_many_malware_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '6' HOUR
COMMENT 'Check for IPS with more than 3 different Malware events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_malware_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_malware_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_malware_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 504 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 4 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_malware_events1_dstnct_sched;

CREATE EVENT check_too_many_malware_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '14' HOUR
COMMENT 'Check for IPS with more than 3 different Malware events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_malware_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_policy_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_policy_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 55 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 5 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_policy_events1_schedule;

CREATE EVENT check_too_many_policy_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '22' HOUR
COMMENT 'Check for IPS with more than 3 different Policy events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_policy_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_policy_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_policy_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 505 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 5 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_policy_events1_dstnct_sched;

CREATE EVENT check_too_many_policy_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '4' HOUR
COMMENT 'Check for IPS with more than 3 different Policy events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_policy_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_denial_of_service_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_denial_of_service_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 56 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 6 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_denial_of_service_events1_schedule;

CREATE EVENT check_too_many_denial_of_service_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '6' HOUR
COMMENT 'Check for IPS with more than 3 different Denial_Of_Service events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_denial_of_service_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_denial_of_service_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_denial_of_service_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 506 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 6 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_denial_of_service_events1_dstnct_sched;

CREATE EVENT check_too_many_denial_of_service_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '2' HOUR
COMMENT 'Check for IPS with more than 3 different Denial_Of_Service events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_denial_of_service_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_suspicious_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_suspicious_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 57 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 7 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_suspicious_events1_schedule;

CREATE EVENT check_too_many_suspicious_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '0' HOUR
COMMENT 'Check for IPS with more than 3 different Suspicious events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_suspicious_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_suspicious_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_suspicious_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 507 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 7 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_suspicious_events1_dstnct_sched;

CREATE EVENT check_too_many_suspicious_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '2' HOUR
COMMENT 'Check for IPS with more than 3 different Suspicious events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_suspicious_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_network_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_network_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 58 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 8 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_network_events1_schedule;

CREATE EVENT check_too_many_network_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '2' HOUR
COMMENT 'Check for IPS with more than 3 different Network events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_network_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_network_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_network_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 508 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 8 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_network_events1_dstnct_sched;

CREATE EVENT check_too_many_network_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '11' HOUR
COMMENT 'Check for IPS with more than 3 different Network events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_network_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_recon_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_recon_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 59 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 9 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_recon_events1_schedule;

CREATE EVENT check_too_many_recon_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '2' HOUR
COMMENT 'Check for IPS with more than 3 different Recon events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_recon_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_recon_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_recon_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 509 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 9 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_recon_events1_dstnct_sched;

CREATE EVENT check_too_many_recon_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '22' HOUR
COMMENT 'Check for IPS with more than 3 different Recon events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_recon_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_info_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_info_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 60 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 10 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_info_events1_schedule;

CREATE EVENT check_too_many_info_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '19' HOUR
COMMENT 'Check for IPS with more than 3 different Info events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_info_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_info_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_info_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 510 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 10 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_info_events1_dstnct_sched;

CREATE EVENT check_too_many_info_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '5' HOUR
COMMENT 'Check for IPS with more than 3 different Info events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_info_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_system_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_system_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 61 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 11 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_system_events1_schedule;

CREATE EVENT check_too_many_system_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '22' HOUR
COMMENT 'Check for IPS with more than 3 different System events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_system_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_system_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_system_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 511 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 11 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_system_events1_dstnct_sched;

CREATE EVENT check_too_many_system_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '12' HOUR
COMMENT 'Check for IPS with more than 3 different System events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_system_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_antivirus_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_antivirus_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 62 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 12 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_antivirus_events1_schedule;

CREATE EVENT check_too_many_antivirus_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '13' HOUR
COMMENT 'Check for IPS with more than 3 different Antivirus events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_antivirus_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_antivirus_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_antivirus_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 512 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 12 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_antivirus_events1_dstnct_sched;

CREATE EVENT check_too_many_antivirus_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '1' HOUR
COMMENT 'Check for IPS with more than 3 different Antivirus events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_antivirus_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_application_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_application_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 63 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 13 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_application_events1_schedule;

CREATE EVENT check_too_many_application_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '14' HOUR
COMMENT 'Check for IPS with more than 3 different Application events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_application_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_application_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_application_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 513 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 13 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_application_events1_dstnct_sched;

CREATE EVENT check_too_many_application_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '12' HOUR
COMMENT 'Check for IPS with more than 3 different Application events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_application_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_voip_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_voip_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 64 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 14 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_voip_events1_schedule;

CREATE EVENT check_too_many_voip_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '6' HOUR
COMMENT 'Check for IPS with more than 3 different Voip events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_voip_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_voip_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_voip_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 514 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 14 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_voip_events1_dstnct_sched;

CREATE EVENT check_too_many_voip_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '2' HOUR
COMMENT 'Check for IPS with more than 3 different Voip events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_voip_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_alert_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_alert_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 65 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 15 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_alert_events1_schedule;

CREATE EVENT check_too_many_alert_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '18' HOUR
COMMENT 'Check for IPS with more than 3 different Alert events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_alert_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_alert_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_alert_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 515 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 15 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_alert_events1_dstnct_sched;

CREATE EVENT check_too_many_alert_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '5' HOUR
COMMENT 'Check for IPS with more than 3 different Alert events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_alert_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_availability_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_availability_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 66 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 16 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_availability_events1_schedule;

CREATE EVENT check_too_many_availability_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '6' HOUR
COMMENT 'Check for IPS with more than 3 different Availability events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_availability_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_availability_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_availability_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 516 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 16 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_availability_events1_dstnct_sched;

CREATE EVENT check_too_many_availability_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '12' HOUR
COMMENT 'Check for IPS with more than 3 different Availability events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_availability_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_wireless_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_wireless_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 67 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 17 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_wireless_events1_schedule;

CREATE EVENT check_too_many_wireless_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '2' HOUR
COMMENT 'Check for IPS with more than 3 different Wireless events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_wireless_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_wireless_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_wireless_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 517 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 17 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_wireless_events1_dstnct_sched;

CREATE EVENT check_too_many_wireless_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '4' HOUR
COMMENT 'Check for IPS with more than 3 different Wireless events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_wireless_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_inventory_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_inventory_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 68 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 18 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_inventory_events1_schedule;

CREATE EVENT check_too_many_inventory_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '21' HOUR
COMMENT 'Check for IPS with more than 3 different Inventory events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_inventory_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_inventory_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_inventory_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 518 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 18 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_inventory_events1_dstnct_sched;

CREATE EVENT check_too_many_inventory_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '17' HOUR
COMMENT 'Check for IPS with more than 3 different Inventory events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_inventory_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_honeypot_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_honeypot_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 69 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 19 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_honeypot_events1_schedule;

CREATE EVENT check_too_many_honeypot_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '18' HOUR
COMMENT 'Check for IPS with more than 3 different Honeypot events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_honeypot_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_honeypot_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_honeypot_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 519 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 19 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_honeypot_events1_dstnct_sched;

CREATE EVENT check_too_many_honeypot_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '2' HOUR
COMMENT 'Check for IPS with more than 3 different Honeypot events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_honeypot_dstnct_evts1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_database_events1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_database_events1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select e.ip_src as ip_src,count(ip_src) as cnt, 12001, 70 from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 20 and e.timestamp>=DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) group by e.ip_src having cnt>=200;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_database_events1_schedule;

CREATE EVENT check_too_many_database_events1_schedule ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '3' HOUR
COMMENT 'Check for IPS with more than 3 different Database events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_database_events1();



DROP PROCEDURE IF EXISTS post_correlation_check_too_many_database_dstnct_evts1;
DELIMITER '|'

CREATE PROCEDURE post_correlation_check_too_many_database_dstnct_evts1()
BEGIN
insert into alienvault.post_correlation(src_ip, ocurrences, plugin_id, plugin_sid) select ip_src,count(*) as cnt,12001, 520 from (select distinct e.plugin_id,e.plugin_sid, e.ip_src from alienvault_siem.acid_event as e, alienvault.plugin_sid as s where  e.plugin_id=s.plugin_id and e.plugin_sid = s.sid and s.category_id= 20 and e.timestamp > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)) as unnique group by ip_src having cnt>=3;
END
|

DELIMITER ';'

DROP EVENT IF EXISTS check_too_many_database_events1_dstnct_sched;

CREATE EVENT check_too_many_database_events1_dstnct_sched ON SCHEDULE
EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL '6' HOUR
COMMENT 'Check for IPS with more than 3 different Database events during the last 24 hours'
DO CALL alienvault.post_correlation_check_too_many_database_dstnct_evts1();

