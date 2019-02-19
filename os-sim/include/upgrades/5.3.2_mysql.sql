USE alienvault;
SET autocommit=0;

DELIMITER $$

DROP PROCEDURE IF EXISTS upgrade_databaseRC5_3_2 $$
CREATE DEFINER=`root`@`127.0.0.1` PROCEDURE upgrade_databaseRC5_3_2()
BEGIN

-- add a column safely
IF NOT EXISTS( (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
        AND COLUMN_NAME='salt' AND TABLE_NAME='pass_history') ) THEN
    ALTER TABLE pass_history ADD column salt TEXT NOT NULL;
END IF;
IF NOT EXISTS( (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
        AND COLUMN_NAME='salt' AND TABLE_NAME='users') ) THEN
    ALTER TABLE users ADD column salt TEXT NOT NULL;
END IF;
IF NOT EXISTS( (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
        AND COLUMN_NAME='exclude_ports' AND TABLE_NAME='vuln_job_schedule') ) THEN
	ALTER TABLE vuln_job_schedule ADD column exclude_ports TEXT NOT NULL;
END IF;
IF NOT EXISTS( (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
        AND COLUMN_NAME='exclude_ports' AND TABLE_NAME='vuln_jobs') ) THEN
	ALTER TABLE vuln_jobs ADD column exclude_ports TEXT NOT NULL;
END IF;


END $$

CALL upgrade_databaseRC5_3_2() $$
drop procedure upgrade_databaseRC5_3_2;

DROP PROCEDURE IF EXISTS user_add $$
CREATE DEFINER=`root`@`127.0.0.1` PROCEDURE user_add (
    IN _login VARCHAR(64),
    IN _passwd VARCHAR(128),
	IN _salt VARCHAR(8),
    IN _is_admin INT
)
BEGIN

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

DELIMITER ;

UPDATE users SET first_login=1;

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2016-09-23');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '5.3.2');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA