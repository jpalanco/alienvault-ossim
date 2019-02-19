-- Intended for/will run after inserting INTO net from Avconfig_profile_database, only when creating/rebuilding database.
-- TRUNCATE net_cidrs;
DROP PROCEDURE IF EXISTS net_convert;
DELIMITER ;;
CREATE PROCEDURE net_convert()
BEGIN
DECLARE done BOOLEAN DEFAULT 0;
DECLARE nid  VARCHAR(32);
DECLARE cidr VARCHAR(15);
DECLARE mask VARCHAR(3);
DECLARE net_list CURSOR FOR SELECT HEX(id) as nid,SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(ips,'\r\n',''), ',', 1), '/', 1),SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(ips,'\r\n',''), ',', 1), '/', -1) FROM net UNION SELECT HEX(id) as nid,SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(ips,'\r\n',''), ',', -1), '/', 1),SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(ips,'\r\n',''), ',', -1), '/', -1) FROM net;
DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done=1;
OPEN net_list;
REPEAT
FETCH net_list INTO nid, cidr, mask;
set @ips = CONCAT(cidr,"/",mask);
SELECT inet_aton(cidr) INTO @begin;
SELECT inet_aton(cidr) + (pow(2, (32-mask))-1) INTO @end;
REPLACE INTO net_cidrs(net_id,cidr,begin,end) VALUES (UNHEX(nid),@ips,inet6_aton(inet_ntoa(@begin)),inet6_aton(inet_ntoa(@end)));
UNTIL done END REPEAT;
CLOSE net_list;
END ;;
DELIMITER ;
CALL net_convert;
DROP PROCEDURE IF EXISTS net_convert;
REPLACE INTO alienvault.host_net_reference SELECT host.id,net_id FROM alienvault.host, alienvault.host_ip, alienvault.net_cidrs WHERE host.id = host_ip.host_id AND host_ip.ip >= net_cidrs.begin AND host_ip.ip <= net_cidrs.end;
    
