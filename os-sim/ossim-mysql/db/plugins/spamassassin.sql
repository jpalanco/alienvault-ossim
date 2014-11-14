-- spamassassin
-- plugin_id: 1524
--
-- $Log: spamassassin.sql,v $
-- Revision 1.2  2007/03/26 18:36:15  juanmals
-- delete previous sids before inserting new ones
--
-- Revision 1.1  2006/11/09 23:45:49  dvgil
-- spamassassin plugin, sid=1 -> detected spam
--
DELETE FROM plugin WHERE id = "1524";
DELETE FROM plugin_sid where plugin_id = "1524";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1524, 1, 'spamassassin', 'Spamassassin: Perl-based spam filter using text analysis');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1524, 1, NULL, NULL, 'spamassassin: spam detected');
