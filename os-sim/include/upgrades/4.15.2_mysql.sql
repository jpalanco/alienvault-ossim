USE alienvault;
SET AUTOCOMMIT=0;

DELIMITER $$

DROP PROCEDURE IF EXISTS addcol$$
CREATE PROCEDURE addcol() BEGIN

    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'sensor' AND INDEX_NAME = 'ip_UNIQUE')
    THEN
        ALTER TABLE `alienvault`.`sensor` DROP KEY `ip_UNIQUE`;
    END IF;
    
END$$

DELIMITER ;

CALL addcol();
DROP PROCEDURE addcol;

UPDATE custom_report_types SET name=REPLACE(name,'Destionation','Destination') where name like '%Destionation%';
UPDATE custom_report_types SET inputs=REPLACE(inputs,'Destionation','Destination') where inputs like '%Destionation%';

REPLACE INTO `dashboard_custom_type` (`id`, `name`, `type`, `category`, `title_default`, `help_default`, `file`, `params`, `thumb`) VALUES
(1007, 'Ticket Risk', 'gauge', 'SIEM', 'Ticket Risk', 'Ticket Risk', 'widgets/data/gauge.php?type=ticket', 'Level:type:select:OSS_LETTER:max,avg,min::Maximum,Average,Minimum', '1007.png'),
(1008, 'Alarm Risk', 'gauge', 'SIEM', 'Alarm Risk', 'Alarm Risk', 'widgets/data/gauge.php?type=alarm', 'Level:type:select:OSS_LETTER:max,avg,min::Maximum,Average,Minimum', '1008.png'),
(2003, 'Open Ticket Types', 'chart', 'Tickets', 'Open Ticket Types', 'Open ticket grouped by ticket type', 'widgets/data/tickets.php?type=ticketTypes', 'Type:type:select:OSS_LETTER:pie,hbar,vbar::Pie,Horizontal Bar,Vertical Bar;\r\nLegend:legend:radiobuttons:OSS_DIGIT:1,0::Yes,No;\r\nPosition:position:radiobuttons:OSS_LETTER:nw,n,ne,e,se,s,sw,w::North West,North,North East,East,South East,South,South West,West;\r\nLegend Columns:columns:text:OSS_DIGIT:1:4:1;\r\nPlacement:placement:radiobuttons:OSS_LETTER:outsideGrid,insideGrid::Outside Grid,Inside Grid', '2003.png'),
(2004, 'Open Tickets by Class', 'chart', 'Tickets', 'Open Tickets by Class', 'Open tickets grouped by ticket class', 'widgets/data/tickets.php?type=ticketsByClass', 'Type:type:select:OSS_LETTER:pie,hbar,vbar::Pie,Horizontal Bar,Vertical Bar;\r\nLegend:legend:radiobuttons:OSS_DIGIT:1,0::Yes,No;\r\nPosition:position:radiobuttons:OSS_LETTER:nw,n,ne,e,se,s,sw,w::North West,North,North East,East,South East,South,South West,West;\r\nLegend Columns:columns:text:OSS_DIGIT:1:4:1;\r\nPlacement:placement:radiobuttons:OSS_LETTER:outsideGrid,insideGrid::Outside Grid,Inside Grid', '2004.png');

REPLACE INTO `dashboard_widget_config` (`panel_id`, `type_id`, `user`, `col`, `fil`, `height`, `title`, `help`, `refresh`, `color`, `file`, `type`, `asset`, `media`, `params`) VALUES
(2, 2003, '0', 2, 1, 320, 'Open Ticket Types', 'Open tickets grouped by ticket type', 0, 'db_color_2', 'widgets/data/tickets.php?type=ticketTypes', 'chart', 'ALL_ASSETS', NULL, 'a:5:{s:4:"type";s:3:"pie";s:6:"legend";s:1:"1";s:8:"position";s:1:"w";s:7:"columns";s:1:"2";s:9:"placement";s:10:"insideGrid";}'),
(2, 2004, '0', 1, 1, 320, 'Open Tickets by Class', 'Open tickets grouped by ticket class', 0, 'db_color_2', 'widgets/data/tickets.php?type=ticketsByClass', 'chart', 'ALL_ASSETS', NULL, 'a:5:{s:4:"type";s:3:"pie";s:6:"legend";s:1:"1";s:8:"position";s:1:"w";s:7:"columns";s:1:"2";s:9:"placement";s:10:"insideGrid";}');

REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp());
REPLACE INTO config (conf, value) VALUES ('last_update', '2015-03-03');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.15.2');

COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
