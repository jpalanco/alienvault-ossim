USE alienvault_api;
SET autocommit=0;

ALTER TABLE current_status MODIFY COLUMN `creation_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(CURRENT_TIMESTAMP | on create CURRENT_TIMESTAMP )\n';

USE alienvault;

DELIMITER $$

DROP PROCEDURE IF EXISTS fill_tables $$
CREATE DEFINER=`root`@`127.0.0.1` PROCEDURE fill_tables(
    IN date_from VARCHAR(19),
    IN date_to VARCHAR(19)
)
BEGIN
    ANALYZE TABLE acid_event;
    IF date_from <> '' AND date_to <> '' THEN
        DELETE FROM po_acid_event WHERE timestamp BETWEEN date_from AND date_to;
        REPLACE INTO po_acid_event (select ctx, device_id, plugin_id, plugin_sid, ip_src, ip_dst, DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00'), IFNULL(src_host,0x00000000000000000000000000000000), IFNULL(dst_host,0x00000000000000000000000000000000), IFNULL(src_net,0x00000000000000000000000000000000), IFNULL(dst_net,0x00000000000000000000000000000000), count(*) FROM acid_event  WHERE timestamp BETWEEN date_from AND date_to GROUP BY 1,2,3,4,5,6,7,8,9,10,11);
        DELETE FROM ac_acid_event WHERE timestamp BETWEEN date_from AND date_to;
        INSERT INTO ac_acid_event (select ctx, device_id, plugin_id, plugin_sid, timestamp, src_host, dst_host, src_net, dst_net, sum(cnt) cnt FROM po_acid_event WHERE timestamp BETWEEN date_from AND date_to GROUP BY 1,2,3,4,5,6,7,8,9) ON duplicate key UPDATE cnt = values(cnt);
    ELSE
        TRUNCATE TABLE po_acid_event;
        REPLACE INTO po_acid_event (select ctx, device_id, plugin_id, plugin_sid, ip_src, ip_dst, DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00'), IFNULL(src_host,0x00000000000000000000000000000000), IFNULL(dst_host,0x00000000000000000000000000000000), IFNULL(src_net,0x00000000000000000000000000000000), IFNULL(dst_net,0x00000000000000000000000000000000), count(*) FROM acid_event GROUP BY 1,2,3,4,5,6,7,8,9,10,11);
        TRUNCATE TABLE ac_acid_event;
        REPLACE INTO ac_acid_event (select ctx, device_id, plugin_id, plugin_sid, DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00'), IFNULL(src_host,0x00000000000000000000000000000000), IFNULL(dst_host,0x00000000000000000000000000000000), IFNULL(src_net,0x00000000000000000000000000000000), IFNULL(dst_net,0x00000000000000000000000000000000), count(*) FROM acid_event GROUP BY 1,2,3,4,5,6,7,8,9);
    END IF;
END
$$

DROP PROCEDURE IF EXISTS user_add $$
CREATE DEFINER=`root`@`127.0.0.1` PROCEDURE user_add (
    IN _login VARCHAR(64),
    IN _passwd VARCHAR(128),
    IN _salt VARCHAR(8),
    IN _is_admin INT
)
BEGIN
    SET _passwd = SHA2(CONCAT(_salt,_passwd), 256);
    IF EXISTS (SELECT 1 FROM users WHERE users.login=_login)
    THEN
        SELECT CONCAT(_login,' already exist') as status;
    ELSE
        SET @uuid = SUBSTRING(SHA1(CONCAT(_login,'#',_passwd)),1,32);
        SELECT HEX(id) FROM acl_templates LIMIT 1 INTO @template_id;
        INSERT INTO users (login, login_method, name, pass, email, company, department, template_id, language, first_login, timezone, is_admin, uuid, last_logon_try, salt) VALUES (_login, 'pass', _login, _passwd, '', '', '', UNHEX(@template_id), 'en_GB', 0, 'US/Eastern', _is_admin, UNHEX(@uuid), now(), _salt);
        INSERT INTO acl_entities_users (login, entity_id) VALUES (_login, (SELECT UNHEX(REPLACE (value, '-', '')) FROM config WHERE conf = 'default_context_id'));
        INSERT INTO dashboard_tab_options (`id`, `user`, `visible`, `tab_order`) VALUES (1, _login, 1, 11), (2, _login, 1, 10), (3, _login, 1, 9), (4, _login, 1, 8), (5, _login, 0, 7), (6, _login, 1, 6), (8, _login, 0, 5), (9, _login, 0, 4), (10, _login, 0, 3), (11, _login, 0, 2), (12, _login, 0, 1);
        IF _is_admin=0 THEN
            CALL acl_user_permissions(_login);
        END IF;
        SELECT CONCAT(_login,' has been successfully created') as status;
    END IF;

END $$

DROP PROCEDURE IF EXISTS system_update$$
CREATE PROCEDURE system_update(
    _system_id VARCHAR(36),
    _name      VARCHAR(64),
    _admin_ip  VARCHAR(64),
    _vpn_ip    VARCHAR(64),
    _profile   VARCHAR(64),
    _ha_ip     VARCHAR(64),
    _ha_name   VARCHAR(64),
    _ha_role   VARCHAR(64),
    _sensor_id VARCHAR(64),
    _server_id VARCHAR(64)
)
UPDATE_SYSTEM:BEGIN

    SELECT REPLACE(_system_id,'-','') into @system_id;

    IF NOT EXISTS(SELECT 1 FROM alienvault.system WHERE id=UNHEX(@system_id)) THEN

        -- create new one if it's possible

        IF (_system_id != '' AND _name != '' AND _admin_ip != '' AND _profile != '') THEN

            SELECT IF (_sensor_id='',NULL,REPLACE(_sensor_id,'-','')) into @sensor_id;
            SELECT IF (_server_id='',NULL,REPLACE(_server_id,'-','')) into @server_id;
            REPLACE INTO `system` (id,name,admin_ip,vpn_ip,profile,ha_ip,ha_name,ha_role,sensor_id,server_id) VALUES (UNHEX(@system_id), _name, inet6_aton(_admin_ip), inet6_aton(_vpn_ip), _profile, inet6_aton(_ha_ip), _ha_name, _ha_role, UNHEX(@sensor_id), UNHEX(@server_id));

        ELSE

            SELECT CONCAT('It needs al least system uuid, name, admin_ip and profile to create a new system') as status;
            LEAVE UPDATE_SYSTEM;

        END IF;

        SELECT CONCAT('System ',_system_id,' created') as status;

    ELSE

        -- update each field

        IF (_sensor_id != '') THEN
            UPDATE alienvault.system SET sensor_id=UNHEX(REPLACE(_sensor_id,'-','')) WHERE id=UNHEX(@system_id);
        END IF;

        IF (_server_id != '') THEN
            UPDATE alienvault.system SET server_id=UNHEX(REPLACE(_server_id,'-','')) WHERE id=UNHEX(@system_id);
        END IF;

        IF (_name != '') THEN
            UPDATE alienvault.system SET name=_name WHERE id=UNHEX(@system_id);

            -- name populate in server/sensor
            SELECT HEX(sensor_id),HEX(server_id),name FROM alienvault.system WHERE id=UNHEX(@system_id) into @sensor_id, @server_id, @system_name;

            UPDATE server SET name=@system_name WHERE id=UNHEX(@server_id);

            UPDATE sensor SET name=@system_name WHERE id=UNHEX(@sensor_id);

        END IF;

        IF (_profile != '') THEN
            UPDATE alienvault.system SET profile=_profile WHERE id=UNHEX(@system_id);
        END IF;

        IF (_ha_ip != '' AND _ha_name != '' AND _ha_role != '') THEN
            UPDATE alienvault.system SET ha_ip=inet6_aton(_ha_ip), ha_name=_ha_name, ha_role=_ha_role WHERE id=UNHEX(@system_id);
        END IF;

        IF (_admin_ip != '' OR _vpn_ip != '') THEN

            -- admin_ip or vpn_ip populate in server/sensor
            IF (_admin_ip != '') THEN
                UPDATE alienvault.system SET admin_ip=inet6_aton(_admin_ip) WHERE id=UNHEX(@system_id);
            END IF;

            IF (_vpn_ip != '') THEN
                UPDATE alienvault.system SET vpn_ip=inet6_aton(_vpn_ip) WHERE id=UNHEX(@system_id);
            END IF;

            -- Populate admin_ip if the system is not HA
            SELECT inet6_ntoa(ha_ip) FROM alienvault.system WHERE id=UNHEX(@system_id) into @ha_ip;

            IF @ha_ip IS NULL OR @ha_ip = '' THEN
                SELECT HEX(sensor_id),HEX(server_id),inet6_ntoa(admin_ip),inet6_ntoa(vpn_ip) FROM alienvault.system WHERE id=UNHEX(@system_id) into @sensor_id, @server_id, @admin_ip, @vpn_ip;

                UPDATE server SET ip=IFNULL(inet6_aton(@vpn_ip),inet6_aton(@admin_ip)) WHERE id=UNHEX(@server_id);

                UPDATE sensor SET ip=IFNULL(inet6_aton(@vpn_ip),inet6_aton(@admin_ip)) WHERE id=UNHEX(@sensor_id);
            END IF;

        END IF;

        CALL _host_default_os();

        SELECT CONCAT('System ',_system_id,' updated') as status;

    END IF;

END$$

DELIMITER ;

alter table custom_report_scheduler add column file_type text default null;

INSERT INTO device_types (id,name,class) VALUES

(10,'Medical Device',0),
(128,'DMZ Server',1),
(129,'Internal Server',1),
(130,'Backup Server',1),
(131,'DHCP Server',1),
(1001,'Other',10),
(1002,'High Priority',10);


INSERT IGNORE INTO config (conf, value) VALUES
('tcp_max_download',0),
('tcp_max_upload',0),
('udp_max_download',0),
('udp_max_upload',0),
('agg_function',0),
('inspection_window',0);

SELECT UNHEX(REPLACE(value, '-', '')) FROM config WHERE conf = 'default_context_id' into @default_ctx;

REPLACE INTO log_config (ctx, code, log, descr, priority) VALUES
(@default_ctx, 015, 1, 'Reports - Incident %1% modified', 1),
(@default_ctx, 016, 1, 'Reports - Incident %1% deleted', 3),
(@default_ctx, 046, 1, 'Policy (%1%) %2% by %3%', 2),
(@default_ctx, 047, 1, 'Policy (%1%) %2% by %3%', 2),
(@default_ctx, 048, 1, 'Policy (%1%) %2% by %3%', 2),
(@default_ctx, 099, 1, 'Policy Group - Order: change from %1% to %2%', 2),
(@default_ctx, 100, 1, 'HIDS-agent (%2%) deployed to %3%(%4%)  by %1% ', 2),
(@default_ctx, 101, 1, 'User %1% has changed HIDS configuration file: %2% ', 2),
(@default_ctx, 102, 1, 'HIDS-agent (%2%)  deleted from %3%(%4%)  by %1% ', 2),
(@default_ctx, 103, 1, 'Agentless HIDS deployed to host-server %2%(%3%) by %1% ', 2),
(@default_ctx, 104, 1, 'Agentless HIDS deleted from  host-server %2%(%3%) by %1% ', 2),
(@default_ctx, 105, 1, 'Agentless HIDS   %2%(%3%)  has been changed  by %1% ', 2),
(@default_ctx, 106, 1, 'Policy (%1%) %2% by %3%', 2);


ALTER TABLE plugin_data ADD column product_type INT NOT NULL;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2017-04-01');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.4.0');

-- PLEASE ADD NOTHING HERE

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
