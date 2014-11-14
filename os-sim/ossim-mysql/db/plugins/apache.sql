-- apache
-- plugin_id: 1501

DELETE FROM plugin WHERE id = "1501";
DELETE FROM plugin_sid where plugin_id = "1501";

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (1501, 1, 'apache', 'Apache');

INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 1, NULL, NULL, 'Apache: server error [emerg]', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 2, NULL, NULL, 'Apache: server error [alert]', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 3, NULL, NULL, 'Apache: server error [crit]', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 4, NULL, NULL, 'Apache: server error [error]', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 5, NULL, NULL, 'Apache: server error [warn]', 2, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 6, NULL, NULL, 'Apache: server error [notice]', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 7, NULL, NULL, 'Apache: server error [info]', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 8, NULL, NULL, 'Apache: server error [debug]', 0, 0);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 200, NULL, NULL, 'Apache: OK', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 201, NULL, NULL, 'Apache: Created');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 202, NULL, NULL, 'Apache: Accepted');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 203, NULL, NULL, 'Apache: Non-Authorative Information');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 204, NULL, NULL, 'Apache: No Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 205, NULL, NULL, 'Apache: Reset Content');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 206, NULL, NULL, 'Apache: Partial Content', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 300, NULL, NULL, 'Apache: Multiple Choices');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 301, NULL, NULL, 'Apache: Moved Permanently');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 302, NULL, NULL, 'Apache: Moved Temporarily');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 303, NULL, NULL, 'Apache: See Other');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 304, NULL, NULL, 'Apache: Not Modified', 1, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 305, NULL, NULL, 'Apache: Use Proxy');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 307, NULL, NULL, 'Apache: Temporary Redirect');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 400, NULL, NULL, 'Apache: Bad Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 401, NULL, NULL, 'Apache: Unauthorized', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 402, NULL, NULL, 'Apache: Payment Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 403, NULL, NULL, 'Apache: Forbidden', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 404, NULL, NULL, 'Apache: Not Found');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 405, NULL, NULL, 'Apache: Method Not Allowed', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 406, NULL, NULL, 'Apache: Not Acceptable (encoding)');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 407, NULL, NULL, 'Apache: Proxy Authentication Required', 3, 1);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 408, NULL, NULL, 'Apache: Request Timed Out');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 409, NULL, NULL, 'Apache: Conflicting Request');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 410, NULL, NULL, 'Apache: Gone');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 411, NULL, NULL, 'Apache: Content Length Required');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 412, NULL, NULL, 'Apache: Precondition Failed');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 413, NULL, NULL, 'Apache: Request Entity Too Long', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 414, NULL, NULL, 'Apache: Request URI Too Long', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 415, NULL, NULL, 'Apache: Unsupported Media Type');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 500, NULL, NULL, 'Apache: Internal Server Error', 2, 2);
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 501, NULL, NULL, 'Apache: Not implemented');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 502, NULL, NULL, 'Apache: Bad Gateway');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 503, NULL, NULL, 'Apache: Service Unavailable');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1501, 504, NULL, NULL, 'Apache: Gateway Timeout');
INSERT IGNORE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1501, 505, NULL, NULL, 'Apache: HTTP Version Not Supported', 2, 2);
