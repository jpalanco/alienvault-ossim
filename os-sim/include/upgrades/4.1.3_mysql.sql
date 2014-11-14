USE alienvault;
SET AUTOCOMMIT=0;

REPLACE INTO host_source_reference(id, name, relevance) VALUES (18,'OSSEC', 5);

UPDATE `dashboard_custom_type`   set title_default='Latest SIEM vs Logger Events' WHERE id=1001;
UPDATE `dashboard_widget_config` set title='Latest SIEM vs Logger Events' WHERE id=1;

REPLACE INTO config (conf, value) VALUES ('last_update', '2012-01-31');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '4.1.3');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
