-- Delete temporal tables
DROP TABLE IF EXISTS _ttmp_events;
DROP TABLE IF EXISTS alienvault_siem.tmp_events;
DROP TABLE IF EXISTS alienvault_siem._extra_data;
DROP TABLE IF EXISTS alienvault_siem._idm_data;
DROP TABLE IF EXISTS alienvault_siem._reputation_data;
DROP TABLE IF EXISTS alienvault_siem._otx_data;
DROP TABLE IF EXISTS alienvault_siem._acid_event;
DROP TABLE IF EXISTS alienvault_siem._ac_acid_event;
DROP TABLE IF EXISTS alienvault_siem._po_acid_event;

DROP PROCEDURE IF EXISTS _delete_orphans;
DROP PROCEDURE IF EXISTS _clean_devices;

